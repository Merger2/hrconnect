<x-guest-layout>
    <div class="flex min-h-screen">
        <!-- Sidebar - Desktop only -->
        <div class="hidden lg:flex w-1/2 bg-primary items-center justify-center p-12 relative overflow-hidden">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-20 left-20 w-64 h-64 border-4 border-white rounded-full"></div>
                <div class="absolute bottom-20 right-20 w-48 h-48 border-4 border-white rounded-full"></div>
                <div class="absolute top-1/2 left-1/3 w-32 h-32 border-4 border-white rounded-full"></div>
            </div>

            <div class="max-w-md w-full relative z-10">
                <!-- Logo -->
                <div class="mb-10 flex items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                        <x-app-logo-icon class="size-8 fill-current text-white" />
                    </div>
                    <h1 class="text-3xl font-bold text-white">HRConnect</h1>
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
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm shrink-0">
                            <span class="material-symbols-outlined text-white text-xl">groups</span>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">Manajemen Karyawan</h3>
                            <p class="text-white/80 text-sm">Data karyawan terpusat dan terverifikasi</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm shrink-0">
                            <span class="material-symbols-outlined text-white text-xl">schedule</span>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">Kehadiran Real-time</h3>
                            <p class="text-white/80 text-sm">Tracking hadir, izin, dan lembur akurat</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm shrink-0">
                            <span class="material-symbols-outlined text-white text-xl">payments</span>
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
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12 bg-surface">
            <div class="w-full max-w-sm">
                <!-- Mobile Logo -->
                <div class="lg:hidden mb-8 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-on-primary">
                        <x-app-logo-icon class="size-5 fill-current" />
                    </div>
                    <h1 class="text-xl font-bold text-ink">HRConnect</h1>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-ink">Selamat Datang</h2>
                    <p class="mt-2 text-sm text-muted">Silakan masuk menggunakan kredensial perusahaan Anda</p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <x-forms.validation-errors class="mb-4" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ show: false }">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="email" class="block text-sm font-medium text-ink">Alamat Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded-lg border border-outline bg-surface px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="email@perusahaan.com" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="block text-sm font-medium text-ink">Kata Sandi</label>
                        <div class="relative">
                            <input id="password" :type="show ? 'text' : 'password'" name="password" required class="w-full rounded-lg border border-outline bg-surface px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary pr-10" placeholder="••••••••" />
                            <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-muted hover:text-ink" @click="show = !show">
                                <span class="material-symbols-outlined text-xl" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input type="checkbox" name="remember" class="rounded border-outline text-primary focus:ring-primary" />
                            Ingat saya
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary hover:underline">Lupa kata sandi?</a>
                        @endif
                    </div>

                    <button type="submit" class="w-full inline-flex justify-center rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-deep transition">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>