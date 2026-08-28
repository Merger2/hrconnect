#!/usr/bin/env python3
"""
HRConnect Performance Test Report Generator

Reads K6 JSON output files from tests/performance/results/*.json
and generates a single PNG report at tests/performance/reports/hrconnect-performance.png

Usage:
    python3 tests/performance/generate_report.py

Requirements:
    pip install matplotlib numpy
"""

import json
import os
import sys
from pathlib import Path
from collections import defaultdict

import matplotlib
matplotlib.use('Agg')  # Non-interactive backend
import matplotlib.pyplot as plt
import matplotlib.gridspec as gridspec
import numpy as np


# ─── Configuration ──────────────────────────────────────────────

RESULTS_DIR = Path(__file__).parent / 'results'
REPORTS_DIR = Path(__file__).parent / 'reports'
OUTPUT_FILE = REPORTS_DIR / 'hrconnect-performance.png'

# Feature display names and colors
FEATURES = {
    'auth':           {'label': 'Auth',           'color': '#2563EB'},
    'employees':      {'label': 'Employees',      'color': '#059669'},
    'attendance':     {'label': 'Attendance',     'color': '#D97706'},
    'leave':          {'label': 'Leave',          'color': '#DC2626'},
    'payroll':        {'label': 'Payroll',        'color': '#7C3AED'},
    'reports':        {'label': 'Reports',        'color': '#0891B2'},
    'operations':     {'label': 'Operations',     'color': '#475569'},
    'assets':         {'label': 'Assets',         'color': '#0D9488'},
    'kb-chat':        {'label': 'KB Chat',        'color': '#BE185D'},
    'kb':             {'label': 'KB Chat',        'color': '#BE185D'},
    'unknown':        {'label': 'Other',          'color': '#94A3B8'},
}


# ─── K6 JSON Parser ────────────────────────────────────────────

def parse_k6_json(filepath):
    """
    Parse K6 JSON lines output.
    Returns dict with metrics data per feature.
    
    K6 --out json format: one JSON object per line.
    Types: Point (single measurement), Summary (end-of-test summary).
    """
    metrics = defaultdict(lambda: {
        'durations': [],
        'errors': 0,
        'total': 0,
        'http_reqs': 0,
    })

    summary = None

    with open(filepath, 'r') as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            try:
                entry = json.loads(line)
            except json.JSONDecodeError:
                continue

            entry_type = entry.get('type')

            # Summary contains aggregated data
            if entry_type == 'Summary':
                summary = entry.get('data', {})
                continue

            # Point contains individual measurements
            if entry_type != 'Point':
                continue

            metric = entry.get('metric', '')
            data = entry.get('data', {})
            tags = data.get('tags', {})

            feature = tags.get('feature', 'unknown')

            if metric == 'http_req_duration':
                value = data.get('value', 0)
                metrics[feature]['durations'].append(value)
                metrics[feature]['total'] += 1

                # Only count 5xx + network errors as real errors.
                # 4xx (403 permission, 422 validation, 429 rate limit) are
                # expected business responses, NOT server failures.
                status = tags.get('status', '')
                if status and status.startswith('5'):
                    metrics[feature]['errors'] += 1

            elif metric == 'http_reqs':
                metrics[feature]['http_reqs'] += 1

            elif metric == 'http_req_failed':
                # Only count as error if status is 5xx or no status (network fail)
                if data.get('value', 0) > 0:
                    req_status = tags.get('status', '')
                    if not req_status or req_status.startswith('5'):
                        metrics[feature]['errors'] += 1
                        metrics[feature]['total'] += 1

    return dict(metrics), summary


def calculate_percentile(values, percentile):
    """Calculate a percentile from a list of values."""
    if not values:
        return 0
    sorted_vals = sorted(values)
    k = (len(sorted_vals) - 1) * (percentile / 100)
    f = int(k)
    c = f + 1
    if c >= len(sorted_vals):
        return sorted_vals[-1]
    d = k - f
    return sorted_vals[f] + d * (sorted_vals[c] - sorted_vals[f])


