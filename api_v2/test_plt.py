import os
import sys
import tempfile
from cad_parser import CADEntityExtractor

# Sample HPGL / HPGL-2 PLT file content
sample_hpgl_plt = """IN;
IP0,0,10000,10000;
SC0,1000,0,1000;
SP1;
PU1000,2000;
PD3000,2000,3000,5000,1000,5000,1000,2000;
PU;
LBDinding Pasangan Bata t=15cm\x03;
SP2;
PU5000,5000;
PD5000,9000;
PU;
LBKolom Beton K250 30x30cm\x03;
SP3;
PU;
LBPipaning DN50 Pipe L=15m\x03;
"""

def test_plt_extraction():
    with tempfile.NamedTemporaryFile(suffix=".plt", mode="w", delete=False) as tmp:
        tmp.write(sample_hpgl_plt)
        tmp_path = tmp.name

    try:
        res = CADEntityExtractor.extract_from_plt_file(tmp_path)
        print("=== PLT Extraction Result ===")
        print("Source Type:", res.get("source_file_type"))
        print("Layers:", res.get("layers"))
        print("Total Texts Count:", res.get("total_texts_count"))
        print("Text by Layer:", res.get("text_by_layer"))
        print("Dimensions:", res.get("dimensions"))
        print("Geometry by Layer:", res.get("geometry_by_layer"))

        assert res.get("total_texts_count") > 0, "No text extracted!"
        assert len(res.get("layers")) >= 2, "Layers not extracted!"
        assert "PEN_1" in res.get("layers"), "PEN_1 layer missing!"
        
        payload = CADEntityExtractor.format_to_llm_payload(res)
        print("\n=== Formatted LLM Payload ===")
        print(payload)
        print("\nTEST PASSED SUCCESSFULY!")
    finally:
        if os.path.exists(tmp_path):
            os.remove(tmp_path)

if __name__ == "__main__":
    test_plt_extraction()
