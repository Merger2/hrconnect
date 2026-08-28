#!/bin/bash
# =============================================================================
# OWASP ZAP Security Scan Runner for HRConnect
# =============================================================================
# Usage:
#   ./run-scan.sh                    # Run baseline scan (passive only)
#   ./run-scan.sh --full             # Run full scan (passive + active)
#   ./run-scan.sh --api              # Run API scan (requires OpenAPI spec)
#   ./run-scan.sh --report-only      # Generate report from existing results
# =============================================================================

set -e

# Configuration
TARGET_URL="${TARGET_URL:-http://localhost:8000}"
REPORT_DIR="tests/security/zap/reports"
CONTEXT_FILE="tests/security/zap/context/hrconnect.context"
BASELINE_CONF="tests/security/zap/zap-baseline.conf"
FULL_CONF="tests/security/zap/zap-full.conf"
ZAP_IMAGE="zaproxy/zap-stable"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create report directory
mkdir -p "$REPORT_DIR"

# Parse arguments
SCAN_TYPE="baseline"
for arg in "$@"; do
    case $arg in
        --full)
            SCAN_TYPE="full"
            ;;
        --api)
            SCAN_TYPE="api"
            ;;
        --report-only)
            SCAN_TYPE="report-only"
            ;;
        --help|-h)
            echo "Usage: $0 [--full|--api|--report-only]"
            echo ""
            echo "Options:"
            echo "  --full          Run full scan (passive + active)"
            echo "  --api           Run API scan (requires OpenAPI spec)"
            echo "  --report-only   Generate report from existing results"
            echo "  --help          Show this help message"
            echo ""
            echo "Environment Variables:"
            echo "  TARGET_URL      Target URL (default: http://localhost:8000)"
            exit 0
            ;;
    esac
done

echo "=========================================="
echo "  OWASP ZAP Security Scan - HRConnect"
echo "=========================================="
echo ""
echo "Target: $TARGET_URL"
echo "Scan Type: $SCAN_TYPE"
echo "Timestamp: $TIMESTAMP"
echo ""

# Check if Docker is available
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Use sudo for docker commands if needed
DOCKER_CMD="docker"
if ! docker info &> /dev/null 2>&1; then
    DOCKER_CMD="sudo docker"
fi

# Check if ZAP image exists
if ! $DOCKER_CMD image inspect "$ZAP_IMAGE" &> /dev/null; then
    echo "📦 Pulling ZAP Docker image..."
    $DOCKER_CMD pull "$ZAP_IMAGE"
fi

# Function to run baseline scan
run_baseline() {
    echo "🔍 Running Baseline Scan (Passive Only)..."
    echo "Duration: ~10 minutes"
    echo ""
    
    $DOCKER_CMD run --rm \
        -v "$(pwd)/$REPORT_DIR:/zap/wrk/:rw" \
        -v "$(pwd)/tests/security/zap:/zap/config/:ro" \
        --network="host" \
        "$ZAP_IMAGE" \
        zap-baseline.py \
        -t "$TARGET_URL" \
        -c "/zap/config/zap-baseline.conf" \
        -r "baseline-report-${TIMESTAMP}.html" \
        -J "baseline-report-${TIMESTAMP}.json" \
        -w "baseline-report-${TIMESTAMP}.md" \
        -l WARN \
        -I
    
    echo ""
    echo "✅ Baseline scan completed!"
    echo "Reports saved to: $REPORT_DIR/"
    echo "  - HTML: baseline-report-${TIMESTAMP}.html"
    echo "  - JSON: baseline-report-${TIMESTAMP}.json"
    echo "  - Markdown: baseline-report-${TIMESTAMP}.md"
}

# Function to run full scan
run_full() {
    echo "🔍 Running Full Scan (Passive + Active)..."
    echo "Duration: ~30-60 minutes"
    echo "⚠️  This will actively test for vulnerabilities!"
    echo ""
    
    $DOCKER_CMD run --rm \
        -v "$(pwd)/$REPORT_DIR:/zap/wrk/:rw" \
        -v "$(pwd)/tests/security/zap:/zap/config/:ro" \
        --network="host" \
        "$ZAP_IMAGE" \
        zap-full-scan.py \
        -t "$TARGET_URL" \
        -c "/zap/config/zap-full.conf" \
        -r "full-report-${TIMESTAMP}.html" \
        -J "full-report-${TIMESTAMP}.json" \
        -w "full-report-${TIMESTAMP}.md" \
        -l WARN \
        -I \
        -j
    
    echo ""
    echo "✅ Full scan completed!"
    echo "Reports saved to: $REPORT_DIR/"
    echo "  - HTML: full-report-${TIMESTAMP}.html"
    echo "  - JSON: full-report-${TIMESTAMP}.json"
    echo "  - Markdown: full-report-${TIMESTAMP}.md"
}

# Function to run API scan
run_api() {
    echo "🔍 Running API Scan..."
    echo "Duration: ~15-30 minutes"
    echo ""
    
    # Check if OpenAPI spec exists
    if [ ! -f "tests/security/zap/openapi.json" ]; then
        echo "⚠️  No OpenAPI spec found at tests/security/zap/openapi.json"
        echo "   Please export your API spec first."
        echo ""
        echo "   To export Laravel API routes to OpenAPI:"
        echo "   php artisan route:list --path=api --json > tests/security/zap/openapi.json"
        exit 1
    fi
    
    $DOCKER_CMD run --rm \
        -v "$(pwd)/$REPORT_DIR:/zap/wrk/:rw" \
        -v "$(pwd)/tests/security/zap:/zap/config/:ro" \
        --network="host" \
        "$ZAP_IMAGE" \
        zap-api-scan.py \
        -t "$TARGET_URL" \
        -f openapi \
        -c "/zap/config/zap-full.conf" \
        -r "api-report-${TIMESTAMP}.html" \
        -J "api-report-${TIMESTAMP}.json" \
        -w "api-report-${TIMESTAMP}.md" \
        -l WARN \
        -I
    
    echo ""
    echo "✅ API scan completed!"
    echo "Reports saved to: $REPORT_DIR/"
    echo "  - HTML: api-report-${TIMESTAMP}.html"
    echo "  - JSON: api-report-${TIMESTAMP}.json"
    echo "  - Markdown: api-report-${TIMESTAMP}.md"
}

# Function to generate report only
generate_report() {
    echo "📊 Generating report from existing results..."
    
    # Find latest JSON file
    LATEST_JSON=$(ls -t "$REPORT_DIR"/*.json 2>/dev/null | head -1)
    
    if [ -z "$LATEST_JSON" ]; then
        echo "❌ No JSON report files found in $REPORT_DIR/"
        exit 1
    fi
    
    echo "Using: $LATEST_JSON"
    python3 tests/security/zap/generate_report.py "$LATEST_JSON"
}

# Execute scan
case $SCAN_TYPE in
    baseline)
        run_baseline
        ;;
    full)
        run_full
        ;;
    api)
        run_api
        ;;
    report-only)
        generate_report
        ;;
esac

echo ""
echo "=========================================="
echo "  Scan Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Open HTML report in browser"
echo "  2. Review findings and classify by OWASP Top 10"
echo "  3. Generate PNG chart: python3 tests/security/zap/generate_report.py"
echo ""
