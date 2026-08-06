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
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg">
                {{-- Card header --}}
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-base font-bold text-slate-900">{{ __('Forgot Password') }}</h1>
                            <p class="text-sm text-slate-500">{{ __('Reset your password via email.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Card body --}}
                <div class="px-6 py-5">
                    <p class="mb-5 text-sm leading-relaxed text-slate-600">
                        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                    </p>

                    @session('status')
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3" role="status">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-emerald-800">{{ $value }}</p>
                        </div>
                    @endsession

                    @if ($errors->any())
                        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <ul class="list-inside list-disc text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('Email') }}</label>
                            <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20" />
                        </div>

                        <div class="mt-6 flex items-center justify-between gap-4">
                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 underline decoration-slate-300 underline-offset-2 transition-colors hover:text-brand-600 hover:decoration-brand-300">
                                {{ __('Back to Login') }}
                            </a>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-[0.97]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.039a2.25 2.25 0 0 1 2.134 0l7.5 4.039a2.25 2.25 0 0 1 1.183 1.98V19.5Z" />
                                </svg>
                                {{ __('Email Password Reset Link') }}
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
