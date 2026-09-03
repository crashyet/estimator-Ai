"""
src/bim_parser.py — OpenBIM IFC Parser Engine

Memroses file OpenBIM IFC (.ifc) dan file 3D Autodesk Revit (.rvt, .rfa, .nwd, .nwc)
untuk mengekstrak kuantitas parametrik 3D (volume, luas, panjang) per elemen bangunan.

Alur kerja:
  1. File diterima sebagai bytes → disimpan ke file sementara.
  2. Untuk .ifc: langsung diproses oleh ifcopenshell.
  3. Untuk .rvt/.nwd: dicoba konversi via Autodesk Cloud (APS) melalui APSConverter,
     lalu fallback ke converter lokal (rvt2ifc / IfcConvert).
  4. Kuantitas elemen diagregasi per (level, kategori, family, material).
  5. Hasil dikembalikan sebagai List[BIMElementQuantity] untuk dikirim ke LLM estimator.

Kelas:
  BIMEntityExtractor: Parser utama OpenBIM IFC dan Revit.
"""

import os
import tempfile
import logging
from typing import List, Dict, Any, Optional, Union
import ifcopenshell
import ifcopenshell.util.element

from src.schemas import BIMElementQuantity
from src.aps_client import APSConverter

logger = logging.getLogger(__name__)

