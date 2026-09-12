/**
 * K6 Suite 7: KB Chat
 *
 * Tests: Knowledge Base RAG chat (external Gemini AI latency).
 * Tag: feature=kb-chat
 *
 * NOTE: This suite tests AI inference latency which is EXTERNAL.
 *       Thresholds are much more lenient than other suites.
 *       Run separately from core suites.
 *
 * Run:
 *   k6 run --out json=tests/performance/results/kb-chat.json tests/performance/suites/kb-chat.js
 */

import { sleep, group } from 'k6';
import { apiGet, apiPost, THINK_TIME } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 2;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    kb_chat_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    // AI inference is EXTERNAL (Gemini RAG) — threshold mengikuti SLA nyata
    // terukur (run 2026-09-06/07: RAG p95 ≈ 12–17s). Threshold lama p(95)<10000
    // tidak pernah tereksekusi karena tag {feature:kb-chat} vacuous (nama step
    // kb_chat_* menghasilkan tag feature=kb) — di-fix via featureTag() di helpers.
    'http_req_duration{feature:kb-chat}': ['p(95)<20000', 'p(99)<45000'],
    'http_req_failed{feature:kb-chat}': ['rate<0.10'],
    checks: ['rate>0.90'],
  },
};

export default function () {
  // ── 1. KB index (read entries, no AI) ──
  group('kb-chat: index', () => {
    apiGet('/knowledge-base', 'kb_chat_index', 'employee');
  });
  sleep(THINK_TIME);

  // ── 2. KB detail (single entry) ──
  group('kb-chat: detail', () => {
    apiGet('/knowledge-base/1', 'kb_chat_detail', 'employee');
  });
  sleep(THINK_TIME);

  // ── 3. KB chat (RAG query — Gemini AI) ──
  group('kb-chat: rag query', () => {
    apiPost('/knowledge-base/chat', {
      message: 'Apa kebijakan cuti tahunan di perusahaan?',
    }, 'kb_chat_rag_query', 'employee');
  });
  sleep(THINK_TIME * 2); // AI can be slow

  // ── 4. KB chat with follow-up ──
  group('kb-chat: rag follow-up', () => {
    apiPost('/knowledge-base/chat', {
      message: 'Berapa hari cuti yang diperbolehkan?',
    }, 'kb_chat_rag_followup', 'employee');
  });
  sleep(THINK_TIME * 2);

  // ── 5. KB chat (different topic) ──
  group('kb-chat: rag topic shift', () => {
    apiPost('/knowledge-base/chat', {
      message: 'Bagaimana proses pengajuan reimbursement?',
    }, 'kb_chat_rag_topic', 'employee');
  });
  sleep(THINK_TIME * 3);

  sleep(THINK_TIME * 2);
}
