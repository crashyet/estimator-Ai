import os
import io
import sys
import math
import shutil
import tempfile
import logging
import subprocess
from typing import Dict, List, Any

import ezdwg
import ezdxf

logger = logging.getLogger(__name__)

def fix_ezdwg_cjk_encoding(text: str) -> str:
    """
    Unpack 16-bit double-packed ASCII bytes produced by ezdwg Rust parser for CJK codepoints.
    Converts corrupted characters like '汒' -> 'Rl', '㉓' -> 'S2'.
    """
    res_chars = []
    for ch in text:
        code = ord(ch)
        if code > 0x7F:
            try:
                le_bytes = ch.encode("utf-16-le")
                decoded = le_bytes.decode("cp1252", errors="ignore")
                # Filter non-printable control characters
                clean_dec = "".join([c for c in decoded if c.isprintable() or c.isalnum() or c in " ._-\\/()"])
                res_chars.append(clean_dec if clean_dec else ch)
            except Exception:
                res_chars.append(ch)
        else:
            res_chars.append(ch)
    return "".join(res_chars).strip()

def get_polyline_length(poly) -> float:
    """Calculate total 2D length of polyline or lwpolyline."""
    try:
        if hasattr(poly, "get_points"):
            points = list(poly.get_points(format='xy'))
        elif hasattr(poly, "vertices"):
            points = [(v.dxf.location.x, v.dxf.location.y) for v in poly.vertices]
        else:
            return 0.0

        if not points or len(points) < 2:
            return 0.0

        total = 0.0
        for i in range(len(points) - 1):
            p1, p2 = points[i], points[i+1]
            total += math.hypot(p2[0] - p1[0], p2[1] - p1[1])

        if getattr(poly, "is_closed", False) or getattr(poly.dxf, "is_closed", False):
            p1, p2 = points[-1], points[0]
            total += math.hypot(p2[0] - p1[0], p2[1] - p1[1])

        return total
    except Exception:
        return 0.0