class BIMEntityExtractor:
    """
    OpenBIM IFC Parser Engine menggunakan library ifcopenshell.

    Mengekstrak kuantitas parametrik 3D dari file .ifc secara langsung,
    dan mengorkestrasi konversi native Revit (.rvt, .nwd) via Autodesk APS Cloud
    atau converter lokal sebagai fallback.

    Atribut Kelas:
        TARGET_CATEGORIES (List[str]): Daftar tipe elemen IFC yang diproses.

    Methods:
        process_ifc_file()  : Memproses file IFC dari path lokal.
        process_rvt_file()  : Memproses file Revit native melalui APS Cloud / converter lokal.
        process_bim_bytes() : Entry point utama — menerima bytes + nama file.
        process_ifc_bytes() : Alias ke process_bim_bytes untuk backward compatibility.
        format_to_llm_payload(): Memformat hasil kuantitas ke JSON string untuk LLM.
    """

    TARGET_CATEGORIES = [
        "IfcWall",
        "IfcColumn",
        "IfcBeam",
        "IfcSlab",
        "IfcFooting",
        "IfcDoor",
        "IfcWindow",
        "IfcStair",
        "IfcCovering",
        "IfcMember",
        "IfcRoof",
        "IfcBuildingElementProxy"
    ]

    @classmethod
    def process_ifc_file(cls, file_path: str) -> List[BIMElementQuantity]:
        """
        Memproses file OpenBIM IFC dari path lokal di filesystem.

        Args:
            file_path (str): Path absolut ke file .ifc yang valid.

        Returns:
            List[BIMElementQuantity]: Daftar kuantitas elemen BIM yang telah diagregasi.

        Raises:
            ValueError: Jika file IFC tidak valid atau korup.
        """
        try:
            model = ifcopenshell.open(file_path)
            return cls._extract_from_model(model)
        except Exception as e:
            logger.error(f"Failed to open IFC file {file_path}: {e}", exc_info=True)
            raise ValueError(f"Invalid or corrupted IFC file: {e}")

    @classmethod
    def process_rvt_file(cls, rvt_file_path: str) -> List[BIMElementQuantity]:
        """
        Memproses file Revit native (.rvt, .rfa, .nwd, .nwc) ke kuantitas BIM.

        Urutan fallback konversi:
          1. Autodesk Platform Services (APS) Cloud API (jika APS_CLIENT_ID & APS_CLIENT_SECRET dikonfigurasi).
          2. Converter lokal: rvt2ifc atau IfcConvert (jika tersedia di PATH sistem).
          3. Jika semua gagal, melempar ValueError dengan instruksi ekspor manual.

        Args:
            rvt_file_path (str): Path ke file Revit atau Navisworks yang akan diproses.

        Returns:
            List[BIMElementQuantity]: Daftar kuantitas elemen BIM.

        Raises:
            ValueError: Jika semua metode konversi gagal.
        """
        import shutil
        import subprocess

        with tempfile.NamedTemporaryFile(suffix=".ifc", delete=False) as tmp_ifc:
            temp_ifc_path = tmp_ifc.name

        try:
            aps_client_id = os.getenv("APS_CLIENT_ID")
            aps_client_secret = os.getenv("APS_CLIENT_SECRET")
            if aps_client_id and aps_client_secret:
                try:
                    logger.info("APS Credentials detected in .env! Translating 3D model via Autodesk Cloud API...")
                    res = APSConverter.convert_autodesk_model(rvt_file_path, temp_ifc_path, aps_client_id, aps_client_secret)
                    if isinstance(res, list) and len(res) > 0:
                        return res
                    elif res is True and os.path.exists(temp_ifc_path) and os.path.getsize(temp_ifc_path) > 0:
                        return cls.process_ifc_file(temp_ifc_path)
                except Exception as aps_err:
                    logger.warning(f"Autodesk APS Cloud translation failed: {aps_err}. Falling back to local converter...")

            cmd = None
            if shutil.which("rvt2ifc"):
                cmd = [shutil.which("rvt2ifc"), rvt_file_path, temp_ifc_path]
            elif shutil.which("IfcConvert"):
                cmd = [shutil.which("IfcConvert"), rvt_file_path, temp_ifc_path]

            if cmd:
                rvt_timeout = int(os.getenv("RVT_TIMEOUT_SECONDS", "300"))
                logger.info(f"Converting native .rvt file using system command {cmd} with timeout {rvt_timeout}s...")
                res = subprocess.run(cmd, timeout=rvt_timeout, capture_output=True, text=True)
                if res.returncode == 0 and os.path.exists(temp_ifc_path) and os.path.getsize(temp_ifc_path) > 0:
                    return cls.process_ifc_file(temp_ifc_path)
                logger.warning(f"Local RVT converter exited with error: {res.stderr}")

            err_msg = (
                "Konversi cloud Autodesk APS tidak berhasil atau mengalami kendala izin/timeout. "
                "Silakan ekspor proyek Anda ke format .ifc langsung dari Autodesk Revit (File -> Export -> IFC) "
                "lalu unggah file .ifc tersebut."
            )
            logger.error(err_msg)
            raise ValueError(err_msg)

        finally:
            if os.path.exists(temp_ifc_path):
                try:
                    os.remove(temp_ifc_path)
                except Exception:
                    pass

    @classmethod
    def process_bim_bytes(cls, file_bytes: bytes, filename: str = "model.ifc") -> List[BIMElementQuantity]:
        """
        Entry point utama untuk memproses file BIM dari raw bytes.

        Menulis bytes ke file sementara, lalu memanggil extractor yang sesuai
        berdasarkan ekstensi file. Membersihkan file sementara setelah selesai.

        Args:
            file_bytes (bytes): Konten biner file BIM yang diunggah.
            filename (str): Nama asli file (termasuk ekstensi) untuk menentukan tipe parser.

        Returns:
            List[BIMElementQuantity]: Daftar kuantitas elemen BIM.

        Raises:
            ValueError: Jika ekstensi file tidak didukung.
        """
        ext = os.path.splitext(filename)[1].lower()
        with tempfile.NamedTemporaryFile(suffix=ext, delete=False) as tmp:
            tmp.write(file_bytes)
            tmp_path = tmp.name

        try:
            if ext == ".ifc":
                return cls.process_ifc_file(tmp_path)
            elif ext in [".rvt", ".rfa", ".nwd", ".nwc", ".skp"]:
                return cls.process_rvt_file(tmp_path)
            else:
                raise ValueError(f"Unsupported BIM file extension: {ext}")
        finally:
            if os.path.exists(tmp_path):
                try:
                    os.remove(tmp_path)
                except Exception:
                    pass

    @classmethod
    def process_ifc_bytes(cls, file_bytes: bytes, filename: str = "model.ifc") -> List[BIMElementQuantity]:
        return cls.process_bim_bytes(file_bytes, filename)

    @classmethod
    def _extract_from_model(cls, model: ifcopenshell.file) -> List[BIMElementQuantity]:
        """
        Mengekstrak dan mengagregasi kuantitas elemen dari model IFC yang sudah dibuka.

        Iterasi seluruh TARGET_CATEGORIES, ekstrak properti level, family, material,
        volume/area/panjang dari IFC quantity sets, lalu agregasi per key unik
        (level, kategori, family_name, material).

        Args:
            model (ifcopenshell.file): Model IFC yang sudah dibuka oleh ifcopenshell.

        Returns:
            List[BIMElementQuantity]: Daftar kuantitas yang telah diagregasi.
        """
        raw_elements: List[Dict[str, Any]] = []

        for cat in cls.TARGET_CATEGORIES:
            elements = model.by_type(cat)
            for elem in elements:
                container = ifcopenshell.util.element.get_container(elem)
                level_name = container.Name if (container and hasattr(container, "Name") and container.Name) else "Lantai 1"

                elem_name = (
                    getattr(elem, "ObjectType", None)
                    or getattr(elem, "Name", None)
                    or elem.is_a()
                )

                mat_name = "Standard Material"
                try:
                    mat = ifcopenshell.util.element.get_material(elem)
                    if mat:
                        if hasattr(mat, "Name") and mat.Name:
                            mat_name = mat.Name
                        elif isinstance(mat, dict) and "Name" in mat:
                            mat_name = mat["Name"]
                        else:
                            mat_name = str(mat)
                except Exception:
                    pass

                vol, area, length = cls._extract_quantities(elem, cat)

                raw_elements.append({
                    "level": str(level_name).strip(),
                    "category": cat,
                    "family_name": str(elem_name).strip(),
                    "material": str(mat_name).strip(),
                    "volume_m3": float(vol),
                    "area_m2": float(area),
                    "length_m": float(length)
                })

        aggregated_map: Dict[tuple, BIMElementQuantity] = {}

        for item in raw_elements:
            key = (item["level"], item["category"], item["family_name"], item["material"])
            if key not in aggregated_map:
                aggregated_map[key] = BIMElementQuantity(
                    level=item["level"],
                    category=item["category"],
                    family_name=item["family_name"],
                    material=item["material"],
                    count=1,
                    total_volume_m3=round(item["volume_m3"], 3),
                    total_area_m2=round(item["area_m2"], 3),
                    total_length_m=round(item["length_m"], 3)
                )
            else:
                existing = aggregated_map[key]
                existing.count += 1
                existing.total_volume_m3 = round(existing.total_volume_m3 + item["volume_m3"], 3)
                existing.total_area_m2 = round(existing.total_area_m2 + item["area_m2"], 3)
                existing.total_length_m = round(existing.total_length_m + item["length_m"], 3)

        return list(aggregated_map.values())

    @classmethod
    def _extract_quantities(cls, elem: Any, category: str) -> tuple[float, float, float]:
        """
        Mengekstrak nilai volume, area, dan panjang dari Quantity Sets (Qto) elemen IFC.

        Mencari Qto yang relevan berdasarkan kategori elemen (contoh: 'Qto_WallBaseQuantities'
        untuk IfcWall), lalu mengambil nilai NetVolume, GrossArea, Length, dsb.

        Args:
            elem: Elemen IFC yang diproses.
            category (str): Nama tipe IFC elemen (contoh: 'IfcWall', 'IfcColumn').

        Returns:
            tuple[float, float, float]: (volume_m3, area_m2, length_m)
        """
        vol = 0.0
        area = 0.0
        length = 0.0

        try:
            psets = ifcopenshell.util.element.get_psets(elem)
        except Exception:
            psets = {}

        qto_keys = [
            f"Qto_{category[3:]}BaseQuantities",
            "BaseQuantities",
            "Qto_BuildingElementProxyBaseQuantities"
        ]

        qto_dicts = []
        for key in qto_keys:
            if key in psets and isinstance(psets[key], dict):
                qto_dicts.append(psets[key])

        for pset_name, pset_val in psets.items():
            if pset_name.startswith("Qto_") and isinstance(pset_val, dict) and pset_val not in qto_dicts:
                qto_dicts.append(pset_val)

        for qto in qto_dicts:
            if not vol:
                vol = float(
                    qto.get("NetVolume")
                    or qto.get("GrossVolume")
                    or qto.get("Volume")
                    or 0.0
                )
            if not area:
                area = float(
                    qto.get("NetSideArea")
                    or qto.get("GrossSideArea")
                    or qto.get("NetArea")
                    or qto.get("GrossArea")
                    or qto.get("Area")
                    or 0.0
                )
            if not length:
                length = float(
                    qto.get("Length")
                    or qto.get("Height")
                    or qto.get("UnconnectedHeight")
                    or qto.get("Width")
                    or 0.0
                )

        return vol, area, length

    @classmethod
    def format_to_llm_payload(cls, quantities: List[BIMElementQuantity]) -> str:
        """
        Memformat daftar BIMElementQuantity menjadi JSON string untuk dikirim ke LLM.

        Mengonversi list objek Pydantic menjadi JSON terindentasi agar dapat dibaca
        dan diproses oleh CADLLMEstimator.analyze_bim_payload().

        Args:
            quantities (List[BIMElementQuantity]): Hasil ekstraksi kuantitas BIM.

        Returns:
            str: JSON string terformat dengan indentasi 2 spasi.
        """
        import json
        payload_data = [item.model_dump() for item in quantities]
        return json.dumps(payload_data, indent=2, ensure_ascii=False)
