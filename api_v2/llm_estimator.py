import os
import json
import re
import logging
import urllib.request
import base64
from typing import Dict, Any, Optional

from google import genai
from google.genai import types
from dotenv import load_dotenv

from schemas import DynamicTakeoffResponse

load_dotenv()
logger = logging.getLogger(__name__)

# Default API key and settings from environment
DEFAULT_GEMINI_KEY = os.getenv("GEMINI_API_KEY", "")
DEFAULT_MODEL = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")

class CADLLMEstimator:
    """
    Direct AI WBS Takeoff Estimator powered by Google Gemini.
    Generates dynamic WBS sections and work items directly from AI without post-processing mapping.
    """

    def __init__(self, api_key: Optional[str] = None, model: Optional[str] = None):
        self.api_key = api_key or DEFAULT_GEMINI_KEY
        self.model = model or DEFAULT_MODEL
        
        if self.api_key:
            try:
                self.client = genai.Client(api_key=self.api_key)
            except Exception as e:
                logger.warning(f"Could not initialize genai.Client: {e}")
                self.client = None
        else:
            logger.warning("GEMINI_API_KEY is not set in environment (.env). AI requests will fail until an API key is provided.")
            self.client = None

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

        # 1. SDK Call via google-genai
        if self.client:
            try:
                logger.info(f"Calling Gemini LLM ({self.model}) via SDK for Direct Dynamic WBS Takeoff...")
                response = self.client.models.generate_content(
                    model=self.model,
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
                logger.warning(f"Gemini SDK call failed: {sdk_err}. Falling back to REST API...")

        # 2. Direct REST Call Fallback
        return self._analyze_via_rest(prompt_content, system_prompt, project_name, client_name)

    def _analyze_via_rest(self, prompt_content: str, system_prompt: str, project_name: str, client_name: str) -> DynamicTakeoffResponse:
        """Fallback REST request to Gemini API."""
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{self.model}:generateContent?key={self.api_key}"
        
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

        req = urllib.request.Request(
            url,
            data=json.dumps(payload).encode("utf-8"),
            headers={"Content-Type": "application/json"},
            method="POST"
        )

        with urllib.request.urlopen(req, timeout=120) as resp:
            res_data = json.loads(resp.read().decode("utf-8"))
            candidates = res_data.get("candidates", [])
            if candidates:
                parts = candidates[0].get("content", {}).get("parts", [])
                if parts:
                    raw_text = parts[0].get("text", "")
                    parsed_dict = self._clean_and_parse_json(raw_text)
                    return DynamicTakeoffResponse(**parsed_dict)

        raise RuntimeError("Failed to obtain valid response from Gemini API.")

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

        # 1. SDK Call via google-genai
        if self.client:
            try:
                logger.info(f"Calling Gemini LLM ({self.model}) via SDK for Direct PDF Multimodal WBS Takeoff...")
                pdf_part = types.Part.from_bytes(data=pdf_bytes, mime_type="application/pdf")
                response = self.client.models.generate_content(
                    model=self.model,
                    contents=[pdf_part, prompt_content],
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
                logger.warning(f"Gemini SDK direct PDF call failed: {sdk_err}. Falling back to REST API...")

        # 2. Direct REST Call Fallback with inlineData
        return self._analyze_pdf_via_rest(pdf_bytes, filename, project_name, client_name)

    def _analyze_pdf_via_rest(self, pdf_bytes: bytes, filename: str = "document.pdf", project_name: str = "Proyek CAD PDF", client_name: str = "Client") -> DynamicTakeoffResponse:
        """Fallback REST request to Gemini API with direct PDF inlineData."""
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{self.model}:generateContent?key={self.api_key}"
        
        pdf_b64 = base64.b64encode(pdf_bytes).decode("utf-8")
        prompt_content = f"Judul Gambar PDF: '{project_name}' (File: {filename})\nKlien: '{client_name}'"
        system_prompt = "You are a professional Senior Quantity Surveyor (QS) in Indonesia."
        
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

        raise RuntimeError("Failed to obtain valid response from Gemini API for PDF.")

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

        if "project_summary" not in data or not data.get("project_summary"):
            data["project_summary"] = data.get("summary") or "Ringkasan takeoff material otomatis dari AI."

        if "wbs_sections" not in data or not isinstance(data.get("wbs_sections"), list):
            data["wbs_sections"] = []

        return data
