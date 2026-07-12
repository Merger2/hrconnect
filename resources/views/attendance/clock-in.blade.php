<x-layouts::app.sidebar :title="__('Absen')">
    <div class="mx-auto flex max-w-[480px] flex-col gap-5 md:max-w-3xl md:gap-6"
         x-data="clockIn()" x-init="init()">

        {{-- Camera View --}}
        <section class="relative overflow-hidden rounded-2xl bg-surface-dim" aria-label="{{ __('Kamera Absensi') }}">
            <video x-ref="video" autoplay playsinline muted class="block w-full" style="aspect-ratio: 4/3;"></video>
            <canvas x-ref="overlay" class="absolute inset-0 h-full w-full"></canvas>

            {{-- Initial placeholder --}}
            <div x-ref="placeholder" x-show="status === 'loading-models' || status === 'opening-camera'"
                 class="absolute inset-0 z-[1] flex items-center justify-center bg-surface-container-low/80 transition-opacity">
                <div class="flex flex-col items-center gap-3">
                    <div class="size-8 animate-spin rounded-full border-4 border-outline-variant border-t-ink"></div>
                    <p class="text-sm font-medium text-ink" x-text="statusMessage"></p>
                </div>
            </div>
        </section>

        {{-- Status pill + hint --}}
        <div class="flex flex-col items-center gap-2">
            <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium" role="status" aria-live="polite"
                :class="{
                    'bg-success/10 text-success': status === 'ready-to-capture',
                    'bg-warning/10 text-warning ring-1 ring-warning/30': ['turn-face', 'arming-liveness', 'recenter-face'].includes(status),
                    'bg-error/10 text-error': status === 'error',
                    'bg-surface-container text-on-surface-variant': !['ready-to-capture', 'turn-face', 'arming-liveness', 'recenter-face', 'error'].includes(status)
                }">
                <span x-show="showSpinner()" class="material-symbols-outlined animate-spin text-base">sync</span>
                <span x-text="statusMessage"></span>
            </div>
            <p class="text-xs text-on-surface-variant" x-text="hintMessage"></p>
        </div>

        {{-- Location & WFA Panel --}}
        <section class="relative flex flex-col gap-4 overflow-hidden rounded-xl border border-outline-variant bg-surface-container-low p-4">
            <div class="pointer-events-none absolute -bottom-8 -right-8 opacity-15">
                <span class="material-symbols-outlined text-8xl text-ink">map</span>
            </div>

            <div class="z-10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-surface-container text-on-surface-variant">
                        <span class="material-symbols-outlined">location_on</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-ink">{{ __('Lokasi Saat Ini') }}</h3>
                        <p class="text-sm text-on-surface-variant" x-text="locationName"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 rounded-full border border-outline-variant bg-surface-container px-3 py-1 text-xs font-semibold uppercase">
                    <div class="size-2 rounded-full" :class="geoStatus !== '{{ __('Tidak Ada') }}' && geoStatus !== '{{ __('Mendeteksi...') }}' ? 'animate-pulse bg-success' : 'bg-on-surface-variant/40'"></div>
                    <span x-text="geoStatus" class="text-on-surface-variant"></span>
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
                    <div class="peer h-6 w-11 rounded-full bg-surface-variant after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-outline-variant after:bg-white after:transition-all peer-checked:bg-ink peer-checked:after:translate-x-full"></div>
                </label>
            </div>
        </section>

        {{-- Clock In Button --}}
        <button @click="doClockIn()"
                :disabled="clockingIn || !canCapture()"
                :class="canCapture() ? 'bg-ink text-white hover:bg-ink/90' : 'bg-surface-container text-on-surface-variant cursor-not-allowed'"
                class="flex w-full items-center justify-center gap-2 rounded-xl px-6 py-4 text-sm font-semibold shadow-sm transition active:scale-[0.98]">
            <span x-show="!clockingIn" class="material-symbols-outlined">fingerprint</span>
            <span x-show="clockingIn" class="inline-block size-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <span x-text="clockingIn ? '{{ __('Memproses...') }}' : (status === 'ready-to-capture' ? '{{ __('Absen Sekarang') }}' : '{{ __('Verifikasi wajah...') }}')"></span>
        </button>

        {{-- Hint for face --}}
        <div x-show="status !== 'ready-to-capture' && status !== 'saving' && status !== 'error' && status !== 'loading-models' && status !== 'opening-camera'"
             class="flex items-center justify-center gap-2 rounded-xl bg-warning/5 px-4 py-2.5 text-sm text-on-surface-variant">
            <span class="material-symbols-outlined text-lg">tips_and_updates</span>
            <span>{{ __('Posisikan wajah di dalam panduan, lalu kedipkan mata saat diminta') }}</span>
        </div>
    </div>
