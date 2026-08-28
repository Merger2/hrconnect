#!/usr/bin/env python3
"""
OWASP ZAP Report Generator for HRConnect
Generates PNG charts from ZAP JSON scan results
"""

import json
import sys
from pathlib import Path
from datetime import datetime

try:
    import matplotlib.pyplot as plt
    import matplotlib.patches as mpatches
    from matplotlib.gridspec import GridSpec
    import numpy as np
except ImportError:
    print("❌ matplotlib not installed. Run: pip install matplotlib")
    sys.exit(1)

# Configuration
REPORT_DIR = Path("tests/security/zap/reports")
OUTPUT_DIR = Path("tests/security/zap/reports")
OUTPUT_FILE = OUTPUT_DIR / "hrconnect-security-report.png"

# OWASP Top 10 2025 mapping
OWASP_CATEGORIES = {
    "A01": "Broken Access Control",
    "A02": "Cryptographic Failures",
    "A03": "Injection",
    "A04": "Insecure Design",
    "A05": "Security Misconfiguration",
    "A06": "Vulnerable Components",
    "A07": "Auth Failures",
    "A08": "Data Integrity Failures",
    "A09": "Logging Failures",
    "A10": "SSRF",
}

# Risk colors
RISK_COLORS = {
    "High": "#dc3545",      # Red
    "Medium": "#fd7e14",    # Orange
    "Low": "#ffc107",       # Yellow
    "Informational": "#17a2b8",  # Cyan
}

# Alert to OWASP category mapping (simplified)
ALERT_OWASP_MAP = {
    "X-Frame-Options": "A05",
    "X-Content-Type-Options": "A05",
    "Cookie No HttpOnly": "A07",
    "Cookie Without Secure": "A05",
    "XSS": "A03",
    "SQL Injection": "A03",
    "CSRF": "A01",
    "Authentication": "A07",
    "Session": "A07",
    "Directory Browsing": "A05",
    "Information Disclosure": "A05",
    "Mixed Content": "A02",
    "Cache-control": "A05",
    "Password Autocomplete": "A07",
}


def load_zap_json(json_path: Path) -> dict:
    """Load ZAP JSON report."""
    with open(json_path, "r") as f:
        return json.load(f)


def extract_alerts(zap_data: dict) -> list:
    """Extract alerts from ZAP JSON data."""
    alerts = []
    
    # Handle different ZAP JSON formats
    if "site" in zap_data:
        for site in zap_data["site"]:
            if "alerts" in site:
                for alert in site["alerts"]:
                    alerts.append({
                        "name": alert.get("name", "Unknown"),
                        "risk": alert.get("riskdesc", "Informational").split(" ")[0],
                        "count": alert.get("count", len(alert.get("instances", []))),
                        "url": alert.get("instances", [{}])[0].get("uri", ""),
                        "solution": alert.get("solution", ""),
                        "reference": alert.get("reference", ""),
                        "cweid": alert.get("cweid", ""),
                        "wasid": alert.get("wasid", ""),
                    })
    elif "alerts" in zap_data:
        for alert in zap_data["alerts"]:
            alerts.append({
                "name": alert.get("name", "Unknown"),
                "risk": alert.get("risk", "Informational"),
                "count": alert.get("count", 1),
                "url": alert.get("url", ""),
                "solution": alert.get("solution", ""),
                "reference": alert.get("reference", ""),
                "cweid": alert.get("cweid", ""),
                "wasid": alert.get("wasid", ""),
            })
    
    return alerts


def classify_by_risk(alerts: list) -> dict:
    """Classify alerts by risk level."""
    risk_counts = {"High": 0, "Medium": 0, "Low": 0, "Informational": 0}
    
    for alert in alerts:
        risk = alert.get("risk", "Informational")
        count = int(alert.get("count", 1))
        if risk in risk_counts:
            risk_counts[risk] += count
        else:
            risk_counts["Informational"] += count
    
    return risk_counts


def classify_by_owasp(alerts: list) -> dict:
    """Classify alerts by OWASP Top 10 category."""
    owasp_counts = {cat: 0 for cat in OWASP_CATEGORIES.keys()}
    
    for alert in alerts:
        alert_name = alert.get("name", "")
        count = int(alert.get("count", 1))
        matched = False
        
        for keyword, category in ALERT_OWASP_MAP.items():
            if keyword.lower() in alert_name.lower():
                owasp_counts[category] += count
                matched = True
                break
        
        if not matched:
            # Default to A05 (Security Misconfiguration) for unknown
            owasp_counts["A05"] += count
    
    return owasp_counts


