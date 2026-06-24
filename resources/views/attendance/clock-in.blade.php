op<x-layouts::app.sidebar>
    {{-- Load face-api.js from CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <div class="mx-auto flex max-w-[480px] flex-col gap-4 md:max-w-3xl md:gap-6"
         x-data="{
            faceDetected: false,
            faceStatus: '{{ __('Memulai kamera...') }}',
            modelsLoading: true,
            clockingIn: false,
            wfaMode: false,
            locationName: '{{ __('Mendapatkan lokasi...') }}',
            geoStatus: '{{ __('Mendeteksi...') }}',
            cameraStream: null,
            detectionTimer: null,
            lastDescriptor: null,
            gps: { latitude: null, longitude: null, accuracy: null },

            async init() {
                if (typeof faceapi === 'undefined') {
                    await new Promise(r => { const c = () => { if (typeof faceapi !== 'undefined') r(); else setTimeout(c, 100); }; c(); });
                }
                await faceapi.nets.ssdMobilenetv1.loadFromUri('/models');
                await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
                await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
                this.modelsLoading = false;
                this.faceStatus = '{{ __('Memindai wajah...') }}';

                if (!navigator.mediaDevices?.getUserMedia) { this.faceStatus = '{{ __('Kamera tidak didukung') }}'; return; }
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } });
                    this.cameraStream = stream;
                    const video = this.$refs.video;
                    video.srcObject = stream;
                    await video.play();
                    this.faceStatus = '{{ __('Memindai wajah...') }}';
                    this.startDetectionLoop();
                } catch { this.faceStatus = '{{ __('Izin kamera ditolak') }}'; }

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        pos => { this.gps = { latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: Math.round(pos.coords.accuracy) }; this.geoStatus = '{{ __('Dalam Radius') }}'; },
                        () => { this.geoStatus = '{{ __('Tidak Ada') }}'; },
                        { enableHighAccuracy: true, timeout: 10000 },
                    );
                } else { this.geoStatus = '{{ __('Tidak Ada') }}'; }
            },

            startDetectionLoop() {
                const video = this.$refs.video;
                const detect = async () => {
                    if (!video.videoWidth) { this.detectionTimer = setTimeout(detect, 200); return; }
                    try {
                        const d = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 })).withFaceLandmarks().withFaceDescriptors();
                        if (d.length > 0) { this.faceDetected = true; this.faceStatus = '{{ __('Wajah Terdeteksi') }}'; this.lastDescriptor = Array.from(d[0].descriptor); }
                        else { this.faceDetected = false; this.faceStatus = '{{ __('Arahkan wajah ke kamera') }}'; this.lastDescriptor = null; }
                    } catch {}
                    this.detectionTimer = setTimeout(detect, 300);
                };
                this.detectionTimer = setTimeout(detect, 500);
            },

            async clockIn() {
                if (this.clockingIn || !this.lastDescriptor) return;
                this.clockingIn = true;
                try {
                    const payload = { embedding: this.lastDescriptor, latitude: this.gps.latitude, longitude: this.gps.longitude, accuracy: this.gps.accuracy, is_wfa: this.wfaMode, is_mocked: false };
                    if (this.wfaMode) { payload.wfa_note = '{{ __('Absen WFA via aplikasi') }}'; }
                    const res = await fetch('/api/v1/attendance/clock-in', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify(payload),
                    });
                    const json = await res.json();
                    if (res.ok && json.status === 'success') {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { variant: 'success', text: json.message } }));
                        setTimeout(() => window.location.href = '/attendance', 1500);
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { variant: 'error', text: json.message || '{{ __('Clock In gagal') }}' } }));
                    }
                } catch {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { variant: 'error', text: '{{ __('Koneksi error') }}' } }));
                } finally { this.clockingIn = false; }
            },

            destroy() {
                if (this.detectionTimer) clearTimeout(this.detectionTimer);
                if (this.cameraStream) this.cameraStream.getTracks().forEach(t => t.stop());
            },
        }">

        {{-- Camera Preview Area --}}
        <section class="relative flex min-h-[360px] flex-col items-center justify-center overflow-hidden rounded-[2rem] bg-brand-lavender p-3 shadow-sm">
            {{-- Video feed — always visible, never {display:none} for iOS --}}
            <video x-ref="video" autoplay muted playsinline
                   class="absolute inset-0 h-full w-full object-cover">
            </video>

            {{-- Placeholder — fades out when camera starts --}}
            <div x-ref="placeholder"
                 class="absolute inset-0 z-[1] flex items-center justify-center bg-secondary-fixed/50 transition-opacity duration-500"
                 :class="cameraStream ? 'opacity-0 pointer-events-none' : 'opacity-100'">
                <span class="material-symbols-outlined text-6xl text-brand-teal/30">face</span>
            </div>

            <div class="relative z-10 flex h-64 w-48 items-center justify-center rounded-full border-2 border-dashed border-white/70">
                <div class="absolute -right-4 -top-4 flex size-8 items-center justify-center rounded-full bg-brand-mint shadow-md">
                    <span class="material-symbols-outlined text-sm text-white" data-weight="fill">face</span>
                </div>
            </div>

            <div class="z-10 mb-4 mt-auto flex w-[90%] items-center justify-between rounded-xl border border-white/20 bg-canvas/90 px-4 py-3 backdrop-blur-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-outline">{{ __('Status Face-ID') }}</p>
                    <p class="text-lg font-semibold text-ink" x-text="faceStatus"></p>
                </div>
                <span class="material-symbols-outlined text-3xl text-brand-mint" data-weight="fill" x-show="faceDetected">check_circle</span>
                <span class="material-symbols-outlined text-3xl text-error" data-weight="fill" x-show="!faceDetected" x-cloak>cancel</span>
            </div>

            {{-- Loading overlay while models load --}}
            <div x-show="modelsLoading" class="absolute inset-0 z-20 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                <div class="flex flex-col items-center gap-3 rounded-2xl bg-canvas px-8 py-6 shadow-lg">
                    <div class="size-8 animate-spin rounded-full border-4 border-outline-variant border-t-ink"></div>
                    <p class="text-sm font-medium text-ink">{{ __('Memuat model wajah...') }}</p>
                </div>
            </div>
        </section>

        {{-- Location & WFA Panel --}}
        <section class="relative flex flex-col gap-4 overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-low p-4">
            <div class="pointer-events-none absolute -bottom-8 -right-8 opacity-20">
                <span class="material-symbols-outlined text-8xl text-brand-teal">map</span>
            </div>

            <div class="z-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-brand-teal">
                        <span class="material-symbols-outlined text-white">location_on</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-ink">{{ __('Lokasi Saat Ini') }}</h3>
                        <p class="text-sm text-on-surface-variant" x-text="locationName"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 rounded-full border border-brand-mint/30 bg-brand-mint/20 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-brand-teal">
                    <div class="size-2 animate-pulse rounded-full bg-brand-mint"></div>
                    <span x-text="geoStatus"></span>
                </div>
            </div>

            <div class="z-10 h-px w-full bg-outline-variant/50"></div>

            <div class="z-10 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-ink">{{ __('Mode WFA') }}</h3>
                    <p class="text-sm text-on-surface-variant">{{ __('Aktifkan untuk absen di luar area') }}</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="checkbox" class="peer sr-only" x-model="wfaMode" />
                    <div class="peer h-6 w-11 rounded-full bg-surface-variant after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-outline-variant after:bg-white after:transition-all peer-checked:bg-brand-ochre peer-checked:after:translate-x-full"></div>
                </label>
            </div>
        </section>

        {{-- Clock In Button --}}
        <button @click="clockIn()"
                :disabled="clockingIn || !faceDetected"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-ink px-6 py-4 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[#1f1f1f] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
            <span class="material-symbols-outlined" x-show="!clockingIn">fingerprint</span>
            <span x-show="clockingIn" class="inline-block size-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <span x-text="clockingIn ? '{{ __('Memproses...') }}' : '{{ __('Clock In Sekarang') }}'"></span>
        </button>
    </div>
</x-layouts::app.sidebar>
