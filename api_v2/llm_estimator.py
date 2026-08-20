import os
import json
import re
import time
import logging
import urllib.request
import base64
from typing import Dict, Any, Optional, List

from google import genai
from google.genai import types
from dotenv import load_dotenv

from schemas import DynamicTakeoffResponse

load_dotenv()
logger = logging.getLogger(__name__)

# Default API key and settings from environment
DEFAULT_GEMINI_KEY = os.getenv("GEMINI_API_KEY", "")
DEFAULT_MODEL = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")
FALLBACK_MODELS = ["gemini-2.5-flash", "gemini-2.5-flash-preview-05-20", "gemini-2.0-flash"]

class CADLLMEstimator:
    """
    Direct AI WBS Takeoff Estimator powered by Google Gemini.
    Generates dynamic WBS sections and work items directly from AI without post-processing mapping.
    """

    def __init__(self, api_key: Optional[str] = None, model: Optional[str] = None):
        self.api_key = api_key or DEFAULT_GEMINI_KEY
        self.model = model or DEFAULT_MODEL
        
        self.primary_api_base = os.getenv("PRIMARY_API_BASE", "https://bandelbanget.xyz/v1")
        self.primary_api_key = os.getenv("PRIMARY_API_KEY", "")
        self.primary_model = os.getenv("PRIMARY_MODEL", "gpt-5.6")

        if self.api_key:
            try:
                self.client = genai.Client(
                    api_key=self.api_key,
                    http_options=types.HttpOptions(timeout=300000)
                )
            except Exception as e:
                logger.warning(f"Could not initialize genai.Client: {e}")
                self.client = None
        else:
            logger.warning("GEMINI_API_KEY is not set in environment (.env). AI requests will fail until an API key is provided.")
            self.client = None

    def _analyze_via_primary_api(self, prompt_content: str, system_prompt: str, project_name: str, client_name: str, media_bytes: Optional[bytes] = None, mime_type: Optional[str] = None) -> Optional[DynamicTakeoffResponse]:
        """Call the primary OpenAI-compatible API to perform estimation with fallback check."""
        if not self.primary_api_key or not self.primary_api_base:
            logger.warning("Primary API key or base URL is not configured. Skipping primary API.")
            return None

        url = f"{self.primary_api_base.rstrip('/')}/chat/completions"
        headers = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer {self.primary_api_key}",
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }

        # Prepare messages
        messages = [
            {"role": "system", "content": system_prompt},
        ]

        if media_bytes:
            if mime_type and mime_type.startswith("image/"):
                try:
                    import io
                    from PIL import Image
                    img = Image.open(io.BytesIO(media_bytes))
                    if img.mode != "RGB":
                        img = img.convert("RGB")
                    img.thumbnail((1600, 1600), Image.Resampling.LANCZOS)
                    out_buf = io.BytesIO()
                    img.save(out_buf, format="JPEG", quality=85)
                    compressed_bytes = out_buf.getvalue()
                    media_b64 = base64.b64encode(compressed_bytes).decode("utf-8")
                    media_mime = "image/jpeg"
                    logger.info(f"Compressed image from {len(media_bytes)} bytes to {len(compressed_bytes)} bytes JPEG base64.")
                except Exception as compress_err:
                    logger.warning(f"Image compression failed ({compress_err}), using raw media bytes.")
                    media_b64 = base64.b64encode(media_bytes).decode("utf-8")
                    media_mime = mime_type or "image/jpeg"
            else:
                media_b64 = base64.b64encode(media_bytes).decode("utf-8")
                media_mime = mime_type or "application/pdf"

            messages.append({
                "role": "user",
                "content": [
                    {"type": "text", "text": prompt_content},
                    {
                        "type": "image_url",
                        "image_url": {
                            "url": f"data:{media_mime};base64,{media_b64}"
                        }
                    }
                ]
            })
        else:
            messages.append({"role": "user", "content": prompt_content})

        # Primary API Call: Try stream=False first for clean JSON response
        payload = {
            "model": self.primary_model,
            "messages": messages,
            "temperature": 0.0,
            "stream": False,
            "response_format": {"type": "json_object"}
        }

        logger.info(f"Calling primary API ({self.primary_model}) at {url}...")
        try:
            req = urllib.request.Request(
                url,
                data=json.dumps(payload).encode("utf-8"),
                headers=headers,
                method="POST"
            )
            with urllib.request.urlopen(req, timeout=180) as resp:
                res_data = json.loads(resp.read().decode("utf-8"))
                choices = res_data.get("choices", [])
                if choices:
                    msg = choices[0].get("message", {})
                    raw_text = msg.get("content") or msg.get("reasoning_content") or ""
                    if raw_text.strip():
                        parsed_dict = self._clean_and_parse_json(raw_text)
                        return DynamicTakeoffResponse(**parsed_dict)

            # If non-streaming returned empty, fallback to streaming SSE
            logger.warning("Non-streaming call returned empty, trying SSE streaming...")
            payload["stream"] = True
            req_stream = urllib.request.Request(
                url,
                data=json.dumps(payload).encode("utf-8"),
                headers=headers,
                method="POST"
            )
            raw_text_acc = []
            reasoning_acc = []
            with urllib.request.urlopen(req_stream, timeout=180) as resp_s:
                for line in resp_s:
                    line_str = line.decode("utf-8", errors="ignore").strip()
                    if line_str.startswith("data:"):
                        data_part = line_str[5:].strip()
                        if data_part == "[DONE]":
                            continue
                        try:
                            chunk = json.loads(data_part)
                            choices = chunk.get("choices", [])
                            if choices:
                                delta = choices[0].get("delta", {})
                                c = delta.get("content")
                                r = delta.get("reasoning_content")
                                if c is not None:
                                    raw_text_acc.append(str(c))
                                if r is not None:
                                    reasoning_acc.append(str(r))
                        except Exception:
                            pass

            raw_text = "".join(raw_text_acc).strip()
            if not raw_text and reasoning_acc:
                raw_text = "".join(reasoning_acc).strip()

            if not raw_text:
                logger.warning("Primary API returned empty text.")
                return None

            parsed_dict = self._clean_and_parse_json(raw_text)
            return DynamicTakeoffResponse(**parsed_dict)
        except Exception as e:
            logger.warning(f"Primary API call failed: {e}. Falling back to Gemini.")
            return None


    def _get_model_candidates(self) -> List[str]:
        candidates = [self.model]
        for m in FALLBACK_MODELS:
            if m not in candidates:
                candidates.append(m)
        return candidates

    def analyze_cad_payload(self, text_payload: str, project_name: str = "Proyek CAD DWG", client_name: str = "Client") -> DynamicTakeoffResponse:
        """
        Send formatted CAD payload to Gemini LLM for direct dynamic WBS Quantity Takeoff estimation.
        """
        system_prompt = (
            "You are a professional Senior Quantity Surveyor (QS) in Indonesia. "
            "Analyze the provided pure vector CAD drawing entities (layers, text notations, dimensions, geometry measurements, schedule tables extracted from DWG/DXF files). "
            "Directly generate complete WBS (Work Breakdown Structure) sections and work items according to real Indonesian Civil Engineering standards. "
            "CRITICAL CAD SCALE & UNIT NORMALIZATION RULES: "
            "1. CAD UNIT NORMALIZATION: CAD drawings in Indonesia are drawn in Millimeters (mm) or Centimeters (cm). "
            "   - If CAD dimensions say '6000' x '10000' or '600' x '1000', convert to METERS: 6.0m x 10.0m = 60.0 m2 plot area! "
            "   - NEVER treat raw CAD millimeter or centimeter values directly as meters or m2! "
            "2. SANITY CHECK ON AREA & FOOTPRINT: "
            "   - 'Pembersihan Lapangan' & 'Bouwplank' MUST match the real building/site footprint area (typically 30 m2 to 500 m2 for residential/building drawings). "
            "3. DETERMINISTIC STRUCTURAL FALLBACK SPECIFICATIONS (Use strictly when unstated in CAD): "
            "   - Wall height = 3.0 meters. "
            "   - Foundation trench depth = 0.8 meters, bottom width = 0.7 meters, top width = 0.5 meters. "
            "   - Footplat P1 (if present without explicit dimensions) = 1.0m x 1.0m x 0.4m. "
            "   - Sloof S1 = 0.15m x 0.20m. Kolom K1 = 0.15m x 0.15m x 3.0m. "
            "   - Sand bed thickness = 0.05m. Aanstamping thickness = 0.10m. "
            "4. ATOMIC VOLUME RULES: "
            "   - EVERY WORK ITEM MUST HAVE A REALISTIC POSITIVE VOLUME (> 0.0). NEVER RETURN 0 OR 0.0 FOR VOLUME! "
            "   - Concrete/Foundation/Excavation (m3): length x width x depth. "
            "   - Area (m2): wall area, plastering, floor area, site area. "
            "   - Separate PEKERJAAN TANAH (galian, urugan, pemadatan) from PEKERJAAN PONDASI (batu belah, footplat, pancang). "
            "   - Put clear mathematical calculation steps in `warning_note` (e.g. 'Site footprint 6.0m x 10.0m = 60.0 m2'). "
            "Output JSON directly conforming to the DynamicTakeoffResponse schema."
        )

        prompt_content = f"""Judul Gambar CAD: '{project_name}'
Klien: '{client_name}'

=== CAD VECTOR ENTITIES & ANNOTATIONS DUMP ===
{text_payload}

Tugas QS:
1. Periksa notasi dimensi CAD & tentukan skala unit (mm atau cm ke meter).
2. Lakukan Sanity Check Luas Tapak/Bangunan: Pastikan luas 'Pembersihan Lapangan' & 'Bouwplank' realistis sesuai ukuran tanah (contoh: 6m x 10m = 60 m2, BUKAN ribuan m2).
3. Lakukan Material Takeoff & Perhitungan Volume Kuantitas Riil untuk SETIAP item pekerjaan.
4. Pisahkan seksi WBS secara spesifik (misal: Pekerjaan Tanah TERPISAH dari Pekerjaan Pondasi).

Contoh Struktur JSON Output:
{{
  "project": {{
    "title": "{project_name}",
    "client": "{client_name}",
    "status": "Perencanaan"
  }},
  "project_summary": "Ringkasan analisis kuantitas CAD dari AI",
  "wbs_sections": [
    {{
      "section": {{
        "id": "sec-A",
        "type": "section",
        "code": "A",
        "name": "PEKERJAAN PERSIAPAN & K3"
      }},
      "items": [
        {{
          "id": "item-A-1",
          "type": "item",
          "sectionCode": "A",
          "no": 1,
          "code": "A.1",
          "name": "Pembersihan Lapangan & Pembuatan Bouwplank",
          "volume": 120.0,
          "unit": "m2",
          "confidence": "high",
          "warning_note": "Berdasarkan luas bangunan denah"
        }}
      ]
    }}
  ]
}}
"""

        # Try Primary API first
        primary_res = self._analyze_via_primary_api(prompt_content, system_prompt, project_name, client_name)
        if primary_res:
            logger.info("Successfully obtained CAD estimation from Primary API.")
            return primary_res
        
        logger.warning("Primary API failed or skipped. Falling back to Gemini API.")

        # 1. SDK Call via google-genai with model fallback loop
        if self.client:
            for model_name in self._get_model_candidates():
                for attempt in range(2):
                    try:
                        logger.info(f"Calling Gemini LLM ({model_name}) via SDK (Attempt {attempt+1})...")
                        response = self.client.models.generate_content(
                            model=model_name,
                            contents=prompt_content,
                            config=types.GenerateContentConfig(
                                system_instruction=system_prompt,
                                response_mime_type="application/json",
                                response_schema=DynamicTakeoffResponse,
                                temperature=0.0,
                                seed=42,
                                max_output_tokens=32768
                            )
                        )
                        if response and response.text:
                            parsed_json = self._clean_and_parse_json(response.text)
                            return DynamicTakeoffResponse(**parsed_json)
                    except Exception as sdk_err:
                        logger.warning(f"Gemini SDK call to {model_name} failed: {sdk_err}. Retrying/falling back...")
                        time.sleep(2 * (attempt + 1))

        # 2. Direct REST Call Fallback
        return self._analyze_via_rest(prompt_content, system_prompt, project_name, client_name)

    def _analyze_via_rest(self, prompt_content: str, system_prompt: str, project_name: str, client_name: str) -> DynamicTakeoffResponse:
        """Fallback REST request to Gemini API with automatic model retry and fallback on 503/429 errors."""
        for model_name in self._get_model_candidates():
            url = f"https://generativelanguage.googleapis.com/v1beta/models/{model_name}:generateContent?key={self.api_key}"
            payload = {
                "contents": [{"role": "user", "parts": [{"text": prompt_content}]}],
                "systemInstruction": {"parts": [{"text": system_prompt}]},
                "generationConfig": {
                    "responseMimeType": "application/json",
                    "temperature": 0.0,
                    "seed": 42,
                    "maxOutputTokens": 32768
                }
            }

            for attempt in range(2):
                try:
                    logger.info(f"Calling Gemini REST model '{model_name}' (Attempt {attempt+1})...")
                    req = urllib.request.Request(
                        url,
                        data=json.dumps(payload).encode("utf-8"),
                        headers={"Content-Type": "application/json"},
                        method="POST"
                    )
                    with urllib.request.urlopen(req, timeout=180) as resp:
                        res_data = json.loads(resp.read().decode("utf-8"))
                        candidates = res_data.get("candidates", [])
                        if candidates:
                            parts = candidates[0].get("content", {}).get("parts", [])
                            if parts:
                                raw_text = parts[0].get("text", "")
                                parsed_dict = self._clean_and_parse_json(raw_text)
                                return DynamicTakeoffResponse(**parsed_dict)
                except urllib.error.HTTPError as http_err:
                    logger.warning(f"Gemini REST model '{model_name}' HTTP error {http_err.code}: {http_err.reason}. Retrying...")
                    time.sleep(3 * (attempt + 1))
                except Exception as err:
                    logger.warning(f"Gemini REST model '{model_name}' failed: {err}")
                    time.sleep(2)

        raise RuntimeError("Failed to obtain valid response from all Gemini API model candidates.")

    def analyze_pdf_bytes(self, pdf_bytes: bytes, filename: str = "document.pdf", project_name: str = "Proyek CAD PDF", client_name: str = "Client") -> DynamicTakeoffResponse:
        """
        Send raw PDF bytes directly to Gemini LLM for direct multimodal vision/text Quantity Takeoff estimation without conversion.
        """
        system_prompt = (
            "You are a professional Senior Quantity Surveyor (QS) in Indonesia. "
            "Analyze the provided PDF engineering/construction drawing document directly. "
            "Directly generate complete WBS (Work Breakdown Structure) sections and work items according to real Indonesian Civil Engineering standards. "
            "CRITICAL CAD SCALE & UNIT NORMALIZATION RULES: "
            "1. CAD UNIT NORMALIZATION: CAD/DED drawings in Indonesia are drawn in Millimeters (mm) or Centimeters (cm). "
            "   - If drawing dimensions say '6000' x '10000' or '600' x '1000', convert to METERS: 6.0m x 10.0m = 60.0 m2 plot area! "
            "   - NEVER treat raw CAD millimeter or centimeter values directly as meters or m2! "
            "2. SANITY CHECK ON AREA & FOOTPRINT: "
            "   - 'Pembersihan Lapangan' & 'Bouwplank' MUST match the real building/site footprint area (typically 30 m2 to 500 m2 for residential/building drawings). "
            "3. DETERMINISTIC STRUCTURAL FALLBACK SPECIFICATIONS (Use strictly when unstated in drawing): "
            "   - Wall height = 3.0 meters. "
            "   - Foundation trench depth = 0.8 meters, bottom width = 0.7 meters, top width = 0.5 meters. "
            "   - Footplat P1 (if present without explicit dimensions) = 1.0m x 1.0m x 0.4m. "
            "   - Sloof S1 = 0.15m x 0.20m. Kolom K1 = 0.15m x 0.15m x 3.0m. "
            "   - Sand bed thickness = 0.05m. Aanstamping thickness = 0.10m. "
            "4. ATOMIC VOLUME RULES: "
            "   - EVERY WORK ITEM MUST HAVE A REALISTIC POSITIVE VOLUME (> 0.0). NEVER RETURN 0 OR 0.0 FOR VOLUME! "
            "   - Concrete/Foundation/Excavation (m3): length x width x depth. "
            "   - Area (m2): wall area, plastering, floor area, site area. "
            "   - Separate PEKERJAAN TANAH (galian, urugan, pemadatan) from PEKERJAAN PONDASI (batu belah, footplat, pancang). "
            "   - Put clear mathematical calculation steps in `warning_note` (e.g. 'Site footprint 6.0m x 10.0m = 60.0 m2'). "
            "Output JSON directly conforming to the DynamicTakeoffResponse schema."
        )

        prompt_content = f"""Judul Gambar PDF: '{project_name}' (File: {filename})
Klien: '{client_name}'

Tugas QS:
1. Periksa notasi dimensi & denah dalam dokumen PDF ini, tentukan skala unit (mm atau cm ke meter).
2. Lakukan Sanity Check Luas Tapak/Bangunan: Pastikan luas 'Pembersihan Lapangan' & 'Bouwplank' realistis sesuai ukuran tanah (contoh: 6m x 10m = 60 m2, BUKAN ribuan m2).
3. Lakukan Material Takeoff & Perhitungan Volume Kuantitas Riil untuk SETIAP item pekerjaan.
4. Pisahkan seksi WBS secara spesifik (misal: Pekerjaan Tanah TERPISAH dari Pekerjaan Pondasi).
"""

        # Extract text & dimension annotations directly from PDF using pypdf (No image conversion)
        pdf_text = ""
        try:
            import io
            import pypdf
            reader = pypdf.PdfReader(io.BytesIO(pdf_bytes))
            text_pages = []
            for idx, page in enumerate(reader.pages):
                txt = page.extract_text() or ""
                if txt.strip():
                    text_pages.append(f"--- HALAMAN {idx+1} ---\n{txt.strip()}")
            pdf_text = "\n\n".join(text_pages)
            logger.info(f"Extracted {len(pdf_text)} characters of text from {len(reader.pages)} PDF pages.")
            if len(pdf_text) > 15000:
                logger.info(f"Large PDF text detected ({len(pdf_text)} chars). Trimming to 15,000 chars to avoid Cloudflare 524 timeout.")
                pdf_text = pdf_text[:15000] + "\n\n... [Sisa teks PDF dipotong untuk optimasi kecepatan AI]"
        except Exception as pdf_err:
            logger.warning(f"pypdf text extraction skipped/failed: {pdf_err}")

        full_prompt = prompt_content
        if pdf_text:
            full_prompt += f"\n\n[DATA TEKS & ANOTASI DIMENSI PDF]:\n{pdf_text}"

        # 1. Try Primary API (combo-code) first with PDF text payload
        primary_res = self._analyze_via_primary_api(full_prompt, system_prompt, project_name, client_name)
        if primary_res:
            logger.info("Successfully obtained PDF takeoff estimation from Primary API (combo-code).")
            return primary_res

        logger.warning("Primary API failed or skipped for PDF. Falling back to Gemini API.")

        # 2. SDK Call via google-genai using Files API for reliable large PDF upload
        if self.client:
            import tempfile
            with tempfile.NamedTemporaryFile(suffix=".pdf", delete=False) as tmp_pdf:
                tmp_pdf.write(pdf_bytes)
                tmp_pdf_path = tmp_pdf.name

            uploaded_file = None
            try:
                logger.info(f"Uploading PDF ({len(pdf_bytes)} bytes) to Gemini Files API...")
                uploaded_file = self.client.files.upload(file=tmp_pdf_path)
                logger.info(f"PDF successfully uploaded to Gemini Files API: {getattr(uploaded_file, 'name', 'OK')}")

                for model_name in self._get_model_candidates():
                    for attempt in range(2):
                        try:
                            logger.info(f"Calling Gemini LLM ({model_name}) via SDK for Direct PDF Takeoff (Attempt {attempt+1})...")
                            response = self.client.models.generate_content(
                                model=model_name,
                                contents=[uploaded_file, prompt_content],
                                config=types.GenerateContentConfig(
                                    system_instruction=system_prompt,
                                    response_mime_type="application/json",
                                    response_schema=DynamicTakeoffResponse,
                                    temperature=0.0,
                                    seed=42,
                                    max_output_tokens=32768
                                )
                            )
                            if response and response.text:
                                parsed_json = self._clean_and_parse_json(response.text)
                                return DynamicTakeoffResponse(**parsed_json)
                        except Exception as sdk_err:
                            logger.warning(f"Gemini SDK direct PDF call for {model_name} failed: {sdk_err}. Retrying/falling back...")
                            time.sleep(2 * (attempt + 1))
            except Exception as upload_err:
                logger.warning(f"Failed to upload PDF via Gemini Files API: {upload_err}")
            finally:
                if os.path.exists(tmp_pdf_path):
                    try:
                        os.remove(tmp_pdf_path)
                    except Exception:
                        pass
                if uploaded_file and hasattr(uploaded_file, "name"):
                    try:
                        self.client.files.delete(name=uploaded_file.name)
                    except Exception:
                        pass

        # 2. Direct REST Call Fallback with inlineData
        return self._analyze_pdf_via_rest(pdf_bytes, filename, project_name, client_name)

    def _analyze_pdf_via_rest(self, pdf_bytes: bytes, filename: str = "document.pdf", project_name: str = "Proyek CAD PDF", client_name: str = "Client") -> DynamicTakeoffResponse:
        """Fallback REST request to Gemini API with direct PDF inlineData and model fallback."""
        pdf_b64 = base64.b64encode(pdf_bytes).decode("utf-8")
        prompt_content = f"Judul Gambar PDF: '{project_name}' (File: {filename})\nKlien: '{client_name}'"
        system_prompt = "You are a professional Senior Quantity Surveyor (QS) in Indonesia."

        for model_name in self._get_model_candidates():
            url = f"https://generativelanguage.googleapis.com/v1beta/models/{model_name}:generateContent?key={self.api_key}"
            payload = {
                "contents": [{
                    "role": "user",
                    "parts": [
                        {
                            "inlineData": {
                                "mimeType": "application/pdf",
                                "data": pdf_b64
                            }
                        },
                        {"text": prompt_content}
                    ]
                }],
                "systemInstruction": {"parts": [{"text": system_prompt}]},
                "generationConfig": {
                    "responseMimeType": "application/json",
                    "temperature": 0.0,
                    "seed": 42,
                    "maxOutputTokens": 32768
                }
            }

            for attempt in range(2):
                try:
                    logger.info(f"Calling Gemini REST model '{model_name}' for PDF (Attempt {attempt+1})...")
                    req = urllib.request.Request(
                        url,
                        data=json.dumps(payload).encode("utf-8"),
                        headers={"Content-Type": "application/json"},
                        method="POST"
                    )
                    with urllib.request.urlopen(req, timeout=180) as resp:
                        res_data = json.loads(resp.read().decode("utf-8"))
                        candidates = res_data.get("candidates", [])
                        if candidates:
                            parts = candidates[0].get("content", {}).get("parts", [])
                            if parts:
                                raw_text = parts[0].get("text", "")
                                parsed_dict = self._clean_and_parse_json(raw_text)
                                return DynamicTakeoffResponse(**parsed_dict)
                except urllib.error.HTTPError as http_err:
                    logger.warning(f"Gemini REST PDF model '{model_name}' HTTP error {http_err.code}: {http_err.reason}. Retrying...")
                    time.sleep(3 * (attempt + 1))
                except Exception as err:
                    logger.warning(f"Gemini REST PDF model '{model_name}' failed: {err}")
                    time.sleep(2)

        raise RuntimeError("Failed to obtain valid response from all Gemini API model candidates for PDF.")

    def analyze_image_bytes(self, image_bytes: bytes, filename: str = "image.jpg", mime_type: str = "image/jpeg", project_name: str = "Proyek DED Gambar", client_name: str = "Client") -> DynamicTakeoffResponse:
        """
        Send raw image bytes (JPEG, PNG, JPG) directly to Gemini LLM for direct multimodal vision Quantity Takeoff estimation.
        """
        system_prompt = (
            "You are a professional Senior Quantity Surveyor (QS) in Indonesia. "
            "Analyze the provided construction / engineering drawing image (architectural blueprint, floor plan, elevation, structural drawing, or site plan) directly. "
            "Directly generate complete WBS (Work Breakdown Structure) sections and work items according to real Indonesian Civil Engineering standards. "
            "CRITICAL CAD/DRAWING SCALE & UNIT NORMALIZATION RULES: "
            "1. DRAWING UNIT NORMALIZATION: Construction drawings in Indonesia are drawn in Millimeters (mm) or Centimeters (cm). "
            "   - If drawing dimensions say '6000' x '10000' or '600' x '1000', convert to METERS: 6.0m x 10.0m = 60.0 m2 plot area! "
            "   - NEVER treat raw millimeter or centimeter values directly as meters or m2! "
            "2. SANITY CHECK ON AREA & FOOTPRINT: "
            "   - 'Pembersihan Lapangan' & 'Bouwplank' MUST match the real building/site footprint area (typically 30 m2 to 500 m2 for residential/building drawings). "
            "3. DETERMINISTIC STRUCTURAL FALLBACK SPECIFICATIONS (Use strictly when unstated in drawing): "
            "   - Wall height = 3.0 meters. "
            "   - Foundation trench depth = 0.8 meters, bottom width = 0.7 meters, top width = 0.5 meters. "
            "   - Footplat P1 (if present without explicit dimensions) = 1.0m x 1.0m x 0.4m. "
            "   - Sloof S1 = 0.15m x 0.20m. Kolom K1 = 0.15m x 0.15m x 3.0m. "
            "   - Sand bed thickness = 0.05m. Aanstamping thickness = 0.10m. "
            "4. ATOMIC VOLUME RULES: "
            "   - EVERY WORK ITEM MUST HAVE A REALISTIC POSITIVE VOLUME (> 0.0). NEVER RETURN 0 OR 0.0 FOR VOLUME! "
            "   - Concrete/Foundation/Excavation (m3): length x width x depth. "
            "   - Area (m2): wall area, plastering, floor area, site area. "
            "   - Separate PEKERJAAN TANAH (galian, urugan, pemadatan) from PEKERJAAN PONDASI (batu belah, footplat, pancang). "
            "   - Put clear mathematical calculation steps in `warning_note` (e.g. 'Site footprint 6.0m x 10.0m = 60.0 m2'). "
            "Output JSON directly conforming to the DynamicTakeoffResponse schema."
        )

        prompt_content = f"""Judul Gambar DED: '{project_name}' (File: {filename})
Klien: '{client_name}'

Tugas QS:
1. Periksa notasi dimensi & denah dalam gambar ini, tentukan skala unit (mm atau cm ke meter).
2. Lakukan Sanity Check Luas Tapak/Bangunan: Pastikan luas 'Pembersihan Lapangan' & 'Bouwplank' realistis sesuai ukuran tanah (contoh: 6m x 10m = 60 m2, BUKAN ribuan m2).
3. Lakukan Material Takeoff & Perhitungan Volume Kuantitas Riil untuk SETIAP item pekerjaan.
4. Pisahkan seksi WBS secara spesifik (misal: Pekerjaan Tanah TERPISAH dari Pekerjaan Pondasi).
"""

        # Try Primary API first with image
        primary_res = self._analyze_via_primary_api(prompt_content, system_prompt, project_name, client_name, media_bytes=image_bytes, mime_type=mime_type)
        if primary_res:
            logger.info("Successfully obtained image estimation from Primary API.")
            return primary_res

        logger.warning("Primary API failed or skipped for image. Falling back to Gemini API.")

        # 1. SDK Call via google-genai
        if self.client:
            try:
                part = types.Part.from_bytes(data=image_bytes, mime_type=mime_type)
                for model_name in self._get_model_candidates():
                    for attempt in range(2):
                        try:
                            logger.info(f"Calling Gemini LLM ({model_name}) via SDK for Direct Image Takeoff (Attempt {attempt+1})...")
                            response = self.client.models.generate_content(
                                model=model_name,
                                contents=[part, prompt_content],
                                config=types.GenerateContentConfig(
                                    system_instruction=system_prompt,
                                    response_mime_type="application/json",
                                    response_schema=DynamicTakeoffResponse,
                                    temperature=0.0,
                                    seed=42,
                                    max_output_tokens=32768
                                )
                            )
                            if response and response.text:
                                parsed_json = self._clean_and_parse_json(response.text)
                                return DynamicTakeoffResponse(**parsed_json)
                        except Exception as sdk_err:
                            logger.warning(f"Gemini SDK direct image call for {model_name} failed: {sdk_err}. Retrying/falling back...")
                            time.sleep(2 * (attempt + 1))
            except Exception as part_err:
                logger.warning(f"Failed to prepare Part.from_bytes for image: {part_err}")

        # 2. Direct REST Call Fallback with inlineData
        return self._analyze_image_via_rest(image_bytes, filename, mime_type, project_name, client_name)

    def _analyze_image_via_rest(self, image_bytes: bytes, filename: str = "image.jpg", mime_type: str = "image/jpeg", project_name: str = "Proyek DED Gambar", client_name: str = "Client") -> DynamicTakeoffResponse:
        """Fallback REST request to Gemini API with direct image inlineData and model fallback."""
        img_b64 = base64.b64encode(image_bytes).decode("utf-8")
        prompt_content = f"Judul Gambar DED: '{project_name}' (File: {filename})\nKlien: '{client_name}'"
        system_prompt = "You are a professional Senior Quantity Surveyor (QS) in Indonesia."

        for model_name in self._get_model_candidates():
            url = f"https://generativelanguage.googleapis.com/v1beta/models/{model_name}:generateContent?key={self.api_key}"
            payload = {
                "contents": [{
                    "role": "user",
                    "parts": [
                        {
                            "inlineData": {
                                "mimeType": mime_type,
                                "data": img_b64
                            }
                        },
                        {"text": prompt_content}
                    ]
                }],
                "systemInstruction": {"parts": [{"text": system_prompt}]},
                "generationConfig": {
                    "responseMimeType": "application/json",
                    "temperature": 0.0,
                    "seed": 42,
                    "maxOutputTokens": 32768
                }
            }

            for attempt in range(2):
                try:
                    logger.info(f"Calling Gemini REST model '{model_name}' for Image (Attempt {attempt+1})...")
                    req = urllib.request.Request(
                        url,
                        data=json.dumps(payload).encode("utf-8"),
                        headers={"Content-Type": "application/json"},
                        method="POST"
                    )
                    with urllib.request.urlopen(req, timeout=180) as resp:
                        res_data = json.loads(resp.read().decode("utf-8"))
                        candidates = res_data.get("candidates", [])
                        if candidates:
                            parts = candidates[0].get("content", {}).get("parts", [])
                            if parts:
                                raw_text = parts[0].get("text", "")
                                parsed_dict = self._clean_and_parse_json(raw_text)
                                return DynamicTakeoffResponse(**parsed_dict)
                except urllib.error.HTTPError as http_err:
                    logger.warning(f"Gemini REST Image model '{model_name}' HTTP error {http_err.code}: {http_err.reason}. Retrying...")
                    time.sleep(3 * (attempt + 1))
                except Exception as err:
                    logger.warning(f"Gemini REST Image model '{model_name}' failed: {err}")
                    time.sleep(2)

        raise RuntimeError("Failed to obtain valid response from all Gemini API model candidates for Image.")

    def analyze_bim_payload(self, bim_payload: str, project_name: str = "Proyek BIM Revit", client_name: str = "Client") -> DynamicTakeoffResponse:
        """
        Send structured BIM 3D parametric quantities to Gemini LLM for standard WBS mapping and AHSP classification.
        Quantities (volume, area, count) are 100% deterministic from BIM, while AI maps technical family/material names to Indonesian RAB standards.
        """
        system_prompt = (
            "You are a professional Senior Quantity Surveyor (QS) and BIM Estimator in Indonesia. "
            "Analyze the provided 3D BIM parametric quantity data extracted from OpenBIM IFC / Revit models. "
            "Your job is to produce a COMPLETE and COMPREHENSIVE RAB (Rencana Anggaran Biaya) WBS document — not just a mapping of BIM elements, but a FULL construction project breakdown. "
            "\n\nTWO-TIER QUANTITY SYSTEM: "
            "\n  TIER 1 — DIRECT BIM QUANTITIES (confidence: 'high'): "
            "\n    Items directly present in the BIM payload. Use the EXACT numerical values provided. "
            "\n    - Concrete/Footing/Column/Beam/Slab (IfcColumn, IfcBeam, IfcSlab, IfcFooting): Unit = 'm3' (use total_volume_m3). "
            "\n    - Walls/Coverings/Roof (IfcWall, IfcCovering, IfcRoof): Unit = 'm2' (use total_area_m2). "
            "\n    - Linear Members (IfcMember): Unit = 'm' or 'm1' (use total_length_m). "
            "\n    - Doors/Windows (IfcDoor, IfcWindow): Unit = 'unit' or 'bh' (use count). "
            "\n  TIER 2 — DERIVED/IMPLIED QUANTITIES (confidence: 'medium' or 'low'): "
            "\n    Items NOT directly in BIM but REQUIRED for a complete RAB. Calculate these from BIM data using standard Indonesian engineering ratios: "
            "\n    A. PEKERJAAN PERSIAPAN: "
            "\n       - Pembersihan Lapangan: Derive site area from total IfcSlab ground floor area or building footprint. "
            "\n       - Bouwplank/Uitzet: Derive from building perimeter. "
            "\n    B. PEKERJAAN TANAH: "
            "\n       - Galian Tanah Pondasi: Derive from IfcFooting dimensions (volume x 1.5 expansion factor for trench). "
            "\n       - Urugan Pasir Bawah Pondasi: thickness 0.05m x footprint area of footings. "
            "\n       - Urugan Tanah Kembali: Galian volume - foundation volume. "
            "\n       - Pemadatan Tanah: Equal to urugan tanah kembali area. "
            "\n    C. PEKERJAAN BEKISTING (FORMWORK): "
            "\n       - Bekisting Kolom: 4 x side_dimension x height x count (from IfcColumn). "
            "\n       - Bekisting Balok: (2 x height + width) x length (from IfcBeam). "
            "\n       - Bekisting Sloof: Similar to beam formwork. "
            "\n       - Bekisting Plat Lantai: Same as slab area (from IfcSlab). "
            "\n    D. PEKERJAAN PEMBESIAN (REINFORCEMENT): "
            "\n       - Estimate rebar weight using standard ratio: 80-120 kg/m3 of concrete for columns, 100-150 kg/m3 for beams, 60-80 kg/m3 for slabs. "
            "\n       - Unit = 'kg'. "
            "\n    E. PEKERJAAN FINISHING: "
            "\n       - Plesteran Dinding: 2 x total_area_m2 of IfcWall (both sides). "
            "\n       - Acian Dinding: Same as plesteran area. "
            "\n       - Pengecatan Dinding: Same as plesteran area. "
            "\n       - Plesteran & Acian Plafon: Use IfcSlab/IfcCovering ceiling area. "
            "\n       - Pengecatan Plafon: Same as ceiling plaster area. "
            "\n    F. PEKERJAAN LANTAI: "
            "\n       - Pemasangan Keramik/Ubin: Use IfcSlab floor area or IfcCovering floor area. "
            "\n    G. PEKERJAAN SANITASI & MEP (if applicable): "
            "\n       - Estimate basic plumbing/electrical as lump sum (ls) if building has bathrooms/kitchens. "
            "\n\nSTRICT RULES: "
            "\n1. EVERY work item MUST have volume > 0.0. "
            "\n2. For TIER 1 items: Use exact BIM values. Set confidence = 'high'. "
            "\n3. For TIER 2 items: Show calculation formula in `warning_note` (e.g., 'Derived: 2 x IfcWall area 150.5 m2 = 301.0 m2 plesteran'). Set confidence = 'medium' or 'low'. "
            "\n4. Convert technical BIM names to standard Indonesian AHSP RAB descriptions. "
            "\n5. Group items into proper WBS sections (minimum 6-8 sections for a complete building). "
            "\n6. State Level/Lantai in description or warning_note. "
            "\n7. Aim for 30-60 total work items for a typical building project. "
            "\nOutput JSON directly conforming to the DynamicTakeoffResponse schema."
        )

        prompt_content = f"""Judul Proyek BIM: '{project_name}'
Klien: '{client_name}'

=== STRUCTURED BIM 3D PARAMETRIC QUANTITIES PAYLOAD ===
{bim_payload}

Tugas QS — Buat RAB LENGKAP:
1. Petakan setiap kelompok elemen BIM ke seksi WBS RAB yang relevan (TIER 1 — langsung dari BIM).
2. TAMBAHKAN pekerjaan yang TIDAK ADA di model BIM tapi WAJIB ada di RAB (TIER 2 — derived/implied):
   - Pekerjaan Persiapan (pembersihan lahan, bouwplank)
   - Pekerjaan Tanah (galian, urugan pasir, urugan tanah kembali, pemadatan)
   - Bekisting untuk semua elemen beton (kolom, balok, sloof, plat)
   - Pembesian/tulangan untuk semua elemen beton (gunakan rasio kg/m3 standar)
   - Finishing dinding (plesteran 2 sisi, acian, cat)
   - Finishing plafon (plesteran, acian, cat)
   - Pemasangan lantai keramik
3. Gunakan nilai kuantitas eksak dari BIM untuk TIER 1, dan hitung derivasi untuk TIER 2.
4. Buat deskripsi pekerjaan terstandardisasi sesuai AHSP Indonesia.
5. Cantumkan rumus perhitungan pada `warning_note` untuk setiap item.
6. Target: minimal 30 work items untuk RAB yang komprehensif.
"""

        # Try Primary API first
        primary_res = self._analyze_via_primary_api(prompt_content, system_prompt, project_name, client_name)
        if primary_res:
            logger.info("Successfully obtained BIM estimation from Primary API.")
            return primary_res
        
        logger.warning("Primary API failed or skipped. Falling back to Gemini API.")

        # 1. SDK Call via google-genai with model fallback loop
        if self.client:
            for model_name in self._get_model_candidates():
                for attempt in range(2):
                    try:
                        logger.info(f"Calling Gemini LLM ({model_name}) via SDK for BIM 3D WBS Takeoff Mapping (Attempt {attempt+1})...")
                        response = self.client.models.generate_content(
                            model=model_name,
                            contents=prompt_content,
                            config=types.GenerateContentConfig(
                                system_instruction=system_prompt,
                                response_mime_type="application/json",
                                response_schema=DynamicTakeoffResponse,
                                temperature=0.0,
                                seed=42,
                                max_output_tokens=32768
                            )
                        )
                        if response and response.text:
                            parsed_json = self._clean_and_parse_json(response.text)
                            return DynamicTakeoffResponse(**parsed_json)
                    except Exception as sdk_err:
                        logger.warning(f"Gemini SDK BIM call for {model_name} failed: {sdk_err}. Retrying/falling back...")
                        time.sleep(2 * (attempt + 1))

        # 2. Direct REST Call Fallback
        return self._analyze_via_rest(prompt_content, system_prompt, project_name, client_name)

    @staticmethod
    def _clean_and_parse_json(text: str) -> dict:
        """Clean markdown wrapping, auto-repair broken JSON response, and normalize schema fields."""
        clean = text.strip()
        clean = re.sub(r'<think>.*?</think>', '', clean, flags=re.DOTALL).strip()
        
        match = re.search(r'```json\s*(\{.*?\})\s*```', clean, re.DOTALL)
        if match:
            clean = match.group(1)

        data = None
        try:
            data = json.loads(clean)
        except json.JSONDecodeError:
            start = clean.find('{')
            end = clean.rfind('}')
            if start != -1 and end != -1 and end > start:
                cand = clean[start:end+1]
                try:
                    data = json.loads(cand)
                except json.JSONDecodeError:
                    pass

        if not isinstance(data, dict):
            data = {}

        # Normalize schema for DynamicTakeoffResponse
        if "project" not in data or not isinstance(data.get("project"), dict):
            project_title = data.get("project_name") or data.get("title") or "Analisis Estimator CAD/PDF"
            client_name = data.get("client_name") or data.get("client") or "Client"
            data["project"] = {
                "title": project_title,
                "client": client_name,
                "status": "Perencanaan"
            }

        # Normalize project_summary — MUST be a string, GPT-5.6 often returns dict/object
        raw_summary = data.get("project_summary")
        # Check if wbs_sections, sections, or items are nested inside dictionary fields (like project_summary, project, takeoff) BEFORE normalizing project_summary
        for parent_key in list(data.keys()):
            sub_dict = data.get(parent_key)
            if isinstance(sub_dict, dict):
                for sub_k in ("wbs_sections", "sections", "wbs", "categories", "items", "work_items"):
                    if sub_k in sub_dict and isinstance(sub_dict[sub_k], list):
                        logger.info(f"[JSON Normalize] Extracted nested '{sub_k}' ({len(sub_dict[sub_k])} items) from parent dict '{parent_key}'.")
                        data[sub_k] = sub_dict[sub_k]
                        break

        # Normalize project_summary to string safely
        raw_summary = data.get("project_summary")
        if not isinstance(raw_summary, str):
            if isinstance(raw_summary, dict):
                # Try to extract a readable description string from the dict
                for key in ("description", "summary", "text", "overview", "note"):
                    candidate = raw_summary.get(key)
                    if isinstance(candidate, str) and len(candidate) > 5:
                        data["project_summary"] = candidate
                        break
                else:
                    data["project_summary"] = "Ringkasan takeoff material otomatis dari AI."
            elif isinstance(raw_summary, list):
                data["project_summary"] = "; ".join(str(x) for x in raw_summary)
            else:
                fallback = data.get("summary")
                data["project_summary"] = str(fallback) if fallback else "Ringkasan takeoff material otomatis dari AI."
        
        # Final safety: ensure it's definitely a string
        if not isinstance(data.get("project_summary"), str):
            data["project_summary"] = "Ringkasan takeoff material otomatis dari AI."

        # Debug: log top-level keys to understand LLM response structure
        logger.info(f"[JSON Normalize] Top-level keys from LLM: {list(data.keys())}")

        if "wbs_sections" not in data or not isinstance(data.get("wbs_sections"), list):
            alt_list = data.get("sections") or data.get("wbs") or data.get("categories")
            if isinstance(alt_list, list):
                logger.info(f"[JSON Normalize] Found alt sections key with {len(alt_list)} sections.")
                data["wbs_sections"] = alt_list
            elif isinstance(data.get("items"), list):
                # GPT-5.6 often returns a flat list of items — group them by sectionCode into multiple WBS sections
                flat_items = data["items"]
                logger.info(f"[JSON Normalize] Flat items list detected ({len(flat_items)} items). Grouping by sectionCode...")
                
                from collections import OrderedDict
                sections_map = OrderedDict()
                for item in flat_items:
                    if not isinstance(item, dict):
                        continue
                    # Extract section code from item
                    sec_code = (item.get("sectionCode") or item.get("section_code") 
                                or item.get("section") or "A")
                    # Handle case where sectionCode is a dict (nested section object)
                    sec_name = None
                    if isinstance(sec_code, dict):
                        sec_name = sec_code.get("name")
                        sec_code = sec_code.get("code", "A")
                    sec_code = str(sec_code)
                    
                    if sec_code not in sections_map:
                        # Try to extract section name from item metadata
                        if not sec_name:
                            sec_name = (item.get("section_name") or item.get("category") 
                                        or item.get("category_name") or f"Pekerjaan Seksi {sec_code}")
                        sections_map[sec_code] = {
                            "section": {
                                "id": f"sec-{sec_code}",
                                "type": "section",
                                "code": sec_code,
                                "name": sec_name
                            },
                            "items": []
                        }
                    sections_map[sec_code]["items"].append(item)
                
                if sections_map:
                    data["wbs_sections"] = list(sections_map.values())
                    logger.info(f"[JSON Normalize] Grouped into {len(sections_map)} WBS sections: {list(sections_map.keys())}")
                else:
                    data["wbs_sections"] = [{
                        "section": {"id": "sec-A", "type": "section", "code": "A", "name": "PEKERJAAN KONSTRUKSI (AI TAKEOFF)"},
                        "items": flat_items
                    }]
            else:
                # Custom Key Recovery: LLMs (like Gemini/Claude) sometimes return custom top-level keys like
                # 'room_approximate_areas_sqm', 'roof_structure_details', 'overall_dimensions', etc.
                recovered_sections = []
                sec_counter = 65  # 'A'
                for k, v in data.items():
                    if k in ("project", "project_summary", "wbs_sections", "sections", "items", "project_info", "general_notes"):
                        continue
                    sec_code = chr(sec_counter)
                    sec_name = k.replace("_", " ").upper()
                    items_list = []
                    
                    if isinstance(v, dict):
                        for item_key, item_val in v.items():
                            vol = 1.0
                            if isinstance(item_val, (int, float)):
                                vol = float(item_val)
                            elif isinstance(item_val, str):
                                match = re.search(r"(\d+(?:\.\d+)?)", item_val)
                                if match:
                                    vol = float(match.group(1))

                            items_list.append({
                                "no": len(items_list) + 1,
                                "name": str(item_key).replace("_", " ").title(),
                                "volume": vol,
                                "unit": "m2" if "area" in k or "area" in str(item_key) else ("m3" if "vol" in k else "ls"),
                                "warning_note": f"Dari {k}: {item_val}"
                            })
                    elif isinstance(v, list):
                        for idx, el in enumerate(v):
                            if isinstance(el, dict):
                                items_list.append(el)
                            else:
                                items_list.append({
                                    "no": idx + 1,
                                    "name": str(el),
                                    "volume": 1.0,
                                    "unit": "ls",
                                    "warning_note": f"Dari {k}"
                                })

                    if items_list:
                        recovered_sections.append({
                            "section": {
                                "id": f"sec-{sec_code}",
                                "code": sec_code,
                                "name": f"PEKERJAAN {sec_name}"
                            },
                            "items": items_list
                        })
                        sec_counter += 1

                data["wbs_sections"] = recovered_sections
                if recovered_sections:
                    logger.info(f"[JSON Normalize] Recovered {len(recovered_sections)} WBS sections from custom LLM keys: {[s['section']['name'] for s in recovered_sections]}")

        # Deep-repair and normalize WBS sections and items
        for sec_idx, sec_block in enumerate(data["wbs_sections"]):
            if not isinstance(sec_block, dict):
                data["wbs_sections"][sec_idx] = {"section": {}, "items": []}
                sec_block = data["wbs_sections"][sec_idx]

            # Debug: log raw section block keys to understand GPT-5.6 structure
            sec_keys = {k: type(v).__name__ for k, v in sec_block.items() if k != "items"}
            logger.info(f"[JSON Normalize] Section #{sec_idx} raw keys: {sec_keys}")

            # Normalize Section header — GPT-5.6 may nest section info differently
            if "section" not in sec_block or not isinstance(sec_block.get("section"), dict):
                sec_block["section"] = {}
            
            section = sec_block["section"]
            sec_code = (section.get("code") or sec_block.get("code") or sec_block.get("wbs_code")
                        or sec_block.get("sectionCode") or sec_block.get("section_code") 
                        or chr(65 + (sec_idx % 26)))
            section["code"] = str(sec_code)
            
            # Expanded section name lookup — GPT-5.6 uses 'wbs_name'
            resolved_name = (
                section.get("name") 
                or sec_block.get("name") 
                or sec_block.get("wbs_name")
                or sec_block.get("title") 
                or sec_block.get("section_name")
                or sec_block.get("heading")
                or sec_block.get("description")
                or sec_block.get("category")
                or sec_block.get("category_name")
                or sec_block.get("label")
            )
            # If still no name, try to extract from first item's section info
            if not resolved_name and sec_block.get("items") and isinstance(sec_block["items"], list):
                first_item = sec_block["items"][0] if sec_block["items"] else {}
                if isinstance(first_item, dict):
                    resolved_name = first_item.get("section_name") or first_item.get("category")
            
            section["name"] = resolved_name or f"Pekerjaan Seksi {sec_code}"
            section["id"] = section.get("id") or f"sec-{sec_code}"
            section["type"] = "section"
            
            logger.info(f"[JSON Normalize] Section #{sec_idx}: code='{sec_code}', name='{section['name']}'")

            # Normalize Items list
            if "items" not in sec_block or not isinstance(sec_block.get("items"), list):
                sec_block["items"] = sec_block.get("work_items") or sec_block.get("tasks") or sec_block.get("details") or []

            if not isinstance(sec_block["items"], list):
                sec_block["items"] = []

            repaired_items = []
            for item_idx, item in enumerate(sec_block["items"]):
                if not isinstance(item, dict):
                    continue

                repaired_item = {}
                repaired_item["sectionCode"] = item.get("sectionCode") or item.get("section_code") or str(sec_code)

                raw_no = item.get("no") or item.get("number") or item.get("index") or (item_idx + 1)
                try:
                    repaired_item["no"] = int(raw_no)
                except (ValueError, TypeError):
                    repaired_item["no"] = item_idx + 1

                repaired_item["code"] = item.get("code") or item.get("wbs_code") or f"{repaired_item['sectionCode']}.{repaired_item['no']}"
                repaired_item["name"] = item.get("name") or item.get("wbs_item") or item.get("description") or item.get("title") or "Pekerjaan Kategori"

                raw_volume = item.get("volume") or item.get("qty") or item.get("quantity") or 1.0
                try:
                    repaired_item["volume"] = float(raw_volume)
                except (ValueError, TypeError):
                    repaired_item["volume"] = 1.0

                repaired_item["unit"] = item.get("unit") or item.get("satuan") or "m2"
                repaired_item["id"] = item.get("id") or f"item-{repaired_item['sectionCode']}-{repaired_item['no']}"
                repaired_item["type"] = "item"
                repaired_item["confidence"] = item.get("confidence") or "high"
                repaired_item["warning_note"] = item.get("warning_note") or item.get("calculation_note") or item.get("notes") or ""

                repaired_items.append(repaired_item)

            sec_block["items"] = repaired_items

        return data
