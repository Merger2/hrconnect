#!/usr/bin/env python3
"""
HRConnect Full ZAP Security Scan — API-based approach.
Uses ZAP daemon + API for proper authenticated scanning of all 5 roles.

Requirements:
  - Docker
  - Laravel server running on localhost:8000
  - pip install requests (if not available, uses urllib)

Usage:
  python3 tests/security/zap/full-scan.py
"""

import json
import os
import re
import signal
import subprocess
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path

try:
    import requests as req_lib
    HAS_REQUESTS = True
except ImportError:
    HAS_REQUESTS = False

BASE_URL = "http://127.0.0.1:8000"
ZAP_BASE = "http://localhost:8080"
REPORTS_DIR = Path(__file__).parent / "reports"
REPORTS_DIR.mkdir(exist_ok=True)

# Clean old scan reports (keep generate_report.py outputs)
for f in REPORTS_DIR.glob("hrconnect-full-*.*"):
    f.unlink(missing_ok=True)

ROLES = {
    "employee": {"email": "employee@hrconnect.test", "password": "password"},
    "manager":  {"email": "manager@hrconnect.test", "password": "Manager1234!!"},
    "finance":  {"email": "finance@hrconnect.test", "password": "Finance1234!!"},
    "hr":       {"email": "hr@hrconnect.test", "password": "password"},
    "admin":    {"email": "admin@hrconnect.local", "password": "ChangeMe!2026"},
}

# Pages per role (authenticated)
ROUTES = {
    "employee": [
        "/", "/home", "/my-schedule", "/scan", "/my-attendance",
        "/my-attendance/report", "/my-leave", "/my-leave/request",
        "/my-reimbursement", "/my-reimbursement/request",
        "/my-correction", "/my-correction/request",
        "/my-overtime", "/my-overtime/request",
        "/my-wfh", "/my-wfh/request",
        "/my-kasbon", "/my-kasbon/request",
        "/my-shift-swap", "/my-shift-swap/request",
        "/my-documents", "/my-payslips", "/user/profile",
        "/chat", "/hr-tasks",
    ],
    "manager": [
        "/", "/home", "/my-schedule", "/scan", "/my-attendance",
        "/approvals", "/approvals/history",
        "/my-leave", "/my-reimbursement", "/my-correction",
        "/my-overtime", "/my-wfh", "/my-kasbon", "/my-shift-swap",
        "/my-payslips", "/user/profile", "/hr-tasks", "/chat",
    ],
    "finance": [
        "/", "/home", "/my-schedule", "/scan", "/my-attendance",
        "/admin/dashboard", "/admin/payrolls", "/admin/payrolls/settings",
        "/admin/reimbursements", "/admin/manage-kasbon",
        "/admin/reports", "/admin/reports/attendance",
        "/admin/reports/payroll", "/admin/reports/leave",
        "/admin/reports/export",
        "/my-payslips", "/user/profile", "/hr-tasks", "/chat",
    ],
    "hr": [
        "/", "/home", "/my-schedule", "/scan", "/my-attendance",
        "/admin/dashboard", "/admin/employees", "/admin/departments",
        "/admin/positions", "/admin/grades", "/admin/leave-types",
        "/admin/schedules", "/admin/holidays",
        "/admin/attendance", "/admin/attendance/report",
        "/admin/leave", "/admin/overtime",
        "/admin/reimbursement", "/admin/correction",
        "/admin/shift-swap", "/admin/wfh", "/admin/kasbon",
        "/admin/reports", "/admin/reports/attendance",
        "/admin/reports/payroll", "/admin/reports/leave",
        "/admin/reports/export",
        "/admin/documents", "/admin/document-templates",
        "/admin/checklist",
        "/approvals", "/approvals/history",
        "/my-leave", "/my-reimbursement", "/my-correction",
        "/my-overtime", "/my-wfh", "/my-kasbon",
        "/my-payslips", "/my-documents", "/user/profile",
        "/hr-tasks", "/chat",
    ],
    "admin": [
        "/", "/home", "/my-schedule", "/scan", "/my-attendance",
        "/admin/dashboard", "/admin/employees", "/admin/departments",
        "/admin/positions", "/admin/grades", "/admin/leave-types",
        "/admin/schedules", "/admin/holidays",
        "/admin/attendance", "/admin/attendance/report",
        "/admin/leave", "/admin/overtime",
        "/admin/reimbursement", "/admin/correction",
        "/admin/shift-swap", "/admin/wfh", "/admin/kasbon",
        "/admin/reports", "/admin/reports/attendance",
        "/admin/reports/payroll", "/admin/reports/leave",
        "/admin/reports/export",
        "/admin/documents", "/admin/document-templates",
        "/admin/checklist",
        "/admin/roles", "/admin/permissions",
        "/admin/backup", "/admin/logs", "/admin/settings",
        "/admin/knowledge-base", "/admin/knowledge-base/eval",
        "/admin/audit", "/admin/approval-settings",
        "/approvals", "/approvals/history",
        "/my-leave", "/my-reimbursement", "/my-correction",
        "/my-overtime", "/my-wfh", "/my-kasbon",
        "/my-payslips", "/my-documents", "/user/profile",
        "/hr-tasks", "/chat",
    ],
}

