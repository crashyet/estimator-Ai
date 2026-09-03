# LLM Takeoff Execution Engine — lihat docs/modules/llm_estimator.md untuk dokumentasi lengkap.

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

from src.schemas import DynamicTakeoffResponse
from src.prompts import (
    CAD_SYSTEM_PROMPT, build_cad_user_prompt,
    PDF_SYSTEM_PROMPT, build_pdf_user_prompt,
    IMAGE_SYSTEM_PROMPT, build_image_user_prompt,
    BIM_SYSTEM_PROMPT, build_bim_user_prompt
)

load_dotenv()
logger = logging.getLogger(__name__)

DEFAULT_GEMINI_KEY = os.getenv("GEMINI_API_KEY", "")
DEFAULT_MODEL = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")
FALLBACK_MODELS = ["gemini-2.5-flash", "gemini-2.0-flash", "gemini-1.5-pro"]

class CADLLMEstimator:
    """
    AI Takeoff Estimator Engine berbasis Google Gemini dan Primary OpenAI-compatible API.

    Menerima payload teks (CAD entities, BIM quantities, atau prompt gambar) dan
    mengembalikan DynamicTakeoffResponse berisi WBS sections & item pekerjaan.

    Urutan Fallback:
      1. _analyze_via_primary_api() — Primary API (OpenAI-compatible proxy).
      2. Gemini SDK (genai.Client) dengan model fallback list.
      3. _analyze_via_rest()        — Gemini REST HTTP API langsung.

    Args:
        api_key (str, optional)  : Gemini API Key. Default dari env GEMINI_API_KEY.
        model   (str, optional)  : Model Gemini awal. Default dari env GEMINI_MODEL.
    """

    def __init__(self, api_key: Optional[str] = None, model: Optional[str] = None):
        self.api_key = api_key or DEFAULT_GEMINI_KEY
        self.model = model or DEFAULT_MODEL
        
        self.primary_api_base = os.getenv("PRIMARY_API_BASE", "")
        self.primary_api_key = os.getenv("PRIMARY_API_KEY", "")
        self.primary_model = os.getenv("PRIMARY_MODEL", "")

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
        """
        Memanggil Primary OpenAI-compatible API (non-streaming dan SSE streaming fallback).

        Mendukung pengiriman media (gambar) sebagai base64 inline jika `media_bytes` diberikan.
        Secara otomatis mengompresi gambar ke JPEG jika ukurannya melebihi threshold.

        Args:
            prompt_content (str)           : Perintah pengguna.
            system_prompt  (str)           : Instruksi sistem QS.
            project_name   (str)           : Nama proyek (untuk metadata).
            client_name    (str)           : Nama klien (untuk metadata).
            media_bytes    (bytes, optional): Bytes biner file media (gambar/PDF).
            mime_type      (str, optional) : MIME type media (contoh: 'image/jpeg', 'application/pdf').

        Returns:
            DynamicTakeoffResponse | None: Hasil parsing, atau None jika Primary API gagal/tidak terkonfigurasi.
        """
        if not self.primary_api_key or not self.primary_api_base:
            logger.warning("Primary API key or base URL is not configured. Skipping primary API.")
            return None

        url = f"{self.primary_api_base.rstrip('/')}/chat/completions"
        headers = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer {self.primary_api_key}",
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }

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
        """
        Mengembalikan daftar prioritas model Gemini untuk dicoba secara berurutan.

        Menempatkan model utama (dari konfigurasi) di posisi pertama,
        diikuti oleh FALLBACK_MODELS tanpa duplikat.

        Returns:
            List[str]: Daftar nama model Gemini dalam urutan prioritas.
        """
        candidates = [self.model]
        for m in FALLBACK_MODELS:
            if m not in candidates:
                candidates.append(m)
        return candidates

    def analyze_cad_payload(self, text_payload: str, project_name: str = "Proyek CAD DWG", client_name: str = "Client") -> DynamicTakeoffResponse:
        """
        Menganalisis payload teks entitas CAD dan menghasilkan WBS takeoff lengkap.

        Args:
            text_payload (str): Teks entitas CAD dari CADEntityExtractor.format_to_llm_payload().
            project_name (str): Nama proyek.
            client_name  (str): Nama klien.

        Returns:
            DynamicTakeoffResponse: Hasil WBS takeoff dari analisis AI.

        Raises:
            RuntimeError: Jika seluruh fallback API gagal.
        """
        system_prompt = CAD_SYSTEM_PROMPT
        prompt_content = build_cad_user_prompt(text_payload, project_name, client_name)

        primary_res = self._analyze_via_primary_api(prompt_content, system_prompt, project_name, client_name)
        if primary_res:
            logger.info("Successfully obtained CAD estimation from Primary API.")
            return primary_res
        
        logger.warning("Primary API failed or skipped. Falling back to Gemini API.")

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

        return self._analyze_via_rest(prompt_content, system_prompt, project_name, client_name)

    def _analyze_via_rest(self, prompt_content: str, system_prompt: str, project_name: str, client_name: str) -> DynamicTakeoffResponse:
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
        Menganalisis file PDF set gambar secara multimodal page-by-page menggunakan Gemini.

        Proses:
          1. Upload file PDF ke Gemini Files API.
          2. Kirim file yang diupload beserta prompt ke Gemini SDK (native PDF engine).
          3. Jika SDK gagal, fallback ke REST API dengan inline base64 PDF.

        Args:
            pdf_bytes    (bytes): Konten biner file PDF.
            filename     (str)  : Nama file PDF asli.
            project_name (str)  : Nama proyek.
            client_name  (str)  : Nama klien.

        Returns:
            DynamicTakeoffResponse: Hasil WBS takeoff dari analisis multimodal PDF.
        """
        system_prompt = PDF_SYSTEM_PROMPT
        prompt_content = build_pdf_user_prompt(filename, project_name, client_name)

        logger.info(f"Processing PDF document '{filename}' ({len(pdf_bytes)} bytes) directly via Gemini Multimodal Native PDF engine...")

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

        return self._analyze_pdf_via_rest(pdf_bytes, filename, prompt_content, system_prompt)

    def _analyze_pdf_via_rest(self, pdf_bytes: bytes, filename: str = "document.pdf", prompt_content: str = "", system_prompt: str = "") -> DynamicTakeoffResponse:
        pdf_b64 = base64.b64encode(pdf_bytes).decode("utf-8")
        if not prompt_content:
            prompt_content = f"Judul Gambar PDF: (File: {filename})"
        if not system_prompt:
            system_prompt = PDF_SYSTEM_PROMPT

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
        system_prompt = IMAGE_SYSTEM_PROMPT
        prompt_content = build_image_user_prompt(filename, project_name, client_name)

        primary_res = self._analyze_via_primary_api(prompt_content, system_prompt, project_name, client_name, media_bytes=image_bytes, mime_type=mime_type)
        if primary_res:
            logger.info("Successfully obtained image estimation from Primary API.")
            return primary_res

        logger.warning("Primary API failed or skipped for image. Falling back to Gemini API.")

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

        return self._analyze_image_via_rest(image_bytes, filename, mime_type, project_name, client_name)

    def _analyze_image_via_rest(self, image_bytes: bytes, filename: str = "image.jpg", mime_type: str = "image/jpeg", project_name: str = "Proyek DED Gambar", client_name: str = "Client") -> DynamicTakeoffResponse:
        img_b64 = base64.b64encode(image_bytes).decode("utf-8")
        prompt_content = f"Judul Gambar DED: '{project_name}' (File: {filename})\nKlien: '{client_name}'"
        system_prompt = IMAGE_SYSTEM_PROMPT

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
        system_prompt = BIM_SYSTEM_PROMPT
        prompt_content = build_bim_user_prompt(bim_payload, project_name, client_name)

        primary_res = self._analyze_via_primary_api(prompt_content, system_prompt, project_name, client_name)
        if primary_res:
            logger.info("Successfully obtained BIM estimation from Primary API.")
            return primary_res
        
        logger.warning("Primary API failed or skipped. Falling back to Gemini API.")

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

        return self._analyze_via_rest(prompt_content, system_prompt, project_name, client_name)

    @staticmethod
    def _clean_and_parse_json(text: str) -> dict:
        """
        Membersihkan dan memperbaiki output teks LLM menjadi dict Python yang valid.

        Tahapan pembersihan:
          1. Strip tag <think>...</think> (dari model reasoning seperti DeepSeek/QwQ).
          2. Ekstraksi blok ```json ... ``` jika ada.
          3. json.loads() langsung; jika gagal, cari {} dari posisi pertama sampai terakhir.
          4. Normalisasi key alternatif (sections, wbs, categories → wbs_sections).
          5. Normalisasi project_summary jika berbentuk dict atau list.
          6. Grouping flat items list per sectionCode jika AI mengembalikan flat array.

        Args:
            text (str): Raw string output dari LLM (mungkin berisi markdown, tag, atau JSON rusak).

        Returns:
            dict: Dictionary yang sudah ternormalisasi dan siap untuk DynamicTakeoffResponse(**dict).
        """
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

        if "project" not in data or not isinstance(data.get("project"), dict):
            project_title = data.get("project_name") or data.get("title") or "Analisis Estimator CAD/PDF"
            client_name = data.get("client_name") or data.get("client") or "Client"
            data["project"] = {
                "title": project_title,
                "client": client_name,
                "status": "Perencanaan"
            }

        raw_summary = data.get("project_summary")
        for parent_key in list(data.keys()):
            sub_dict = data.get(parent_key)
            if isinstance(sub_dict, dict):
                for sub_k in ("wbs_sections", "sections", "wbs", "categories", "items", "work_items"):
                    if sub_k in sub_dict and isinstance(sub_dict[sub_k], list):
                        logger.info(f"[JSON Normalize] Extracted nested '{sub_k}' ({len(sub_dict[sub_k])} items) from parent dict '{parent_key}'.")
                        data[sub_k] = sub_dict[sub_k]
                        break

        raw_summary = data.get("project_summary")
        if not isinstance(raw_summary, str):
            if isinstance(raw_summary, dict):
                for key in ("description", "summary", "text", "overview", "note"):
                    candidate = raw_summary.get(key)
                    if isinstance(candidate, str) and len(candidate) > 5:
                        data["project_summary"] = candidate
                        break
                else:
                    data["project_summary"] = f"Ringkasan estimasi untuk proyek: {data.get('project', {}).get('title', 'Tidak diketahui')}"
            elif isinstance(raw_summary, list):
                data["project_summary"] = "; ".join(str(x) for x in raw_summary)
            else:
                fallback = data.get("summary")
                data["project_summary"] = str(fallback) if fallback else f"Ringkasan estimasi untuk proyek: {data.get('project', {}).get('title', 'Tidak diketahui')}"
        
        if not isinstance(data.get("project_summary"), str):
            data["project_summary"] = f"Ringkasan estimasi untuk proyek: {data.get('project', {}).get('title', 'Tidak diketahui')}"

        logger.info(f"[JSON Normalize] Top-level keys from LLM: {list(data.keys())}")

        if "wbs_sections" not in data or not isinstance(data.get("wbs_sections"), list):
            alt_list = data.get("sections") or data.get("wbs") or data.get("categories")
            if isinstance(alt_list, list):
                logger.info(f"[JSON Normalize] Found alt sections key with {len(alt_list)} sections.")
                data["wbs_sections"] = alt_list
            elif isinstance(data.get("items"), list):
                flat_items = data["items"]
                logger.info(f"[JSON Normalize] Flat items list detected ({len(flat_items)} items). Grouping by sectionCode...")
                
                from collections import OrderedDict
                sections_map = OrderedDict()
                for item in flat_items:
                    if not isinstance(item, dict):
                        continue
                    sec_code = (item.get("sectionCode") or item.get("section_code") 
                                or item.get("section") or "A")
                    sec_name = None
                    if isinstance(sec_code, dict):
                        sec_name = sec_code.get("name")
                        sec_code = sec_code.get("code", "A")

                    if sec_code not in sections_map:
                        sections_map[sec_code] = {
                            "section": {
                                "id": f"sec-{sec_code}",
                                "type": "section",
                                "code": str(sec_code),
                                "name": str(sec_name or f"PEKERJAAN SEKSI {sec_code}")
                            },
                            "items": []
                        }
                    
                    item["sectionCode"] = str(sec_code)
                    sections_map[sec_code]["items"].append(item)

                data["wbs_sections"] = list(sections_map.values())
            else:
                data["wbs_sections"] = []

        return data