def generate_chart(alerts: list, risk_counts: dict, owasp_counts: dict, output_path: Path):
    """Generate PNG report with charts - Desktop format (1440x900 landscape)."""
    # Create figure with landscape layout for desktop
    fig = plt.figure(figsize=(19.2, 10.8))  # 1440x900 at 75 DPI
    gs = GridSpec(3, 2, figure=fig, hspace=0.35, wspace=0.3, left=0.06, right=0.97, top=0.88, bottom=0.05)
    
    # Title
    fig.suptitle(
        "HRIS PT Daya Cipta Mandiri Solusi — Security Test Report (OWASP ZAP)",
        fontsize=18,
        fontweight="bold",
        y=0.96,
    )
    
    # Summary card (top row, full width)
    ax_summary = fig.add_subplot(gs[0, :])
    ax_summary.axis("off")
    
    total_alerts = sum(risk_counts.values())
    summary_text = (
        f"Scan Date: {datetime.now().strftime('%Y-%m-%d %H:%M')}   |   "
        f"Total Alerts: {total_alerts}   |   "
        f"High: {risk_counts['High']}   |   Medium: {risk_counts['Medium']}   |   "
        f"Low: {risk_counts['Low']}   |   Info: {risk_counts['Informational']}"
    )
    ax_summary.text(
        0.5, 0.5, summary_text,
        ha="center", va="center",
        fontsize=13,
        bbox=dict(boxstyle="round,pad=0.8", facecolor="#e9ecef", alpha=0.9),
    )
    
    # Risk Distribution (Pie Chart)
    ax_risk = fig.add_subplot(gs[1, 0])
    risk_labels = []
    risk_values = []
    risk_colors = []
    
    for risk, count in risk_counts.items():
        if count > 0:
            risk_labels.append(f"{risk}\n({count})")
            risk_values.append(count)
            risk_colors.append(RISK_COLORS[risk])
    
    if risk_values:
        wedges, texts, autotexts = ax_risk.pie(
            risk_values,
            labels=risk_labels,
            colors=risk_colors,
            autopct="%1.1f%%",
            startangle=90,
        )
        ax_risk.set_title("Risk Distribution", fontweight="bold", fontsize=12)
    else:
        ax_risk.text(0.5, 0.5, "No alerts found", ha="center", va="center")
        ax_risk.set_title("Risk Distribution", fontweight="bold", fontsize=12)
    
    # OWASP Top 10 (Bar Chart)
    ax_owasp = fig.add_subplot(gs[1, 1])
    owasp_labels = []
    owasp_values = []
    
    for cat, count in owasp_counts.items():
        if count > 0:
            label = f"{cat}: {OWASP_CATEGORIES.get(cat, 'Unknown')}"
            owasp_labels.append(label)
            owasp_values.append(count)
    
    if owasp_values:
        y_pos = np.arange(len(owasp_labels))
        bars = ax_owasp.barh(y_pos, owasp_values, color="#0d6efd", alpha=0.8)
        ax_owasp.set_yticks(y_pos)
        ax_owasp.set_yticklabels(owasp_labels, fontsize=9)
        ax_owasp.set_xlabel("Number of Alerts")
        ax_owasp.set_title("OWASP Top 10 Distribution", fontweight="bold", fontsize=12)
        
        # Add count labels on bars
        for bar, count in zip(bars, owasp_values):
            ax_owasp.text(
                bar.get_width() + 0.1,
                bar.get_y() + bar.get_height() / 2,
                str(count),
                va="center",
                fontsize=10,
            )
    else:
        ax_owasp.text(0.5, 0.5, "No OWASP findings", ha="center", va="center")
        ax_owasp.set_title("OWASP Top 10 Distribution", fontweight="bold", fontsize=12)
    
    # Bottom row: Top Alerts (left) + Summary Table (right)
    sorted_alerts = sorted(alerts, key=lambda x: x.get("count", 0), reverse=True)[:10]
    
    # Top 10 Alerts (Bar Chart) - left bottom
    ax_top = fig.add_subplot(gs[2, 0])
    
    if sorted_alerts:
        alert_names = [a["name"][:35] for a in sorted_alerts[:6]]
        alert_counts = [a.get("count", 1) for a in sorted_alerts[:6]]
        alert_risks = [a.get("risk", "Informational") for a in sorted_alerts[:6]]
        alert_colors = [RISK_COLORS.get(r, "#6c757d") for r in alert_risks]
        
        y_pos = np.arange(len(alert_names))
        bars = ax_top.barh(y_pos, alert_counts, color=alert_colors, alpha=0.8)
        ax_top.set_yticks(y_pos)
        ax_top.set_yticklabels(alert_names, fontsize=9)
        ax_top.set_xlabel("Occurrences")
        ax_top.set_title("Top Alerts", fontweight="bold", fontsize=11)
        ax_top.invert_yaxis()
        
        for bar, count in zip(bars, alert_counts):
            ax_top.text(bar.get_width() + 0.1, bar.get_y() + bar.get_height() / 2, str(count), va="center", fontsize=9)
        
        legend_patches = [
            mpatches.Patch(color=RISK_COLORS["High"], label="High"),
            mpatches.Patch(color=RISK_COLORS["Medium"], label="Medium"),
            mpatches.Patch(color=RISK_COLORS["Low"], label="Low"),
            mpatches.Patch(color=RISK_COLORS["Informational"], label="Info"),
        ]
        ax_top.legend(handles=legend_patches, loc="lower right", fontsize=8)
    else:
        ax_top.text(0.5, 0.5, "No alerts found", ha="center", va="center")
        ax_top.set_title("Top Alerts", fontweight="bold", fontsize=11)
    
    # Summary Table - right bottom
    ax_table = fig.add_subplot(gs[2, 1])
    ax_table.axis("off")
    
    if sorted_alerts:
        table_data = []
        for alert in sorted_alerts[:6]:
            table_data.append([
                alert["name"][:35],
                alert.get("risk", "Info"),
                str(alert.get("count", 1)),
            ])
        
        table = ax_table.table(
            cellText=table_data,
            colLabels=["Alert", "Risk", "Count"],
            cellLoc="left",
            loc="center",
        )
        table.auto_set_font_size(False)
        table.set_fontsize(9)
        table.auto_set_column_width([0, 1, 2])
        
        for i, row in enumerate(table_data):
            risk = row[1]
            color = RISK_COLORS.get(risk, "#ffffff")
            table[i + 1, 1].set_facecolor(color)
            table[i + 1, 1].set_text_props(color="white" if risk == "High" else "black")
        
        for j in range(3):
            table[0, j].set_facecolor("#343a40")
            table[0, j].set_text_props(color="white", fontweight="bold")
        
        ax_table.set_title("Alert Summary", fontweight="bold", fontsize=11, pad=15)
    
    # Footer
    fig.text(
        0.5, 0.01,
        f"Generated by OWASP ZAP • {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}  •  Resolution: 1440×900 (Desktop)",
        ha="center",
        fontsize=9,
        color="gray",
    )
    
    # Save at 75 DPI = 1440x900 pixels
    plt.savefig(output_path, dpi=75, bbox_inches="tight", facecolor="white")
    plt.close()
    
    print(f"✅ Report saved to: {output_path}")
    return output_path