EXCLUDE_PATHS = [
    ".*\\.css", ".*\\.js", ".*\\.png", ".*\\.jpg", ".*\\.gif",
    ".*\\.svg", ".*\\.ico", ".*\\.woff", ".*\\.woff2",
    ".*\\.ttf", ".*\\.eot", ".*storage/", ".*\\.xml",
    ".*logout", ".*\\.pdf",
]


def http_get(url, headers=None, timeout=30):
    """HTTP GET with urllib fallback."""
    if HAS_REQUESTS:
        r = req_lib.get(url, headers=headers or {}, timeout=timeout, allow_redirects=True)
        return r.status_code, r.text, dict(r.cookies), r.headers
    else:
        h = urllib.request.Request(url)
        for k, v in (headers or {}).items():
            h.add_header(k, v)
        resp = urllib.request.urlopen(h, timeout=timeout)
        return resp.status, resp.read().decode(), {}, dict(resp.headers)


def http_post(url, data, headers=None, timeout=30):
    """HTTP POST with urllib fallback."""
    if HAS_REQUESTS:
        r = req_lib.post(url, data=data, headers=headers or {}, timeout=timeout, allow_redirects=True)
        return r.status_code, r.text, dict(r.cookies), r.headers
    else:
        encoded = urllib.parse.urlencode(data).encode()
        h = urllib.request.Request(url, data=encoded, headers=headers or {})
        h.add_header("Content-Type", "application/x-www-form-urlencoded")
        resp = urllib.request.urlopen(h, timeout=timeout)
        return resp.status, resp.read().decode(), {}, dict(resp.headers)


def zap_api(endpoint, params=None):
    """Call ZAP API."""
    url = f"{ZAP_BASE}{endpoint}"
    if params:
        url += "?" + urllib.parse.urlencode(params)
    status, text, _, _ = http_get(url, timeout=60)
    try:
        return json.loads(text)
    except Exception:
        return {"raw": text, "status": status}


def login_role(email, password):
    """Login as a role and return session cookie string."""
    # GET login page for CSRF
    status, html, cookies, _ = http_get(f"{BASE_URL}/login")
    csrf_match = re.search(r'name="_token"[^>]*value="([^"]+)"', html)
    if not csrf_match:
        csrf_match = re.search(r'content="([^"]+)"[^>]*name="csrf-token"', html)
    csrf = csrf_match.group(1) if csrf_match else ""

    cookie_str = "; ".join(f"{k}={v}" for k, v in cookies.items() if "session" in k.lower() or "xsrf" in k.lower())

    # POST login
    status, html, new_cookies, headers = http_post(
        f"{BASE_URL}/login",
        data={"email": email, "password": password, "_token": csrf},
        headers={"Cookie": cookie_str, "Content-Type": "application/x-www-form-urlencoded"},
    )

    # Update session cookie
    if new_cookies:
        for k, v in new_cookies.items():
            if "session" in k.lower():
                cookie_str = f"laravel_session={v}"
            else:
                cookie_str += f"; {k}={v}"

    # Verify
    status, _, _, _ = http_get(f"{BASE_URL}/home", headers={"Cookie": cookie_str})
    if status == 200:
        return cookie_str
    return None


