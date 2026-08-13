import os
import tempfile
import logging
from typing import List, Dict, Any, Optional
from pydantic import BaseModel, Field
import ifcopenshell
import ifcopenshell.util.element

logger = logging.getLogger(__name__)

class BIMElementQuantity(BaseModel):
    level: str = Field(default="Unassigned Level", description="Nama Level/Lantai Gedung")
    category: str = Field(..., description="Kategori IFC (IfcWall, IfcColumn, dll.)")
    family_name: str = Field(..., description="Nama Tipe / Family Elemen BIM")
    material: str = Field(default="Standard Material", description="Bahan/Material Elemen")
    count: int = Field(default=1, description="Jumlah elemen")
    total_volume_m3: float = Field(default=0.0, description="Total Net Volume (m3)")
    total_area_m2: float = Field(default=0.0, description="Total Net Area (m2)")
    total_length_m: float = Field(default=0.0, description="Total Panjang (m)")

class APSConverter:
    @staticmethod
    def convert_rvt_to_ifc(rvt_file_path: str, output_ifc_path: str, client_id: str, client_secret: str) -> bool:
        """
        Translates native Revit (.rvt) binary file to OpenBIM (.ifc) using Autodesk Platform Services (APS / Forge) Cloud API.
        """
        import base64
        import json
        import time
        import urllib.request
        import urllib.parse
        import urllib.error

        logger.info("[APS CLOUD API] Authenticating with Autodesk Platform Services...")
        
        # 1. Get 2-Legged Access Token
        token_url = "https://developer.api.autodesk.com/authentication/v2/token"
        token_data = urllib.parse.urlencode({
            "client_id": client_id,
            "client_secret": client_secret,
            "grant_type": "client_credentials",
            "scope": "data:read data:write data:create bucket:create bucket:read"
        }).encode("utf-8")

        req = urllib.request.Request(
            token_url,
            data=token_data,
            headers={"Content-Type": "application/x-www-form-urlencoded"},
            method="POST"
        )
        
        with urllib.request.urlopen(req, timeout=30) as resp:
            token_res = json.loads(resp.read().decode("utf-8"))
            access_token = token_res.get("access_token")

        if not access_token:
            raise ValueError("[APS CLOUD API] Failed to obtain access token from Autodesk.")

        headers = {"Authorization": f"Bearer {access_token}"}
        bucket_key = "beecons_bim_estimator_transient"

        # 2. Ensure Transient Bucket Exists
        bucket_url = "https://developer.api.autodesk.com/oss/v2/buckets"
        bucket_payload = json.dumps({
            "bucketKey": bucket_key,
            "policyKey": "transient"
        }).encode("utf-8")

        try:
            b_req = urllib.request.Request(
                bucket_url,
                data=bucket_payload,
                headers={**headers, "Content-Type": "application/json"},
                method="POST"
            )
            urllib.request.urlopen(b_req, timeout=30)
        except urllib.error.HTTPError as err:
            if err.code != 409: # 409 Conflict means bucket already exists
                logger.warning(f"[APS CLOUD API] Bucket creation note: {err}")

        # 3. Upload RVT file to OSS Bucket
        filename = os.path.basename(rvt_file_path)
        upload_url = f"https://developer.api.autodesk.com/oss/v2/buckets/{bucket_key}/objects/{urllib.parse.quote(filename)}"
        
        logger.info(f"[APS CLOUD API] Uploading {filename} ({os.path.getsize(rvt_file_path)} bytes) to Autodesk Cloud...")
        with open(rvt_file_path, "rb") as f:
            file_data = f.read()

        u_req = urllib.request.Request(
            upload_url,
            data=file_data,
            headers={**headers, "Content-Type": "application/octet-stream"},
            method="PUT"
        )
        with urllib.request.urlopen(u_req, timeout=300) as u_resp:
            upload_res = json.loads(u_resp.read().decode("utf-8"))
            object_id = upload_res.get("objectId")

        # 4. Base64 Encode URN (without padding =)
        urn_b64 = base64.b64encode(object_id.encode("utf-8")).decode("utf-8").rstrip("=")

        # 5. Start Model Derivative Translation Job (RVT -> IFC)
        job_url = "https://developer.api.autodesk.com/modelderivative/v2/designdata/job"
        job_payload = json.dumps({
            "input": {"urn": urn_b64},
            "output": {
                "destination": {"region": "us"},
                "formats": [{"type": "ifc"}]
            }
        }).encode("utf-8")

        logger.info("[APS CLOUD API] Triggering RVT to IFC transcoding job on Autodesk Cloud...")
        j_req = urllib.request.Request(
            job_url,
            data=job_payload,
            headers={**headers, "Content-Type": "application/json"},
            method="POST"
        )
        urllib.request.urlopen(j_req, timeout=30)

        # 6. Poll Job Manifest Status
        manifest_url = f"https://developer.api.autodesk.com/modelderivative/v2/designdata/{urn_b64}/manifest"
        start_time = time.time()
        download_urn = None

        while time.time() - start_time < 300: # 5 min timeout
            time.sleep(5)
            m_req = urllib.request.Request(manifest_url, headers=headers, method="GET")
            with urllib.request.urlopen(m_req, timeout=30) as m_resp:
                manifest = json.loads(m_resp.read().decode("utf-8"))
                status = manifest.get("status")
                progress = manifest.get("progress")
                logger.info(f"[APS CLOUD API] RVT Translation Progress: {progress} (Status: {status})...")

                if status == "success" or progress == "complete":
                    # Locate derivative urn for IFC
                    for derivatives in manifest.get("derivatives", []):
                        if derivatives.get("outputType") == "ifc":
                            for child in derivatives.get("children", []):
                                if child.get("role") == "ifc" or child.get("urn", "").endswith(".ifc"):
                                    download_urn = child.get("urn")
                                    break
                    break
                elif status == "failed":
                    raise ValueError("[APS CLOUD API] Autodesk Model Derivative translation failed.")

        if not download_urn:
            logger.warning("[APS CLOUD API] IFC derivative URN not found in manifest.")
            return False

        # 7. Download translated IFC file
        dl_url = f"https://developer.api.autodesk.com/modelderivative/v2/designdata/{urn_b64}/manifest/{urllib.parse.quote(download_urn)}"
        logger.info("[APS CLOUD API] Downloading transcoded IFC model from Autodesk Cloud...")
        dl_req = urllib.request.Request(dl_url, headers=headers, method="GET")
        with urllib.request.urlopen(dl_req, timeout=120) as dl_resp:
            with open(output_ifc_path, "wb") as f_out:
                f_out.write(dl_resp.read())

        logger.info(f"[APS CLOUD API] Successfully converted .rvt to .ifc via Autodesk Cloud ({os.path.getsize(output_ifc_path)} bytes)")
        return True

