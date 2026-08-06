<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-brand-50/40 px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            {{-- Logo / Branding --}}
            <div class="mb-8 flex justify-center">
                <a href="/" class="flex items-center gap-3 transition-opacity hover:opacity-80 no-underline">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-white shadow-sm">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                        </svg>
                    </span>
                    <span class="text-xl font-bold tracking-tight text-slate-900">{{ config('app.name', 'HRConnect') }}</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg" x-data="{ recovery: false }">
                {{-- Card header --}}
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-base font-bold text-slate-900">{{ __('Two-Factor Authentication') }}</h1>
                            <p class="text-sm text-slate-500">{{ __('Verify your identity with a second factor.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Card body --}}
                <div class="px-6 py-5">
                    <p class="mb-5 text-sm leading-relaxed text-slate-600" x-show="! recovery">
                        {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
                    </p>
                    <p class="mb-5 text-sm leading-relaxed text-slate-600" x-cloak x-show="recovery">
                        {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
                    </p>

                    @if ($errors->any())
                        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <div class="font-medium text-red-600">{{ __('Whoops! Something went wrong.') }}</div>
                            <ul class="mt-2 list-inside list-disc text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('two-factor.login') }}">
                        @csrf

                        <div class="mb-4" x-show="! recovery">
                            <label for="code" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('Authentication Code') }}</label>
                            <input id="code" type="text" inputmode="numeric" name="code" required autofocus x-ref="code" autocomplete="one-time-code"
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 font-mono text-center text-lg tracking-[0.3em]"
                                placeholder="• • • • • •" />
                        </div>

                        <div class="mb-4" x-cloak x-show="recovery">
                            <label for="recovery_code" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('Recovery Code') }}</label>
                            <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code"
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20"
                                placeholder="{{ __('Enter recovery code') }}" />
                        </div>

                        <div class="mt-6 flex items-center justify-between gap-3">
                            <div>
                                <button type="button" x-show="! recovery"
                                    x-on:click="recovery = true; $nextTick(() => $refs.recovery_code.focus())"
                                    class="text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-2 transition-colors hover:text-brand-600 hover:decoration-brand-300">
                                    {{ __('Use a recovery code') }}
                                </button>
                                <button type="button" x-cloak x-show="recovery"
                                    x-on:click="recovery = false; $nextTick(() => $refs.code.focus())"
                                    class="text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-2 transition-colors hover:text-brand-600 hover:decoration-brand-300">
                                    {{ __('Use an authentication code') }}
                                </button>
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-[0.97]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                {{ __('Log in') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} {{ config('app.name', 'HRConnect') }}. {{ __('All rights reserved.') }}
            </p>
        </div>
    </div>
</x-guest-layout>
