@props(['id' => 'feature-lock-modal'])

<div x-data="{
    show: false,
    title: '',
    message: '',
    nama: '',
    email: '',
    perusahaan: '',
    whatsapp: '',
    jumlahKaryawan: '',
    catatan: '',
    errors: {},
    touched: {},
    hwid: '{{ \App\Console\Commands\EnterpriseHwId::generate() }}',
    domain: '{{ request()->getHost() }}',
    defaultDomain: '{{ request()->getHost() }}',
    supportUrl: @js(route('enterprise-support.whatsapp')),
    messages: {
        required: @js(__('This field is required.')),
        name: @js(__('Please enter at least 2 characters.')),
        email: @js(__('Please enter a valid email address.')),
        phone: @js(__('Use a valid WhatsApp number, 8-15 digits.')),
        company: @js(__('Please enter a valid company or organization name.')),
        domain: @js(__('Use a valid domain, IP address, or localhost.')),
        employees: @js(__('Enter a valid number of employees.')),
        notes: @js(__('Notes are too long. Please keep them under 500 characters.')),
    },
    normalizeWhitespace(value) {
        return (value || '').toString().replace(/\s+/g, ' ').trim();
    },
    normalizeEmail(value) {
        return this.normalizeWhitespace(value).toLowerCase();
    },
    normalizeWhatsapp(value) {
        const cleaned = (value || '').toString().trim().replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '');

        if (cleaned.startsWith('+')) {
            return '+' + cleaned.slice(1).replace(/\D/g, '');
        }

        if (cleaned.startsWith('62')) {
            return '+62' + cleaned.slice(2).replace(/\D/g, '');
        }

        return cleaned.replace(/\D/g, '');
    },
    normalizeDomain(value) {
        let domain = this.normalizeWhitespace(value || this.defaultDomain).toLowerCase();

        domain = domain.replace(/^https?:\/\//, '').replace(/^www\./, '').split('/')[0].split('?')[0].split('#')[0].replace(/\.+$/, '');

        return domain;
    },
    normalizeEmployees(value) {
        const numeric = parseInt((value || '').toString().replace(/[^\d]/g, ''), 10);

        return Number.isFinite(numeric) ? String(numeric) : '';
    },
    isValidDomain(value) {
        const domain = (value || '').toString();
        const parts = domain.split(':');
        const host = parts[0] || '';
        const port = parts[1] || '';
        const domainPattern = /^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/;
        const ipPattern = /^(\d{1,3}\.){3}\d{1,3}$/;

        if (parts.length > 2) {
            return false;
        }

        if (port !== '') {
            const portNumber = parseInt(port, 10);

            if (!/^\d{2,5}$/.test(port) || portNumber < 1 || portNumber > 65535) {
                return false;
            }
        }

        if (host === 'localhost') {
            return true;
        }

        if (ipPattern.test(host)) {
            return host.split('.').every((octet) => {
                const number = parseInt(octet, 10);

                return number >= 0 && number <= 255;
            });
        }

        return domainPattern.test(host);
    },
    normalizeField(field) {
        if (field === 'nama') {
            this.nama = this.normalizeWhitespace(this.nama);
        } else if (field === 'email') {
            this.email = this.normalizeEmail(this.email);
        } else if (field === 'perusahaan') {
            this.perusahaan = this.normalizeWhitespace(this.perusahaan);
        } else if (field === 'whatsapp') {
            this.whatsapp = this.normalizeWhatsapp(this.whatsapp);
        } else if (field === 'domain') {
            this.domain = this.normalizeDomain(this.domain);
        } else if (field === 'jumlahKaryawan') {
            this.jumlahKaryawan = this.normalizeEmployees(this.jumlahKaryawan);
        } else if (field === 'catatan') {
            this.catatan = this.normalizeWhitespace(this.catatan);
        }
    },
    normalizeAll() {
        ['nama', 'email', 'perusahaan', 'whatsapp', 'domain', 'jumlahKaryawan', 'catatan'].forEach((field) => this.normalizeField(field));
    },
    touch(field) {
        this.touched[field] = true;
        this.normalizeField(field);
        this.validate(false);
    },
    validate(markTouched = false) {
        const nextErrors = {};
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        const phonePattern = /^\+?\d{8,15}$/;
        const normalized = {
            nama: this.normalizeWhitespace(this.nama),
            email: this.normalizeEmail(this.email),
            perusahaan: this.normalizeWhitespace(this.perusahaan),
            whatsapp: this.normalizeWhatsapp(this.whatsapp),
            domain: this.normalizeDomain(this.domain),
            jumlahKaryawan: this.normalizeEmployees(this.jumlahKaryawan),
            catatan: this.normalizeWhitespace(this.catatan),
        };
        const employees = parseInt(normalized.jumlahKaryawan, 10);

        if (markTouched) {
            ['nama', 'email', 'perusahaan', 'whatsapp', 'domain', 'jumlahKaryawan', 'catatan'].forEach((field) => {
                this.touched[field] = true;
            });
        }

        if (normalized.nama.length < 2) {
            nextErrors.nama = normalized.nama ? this.messages.name : this.messages.required;
        }

        if (!emailPattern.test(normalized.email)) {
            nextErrors.email = normalized.email ? this.messages.email : this.messages.required;
        }

        if (normalized.perusahaan.length < 2) {
            nextErrors.perusahaan = normalized.perusahaan ? this.messages.company : this.messages.required;
        }

        if (!phonePattern.test(normalized.whatsapp)) {
            nextErrors.whatsapp = normalized.whatsapp ? this.messages.phone : this.messages.required;
        }

        if (!this.isValidDomain(normalized.domain)) {
            nextErrors.domain = normalized.domain ? this.messages.domain : this.messages.required;
        }

        if (!Number.isInteger(employees) || employees < 1 || employees > 1000000) {
            nextErrors.jumlahKaryawan = normalized.jumlahKaryawan ? this.messages.employees : this.messages.required;
        }

        if (normalized.catatan.length > 500) {
            nextErrors.catatan = this.messages.notes;
        }

        this.errors = nextErrors;

        return Object.keys(nextErrors).length === 0;
    },
    showError(field) {
        return this.touched[field] && this.errors[field];
    },
    isFormValid() {
        return this.validate(false);
    },
    submitToWhatsApp() {
        this.normalizeAll();
        if (!this.validate(true)) {
            return;
        }

        const lines = [
            '*Enterprise License Request*',
            '',
            '--- Contact ---',
            'Name: ' + this.nama,
            'Email: ' + this.email,
            'Company: ' + this.perusahaan,
            'WhatsApp: ' + this.whatsapp,
            'Employees: ' + this.jumlahKaryawan,
            '',
            '--- Server Info ---',
            'Feature: ' + this.title,
            'Domain: ' + this.domain,
            'HWID: ' + this.hwid,
        ];
        if (this.catatan) {
            lines.push('');
            lines.push('Notes: ' + this.catatan);
        }
        lines.push('');
        lines.push('_Sent from admin panel_');

        const text = lines.join('\n');
        const url = this.supportUrl + '?text=' + encodeURIComponent(text);

        const a = document.createElement('a');
        a.href = url;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        this.show = false;
    }
}"
    x-on:feature-lock.window="
        show = true;
        title = $event.detail.title || @js(__('Enterprise Feature'));
        message = $event.detail.message || @js(__('This feature is available in the Enterprise Edition. Please upgrade.'));
        nama = '';
        email = '';
        perusahaan = '';
        whatsapp = '';
        jumlahKaryawan = '';
        catatan = '';
        domain = defaultDomain;
        errors = {};
        touched = {};
     "
    x-on:close-modal.window="show = false" x-show="show" style="display: none;"
    class="fixed inset-0 z-[90] overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]"
    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    <div class="fixed inset-0 z-0 transform transition-all" x-on:click="show = false" aria-hidden="true">
        <div class="absolute inset-0 z-0 bg-slate-950/70 backdrop-blur-sm dark:bg-slate-950/80"></div>
    </div>

    <div class="relative z-10 mx-auto w-full max-w-md overflow-y-auto rounded-xl bg-white shadow-xl transform transition-all dark:bg-gray-800"
        style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

        {{-- Header (compact) --}}
        <div class="px-5 py-3 bg-gradient-to-r from-red-600 to-orange-500 flex items-center gap-3">
            <div class="p-1.5 bg-white/20 rounded-full backdrop-blur-sm">
                <x-heroicon-o-lock-closed class="h-5 w-5 text-white" />
            </div>
            <div>
                <h3 class="text-sm font-bold text-white" x-text="title"></h3>
                <p class="text-xs text-white/80" x-text="message"></p>
            </div>
        </div>

        {{-- Form (compact) --}}
        <div class="px-5 py-4 space-y-3">
            <p class="sr-only">
                {{ __('Fill in your details below to request an Enterprise upgrade. We will contact you via WhatsApp.') }}
            </p>

            <div class="grid grid-cols-2 gap-3">
                {{-- Nama --}}
                <div>
                    <label
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Your Name') }}
                        <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="nama" x-on:blur="touch('nama')" x-on:input.debounce.300ms="validate(false)"
                        x-bind:aria-invalid="showError('nama') ? 'true' : 'false'"
                        x-bind:class="showError('nama') ?
                            'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : ''"
                        type="text" required minlength="2" maxlength="100" autocomplete="name"
                        placeholder="{{ __('Full name') }}"
                        class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    <p x-cloak x-show="showError('nama')" x-text="errors.nama"
                        class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"></p>
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Email') }}
                        <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="email" x-on:blur="touch('email')"
                        x-on:input.debounce.300ms="validate(false)"
                        x-bind:aria-invalid="showError('email') ? 'true' : 'false'"
                        x-bind:class="showError('email') ?
                            'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : ''"
                        type="email" required maxlength="160" autocomplete="email"
                        placeholder="{{ __('name@company.com') }}"
                        class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    <p x-cloak x-show="showError('email')" x-text="errors.email"
                        class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- Perusahaan --}}
                <div>
                    <label
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Company Name') }}
                        <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="perusahaan" x-on:blur="touch('perusahaan')"
                        x-on:input.debounce.300ms="validate(false)"
                        x-bind:aria-invalid="showError('perusahaan') ? 'true' : 'false'"
                        x-bind:class="showError('perusahaan') ?
                            'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : ''"
                        type="text" required minlength="2" maxlength="140" autocomplete="organization"
                        placeholder="{{ __('PT / CV / Organization') }}"
                        class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    <p x-cloak x-show="showError('perusahaan')" x-text="errors.perusahaan"
                        class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"></p>
                </div>

                {{-- WhatsApp --}}
                <div>
                    <label
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('WhatsApp Contact') }}
                        <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="whatsapp" x-on:blur="touch('whatsapp')"
                        x-on:input.debounce.300ms="validate(false)"
                        x-bind:aria-invalid="showError('whatsapp') ? 'true' : 'false'"
                        x-bind:class="showError('whatsapp') ?
                            'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : ''"
                        type="tel" required inputmode="tel" autocomplete="tel"
                        placeholder="{{ __('08xxxxxxxxxx') }}"
                        class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    <p x-cloak x-show="showError('whatsapp')" x-text="errors.whatsapp"
                        class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- Domain (editable) --}}
                <div>
                    <label
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Domain') }}</label>
                    <x-forms.input x-model="domain" x-on:blur="touch('domain')"
                        x-on:input.debounce.300ms="validate(false)"
                        x-bind:aria-invalid="showError('domain') ? 'true' : 'false'"
                        x-bind:class="showError('domain') ?
                            'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : ''"
                        type="text" required maxlength="253" inputmode="url" autocomplete="url"
                        placeholder="{{ __('example.com') }}"
                        class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    <p x-cloak x-show="showError('domain')" x-text="errors.domain"
                        class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"></p>
                </div>

                {{-- Jumlah Karyawan --}}
                <div>
                    <label
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Number of Employees') }}
                        <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="jumlahKaryawan" x-on:blur="touch('jumlahKaryawan')"
                        x-on:input.debounce.300ms="validate(false)"
                        x-bind:aria-invalid="showError('jumlahKaryawan') ? 'true' : 'false'"
                        x-bind:class="showError('jumlahKaryawan') ?
                            'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : ''"
                        type="number" required min="1" max="1000000" inputmode="numeric"
                        placeholder="{{ __('50') }}"
                        class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    <p x-cloak x-show="showError('jumlahKaryawan')" x-text="errors.jumlahKaryawan"
                        class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"></p>
                </div>
            </div>

            {{-- HWID (readonly, full width) --}}
            <div>
                <label
                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Server HWID') }}</label>
                <x-forms.input x-model="hwid" type="text" readonly
                    class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 px-2.5 font-mono text-xs dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
            </div>

            {{-- Catatan --}}
            <div>
                <label
                    class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Notes (optional)') }}</label>
                <x-forms.textarea x-model="catatan" x-on:blur="touch('catatan')"
                    x-on:input.debounce.300ms="validate(false)"
                    x-bind:aria-invalid="showError('catatan') ? 'true' : 'false'"
                    x-bind:class="showError('catatan') ?
                        'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : ''"
                    rows="2" maxlength="500" placeholder="{{ __('Additional requirements or questions...') }}"
                    class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                <div class="mt-1 flex items-center justify-between gap-2">
                    <p x-cloak x-show="showError('catatan')" x-text="errors.catatan"
                        class="text-[11px] font-medium text-red-600 dark:text-red-400"></p>
                    <p class="ml-auto text-[11px] text-gray-400 dark:text-gray-500"><span
                            x-text="catatan.length"></span>/500</p>
                </div>
            </div>

            {{-- Unlocks Info (compact) --}}
            <div class="p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                <p class="font-semibold text-gray-800 dark:text-gray-200 text-xs mb-1">🚀
                    {{ __('Enterprise unlocks:') }}</p>
                <div class="grid grid-cols-2 gap-x-2 gap-y-0.5 text-[11px] text-gray-500 dark:text-gray-400 ml-1">
                    <span>• {{ __('Payroll Generation & Payslips') }}</span>
                    <span>• {{ __('Cash Advance / Kasbon Flow') }}</span>
                    <span>• {{ __('KPI & Performance Appraisals') }}</span>
                    <span>• {{ __('Company Asset Management') }}</span>
                    <span>• {{ __('Import / Export Jobs') }}</span>
                    <span>• {{ __('Monthly PDF Attendance Reports') }}</span>
                    <span>• {{ __('Advanced Analytics Dashboard') }}</span>
                    <span>• {{ __('Audit Trails & Security Logs') }}</span>
                    <span>• {{ __('Face ID Biometric Enforcement') }}</span>
                    <span>• {{ __('System Backup & Maintenance') }}</span>
                </div>
            </div>
        </div>

        {{-- Footer (compact) --}}
        <div
            class="flex flex-row justify-end gap-2 px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700">
            <button x-on:click="show = false" type="button"
                class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                {{ __('Close') }}
            </button>
            <button x-on:click="submitToWhatsApp()" type="button" title="{{ __('Send via WhatsApp') }}"
                x-bind:disabled="!isFormValid()"
                x-bind:class="!isFormValid() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-600'"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest active:bg-green-700 transition">
                <x-heroicon-o-chat-bubble-left-ellipsis class="h-3.5 w-3.5" />
                {{ __('Send') }}
            </button>
        </div>
    </div>
</div>