def compute_feature_metrics(feature_data, total_duration_secs=None):
    """Compute avg, p95, p99, error rate, RPS for a feature."""
    durations = feature_data['durations']
    errors = feature_data['errors']
    http_reqs = feature_data['http_reqs']

    if not durations:
        return None

    avg = sum(durations) / len(durations)
    p95 = calculate_percentile(durations, 95)
    p99 = calculate_percentile(durations, 99)
    # Error rate: only 5xx / network errors (not 4xx validation/permission)
    error_rate = (errors / len(durations) * 100) if durations else 0
    # RPS: total HTTP requests / test duration (approx)
    rps = http_reqs / max(1, total_duration_secs) if total_duration_secs else http_reqs / max(1, len(durations) / 5)

    return {
        'avg': avg,
        'p95': p95,
        'p99': p99,
        'error_rate': error_rate,
        'rps': rps,
        'total_requests': len(durations),
    }


# ─── Report Generator ──────────────────────────────────────────

def generate_report(all_metrics, all_summaries):
    """Generate PNG report from collected metrics."""

    # Compute overall stats first (from raw observations — statistically valid)
    all_durations = []
    total_errors = 0
    total_requests = 0
    for data in all_metrics.values():
        all_durations.extend(data['durations'])
        total_errors += data['errors']
        total_requests += data['total'] or len(data['durations'])

    overall_p95 = calculate_percentile(all_durations, 95) if all_durations else 0
    overall_avg = sum(all_durations) / len(all_durations) if all_durations else 0
    overall_error_rate = (total_errors / max(1, len(all_durations)) * 100) if all_durations else 0

    # Estimate total test duration from summary data
    total_duration_secs = 0
    for s in all_summaries:
        root_group = s.get('root_group', {})
        total_duration_secs += root_group.get('duration', 0) / 1000  # ms to secs
    if total_duration_secs == 0:
        total_duration_secs = max(1, len(all_durations) / 5)  # fallback estimate

    # Compute per-feature metrics
    feature_results = {}
    for feature, data in all_metrics.items():
        result = compute_feature_metrics(data, total_duration_secs)
        if result:
            feature_results[feature] = result

    if not feature_results:
        print("❌ No valid metrics found. Run K6 suites first.")
        sys.exit(1)

    # Count suites
    suite_count = len(feature_results)

    # ─── Create figure ───
    fig = plt.figure(figsize=(19.2, 10.8), facecolor='#F8FAFC')  # 1920x1080 desktop
    gs = gridspec.GridSpec(3, 3, figure=fig, hspace=0.4, wspace=0.35,
                           left=0.06, right=0.97, top=0.90, bottom=0.05,
                           height_ratios=[0.6, 1, 0.8])

    # ─── Title ───
    fig.suptitle('HRIS PT Daya Cipta Mandiri Solusi — Performance Test Report',
                 fontsize=20, fontweight='bold', color='#1E293B', y=0.97)
    fig.text(0.5, 0.945, 'Generated from actual K6 load test results — no hardcoded values',
             ha='center', fontsize=10, color='#64748B', style='italic')

    # ─── Summary Card (top) ───
    ax_summary = fig.add_subplot(gs[0, :])
    ax_summary.axis('off')

    summary_text = (
        f"Test Suites: {suite_count}    |    "
        f"Total Requests: {total_requests:,}    |    "
        f"Overall p95: {overall_p95:.1f}ms    |    "
        f"Overall Avg: {overall_avg:.1f}ms    |    "
        f"Error Rate: {overall_error_rate:.2f}%"
    )
    ax_summary.text(0.5, 0.5, summary_text, transform=ax_summary.transAxes,
                    fontsize=11, fontweight='bold', color='#1E293B',
                    ha='center', va='center',
                    bbox=dict(boxstyle='round,pad=0.6', facecolor='#E2E8F0',
                              edgecolor='#94A3B8', alpha=0.9))

    # ─── Chart 1: p95 Response Time per Feature (bar) ───
    ax1 = fig.add_subplot(gs[1, 0:2])
    features_sorted = sorted(feature_results.keys(), key=lambda f: feature_results[f]['p95'])
    labels = [FEATURES.get(f, {}).get('label', f) for f in features_sorted]
    p95_vals = [feature_results[f]['p95'] for f in features_sorted]
    colors = [FEATURES.get(f, {}).get('color', '#64748B') for f in features_sorted]

    bars = ax1.barh(labels, p95_vals, color=colors, edgecolor='white', height=0.6)
    ax1.set_xlabel('p95 Response Time (ms)', fontsize=10, color='#475569')
    ax1.set_title('p95 Response Time per Feature', fontsize=13, fontweight='bold',
                  color='#1E293B', pad=10)
    ax1.tick_params(axis='y', labelsize=10)
    ax1.tick_params(axis='x', labelsize=9)
    ax1.set_facecolor('#F8FAFC')
    ax1.spines['top'].set_visible(False)
    ax1.spines['right'].set_visible(False)

    # Add value labels
    for bar, val in zip(bars, p95_vals):
        ax1.text(bar.get_width() + max(p95_vals) * 0.02, bar.get_y() + bar.get_height() / 2,
                 f'{val:.1f}ms', va='center', fontsize=9, color='#475569')

    # ─── Chart 2: Avg Response Time + Error Rate (side by side) ───
    ax2 = fig.add_subplot(gs[1, 2])
    avg_vals = [feature_results[f]['avg'] for f in features_sorted]

    bars2 = ax2.barh(labels, avg_vals, color=colors, edgecolor='white', height=0.6, alpha=0.8)
    ax2.set_xlabel('Avg Response Time (ms)', fontsize=10, color='#475569')
    ax2.set_title('Average Response Time per Feature', fontsize=13, fontweight='bold',
                  color='#1E293B', pad=10)
    ax2.tick_params(axis='y', labelsize=10)
    ax2.tick_params(axis='x', labelsize=9)
    ax2.set_facecolor('#F8FAFC')
    ax2.spines['top'].set_visible(False)
    ax2.spines['right'].set_visible(False)

    for bar, val in zip(bars2, avg_vals):
        ax2.text(bar.get_width() + max(avg_vals) * 0.02, bar.get_y() + bar.get_height() / 2,
                 f'{val:.1f}ms', va='center', fontsize=9, color='#475569')

    # ─── Chart 3: Error Rate per Feature (bar) ───
    ax3 = fig.add_subplot(gs[2, 0])
    error_vals = [feature_results[f]['error_rate'] for f in features_sorted]
    error_colors = ['#DC2626' if e > 5 else '#D97706' if e > 1 else '#059669' for e in error_vals]

    bars3 = ax3.barh(labels, error_vals, color=error_colors, edgecolor='white', height=0.6)
    ax3.set_xlabel('Error Rate (%)', fontsize=10, color='#475569')
    ax3.set_title('Error Rate per Feature', fontsize=13, fontweight='bold',
                  color='#1E293B', pad=10)
    ax3.tick_params(axis='y', labelsize=10)
    ax3.tick_params(axis='x', labelsize=9)
    ax3.set_facecolor('#F8FAFC')
    ax3.spines['top'].set_visible(False)
    ax3.spines['right'].set_visible(False)

    # Add threshold line
    ax3.axvline(x=5, color='#DC2626', linestyle='--', alpha=0.5, linewidth=1)
    ax3.text(5.2, len(labels) - 0.5, '5% threshold', fontsize=8, color='#DC2626', alpha=0.7)

    for bar, val in zip(bars3, error_vals):
        ax3.text(bar.get_width() + max(error_vals) * 0.02 + 0.1,
                 bar.get_y() + bar.get_height() / 2,
                 f'{val:.2f}%', va='center', fontsize=9, color='#475569')

    # ─── Chart 4: Request Throughput (bar) ───
    ax4 = fig.add_subplot(gs[2, 1])
    rps_vals = [feature_results[f]['rps'] for f in features_sorted]

    bars4 = ax4.barh(labels, rps_vals, color=colors, edgecolor='white', height=0.6, alpha=0.7)
    ax4.set_xlabel('Requests/sec', fontsize=10, color='#475569')
    ax4.set_title('Request Throughput per Feature', fontsize=13, fontweight='bold',
                  color='#1E293B', pad=10)
    ax4.tick_params(axis='y', labelsize=10)
    ax4.tick_params(axis='x', labelsize=9)
    ax4.set_facecolor('#F8FAFC')
    ax4.spines['top'].set_visible(False)
    ax4.spines['right'].set_visible(False)

    for bar, val in zip(bars4, rps_vals):
        ax4.text(bar.get_width() + max(rps_vals) * 0.02, bar.get_y() + bar.get_height() / 2,
                 f'{val:.1f}', va='center', fontsize=9, color='#475569')

    # ─── Summary Table (right side) ───
    ax_table = fig.add_subplot(gs[2, 2])
    ax_table.axis('off')

    table_data = [['Feature', 'Avg (ms)', 'p95 (ms)', 'p99 (ms)', 'Error Rate', 'Requests']]
    for f in features_sorted:
        r = feature_results[f]
        table_data.append([
            FEATURES.get(f, {}).get('label', f),
            f"{r['avg']:.1f}",
            f"{r['p95']:.1f}",
            f"{r['p99']:.1f}",
            f"{r['error_rate']:.2f}%",
            f"{r['total_requests']:,}",
        ])

    # Overall row
    overall_p99 = calculate_percentile(all_durations, 99) if all_durations else 0
    table_data.append([
        'OVERALL',
        f"{overall_avg:.1f}",
        f"{overall_p95:.1f}",
        f"{overall_p99:.1f}",
        f"{overall_error_rate:.2f}%",
        f"{total_requests:,}",
    ])

    table = ax_table.table(
        cellText=table_data,
        cellLoc='center',
        loc='center',
        colWidths=[0.22, 0.15, 0.15, 0.15, 0.15, 0.15],
    )
    table.auto_set_font_size(False)
    table.set_fontsize(8)
    table.scale(1, 1.4)

    # Style header row
    for j in range(len(table_data[0])):
        cell = table[0, j]
        cell.set_facecolor('#1E293B')
        cell.set_text_props(color='white', fontweight='bold')

    # Style data rows
    for i in range(1, len(table_data)):
        for j in range(len(table_data[0])):
            cell = table[i, j]
            if i == len(table_data) - 1:  # Overall row
                cell.set_facecolor('#E2E8F0')
                cell.set_text_props(fontweight='bold')
            elif i % 2 == 0:
                cell.set_facecolor('#F1F5F9')
            else:
                cell.set_facecolor('#FFFFFF')

    # ─── Save ───
    REPORTS_DIR.mkdir(parents=True, exist_ok=True)
    fig.savefig(OUTPUT_FILE, dpi=150, bbox_inches='tight',
                facecolor=fig.get_facecolor(), edgecolor='none')
    plt.close(fig)

    print(f"✅ Report generated: {OUTPUT_FILE}")
    print(f"   {suite_count} suites | {total_requests:,} requests | p95: {overall_p95:.1f}ms")


