<div x-data>
    <x-admin.page-shell :title="__('Payroll Adjustments')" :description="__('Manage payroll corrections and adjustments.')">
        <x-slot name="actions">
            {{-- No actions here, adjustments are made per payroll row --}}
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools>
                <div class="md:col-span-4 xl:col-span-6">
                    <x-forms.label for="payroll-search" value="{{ __('Search Payroll Records') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                            <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                        </span>
                        <x-forms.input id="payroll-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by employee name or NIP...') }}" class="w-full pl-11" />
                    </div>
                </div>

                <div class="xl:col-span-2">
                    <x-forms.label for="payroll-month" value="{{ __('Month') }}" class="mb-1.5 block" />
                    <x-forms.select id="payroll-month" wire:model.live="month" class="w-full">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">
                                {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}</option>
                        @endforeach
                    </x-forms.select>
                </div>

                <div class="xl:col-span-2">
                    <x-forms.label for="payroll-year" value="{{ __('Year') }}" class="mb-1.5 block" />
                    <x-forms.select id="payroll-year" wire:model.live="year" class="w-full">
                        @foreach (range(date('Y') - 1, date('Y') + 1) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </x-forms.select>
                </div>

            </x-admin.page-tools>
        </x-slot>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <x-admin.tables.th>{{ __('Employee') }}</x-admin.tables.th>
                                    <x-admin.tables.th>{{ __('Period') }}</x-admin.tables.th>
                                    <x-admin.tables.th>{{ __('Net Salary') }}</x-admin.tables.th>
                                    <x-admin.tables.th>{{ __('Status') }}</x-admin.tables.th>
                                    <x-admin.tables.th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Actions</span>
                                    </x-admin.tables.th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                @forelse ($payrolls as $payroll)
                                    <tr>
                                        <x-admin.tables.td>
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <img class="h-10 w-10 rounded-full object-cover" src="{{ $payroll->employee->user->profile_photo_url }}" alt="">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ $payroll->employee->full_name }}</div>
                                                    <div class="text-gray-500">{{ $payroll->employee->employee_number }}</div>
                                                </div>
                                            </div>
                                        </x-admin.tables.td>
                                        <x-admin.tables.td>{{ $payroll->period }}</x-admin.tables.td>
                                        <x-admin.tables.td>
                                            <span class="font-mono">{{ money($payroll->net_salary, 'IDR') }}</span>
                                        </x-admin.tables.td>
                                        <x-admin.tables.td>
                                            <x-admin.badge :variant="$payroll->status->color()">{{ $payroll->status->label() }}</x-admin.badge>
                                        </x-admin.tables.td>
                                        <x-admin.tables.td-actions>
                                            <x-actions.button wire:click="openAdjustmentModal({{ $payroll->id }})">
                                                {{ __('Adjust') }}
                                            </x-actions.button>
                                        </x-admin.tables.td-actions>
                                    </tr>
                                @empty
                                    <tr>
                                        <x-admin.tables.td colspan="5">
                                            <x-admin.empty-state :title="__('No Payroll Data')" :description="__('No payroll records found for the selected period.')" />
                                        </x-admin.tables.td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($payrolls->hasPages())
                        <div class="mt-4">
                            {{ $payrolls->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-16">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Recent Adjustments') }}</h2>

            <div class="mt-4 flow-root">
                <div class="inline-block min-w-full py-2 align-middle">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                         <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <x-admin.tables.th>{{ __('Employee') }}</x-admin.tables.th>
                                    <x-admin.tables.th>{{ __('Amount') }}</x-admin.tables.th>
                                    <x-admin.tables.th>{{ __('Reason') }}</x-admin.tables.th>
                                    <x-admin.tables.th>{{ __('Created By') }}</x-admin.tables.th>
                                    <x-admin.tables.th>{{ __('Date') }}</x-admin.tables.th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                @forelse ($adjustments as $adjustment)
                                    <tr>
                                        <x-admin.tables.td>{{ $adjustment->payroll->employee->full_name }}</x-admin.tables.td>
                                        <x-admin.tables.td>
                                            <span class="font-mono {{ $adjustment->kind === 'allowance' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $adjustment->kind === 'allowance' ? '+' : '-' }} {{ money($adjustment->amount, 'IDR') }}
                                            </span>
                                        </x-admin.tables.td>
                                        <x-admin.tables.td>{{ $adjustment->reason }}</x-admin.tables.td>
                                        <x-admin.tables.td>{{ $adjustment->creator->name }}</x-admin.tables.td>
                                        <x-admin.tables.td>{{ $adjustment->created_at->translatedFormat('d M Y') }}</x-admin.tables.td>
                                    </tr>
                                @empty
                                    <tr>
                                        <x-admin.tables.td colspan="5">
                                            <x-admin.empty-state :title="__('No Adjustments')" :description="__('No recent payroll adjustments found.')" />
                                        </x-admin.tables.td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
             @if ($adjustments->hasPages())
                <div class="mt-4">
                    {{ $adjustments->links(pageName: 'adjustmentsPage') }}
                </div>
            @endif
        </div>

    </x-admin.page-shell>

    {{-- Adjustment Modal --}}
    <x-admin.modal-dialog wire:model="showAdjustmentModal" max-width="lg">
        <x-slot name="title">{{ __('Add Payroll Adjustment') }}</x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                <div>
                    <x-forms.label for="adjustmentKind" value="{{ __('Adjustment Type') }}" />
                    <x-forms.select id="adjustmentKind" wire:model="adjustmentKind" class="mt-1 block w-full">
                        <option value="allowance">{{ __('Allowance (Bonus)') }}</option>
                        <option value="deduction">{{ __('Deduction (Penalty)') }}</option>
                    </x-forms.select>
                </div>
                <div>
                    <x-forms.label for="adjustmentAmount" value="{{ __('Amount') }}" />
                    <x-forms.input id="adjustmentAmount" type="number" step="1000" wire:model.defer="adjustmentAmount" class="mt-1 block w-full" />
                    <x-forms.input-error for="adjustmentAmount" class="mt-2" />
                </div>
                <div>
                    <x-forms.label for="adjustmentReason" value="{{ __('Reason') }}" />
                    <x-forms.input id="adjustmentReason" wire:model.defer="adjustmentReason" class="mt-1 block w-full" />
                    <x-forms.input-error for="adjustmentReason" class="mt-2" />
                </div>
                <div>
                    <x-forms.label for="appliedToPeriod" value="{{ __('Apply to Period (Optional)') }}" />
                    <x-forms.input id="appliedToPeriod" type="month" wire:model.defer="appliedToPeriod" class="mt-1 block w-full" />
                    <x-forms.input-error for="appliedToPeriod" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="closeAdjustmentModal">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button wire:click="saveAdjustment" class="ml-3">{{ __('Save Adjustment') }}</x-actions.button>
        </x-slot>
    </x-admin.modal-dialog>
</div>