class BIMEntityExtractor:
    """
    OpenBIM IFC Parser Engine using ifcopenshell.
    Extracts 3D parametric quantities, spatial levels, and material properties.
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
        Process an IFC file on disk and return aggregated element quantities.
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
        Process native Autodesk Revit (.rvt) binary file.
        Attempts conversion via Autodesk APS Cloud API first, then local CLI converter.
        """
        import shutil
        import subprocess
        import sys

        with tempfile.NamedTemporaryFile(suffix=".ifc", delete=False) as tmp_ifc:
            temp_ifc_path = tmp_ifc.name

        try:
            # 1. Autodesk Platform Services (APS / Forge) Cloud API Strategy (PRIORITY 1 IF CREDENTIALS SET)
            aps_client_id = os.getenv("APS_CLIENT_ID")
            aps_client_secret = os.getenv("APS_CLIENT_SECRET")
            if aps_client_id and aps_client_secret:
                try:
                    logger.info("APS Credentials detected in .env! Translating .rvt model via Autodesk Cloud API...")
                    success = APSConverter.convert_rvt_to_ifc(rvt_file_path, temp_ifc_path, aps_client_id, aps_client_secret)
                    if success and os.path.exists(temp_ifc_path) and os.path.getsize(temp_ifc_path) > 0:
                        return cls.process_ifc_file(temp_ifc_path)
                except Exception as aps_err:
                    logger.warning(f"Autodesk APS Cloud translation failed: {aps_err}. Falling back to local converter...")

            # 2. Local CLI Converter Subprocess Strategy (FALLBACK)
            project_bin = os.path.join(os.path.dirname(__file__), "bin")
            rvt2ifc_src = os.path.join(project_bin, "rvt2ifc_src.py")
            rvt2ifc_wrapper = os.path.join(project_bin, "rvt2ifc_wrapper.sh")
            rvt2ifc_bin = os.path.join(project_bin, "rvt2ifc")

            cmd = None
            if os.path.exists(rvt2ifc_src):
                cmd = [sys.executable, rvt2ifc_src, rvt_file_path, temp_ifc_path]
            elif os.path.exists(rvt2ifc_wrapper):
                cmd = ["bash", rvt2ifc_wrapper, rvt_file_path, temp_ifc_path]
            elif shutil.which("rvt2ifc"):
                cmd = [shutil.which("rvt2ifc"), rvt_file_path, temp_ifc_path]
            elif shutil.which("IfcConvert"):
                cmd = [shutil.which("IfcConvert"), rvt_file_path, temp_ifc_path]
            elif os.path.exists(rvt2ifc_bin):
                cmd = [rvt2ifc_bin, rvt_file_path, temp_ifc_path]

            if cmd:
                rvt_timeout = int(os.getenv("RVT_TIMEOUT_SECONDS", "300"))
                logger.info(f"Converting native .rvt file using command {cmd} with timeout {rvt_timeout}s...")
                res = subprocess.run(cmd, timeout=rvt_timeout, capture_output=True, text=True)
                if res.returncode == 0 and os.path.exists(temp_ifc_path) and os.path.getsize(temp_ifc_path) > 0:
                    return cls.process_ifc_file(temp_ifc_path)
                logger.warning(f"Local RVT converter exited with error: {res.stderr}")

            # 3. Direct instruction fallback
            err_msg = (
                "Direct native .rvt binary parsing requires a converter tool in api_v2/bin (e.g., rvt2ifc/cad2data) "
                "or Autodesk APS Cloud credentials (APS_CLIENT_ID & APS_CLIENT_SECRET in .env). "
                "For instant 100% precision without cloud setup, please export your model to .ifc from Autodesk Revit (File -> Export -> IFC)."
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
        Process BIM file content (.ifc or .rvt) from raw bytes.
        """
        ext = os.path.splitext(filename)[1].lower()
        with tempfile.NamedTemporaryFile(suffix=ext, delete=False) as tmp:
            tmp.write(file_bytes)
            tmp_path = tmp.name

        try:
            if ext == ".ifc":
                return cls.process_ifc_file(tmp_path)
            elif ext == ".rvt":
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
        """Backward compatibility alias for process_bim_bytes."""
        return cls.process_bim_bytes(file_bytes, filename)

    @classmethod
    def _extract_from_model(cls, model: ifcopenshell.file) -> List[BIMElementQuantity]:
        """
        Internal extractor traversing model elements and grouping quantities.
        """
        raw_elements: List[Dict[str, Any]] = []

        for cat in cls.TARGET_CATEGORIES:
            elements = model.by_type(cat)
            for elem in elements:
                # 1. Storey / Level Name
                container = ifcopenshell.util.element.get_container(elem)
                level_name = container.Name if (container and hasattr(container, "Name") and container.Name) else "Lantai 1"

                # 2. Family / Element Name
                elem_name = (
                    getattr(elem, "ObjectType", None)
                    or getattr(elem, "Name", None)
                    or elem.is_a()
                )

                # 3. Material Extraction
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

                # 4. Quantity Extraction from Psets (Qto_* BaseQuantities)
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

        # Aggregate raw elements by key
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
        Extract NetVolume/GrossVolume, NetArea/GrossArea, Length/Height from element Psets.
        """
        vol = 0.0
        area = 0.0
        length = 0.0

        try:
            psets = ifcopenshell.util.element.get_psets(elem)
        except Exception:
            psets = {}

        # Scan Qto_* BaseQuantities and all Psets for matching quantity keys
        qto_keys = [
            f"Qto_{category[3:]}BaseQuantities",
            "BaseQuantities",
            "Qto_BuildingElementProxyBaseQuantities"
        ]

        # Gather relevant property dicts
        qto_dicts = []
        for key in qto_keys:
            if key in psets and isinstance(psets[key], dict):
                qto_dicts.append(psets[key])

        # Add all psets that start with Qto_
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
        Format extracted BIM quantities into a clean, concise JSON payload for Gemini LLM.
        """
        import json
        payload_data = [item.model_dump() for item in quantities]
        return json.dumps(payload_data, indent=2, ensure_ascii=False)