# ─── Main ──────────────────────────────────────────────────────

def main():
    if not RESULTS_DIR.exists():
        print(f"❌ Results directory not found: {RESULTS_DIR}")
        print("   Run K6 suites first: bash tests/performance/run-all.sh")
        sys.exit(1)

    json_files = sorted(RESULTS_DIR.glob('*.json'))
    if not json_files:
        print(f"❌ No JSON files found in {RESULTS_DIR}")
        print("   Run K6 suites first: bash tests/performance/run-all.sh")
        sys.exit(1)

    print(f"📊 Found {len(json_files)} result file(s):")
    for f in json_files:
        size = f.stat().st_size
        print(f"   {f.name} ({size:,} bytes)")

    # Parse all files
    all_metrics = defaultdict(lambda: {
        'durations': [],
        'errors': 0,
        'total': 0,
        'http_reqs': 0,
    })
    all_summaries = []

    for filepath in json_files:
        metrics, summary = parse_k6_json(filepath)
        for feature, data in metrics.items():
            all_metrics[feature]['durations'].extend(data['durations'])
            all_metrics[feature]['errors'] += data['errors']
            all_metrics[feature]['total'] += data['total']
            all_metrics[feature]['http_reqs'] += data['http_reqs']
        if summary:
            all_summaries.append(summary)

    print(f"\n📈 Features found: {', '.join(all_metrics.keys())}")

    # Generate report
    generate_report(dict(all_metrics), all_summaries)


if __name__ == '__main__':
    main()
