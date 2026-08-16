<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-brand-50 px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            {{-- Logo / Branding --}}
            <div class="mb-8 flex justify-center">
                <a href="/" class="flex items-center gap-3 transition-opacity hover:opacity-80 no-underline">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-white shadow-sm">
                        <x-heroicon-o-academic-cap class="h-6 w-6" />
                    </span>
                    <span class="text-xl font-bold tracking-tight text-slate-900">{{ config('app.name', 'PT Daya Cipta Mandiri Solusi') }}</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg">
                {{-- Card header --}}
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600">
                            <x-heroicon-o-lock-closed class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-base font-bold text-slate-900">{{ __('Reset Password') }}</h1>
                            <p class="text-sm text-slate-500">{{ __('Choose a new password for your account.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Card body --}}
                <div class="px-6 py-5">
                    @if ($errors->any())
                        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <ul class="list-inside list-disc text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="mb-4">
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('Email') }}</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20" />
                        </div>

                        <div class="mb-4">
                            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('New Password') }}</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password"
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20" />
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('Confirm New Password') }}</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20" />
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-4">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-[0.97]">
                                <x-heroicon-o-check-circle class="h-4 w-4" />
                                {{ __('Reset Password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} {{ config('app.name', 'PT Daya Cipta Mandiri Solusi') }}. {{ __('All rights reserved.') }}
            </p>
        </div>
    </div>
</x-guest-layout>