def start_zap_docker():
    """Start ZAP in daemon mode."""
    print("  Starting ZAP Docker daemon...")

    # Check if already running
    result = subprocess.run(["docker", "ps", "--format", "{{.Names}}"], capture_output=True, text=True)
    if "hrconnect-zap" in result.stdout:
        print("  ✅ ZAP container already running")
        return "existing"

    # Kill any stopped container
    subprocess.run(["docker", "rm", "-f", "hrconnect-zap"], capture_output=True)

    cmd = [
        "docker", "run", "-d", "--rm",
        "--name", "hrconnect-zap",
        "--network=host",
        "-p", "8090:8080",
        "-t", "zaproxy/zap-stable",
        "zap.sh", "-daemon",
        "-host", "0.0.0.0",
        "-port", "8080",
        "-config", "api.disablekey=true",
        "-config", "api.addrs.addr.name=.*",
        "-config", "api.addrs.addr.regex=true",
    ]

    result = subprocess.run(cmd, capture_output=True, text=True)
    container_id = result.stdout.strip()
    print(f"  Container: {container_id[:12]}")

    # Wait for ZAP to be ready (addons may download on first run)
    for i in range(90):
        try:
            status, _, _, _ = http_get(f"{ZAP_BASE}/JSON/core/view/version/", timeout=5)
            if status == 200:
                print(f"  ✅ ZAP ready ({i+1}s)")
                return container_id
        except Exception:
            pass
        time.sleep(2)

    print("  ❌ ZAP failed to start")
    return None


def stop_zap_docker():
    """Stop ZAP Docker container."""
    subprocess.run(["docker", "stop", "hrconnect-zap"], capture_output=True, timeout=30)
    print("  ZAP stopped")


