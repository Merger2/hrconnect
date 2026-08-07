<x-guest-layout>
    <div class="flex min-h-screen">
        <!-- Sidebar - Desktop only -->
        <div class="relative hidden lg:flex w-1/2 items-center justify-center overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-500 p-12">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-20 left-20 w-64 h-64 border-4 border-white rounded-full"></div>
                <div class="absolute bottom-20 right-20 w-48 h-48 border-4 border-white rounded-full"></div>
                <div class="absolute top-1/2 left-1/3 w-32 h-32 border-4 border-white rounded-full"></div>
            </div>

            {{-- Tier-A enrichment: subtle module-leave (violet) glow wash --}}
            <div class="absolute -bottom-24 -right-24 h-96 w-96 rounded-full bg-module-leave/25 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>

            <div class="max-w-md w-full relative z-10">
                <!-- Logo -->
                <div class="mb-10 flex items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-md">
                        <x-app-logo-icon class="size-8 fill-current text-brand-700" />
                    </div>
                    <h1 class="text-3xl font-bold text-white">{{ config('app.name', 'HRConnect') }}</h1>
                </div>

                <!-- Main headline -->
                <h2 class="text-5xl font-extrabold text-white leading-tight mb-6">
                    Sistem HR & Payroll Terpadu
                </h2>

                <!-- Subheadline -->
                <p class="text-xl text-white/90 mb-10 leading-relaxed">
                    Kelola data karyawan, kehadiran, dan penggajian dengan alur kerja yang efisien dan transparan.
                </p>

                <!-- Feature list with icons -->
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-md shrink-0">
                            <span class="material-symbols-outlined text-brand-700 text-xl">groups</span>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">Manajemen Karyawan</h3>
                            <p class="text-white/80 text-sm">Data karyawan terpusat dan terverifikasi</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-md shrink-0">
                            <span class="material-symbols-outlined text-brand-700 text-xl">schedule</span>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">Kehadiran Real-time</h3>
                            <p class="text-white/80 text-sm">Tracking hadir, izin, dan lembur akurat</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-md shrink-0">
                            <span class="material-symbols-outlined text-brand-700 text-xl">payments</span>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">Penggajian Otomatis</h3>
                            <p class="text-white/80 text-sm">Slip gaji dan perhitungan pajak terintegrasi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auth Form -->
        <div class="relative w-full overflow-hidden bg-white lg:w-1/2 flex items-center justify-center p-6 lg:p-12">
            {{-- Tier-A enrichment: subtle module tint washes (blue + violet) --}}
            <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-module-hr/10 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-module-leave/10 blur-3xl" aria-hidden="true"></div>

            <div class="w-full max-w-sm relative z-10">
                <!-- Mobile Logo -->
                <div class="lg:hidden mb-8 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-module-hr/10 text-module-hr">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </div>
                    <h1 class="text-xl font-bold text-slate-900">{{ config('app.name', 'HRConnect') }}</h1>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-900">Selamat Datang</h2>
                    <p class="mt-2 text-sm text-slate-500">Silakan masuk menggunakan kredensial perusahaan Anda</p>
                    <span class="mt-4 block h-1 w-12 rounded-full bg-gradient-to-r from-brand-500 to-module-leave" aria-hidden="true"></span>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <x-forms.validation-errors class="mb-4" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ show: false }">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="email" class="block text-sm font-semibold text-slate-700">Alamat Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20" placeholder="email@perusahaan.com" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="block text-sm font-semibold text-slate-700">Kata Sandi</label>
                        <div class="relative">
                            <input id="password" :type="show ? 'text' : 'password'" name="password" required class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 pr-12" placeholder="••••••••" />
                            <button type="button" class="absolute inset-y-0 right-0 px-4 flex items-center text-slate-400 hover:text-slate-700 transition-colors" @click="show = !show">
                                <span class="material-symbols-outlined text-xl" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                            Ingat saya
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline">Lupa kata sandi?</a>
                        @endif
                    </div>

                    <button type="submit" class="w-full inline-flex justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-[0.97]">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
