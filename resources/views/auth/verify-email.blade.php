<x-guest-layout>
    <div
        x-data="{
            isVerifying: false,
            pollCount: 0,
            maxPolls: 30,
            async checkVerification() {
                if (this.pollCount >= this.maxPolls) return;
                this.pollCount++;
                try {
                    const response = await fetch('{{ route('home') }}', { method: 'HEAD', redirect: 'follow' });
                    // Check where the HEAD request actually landed after redirects
                    // If it landed on /home (not /email/verify), the user is verified
                    if (response.url && !response.url.includes('verify')) {
                        window.location.reload();
                    }
                } catch (e) { /* ignore */ }
            },
            init() {
                this.pollInterval = setInterval(() => this.checkVerification(), 4000);
                // Cleanup on page unload
                this.$nextTick(() => {
                    window.addEventListener('beforeunload', () => {
                        if (this.pollInterval) clearInterval(this.pollInterval);
                    });
                });
            }
        }"
        class="verify-email-page flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-brand-50/40 px-4 py-12 sm:px-6 lg:px-8"
    >
        <div class="w-full max-w-md">
            {{-- Logo / Branding --}}
            <div class="mb-8 flex justify-center">
                <a href="/" class="verify-email-page__brand flex items-center gap-3 transition-opacity hover:opacity-80">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-white shadow-sm">
                        <x-heroicon-o-academic-cap class="h-6 w-6" />
                    </span>
                    <span class="text-xl font-bold tracking-tight text-slate-900">{{ config('app.name', 'HRConnect') }}</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="verify-email-card overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg transition-shadow duration-200 hover:shadow-card-hover">
                {{-- Card header --}}
                <div class="verify-email-card__header border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="verify-email-card__icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600">
                            <x-heroicon-o-envelope class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-base font-bold text-slate-900">{{ __('Verify Email Address') }}</h1>
                            <p class="text-sm text-slate-500">{{ __('Almost there! Just one more step.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Card body --}}
                <div class="px-6 py-5">
                    {{-- Auto-polling indicator --}}
                    <div
                        x-show="!isVerifying && pollCount > 0"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="mb-4 flex items-center gap-2 rounded-lg border border-brand-100 bg-brand-50/60 px-3.5 py-2.5"
                        role="status"
                        aria-live="polite"
                        style="display: none;"
                    >
                        <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin text-brand-600" />
                        <span class="text-xs font-medium text-brand-800">{{ __('Awaiting verification... page will auto-refresh once confirmed.') }}</span>
                    </div>

                    {{-- Status message --}}
                    @if (session('status') == 'verification-link-sent')
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3" role="status">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                                <x-heroicon-o-check class="h-3.5 w-3.5" />
                            </div>
                            <p class="text-sm font-medium text-emerald-800">
                                {{ __('A new verification link has been sent to the email address you provided in your profile settings.') }}
                            </p>
                        </div>
                    @endif

                    {{-- Info text --}}
                    <div class="verify-email-card__content space-y-4">
                        <p class="text-sm leading-relaxed text-slate-600">
                            {{ __('Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                        </p>

                        <div class="verify-email-card__help rounded-xl bg-slate-50 px-4 py-3">
                            <p class="text-xs text-slate-500">
                                <span class="font-semibold text-slate-700">{{ __('Tip:') }}</span>
                                {{ __('Check your spam folder if you don\'t see the email.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('profile.show') }}"
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-2 transition-colors hover:text-brand-600 hover:decoration-brand-300">
                                <x-heroicon-o-pencil-square class="h-4 w-4" />
                                {{ __('Edit Profile') }}
                            </a>

                            <span class="text-slate-300">|</span>

                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-2 transition-colors hover:text-rose-600 hover:decoration-rose-300">
                                    <x-heroicon-o-lock-closed class="h-4 w-4" />
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit"
                                    class="verify-email-card__btn inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-[0.97] sm:w-auto">
                                <x-heroicon-o-arrow-path class="h-4 w-4" />
                                {{ __('Resend Verification Email') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} {{ config('app.name', 'HRConnect') }}. {{ __('All rights reserved.') }}
            </p>
        </div>
    </div>
</x-guest-layout>