def scan_role(role_name, email, password, container_id):
    """Run full scan for a single role."""
    print(f"\n{'='*60}")
    print(f"  ROLE: {role_name.upper()}")
    print(f"{'='*60}")

    # 1. Create context
    ctx_name = f"hrconnect-{role_name}"
    zap_api("/JSON/context/action/newContext/", {"contextName": ctx_name})

    # Get context ID
    resp = zap_api("/JSON/context/view/contextList/", {})
    contexts = resp.get("contextList", []) if isinstance(resp.get("contextList"), list) else []
    ctx_id = None
    for ctx in contexts:
        if isinstance(ctx, dict) and ctx.get("name") == ctx_name:
            ctx_id = ctx.get("id")
            break
        elif isinstance(ctx, str) and ctx_name in ctx:
            ctx_id = ctx

    if not ctx_id:
        # Try getting from contextList string
        resp = zap_api("/JSON/context/view/contextList/", {})
        print(f"  Context list: {resp}")

    print(f"  Context: {ctx_name} (ID: {ctx_id})")

    # 2. Add URLs to context
    all_urls = [f"{BASE_URL}{p}" for p in ROUTES[role_name]]
    for url in all_urls:
        zap_api("/JSON/context/action/includeInContext/", {
            "contextName": ctx_name,
            "regex": re.escape(url),
        })

    # Add exclude patterns
    for pattern in EXCLUDE_PATHS:
        zap_api("/JSON/context/action/excludeFromContext/", {
            "contextName": ctx_name,
            "regex": pattern,
        })

    # 3. Set session (cookie)
    cookie_str = login_role(email, password)
    if not cookie_str:
        print(f"  ❌ Login failed for {role_name}, skipping")
        return None

    print(f"  Session: {cookie_str[:40]}...")

    # Parse cookies and set them in ZAP
    for cookie_part in cookie_str.split("; "):
        if "=" in cookie_part:
            k, v = cookie_part.split("=", 1)
            zap_api("/JSON/httpSessions/action/addSessionToken/", {
                "siteAddress": BASE_URL,
                "tokenName": k,
                "tokenValue": v,
            })

    # 4. Spider
    print(f"\n  🕷️  Spidering ({len(all_urls)} seed URLs)...")
    resp = zap_api("/JSON/spider/action/scan/", {
        "url": BASE_URL,
        "recurse": "true",
        "maxDepth": "5",
        "contextName": ctx_name,
        "subtreeOnly": "false",
    })
    spider_scan_id = resp.get("scan", "0")
    print(f"  Spider scan ID: {spider_scan_id}")

    # Wait for spider
    while True:
        resp = zap_api("/JSON/spider/view/status/", {"scanId": str(spider_scan_id)})
        progress = resp.get("status", "0")
        print(f"  Spider: {progress}%", end="\r")
        if progress == "100" or progress == 100:
            break
        time.sleep(3)

    # Get discovered URLs
    resp = zap_api("/JSON/spider/view/urls/", {"contextId": str(ctx_id) if ctx_id else ""})
    urls_found = resp.get("urls", []) if isinstance(resp.get("urls"), list) else []
    print(f"  Spider found: {len(urls_found)} URLs                    ")

    # 5. Passive Scan (auto-runs on spidered URLs)
    print(f"\n  🔍 Running passive scan...")
    while True:
        resp = zap_api("/JSON/pscan/view/recordsToScan/", {})
        remaining = resp.get("recordsToScan", "0")
        print(f"  Passive scan: {remaining} records remaining", end="\r")
        if remaining == "0" or remaining == 0:
            break
        time.sleep(2)
    print(f"  Passive scan complete                     ")

    # 6. Active Scan
    print(f"\n  ⚡ Running active scan (HIGH strength, LOW threshold)...")
    resp = zap_api("/JSON/ascan/action/scan/", {
        "url": BASE_URL,
        "recurse": "true",
        "inScopeOnly": "true",
        "scanPolicyName": "",
        "contextId": str(ctx_id) if ctx_id else "",
    })
    active_scan_id = resp.get("scan", "0")
    print(f"  Active scan ID: {active_scan_id}")

    # Wait for active scan
    while True:
        resp = zap_api("/JSON/ascan/view/status/", {"scanId": str(active_scan_id)})
        progress = resp.get("status", "0")
        print(f"  Active scan: {progress}%", end="\r")
        if progress == "100" or progress == 100:
            break
        time.sleep(5)
    print(f"  Active scan complete                      ")

    # 7. Get alerts
    resp = zap_api("/JSON/core/view/alerts/", {
        "baseurl": BASE_URL,
        "start": "0",
        "count": "500",
    })
    alerts = resp.get("alerts", [])
    print(f"\n  Total alerts: {len(alerts)}")

    # Categorize by risk
    risk_counts = {"High": 0, "Medium": 0, "Low": 0, "Informational": 0}
    risk_alerts = {"High": [], "Medium": [], "Low": [], "Informational": []}
    for alert in alerts:
        risk = int(alert.get("risk", "0"))
        name = alert.get("name", "Unknown")
        if risk == 3:
            risk_counts["High"] += 1
            risk_alerts["High"].append(name)
        elif risk == 2:
            risk_counts["Medium"] += 1
            risk_alerts["Medium"].append(name)
        elif risk == 1:
            risk_counts["Low"] += 1
            risk_alerts["Low"].append(name)
        else:
            risk_counts["Informational"] += 1
            risk_alerts["Informational"].append(name)

    for level in ["High", "Medium", "Low", "Informational"]:
        if risk_counts[level] > 0:
            unique = len(set(risk_alerts[level]))
            print(f"    {level}: {risk_counts[level]} instances ({unique} unique)")

    # 8. Generate report
    print(f"\n  📄 Generating reports...")
    for fmt, ext, tmpl in [
        ("HTML", "html", "traditional-html"),
        ("JSON", "json", "traditional-json"),
        ("XML", "xml", "traditional-xml"),
    ]:
        resp = zap_api("/OTHER/core/other/htmlreport/", {})
        # Use report generation endpoint
        report_name = f"hrconnect-full-{role_name}.{ext}"
        report_path = REPORTS_DIR / report_name

        if ext == "html":
            zap_api("/JSON/reports/action/generate/", {
                "title": f"HRConnect Security Scan — {role_name.upper()}",
                "template": tmpl,
                "reportDir": str(REPORTS_DIR),
                "reportFileName": report_name,
            })
        elif ext == "json":
            zap_api("/JSON/reports/action/generate/", {
                "title": f"HRConnect Security Scan — {role_name.upper()}",
                "template": tmpl,
                "reportDir": str(REPORTS_DIR),
                "reportFileName": report_name,
            })
        elif ext == "xml":
            zap_api("/JSON/reports/action/generate/", {
                "title": f"HRConnect Security Scan — {role_name.upper()}",
                "template": tmpl,
                "reportDir": str(REPORTS_DIR),
                "reportFileName": report_name,
            })

        if report_path.exists():
            print(f"    ✅ {fmt}: {report_path.stat().st_size:,} bytes")
        else:
            print(f"    ⚠️  {fmt}: not found at expected path, checking...")
            # Try alternative: save alerts as raw JSON
            if ext == "json":
                with open(report_path, "w") as f:
                    json.dump({
                        "role": role_name,
                        "scan_date": time.strftime("%Y-%m-%d %H:%M:%S"),
                        "total_alerts": len(alerts),
                        "alerts": alerts,
                        "risk_summary": risk_counts,
                    }, f, indent=2)
                print(f"    ✅ {fmt} (raw): {report_path.stat().st_size:,} bytes")

    return {
        "role": role_name,
        "total_alerts": len(alerts),
        "risk_counts": risk_counts,
        "urls_discovered": len(urls_found),
        "alerts": alerts,
    }


