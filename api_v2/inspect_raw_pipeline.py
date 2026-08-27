#!/usr/bin/env python3
"""
Raw Pipeline Inspector & Evaluator — AI Estimator + VectorDB + Reranker Debug Tool.

Allows developers and maintainers to inspect raw outputs at every stage of the estimation pipeline BEFORE sending to the frontend:
1. [RAW AI OUTPUT] Raw LLM Takeoff extraction (Gemini / Primary API)
2. [RAW VECTORDB SEARCH] Initial ChromaDB cosine similarity candidates
3. [RAW RERANKED RESULTS] Multi-tier CrossEncoder BGE-M3 / Cohere / Heuristic reranked scores and deltas
4. [FINAL FRONTEND PAYLOAD] Final enriched payload structure

Usage Examples:
---------------
1. Inspect a single item mapping (VectorDB + Reranker):
   python inspect_raw_pipeline.py --item "Pemasangan bata ringan 10cm" --unit "m2"

2. Inspect a complete drawing / document file (AI + VectorDB + Reranker):
   python inspect_raw_pipeline.py --file path/to/drawing.dwg --project "Proyek Uji"

3. Inspect an existing raw AI Takeoff JSON file:
   python inspect_raw_pipeline.py --json path/to/takeoff.json

4. Save raw debug output to custom JSON file:
   python inspect_raw_pipeline.py --file drawing.pdf --output raw_eval_result.json
"""

import os
import sys
import json
import time
import argparse
from typing import Dict, Any, Optional

# Add current directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from cad_parser import CADEntityExtractor
from bim_parser import BIMEntityExtractor
from llm_estimator import CADLLMEstimator
from ahsp.ahsp_mapper import mapper_engine, initialize_mapper
from schemas import DynamicTakeoffResponse

# ANSI Terminal Color Helpers
COLOR_HEADER = "\033[95m\033[1m"
COLOR_CYAN = "\033[96m"
COLOR_GREEN = "\033[92m"
COLOR_YELLOW = "\033[93m"
COLOR_RED = "\033[91m"
COLOR_BOLD = "\033[1m"
COLOR_RESET = "\033[0m"


def print_banner():
    """Print tool header banner."""
    print(f"{COLOR_HEADER}{'='*80}")
    print("      🔍 AI ESTIMATOR RAW PIPELINE INSPECTOR & EVALUATOR TOOL")
    print(f"{'='*80}{COLOR_RESET}")


def format_status(status: str) -> str:
    """Format status string with color."""
    if status == "mapped_high":
        return f"{COLOR_GREEN}MAPPED HIGH{COLOR_RESET}"
    elif status == "mapped_medium":
        return f"{COLOR_YELLOW}MAPPED MEDIUM{COLOR_RESET}"
    else:
        return f"{COLOR_RED}UNMAPPED{COLOR_RESET}"


