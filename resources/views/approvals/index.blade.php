<x-layouts::app.sidebar :title="__('Persetujuan')">
    <div x-data="approvalsIndex('{{ auth()->user()->roles->first()?->name ?? 'employee' }}')">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink">
                    @php $role = auth()->user()->roles->first()?->name @endphp
                    {{ $role === 'employee' ? __('Pengajuan Saya') : __('Persetujuan') }}
                </h1>
                <p class="mt-1 text-sm text-on-surface-variant">
                    @php
                        $subtitle = match ($role) {
                            'super-admin', 'hr-manager' => __('Semua pengajuan yang menunggu persetujuan'),
                            'manager' => __('Pengajuan tim yang menunggu persetujuan Anda'),
                            'finance' => __('Klaim reimbursement yang menunggu persetujuan'),
                            default => __('Riwayat pengajuan Anda'),
                        };
                    @endphp
                    {{ $subtitle }}
                </p>
            </div>
        </div>

        {{-- Tabs (hidden for employee) --}}
        @php $canApprove = in_array($role, ['manager', 'hr-manager', 'super-admin', 'finance']) @endphp
        @if($canApprove)
        <div class="mb-6 flex gap-1 rounded-xl bg-surface-container-low p-1">
            <button @click="tab = 'pending'; fetchApprovals()"
                    :class="tab === 'pending' ? 'bg-canvas text-ink shadow-sm' : 'text-on-surface-variant hover:text-ink'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-all">
                {{ __('Pending') }}
                <span x-show="pendingCount > 0" class="ml-1.5 inline-flex items-center justify-center rounded-full bg-warning/20 px-1.5 text-xs font-bold text-warning" x-text="pendingCount"></span>
            </button>
            <button @click="tab = 'history'; fetchApprovals()"
                    :class="tab === 'history' ? 'bg-canvas text-ink shadow-sm' : 'text-on-surface-variant hover:text-ink'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-all">
                {{ __('History') }}
            </button>
        </div>
        @endif

        {{-- Toolbar --}}
        <x-app.panel class="mb-6">
            <div class="p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-2">
                        <x-forms.select name="type" x-model="typeFilter" @change="fetchApprovals()"
                            :options="['' => __('All Types'), 'leave' => __('Leave'), 'overtime' => __('Overtime'), 'reimbursement' => __('Reimbursement')]"
                            placeholder="Pilih..."
                            x-bind:disabled="role === 'finance'" />
                    </div>
                </div>
            </div>
        </x-app.panel>

        {{-- Summary stats --}}
        <dl class="mb-4 flex flex-wrap gap-2" x-show="!loading && tab === 'pending' && canApprove()">
            <div class="rounded-xl border border-warning/30 bg-warning/10 px-4 py-2">
                <dt class="text-xs font-semibold uppercase text-warning">{{ __('Pending') }}</dt>
                <dd class="text-lg font-bold text-warning" x-text="approvals.length">0</dd>
            </div>
        </dl>

        {{-- Loading --}}
        <div x-show="loading" class="py-16 text-center">
            <span class="material-symbols-outlined animate-spin text-3xl text-on-surface-variant/40">progress_activity</span>
            <p class="mt-2 text-sm text-on-surface-variant">{{ __('Memuat...') }}</p>
        </div>

        {{-- Content Panel --}}
        <x-app.panel x-show="!loading">
            {{-- Desktop Table --}}
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant/30 text-on-surface-variant">
                            <th class="px-4 py-3 font-semibold">{{ __('Type') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Level') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Submitted') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <template x-for="a in approvals" :key="a.approval_id">
                            <tr class="transition-colors hover:bg-surface-container-low">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-ink" x-text="typeLabel(a.approvable_type)"></span>
                                </td>
                                <td class="px-4 py-3 text-ink" x-text="a.submitter?.full_name || '{{ __('Tidak Diketahui') }}'"></td>
                                <td class="px-4 py-3">
                                    <span x-show="tab === 'pending'"
                                          class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="a.level === 1 ? 'bg-info/10 text-info ring-1 ring-inset ring-info/30' : 'bg-primary/10 text-primary ring-1 ring-inset ring-primary/30'"
                                          x-text="a.level_label || (a.level === 1 ? 'L1 {{ __('Supervisor') }}' : 'L2 {{ __('Manager') }}')"></span>
                                    <span x-show="tab === 'history'"
                                          class="text-sm text-on-surface-variant"
                                          x-text="a.level_label || (a.level === 1 ? 'L1 {{ __('Supervisor') }}' : 'L2 {{ __('Manager') }}')"></span>
                                </td>
                                <td class="px-4 py-3 text-ink" x-text="formatDate(a.submitted_at || a.created_at)"></td>
                                <td class="px-4 py-3">
                                    <template x-if="tab === 'pending'">
                                        <x-status-badge tone="warning">{{ __('Pending') }}</x-status-badge>
                                    </template>
                                    <template x-if="tab === 'history'">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                              :class="a.status === 'approved' ? 'bg-success/10 text-success ring-success/30' : 'bg-error/10 text-error ring-error/30'"
                                              x-text="a.status === 'approved' ? '{{ __('Approved') }}' : '{{ __('Rejected') }}'"></span>
                                    </template>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-button variant="ghost" size="sm" icon="open_in_new" @click="openDetail(a.approval_id)">
                                            {{ __('Detail') }}
                                        </x-button>
                                        <template x-if="tab === 'pending' && canApprove()">
                                            <>
                                                <x-button variant="secondary" size="sm" icon="close" @click="openRejectModal(a.approval_id, a.submitter?.full_name)">
                                                    {{ __('Reject') }}
                                                </x-button>
                                                <x-button variant="primary" size="sm" icon="check" @click="approve(a.approval_id)" x-bind:disabled="processing === a.approval_id">
                                                    <span x-show="processing !== a.approval_id">{{ __('Approve') }}</span>
                                                    <span x-show="processing === a.approval_id" class="material-symbols-outlined animate-spin">progress_activity</span>
                                                </x-button>
                                            </>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="approvals.length === 0 && tab === 'pending'">
                            <tr>
                                <td colspan="6">
                                    <x-empty-state :title="__('Tidak ada pending approval')" :description="__('Semua request sudah diproses')">
                                        <x-slot name="icon"><span class="material-symbols-outlined text-3xl text-on-surface-variant/50">approval</span></x-slot>
                                    </x-empty-state>
                                </td>
                            </tr>
                        </template>
                        <template x-if="approvals.length === 0 && tab === 'history'">
                            <tr>
                                <td colspan="6">
                                    <x-empty-state :title="__('Belum ada histori approval')" :description="__('Anda belum memproses approval apapun')">
                                        <x-slot name="icon"><span class="material-symbols-outlined text-3xl text-on-surface-variant/50">approval</span></x-slot>
                                    </x-empty-state>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="space-y-3 lg:hidden">
                <template x-for="a in approvals" :key="a.approval_id">
                    <article class="user-list-card p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-ink" x-text="typeLabel(a.approvable_type) + ' #' + a.approvable_id"></p>
                                <p class="mt-0.5 text-xs text-on-surface-variant" x-text="a.submitter?.full_name || '{{ __('Tidak Diketahui') }}'"></p>
                            </div>
                            <template x-if="tab === 'pending'">
                                <x-status-badge tone="warning">{{ __('Pending') }}</x-status-badge>
                            </template>
                            <template x-if="tab === 'history'">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                      :class="a.status === 'approved' ? 'bg-success/10 text-success ring-success/30' : 'bg-error/10 text-error ring-error/30'"
                                      x-text="a.status === 'approved' ? '{{ __('Approved') }}' : '{{ __('Rejected') }}'"></span>
                            </template>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <span x-text="formatDate(a.submitted_at || a.created_at)"></span>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                  :class="a.level === 1 ? 'bg-info/10 text-info' : 'bg-primary/10 text-primary'"
                                  x-text="a.level_label || (a.level === 1 ? 'L1' : 'L2')"></span>
                        </div>
                        <div class="flex gap-2">
                            <x-button variant="ghost" size="sm" @click="openDetail(a.approval_id)" icon="open_in_new" class="flex-1">{{ __('Detail') }}</x-button>
                            <template x-if="tab === 'pending' && canApprove()">
                                <>
                                    <x-button variant="secondary" size="sm" @click="openRejectModal(a.approval_id, a.submitter?.full_name)" icon="close" class="flex-1">{{ __('Reject') }}</x-button>
                                    <x-button variant="primary" size="sm" @click="approve(a.approval_id)" x-bind:disabled="processing === a.approval_id" class="flex-1">
                                        <span x-show="processing !== a.approval_id">{{ __('Approve') }}</span>
                                        <span x-show="processing === a.approval_id" class="material-symbols-outlined animate-spin">progress_activity</span>
                                    </x-button>
                                </>
                            </template>
                        </div>
                    </article>
                </template>
                <template x-if="approvals.length === 0 && tab === 'pending'">
                    <x-empty-state :title="__('Tidak ada pending approval')" />
                </template>
                <template x-if="approvals.length === 0 && tab === 'history'">
                    <x-empty-state :title="__('Belum ada histori approval')" />
                </template>
            </div>
        </x-app.panel>

        {{-- Detail Modal --}}
        <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 pt-12" @keydown.escape.window="detailModalOpen = false" @click.outside="detailModalOpen = false">
            <div class="w-full max-w-2xl rounded-lg bg-canvas p-6 shadow-xl">
                {{-- Modal header --}}
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-ink" x-text="'{{ __('Detail') }}: ' + typeLabel(detailData?.approvable?.type || '')"></h3>
                        <p class="mt-0.5 text-sm text-on-surface-variant" x-text="detailData?.submitter?.full_name ? '{{ __('By') }}: ' + detailData.submitter.full_name : ''"></p>
                    </div>
                    <button @click="detailModalOpen = false" class="rounded-lg p-1 text-on-surface-variant hover:bg-ink/5 hover:text-ink">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                {{-- Approval chain timeline --}}
                <div class="mt-6 space-y-3" x-show="detailData?.approval_chain?.length">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Approval Chain') }}</h4>
                    <template x-for="(step, i) in detailData?.approval_chain || []" :key="step.id">
                        <div class="flex items-start gap-3">
                            <div class="flex flex-col items-center">
                                <div class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                                     :class="step.status === 'approved' ? 'bg-success/10 text-success' : step.status === 'rejected' ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning'"
                                     x-text="step.status === 'approved' ? '✓' : step.status === 'rejected' ? '✗' : '○'"></div>
                                <div x-show="i < (detailData?.approval_chain?.length || 1) - 1" class="mt-1 h-6 w-0.5 bg-outline-variant/30"></div>
                            </div>
                            <div class="flex-1 pb-4">
                                <p class="text-sm font-medium text-ink" x-text="step.level_label || 'L' + step.level"></p>
                                <p class="text-xs text-on-surface-variant" x-text="step.approver?.full_name || '{{ __('Unknown') }}'"></p>
                                <p class="mt-0.5 text-xs" :class="step.status === 'approved' ? 'text-success' : step.status === 'rejected' ? 'text-error' : 'text-warning'"
                                   x-text="step.status === 'approved' ? '{{ __('Approved') }}' : step.status === 'rejected' ? '{{ __('Rejected') }}' : '{{ __('Pending') }}'"></p>
                                <p x-show="step.notes" class="mt-1 rounded-lg bg-surface-container-low px-2.5 py-1.5 text-xs text-on-surface-variant" x-text="'{{ __('Notes') }}: ' + step.notes"></p>
                                <p x-show="step.approved_at" class="mt-0.5 text-xs text-on-surface-variant" x-text="formatDateTime(step.approved_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Leave details --}}
                <div x-show="detailData?.approvable?.type === 'leave'" class="mt-6 space-y-3 rounded-xl bg-surface-container-low p-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Leave Details') }}</h4>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Leave Type') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.leave_type?.name || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Paid') }}</dt>
                            <dd x-show="detailData?.approvable?.leave_type?.is_paid" class="font-medium text-success">{{ __('Yes') }}</dd>
                            <dd x-show="!detailData?.approvable?.leave_type?.is_paid" class="font-medium text-on-surface-variant">{{ __('No') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Start Date') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.start_date || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('End Date') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.end_date || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Day Type') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.day_type_label || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Total Days') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.total_days || '-'"></dd>
                        </div>
                    </dl>
                    <div>
                        <dt class="text-xs text-on-surface-variant">{{ __('Reason') }}</dt>
                        <dd class="mt-1 text-sm text-ink" x-text="detailData?.approvable?.reason || '-'"></dd>
                    </div>
                </div>

                {{-- Overtime details --}}
                <div x-show="detailData?.approvable?.type === 'overtime'" class="mt-6 space-y-3 rounded-xl bg-surface-container-low p-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Overtime Details') }}</h4>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Date') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.date || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Total Hours') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.total_hours + ' jam' || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Start Time') }}</dt>
                            <dd class="font-medium text-ink" x-text="formatTime(detailData?.approvable?.start_time) || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('End Time') }}</dt>
                            <dd class="font-medium text-ink" x-text="formatTime(detailData?.approvable?.end_time) || '-'"></dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs text-on-surface-variant">{{ __('Amount') }}</dt>
                            <dd class="font-medium text-ink" x-text="formatCurrency(detailData?.approvable?.amount) || '-'"></dd>
                        </div>
                    </dl>
                    <div>
                        <dt class="text-xs text-on-surface-variant">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-ink" x-text="detailData?.approvable?.description || '-'"></dd>
                    </div>
                </div>

                {{-- Reimbursement details --}}
                <div x-show="detailData?.approvable?.type === 'reimbursement'" class="mt-6 space-y-3 rounded-xl bg-surface-container-low p-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Reimbursement Details') }}</h4>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Category') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.category?.name || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Title') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.title || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Amount') }}</dt>
                            <dd class="font-medium text-ink" x-text="formatCurrency(detailData?.approvable?.amount) || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">{{ __('Expense Date') }}</dt>
                            <dd class="font-medium text-ink" x-text="detailData?.approvable?.expense_date || '-'"></dd>
                        </div>
                    </dl>
                    <div>
                        <dt class="text-xs text-on-surface-variant">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-ink" x-text="detailData?.approvable?.description || '-'"></dd>
                    </div>
                </div>

                {{-- Modal footer --}}
                <div class="mt-6 flex justify-end gap-3 border-t border-outline-variant/20 pt-4">
                    <x-button variant="secondary" @click="detailModalOpen = false">{{ __('Close') }}</x-button>
                </div>
            </div>
        </div>

        {{-- Reject Modal --}}
        <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4" @keydown.escape.window="rejectModalOpen = false">
            <div class="w-full max-w-md rounded-lg bg-canvas p-6 shadow-xl" @click.outside="rejectModalOpen = false">
                <h3 class="text-lg font-semibold text-ink">{{ __('Reject Request') }}</h3>
                <p class="mt-1 text-sm text-on-surface-variant" x-text="'{{ __('Reason for') }}: ' + (rejectTargetName || '')"></p>
                <textarea x-model="rejectReason" class="mt-4 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-3 text-sm text-ink placeholder:text-on-surface-variant/50" rows="3" placeholder="{{ __('Alasan penolakan...') }}"></textarea>
                <div class="mt-6 flex justify-end gap-3">
                    <x-button variant="secondary" @click="rejectModalOpen = false">{{ __('Cancel') }}</x-button>
                    <x-button variant="danger" @click="confirmReject()" x-bind:disabled="!rejectReason || rejectReason.length < 5">{{ __('Reject') }}</x-button>
                </div>
            </div>
        </div>
    </div>


</x-layouts::app.sidebar>
