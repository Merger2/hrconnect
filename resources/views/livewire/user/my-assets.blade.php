<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section
            aria-labelledby="my-assets-title"
            class="user-page-surface my-assets-page relative"
            @unless($showReturnModal) wire:poll.visible.15s @endunless
        >
            <x-user.page-header
                :back-href="route('home')"
                :title="__('My Assets')"
                title-id="my-assets-title"
                class="border-b-0">
                <x-slot name="actions">
                    <button type="button"
                        wire:click="setAssetFilter('active')"
                        @class([
                            'user-header-icon-action',
                            'bg-primary-600 text-white hover:bg-primary-700 hover:text-white' => $assetFilter === 'active',
                        ])
                        aria-label="{{ __('Active') }}"
                        title="{{ __('Active') }}">
                        <x-heroicon-o-computer-desktop class="h-5 w-5" />
                    </button>

                    <button type="button"
                        wire:click="setAssetFilter('returned')"
                        @class([
                            'user-header-icon-action',
                            'bg-primary-600 text-white hover:bg-primary-700 hover:text-white' => $assetFilter === 'returned',
                        ])
                        aria-label="{{ __('Returned') }}"
                        title="{{ __('Returned') }}">
                        <x-heroicon-o-arrow-uturn-left class="h-5 w-5" />
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <div class="mb-4">
                    <x-feedback.alert-messages />
                </div>

                <div class="asset-overview-strip" aria-label="{{ __('Asset summary') }}">
                    <button type="button"
                        wire:click="setAssetFilter('active')"
                        aria-selected="{{ $assetFilter === 'active' ? 'true' : 'false' }}"
                        class="asset-overview-strip__item">
                        <span>{{ __('Active') }}</span>
                        <strong>{{ $assets->count() }}</strong>
                    </button>
                    <button type="button"
                        wire:click="setAssetFilter('returned')"
                        aria-selected="{{ $assetFilter === 'returned' ? 'true' : 'false' }}"
                        class="asset-overview-strip__item">
                        <span>{{ __('Returned') }}</span>
                        <strong>{{ $returnedHistories->count() }}</strong>
                    </button>
                </div>

                @if ($assetFilter === 'active' && $assets->isEmpty())
                    <div class="user-empty-state">
                        <div class="user-empty-state__icon">
                            <x-heroicon-o-computer-desktop class="h-8 w-8" />
                        </div>
                        <h3 class="user-empty-state__title">{{ __('No assets assigned to you') }}</h3>
                        <p class="user-empty-state__copy">{{ __('Contact your administrator if you believe this is an error.') }}</p>
                    </div>
                @elseif ($assetFilter === 'active')
                    <div class="asset-pass-list">
                        @foreach ($assets as $asset)
                            @php
                                $isReturnable = $asset->status === 'assigned';
                                $assetName = \Illuminate\Support\Str::lower($asset->name ?? '');
                                $assetTypeMeta = match ($asset->type) {
                                    'vehicle' => [
                                        'icon' => 'heroicon-o-truck',
                                        'tone' => 'asset-pass__mark--sky',
                                    ],
                                    'furniture' => [
                                        'icon' => 'heroicon-o-building-office-2',
                                        'tone' => 'asset-pass__mark--amber',
                                    ],
                                    'uniform' => [
                                        'icon' => 'heroicon-o-shield-check',
                                        'tone' => 'asset-pass__mark--violet',
                                    ],
                                    default => [
                                        'icon' => \Illuminate\Support\Str::contains($assetName, ['iphone', 'phone', 'mobile', 'tablet', 'tab', 'ipad'])
                                            ? 'heroicon-o-device-phone-mobile'
                                            : 'heroicon-o-computer-desktop',
                                        'tone' => 'asset-pass__mark--green',
                                    ],
                                };
                                $statusTone = match ($asset->status) {
                                    'assigned' => 'success',
                                    'maintenance' => 'warning',
                                    'available' => 'info',
                                    default => 'neutral',
                                };
                                $expiryTone = $asset->expiration_date
                                    ? ($asset->isExpired()
                                        ? 'danger'
                                        : ($asset->isExpiringSoon() ? 'warning' : 'success'))
                                    : 'neutral';
                                $expiryLabel = $asset->expiration_date
                                    ? ($asset->isExpired()
                                        ? __('Expired')
                                        : ($asset->isExpiringSoon() ? __('Expiring') : __('Valid till')))
                                    : __('No expiration');
                            @endphp

                            <article class="asset-pass">
                                <div class="asset-pass__main">
                                    <div class="asset-pass__mark {{ $assetTypeMeta['tone'] }}" aria-hidden="true">
                                        <x-dynamic-component :component="$assetTypeMeta['icon']" class="h-5 w-5" />
                                    </div>

                                    <div class="asset-pass__content">
                                        <div class="asset-pass__heading">
                                            <div class="min-w-0">
                                                <h3 class="asset-pass__title">{{ $asset->name }}</h3>
                                                <p class="asset-pass__serial">{{ $asset->serial_number ?: __('No serial number') }}</p>
                                            </div>

                                            <span class="asset-pass__status asset-pass__status--{{ $statusTone }}">
                                                {{ $asset->displayStatus() }}
                                            </span>
                                        </div>

                                        <div class="asset-pass__meta">
                                            <span>
                                                <x-heroicon-o-tag class="h-4 w-4" />
                                                {{ filled($asset->type) ? __(ucfirst($asset->type)) : __('Asset') }}
                                            </span>
                                            <span>
                                                <x-heroicon-o-calendar-days class="h-4 w-4" />
                                                {{ $asset->date_assigned?->format('d M Y') ?? '—' }}
                                            </span>
                                            <span class="asset-pass__expiry asset-pass__expiry--{{ $expiryTone }}">
                                                <x-heroicon-o-shield-check class="h-4 w-4" />
                                                {{ $expiryLabel }}
                                                @if ($asset->expiration_date)
                                                    · {{ $asset->expiration_date->format('d M Y') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if ($asset->return_date || $asset->purchase_date || $asset->notes)
                                    <div class="asset-pass__details">
                                        @if ($asset->return_date)
                                            <span>{{ __('Planned Return') }}: <strong>{{ $asset->return_date->format('d M Y') }}</strong></span>
                                        @endif
                                        @if ($asset->purchase_date)
                                            <span>{{ __('Purchase Date') }}: <strong>{{ $asset->purchase_date->format('d M Y') }}</strong></span>
                                        @endif
                                        @if ($asset->notes)
                                            <span>{{ __('Note') }}: <strong>{{ $asset->notes }}</strong></span>
                                        @endif
                                    </div>
                                @endif

                                <div class="asset-pass__footer">
                                    <p>{{ __('Return request uses OTP verification and is available only for assigned assets.') }}</p>

                                    @if ($isReturnable)
                                        <button type="button"
                                            wire:click="openReturnModal('{{ $asset->id }}')"
                                            class="asset-pass__action"
                                            aria-label="{{ __('Request Return') }} {{ $asset->name }}">
                                            <x-heroicon-o-arrow-path class="h-4 w-4" />
                                            <span>{{ __('Request Return') }}</span>
                                        </button>
                                    @else
                                        <button type="button" disabled class="asset-pass__action asset-pass__action--disabled"
                                            aria-label="{{ __('Request Return') }} {{ $asset->name }}">
                                            <x-heroicon-o-arrow-path class="h-4 w-4" />
                                            <span>{{ __('Request Return') }}</span>
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @elseif ($returnedHistories->isEmpty())
                    <div class="user-empty-state">
                        <div class="user-empty-state__icon">
                            <x-heroicon-o-arrow-uturn-left class="h-8 w-8" />
                        </div>
                        <h3 class="user-empty-state__title">{{ __('No returned asset history yet.') }}</h3>
                        <p class="user-empty-state__copy">{{ __('Assets that you have already returned will appear here as history.') }}</p>
                    </div>
                @else
                    <div class="asset-pass-list">
                        @foreach ($returnedHistories as $history)
                            @php
                                $historyAssetName = \Illuminate\Support\Str::lower($history->asset?->name ?? '');
                                $historyTypeMeta = match ($history->asset?->type) {
                                    'vehicle' => [
                                        'icon' => 'heroicon-o-truck',
                                        'tone' => 'asset-pass__mark--sky',
                                    ],
                                    'furniture' => [
                                        'icon' => 'heroicon-o-building-office-2',
                                        'tone' => 'asset-pass__mark--amber',
                                    ],
                                    'uniform' => [
                                        'icon' => 'heroicon-o-shield-check',
                                        'tone' => 'asset-pass__mark--violet',
                                    ],
                                    default => [
                                        'icon' => \Illuminate\Support\Str::contains($historyAssetName, ['iphone', 'phone', 'mobile', 'tablet', 'tab', 'ipad'])
                                            ? 'heroicon-o-device-phone-mobile'
                                            : 'heroicon-o-computer-desktop',
                                        'tone' => 'asset-pass__mark--green',
                                    ],
                                };
                            @endphp

                            <article class="asset-pass">
                                <div class="asset-pass__main">
                                    <div class="asset-pass__mark {{ $historyTypeMeta['tone'] }}" aria-hidden="true">
                                        <x-dynamic-component :component="$historyTypeMeta['icon']" class="h-5 w-5" />
                                    </div>

                                    <div class="asset-pass__content">
                                        <div class="asset-pass__heading">
                                            <div class="min-w-0">
                                                <h3 class="asset-pass__title">{{ $history->asset?->name ?? __('Deleted asset record') }}</h3>
                                                <p class="asset-pass__serial">{{ $history->asset?->serial_number ?: __('No serial number') }}</p>
                                            </div>

                                            <span class="asset-pass__status asset-pass__status--info">
                                                {{ __('Returned') }}
                                            </span>
                                        </div>

                                        <div class="asset-pass__meta">
                                            <span>
                                                <x-heroicon-o-tag class="h-4 w-4" />
                                                {{ filled($history->asset?->type) ? __(ucfirst($history->asset->type)) : __('Asset') }}
                                            </span>
                                            <span>
                                                <x-heroicon-o-calendar-days class="h-4 w-4" />
                                                {{ $history->date?->format('d M Y H:i') ?? $history->created_at?->format('d M Y H:i') ?? '—' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if ($history->notes)
                                    <div class="asset-pass__details">
                                        <span>{{ __('Note') }}: <strong>{{ $history->notes }}</strong></span>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    <x-overlays.dialog-modal wire:model.live="showReturnModal">
        <x-slot name="title">
            {{ __('Confirm Asset Return') }}
        </x-slot>

        <x-slot name="content">
            <div class="asset-return-flow">
                @if ($selectedAssetName)
                    <div class="asset-return-flow__asset">
                        <span>{{ __('Asset Name') }}</span>
                        <strong>{{ $selectedAssetName }}</strong>
                    </div>
                @endif

                @if (! $otpRequested)
                    <div class="asset-return-flow__notice">
                        <x-heroicon-o-shield-check class="h-5 w-5" />
                        <p>{{ __('To return this asset, an OTP code will be sent to your immediate supervisor or the administrator. You must acquire this 6-digit code from them to confirm the handover.') }}</p>
                    </div>
                @else
                    <div class="asset-return-flow__notice asset-return-flow__notice--success">
                        <x-heroicon-o-clock class="h-5 w-5" />
                        <div>
                            <strong>{{ __('OTP expires in 15 minutes.') }}</strong>
                            <p>{{ __('An OTP code has been sent. Please contact your manager or administrator, ask for the code, and enter it below to finalize the return.') }}</p>
                        </div>
                    </div>

                    <label for="otpCode" class="asset-return-flow__otp">
                        <span>{{ __('Enter 6-Digit OTP Code') }}</span>
                        <input
                            id="otpCode"
                            type="text"
                            wire:model.live="otpCode"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            autocomplete="one-time-code"
                            placeholder="------"
                            autofocus />
                    </label>
                    <x-forms.input-error for="otpCode" class="mt-2" />
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" wire:click="closeReturnModal" wire:loading.attr="disabled" class="user-secondary-action">
                {{ __('Cancel') }}
            </button>

            @if (! $otpRequested)
                <button type="button" class="user-primary-action" wire:click="requestOtp" wire:loading.attr="disabled">
                    {{ __('Request OTP') }}
                </button>
            @else
                <button type="button" class="user-primary-action" wire:click="verifyOtp" wire:loading.attr="disabled">
                    {{ __('Confirm Return') }}
                </button>
            @endif
        </x-slot>
    </x-overlays.dialog-modal>
</div>
