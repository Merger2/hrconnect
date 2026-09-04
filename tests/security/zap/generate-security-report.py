#!/usr/bin/env python3
"""
Generate HRConnect Security Report PNG from ZAP scan JSON results.
Reads all hrconnect-full-*.json and produces a presentation-ready PNG.
"""

import json
import os
import textwrap
from pathlib import Path

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.gridspec import GridSpec
import numpy as np

REPORTS_DIR = Path(__file__).parent / "reports"
OUTPUT_PNG = REPORTS_DIR / "hrconnect-security-report.png"
OUTPUT_PNG.parent.mkdir(exist_ok=True)

ROLES = ["employee", "manager", "finance", "hr", "admin"]
RISK_COLORS = {
    "High": "#dc2626",
    "Medium": "#f59e0b",
    "Low": "#3b82f6",
    "Informational": "#6b7280",
}


def load_results():
    """Load all role JSON reports."""
    results = {}
    for role in ROLES:
        path = REPORTS_DIR / f"hrconnect-full-{role}.json"
        if path.exists():
            with open(path) as f:
                data = json.load(f)
            results[role] = data
    return results


def analyze_alerts(results):
    """Analyze alerts across all roles, deduplicating unique alert types."""
    all_unique = {}  # name -> {risk, roles, count}

    for role, data in results.items():
        for alert in data.get("alerts", []):
            name = alert.get("name", "Unknown")
            risk_raw = alert.get("risk", "Informational")
            risk_map = {"High": "High", "Medium": "Medium", "Low": "Low", "Informational": "Informational", "Info": "Informational"}
            risk = risk_map.get(str(risk_raw), "Informational")
            if isinstance(risk_raw, int):
                risk = {3: "High", 2: "Medium", 1: "Low"}.get(risk_raw, "Informational")

            if name not in all_unique:
                all_unique[name] = {"risk": risk, "roles": set(), "count": 0}
            all_unique[name]["roles"].add(role)
            all_unique[name]["count"] += 1

    return all_unique


