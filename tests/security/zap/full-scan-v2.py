#!/usr/bin/env python3
"""
HRConnect Full ZAP Security Scan — v2 (reliable).
Uses ZAP daemon API on port 8080 with proper error handling.
Scans all 5 roles with full active scan.

Requirements:
  - Docker with zaproxy/zap-stable
  - Laravel server on localhost:8000
  - ZAP daemon running on localhost:8080

Usage:
  python3 tests/security/zap/full-scan-v2.py
"""

import json
import os
import re
import subprocess
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path

ZAP_BASE = "http://localhost:8080"
BASE_URL = "http://127.0.0.1:8000"
REPORTS_DIR = Path(__file__).parent / "reports"
REPORTS_DIR.mkdir(exist_ok=True)

ROLES = {
    "employee": {"email": "employee@hrconnect.test", "password": "password"},
    "manager":  {"email": "manager@hrconnect.test", "password": "Manager1234!!"},
    "finance":  {"email": "finance@hrconnect.test", "password": "Finance1234!!"},
    "hr":       {"email": "hr@hrconnect.test", "password": "password"},
    "admin":    {"email": "admin@hrconnect.local", "password": "ChangeMe!2026"},
}

# Seed URLs per role
ROUTES = {
    "employee": [
        "/login", "/forgot-password",
        "/home", "/my-schedule", "/scan", "/my-attendance",
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
        "/login", "/forgot-password",
        "/home", "/my-schedule", "/scan", "/my-attendance",
        "/approvals", "/approvals/history",
        "/my-leave", "/my-reimbursement", "/my-correction",
        "/my-overtime", "/my-wfh", "/my-kasbon", "/my-shift-swap",
        "/my-payslips", "/user/profile", "/hr-tasks", "/chat",
    ],
    "finance": [
        "/login", "/forgot-password",
        "/home", "/my-schedule", "/scan", "/my-attendance",
        "/admin/dashboard", "/admin/payrolls", "/admin/payrolls/settings",
        "/admin/reimbursements", "/admin/manage-kasbon",
        "/admin/reports", "/admin/reports/attendance",
        "/admin/reports/payroll", "/admin/reports/leave",
        "/admin/reports/export",
        "/my-payslips", "/user/profile", "/hr-tasks", "/chat",
    ],
    "hr": [
        "/login", "/forgot-password",
        "/home", "/my-schedule", "/scan", "/my-attendance",
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
        "/login", "/forgot-password",
        "/home", "/my-schedule", "/scan", "/my-attendance",
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


def zap_api(endpoint, params=None):
    """Call ZAP API and return parsed JSON."""
    url = f"{ZAP_BASE}{endpoint}"
    if params:
        url += "?" + urllib.parse.urlencode(params)
    try:
        req = urllib.request.Request(url)
        resp = urllib.request.urlopen(req, timeout=120)
        data = json.loads(resp.read().decode())
        return data
    except Exception as e:
        return {"error": str(e)}


def zap_poll(endpoint, params=None, key="status", target="100", interval=5, max_wait=3600):
    """Poll ZAP API until target value is reached."""
    start = time.time()
    while time.time() - start < max_wait:
        data = zap_api(endpoint, params)
        val = data.get(key, data.get("does_not_exist", None))
        if str(val) == str(target):
            return True
        if val == "does_not_exist":
            # Scan may have already finished
            return True
        elapsed = int(time.time() - start)
        print(f"    [{elapsed}s] {key}={val}", end="\r", flush=True)
        time.sleep(interval)
    print(f"    ⚠️ Timeout after {max_wait}s")
    return False


def login_laravel(email, password):
    """Login to Laravel and return cookie string."""
    import http.cookiejar
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

    # GET login page
    resp = opener.open(f"{BASE_URL}/login", timeout=30)
    html = resp.read().decode()

    # Extract CSRF
    m = re.search(r'name="_token"[^>]*value="([^"]+)"', html)
    if not m:
        m = re.search(r'content="([^"]+)"[^>]*name="csrf-token"', html)
    csrf = m.group(1) if m else ""

    # POST login
    data = urllib.parse.urlencode({
        "email": email,
        "password": password,
        "_token": csrf,
    }).encode()
    req = urllib.request.Request(f"{BASE_URL}/login", data=data)
    req.add_header("Content-Type", "application/x-www-form-urlencoded")
    try:
        opener.open(req, timeout=30)
    except urllib.error.HTTPError:
        pass

    # Get cookies
    cookies = "; ".join(f"{c.name}={c.value}" for c in cj)

    # Verify
    req2 = urllib.request.Request(f"{BASE_URL}/home")
    req2.add_header("Cookie", cookies)
    try:
        resp2 = opener.open(req2, timeout=30)
        if resp2.status == 200:
            return cookies
    except Exception:
        pass
    return None


def setup_zap_context(role_name, cookies):
    """Set up ZAP context with session cookies."""
    ctx_name = f"hrconnect-{role_name}"

    # Create context
    zap_api("/JSON/context/action/newContext/", {"contextName": ctx_name})

    # Include all URLs in context
    for path in ROUTES[role_name]:
        url_regex = re.escape(f"{BASE_URL}{path}")
        zap_api("/JSON/context/action/includeInContext/", {
            "contextName": ctx_name,
            "regex": url_regex,
        })

    # Also include base URL pattern for discovered links
    zap_api("/JSON/context/action/includeInContext/", {
        "contextName": ctx_name,
        "regex": re.escape(BASE_URL) + ".*",
    })

    # Set cookies via httpSessions
    if cookies:
        for cookie_part in cookies.split("; "):
            if "=" in cookie_part:
                k, v = cookie_part.split("=", 1)
                zap_api("/JSON/httpSessions/action/addSessionToken/", {
                    "siteAddress": BASE_URL,
                    "tokenName": k,
                    "tokenValue": v,
                })

    return ctx_name


def run_scan_for_role(role_name, creds):
    """Run full scan (spider + active) for one role."""
    print(f"\n{'='*60}")
    print(f"  ROLE: {role_name.upper()}")
    print(f"  Email: {creds['email']}")
    print(f"{'='*60}")

    # Login
    print(f"\n  [1/5] Logging in...")
    cookies = login_laravel(creds["email"], creds["password"])
    if not cookies:
        print(f"  ❌ Login failed!")
        return None
    print(f"  ✅ Logged in")

    # Setup context
    print(f"\n  [2/5] Setting up ZAP context...")
    ctx_name = setup_zap_context(role_name, cookies)
    print(f"  Context: {ctx_name}")

    # Spider
    print(f"\n  [3/5] Spidering...")
    resp = zap_api("/JSON/spider/action/scan/", {
        "url": BASE_URL,
        "recurse": "true",
        "maxDepth": "5",
        "contextName": ctx_name,
    })
    spider_id = resp.get("scan", "0")
    print(f"  Spider ID: {spider_id}")

    spider_done = zap_poll(
        "/JSON/spider/view/status/",
        {"scanId": spider_id},
        key="status",
        target="100",
        interval=3,
        max_wait=600,
    )
    print(f"\n  Spider: {'✅ Complete' if spider_done else '⚠️ Timeout'}")

    # Get spider results
    resp = zap_api("/JSON/spider/view/urls/", {"contextId": ""})
    urls = resp.get("urls", [])
    print(f"  URLs discovered: {len(urls)}")

    # Wait for passive scan to finish
    print(f"\n  [4/5] Passive scan...")
    zap_poll(
        "/JSON/pscan/view/recordsToScan/",
        {},
        key="recordsToScan",
        target="0",
        interval=2,
        max_wait=300,
    )
    print(f"  Passive scan: ✅ Complete")

    # Active Scan
    print(f"\n  [5/5] Active scan (HIGH strength, LOW threshold)...")
    resp = zap_api("/JSON/ascan/action/scan/", {
        "url": BASE_URL,
        "recurse": "true",
        "inScopeOnly": "true",
        "contextId": "",
    })
    active_id = resp.get("scan", "0")
    print(f"  Active scan ID: {active_id}")

    active_done = zap_poll(
        "/JSON/ascan/view/status/",
        {"scanId": active_id},
        key="status",
        target="100",
        interval=10,
        max_wait=3600,
    )
    print(f"\n  Active scan: {'✅ Complete' if active_done else '⚠️ Timeout'}")

    # Collect alerts
    print(f"\n  Collecting alerts...")
    alerts_data = zap_api("/JSON/core/view/alerts/", {
        "baseurl": BASE_URL,
        "start": "0",
        "count": "1000",
    })
    alerts = alerts_data.get("alerts", [])

    risk_counts = {"High": 0, "Medium": 0, "Low": 0, "Informational": 0}
    for alert in alerts:
        risk_raw = alert.get("risk", "0")
        # ZAP API may return risk as int (0-3) or string ("Low", "Medium", etc.)
        risk_map = {"High": 3, "Medium": 2, "Low": 1, "Informational": 0, "Info": 0}
        if isinstance(risk_raw, int):
            risk = risk_raw
        elif isinstance(risk_raw, str) and risk_raw.isdigit():
            risk = int(risk_raw)
        else:
            risk = risk_map.get(str(risk_raw).capitalize(), 0)
        if risk == 3:
            risk_counts["High"] += 1
        elif risk == 2:
            risk_counts["Medium"] += 1
        elif risk == 1:
            risk_counts["Low"] += 1
        else:
            risk_counts["Informational"] += 1

    print(f"\n  === RESULTS: {role_name.upper()} ===")
    print(f"  Total alerts: {len(alerts)}")
    for level in ["High", "Medium", "Low", "Informational"]:
        if risk_counts[level] > 0:
            print(f"    {level}: {risk_counts[level]}")

    # Generate HTML report
    report_name = f"hrconnect-full-{role_name}"
    zap_api("/JSON/reports/action/generate/", {
        "title": f"HRConnect Security Scan — {role_name.upper()}",
        "template": "traditional-html",
        "reportDir": "/zap/wrk/",
        "reportFileName": f"{report_name}.html",
    })

    # Generate JSON report (save raw alerts)
    report_json = REPORTS_DIR / f"{report_name}.json"
    with open(report_json, "w") as f:
        json.dump({
            "role": role_name,
            "scan_date": time.strftime("%Y-%m-%d %H:%M:%S"),
            "total_alerts": len(alerts),
            "risk_summary": risk_counts,
            "urls_discovered": len(urls),
            "alerts": alerts,
        }, f, indent=2)

    # Generate XML report
    zap_api("/JSON/reports/action/generate/", {
        "title": f"HRConnect Security Scan — {role_name.upper()}",
        "template": "traditional-xml",
        "reportDir": "/zap/wrk/",
        "reportFileName": f"{report_name}.xml",
    })

    html_path = REPORTS_DIR / f"{report_name}.html"
    xml_path = REPORTS_DIR / f"{report_name}.xml"

    print(f"  Reports:")
    if html_path.exists():
        print(f"    ✅ HTML: {html_path.name} ({html_path.stat().st_size:,} bytes)")
    else:
        print(f"    ❌ HTML: not found")
    print(f"    ✅ JSON: {report_json.name} ({report_json.stat().st_size:,} bytes)")
    if xml_path.exists():
        print(f"    ✅ XML: {xml_path.name} ({xml_path.stat().st_size:,} bytes)")
    else:
        print(f"    ❌ XML: not found")

    return {
        "role": role_name,
        "total_alerts": len(alerts),
        "risk_counts": risk_counts,
        "urls_discovered": len(urls),
        "alerts": alerts,
    }


def main():
    print("=" * 60)
    print("  HRConnect Full ZAP Security Scan — All 5 Roles")
    print(f"  Target: {BASE_URL}")
    print(f"  ZAP:    {ZAP_BASE}")
    print(f"  Roles:  {', '.join(ROLES.keys())}")
    print("=" * 60)

    # Check server
    try:
        req = urllib.request.Request(f"{BASE_URL}/")
        resp = urllib.request.urlopen(req, timeout=10)
        print(f"\n  Laravel: ✅ HTTP {resp.status}")
    except Exception as e:
        print(f"\n  ❌ Laravel not reachable: {e}")
        sys.exit(1)

    # Check ZAP
    try:
        data = zap_api("/JSON/core/view/version/")
        print(f"  ZAP:    ✅ v{data.get('version', '?')}")
    except Exception:
        print(f"  ❌ ZAP not reachable at {ZAP_BASE}")
        sys.exit(1)

    # Run scans
    all_results = []
    for role_name, creds in ROLES.items():
        result = run_scan_for_role(role_name, creds)
        if result:
            all_results.append(result)

    # Combined summary
    summary = {
        "scan_date": time.strftime("%Y-%m-%d %H:%M:%S"),
        "target": BASE_URL,
        "method": "ZAP Full Active Scan (HIGH strength, LOW threshold)",
        "roles_scanned": [r["role"] for r in all_results],
        "results": [],
        "combined_risk_summary": {"High": 0, "Medium": 0, "Low": 0, "Informational": 0},
    }

    for r in all_results:
        summary["results"].append({
            "role": r["role"],
            "total_alerts": r["total_alerts"],
            "risk_counts": r["risk_counts"],
            "urls_discovered": r["urls_discovered"],
        })
        for risk, count in r["risk_counts"].items():
            summary["combined_risk_summary"][risk] += count

    summary_path = REPORTS_DIR / "scan-summary.json"
    with open(summary_path, "w") as f:
        json.dump(summary, f, indent=2)

    # Print final
    print(f"\n{'='*60}")
    print("  FINAL SUMMARY")
    print(f"{'='*60}")
    print(f"  Scan Date: {summary['scan_date']}")
    print(f"  Method: {summary['method']}")
    print(f"  Roles Scanned: {len(all_results)}")
    print()
    for r in all_results:
        rc = r["risk_counts"]
        print(f"  {r['role']:12s} | {r['urls_discovered']:3d} URLs | "
              f"H:{rc['High']:2d} M:{rc['Medium']:2d} "
              f"L:{rc['Low']:2d} I:{rc['Informational']:2d}")
    print()
    print("  Combined:")
    for risk in ["High", "Medium", "Low", "Informational"]:
        c = summary["combined_risk_summary"][risk]
        if c > 0:
            print(f"    {risk:15s}: {c}")
    print(f"\n  Reports: {REPORTS_DIR}/")


if __name__ == "__main__":
    main()
