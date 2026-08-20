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
    """
    Autodesk Platform Services (APS) Cloud Converter.
    Converts native Revit (.rvt, .rfa) and Navisworks (.nwd, .nwc) models via Model Derivative API.

    Required APS Dashboard Settings:
      - Data Management API: ✅ Enabled
      - Model Derivative API: ✅ Enabled (CRITICAL — 403 if missing)
    """

    @classmethod
    def convert_autodesk_model(
        cls, file_path: str, output_ifc_path: str, client_id: str, client_secret: str
    ) -> Union[bool, List[BIMElementQuantity]]:
        import base64
        import json
        import time
        import urllib.request
        import urllib.parse
        import urllib.error

        def _read_http_error(err: urllib.error.HTTPError) -> str:
            try:
                body = err.read().decode("utf-8", errors="ignore")[:500]
                return f"HTTP {err.code} {err.reason}: {body}"
            except Exception:
                return f"HTTP {err.code} {err.reason}"

        ext = os.path.splitext(file_path)[1].lower()

        # ── Step 1: 2-Legged OAuth2 Authentication ──
        logger.info("[APS] Step 1/7: Authenticating with Autodesk Platform Services...")
        token_url = "https://developer.api.autodesk.com/authentication/v2/token"
        token_data = urllib.parse.urlencode({
            "client_id": client_id,
            "client_secret": client_secret,
            "grant_type": "client_credentials",
            "scope": "data:read data:write data:create bucket:create bucket:read viewables:read"
        }).encode("utf-8")

        try:
            req = urllib.request.Request(
                token_url, data=token_data,
                headers={"Content-Type": "application/x-www-form-urlencoded"},
                method="POST"
            )
            with urllib.request.urlopen(req, timeout=30) as resp:
                access_token = json.loads(resp.read().decode("utf-8")).get("access_token")
        except urllib.error.HTTPError as err:
            raise ValueError(f"[APS] Step 1 FAILED — Auth error. Detail: {_read_http_error(err)}")

        if not access_token:
            raise ValueError("[APS] Step 1 FAILED — No access_token returned.")

        logger.info("[APS] Step 1/7: ✅ Authentication successful.")
        headers = {"Authorization": f"Bearer {access_token}"}
        clean_cid = "".join(c for c in client_id.lower() if c.isalnum())[:16]
        bucket_key = f"beecons_bim_{clean_cid}"

        # ── Step 2: Ensure Transient Bucket Exists ──
        logger.info(f"[APS] Step 2/7: Creating/verifying OSS bucket '{bucket_key}'...")
        bucket_url = "https://developer.api.autodesk.com/oss/v2/buckets"
        bucket_payload = json.dumps({"bucketKey": bucket_key, "policyKey": "transient"}).encode("utf-8")

        try:
            b_req = urllib.request.Request(
                bucket_url, data=bucket_payload,
                headers={**headers, "Content-Type": "application/json"},
                method="POST"
            )
            urllib.request.urlopen(b_req, timeout=30)
            logger.info("[APS] Step 2/7: ✅ Bucket created.")
        except urllib.error.HTTPError as err:
            if err.code == 409:
                logger.info("[APS] Step 2/7: ✅ Bucket already exists (409 OK).")
            else:
                raise ValueError(f"[APS] Step 2 FAILED — Bucket creation error: {_read_http_error(err)}")

        # ── Step 3: Direct-to-S3 Signed Upload ──
        filename = os.path.basename(file_path)
        file_size = os.path.getsize(file_path)
        s3_endpoint = f"https://developer.api.autodesk.com/oss/v2/buckets/{bucket_key}/objects/{urllib.parse.quote(filename)}/signeds3upload"

        logger.info(f"[APS] Step 3a/7: Requesting Signed S3 Upload URL for '{filename}' ({file_size:,} bytes)...")
        s3_get_req = urllib.request.Request(s3_endpoint, headers=headers, method="GET")
        with urllib.request.urlopen(s3_get_req, timeout=30) as s3_get_resp:
            s3_info = json.loads(s3_get_resp.read().decode("utf-8"))

        s3_put_url = s3_info["urls"][0]
        upload_key = s3_info["uploadKey"]

        logger.info(f"[APS] Step 3b/7: Uploading {file_size:,} bytes to AWS S3...")
        with open(file_path, "rb") as f:
            file_bytes = f.read()

        s3_put_req = urllib.request.Request(
            s3_put_url, data=file_bytes,
            headers={"Content-Type": "application/octet-stream"},
            method="PUT"
        )
        with urllib.request.urlopen(s3_put_req, timeout=600) as s3_put_resp:
            logger.info(f"[APS] Step 3b/7: ✅ S3 Upload completed (HTTP {s3_put_resp.status}).")

        logger.info("[APS] Step 3c/7: Finalizing upload in Autodesk Cloud...")
        s3_post_req = urllib.request.Request(
            s3_endpoint,
            data=json.dumps({"uploadKey": upload_key}).encode("utf-8"),
            headers={**headers, "Content-Type": "application/json"},
            method="POST"
        )
        with urllib.request.urlopen(s3_post_req, timeout=30) as s3_post_resp:
            post_res = json.loads(s3_post_resp.read().decode("utf-8"))
            object_id = post_res.get("objectId")

        if not object_id:
            raise ValueError("[APS] Step 3c FAILED — No objectId returned.")

        # ── Step 4: Base64 Encode URN ──
        urn_b64 = base64.b64encode(object_id.encode("utf-8")).decode("utf-8").rstrip("=")
        logger.info(f"[APS] Step 4/7: ✅ URN encoded: {urn_b64[:40]}...")

        job_url = "https://developer.api.autodesk.com/modelderivative/v2/designdata/job"

        # ── Step 5: Try direct IFC conversion for .rvt files ──
        if ext == ".rvt":
            try:
                logger.info("[APS] Step 5/7: Triggering RVT → IFC translation job...")
                ifc_payload = json.dumps({
                    "input": {"urn": urn_b64},
                    "output": {"destination": {"region": "us"}, "formats": [{"type": "ifc"}]}
                }).encode("utf-8")
                j_req = urllib.request.Request(
                    job_url, data=ifc_payload,
                    headers={**headers, "Content-Type": "application/json"},
                    method="POST"
                )
                with urllib.request.urlopen(j_req, timeout=30) as j_resp:
                    pass

                # Poll for IFC download URN
                manifest_url = f"https://developer.api.autodesk.com/modelderivative/v2/designdata/{urn_b64}/manifest"
                start_time = time.time()
                download_urn = None
                while time.time() - start_time < 300:
                    time.sleep(5)
                    m_req = urllib.request.Request(manifest_url, headers=headers, method="GET")
                    with urllib.request.urlopen(m_req, timeout=30) as m_resp:
                        manifest = json.loads(m_resp.read().decode("utf-8"))
                    if manifest.get("status") in ["success", "complete"]:
                        for d in manifest.get("derivatives", []):
                            if d.get("outputType") == "ifc":
                                for c in d.get("children", []):
                                    if c.get("role") == "ifc" or c.get("urn", "").endswith(".ifc"):
                                        download_urn = c.get("urn")
                                        break
                        break
                    elif manifest.get("status") == "failed":
                        break

                if download_urn:
                    dl_url = f"https://developer.api.autodesk.com/modelderivative/v2/designdata/{urn_b64}/manifest/{urllib.parse.quote(download_urn, safe='')}"
                    dl_req = urllib.request.Request(dl_url, headers=headers, method="GET")
                    with urllib.request.urlopen(dl_req, timeout=600) as dl_resp:
                        with open(output_ifc_path, "wb") as f_out:
                            f_out.write(dl_resp.read())
                    if os.path.getsize(output_ifc_path) > 0:
                        logger.info("[APS] Step 7/7: ✅ Successfully converted .rvt → .ifc via Autodesk Cloud!")
                        return True
            except Exception as err:
                logger.warning(f"[APS] Direct IFC conversion failed ({err}). Retrying via SVF2 Model Derivative Properties API...")

        # ── Step 5b: Trigger SVF2 translation for .nwd, .nwc, .rfa or fallback .rvt ──
        logger.info(f"[APS] Step 5/7: Triggering SVF2 model derivative translation for {ext.upper()} model...")
        svf_payload = json.dumps({
            "input": {"urn": urn_b64},
            "output": {"destination": {"region": "us"}, "formats": [{"type": "svf2", "views": ["2d", "3d"]}]}
        }).encode("utf-8")
        try:
            j_req = urllib.request.Request(
                job_url, data=svf_payload,
                headers={**headers, "Content-Type": "application/json"},
                method="POST"
            )
            with urllib.request.urlopen(j_req, timeout=30) as j_resp:
                logger.info(f"[APS] Step 5/7: ✅ SVF2 Translation job accepted.")
        except urllib.error.HTTPError as err:
            raise ValueError(f"[APS] Step 5 FAILED — SVF2 job rejected: {_read_http_error(err)}")

        # ── Step 6: Poll SVF2 Manifest Status ──
        manifest_url = f"https://developer.api.autodesk.com/modelderivative/v2/designdata/{urn_b64}/manifest"
        start_time = time.time()
        logger.info("[APS] Step 6/7: Polling SVF2 translation progress...")
        poll_count = 0
        while time.time() - start_time < 300:
            time.sleep(5)
            poll_count += 1
            m_req = urllib.request.Request(manifest_url, headers=headers, method="GET")
            with urllib.request.urlopen(m_req, timeout=30) as m_resp:
                manifest = json.loads(m_resp.read().decode("utf-8"))

            status = manifest.get("status")
            progress = manifest.get("progress")
            logger.info(f"[APS] Step 6/7: Poll #{poll_count} — Progress: {progress} (Status: {status})")

            if status in ["success", "complete"] or progress == "complete":
                break
            elif status == "failed":
                raise ValueError("[APS] Step 6 FAILED — SVF2 translation job failed on Autodesk Cloud.")

        # ── Step 7: Extract quantities directly from Model Derivative Properties API ──
        return cls.extract_quantities_from_aps_properties(urn_b64, headers)

    @classmethod
    def extract_quantities_from_aps_properties(cls, urn_b64: str, headers: dict) -> List[BIMElementQuantity]:
        """
        Extract 3D element quantities directly from Autodesk Cloud Model Derivative Properties API.
        Supports .nwd, .nwc, .rfa, .rvt and all formats viewable in APS.
        Includes GZIP decompression and HTTP 413 objecttree fallback for ultra-large models.
        """
        import json
        import gzip
        import time
        import urllib.request

        gzip_headers = {**headers, "Accept-Encoding": "gzip, deflate"}

        meta_url = f"https://developer.api.autodesk.com/modelderivative/v2/designdata/{urn_b64}/metadata"
        meta_req = urllib.request.Request(meta_url, headers=gzip_headers, method="GET")
        with urllib.request.urlopen(meta_req, timeout=30) as resp:
            raw = resp.read()
            if resp.headers.get("Content-Encoding") == "gzip" or (len(raw) > 2 and raw[:2] == b'\x1f\x8b'):
                raw = gzip.decompress(raw)
            meta_data = json.loads(raw.decode("utf-8"))

        views = meta_data.get("data", {}).get("metadata", [])
        if not views:
            raise ValueError("No view metadata returned by Autodesk Cloud.")

        guid = views[0]["guid"]
        for v in views:
            if v.get("role") == "3d":
                guid = v["guid"]
                break

        props_url = f"https://developer.api.autodesk.com/modelderivative/v2/designdata/{urn_b64}/metadata/{guid}/properties"
        props_data = None
        payload_too_large = False

        logger.info(f"[APS] Step 7/7: Fetching model derivative properties tree with GZIP (GUID: {guid[:8]}...)...")
        for attempt in range(1, 31):
            try:
                p_req = urllib.request.Request(props_url, headers=gzip_headers, method="GET")
                with urllib.request.urlopen(p_req, timeout=120) as resp:
                    if resp.status == 200:
                        raw = resp.read()
                        if resp.headers.get("Content-Encoding") == "gzip" or (len(raw) > 2 and raw[:2] == b'\x1f\x8b'):
                            raw = gzip.decompress(raw)
                        props_data = json.loads(raw.decode("utf-8"))
                        break
            except urllib.error.HTTPError as err:
                if err.code == 202:
                    logger.info(f"[APS] Properties tree is processing... (attempt {attempt}/30)")
                    time.sleep(5)
                    continue
                elif err.code == 413:
                    logger.warning("[APS] Step 7/7: HTTP 413 Payload Too Large for /properties. Falling back to /objecttree hierarchy...")
                    payload_too_large = True
                    break
                else:
                    raise err
            time.sleep(3)

        # Fallback to /objecttree if /properties payload is too large (HTTP 413)
        if payload_too_large or not props_data:
            logger.info("[APS] Fetching model objecttree hierarchy...")
            tree_url = f"https://developer.api.autodesk.com/modelderivative/v2/designdata/{urn_b64}/metadata/{guid}/objecttree"
            tree_req = urllib.request.Request(tree_url, headers=gzip_headers, method="GET")
            with urllib.request.urlopen(tree_req, timeout=120) as resp:
                raw = resp.read()
                if resp.headers.get("Content-Encoding") == "gzip" or (len(raw) > 2 and raw[:2] == b'\x1f\x8b'):
                    raw = gzip.decompress(raw)
                tree_data = json.loads(raw.decode("utf-8"))

            objects_list = []
            def _recurse_tree(node_list):
                for node in node_list:
                    if "name" in node:
                        objects_list.append(node["name"])
                    if "objects" in node and isinstance(node["objects"], list):
                        _recurse_tree(node["objects"])

            root_objects = tree_data.get("data", {}).get("objects", [])
            _recurse_tree(root_objects)
            logger.info(f"[APS] Extracted {len(objects_list):,} object nodes from model object tree.")

            aggregated_map: Dict[tuple, BIMElementQuantity] = {}
            for name in objects_list:
                cat = "IfcBuildingElementProxy"
                name_lower = name.lower()
                if "wall" in name_lower or "dinding" in name_lower: cat = "Walls"
                elif "column" in name_lower or "kolom" in name_lower: cat = "Columns"
                elif "beam" in name_lower or "balok" in name_lower: cat = "Beams"
                elif "floor" in name_lower or "slab" in name_lower or "plat" in name_lower: cat = "Floors"
                elif "window" in name_lower or "jendela" in name_lower: cat = "Windows"
                elif "door" in name_lower or "pintu" in name_lower: cat = "Doors"
                elif "roof" in name_lower or "atap" in name_lower: cat = "Roofs"
                elif "pipe" in name_lower or "pipa" in name_lower: cat = "Pipes"
                elif "duct" in name_lower: cat = "Ducts"

                key = ("Lantai 1", cat, name, "Standard Material")
                if key not in aggregated_map:
                    aggregated_map[key] = BIMElementQuantity(
                        level="Lantai 1", category=cat, family_name=name, material="Standard Material", count=1
                    )
                else:
                    aggregated_map[key].count += 1

            return list(aggregated_map.values())

        raw_collection = props_data.get("data", {}).get("collection", [])
        logger.info(f"[APS] Step 7/7: Successfully retrieved {len(raw_collection):,} elements from Autodesk Cloud properties API.")

        raw_elements = []
        for item in raw_collection:
            props = item.get("properties", {})
            obj_name = item.get("name", "BIM Element")

            # Flatten property groups & preserve full key paths
            flat_props = {}
            if isinstance(props, dict):
                for g_name, g_val in props.items():
                    if isinstance(g_val, dict):
                        for k, v in g_val.items():
                            flat_props[k] = v
                            flat_props[f"{g_name}:{k}"] = v
                    else:
                        flat_props[g_name] = g_val

            # Infer category
            cat_candidates = [
                flat_props.get("Category"),
                flat_props.get("Element Category"),
                flat_props.get("Type Name"),
                flat_props.get("Item Type"),
                flat_props.get("Icon"),
                flat_props.get("Class"),
                obj_name
            ]
            category = "IfcBuildingElementProxy"
            for cand in cat_candidates:
                if cand and isinstance(cand, str) and cand.strip() and cand.strip().lower() not in ["item", "node", "entity", "element", "model", "root"]:
                    category = cand.strip()
                    break

            # Infer level
            level = (
                flat_props.get("Level")
                or flat_props.get("Reference Level")
                or flat_props.get("Base Constraint")
                or flat_props.get("Storey")
                or flat_props.get("Layer")
                or flat_props.get("Location")
                or "Lantai 1"
            )

            # Infer material
            material = (
                flat_props.get("Material")
                or flat_props.get("Structural Material")
                or flat_props.get("Material Name")
                or flat_props.get("Appearance")
                or "Standard Material"
            )

            # Infer family / type
            family = (
                flat_props.get("Family")
                or flat_props.get("Family Name")
                or flat_props.get("Type")
                or flat_props.get("Item Name")
                or flat_props.get("Name")
                or obj_name
            )

            def parse_num(val):
                if isinstance(val, (int, float)):
                    return float(val)
                if isinstance(val, str):
                    import re
                    m = re.search(r"[-+]?\d*\.\d+|\d+", val)
                    if m:
                        try:
                            return float(m.group(0))
                        except ValueError:
                            pass
                return 0.0

            vol = 0.0
            area = 0.0
            length = 0.0

            for k, v in flat_props.items():
                k_lower = k.lower()
                if "volume" in k_lower and vol == 0:
                    vol = parse_num(v)
                elif "area" in k_lower and area == 0:
                    area = parse_num(v)
                elif ("length" in k_lower or "height" in k_lower or "width" in k_lower or "thickness" in k_lower or "depth" in k_lower) and length == 0:
                    length = parse_num(v)

            # Retain element if it has props or valid name
            if props or obj_name:
                raw_elements.append({
                    "level": str(level).strip(),
                    "category": str(category).strip(),
                    "family_name": str(family).strip(),
                    "material": str(material).strip(),
                    "volume_m3": vol,
                    "area_m2": area,
                    "length_m": length
                })

        aggregated_map: Dict[tuple, BIMElementQuantity] = {}
        for el in raw_elements:
            key = (el["level"], el["category"], el["family_name"], el["material"])
            if key not in aggregated_map:
                aggregated_map[key] = BIMElementQuantity(
                    level=el["level"],
                    category=el["category"],
                    family_name=el["family_name"],
                    material=el["material"],
                    count=1,
                    total_volume_m3=round(el["volume_m3"], 3),
                    total_area_m2=round(el["area_m2"], 3),
                    total_length_m=round(el["length_m"], 3)
                )
            else:
                existing = aggregated_map[key]
                existing.count += 1
                existing.total_volume_m3 = round(existing.total_volume_m3 + el["volume_m3"], 3)
                existing.total_area_m2 = round(existing.total_area_m2 + el["area_m2"], 3)
                existing.total_length_m = round(existing.total_length_m + el["length_m"], 3)

        result_list = list(aggregated_map.values())
        logger.info(f"[APS] Aggregated into {len(result_list)} element quantity groups.")
        return result_list

    @staticmethod
    def convert_rvt_to_ifc(rvt_file_path: str, output_ifc_path: str, client_id: str, client_secret: str) -> bool:
        res = APSConverter.convert_autodesk_model(rvt_file_path, output_ifc_path, client_id, client_secret)
        return res is True

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
                    logger.info("APS Credentials detected in .env! Translating 3D model via Autodesk Cloud API...")
                    res = APSConverter.convert_autodesk_model(rvt_file_path, temp_ifc_path, aps_client_id, aps_client_secret)
                    if isinstance(res, list) and len(res) > 0:
                        return res
                    elif res is True and os.path.exists(temp_ifc_path) and os.path.getsize(temp_ifc_path) > 0:
                        return cls.process_ifc_file(temp_ifc_path)
                except Exception as aps_err:
                    logger.warning(f"Autodesk APS Cloud translation failed: {aps_err}. Falling back to local converter...")

            # 2. Local CLI Converter Subprocess Strategy (if system rvt2ifc or IfcConvert is installed)
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

            # 3. Direct instruction fallback for 100% Real Data Policy
            err_msg = (
                "Konversi cloud Autodesk APS tidak berhasil atau mengalami kendala izin/timeout. "
                "Untuk memastikan estimasi RAB 100% PRESISI dan REAL tanpa data buatan/dummy, "
                "silakan ekspor proyek Anda ke format .ifc langsung dari Autodesk Revit (File -> Export -> IFC) "
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
        Process BIM file content (.ifc, .rvt, .rfa, .nwd, or .nwc) from raw bytes.
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
