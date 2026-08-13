#!/usr/bin/env bash
# Local Revit (.rvt) to OpenBIM (.ifc) converter wrapper
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_PYTHON="$SCRIPT_DIR/../.venv/bin/python3"
SRC_SCRIPT="$SCRIPT_DIR/rvt2ifc_src.py"

if [ -f "$VENV_PYTHON" ]; then
    exec "$VENV_PYTHON" "$SRC_SCRIPT" "$@"
else
    exec python3 "$SRC_SCRIPT" "$@"
fi