def inspect_single_item_cli(item_name: str, item_unit: str = "", top_k: int = 5) -> Dict[str, Any]:
    """Inspect mapping pipeline for a single work item."""
    if not mapper_engine.is_ready():
        print(f"{COLOR_YELLOW}Initializing AHSP Mapper Engine...{COLOR_RESET}")
        initialize_mapper()

    print(f"\n{COLOR_CYAN}▶ Inspecting Item: '{item_name}' (Unit: '{item_unit}'){COLOR_RESET}\n")

    result = mapper_engine.inspect_single_item(item_name, item_unit, top_k=top_k)

    if "error" in result:
        print(f"{COLOR_RED}Error: {result['error']}{COLOR_RESET}")
        return result

    # 1. Query Info
    q_info = result["query"]
    print(f"{COLOR_BOLD}[1. INPUT & NORMALIZATION]{COLOR_RESET}")
    print(f"  - Original Name: {q_info['input_name']}")
    print(f"  - Unit:          {q_info['input_unit']}")
    print(f"  - Cleaned Query: {q_info['cleaned_name']}")

    # 2. Engine Stats
    stats = result.get("stats", {})
    print(f"\n{COLOR_BOLD}[2. ENGINE CONFIGURATION]{COLOR_RESET}")
    print(f"  - Active Reranker: {stats.get('active_reranker')}")
    print(f"  - Embedding Model: {stats.get('embedding_model')}")
    print(f"  - Total Indexed:   {stats.get('total_indexed_items')} items")

    # 3. Raw VectorDB Output
    print(f"\n{COLOR_BOLD}[3. RAW VECTORDB SEARCH RESULTS (ChromaDB Cosine Similarity)]{COLOR_RESET}")
    raw_vector = result.get("raw_vectordb_candidates", [])
    if not raw_vector:
        print("  (No VectorDB candidates found)")
    else:
        print(f"  {'Rank':<5} | {'Code':<12} | {'Base Score':<10} | {'Unit':<6} | {'AHSP Name'}")
        print("  " + "-" * 75)
        for cand in raw_vector:
            print(f"  #{cand['vector_rank']:<4} | {cand['id_pekerjaan']:<12} | {cand['base_score']:<10} | {cand['satuan']:<6} | {cand['nama_pekerjaan']}")

    # 4. Raw Reranked Output
    print(f"\n{COLOR_BOLD}[4. RAW RERANKED RESULTS (CrossEncoder BGE-M3 / Cohere / Heuristic)]{COLOR_RESET}")
    raw_reranked = result.get("raw_reranked_candidates", [])
    if not raw_reranked:
        print("  (No Reranked candidates found)")
    else:
        print(f"  {'Rank':<5} | {'Code':<12} | {'Rerank Score':<12} | {'Base Score':<10} | {'Delta':<8} | {'AHSP Name'}")
        print("  " + "-" * 85)
        for cand in raw_reranked:
            rank_str = f"#{cand['rank']}"
            r_score = cand['reranked_score']
            b_score = cand['base_vector_score']
            delta = cand['score_delta']
            print(f"  {rank_str:<5} | {cand['id_pekerjaan']:<12} | {r_score:<12} | {b_score:<10} | {delta:<8} | {cand['nama_pekerjaan']}")

    # 5. Final Mapping Decision
    final_map = result.get("final_mapping", {})
    status_fmt = format_status(final_map.get("ahsp_status"))
    print(f"\n{COLOR_BOLD}[5. FINAL MAPPING DECISION]{COLOR_RESET}")
    print(f"  - Status:     {status_fmt}")
    print(f"  - Code:       {final_map.get('ahsp_code') or 'None'}")
    print(f"  - Name:       {final_map.get('ahsp_name') or 'None'}")
    print(f"  - Unit:       {final_map.get('ahsp_unit') or 'None'}")
    print(f"  - Final Score:{final_map.get('ahsp_score') or 0.0}")

    return result


