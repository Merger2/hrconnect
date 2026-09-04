#!/usr/bin/env python3
"""Full ZAP scan for all 5 roles — post-fix verification."""
import json, re, time, urllib.parse, urllib.request, http.cookiejar
from pathlib import Path

ZAP_BASE = "http://localhost:8090"
BASE_URL = "http://127.0.0.1:8000"
REPORTS_DIR = Path(__file__).parent / "reports"

ROLES = {
    "employee": {"email": "employee@hrconnect.test", "password": "password"},
    "manager":  {"email": "manager@hrconnect.test", "password": "Manager1234!!"},
    "finance":  {"email": "finance@hrconnect.test", "password": "Finance1234!!"},
    "hr":       {"email": "hr@hrconnect.test", "password": "password"},
    "admin":    {"email": "admin@hrconnect.local", "password": "ChangeMe!2026"},
}

ROUTES = {
    "employee": [
        "/login", "/forgot-password", "/home", "/my-schedule", "/scan", "/my-attendance",
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
        "/login", "/forgot-password", "/home", "/my-schedule", "/scan", "/my-attendance",
        "/approvals", "/approvals/history",
        "/my-leave", "/my-reimbursement", "/my-correction",
        "/my-overtime", "/my-wfh", "/my-kasbon", "/my-shift-swap",
        "/my-payslips", "/user/profile", "/hr-tasks", "/chat",
    ],
    "finance": [
        "/login", "/forgot-password", "/home", "/my-schedule", "/scan", "/my-attendance",
        "/admin/dashboard", "/admin/payrolls", "/admin/payrolls/settings",
        "/admin/reimbursements", "/admin/manage-kasbon",
        "/admin/reports", "/admin/reports/attendance",
        "/admin/reports/payroll", "/admin/reports/leave",
        "/admin/reports/export",
        "/my-payslips", "/user/profile", "/hr-tasks", "/chat",
    ],
    "hr": [
        "/login", "/forgot-password", "/home", "/my-schedule", "/scan", "/my-attendance",
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
        "/login", "/forgot-password", "/home", "/my-schedule", "/scan", "/my-attendance",
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


def zap_api(ep, params=None):
    url = f"{ZAP_BASE}{ep}"
    if params:
        url += "?" + urllib.parse.urlencode(params)
    try:
        resp = urllib.request.urlopen(urllib.request.Request(url), timeout=120)
        return json.loads(resp.read().decode())
    except Exception as e:
        return {"error": str(e)}


def login(email, password):
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
    resp = opener.open(f"{BASE_URL}/login", timeout=30)
    html = resp.read().decode()
    m = re.search(r'name="_token"[^>]*value="([^"]+)"', html)
    csrf = m.group(1) if m else ""
    data = urllib.parse.urlencode({"email": email, "password": password, "_token": csrf}).encode()
    req = urllib.request.Request(f"{BASE_URL}/login", data=data)
    req.add_header("Content-Type", "application/x-www-form-urlencoded")
    try:
        opener.open(req, timeout=30)
    except Exception:
        pass
    return "; ".join(f"{c.name}={c.value}" for c in cj)


def scan_role(role_name, creds):
    print(f"\n{'='*60}")
    print(f"  ROLE: {role_name.upper()}")
    print(f"{'='*60}")

    cookies = login(creds["email"], creds["password"])
    if not cookies:
        print("  Login failed!")
        return None

    ctx_name = f"hrconnect-{role_name}"
    zap_api("/JSON/context/action/newContext/", {"contextName": ctx_name})
    for path in ROUTES[role_name]:
        zap_api("/JSON/context/action/includeInContext/", {
            "contextName": ctx_name, "regex": re.escape(BASE_URL + path),
        })
    zap_api("/JSON/context/action/includeInContext/", {
        "contextName": ctx_name, "regex": re.escape(BASE_URL) + ".*",
    })
    for cp in cookies.split("; "):
        if "=" in cp:
            k, v = cp.split("=", 1)
            zap_api("/JSON/httpSessions/action/addSessionToken/", {
                "siteAddress": BASE_URL, "tokenName": k, "tokenValue": v,
            })

    # Spider
    print("  Spidering...")
    resp = zap_api("/JSON/spider/action/scan/", {
        "url": BASE_URL, "recurse": "true", "maxDepth": "5", "contextName": ctx_name,
    })
    sid = resp.get("scan", "0")
    while True:
        r = zap_api("/JSON/spider/view/status/", {"scanId": sid})
        st = r.get("status", "0")
        print(f"    Spider: {st}%", end="\r")
        if st == "100":
            break
        time.sleep(3)
    print("    Spider complete    ")

    while True:
        r = zap_api("/JSON/pscan/view/recordsToScan/", {})
        rem = r.get("recordsToScan", "0")
        if rem == "0" or rem == 0:
            break
        time.sleep(2)
    print("  Passive scan complete")

    # Active scan
    print("  Active scan...")
    resp = zap_api("/JSON/ascan/action/scan/", {
        "url": BASE_URL, "recurse": "true", "inScopeOnly": "true", "contextId": "",
    })
    aid = resp.get("scan", "0")
    while True:
        r = zap_api("/JSON/ascan/view/status/", {"scanId": aid})
        st = r.get("status", "0")
        print(f"    Active: {st}%", end="\r")
        if st == "100":
            break
        time.sleep(10)
    print("    Active scan complete")

    # Collect alerts
    alerts_data = zap_api("/JSON/core/view/alerts/", {
        "baseurl": BASE_URL, "start": "0", "count": "1000",
    })
    alerts = alerts_data.get("alerts", [])

    risk_names = {3: "High", 2: "Medium", 1: "Low", 0: "Informational"}
    risk_counts = {"High": 0, "Medium": 0, "Low": 0, "Informational": 0}
    for a in alerts:
        r = a.get("risk", 0)
        if isinstance(r, int):
            rn = risk_names.get(r, "Informational")
        else:
            rn = str(r)
        risk_counts[rn] = risk_counts.get(rn, 0) + 1

    total = len(alerts)
    print(f"\n  {role_name.upper()}: {total} alerts | H:{risk_counts['High']} M:{risk_counts['Medium']} L:{risk_counts['Low']} I:{risk_counts['Informational']}")

    # Save JSON
    report = {
        "role": role_name,
        "scan_date": time.strftime("%Y-%m-%d %H:%M:%S"),
        "total_alerts": total,
        "risk_summary": risk_counts,
        "urls_discovered": len(ROUTES[role_name]),
        "alerts": alerts,
    }
    with open(REPORTS_DIR / f"hrconnect-full-{role_name}.json", "w") as f:
        json.dump(report, f, indent=2)

    # Generate HTML
    zap_api("/JSON/reports/action/generate/", {
        "title": f"HRConnect Security Scan - {role_name.upper()}",
        "template": "traditional-html",
        "reportDir": "/zap/wrk/",
        "reportFileName": f"hrconnect-full-{role_name}.html",
    })

    return {"role": role_name, "total": total, "risk_counts": risk_counts}


def main():
    print("="*60)
    print("  HRConnect Full ZAP Scan — All 5 Roles (Post-Fix)")
    print("="*60)

    all_results = []
    for role_name, creds in ROLES.items():
        result = scan_role(role_name, creds)
        if result:
            all_results.append(result)

    # Summary
    print(f"\n{'='*60}")
    print("  POST-FIX RESULTS")
    print(f"{'='*60}")

    combined = {"High": 0, "Medium": 0, "Low": 0, "Informational": 0}
    for r in all_results:
        for k in combined:
            combined[k] += r["risk_counts"].get(k, 0)
        print(f"  {r['role']:12s} | H:{r['risk_counts']['High']:2d} M:{r['risk_counts']['Medium']:2d} L:{r['risk_counts']['Low']:2d} I:{r['risk_counts']['Informational']:2d} | Total: {r['total']:3d}")

    print(f"\n  COMBINED: H:{combined['High']} M:{combined['Medium']} L:{combined['Low']} I:{combined['Informational']} | Total: {sum(combined.values())}")

    # Save combined
    with open(REPORTS_DIR / "scan-summary-postfix.json", "w") as f:
        json.dump({
            "scan_date": time.strftime("%Y-%m-%d %H:%M:%S"),
            "results": [{"role": r["role"], "total": r["total"], "risk_counts": r["risk_counts"]} for r in all_results],
            "combined": combined,
        }, f, indent=2)

    print(f"\n  Reports saved to {REPORTS_DIR}/")


if __name__ == "__main__":
    main()
