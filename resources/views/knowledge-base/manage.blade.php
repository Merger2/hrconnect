<x-layouts::app.sidebar :title="__('Manage Knowledge Base')">
    <div class="px-4 py-6 lg:px-6">
        <div class="mb-6">
            <h1 class="font-display text-2xl font-semibold text-ink">Manage Knowledge Base</h1>
            <p class="mt-1 text-sm text-muted-soft">Upload dokumen PDF untuk memperkaya basis pengetahuan AI.</p>
        </div>

        {{-- upload form --}}
        <div class="mb-8 rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="font-display text-lg font-semibold text-ink mb-4">Upload Dokumen Baru</h2>

            <form id="upload-form" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-body">Judul Dokumen</label>
                    <input type="text" id="doc-title" required
                        class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-body outline-none focus:border-ink focus:ring-1 focus:ring-ink/20 transition-colors"
                        placeholder="Contoh: Kebijakan Cuti Tahunan 2026">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-body">Kategori</label>
                    <select id="doc-category"
                        class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-body outline-none focus:border-ink focus:ring-1 focus:ring-ink/20 transition-colors">
                        <option value="general">General</option>
                        <option value="hr_policy">HR Policy</option>
                        <option value="finance">Finance</option>
                        <option value="payroll">Payroll</option>
                        <option value="attendance">Attendance</option>
                        <option value="leave">Leave</option>
                        <option value="it">IT</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-body">File PDF (maks 10MB)</label>
                    <input type="file" id="doc-file" accept="application/pdf" required
                        class="w-full text-sm text-body file:mr-4 file:rounded-xl file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:font-medium file:text-canvas hover:file:opacity-90 transition-colors">
                </div>

                <button type="submit"
                    class="rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-canvas hover:opacity-90 transition-opacity">
                    Upload & Proses
                </button>
            </form>

            <div id="upload-status" class="mt-4 hidden">
                <div class="rounded-xl border border-success/30 bg-success/5 px-4 py-3 text-sm text-body">
                    <span class="font-medium text-success">✓</span> Dokumen berhasil diupload. Embedding sedang diproses...
                </div>
            </div>
        </div>

        {{-- document list --}}
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <h2 class="font-display text-lg font-semibold text-ink mb-4">Dokumen Tersimpan</h2>

            {{-- desktop table --}}
            <div id="doc-list-table" class="hidden overflow-x-auto lg:block">
                <div class="flex items-center justify-center py-12 text-muted-soft">
                    <span class="material-symbols-outlined mr-2 text-base">hourglass_empty</span>
                    <span class="text-sm">Memuat daftar dokumen...</span>
                </div>
            </div>

            {{-- mobile cards --}}
            <div id="doc-list-cards" class="space-y-3 lg:hidden">
                <div class="flex items-center justify-center py-12 text-muted-soft">
                    <span class="material-symbols-outlined mr-2 text-base">hourglass_empty</span>
                    <span class="text-sm">Memuat daftar dokumen...</span>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        loadDocuments();

        document.getElementById('upload-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('title', document.getElementById('doc-title').value);
            formData.append('category', document.getElementById('doc-category').value);
            formData.append('file', document.getElementById('doc-file').files[0]);

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const resp = await fetch('/api/v1/knowledgebase', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + (window.Laravel?.sanctumToken || ''),
                        'X-CSRF-TOKEN': token,
                    },
                    body: formData,
                });

                const status = document.getElementById('upload-status');
                status.classList.remove('hidden');

                if (resp.ok) {
                    status.querySelector('div').className = 'rounded-xl border border-success/30 bg-success/5 px-4 py-3 text-sm text-body';
                    status.querySelector('span').textContent = '✓';
                    status.querySelector('div').childNodes[2].textContent = ' Dokumen berhasil diupload. Embedding sedang diproses...';
                    document.getElementById('doc-title').value = '';
                    document.getElementById('doc-file').value = '';
                    loadDocuments();
                } else {
                    const err = await resp.json();
                    status.querySelector('div').className = 'rounded-xl border border-error/30 bg-error/5 px-4 py-3 text-sm text-body';
                    status.querySelector('span').textContent = '✗';
                    status.querySelector('div').childNodes[2].textContent = ' ' + (err.message || 'Gagal upload dokumen.');
                }

                setTimeout(() => status.classList.add('hidden'), 5000);
            } catch (e) {
                alert('Gagal terhubung ke server.');
            }
        });
    });

    async function loadDocuments() {
        const tableContainer = document.getElementById('doc-list-table');
        const cardsContainer = document.getElementById('doc-list-cards');
        const emptyHtml = '<div class="flex items-center justify-center py-12 text-muted-soft"><span class="material-symbols-outlined mr-2 text-base">description</span><span class="text-sm">Belum ada dokumen. Upload PDF pertama Anda.</span></div>';
        const errorHtml = '<div class="flex items-center justify-center py-12 text-muted-soft"><span class="text-sm">Gagal memuat daftar dokumen.</span></div>';

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const resp = await fetch('/api/v1/knowledgebase', {
                headers: {
                    'Authorization': 'Bearer ' + (window.Laravel?.sanctumToken || ''),
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
            });

            if (!resp.ok) {
                tableContainer.innerHTML = errorHtml;
                cardsContainer.innerHTML = errorHtml;
                return;
            }

            const json = await resp.json();
            const items = json.data || [];

            if (items.length === 0) {
                tableContainer.innerHTML = emptyHtml;
                cardsContainer.innerHTML = emptyHtml;
                return;
            }

            // ── desktop table ──
            let tableHtml = '<table class="w-full text-sm"><thead><tr class="border-b border-outline-variant text-left text-xs font-semibold text-muted-soft uppercase tracking-wider">';
            tableHtml += '<th class="pb-3 pr-4">Judul</th><th class="pb-3 pr-4">Kategori</th><th class="pb-3 pr-4">Status</th><th class="pb-3 pr-4">Tanggal</th><th class="pb-3 text-right">Aksi</th>';
            tableHtml += '</tr></thead><tbody>';

            // ── mobile cards ──
            let cardsHtml = '';

            for (const doc of items) {
                const statusClass = doc.status === 'ready' ? 'bg-success/10 text-success' : doc.status === 'error' ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning';
                const statusLabel = doc.status === 'ready' ? 'READY' : doc.status === 'error' ? 'ERROR' : 'PROCESSING';

                // table row
                tableHtml += '<tr class="border-b border-outline-variant/50">';
                tableHtml += '<td class="py-3 pr-4 font-medium text-body">' + escapeHtml(doc.title || '-') + '</td>';
                tableHtml += '<td class="py-3 pr-4 text-muted-soft">' + escapeHtml(doc.category || 'general') + '</td>';
                tableHtml += '<td class="py-3 pr-4"><span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium ' + statusClass + '">' + statusLabel + '</span></td>';
                tableHtml += '<td class="py-3 pr-4 text-muted-soft">' + (doc.created_at ? new Date(doc.created_at).toLocaleDateString('id-ID') : '-') + '</td>';
                tableHtml += '<td class="py-3 text-right"><button onclick="deleteDoc(' + doc.id + ')" class="text-xs font-medium text-error hover:underline">Hapus</button></td>';
                tableHtml += '</tr>';

                // card
                cardsHtml += '<div class="user-list-card p-4">';
                cardsHtml += '  <div class="flex items-start justify-between gap-2">';
                cardsHtml += '    <div class="min-w-0 flex-1">';
                cardsHtml += '      <p class="truncate text-sm font-medium text-body">' + escapeHtml(doc.title || '-') + '</p>';
                cardsHtml += '      <p class="mt-0.5 text-xs text-muted-soft">' + escapeHtml(doc.category || 'general') + '</p>';
                cardsHtml += '    </div>';
                cardsHtml += '    <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium ' + statusClass + '">' + statusLabel + '</span>';
                cardsHtml += '  </div>';
                cardsHtml += '  <div class="mt-3 flex items-center justify-between border-t border-outline-variant/40 pt-3">';
                cardsHtml += '    <span class="text-xs text-muted-soft">' + (doc.created_at ? new Date(doc.created_at).toLocaleDateString('id-ID') : '-') + '</span>';
                cardsHtml += '    <button onclick="deleteDoc(' + doc.id + ')" class="text-xs font-medium text-error hover:underline">Hapus</button>';
                cardsHtml += '  </div>';
                cardsHtml += '</div>';
            }

            tableHtml += '</tbody></table>';
            tableContainer.innerHTML = tableHtml;
            cardsContainer.innerHTML = cardsHtml;
        } catch (e) {
            tableContainer.innerHTML = errorHtml;
            cardsContainer.innerHTML = errorHtml;
        }
    }

    async function deleteDoc(id) {
        if (!confirm('Hapus dokumen ini?')) return;

        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        try {
            const resp = await fetch('/api/v1/knowledgebase/' + id, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + (window.Laravel?.sanctumToken || ''),
                    'X-CSRF-TOKEN': token,
                },
            });

            if (resp.ok) {
                loadDocuments();
            }
        } catch (e) {
            alert('Gagal menghapus dokumen.');
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    </script>
    @endpush
</x-layouts::app.sidebar>
