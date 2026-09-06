#!/usr/bin/env python3
"""Run ZAP full active scan for admin (superadmin) role only."""
import json, re, time, urllib.parse, urllib.request, http.cookiejar
from pathlib import Path

ZAP_BASE = "http://localhost:8080"
BASE_URL = "http://127.0.0.1:8000"
REPORTS_DIR = Path(__file__).parent / "reports"


def zap_api(ep, params=None):
    url = f"{ZAP_BASE}{ep}"
    if params:
        url += "?" + urllib.parse.urlencode(params)
    try:
        resp = urllib.request.urlopen(urllib.request.Request(url), timeout=120)
        return json.loads(resp.read().decode())
    except Exception as e:
        return {"error": str(e)}


# Login
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
resp = opener.open(f"{BASE_URL}/login", timeout=30)
html = resp.read().decode()
m = re.search(r'name="_token"[^>]*value="([^"]+)"', html)
csrf = m.group(1) if m else ""
data = urllib.parse.urlencode({"email": "admin@hrconnect.local", "password": "ChangeMe!2026", "_token": csrf}).encode()
req = urllib.request.Request(f"{BASE_URL}/login", data=data)
req.add_header("Content-Type", "application/x-www-form-urlencoded")
try:
    opener.open(req, timeout=30)
except Exception:
    pass
cookies = "; ".join(f"{c.name}={c.value}" for c in cj)

ctx_name = "hrconnect-admin"
zap_api("/JSON/context/action/newContext/", {"contextName": ctx_name})

admin_routes = [
    "/login", "/forgot-password", "/home", "/my-schedule", "/scan", "/my-attendance",
    "/admin/dashboard", "/admin/employees", "/admin/departments", "/admin/positions",
    "/admin/grades", "/admin/leave-types", "/admin/schedules", "/admin/holidays",
    "/admin/attendance", "/admin/attendance/report", "/admin/leave", "/admin/overtime",
    "/admin/reimbursement", "/admin/correction", "/admin/shift-swap", "/admin/wfh", "/admin/kasbon",
    "/admin/reports", "/admin/reports/attendance", "/admin/reports/payroll",
    "/admin/reports/leave", "/admin/reports/export", "/admin/documents",
    "/admin/document-templates", "/admin/checklist", "/admin/roles", "/admin/permissions",
    "/admin/backup", "/admin/logs", "/admin/settings", "/admin/knowledge-base",
    "/admin/knowledge-base/eval", "/admin/audit", "/admin/approval-settings",
    "/approvals", "/approvals/history", "/my-leave", "/my-reimbursement",
    "/my-correction", "/my-overtime", "/my-wfh", "/my-kasbon", "/my-payslips",
    "/my-documents", "/user/profile", "/hr-tasks", "/chat",
]
for path in admin_routes:
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

print(f"Context: {ctx_name}")

# Spider
print("Spidering...")
resp = zap_api("/JSON/spider/action/scan/", {
    "url": BASE_URL, "recurse": "true", "maxDepth": "5", "contextName": ctx_name,
})
sid = resp.get("scan", "0")
while True:
    r = zap_api("/JSON/spider/view/status/", {"scanId": sid})
    st = r.get("status", "0")
    print(f"  Spider: {st}%", end="\r")
    if st == "100":
        break
    time.sleep(3)
print("  Spider complete    ")

# Passive scan wait
while True:
    r = zap_api("/JSON/pscan/view/recordsToScan/", {})
    rem = r.get("recordsToScan", "0")
    if rem == "0" or rem == 0:
        break
    time.sleep(2)
print("Passive scan complete")

# Active scan
print("Active scan...")
resp = zap_api("/JSON/ascan/action/scan/", {
    "url": BASE_URL, "recurse": "true", "inScopeOnly": "true", "contextId": "",
})
aid = resp.get("scan", "0")
while True:
    r = zap_api("/JSON/ascan/view/status/", {"scanId": aid})
    st = r.get("status", "0")
    print(f"  Active: {st}%", end="\r")
    if st == "100":
        break
    time.sleep(10)
print("  Active scan complete")

# Get alerts
alerts_data = zap_api("/JSON/core/view/alerts/", {
    "baseurl": BASE_URL, "start": "0", "count": "1000",
})
alerts = alerts_data.get("alerts", [])

risk_map_str = {"High": 3, "Medium": 2, "Low": 1, "Informational": 0, "Info": 0}
risk_counts = {"High": 0, "Medium": 0, "Low": 0, "Informational": 0}
for a in alerts:
    r = a.get("risk", "0")
    if isinstance(r, int):
        risk = r
    elif str(r).isdigit():
        risk = int(r)
    else:
        risk = risk_map_str.get(str(r).capitalize(), 0)
    if risk == 3:
        risk_counts["High"] += 1
    elif risk == 2:
        risk_counts["Medium"] += 1
    elif risk == 1:
        risk_counts["Low"] += 1
    else:
        risk_counts["Informational"] += 1

# Save JSON
report = {
    "role": "admin",
    "scan_date": time.strftime("%Y-%m-%d %H:%M:%S"),
    "total_alerts": len(alerts),
    "risk_summary": risk_counts,
    "urls_discovered": len(admin_routes),
    "alerts": alerts,
}
with open(REPORTS_DIR / "hrconnect-full-admin.json", "w") as f:
    json.dump(report, f, indent=2)

# Generate HTML
zap_api("/JSON/reports/action/generate/", {
    "title": "HRConnect Security Scan - ADMIN",
    "template": "traditional-html",
    "reportDir": "/zap/wrk/",
    "reportFileName": "hrconnect-full-admin.html",
})

# Generate XML
zap_api("/JSON/reports/action/generate/", {
    "title": "HRConnect Security Scan - ADMIN",
    "template": "traditional-xml",
    "reportDir": "/zap/wrk/",
    "reportFileName": "hrconnect-full-admin.xml",
})

print(f"\nAdmin results: {len(alerts)} alerts")
for k, v in risk_counts.items():
    if v > 0:
        print(f"  {k}: {v}")
print("Done!")