</x-layouts::app.sidebar>

<script>
    function pointDistance(a, b) {
        return Math.hypot(a.x - b.x, a.y - b.y);
    }

    function averagePoint(points) {
        const total = points.reduce((carry, point) => ({ x: carry.x + point.x, y: carry.y + point.y }), { x: 0, y: 0 });
        return { x: total.x / points.length, y: total.y / points.length };
    }

    function eyeAspectRatio(eye) {
        const vA = pointDistance(eye[1], eye[5]);
        const vB = pointDistance(eye[2], eye[4]);
        const h = pointDistance(eye[0], eye[3]);
        return h ? (vA + vB) / (2 * h) : 0;
    }

    function buildFaceGeometryDescriptor(landmarks) {
        const leftEyeCenter = averagePoint(landmarks.getLeftEye());
        const rightEyeCenter = averagePoint(landmarks.getRightEye());
        const eyeMidX = (leftEyeCenter.x + rightEyeCenter.x) / 2;
        const eyeMidY = (leftEyeCenter.y + rightEyeCenter.y) / 2;
        const eyeDistance = Math.max(pointDistance(leftEyeCenter, rightEyeCenter), 1);
        const roll = Math.atan2(rightEyeCenter.y - leftEyeCenter.y, rightEyeCenter.x - leftEyeCenter.x);
        const cos = Math.cos(-roll);
        const sin = Math.sin(-roll);
        const excluded = new Set([0, 1, 15, 16]);
        const descriptor = [2];

        landmarks.positions.forEach((point, index) => {
            if (excluded.has(index)) return;
            const tx = (point.x - eyeMidX) / eyeDistance;
            const ty = (point.y - eyeMidY) / eyeDistance;
            descriptor.push(Number((tx * cos - ty * sin).toFixed(6)));
            descriptor.push(Number((tx * sin + ty * cos).toFixed(6)));
        });

        return descriptor;
    }

    function clockIn() {
        const modelUrl = '/models/av1';
        const previewOptions = { inputSize: 160, scoreThreshold: 0.5 };
        const captureOptions = [
            { inputSize: 224, scoreThreshold: 0.3 },
            { inputSize: 224, scoreThreshold: 0.2 },
        ];

        return {
            status: 'loading-models',
            statusMessage: '{{ __('Memuat model wajah...') }}',
            hintMessage: '{{ __('Menyiapkan deteksi wajah') }}',
            stream: null,
            detectionTimer: null,
            isDetecting: false,
            initialized: false,
            earHistory: [],
            blinkState: 'lookingForOpen',
            blinkCount: 0,
            livenessPassed: false,
            clockingIn: false,
            wfaMode: false,
            captureBusy: false,
            lastDescriptor: null,
            gps: { latitude: null, longitude: null, accuracy: null },
            gpsSamples: [],
            gpsVariance: null,
            geoStatus: '{{ __('Mendeteksi...') }}',
            locationName: '{{ __('Mendapatkan lokasi...') }}',

            async init() {
                if (this.initialized) return;
                this.initialized = true;

                try {
                    this.cleanup();
                    await this.loadModels();
                    await this.startCamera();
                    this.startDetection();
                    this.collectGpsSamples();
                } catch (error) {
                    this.failHard('{{ __('Tidak dapat mengakses kamera.') }}');
                }
            },

            setStage(stage, message, hint = null) {
                this.status = stage;
                this.statusMessage = message;
                if (hint !== null) this.hintMessage = hint;
            },

            showSpinner() {
                return ['loading-models', 'opening-camera', 'saving'].includes(this.status);
            },

            canCapture() {
                return this.status === 'ready-to-capture' && !this.clockingIn && !this.captureBusy;
            },

            async loadModels() {
                if (typeof window.faceapi === 'undefined') throw new Error('face-api.js unavailable');
                await Promise.all([
                    window.faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
                    window.faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
                ]);
            },

            async startCamera() {
                this.setStage('opening-camera', '{{ __('Membuka kamera...') }}', '{{ __('Izinkan akses kamera') }}');
                const video = this.$refs.video;
                const getUserMedia = navigator.mediaDevices?.getUserMedia?.bind(navigator.mediaDevices);
                if (!getUserMedia) throw new Error('Camera API not available');

                try {
                    this.stream = await getUserMedia({ video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 360 }, frameRate: { ideal: 24 } } });
                } catch {
                    this.stream = await getUserMedia({ video: true });
                }

                video.srcObject = this.stream;
                await new Promise(r => { if (video.readyState >= 1) r(); else video.onloadedmetadata = () => r(); });
                await video.play();
                this.setStage('align-face', '{{ __('Posisikan wajah di kamera') }}', '{{ __('Hadap ke depan') }}');
            },

            startDetection() {
                this.stopDetection();
                this.queueDetection(120);
            },

            stopDetection() {
                if (this.detectionTimer) { clearTimeout(this.detectionTimer); this.detectionTimer = null; }
                this.isDetecting = false;
            },

            queueDetection(delay = 300) {
                this.stopDetection();
                this.detectionTimer = setTimeout(() => this.runDetection(), delay);
            },

            getGuideRect(width, height) {
                const gw = width * 0.54;
                const gh = height * 0.8;
                return { x: (width - gw) / 2, y: (height - gh) / 2, width: gw, height: gh };
            },

            ensureCanvasSize(width, height) {
                const canvas = this.$refs.overlay;
                if (!canvas) return;
                if (canvas.width !== width) canvas.width = width;
                if (canvas.height !== height) canvas.height = height;
            },

            drawOverlay(guideTone = 'neutral', detection = null) {
                const video = this.$refs.video;
                const canvas = this.$refs.overlay;
                if (!video || !canvas || !video.videoWidth || !video.videoHeight) return;

                this.ensureCanvasSize(video.videoWidth, video.videoHeight);
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                const guide = this.getGuideRect(canvas.width, canvas.height);
                const tones = {
                    neutral: { stroke: 'rgba(255,255,255,0.82)', shadow: 'rgba(255,255,255,0.18)', dash: [] },
                    warning: { stroke: '#f59e0b', shadow: 'rgba(245,158,11,0.26)', dash: [14, 10] },
                    success: { stroke: '#38bdf8', shadow: 'rgba(56,189,248,0.28)', dash: [] },
                    danger: { stroke: '#fb7185', shadow: 'rgba(251,113,133,0.28)', dash: [8, 8] },
                };
                const style = tones[guideTone] || tones.neutral;

                const cx = guide.x + guide.width / 2;
                const cy = guide.y + guide.height / 2;

                ctx.fillStyle = 'rgba(2,6,23,0.18)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.save();
                ctx.globalCompositeOperation = 'destination-out';
                ctx.beginPath();
                ctx.ellipse(cx, cy, guide.width / 2, guide.height / 2, 0, 0, Math.PI * 2);
                ctx.fill();
                ctx.restore();

                ctx.save();
                ctx.setLineDash(style.dash);
                ctx.strokeStyle = style.stroke;
                ctx.lineWidth = 4;
                ctx.shadowColor = style.shadow;
                ctx.shadowBlur = 18;
                ctx.beginPath();
                ctx.ellipse(cx, cy, guide.width / 2, guide.height / 2, 0, 0, Math.PI * 2);
                ctx.stroke();
                ctx.restore();
            },

            isFaceAligned(box, guide, video) {
                const cx = box.x + box.width / 2;
                const cy = box.y + box.height / 2;
                const minW = video.videoWidth * 0.24;
                const maxW = video.videoWidth * 0.7;
                const within = cx >= guide.x + guide.width * 0.08 && cx <= guide.x + guide.width - guide.width * 0.08 &&
                    cy >= guide.y + guide.height * 0.08 && cy <= guide.y + guide.height - guide.height * 0.08;
                return { aligned: within && box.width >= minW && box.width <= maxW, tooSmall: box.width < minW, tooLarge: box.width > maxW };
            },

            computeEAR(landmarks) {
                const d = (a, b) => Math.sqrt((a.x - b.x) ** 2 + (a.y - b.y) ** 2);
                const l = landmarks.getLeftEye();
                const r = landmarks.getRightEye();
                const earL = (d(l[1], l[5]) + d(l[2], l[4])) / (2 * d(l[0], l[3]));
                const earR = (d(r[1], r[5]) + d(r[2], r[4])) / (2 * d(r[0], r[3]));
                return (earL + earR) / 2;
            },

            trackBlink(ear) {
                if (this.livenessPassed) return;
                if (this.blinkState === 'lookingForOpen') {
                    if (ear > 0.25) this.blinkState = 'lookingForClosed';
                    this.setStage('blink', '{{ __('Kedipkan mata untuk verifikasi') }}', '{{ __('Buka mata lebar, lalu kedip') }}');
                } else if (this.blinkState === 'lookingForClosed') {
                    if (ear < 0.2) this.blinkState = 'lookingForOpenAgain';
                } else if (this.blinkState === 'lookingForOpenAgain') {
                    if (ear > 0.25) {
                        this.blinkCount++;
                        if (this.blinkCount >= 1) {
                            this.livenessPassed = true;
                            this.stopDetection();
                            this.setStage('ready-to-capture', '{{ __('Wajah terverifikasi') }}', '{{ __('Siap absen. Tekan tombol untuk melanjutkan.') }}');
                        } else {
                            this.blinkState = 'lookingForClosed';
                        }
                    }
                }
            },

            resetBlink() {
                if (this.livenessPassed) return;
                this.blinkState = 'lookingForOpen';
                this.blinkCount = 0;
            },

            async runDetection() {
                const video = this.$refs.video;
                if (!video || !this.stream || this.captureBusy) { this.stopDetection(); return; }
                if (!video.videoWidth || !video.videoHeight) { this.queueDetection(250); return; }
                if (this.isDetecting) { this.queueDetection(250); return; }

                this.isDetecting = true;

                try {
                    const options = new window.faceapi.TinyFaceDetectorOptions(previewOptions);
                    const detections = await window.faceapi.detectAllFaces(video, options).withFaceLandmarks();
                    const guide = this.getGuideRect(video.videoWidth, video.videoHeight);

                    if (!detections.length) {
                        this.drawOverlay('neutral');
                        this.resetBlink();
                        this.setStage('align-face', '{{ __('Arahkan wajah ke kamera') }}', '{{ __('Hadap ke depan') }}');
                        return;
                    }

                    if (detections.length > 1) {
                        this.drawOverlay('danger', detections[0]);
                        this.resetBlink();
                        this.setStage('align-face', '{{ __('Hanya satu wajah yang terdeteksi') }}');
                        return;
                    }

                    const detection = detections[0];
                    const faceBox = detection.detection.box;
                    const alignment = this.isFaceAligned(faceBox, guide, video);

                    if (!alignment.aligned) {
                        this.drawOverlay(alignment.tooSmall || alignment.tooLarge ? 'warning' : 'neutral', detection);
                        if (alignment.tooSmall) this.setStage('align-face', '{{ __('Mendekatlah sedikit') }}');
                        else if (alignment.tooLarge) this.setStage('align-face', '{{ __('Mundur sedikit') }}');
                        else this.setStage('align-face', '{{ __('Posisikan wajah di tengah') }}');
                        this.resetBlink();
                        return;
                    }

                    this.drawOverlay(this.livenessPassed ? 'success' : 'warning', detection);

                    if (this.livenessPassed) return;

                    const ear = this.computeEAR(detection.landmarks);
                    this.earHistory.push(ear);
                    if (this.earHistory.length > 10) this.earHistory.shift();
                    this.trackBlink(ear);
                } catch (error) {
                    this.drawOverlay('danger');
                    this.setStage('error', '{{ __('Gagal deteksi wajah') }}');
                } finally {
                    this.isDetecting = false;
                    if (!this.captureBusy) this.queueDetection();
                }
            },

            snapshotCanvas() {
                const video = this.$refs.video;
                const width = video.videoWidth;
                const height = video.videoHeight;
                if (!width || !height) throw new Error('Camera not ready');
                const scale = Math.min(1, 360 / width);
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(width * scale));
                canvas.height = Math.max(1, Math.round(height * scale));
                canvas.getContext('2d', { willReadFrequently: true }).drawImage(video, 0, 0, canvas.width, canvas.height);
                return canvas;
            },

            async captureSnapshots() {
                const snapshots = [];
                for (let i = 0; i < 2; i++) {
                    snapshots.push(this.snapshotCanvas());
                    await new Promise(r => setTimeout(r, 120));
                }
                return snapshots;
            },

            async describeSnapshots(snapshots) {
                let lastError = null;
                for (const snapshot of snapshots) {
                    for (const opts of captureOptions) {
                        try {
                            const detection = await Promise.race([
                                window.faceapi.detectSingleFace(snapshot, new window.faceapi.TinyFaceDetectorOptions(opts)).withFaceLandmarks(),
                                new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 4000)),
                            ]);
                            if (detection?.landmarks?.positions?.length === 68) {
                                return buildFaceGeometryDescriptor(detection.landmarks);
                            }
                        } catch (error) { lastError = error; }
                    }
                }
                if (lastError) throw lastError;
                throw new Error('{{ __('Gagal merekam wajah') }}');
            },

            async doClockIn() {
                if (!this.canCapture()) return;

                this.captureBusy = true;
                this.clockingIn = true;
                this.stopDetection();
                this.setStage('saving', '{{ __('Merekam wajah...') }}', '{{ __('Tahan posisi') }}');

                try {
                    const snapshots = await this.captureSnapshots();
                    const descriptor = await this.describeSnapshots(snapshots);
                    if (!descriptor || descriptor.length !== 129 || descriptor[0] !== 2) {
                        throw new Error('{{ __('Descriptor tidak valid') }}');
                    }

                    this.setStage('saving', '{{ __('Mengirim absen...') }}', '');

                    const video = this.$refs.video;
                    const cropCanvas = document.createElement('canvas');
                    cropCanvas.width = 200;
                    cropCanvas.height = 200;
                    cropCanvas.getContext('2d').drawImage(video, 0, 0, video.videoWidth, video.videoHeight, 0, 0, 200, 200);
                    const blob = await new Promise(r => cropCanvas.toBlob(r, 'image/jpeg', 0.85));
                    const reader = new FileReader();
                    const photoSelfie = await new Promise(r => { reader.onload = () => r(reader.result.split(',')[1]); reader.readAsDataURL(blob); });

                    const payload = {
                        descriptor: descriptor,
                        latitude: this.gps.latitude,
                        longitude: this.gps.longitude,
                        accuracy: this.gps.accuracy,
                        gps_variance: this.gpsVariance,
                        is_wfa: this.wfaMode,
                        photo_selfie: photoSelfie,
                    };
                    if (this.wfaMode) payload.wfa_note = '{{ __('Absen WFA via aplikasi') }}';

                    const res = await fetch('/api/v1/attendance/clock-in', {
                        method: 'POST',
                        headers: { ...window.apiHeaders(), 'Content-Type': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });
                    const json = await res.json();

                    if (res.ok && json.status === 'success') {
                        Livewire.dispatch('toast', { variant: 'success', text: json.message });
                        this.cleanup();
                        setTimeout(() => window.location.href = '/attendance', 1500);
                    } else {
                        Livewire.dispatch('toast', { variant: 'error', text: json.message || '{{ __('Clock In gagal') }}' });
                        this.captureBusy = false;
                        this.clockingIn = false;
                        this.resetBlink();
                        this.livenessPassed = false;
                        this.drawOverlay('neutral');
                        this.setStage('align-face', '{{ __('Coba lagi') }}', '{{ __('Posisikan wajah di kamera') }}');
                        this.startDetection();
                    }
                } catch (error) {
                    Livewire.dispatch('toast', { variant: 'error', text: '{{ __('Koneksi error') }}' });
                    this.captureBusy = false;
                    this.clockingIn = false;
                    this.resetBlink();
                    this.livenessPassed = false;
                    this.drawOverlay('neutral');
                    this.setStage('align-face', '{{ __('Coba lagi') }}');
                    this.startDetection();
                }
            },

            collectGpsSamples() {
                if (!navigator.geolocation) { this.geoStatus = '{{ __('Tidak Ada') }}'; return; }
                const sample = (i) => {
                    if (i >= 3) {
                        const lats = this.gpsSamples.map(s => s.latitude);
                        const lngs = this.gpsSamples.map(s => s.longitude);
                        const avgLat = lats.reduce((a, b) => a + b) / lats.length;
                        const avgLng = lngs.reduce((a, b) => a + b) / lngs.length;
                        const latVar = lats.reduce((s, v) => s + (v - avgLat) ** 2, 0) / lats.length;
                        const lngVar = lngs.reduce((s, v) => s + (v - avgLng) ** 2, 0) / lngs.length;
                        this.gpsVariance = Math.sqrt(latVar + lngVar);
                        this.geoStatus = this.gpsVariance < 0.000001 ? '{{ __('GPS Mencurigakan') }}' : '{{ __('Dalam Radius') }}';
                        this.gps = this.gpsSamples[this.gpsSamples.length - 1];
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(
                        pos => { this.gpsSamples.push({ latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: Math.round(pos.coords.accuracy) }); setTimeout(() => sample(i + 1), 1500); },
                        () => { this.geoStatus = '{{ __('Tidak Ada') }}'; },
                        { enableHighAccuracy: true, timeout: 10000 },
                    );
                };
                sample(0);
            },

            stopStream() {
                if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
                if (this.$refs?.video) { this.$refs.video.pause?.(); this.$refs.video.srcObject = null; }
            },

            clearOverlay() {
                if (!this.$refs?.overlay) return;
                const ctx = this.$refs.overlay.getContext?.('2d');
                ctx?.clearRect(0, 0, this.$refs.overlay.width, this.$refs.overlay.height);
            },

            cleanup() {
                this.stopDetection();
                this.stopStream();
                this.clearOverlay();
                this.captureBusy = false;
            },

            failHard(message) {
                this.cleanup();
                this.setStage('error', message, '{{ __('Muat ulang halaman untuk mencoba lagi') }}');
            },
        };
    }
</script>