class CADEntityExtractor:
    """
    Direct CAD Vector Data Extractor for DWG and DXF files.
    Extracts text, dimensions, block attributes, and geometric measurements deterministically.
    """

    @staticmethod
    def extract_from_dxf_file(dxf_file_path: str) -> Dict[str, Any]:
        """Extract text, dimensions, block attributes, and geometry measurements from a DXF file."""
        try:
            doc = ezdxf.readfile(dxf_file_path)
            return CADEntityExtractor._process_ezdxf_doc(doc)
        except Exception as e:
            logger.error(f"Error reading DXF file {dxf_file_path}: {e}")
            return {"error": str(e), "layers": [], "text_by_layer": {}, "block_attributes": [], "dimensions": [], "geometry_by_layer": {}}

    @staticmethod
    def extract_from_dwg_file(dwg_file_path: str, timeout_seconds: int = 15) -> Dict[str, Any]:
        """Extract CAD data from DWG file using dwg2dxf / ODAFileConverter / ezdwg in a subprocess with timeout."""
        temp_dxf_path = None
        dwg_version = "Unknown"
        try:
            # Check version header quickly
            try:
                dwg_doc = ezdwg.read(dwg_file_path)
                dwg_version = getattr(dwg_doc, "version", "Unknown")
            except Exception:
                pass

            logger.info(f"Reading native DWG file: {dwg_file_path} (Header Version: {dwg_version})")
            with tempfile.NamedTemporaryFile(suffix=".dxf", delete=False) as tmp_file:
                temp_dxf_path = tmp_file.name

            # 1. Try system dwg2dxf CLI converter if available
            project_bin = os.path.join(os.path.dirname(__file__), "bin", "dwg2dxf")
            dwg2dxf_bin = (
                shutil.which("dwg2dxf") or 
                (project_bin if os.path.exists(project_bin) else None) or
                ("/usr/local/bin/dwg2dxf" if os.path.exists("/usr/local/bin/dwg2dxf") else None) or
                ("/tmp/libredwg_out/bin/dwg2dxf" if os.path.exists("/tmp/libredwg_out/bin/dwg2dxf") else None)
            )
            if dwg2dxf_bin:
                try:
                    logger.info(f"Attempting conversion using system dwg2dxf converter ({dwg2dxf_bin})...")
                    res = subprocess.run([dwg2dxf_bin, "-y", "-o", temp_dxf_path, dwg_file_path], timeout=timeout_seconds, capture_output=True, text=True)
                    if os.path.exists(temp_dxf_path) and os.path.getsize(temp_dxf_path) > 0:
                        out = CADEntityExtractor.extract_from_dxf_file(temp_dxf_path)
                        out["source_file_type"] = f"DWG ({dwg_version} via dwg2dxf)"
                        return out
                    else:
                        logger.warning(f"dwg2dxf produced no output. Stderr: {res.stderr.strip()}")
                except Exception as ex:
                    logger.warning(f"dwg2dxf attempt failed: {ex}")

            # 2. Try system ODAFileConverter CLI tool if available
            oda_bin = shutil.which("ODAFileConverter") or shutil.which("oda-file-converter")
            if oda_bin:
                try:
                    in_dir = os.path.dirname(dwg_file_path)
                    out_dir = os.path.dirname(temp_dxf_path)
                    base_name = os.path.basename(dwg_file_path)
                    logger.info("Attempting conversion using system ODAFileConverter...")
                    subprocess.run([oda_bin, in_dir, out_dir, "ACAD2010", "DXF", "0", "1"], timeout=timeout_seconds, capture_output=True, text=True)
                    expected_dxf = os.path.join(out_dir, os.path.splitext(base_name)[0] + ".dxf")
                    if os.path.exists(expected_dxf) and os.path.getsize(expected_dxf) > 0:
                        out = CADEntityExtractor.extract_from_dxf_file(expected_dxf)
                        out["source_file_type"] = f"DWG ({dwg_version} via ODAFileConverter)"
                        try:
                            os.remove(expected_dxf)
                        except Exception:
                            pass
                        return out
                except Exception as ex:
                    logger.warning(f"ODAFileConverter attempt failed: {ex}")

            # 3. Fall back to ezdwg PyO3 engine in isolated worker process
            logger.info(f"Converting DWG to DXF via ezdwg in isolated worker process (timeout: {timeout_seconds}s)...")
            
            cmd = [
                sys.executable, "-c",
                f"import ezdwg; ezdwg.to_dxf({dwg_file_path!r}, {temp_dxf_path!r}, dxf_version='R2010', explode_dimensions=False)"
            ]
            conv_proc = subprocess.run(cmd, timeout=timeout_seconds, capture_output=True, text=True)

            if conv_proc.returncode != 0:
                err_msg = conv_proc.stderr.strip() or f"Process exited with code {conv_proc.returncode}"
                raise RuntimeError(f"ezdwg conversion failed: {err_msg}")

            if not os.path.exists(temp_dxf_path) or os.path.getsize(temp_dxf_path) == 0:
                raise RuntimeError("ezdwg generated empty DXF output.")

            res = CADEntityExtractor.extract_from_dxf_file(temp_dxf_path)
            res["source_file_type"] = f"DWG ({dwg_version} native vector)"
            return res

        except subprocess.TimeoutExpired:
            logger.error(f"ezdwg DWG conversion timed out after {timeout_seconds}s (File version: {dwg_version}).")
            err_details = (
                f"DWG parsing timed out ({timeout_seconds}s). File version is '{dwg_version}' "
                "(AutoCAD 2018+ DWG format AC1032 causes infinite handle loops in ezdwg parser). "
                "Please save/export your DWG file as AutoCAD 2013/2010 DWG format or DXF, or upload a PDF document."
            )
            return {
                "error": err_details,
                "layers": [], "text_by_layer": {}, "block_attributes": [], "dimensions": [], "geometry_by_layer": {}
            }
        except Exception as e:
            logger.warning(f"ezdwg DWG to DXF conversion failed ({e}). Trying fallback...")
            return {
                "error": f"Failed to parse DWG file ({dwg_version}): {str(e)}. Please save as AutoCAD 2013/2010 DWG, DXF format, or upload as PDF.",
                "layers": [], "text_by_layer": {}, "block_attributes": [], "dimensions": [], "geometry_by_layer": {}
            }
        finally:
            if temp_dxf_path and os.path.exists(temp_dxf_path):
                try:
                    os.remove(temp_dxf_path)
                except Exception:
                    pass

    @staticmethod
    def _process_ezdxf_doc(doc) -> Dict[str, Any]:
        """Process ezdxf Document object and extract structured texts, layers, blocks, dimensions, and geometry measurements."""
        layers = sorted([layer.dxf.name for layer in doc.layers])
        msp = doc.modelspace()
        
        text_by_layer: Dict[str, List[str]] = {}
        block_attributes: List[Dict[str, str]] = []
        dimensions: List[str] = []
        geometry_by_layer: Dict[str, Dict[str, Any]] = {}
        total_texts_count = 0

        # 1. Extract TEXT and MTEXT entities
        for entity in msp.query("TEXT MTEXT"):
            layer_name = getattr(entity.dxf, "layer", "DEFAULT")
            text_str = ""
            if hasattr(entity, "plain_text"):
                text_str = entity.plain_text()
            elif hasattr(entity.dxf, "text"):
                text_str = entity.dxf.text
            elif hasattr(entity, "text"):
                text_str = entity.text

            text_clean = fix_ezdwg_cjk_encoding(str(text_str))
            if text_clean:
                if layer_name not in text_by_layer:
                    text_by_layer[layer_name] = []
                text_by_layer[layer_name].append(text_clean)
                total_texts_count += 1

        # 2. Extract Block Attributes (INSERT entities)
        for insert in msp.query("INSERT"):
            block_name = getattr(insert.dxf, "name", "UNKNOWN_BLOCK")
            layer = getattr(insert.dxf, "layer", "DEFAULT")
            if hasattr(insert, "attribs"):
                attrib_dict = {}
                for attrib in insert.attribs:
                    tag = getattr(attrib.dxf, "tag", "")
                    val = getattr(attrib.dxf, "text", "")
                    if tag and val:
                        attrib_dict[tag] = fix_ezdwg_cjk_encoding(str(val))
                if attrib_dict:
                    block_attributes.append({
                        "block_name": block_name,
                        "layer": layer,
                        "attributes": attrib_dict
                    })

        # 3. Extract DIMENSION entities
        for dim in msp.query("DIMENSION"):
            dim_layer = getattr(dim.dxf, "layer", "DEFAULT")
            dim_text = fix_ezdwg_cjk_encoding(getattr(dim.dxf, "text", ""))
            dim_val = getattr(dim.dxf, "actual_measurement", None)
            
            val_str = dim_text if dim_text else (f"{dim_val:.2f}" if dim_val is not None else "")
            if val_str:
                dimensions.append(f"[{dim_layer}] {val_str}")

        # 4. Extract Geometry Measurements (LINE & POLYLINE total lengths) per layer
        for line in msp.query("LINE"):
            layer = getattr(line.dxf, "layer", "DEFAULT")
            p1, p2 = line.dxf.start, line.dxf.end
            length = math.hypot(p2.x - p1.x, p2.y - p1.y, getattr(p2, 'z', 0.0) - getattr(p1, 'z', 0.0))
            if layer not in geometry_by_layer:
                geometry_by_layer[layer] = {"total_length": 0.0, "entity_count": 0}
            geometry_by_layer[layer]["total_length"] += length
            geometry_by_layer[layer]["entity_count"] += 1

        for poly in msp.query("LWPOLYLINE POLYLINE"):
            layer = getattr(poly.dxf, "layer", "DEFAULT")
            length = get_polyline_length(poly)
            if layer not in geometry_by_layer:
                geometry_by_layer[layer] = {"total_length": 0.0, "entity_count": 0}
            geometry_by_layer[layer]["total_length"] += length
            geometry_by_layer[layer]["entity_count"] += 1

        logger.info(f"CAD Extracted: {len(layers)} layers, {total_texts_count} text items across {len(text_by_layer)} layers, {len(block_attributes)} block attributes, {len(dimensions)} dimensions.")

        return {
            "layers": layers,
            "text_by_layer": text_by_layer,
            "block_attributes": block_attributes,
            "dimensions": dimensions,
            "geometry_by_layer": geometry_by_layer,
            "total_texts_count": total_texts_count,
            "source_file_type": "DXF / Converted DWG"
        }

    @staticmethod
    def _process_ezdwg_doc(dwg_doc) -> Dict[str, Any]:
        """Fallback direct ezdwg document processing."""
        text_by_layer: Dict[str, List[str]] = {}
        msp = dwg_doc.modelspace()
        total_texts_count = 0

        try:
            for entity in msp:
                layer = getattr(entity, "layer", "DEFAULT")
                txt = getattr(entity, "text", "") or getattr(entity, "string", "")
                if txt and str(txt).strip():
                    clean_txt = fix_ezdwg_cjk_encoding(str(txt))
                    if layer not in text_by_layer:
                        text_by_layer[layer] = []
                    text_by_layer[layer].append(clean_txt)
                    total_texts_count += 1
        except Exception as e:
            logger.warning(f"Iterating ezdwg modelspace directly: {e}")

        return {
            "layers": sorted(list(text_by_layer.keys())),
            "text_by_layer": text_by_layer,
            "block_attributes": [],
            "dimensions": [],
            "geometry_by_layer": {},
            "total_texts_count": total_texts_count,
            "source_file_type": "DWG (ezdwg direct fallback)"
        }

    @classmethod
    def process_file_bytes(cls, file_bytes: bytes, filename: str) -> Dict[str, Any]:
        """Process DWG or DXF file from raw bytes."""
        ext = os.path.splitext(filename)[1].lower()
        with tempfile.NamedTemporaryFile(suffix=ext, delete=False) as tmp:
            tmp.write(file_bytes)
            tmp_path = tmp.name

        try:
            if ext == ".dwg":
                return cls.extract_from_dwg_file(tmp_path)
            elif ext == ".dxf":
                return cls.extract_from_dxf_file(tmp_path)
            else:
                raise ValueError(f"Unsupported CAD extension: {ext}")
        finally:
            if os.path.exists(tmp_path):
                os.remove(tmp_path)

    @classmethod
    def format_to_llm_payload(cls, cad_data: Dict[str, Any]) -> str:
        """
        Format extracted CAD entity data into deterministic structured Markdown context string
        optimized for LLM Quantity Takeoff reasoning.
        """
        lines = []
        lines.append("=== DRAWING LAYERS & VECTOR CAD ENTITIES DUMP ===")
        lines.append(f"Source Type: {cad_data.get('source_file_type', 'CAD Vector')}")
        lines.append(f"Total Text Entities: {cad_data.get('total_texts_count', 0)}")
        lines.append("")

        # 1. Text & MText grouped by layer (sorted deterministically)
        text_by_layer = cad_data.get("text_by_layer", {})
        if text_by_layer:
            lines.append("--- LAYER-WISE TEXT & ANNOTATIONS ---")
            for layer_name in sorted(text_by_layer.keys()):
                text_list = text_by_layer[layer_name]
                lines.append(f"\n[LAYER: {layer_name}]")
                unique_texts = sorted(list(dict.fromkeys(text_list)))
                for txt in unique_texts[:200]:
                    lines.append(f"- {txt}")

        # 2. Block attributes & Schedules
        block_attributes = cad_data.get("block_attributes", [])
        if block_attributes:
            lines.append("\n--- BLOCK ATTRIBUTES & SCHEDULE TABLES ---")
            for blk in block_attributes[:100]:
                attrs_str = ", ".join([f"{k}: {v}" for k, v in sorted(blk['attributes'].items())])
                lines.append(f"- [Block: {blk['block_name']} | Layer: {blk['layer']}] -> {attrs_str}")

        # 3. Dimensions
        dimensions = cad_data.get("dimensions", [])
        if dimensions:
            lines.append("\n--- DIMENSION NOTATIONS & MEASUREMENTS ---")
            unique_dims = sorted(list(dict.fromkeys(dimensions)))
            for dim in unique_dims[:150]:
                lines.append(f"- {dim}")

        # 4. Geometry Measurements & Total Lengths per layer (sorted deterministically)
        geometry_by_layer = cad_data.get("geometry_by_layer", {})
        if geometry_by_layer:
            lines.append("\n--- LAYER GEOMETRY MEASUREMENTS (LINEAR LENGTHS - NOT AREA) ---")
            lines.append("Note: CAD drawings in Indonesia are drawn in Millimeters (mm) or Centimeters (cm). Check dimension text to confirm unit scale.")
            for layer_name in sorted(geometry_by_layer.keys()):
                geo = geometry_by_layer[layer_name]
                tot_len = geo.get("total_length", 0.0)
                count = geo.get("entity_count", 0)
                tot_m_if_mm = tot_len / 1000.0
                tot_m_if_cm = tot_len / 100.0
                lines.append(
                    f"- [LAYER: {layer_name}] Linear Length: {tot_len:.2f} CAD units "
                    f"(={tot_m_if_mm:.2f}m if mm, ={tot_m_if_cm:.2f}m if cm) | Entities Count: {count}"
                )

        return "\n".join(lines)
