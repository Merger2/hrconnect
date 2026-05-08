# Notion Import Guide — HRConnect

## Cara Import Dokumen ke Notion

### Metode 1: Copy-Paste Langsung (Recommended)

1. Buka file `.md` di code editor
2. **Select All** (`Ctrl+A` / `Cmd+A`)
3. **Copy** (`Ctrl+C` / `Cmd+C`)
4. Di Notion, buat **page baru**
5. **Paste** (`Ctrl+V` / `Cmd+V`)

Notion otomatis convert markdown ke format Notion:
- `# Heading 1` → Heading 1
- `## Heading 2` → Heading 2
- `**bold**` → Bold
- `*italic*` → Italic
- `- list` → Bullet list
- `1. list` → Numbered list
- `| table |` → Table
- `---` → Divider
- `` `code` `` → Inline code
- ` ``` ` → Code block

### Metode 2: Import File

1. Di Notion: **Import** → **Markdown & CSV**
2. Pilih file `.md`
3. Notion convert otomatis

> ⚠️ **Note:** Import file kadang tidak sempurna untuk tabel kompleks. Copy-paste langsung lebih reliable.

---

## Tips Per Dokumen

| Dokumen | Cara Import Terbaik |
|---------|---------------------|
| **PRD.md** | Copy-paste langsung ke 1 page Notion |
| **ERD.md** | Copy Mermaid code → paste ke [mermaid.live](https://mermaid.live) → export PNG → upload ke Notion |
| **Class Diagram.md** | Sama seperti ERD — render dulu di Mermaid Live |
| **Sequence Diagrams.md** | Render di Mermaid Live, upload gambar |
| **API Contracts.md** | Copy-paste langsung — tabel dan JSON blocks paste rapi |
| **Execution Schedule.md** | Copy-paste langsung — tabel otomatis jadi Notion table |
| **Sprint Branch Strategy.md** | Copy-paste langsung |
| **Notion Kanban.md** | Copy isi card → paste sebagai property di Notion database |
| **Wireframes.md** | Copy-paste — ASCII diagrams tetap readable |

---

## Setup Notion Database (untuk Kanban)

1. Buat **Database baru** → pilih **Board View**
2. Buat **Properties:**

| Property | Type | Values |
|----------|------|--------|
| Task | Title | — |
| Fase | Select | `Fase 1-Foundation`, `Fase 2-Attendance`, `Fase 3-Leave`, `Fase 4-Finance`, `Fase 5-Profile`, `Fase 6-HRD Admin`, `Fase 7-Finance Admin`, `Fase 8-KnowledgeBase`, `Fase 9-Settings`, `Fase 10-Testing`, `Fase 11-Polish` |
| Kategori | Select | `Model`, `Migration`, `Seeder`, `Factory`, `Service`, `Observer`, `Job`, `Command`, `Notification`, `Livewire`, `View`, `JS`, `Route`, `Policy`, `Request`, `Middleware`, `Test`, `Lang`, `Config`, `PWA` |
| Status | Select | `Backlog`, `In Progress`, `Done`, `Blocked` |
| Priority | Select | `High`, `Medium`, `Low` |
| Estimasi | Select | `0.5 jam`, `1 jam`, `2 jam`, `3 jam`, `4 jam+` |
| File Path | Text | — |
| Notes | Text | — |

3. **Group by:** `Fase`
4. Copy-paste card dari `planning/notion-kanban.md`

---

## Common Issues & Fixes

| Masalah | Solusi |
|---------|--------|
| Tabel tidak rapi saat paste | Pastikan semua baris tabel punya jumlah kolom yang sama |
| Code block tidak terdeteksi | Tambahkan spasi kosong sebelum dan sesudah code block |
| Heading tidak muncul | Pastikan `#` ada di awal baris tanpa spasi |
| Emoji tidak tampil | Notion support emoji — pastikan format UTF-8 |
| List tidak indent | Gunakan 2 spasi per level indent |

---

## Diagram Rendering (Mermaid → Gambar)

Untuk semua file di `architecture/` yang berisi Mermaid diagram:

1. Buka [mermaid.live](https://mermaid.live)
2. Copy code Mermaid dari file `.md` (antara ` ```mermaid ` dan ` ``` `)
3. Paste di mermaid.live
4. Export sebagai PNG/SVG
5. Upload ke Notion page

File yang perlu di-render:
- `architecture/erd.md`
- `architecture/class-diagram.md`
- `architecture/sequence-diagrams.md`
- `architecture/activity-diagrams.md`
- `architecture/data-flow-diagram.md`
- `architecture/deployment-diagram.md`
- `architecture/use-case-diagram.md`

---

> Last Updated: 2026-05-08