def generate_png(results, unique_alerts):
    """Generate a presentation-ready PNG report."""
    fig = plt.figure(figsize=(20, 14), facecolor="white")
    fig.suptitle("HRConnect (HRIS) — Security Assessment Report", fontsize=20, fontweight="bold", y=0.98)
    fig.text(0.5, 0.955, "PT Daya Cipta Mandiri Solusi — OWASP ZAP Full Active Scan", ha="center", fontsize=12, color="#555")
    fig.text(0.5, 0.935, f"Scan Method: ZAP Full Active Scan (HIGH strength, LOW threshold) | Target: localhost:8000 | 5 Roles Scanned", ha="center", fontsize=9, color="#888")

    gs = GridSpec(3, 2, figure=fig, hspace=0.4, wspace=0.3, top=0.91, bottom=0.06, left=0.06, right=0.96)

    # === 1. Summary Table ===
    ax_table = fig.add_subplot(gs[0, :])
    ax_table.axis("off")
    ax_table.set_title("Scan Summary by Role", fontsize=14, fontweight="bold", pad=15)

    headers = ["Role", "High", "Medium", "Low", "Info", "Total", "Unique Types"]
    table_data = []
    totals = [0, 0, 0, 0, 0, 0]

    for role in ROLES:
        if role not in results:
            continue
        rc = results[role].get("risk_summary", {})
        total = results[role].get("total_alerts", 0)
        unique_count = len([n for n, v in unique_alerts.items() if role in v["roles"]])
        row = [
            role.upper(),
            str(rc.get("High", 0)),
            str(rc.get("Medium", 0)),
            str(rc.get("Low", 0)),
            str(rc.get("Informational", 0)),
            str(total),
            str(unique_count),
        ]
        table_data.append(row)
        totals[0] += rc.get("High", 0)
        totals[1] += rc.get("Medium", 0)
        totals[2] += rc.get("Low", 0)
        totals[3] += rc.get("Informational", 0)
        totals[4] += total

    table_data.append(["TOTAL (all unique)", str(totals[0]), str(totals[1]), str(totals[2]), str(totals[3]), str(totals[4]), str(len(unique_alerts))])

    table = ax_table.table(
        cellText=table_data,
        colLabels=headers,
        loc="center",
        cellLoc="center",
    )
    table.auto_set_font_size(False)
    table.set_fontsize(11)
    table.scale(1, 1.6)

    # Color header
    for j in range(len(headers)):
        cell = table[0, j]
        cell.set_facecolor("#1e293b")
        cell.set_text_props(color="white", fontweight="bold")

    # Color risk columns
    for i in range(1, len(table_data) + 1):
        table[i, 0].set_facecolor("#f1f5f9")
        table[i, 0].set_text_props(fontweight="bold")
        if i == len(table_data):  # total row
            for j in range(len(headers)):
                table[i, j].set_facecolor("#e2e8f0")
                table[i, j].set_text_props(fontweight="bold")
        # Color risk cells
        for j, color in [(1, "#fecaca"), (2, "#fef3c7"), (3, "#dbeafe"), (4, "#f3f4f6")]:
            table[i, j].set_facecolor(color)

    # === 2. Stacked Bar Chart (per role) ===
    ax_bar = fig.add_subplot(gs[1, 0])
    ax_bar.set_title("Alerts by Risk Level (per Role)", fontsize=13, fontweight="bold")

    x = np.arange(len(ROLES))
    width = 0.6
    bottom = np.zeros(len(ROLES))

    for risk in ["Informational", "Low", "Medium", "High"]:
        values = []
        for role in ROLES:
            rc = results.get(role, {}).get("risk_summary", {})
            values.append(rc.get(risk, 0))
        bars = ax_bar.bar(x, values, width, bottom=bottom, label=risk, color=RISK_COLORS[risk], edgecolor="white", linewidth=0.5)
        # Add count labels
        for i, v in enumerate(values):
            if v > 0:
                ax_bar.text(x[i], bottom[i] + v / 2, str(v), ha="center", va="center", fontsize=9, color="white", fontweight="bold")
        bottom += np.array(values)

    ax_bar.set_xticks(x)
    ax_bar.set_xticklabels([r.upper() for r in ROLES], fontsize=10, fontweight="bold")
    ax_bar.set_ylabel("Number of Alert Instances")
    ax_bar.legend(loc="upper right", fontsize=9)
    ax_bar.set_xlim(-0.5, len(ROLES) - 0.5)

    # === 3. Pie Chart (overall) ===
    ax_pie = fig.add_subplot(gs[1, 1])
    ax_pie.set_title("Overall Risk Distribution", fontsize=13, fontweight="bold")

    total_risk = {"High": 0, "Medium": 0, "Low": 0, "Informational": 0}
    for role in ROLES:
        rc = results.get(role, {}).get("risk_summary", {})
        for k in total_risk:
            total_risk[k] += rc.get(k, 0)

    pie_data = [v for v in total_risk.values() if v > 0]
    pie_labels = [k for k, v in total_risk.items() if v > 0]
    pie_colors = [RISK_COLORS[k] for k in pie_labels]

    wedges, texts, autotexts = ax_pie.pie(
        pie_data, labels=pie_labels, colors=pie_colors,
        autopct=lambda p: f"{p:.1f}%\n({int(round(p * sum(pie_data) / 100))})",
        startangle=90, textprops={"fontsize": 10},
    )
    for t in autotexts:
        t.set_fontsize(9)
        t.set_fontweight("bold")

    # === 4. Unique Alert Types Table ===
    ax_unique = fig.add_subplot(gs[2, :])
    ax_unique.axis("off")
    ax_unique.set_title("Unique Alert Types Identified (All Roles Combined)", fontsize=13, fontweight="bold", pad=10)

    sorted_alerts = sorted(unique_alerts.items(), key=lambda x: {"High": 0, "Medium": 1, "Low": 2, "Informational": 3}.get(x[1]["risk"], 4))
    unique_headers = ["#", "Alert Type", "Risk", "Instances", "Found In Roles"]
    unique_data = []
    for idx, (name, info) in enumerate(sorted_alerts, 1):
        roles_str = ", ".join(sorted(info["roles"])).upper()
        unique_data.append([str(idx), name[:60], info["risk"], str(info["count"]), roles_str])

    if unique_data:
        utable = ax_unique.table(
            cellText=unique_data,
            colLabels=unique_headers,
            loc="center",
            cellLoc="left",
        )
        utable.auto_set_font_size(False)
        utable.set_fontsize(9)
        utable.scale(1, 1.4)

        for j in range(len(unique_headers)):
            cell = utable[0, j]
            cell.set_facecolor("#1e293b")
            cell.set_text_props(color="white", fontweight="bold")

        risk_bg = {"High": "#fecaca", "Medium": "#fef3c7", "Low": "#dbeafe", "Informational": "#f3f4f6"}
        for i in range(1, len(unique_data) + 1):
            risk_val = unique_data[i - 1][2]
            for j in range(len(unique_headers)):
                if j == 2:
                    utable[i, j].set_facecolor(risk_bg.get(risk_val, "#f3f4f6"))
                utable[i, j].set_text_props(wrap=True)

    # Footer
    fig.text(0.5, 0.01,
             "Note: SQL Injection and Buffer Overflow findings are ZAP false positives (Laravel uses Eloquent/parameterized queries). "
             "Low confidence findings should be manually verified.",
             ha="center", fontsize=8, color="#666", style="italic")

    plt.savefig(OUTPUT_PNG, dpi=150, bbox_inches="tight", facecolor="white")
    plt.close()
    print(f"  PNG saved: {OUTPUT_PNG} ({OUTPUT_PNG.stat().st_size:,} bytes)")


def main():
    print("=" * 60)
    print("  HRConnect Security Report Generator")
    print("=" * 60)

    results = load_results()
    if not results:
        print("  No scan results found!")
        return

    print(f"  Loaded {len(results)} role reports")
    unique_alerts = analyze_alerts(results)
    print(f"  Unique alert types: {len(unique_alerts)}")

    for risk in ["High", "Medium", "Low", "Informational"]:
        count = sum(1 for v in unique_alerts.values() if v["risk"] == risk)
        if count > 0:
            print(f"    {risk}: {count} types")

    generate_png(results, unique_alerts)
    print(f"\n  Output: {OUTPUT_PNG}")


if __name__ == "__main__":
    main()