def inspect_file_pipeline(file_path: str, project_name: str = "Proyek Evaluasi", client_name: str = "Client", output_file: Optional[str] = None) -> Dict[str, Any]:
    """Run full pipeline on a file and display step-by-step raw outputs."""
    if not os.path.exists(file_path):
        print(f"{COLOR_RED}Error: File not found: {file_path}{COLOR_RESET}")
        return {}

    ext = os.path.splitext(file_path)[1].lower()
    print(f"\n{COLOR_CYAN}▶ Loading File: {file_path} ({ext}){COLOR_RESET}")

    with open(file_path, "rb") as f:
        file_bytes = f.read()

    # Step 1: Run AI Inference (Raw AI Output)
    print(f"\n{COLOR_BOLD}=== STEP 1: EXECUTING AI EXTRACTION (LLM ESTIMATOR) ==={COLOR_RESET}")
    start_ai = time.time()
    estimator = CADLLMEstimator()

    takeoff_result: Optional[DynamicTakeoffResponse] = None

    if ext in [".ifc", ".rvt", ".rfa", ".nwd", ".nwc", ".skp"]:
        print(f"Parsing 3D BIM parametric quantities...")
        bim_quantities = BIMEntityExtractor.process_bim_bytes(file_bytes, os.path.basename(file_path))
        bim_payload = BIMEntityExtractor.format_to_llm_payload(bim_quantities)
        takeoff_result = estimator.analyze_bim_payload(bim_payload, project_name=project_name, client_name=client_name)
    elif ext in [".dwg", ".dxf", ".dwt", ".dwf", ".dwfx", ".svg", ".plt", ".hpgl", ".hpg"]:
        print(f"Parsing CAD vector entities...")
        cad_data = CADEntityExtractor.process_file_bytes(file_bytes, os.path.basename(file_path))
        cad_payload = CADEntityExtractor.format_to_llm_payload(cad_data)
        takeoff_result = estimator.analyze_cad_payload(cad_payload, project_name=project_name, client_name=client_name)
    elif ext in [".jpeg", ".png", ".jpg"]:
        mime_map = {".jpeg": "image/jpeg", ".jpg": "image/jpeg", ".png": "image/png"}
        mime_type = mime_map.get(ext, "image/jpeg")
        print(f"Sending raw image to LLM estimator...")
        takeoff_result = estimator.analyze_image_bytes(file_bytes, filename=os.path.basename(file_path), mime_type=mime_type, project_name=project_name, client_name=client_name)
    elif ext == ".pdf":
        print(f"Sending raw PDF to LLM estimator...")
        takeoff_result = estimator.analyze_pdf_bytes(file_bytes, filename=os.path.basename(file_path), project_name=project_name, client_name=client_name)
    else:
        print(f"{COLOR_RED}Unsupported file extension: {ext}{COLOR_RESET}")
        return {}

    ai_elapsed = time.time() - start_ai
    print(f"✅ AI Extraction complete in {ai_elapsed:.2f}s.")

    # Step 2: Initialize AHSP Mapper & Inspect Pipeline
    print(f"\n{COLOR_BOLD}=== STEP 2: RUNNING VECTORDB SEARCH & RERANKING INSPECTION ==={COLOR_RESET}")
    if not mapper_engine.is_ready():
        initialize_mapper()

    inspection_report = mapper_engine.inspect_takeoff_response(takeoff_result)

    # Step 3: Console Display
    summary = inspection_report.get("summary", {})
    print(f"\n{COLOR_CYAN}--- PIPELINE EVALUATION SUMMARY ---{COLOR_RESET}")
    print(f"Project Title: {takeoff_result.project.title}")
    print(f"Total Work Items: {summary.get('total_items')}")
    print(f"Mapped High Confidence:   {summary.get('mapped_high')} items ({summary.get('high_ratio')})")
    print(f"Mapped Medium Confidence: {summary.get('mapped_medium')} items")
    print(f"Unmapped:                 {summary.get('unmapped')} items")

    print(f"\n{COLOR_BOLD}=== STEP 3: WORK ITEM DETAILED BREAKDOWN ==={COLOR_RESET}")
    items_breakdown = inspection_report.get("items_pipeline_breakdown", [])

    for idx, item in enumerate(items_breakdown, 1):
        ai_item = item["ai_raw_item"]
        final_map = item["final_mapping"]
        status_fmt = format_status(final_map.get("ahsp_status"))

        print(f"\n--------------------------------------------------------------------------------")
        print(f"Item #{idx} [{item['section_code']}] {ai_item['name']} | Vol: {ai_item['volume']} {ai_item['unit']}")
        print(f"  AI Warning/Formula: {ai_item.get('warning_note') or 'None'}")
        print(f"  Final Mapping Status: {status_fmt} (Score: {final_map.get('ahsp_score')})")
        print(f"  Assigned AHSP Code:   {final_map.get('ahsp_code')} — {final_map.get('ahsp_name')}")

        # Show Top 3 VectorDB raw vs Reranked raw comparison
        raw_v = item.get("raw_vectordb_candidates", [])[:3]
        raw_r = item.get("raw_reranked_candidates", [])[:3]

        if raw_v:
            top_v = raw_v[0]
            print(f"  Raw VectorDB Top-1:   [{top_v['id_pekerjaan']}] {top_v['nama_pekerjaan']} (Base Score: {top_v['base_score']})")

        if raw_r:
            top_r = raw_r[0]
            print(f"  Raw Reranked Top-1:   [{top_r['id_pekerjaan']}] {top_r['nama_pekerjaan']} (Final Score: {top_r['reranked_score']}, Delta: {top_r['score_delta']})")

    # Step 4: Generate Final Frontend Response
    frontend_payload = takeoff_result.to_frontend_format()
    inspection_report["final_frontend_payload"] = frontend_payload

    # Step 5: Save JSON report
    if not output_file:
        output_file = f"raw_pipeline_inspection_{int(time.time())}.json"

    with open(output_file, "w", encoding="utf-8") as out_f:
        json.dump(inspection_report, out_f, indent=2, ensure_ascii=False)

    print(f"\n{COLOR_GREEN}{'='*80}")
    print(f"✅ RAW INSPECTION REPORT SAVED TO: {os.path.abspath(output_file)}")
    print(f"{'='*80}{COLOR_RESET}")

    return inspection_report


