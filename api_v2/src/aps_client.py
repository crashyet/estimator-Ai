import os
import base64
import json
import time
import gzip
import logging
import urllib.request
import urllib.parse
import urllib.error
from typing import List, Dict, Any, Union

from src.schemas import BIMElementQuantity

logger = logging.getLogger(__name__)

class APSConverter:
    """
    Autodesk Platform Services (APS) Cloud Converter.
    Converts native Revit (.rvt, .rfa) and Navisworks (.nwd, .nwc) models via Model Derivative API.
    """

    @classmethod
    def convert_autodesk_model(
        cls, file_path: str, output_ifc_path: str, client_id: str, client_secret: str
    ) -> Union[bool, List[BIMElementQuantity]]:

        def _read_http_error(err: urllib.error.HTTPError) -> str:
            try:
                body = err.read().decode("utf-8", errors="ignore")[:500]
                return f"HTTP {err.code} {err.reason}: {body}"
            except Exception:
                return f"HTTP {err.code} {err.reason}"

        ext = os.path.splitext(file_path)[1].lower()

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

        urn_b64 = base64.b64encode(object_id.encode("utf-8")).decode("utf-8").rstrip("=")
        logger.info(f"[APS] Step 4/7: ✅ URN encoded: {urn_b64[:40]}...")

        job_url = "https://developer.api.autodesk.com/modelderivative/v2/designdata/job"

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

        return cls.extract_quantities_from_aps_properties(urn_b64, headers)

    @classmethod
    def extract_quantities_from_aps_properties(cls, urn_b64: str, headers: dict) -> List[BIMElementQuantity]:
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

            flat_props = {}
            if isinstance(props, dict):
                for g_name, g_val in props.items():
                    if isinstance(g_val, dict):
                        for k, v in g_val.items():
                            flat_props[k] = v
                            flat_props[f"{g_name}:{k}"] = v
                    else:
                        flat_props[g_name] = g_val

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

            level = (
                flat_props.get("Level")
                or flat_props.get("Reference Level")
                or flat_props.get("Base Constraint")
                or flat_props.get("Storey")
                or flat_props.get("Layer")
                or flat_props.get("Location")
                or "Lantai 1"
            )

            material = (
                flat_props.get("Material")
                or flat_props.get("Structural Material")
                or flat_props.get("Material Name")
                or flat_props.get("Appearance")
                or "Standard Material"
            )

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
