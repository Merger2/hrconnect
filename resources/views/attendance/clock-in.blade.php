<x-layouts::app.sidebar>
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
            gpsSamples: [],
            gpsVariance: null,
            earHistory: [],

            async init() {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('/models/av1'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('/models/av1'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('/models/av1'),
                ]);
                this.modelsLoading = false;
                this.faceStatus = '{{ __('Memindai wajah...') }}';

                if (!navigator.mediaDevices?.getUserMedia) { this.faceStatus = '{{ __('Kamera tidak didukung') }}'; return; }
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } });
                    this.cameraStream = stream;
                    this.faceStatus = '{{ __('Kamera siap') }}';
                } catch {
                    this.faceStatus = '{{ __('Izin kamera ditolak') }}';
                    return;
                }
                try {
                    const video = this.$refs.video;
                    video.srcObject = this.cameraStream;
                    await video.play();
                    this.faceStatus = '{{ __('Memindai wajah...') }}';
                    this.startDetectionLoop();
                } catch {
                    this.faceStatus = '{{ __('Gagal memutar video') }}';
                    this.cameraStream.getTracks().forEach(t => t.stop());
                    this.cameraStream = null;
                }

                if (navigator.geolocation) {
                    this.gpsSamples = [];
                    this.collectGpsSamples();
                } else { this.geoStatus = '{{ __('Tidak Ada') }}'; }
            },

            collectGpsSamples() {
                const sample = (i) => {
                    if (i >= 3) {
                        const lats = this.gpsSamples.map(s => s.latitude);
                        const lngs = this.gpsSamples.map(s => s.longitude);
                        const avgLat = lats.reduce((a, b) => a + b) / lats.length;
                        const avgLng = lngs.reduce((a, b) => a + b) / lngs.length;
                        const latVar = lats.reduce((s, v) => s + (v - avgLat) ** 2, 0) / lats.length;
                        const lngVar = lngs.reduce((s, v) => s + (v - avgLng) ** 2, 0) / lngs.length;
                        this.gpsVariance = Math.sqrt(latVar + lngVar);
                        if (this.gpsVariance < 0.000001) {
                            this.geoStatus = '{{ __('GPS Mencurigakan') }}';
                        } else {
                            this.geoStatus = '{{ __('Dalam Radius') }}';
                        }
                        this.gps = this.gpsSamples[this.gpsSamples.length - 1];
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(
                        pos => {
                            this.gpsSamples.push({
                                latitude: pos.coords.latitude,
                                longitude: pos.coords.longitude,
                                accuracy: Math.round(pos.coords.accuracy),
                            });
                            setTimeout(() => sample(i + 1), 1500);
                        },
                        () => { this.geoStatus = '{{ __('Tidak Ada') }}'; },
                        { enableHighAccuracy: true, timeout: 10000 },
                    );
                };
                sample(0);
            },

            startDetectionLoop() {
                const video = this.$refs.video;
                const detect = async () => {
                    if (!video.videoWidth) { this.detectionTimer = setTimeout(detect, 200); return; }
                    try {
                        const api = faceapi;
                        const detOptions = new api.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 });
                        const d = await api.detectAllFaces(video, detOptions).withFaceLandmarks().withFaceDescriptors();
                        if (d.length > 0) {
                            this.faceDetected = true;
                            this.faceStatus = '{{ __('Wajah Terdeteksi') }}';
                            this.lastDescriptor = Array.from(d[0].descriptor);
                            const ear = this.computeEAR(d[0].landmarks);
                            this.earHistory.push(ear);
                            if (this.earHistory.length > 10) this.earHistory.shift();
                        } else {
                            this.faceDetected = false;
                            this.faceStatus = '{{ __('Arahkan wajah ke kamera') }}';
                            this.lastDescriptor = null;
                        }
                    } catch { this.faceStatus = '{{ __('Gagal deteksi') }}'; }
                    this.detectionTimer = setTimeout(detect, 300);
                };
                this.detectionTimer = setTimeout(detect, 500);
            },

            computeEAR(landmarks) {
                const d = (a, b) => Math.sqrt((a.x - b.x) ** 2 + (a.y - b.y) ** 2);
                const leftEye = landmarks.getLeftEye();
                const rightEye = landmarks.getRightEye();
                const earLeft = (d(leftEye[1], leftEye[5]) + d(leftEye[2], leftEye[4])) / (2 * d(leftEye[0], leftEye[3]));
                const earRight = (d(rightEye[1], rightEye[5]) + d(rightEye[2], rightEye[4])) / (2 * d(rightEye[0], rightEye[3]));
                return (earLeft + earRight) / 2;
            },

            async captureFaceCrop(video) {
                const api = faceapi;
                const det = await api.detectSingleFace(video, new api.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 })).withFaceLandmarks();
                if (!det) return null;
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                if (!ctx) return null;
                const box = det.detection.box;
                const padding = 0.2;
                const x = Math.max(0, box.x - box.width * padding);
                const y = Math.max(0, box.y - box.height * padding);
                const w = Math.min(video.videoWidth - x, box.width * (1 + 2 * padding));
                const h = Math.min(video.videoHeight - y, box.height * (1 + 2 * padding));
                canvas.width = w;
                canvas.height = h;
                ctx.drawImage(video, x, y, w, h, 0, 0, w, h);
                return new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
            },

            async clockIn() {
                if (this.clockingIn || !this.lastDescriptor) return;
                this.clockingIn = true;
                try {
                    const video = this.$refs.video;
                    const payload = {
                        embedding: this.lastDescriptor,
                        latitude: this.gps.latitude,
                        longitude: this.gps.longitude,
                        accuracy: this.gps.accuracy,
                        gps_variance: this.gpsVariance,
                        is_wfa: this.wfaMode,
                        is_mocked: false,
                    };
                    if (this.wfaMode) { payload.wfa_note = '{{ __('Absen WFA via aplikasi') }}'; }

                    const cropBlob = await this.captureFaceCrop(video);
                    if (cropBlob) {
                        const reader = new FileReader();
                        payload.face_crop = await new Promise(resolve => {
                            reader.onload = () => resolve(reader.result.split(',')[1]);
                            reader.readAsDataURL(cropBlob);
                        });
                    }

                    const res = await fetch('/api/v1/attendance/clock-in', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify(payload),
                    });
                    const json = await res.json();
                    if (res.ok && json.status === 'success') {
                        Livewire.dispatch('toast', { variant: 'success', text: json.message });
                        setTimeout(() => window.location.href = '/attendance', 1500);
                    } else {
                        Livewire.dispatch('toast', { variant: 'error', text: json.message || '{{ __('Clock In gagal') }}' });
                    }
                } catch {
                    Livewire.dispatch('toast', { variant: 'error', text: '{{ __('Koneksi error') }}' });
                } finally { this.clockingIn = false; }
            },

            destroy() {
                if (this.detectionTimer) clearTimeout(this.detectionTimer);
                if (this.cameraStream) this.cameraStream.getTracks().forEach(t => t.stop());
            },
        }">

        {{-- Camera Preview Area --}}
        <section class="relative flex min-h-[360px] flex-col items-center justify-center overflow-hidden rounded-xl bg-brand-lavender p-3 shadow-sm">
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
                <div class="flex flex-col items-center gap-3 rounded-lg bg-canvas px-8 py-6 shadow-lg">
                    <div class="size-8 animate-spin rounded-full border-4 border-outline-variant border-t-ink"></div>
                    <p class="text-sm font-medium text-ink">{{ __('Memuat model wajah...') }}</p>
                </div>
            </div>
        </section>

        {{-- Location & WFA Panel --}}
        <section class="relative flex flex-col gap-4 overflow-hidden rounded-lg border border-outline-variant bg-surface-container-low p-4">
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
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-ink px-6 py-4 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-primary-container active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
            <span class="material-symbols-outlined" x-show="!clockingIn">fingerprint</span>
            <span x-show="clockingIn" class="inline-block size-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <span x-text="clockingIn ? '{{ __('Memproses...') }}' : '{{ __('Absen Sekarang') }}'"></span>
        </button>
    </div>
</x-layouts::app.sidebar>
