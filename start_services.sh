#!/usr/bin/env bash
# =============================================================================
# Estimator AI Services Launcher Script
# Runs:
#   1. AHSP & ChromaDB Microservice (port 8100, 1 single worker, ~2.5GB RAM)
#   2. Main Estimator API Gateway (port 8200, 10 workers, ~800MB RAM)
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="${SCRIPT_DIR}/api_v2"

# Detect Python binary (prefer venv)
if [ -f "${API_DIR}/.venv/bin/python" ]; then
    PYTHON_BIN="${API_DIR}/.venv/bin/python"
elif [ -f "${SCRIPT_DIR}/.venv/bin/python" ]; then
    PYTHON_BIN="${SCRIPT_DIR}/.venv/bin/python"
else
    PYTHON_BIN="python"
fi

echo "======================================================================"
echo "🚀 Starting Estimator AI Microservices"
echo "Python: ${PYTHON_BIN}"
echo "======================================================================"

cleanup() {
    echo ""
    echo "🛑 Shutting down microservices..."
    if [ -n "$AHSP_PID" ]; then
        kill "$AHSP_PID" 2>/dev/null || true
    fi
    exit 0
}

trap cleanup SIGINT SIGTERM EXIT

# 1. Start AHSP Microservice (port 8100)
echo "📦 Starting AHSP & ChromaDB Microservice (port 8100)..."
"$PYTHON_BIN" "${API_DIR}/service_ahsp.py" --port 8100 &
AHSP_PID=$!

# 2. Wait for AHSP service to become ready
echo "⏳ Waiting for AHSP Microservice to initialize..."
for i in {1..40}; do
    if curl -s http://127.0.0.1:8100/health >/dev/null 2>&1; then
        echo "✅ AHSP Microservice is UP and ready!"
        break
    fi
    sleep 0.5
done

# 3. Start Main API (port 8200 with 10 workers)
echo "🌐 Starting Main Estimator API Gateway on port 8200 (10 workers)..."
"$PYTHON_BIN" "${API_DIR}/main.py" server --port 8200 --workers 10