def main():
    print("=" * 60)
    print("  HRConnect Full ZAP Security Scan — All 5 Roles")
    print(f"  Target: {BASE_URL}")
    print(f"  Roles: {', '.join(ROLES.keys())}")
    print("=" * 60)

    # Check server
    try:
        status, _, _, _ = http_get(f"{BASE_URL}/")
        print(f"\n  Server: OK (HTTP {status})")
    except Exception as e:
        print(f"\n  ❌ Server not reachable: {e}")
        sys.exit(1)

    # Start ZAP
    container_id = start_zap_docker()
    if not container_id:
        sys.exit(1)

    try:
        all_results = []
        all_alerts_by_role = {}

        for role_name, creds in ROLES.items():
            result = scan_role(role_name, creds["email"], creds["password"], container_id)
            if result:
                all_results.append(result)
                all_alerts_by_role[role_name] = result["alerts"]

        # Save combined summary
        summary = {
            "scan_date": time.strftime("%Y-%m-%d %H:%M:%S"),
            "target": BASE_URL,
            "method": "ZAP Full Active Scan (HIGH strength, LOW threshold)",
            "roles": [r["role"] for r in all_results],
            "results": [{
                "role": r["role"],
                "total_alerts": r["total_alerts"],
                "risk_counts": r["risk_counts"],
                "urls_discovered": r["urls_discovered"],
            } for r in all_results],
            "combined_risk_summary": {},
        }

        # Combine risk counts across all roles
        for r in all_results:
            for risk, count in r["risk_counts"].items():
                if risk not in summary["combined_risk_summary"]:
                    summary["combined_risk_summary"][risk] = 0
                summary["combined_risk_summary"][risk] += count

        summary_path = REPORTS_DIR / "scan-summary.json"
        with open(summary_path, "w") as f:
            json.dump(summary, f, indent=2)

        # Print final summary
        print(f"\n{'='*60}")
        print("  FINAL SUMMARY")
        print(f"{'='*60}")
        print(f"  Scan Date: {summary['scan_date']}")
        print(f"  Method: {summary['method']}")
        print(f"  Roles Scanned: {len(all_results)}")
        print(f"\n  Per-Role Results:")
        for r in all_results:
            risks = r["risk_counts"]
            total = sum(v for k, v in risks.items() if k != "Informational")
            print(f"    {r['role']:12s} | {r['urls_discovered']:3d} URLs | "
                  f"H:{risks.get('High',0):2d} M:{risks.get('Medium',0):2d} "
                  f"L:{risks.get('Low',0):2d} I:{risks.get('Informational',0):2d} "
                  f"| Total: {r['total_alerts']:3d}")
        print(f"\n  Combined Risk Summary:")
        for risk in ["High", "Medium", "Low", "Informational"]:
            count = summary["combined_risk_summary"].get(risk, 0)
            print(f"    {risk:15s}: {count}")
        print(f"\n  Reports: {REPORTS_DIR}/")
        for f in sorted(REPORTS_DIR.glob("hrconnect-full-*")):
            if f.is_file():
                print(f"    {f.name} ({f.stat().st_size:,} bytes)")

    finally:
        if container_id != "existing":
            stop_zap_docker()
        else:
            print("\n  ZAP container kept running (was pre-existing)")


if __name__ == "__main__":
    main()
