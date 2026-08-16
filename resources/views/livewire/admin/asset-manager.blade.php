<x-admin.page-shell :title="__('Asset Management')" :description="__('Track company properties, electronics, and vehicles assigned to employees.')">
    <x-slot name="actions">
        <x-actions.button wire:click="createAsset" size="icon" label="{{ __('Add Asset') }}">
            <x-heroicon-m-plus class="h-5 w-5" />
        </x-actions.button>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools>

            <div class="md:col-span-2 xl:col-span-6">
                <x-forms.label for="asset-search" value="{{ __('Search assets') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                    </span>
                    <x-forms.input id="asset-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search asset name, serial number, or user...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="typeFilter" value="{{ __('Asset Type') }}" class="mb-1.5 block" />
                <x-forms.tom-select id="typeFilter" wire:model.live="typeFilter" placeholder="{{ __('All Types') }}"
                    class="w-full">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}">{{ __(ucfirst($type)) }}</option>
                    @endforeach
                </x-forms.tom-select>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="statusFilter" value="{{ __('Status') }}" class="mb-1.5 block" />
                <x-forms.tom-select id="statusFilter" wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}"
                    class="w-full">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ \App\Models\CompanyAsset::statusLabel($status) }}</option>
                    @endforeach
                </x-forms.tom-select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div class="w-full" @unless($showAssetModal || $showHistoryModal) wire:poll.visible.10s @endunless>
        <!-- Pending OTP Return Banners -->
        @php
            $otpNotifications = auth()
                ->user()
                ->unreadNotifications->where('type', 'App\Notifications\AssetReturnOtpRequested');
        @endphp

        @if ($otpNotifications->isNotEmpty())
            <div class="mb-6 space-y-3">
                @foreach ($otpNotifications as $notif)
                    <x-admin.alert tone="warning" class="flex items-center justify-between shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="shrink-0 mt-0.5">
                                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-amber-600" />
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-amber-800">
                                    {{ __('Asset Return Request') }}
                                </h3>
                                <p class="mt-1 text-sm text-amber-700">
                                    <span
                                        class="font-semibold">{{ $notif->data['user_name'] ?? 'Unknown User' }}</span>
                                    {{ __('is requesting to return') }} <span
                                        class="font-semibold">{{ $notif->data['asset_name'] ?? 'an asset' }}</span>.
                                    {{ __('Provide them with this OTP to finalize the return:') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div
                                class="rounded-md bg-white px-4 py-2 text-lg font-mono font-bold tracking-widest text-amber-700 shadow-sm border border-amber-200">
                                {{ $notif->data['otp'] ?? '000000' }}
                            </div>
                            <!-- Button to simply dismiss notification if wanted -->
                            <x-actions.button type="button" wire:click="markNotificationAsRead('{{ $notif->id }}')"
                                variant="soft-warning" size="sm"
                                label="{{ __('Dismiss asset return request notification') }}">
                                {{ __('Dismiss') }}
                            </x-actions.button>
                        </div>
                    </x-admin.alert>
                @endforeach
            </div>
        @endif

        <x-admin.panel>
            <div class="hidden lg:block lg:overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Asset Info') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Purchase & Expiry') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Assigned To') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($companyAssets as $companyAsset)
                            <tr class="group hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $companyAsset->name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">
                                        {{ $companyAsset->serial_number ?: __('No Serial') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-admin.status-badge tone="neutral">
                                        {{ __(ucfirst($companyAsset->type)) }}
                                    </x-admin.status-badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        @if ($companyAsset->purchase_cost)
                                            <span class="text-sm font-medium text-gray-900">Rp
                                                {{ number_format($companyAsset->purchase_cost, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-xs text-gray-400 italic">{{ __('Unknown value') }}</span>
                                        @endif

                                        @if ($companyAsset->expiration_date)
                                            @if ($companyAsset->isExpired())
                                                <x-admin.status-badge tone="danger" class="max-w-fit">
                                                    {{ __('Expired') }}:
                                                    {{ \Carbon\Carbon::parse($companyAsset->expiration_date)->format('d M Y') }}
                                                </x-admin.status-badge>
                                            @elseif($companyAsset->isExpiringSoon())
                                                <x-admin.status-badge tone="warning" class="max-w-fit">
                                                    {{ __('Expiring') }}:
                                                    {{ \Carbon\Carbon::parse($companyAsset->expiration_date)->format('d M Y') }}
                                                </x-admin.status-badge>
                                            @else
                                                <x-admin.status-badge tone="success" class="max-w-fit">
                                                    {{ __('Valid till') }}:
                                                    {{ \Carbon\Carbon::parse($companyAsset->expiration_date)->format('d M Y') }}
                                                </x-admin.status-badge>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($companyAsset->user)
                                        <div class="flex items-center gap-3">
                                            <img class="h-8 w-8 rounded-full object-cover"
                                                src="{{ $companyAsset->user->profile_photo_url }}"
                                                alt="{{ $companyAsset->user->name }}" />
                                            <div>
                                                <div class="font-medium text-gray-900">
                                                    {{ $companyAsset->user->name }}</div>
                                                <div class="text-xs text-gray-500">
                                                    {{ \Carbon\Carbon::parse($companyAsset->date_assigned)->format('d M Y') }}
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">{{ __('Unassigned') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-admin.status-badge :tone="in_array($companyAsset->status, [\App\Models\CompanyAsset::STATUS_AVAILABLE])
                                        ? 'success'
                                        : (in_array($companyAsset->status, [\App\Models\CompanyAsset::STATUS_ASSIGNED, \App\Models\CompanyAsset::STATUS_SOLD, \App\Models\CompanyAsset::STATUS_AUCTIONED])
                                            ? 'info'
                                            : (in_array($companyAsset->status, [\App\Models\CompanyAsset::STATUS_LOST, \App\Models\CompanyAsset::STATUS_DISPOSED])
                                                ? 'danger'
                                                : 'warning'))">
                                        {{ $companyAsset->displayStatus() }}
                                    </x-admin.status-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="viewHistory({{ $companyAsset->id }})"
                                            variant="primary"
                                            label="{{ __('View asset history') }}: {{ $companyAsset->name }}">
                                            <x-heroicon-m-clock class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="editAsset({{ $companyAsset->id }})" variant="primary"
                                            label="{{ __('Edit asset') }}: {{ $companyAsset->name }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="confirmAssetDeletion({{ $companyAsset->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this asset?') }}"
                                            variant="danger" label="{{ __('Delete asset') }}: {{ $companyAsset->name }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-computer-desktop
                                            class="h-12 w-12 text-gray-300 mb-3" />
                                        <p class="font-medium">{{ __('No assets found in inventory') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 divide-y divide-gray-200 lg:hidden">
                @forelse($companyAssets as $companyAsset)
                    <article class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-gray-900">
                                    {{ $companyAsset->name }}
                                </h3>
                                <p class="mt-1 font-mono text-xs text-gray-500">
                                    {{ $companyAsset->serial_number ?: __('No Serial') }}
                                </p>
                            </div>
                            <x-admin.status-badge :tone="in_array($companyAsset->status, [\App\Models\CompanyAsset::STATUS_AVAILABLE])
                                ? 'success'
                                : (in_array($companyAsset->status, [\App\Models\CompanyAsset::STATUS_ASSIGNED, \App\Models\CompanyAsset::STATUS_SOLD, \App\Models\CompanyAsset::STATUS_AUCTIONED])
                                    ? 'info'
                                    : (in_array($companyAsset->status, [\App\Models\CompanyAsset::STATUS_LOST, \App\Models\CompanyAsset::STATUS_DISPOSED])
                                        ? 'danger'
                                        : 'warning'))">
                                {{ $companyAsset->displayStatus() }}
                            </x-admin.status-badge>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <dt class="font-medium text-gray-500">{{ __('Type') }}</dt>
                                <dd class="mt-1 text-gray-900">{{ __(ucfirst($companyAsset->type)) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500">{{ __('Assigned To') }}</dt>
                                <dd class="mt-1 truncate text-gray-900">
                                    {{ $companyAsset->user?->name ?: __('Unassigned') }}
                                </dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="font-medium text-gray-500">{{ __('Purchase & Expiry') }}</dt>
                                <dd class="mt-1 text-gray-900">
                                    @if ($companyAsset->purchase_cost)
                                        Rp {{ number_format($companyAsset->purchase_cost, 0, ',', '.') }}
                                    @else
                                        {{ __('Unknown value') }}
                                    @endif
                                    @if ($companyAsset->expiration_date)
                                        <span class="text-gray-500">
                                            - {{ \Carbon\Carbon::parse($companyAsset->expiration_date)->format('d M Y') }}
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 pt-3">
                            <x-actions.icon-button wire:click="viewHistory({{ $companyAsset->id }})"
                                variant="primary"
                                label="{{ __('View asset history') }}: {{ $companyAsset->name }}">
                                <x-heroicon-m-clock class="h-5 w-5" />
                            </x-actions.icon-button>
                            <x-actions.icon-button wire:click="editAsset({{ $companyAsset->id }})" variant="primary"
                                label="{{ __('Edit asset') }}: {{ $companyAsset->name }}">
                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                            </x-actions.icon-button>
                            <x-actions.icon-button wire:click="confirmAssetDeletion({{ $companyAsset->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this asset?') }}"
                                variant="danger" label="{{ __('Delete asset') }}: {{ $companyAsset->name }}">
                                <x-heroicon-m-trash class="h-5 w-5" />
                            </x-actions.icon-button>
                        </div>
                    </article>
                @empty
                    <x-admin.empty-state :title="__('No assets found in inventory')" />
                @endforelse
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-4 py-2.5">
                {{ $companyAssets->links() }}
            </div>
        </x-admin.panel>
    </div>

    <!-- Modal -->
    <x-overlays.dialog-modal wire:model.live="showAssetModal">
        <x-slot name="title">
            {{ $selectedCompanyAssetId ? __('Edit Asset') : __('Register New Asset') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="saveAsset">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="form.name" value="{{ __('Asset Name') }}" />
                            <x-forms.input id="form.name" type="text" class="mt-1 block w-full" wire:model="form.name"
                                required placeholder="{{ __('e.g. Macbook Pro M2') }}" />
                            <x-forms.input-error for="form.name" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="form.serial_number" value="{{ __('Serial Number') }}" />
                            <x-forms.input id="form.serial_number" type="text"
                                class="mt-1 block w-full font-mono text-sm" wire:model="form.serial_number"
                                placeholder="{{ __('SN-12345') }}" />
                            <x-forms.input-error for="form.serial_number" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="form.type" value="{{ __('Asset Type') }}" />
                            <x-forms.tom-select id="asset_type" wire:model="form.type"
                                placeholder="{{ __('Select Type') }}" class="mt-1">
                                <option value="electronics">{{ __('Electronics') }}</option>
                                <option value="vehicle">{{ __('Vehicle') }}</option>
                                <option value="furniture">{{ __('Furniture') }}</option>
                                <option value="uniform">{{ __('Uniform / Gear') }}</option>
                            </x-forms.tom-select>
                            <x-forms.input-error for="form.type" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="form.status" value="{{ __('Condition / Status') }}" />
                            <x-forms.tom-select id="asset_status" wire:model="form.status"
                                placeholder="{{ __('Select Status') }}" class="mt-1">
                                <option value="available">{{ __('Ready') }}</option>
                                <option value="assigned">{{ __('Assigned') }}</option>
                                <option value="maintenance">{{ __('In Maintenance') }}</option>
                                <option value="lost">{{ __('Lost / Missing') }}</option>
                                <option value="retired">{{ __('Retired') }}</option>
                                <option value="sold">{{ __('Sold') }}</option>
                                <option value="auctioned">{{ __('Auctioned') }}</option>
                                <option value="disposed">{{ __('Disposed / Scrapped') }}</option>
                            </x-forms.tom-select>
                            <x-forms.input-error for="form.status" class="mt-2" />
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">
                            {{ __('Financials & Validity') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <x-forms.label for="form.purchase_date" value="{{ __('Purchase Date') }}" />
                                <x-forms.input id="form.purchase_date" type="date" class="mt-1 block w-full"
                                    wire:model="form.purchase_date" />
                                <x-forms.input-error for="form.purchase_date" class="mt-2" />
                            </div>
                            <div>
                                <x-forms.label for="form.purchase_cost" value="{{ __('Purchase Cost') }}" />
                                <x-forms.input id="form.purchase_cost" type="number" step="0.01"
                                    class="mt-1 block w-full font-mono text-sm" wire:model="form.purchase_cost"
                                    placeholder="{{ __('5000000') }}" />
                                <x-forms.input-error for="form.purchase_cost" class="mt-2" />
                            </div>
                            <div>
                                <x-forms.label for="form.expiration_date" value="{{ __('Expiration / Warranty') }}" />
                                <x-forms.input id="form.expiration_date" type="date" class="mt-1 block w-full"
                                    wire:model="form.expiration_date" />
                                <x-forms.input-error for="form.expiration_date" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">
                            {{ __('Assignment Checkout') }}</h4>
                        <div>
                            <x-forms.label for="form.user_id" value="{{ __('Assign To Employee') }}" />
                            <x-forms.tom-select id="asset_user" wire:model="form.user_id"
                                placeholder="-- {{ __('Unassigned (Keep in Storage)') }} --" class="mt-1">
                                <option value="">-- {{ __('Unassigned (Keep in Storage)') }} --</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->employee?->nip }})
                                    </option>
                                @endforeach
                            </x-forms.tom-select>
                            <x-forms.input-error for="form.user_id" class="mt-2" />
                        </div>

                        @if ($form->user_id)
                            <div class="grid grid-cols-2 gap-4 mt-4" x-data x-transition>
                                <div>
                                    <x-forms.label for="form.date_assigned" value="{{ __('Date Assigned') }}" />
                                    <x-forms.input id="form.date_assigned" type="date" class="mt-1 block w-full"
                                        wire:model="form.date_assigned" />
                                    <x-forms.input-error for="form.date_assigned" class="mt-2" />
                                </div>
                                <div>
                                    <x-forms.label for="form.return_date" value="{{ __('Expected Return Date') }}" />
                                    <x-forms.input id="form.return_date" type="date" class="mt-1 block w-full"
                                        wire:model="form.return_date" />
                                    <x-forms.input-error for="form.return_date" class="mt-2" />
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <x-forms.label for="form.notes" value="{{ __('Notes / Specs') }}" />
                        <x-forms.textarea id="form.notes" wire:model="form.notes" rows="2" class="mt-1 block w-full"
                            placeholder="{{ __('Intel i7, 16GB RAM...') }}" />
                        <x-forms.input-error for="form.notes" class="mt-2" />
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showAssetModal', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="saveAsset" wire:loading.attr="disabled">
                {{ $selectedCompanyAssetId ? __('Update') : __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <!-- Asset History Modal -->
    <x-overlays.dialog-modal wire:model.live="showHistoryModal">
        <x-slot name="title">
            {{ __('Asset Lifecycle History') }}
        </x-slot>

        <x-slot name="content">
            @if (isset($assetHistories) && $assetHistories->isNotEmpty())
                <div class="flow-root mt-4">
                    <ul role="list" class="-mb-8">
                        @foreach ($assetHistories as $index => $history)
                            <li>
                                <div class="relative pb-8">
                                    @if (!$loop->last)
                                        <span
                                            class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                            aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3 text-sm">
                                        <div>
                                            <span
                                                class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                                {{ $history->action === 'created' ? 'bg-green-100' : '' }}
                                                {{ $history->action === 'assigned' ? 'bg-blue-100' : '' }}
                                                {{ $history->action === 'returned' ? 'bg-indigo-100' : '' }}
                                                {{ in_array($history->action, ['maintenance', 'lost', 'retired']) ? 'bg-red-100' : '' }}">
                                                @if ($history->action === 'created')
                                                    <x-heroicon-m-plus
                                                        class="h-4 w-4 text-green-600" />
                                                @elseif($history->action === 'assigned')
                                                    <x-heroicon-m-user-plus
                                                        class="h-4 w-4 text-blue-600" />
                                                @elseif($history->action === 'returned')
                                                    <x-heroicon-m-arrow-uturn-left
                                                        class="h-4 w-4 text-indigo-600" />
                                                @else
                                                    <x-heroicon-m-wrench
                                                        class="h-4 w-4 text-red-600" />
                                                @endif
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-gray-900">
                                                    <span class="font-semibold">
                                                        {{ match ($history->action) {
                                                            'created' => __('Created'),
                                                            'assigned' => __('Assigned'),
                                                            'returned' => __('Ready'),
                                                            default => \App\Models\CompanyAsset::statusLabel($history->action),
                                                        } }}
                                                    </span>
                                                    @if ($history->user)
                                                        {{ __('to / by') }} <span
                                                            class="font-medium text-gray-900">{{ $history->user->name }}</span>
                                                    @endif
                                                </p>
                                                @if ($history->notes)
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        {{ $history->notes }}</p>
                                                @endif
                                            </div>
                                            <div
                                                class="text-right text-xs whitespace-nowrap text-gray-500">
                                                <time
                                                    datetime="{{ $history->created_at?->toIso8601String() }}">{{ $history->created_at?->format('d M Y, H:i') }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="py-6 text-center text-gray-500">
                    <x-heroicon-o-clock class="h-10 w-10 text-gray-300 mx-auto mb-3" />
                    <p>{{ __('No history recorded for this asset.') }}</p>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showHistoryModal', false)">
                {{ __('Close') }}
            </x-actions.secondary-button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