def inspect_json_pipeline(json_path: str, output_file: Optional[str] = None) -> Dict[str, Any]:
    """Load existing raw AI Takeoff JSON and run inspection."""
    if not os.path.exists(json_path):
        print(f"{COLOR_RED}Error: File not found: {json_path}{COLOR_RESET}")
        return {}

    print(f"\n{COLOR_CYAN}▶ Loading Takeoff JSON: {json_path}{COLOR_RESET}")
    with open(json_path, "r", encoding="utf-8") as f:
        data = json.load(f)

    takeoff_res = DynamicTakeoffResponse(**data)

    if not mapper_engine.is_ready():
        initialize_mapper()

    report = mapper_engine.inspect_takeoff_response(takeoff_res)

    if not output_file:
        output_file = f"raw_pipeline_json_inspection_{int(time.time())}.json"

    with open(output_file, "w", encoding="utf-8") as out_f:
        json.dump(report, out_f, indent=2, ensure_ascii=False)

    print(f"\n{COLOR_GREEN}✅ Inspection report saved to: {output_file}{COLOR_RESET}")
    return report


def main():
    print_banner()

    parser = argparse.ArgumentParser(
        description="Inspect raw AI outputs, VectorDB candidates, and Reranked scores for evaluation and maintenance."
    )
    parser.add_argument("--item", help="Work item name to inspect (e.g. 'Pemasangan bata ringan')")
    parser.add_argument("--unit", default="", help="Work item unit (e.g. 'm2', 'm3', 'kg')")
    parser.add_argument("--file", help="Path to input drawing/document (.dwg, .dxf, .ifc, .rvt, .pdf, .png, .jpg)")
    parser.add_argument("--json", help="Path to existing AI Takeoff JSON file")
    parser.add_argument("--project", default="Proyek Evaluasi", help="Project Title for file analysis")
    parser.add_argument("--client", default="Client", help="Client Name for file analysis")
    parser.add_argument("--output", help="Custom path to save JSON raw inspection report")
    parser.add_argument("--top_k", type=int, default=5, help="Number of candidates to inspect (default: 5)")

    args = parser.parse_args()

    if args.item:
        inspect_single_item_cli(args.item, args.unit, top_k=args.top_k)
    elif args.file:
        inspect_file_pipeline(args.file, project_name=args.project, client_name=args.client, output_file=args.output)
    elif args.json:
        inspect_json_pipeline(args.json, output_file=args.output)
    else:
        # Interactive mode if no args provided
        print(f"\n{COLOR_YELLOW}No CLI arguments passed. Entering Interactive Single-Item Mode...{COLOR_RESET}")
        try:
            item_input = input("Enter item name (e.g. 'Pemasangan bata ringan'): ").strip()
            unit_input = input("Enter unit (optional, e.g. 'm2'): ").strip()
            if item_input:
                inspect_single_item_cli(item_input, unit_input, top_k=args.top_k)
            else:
                print("No item provided. Use --help to view available commands.")
        except (KeyboardInterrupt, EOFError):
            print("\nExiting.")


if __name__ == "__main__":
    main()