def main():
    """Main function."""
    if len(sys.argv) < 2:
        # Find latest JSON file
        json_files = sorted(REPORT_DIR.glob("*.json"), key=lambda x: x.stat().st_mtime, reverse=True)
        if not json_files:
            print("❌ No JSON report files found in tests/security/zap/reports/")
            print("   Run a ZAP scan first: ./tests/security/zap/run-scan.sh")
            sys.exit(1)
        json_path = json_files[0]
        print(f"📊 Using latest report: {json_path}")
    else:
        json_path = Path(sys.argv[1])
    
    if not json_path.exists():
        print(f"❌ File not found: {json_path}")
        sys.exit(1)
    
    # Load and process
    print(f"📥 Loading {json_path}...")
    zap_data = load_zap_json(json_path)
    
    print("🔍 Extracting alerts...")
    alerts = extract_alerts(zap_data)
    
    print(f"📋 Found {len(alerts)} alert types")
    
    print("📊 Classifying by risk...")
    risk_counts = classify_by_risk(alerts)
    
    print("📊 Classifying by OWASP Top 10...")
    owasp_counts = classify_by_owasp(alerts)
    
    print("🎨 Generating chart...")
    output_path = generate_chart(alerts, risk_counts, owasp_counts, OUTPUT_FILE)
    
    # Print summary
    print("\n" + "=" * 50)
    print("  SECURITY SCAN SUMMARY")
    print("=" * 50)
    print(f"\n  Risk Distribution:")
    for risk, count in risk_counts.items():
        if count > 0:
            print(f"    {risk:15} {count:5} alerts")
    print(f"\n  OWASP Top 10 Findings:")
    for cat, count in owasp_counts.items():
        if count > 0:
            print(f"    {cat}: {OWASP_CATEGORIES.get(cat, 'Unknown'):30} {count:5}")
    print(f"\n  Report: {output_path}")
    print("=" * 50)


if __name__ == "__main__":
    main()
