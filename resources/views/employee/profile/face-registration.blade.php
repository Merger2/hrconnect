<x-layouts::app.sidebar>
    <div class="mx-auto flex max-w-[480px] flex-col gap-4 md:max-w-3xl md:gap-6"
         x-data="{
            step: 'intro',
            faceapi: null,
            modelsLoading: false,
            cameraStream: null,
            videoReady: false,
            captureCount: 0,
            maxCaptures: 6,
            embeddings: [],
            captures: [],
            isCapturing: false,
            registrationDone: false,
            registerError: null,
            countdown: null,
            faceDetected: false,
            livenessScore: null,
            statusText: '{{ __('Siap') }}',

            async startCamera() {
                const mod = await import('face-api.js');
                this.faceapi = mod.default || mod;
                this.modelsLoading = true;
                await Promise.all([
                    this.faceapi.nets.tinyFaceDetector.loadFromUri('/models/av1'),
                    this.faceapi.nets.faceLandmark68Net.loadFromUri('/models/av1'),
                    this.faceapi.nets.faceRecognitionNet.loadFromUri('/models/av1'),
                ]);
                this.modelsLoading = false;

                if (!navigator.mediaDevices?.getUserMedia) {
                    this.statusText = '{{ __('Kamera tidak didukung') }}';
                    return;
                }
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                    });
                    this.cameraStream = stream;
                    const video = this.$refs.video;
                    video.srcObject = stream;
                    await video.play();
                    this.videoReady = true;
                    this.step = 'capture';
                    this.statusText = '{{ __('Arahkan wajah ke kamera') }}';
                    this.startDetection();
                } catch {
                    this.statusText = '{{ __('Izin kamera ditolak') }}';
                }
            },

            startDetection() {
                const detect = async () => {
                    if (!this.videoReady || this.isCapturing) { requestAnimationFrame(detect); return; }
                    try {
                        const api = this.faceapi;
                        const video = this.$refs.video;
                        const detOptions = new api.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 });
                        const d = await api.detectSingleFace(video, detOptions).withFaceLandmarks().withFaceDescriptor();
                        if (d) {
                            this.faceDetected = true;
                            this.statusText = this.captureCount < this.maxCaptures
                                ? '{{ __('Tunggu sesi berikutnya...') }}'
                                : '{{ __('Wajah terdaftar') }}';
                        } else {
                            this.faceDetected = false;
                            this.statusText = '{{ __('Arahkan wajah ke kamera') }}';
                        }
                    } catch {}
                    requestAnimationFrame(detect);
                };
                detect();
            },

            async captureFrame() {
                if (this.isCapturing || this.captureCount >= this.maxCaptures) return;
                this.isCapturing = true;
                try {
                    const api = this.faceapi;
                    const video = this.$refs.video;
                    const detOptions = new api.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 });
                    const d = await api.detectSingleFace(video, detOptions).withFaceLandmarks().withFaceDescriptor();
                    if (!d) {
                        this.statusText = '{{ __('Wajah tidak terdeteksi, coba lagi') }}';
                        this.isCapturing = false;
                        return;
                    }
                    const emb = Array.from(d.descriptor);
                    this.embeddings.push(emb);
                    this.captureCount++;

                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d', { willReadFrequently: true }).drawImage(video, 0, 0);
                    this.captures.push(canvas.toDataURL('image/jpeg', 0.85));

                    this.statusText = '{{ __('Capture ') }}' + this.captureCount + '/' + this.maxCaptures;

                    if (this.captureCount >= this.maxCaptures) {
                        const variance = this.computeVariance(this.embeddings);
                        this.livenessScore = variance;
                        if (variance > 0.5) {
                            this.step = 'register';
                            this.statusText = '{{ __('Liveness terverifikasi') }}';
                        } else {
                            this.statusText = '{{ __('Gerakan terlalu sedikit, ulangi') }}';
                            this.embeddings = [];
                            this.captureCount = 0;
                            this.captures = [];
                        }
                    }
                } catch {
                    this.statusText = '{{ __('Gagal capture') }}';
                }
                this.isCapturing = false;
            },

            computeVariance(embeddings) {
                if (!embeddings || embeddings.length < 2) return 0;
                const dims = embeddings[0].length;
                const means = new Array(dims).fill(0);
                for (const emb of embeddings) {
                    for (let i = 0; i < dims; i++) means[i] += emb[i];
                }
                for (let i = 0; i < dims; i++) means[i] /= embeddings.length;
                let variance = 0;
                for (const emb of embeddings) {
                    for (let i = 0; i < dims; i++) variance += (emb[i] - means[i]) ** 2;
                }
                return variance / embeddings.length;
            },

            async registerFace() {
                this.registerError = null;
                try {
                    const res = await fetch('/api/v1/face/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ embeddings: this.embeddings, captures: this.captures }),
                    });
                    const json = await res.json();
                    if (res.ok) {
                        this.registrationDone = true;
                        this.step = 'done';
                        Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Wajah berhasil didaftarkan') }}' });
                    } else {
                        this.registerError = json.message || '{{ __('Gagal mendaftarkan wajah') }}';
                    }
                } catch {
                    this.registerError = '{{ __('Koneksi error') }}';
                }
            },

            async resetCapture() {
                this.embeddings = [];
                this.captures = [];
                this.captureCount = 0;
                this.registrationDone = false;
                this.registerError = null;
                this.step = 'capture';
                this.statusText = '{{ __('Arahkan wajah ke kamera') }}';
            },

            destroy() {
                if (this.cameraStream) this.cameraStream.getTracks().forEach(t => t.stop());
            },
        }">

        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-brand-lavender">
                <span class="material-symbols-outlined text-brand-teal">face</span>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-ink">{{ __('Registrasi Wajah') }}</h2>
                <p class="text-sm text-on-surface-variant">{{ __('Pendaftaran Face ID untuk absensi') }}</p>
            </div>
        </div>

        {{-- Step: Intro --}}
        <div x-show="step === 'intro'"
             class="flex flex-col items-center gap-6 rounded-2xl border border-outline-variant bg-surface-container-low p-8 text-center">
            <span class="material-symbols-outlined text-7xl text-brand-teal/40">face_6</span>
            <div>
                <h3 class="text-xl font-semibold text-ink">{{ __('Registrasi Face ID') }}</h3>
                <p class="mt-2 text-sm text-on-surface-variant">
                    {{ __('Anda akan mengambil 6 foto wajah dari berbagai sudut.') }}<br>
                    {{ __('Gerakkan kepala perlahan untuk verifikasi liveness.') }}
                </p>
            </div>
            <button @click="startCamera()"
                    class="flex items-center gap-2 rounded-xl bg-ink px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-container active:scale-[0.98]">
                <span class="material-symbols-outlined">camera_alt</span>
                {{ __('Mulai Kamera') }}
            </button>
        </div>

        {{-- Loading overlay --}}
        <div x-show="modelsLoading"
             class="flex items-center justify-center rounded-2xl bg-surface-container-low py-12">
            <div class="flex flex-col items-center gap-3">
                <div class="size-8 animate-spin rounded-full border-4 border-outline-variant border-t-ink"></div>
                <p class="text-sm font-medium text-ink">{{ __('Memuat model wajah...') }}</p>
            </div>
        </div>

        {{-- Step: Capture --}}
        <div x-show="step === 'capture' && !modelsLoading"
             class="relative flex min-h-[400px] flex-col items-center justify-center overflow-hidden rounded-[2rem] bg-brand-lavender p-3 shadow-sm">
            <video x-ref="video" autoplay muted playsinline
                   class="absolute inset-0 h-full w-full object-cover"
                   :class="videoReady ? 'opacity-100' : 'opacity-0'">
            </video>

            <div x-show="!videoReady"
                 class="absolute inset-0 z-[1] flex items-center justify-center bg-secondary-fixed/50">
                <span class="material-symbols-outlined text-6xl text-brand-teal/30">face</span>
            </div>

            <div class="relative z-10 flex h-64 w-48 items-center justify-center rounded-full border-2 border-dashed border-white/70">
                <span class="material-symbols-outlined text-4xl text-white/70">face</span>
            </div>

            <div class="z-10 mb-4 mt-auto flex w-[90%] flex-col gap-2 rounded-xl border border-white/20 bg-canvas/90 px-4 py-3 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-outline">{{ __('Status') }}</p>
                        <p class="text-lg font-semibold text-ink" x-text="statusText"></p>
                    </div>
                    <span class="material-symbols-outlined text-3xl text-brand-mint" data-weight="fill" x-show="faceDetected">check_circle</span>
                    <span class="material-symbols-outlined text-3xl text-error" data-weight="fill" x-show="!faceDetected">cancel</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-on-surface-variant">{{ __('Capture') }}: <span x-text="captureCount + '/' + maxCaptures"></span></span>
                    <div class="flex gap-1">
                        <template x-for="i in maxCaptures" :key="i">
                            <div class="size-2 rounded-full"
                                 :class="i <= captureCount ? 'bg-brand-mint' : 'bg-outline-variant'">
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Capture button (visible during capture step) --}}
        <button x-show="step === 'capture' && videoReady && !modelsLoading"
                @click="captureFrame()"
                :disabled="isCapturing"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-ink px-6 py-4 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-primary-container active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
            <span class="material-symbols-outlined" x-show="!isCapturing">camera</span>
            <span x-show="isCapturing" class="inline-block size-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <span x-text="isCapturing ? '{{ __('Memproses...') }}' : (captureCount >= maxCaptures ? '{{ __('Selesai') }}' : '{{ __('Ambil Foto ') }}' + (captureCount + 1) + '/' + maxCaptures)"></span>
        </button>

        {{-- Step: Register (all captures done) --}}
        <div x-show="step === 'register'"
             class="flex flex-col gap-4 rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-3xl text-brand-mint">verified</span>
                <div>
                    <h3 class="font-semibold text-ink">{{ __('Semua foto berhasil diambil') }}</h3>
                    <p class="text-sm text-on-surface-variant">
                        {{ __('Skor liveness') }}: <span x-text="livenessScore?.toFixed(4)"></span>
                    </p>
                </div>
            </div>
            <div x-show="registerError" class="rounded-xl bg-error/10 px-4 py-3 text-sm text-error" x-text="registerError"></div>
            <div class="flex gap-3">
                <button @click="resetCapture()"
                        class="flex-1 rounded-xl border border-outline-variant px-4 py-3 text-sm font-semibold text-ink hover:bg-surface-variant">
                    {{ __('Ulang') }}
                </button>
                <button @click="registerFace()"
                        class="flex-1 rounded-xl bg-ink px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-container active:scale-[0.98]">
                    {{ __('Simpan Wajah') }}
                </button>
            </div>
        </div>

        {{-- Step: Done --}}
        <div x-show="step === 'done'"
             class="flex flex-col items-center gap-4 rounded-2xl border border-outline-variant bg-surface-container-low p-8 text-center">
            <span class="material-symbols-outlined text-6xl text-brand-mint">check_circle</span>
            <h3 class="text-xl font-semibold text-ink">{{ __('Registrasi Berhasil') }}</h3>
            <p class="text-sm text-on-surface-variant">{{ __('Wajah Anda telah terdaftar untuk absensi Face ID.') }}</p>
        </div>
    </div>
</x-layouts::app.sidebar>
