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
FALLBACK_MODELS = ["gemini-2.5-flash", "gemini-2.5-pro", "gemini-2.0-flash-exp", "gemini-1.5-flash-latest"]

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

        # 1. SDK Call via google-genai with model fallback loop
        if self.client:
            for model_name in self._get_model_candidates():
                for attempt in range(2):
                    try:
                        logger.info(f"Calling Gemini LLM ({model_name}) via SDK for Direct PDF Takeoff (Attempt {attempt+1})...")
                        pdf_part = types.Part.from_bytes(data=pdf_bytes, mime_type="application/pdf")
                        response = self.client.models.generate_content(
                            model=model_name,
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
                        logger.warning(f"Gemini SDK direct PDF call for {model_name} failed: {sdk_err}. Retrying/falling back...")
                        time.sleep(2 * (attempt + 1))

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

    def analyze_bim_payload(self, bim_payload: str, project_name: str = "Proyek BIM Revit", client_name: str = "Client") -> DynamicTakeoffResponse:
        """
        Send structured BIM 3D parametric quantities to Gemini LLM for standard WBS mapping and AHSP classification.
        Quantities (volume, area, count) are 100% deterministic from BIM, while AI maps technical family/material names to Indonesian RAB standards.
        """
        system_prompt = (
            "You are a professional Senior Quantity Surveyor (QS) and BIM Estimator in Indonesia. "
            "Analyze the provided 100% deterministic 3D BIM parametric quantity data extracted from OpenBIM IFC / Revit models. "
            "Map raw BIM categories, family names, levels, and materials to standardized Indonesian Civil Engineering WBS RAB sections and AHSP work item descriptions. "
            "STRICT BIM TAKE-OFF RULES: "
            "1. DETERMINISTIC QUANTITIES: DO NOT fabricate or hallucinate volumes, areas, or lengths. Use the exact numerical values provided in the BIM payload! "
            "2. UNIT MAPPING ACCORDING TO ITEM TYPE: "
            "   - Concrete/Footing/Column/Beam/Slab (IfcColumn, IfcBeam, IfcSlab, IfcFooting): Unit = 'm3' (use total_volume_m3). "
            "   - Walls/Plastering/Coverings/Roof (IfcWall, IfcCovering, IfcRoof): Unit = 'm2' (use total_area_m2). If volume is required for brick volume, state calculation in warning_note. "
            "   - Linear Members (IfcMember): Unit = 'm' or 'm1' (use total_length_m). "
            "   - Doors/Windows/Fixtures (IfcDoor, IfcWindow): Unit = 'unit' or 'bh' or 'set' (use count). "
            "3. INDONESIAN AHSP STANDARDIZATION: "
            "   - Convert raw technical strings (e.g. 'Rectangular Column 400x400 - Concrete K350') to standard Indonesian RAB item names (e.g. 'Pekerjaan Beton Struktur Kolom K-350 UK. 40x40 cm'). "
            "4. WBS SECTIONING & LEVEL ORGANIZING: "
            "   - Group work items logically by WBS sections (e.g. PEKERJAAN STRUKTUR BETON, PEKERJAAN DINDING & PLESTERAN, PEKERJAAN PINTU & JENDELA, PEKERJAAN ATAP & PLAFON). "
            "   - State the Level/Lantai in the item description or warning_note. "
            "5. NON-ZERO VOLUME: Every work item must have volume > 0.0 corresponding to the physical BIM quantity. Put calculation provenance in `warning_note`. "
            "Output JSON directly conforming to the DynamicTakeoffResponse schema."
        )

        prompt_content = f"""Judul Proyek BIM: '{project_name}'
Klien: '{client_name}'

=== STRUCTURED BIM 3D PARAMETRIC QUANTITIES PAYLOAD ===
{bim_payload}

Tugas QS:
1. Petakan setiap kelompok elemen BIM ke seksi WBS RAB yang relevan.
2. Gunakan nilai kuantitas eksak dari BIM (m3 untuk beton, m2 untuk dinding/lantai, unit untuk pintu/jendela).
3. Buat deskripsi pekerjaan terstandardisasi sesuai AHSP Indonesia.
4. Cantumkan rincian sumber data BIM (level, jumlah elemen, volume 3D) pada `warning_note`.
"""

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

        if "project_summary" not in data or not data.get("project_summary"):
            data["project_summary"] = data.get("summary") or "Ringkasan takeoff material otomatis dari AI."

        if "wbs_sections" not in data or not isinstance(data.get("wbs_sections"), list):
            alt_list = data.get("sections") or data.get("wbs") or data.get("categories")
            if isinstance(alt_list, list):
                data["wbs_sections"] = alt_list
            elif isinstance(data.get("items"), list):
                # If Gemini returned a flat list of work items, wrap them in a default section
                data["wbs_sections"] = [{
                    "code": "A",
                    "title": "PEKERJAAN KONSTRUKSI (AI TAKEOFF)",
                    "items": data["items"]
                }]
            else:
                data["wbs_sections"] = []

        return data
