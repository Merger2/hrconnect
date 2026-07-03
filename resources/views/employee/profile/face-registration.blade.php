<x-layouts::app.sidebar>
    <script src="/assets/js/face-api.min.js"></script>
    <div class="mx-auto flex max-w-[480px] flex-col gap-4 md:max-w-3xl md:gap-6"
         x-data="faceRegistration()" x-init="init()">

        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-brand-lavender">
                <span class="material-symbols-outlined text-brand-teal">face</span>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-ink">{{ __('Registrasi Wajah') }}</h2>
                <p class="text-sm text-on-surface-variant">{{ __('Pendaftaran Face ID untuk absensi') }}</p>
            </div>
        </div>

        {{-- Guide & Camera Area --}}
        <section class="relative flex min-h-[400px] flex-col items-center justify-center overflow-hidden rounded-xl bg-brand-lavender p-3 shadow-sm">
            <video x-ref="video" autoplay muted playsinline
                   class="absolute inset-0 h-full w-full object-cover"
                   :class="stream ? 'opacity-100' : 'opacity-0'">
            </video>
            <canvas x-ref="overlay" class="absolute inset-0 h-full w-full"></canvas>

            {{-- Countdown overlay --}}
            <div x-show="countdown > 0 && stream"
                 x-cloak
                 class="absolute inset-0 z-20 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                <span class="text-7xl font-bold text-white drop-shadow-lg" x-text="countdown"></span>
            </div>

            {{-- Liveness direction arrows --}}
            <div x-show="stream && livenessPassed === false && (challengeStep === 'turn-first-side' || challengeStep === 'turn-opposite-side')"
                 x-cloak
                 class="absolute left-1/2 top-1/2 z-10 -translate-x-1/2 -translate-y-1/2">
                <template x-if="challengeStep === 'turn-first-side'">
                    <div class="flex gap-6">
                        <span class="material-symbols-outlined animate-pulse text-5xl text-white drop-shadow-lg">arrow_back</span>
                        <span class="material-symbols-outlined animate-pulse text-5xl text-white drop-shadow-lg">arrow_forward</span>
                    </div>
                </template>
                <template x-if="challengeStep === 'turn-opposite-side'">
                    <div>
                        <span x-show="firstTurnDirection === 'left'"
                              class="material-symbols-outlined animate-pulse text-5xl text-white drop-shadow-lg">arrow_forward</span>
                        <span x-show="firstTurnDirection === 'right'"
                              class="material-symbols-outlined animate-pulse text-5xl text-white drop-shadow-lg">arrow_back</span>
                    </div>
                </template>
            </div>

            {{-- Recenter indicator --}}
            <div x-show="stream && (challengeStep === 'recenter-after-first-turn' || challengeStep === 'recenter-final')"
                 x-cloak
                 class="absolute left-1/2 top-1/2 z-10 -translate-x-1/2 -translate-y-1/2">
                <span class="material-symbols-outlined text-5xl text-white drop-shadow-lg">unfold_less</span>
            </div>

            {{-- Liveness progress bar --}}
            <div x-show="stream && livenessPassed === false && status !== 'loading-models' && status !== 'opening-camera'"
                 class="absolute left-3 right-3 top-3 z-10">
                <div class="flex items-center gap-1.5">
                    <div class="flex-1 h-1.5 rounded-full bg-white/30 overflow-hidden">
                        <div class="h-full rounded-full bg-white transition-all duration-500"
                             :style="'width: ' + livenessProgress + '%'"></div>
                    </div>
                    <span class="text-xs font-medium text-white/80 drop-shadow-sm" x-text="livenessLabel"></span>
                </div>
            </div>

            <template x-if="!stream">
                <div class="absolute inset-0 z-[1] flex items-center justify-center bg-secondary-fixed/50">
                    <span class="material-symbols-outlined text-6xl text-brand-teal/30">face</span>
                </div>
            </template>

            <div class="z-10 mb-4 mt-auto flex w-[90%] flex-col gap-2 rounded-xl border border-white/20 bg-canvas/90 px-4 py-3 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-outline">{{ __('Status') }}</p>
                        <p class="text-lg font-semibold text-ink" x-text="statusMessage"></p>
                    </div>
                    <template x-if="status === 'ready-to-capture' || status === 'saving'">
                        <span class="material-symbols-outlined text-3xl text-brand-mint" data-weight="fill">check_circle</span>
                    </template>
                    <template x-if="status === 'error'">
                        <span class="material-symbols-outlined text-3xl text-error">error</span>
                    </template>
                    <template x-if="['loading-models','opening-camera','arming-liveness','turn-face','turn-opposite-face','recenter-face'].includes(status)">
                        <span class="material-symbols-outlined text-3xl text-brand-ochre">face</span>
                    </template>
                </div>
                <p class="text-xs text-on-surface-variant" x-text="hintMessage"></p>
            </div>

            {{-- Loading overlay --}}
            <div x-show="status === 'loading-models' || status === 'opening-camera'"
                 class="absolute inset-0 z-20 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                <div class="flex flex-col items-center gap-3 rounded-lg bg-canvas px-8 py-6 shadow-lg">
                    <div class="size-8 animate-spin rounded-full border-4 border-outline-variant border-t-ink"></div>
                    <p class="text-sm font-medium text-ink" x-text="statusMessage"></p>
                </div>
            </div>
        </section>

        {{-- Action buttons --}}
        <div class="flex gap-3">
            <button x-show="registrationDone"
                    @click="resetCapture()"
                    class="flex-1 rounded-xl border border-outline-variant px-4 py-3 text-sm font-semibold text-ink hover:bg-surface-variant">
                {{ __('Ulang') }}
            </button>

            <button x-show="!registrationDone && stream"
                    @click="capture()"
                    :disabled="!canCapture || captureBusy"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-ink px-6 py-4 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-primary-container active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                <span class="material-symbols-outlined" x-show="!captureBusy">camera</span>
                <span x-show="captureBusy" class="inline-block size-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                <span x-text="captureBusy ? '{{ __('Menyimpan...') }}' : buttonLabel()"></span>
            </button>

            <button x-show="!stream && !registrationDone"
                    @click="startCamera()"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-ink px-6 py-4 text-sm font-semibold text-white shadow-sm hover:bg-primary-container active:scale-[0.98]">
                <span class="material-symbols-outlined">camera_alt</span>
                {{ __('Mulai Kamera') }}
            </button>
        </div>

        {{-- Error --}}
        <div x-show="registerError"
             class="rounded-xl bg-error/10 px-4 py-3 text-sm text-error"
             x-text="registerError"></div>

        {{-- Done --}}
        <div x-show="registrationDone && !stream"
             class="flex flex-col items-center gap-4 rounded-lg border border-outline-variant bg-surface-container-low p-8 text-center">
            <span class="material-symbols-outlined text-6xl text-brand-mint">check_circle</span>
            <h3 class="text-xl font-semibold text-ink">{{ __('Registrasi Berhasil') }}</h3>
            <p class="text-sm text-on-surface-variant">{{ __('Wajah Anda telah terdaftar untuk absensi Face ID.') }}</p>
            <div class="mt-2 flex gap-3">
                <a href="{{ route('attendance.index') }}"
                   class="flex items-center gap-2 rounded-xl bg-ink px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-container">
                    <span class="material-symbols-outlined text-lg">fact_check</span>
                    {{ __('Absen Sekarang') }}
                </a>
                <button @click="resetCapture()"
                        class="rounded-xl border border-outline-variant px-6 py-3 text-sm font-semibold text-ink hover:bg-surface-variant">
                    {{ __('Ulang') }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function faceRegistration() {
            return {
                status: 'idle',
                statusMessage: '{{ __('Siap') }}',
                hintMessage: '{{ __('Tekan Mulai Kamera untuk memulai') }}',
                stream: null,
                detectionTimer: null,
                isDetecting: false,
                captureBusy: false,
                canCapture: false,
                registrationDone: false,
                registerError: null,

                stableFrames: 0,
                requiredStableFrames: 2,
                baselineYaw: null,
                livenessPassed: false,
                challengeStep: 'turn-first-side',
                firstTurnDirection: null,
                leftTurnDetected: false,
                rightTurnDetected: false,
                recenterFrames: 0,
                requiredRecenterFrames: 1,
                descriptorVersion: 2,

                countdown: 0,
                countdownTimer: null,

                livenessProgress: 0,
                livenessLabel: '',

                messages: {
                    loadingModels: '{{ __('Memuat model wajah...') }}',
                    openingCamera: '{{ __('Membuka kamera...') }}',
                    centerFace: '{{ __('Arahkan wajah ke tengah panduan') }}',
                    holdStill: '{{ __('Tahan wajah sebentar') }}',
                    passChallenge: '{{ __('Gerakkan kepala perlahan') }}',
                    liveConfirmed: '{{ __('Wajah asli terverifikasi') }}',
                    savingFace: '{{ __('Menyimpan data wajah...') }}',
                    tooManyFaces: '{{ __('Hanya satu wajah yang boleh terlihat') }}',
                    moveCloser: '{{ __('Dekatkan wajah ke kamera') }}',
                    moveBack: '{{ __('Mundur sedikit dari kamera') }}',
                    alignFace: '{{ __('Jaga wajah tetap di tengah') }}',
                    descriptorFailed: '{{ __('Gagal menangkap wajah. Tetap diam dan coba lagi.') }}',
                    cameraError: '{{ __('Tidak dapat mengakses kamera.') }}',
                    faceError: '{{ __('Tidak dapat memverifikasi wajah. Coba lagi.') }}',
                    captureFace: '{{ __('Ambil Wajah') }}',
                    captureNow: '{{ __('Ambil sekarang') }}',
                    permissionHint: '{{ __('Izinkan akses kamera untuk melanjutkan') }}',
                    livenessHint: '{{ __('Hadap ke depan, lalu ikuti petunjuk gerakan') }}',
                    readyHint: '{{ __('Siap. Mengambil otomatis...') }}',
                    loadingHint: '{{ __('Menyiapkan deteksi wajah') }}',
                    turnBothSidesHint: '{{ __('Pertama, tengok ke satu sisi') }}',
                    turnOppositeHint: '{{ __('Sekarang tengok ke sisi sebaliknya') }}',
                    recenterHint: '{{ __('Kembali hadap ke depan sebelum gerakan berikutnya') }}',
                    finalCenterHint: '{{ __('Hadap ke depan untuk menyelesaikan') }}',
                },

                async init() {
                    if (typeof faceapi === 'undefined') {
                        this.registerError = '{{ __('Gagal memuat library wajah. Refresh halaman.') }}';
                        return;
                    }
                },

                setStage(stage, message, hint) {
                    this.status = stage;
                    this.statusMessage = message;
                    if (hint) this.hintMessage = hint;
                },

                async startCamera() {
                    this.registerError = null;
                    this.setStage('loading-models', this.messages.loadingModels, this.messages.loadingHint);

                    try {
                        if (!window.__faceApiLoaded) {
                            await Promise.all([
                                faceapi.nets.tinyFaceDetector.loadFromUri('/models/av1'),
                                faceapi.nets.faceLandmark68Net.loadFromUri('/models/av1'),
                            ]);
                            window.__faceApiLoaded = true;
                        }
                    } catch (e) {
                        this.setStage('error', this.messages.cameraError, '');
                        this.registerError = '{{ __('Gagal memuat model wajah') }}';
                        return;
                    }

                    this.setStage('opening-camera', this.messages.openingCamera, this.messages.permissionHint);

                    try {
                        const video = this.$refs.video;
                        const getUserMedia = navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);

                        let s;
                        try {
                            s = await getUserMedia({
                                video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 360 }, frameRate: { ideal: 24, max: 24 } },
                            });
                        } catch {
                            s = await getUserMedia({ video: true });
                        }

                        this.stream = s;
                        video.srcObject = s;
                        await new Promise((resolve) => {
                            if (video.readyState >= 1) return resolve();
                            video.onloadedmetadata = () => resolve();
                        });
                        await video.play();

                        this.resetLiveness(this.messages.centerFace);
                        this.startDetection();
                    } catch (e) {
                        this.setStage('error', this.messages.cameraError, '');
                        this.registerError = '{{ __('Izin kamera ditolak') }}';
                    }
                },

                startDetection() {
                    this.stopDetection();
                    this.queueDetection(120);
                },

                stopDetection() {
                    if (this.detectionTimer) {
                        clearTimeout(this.detectionTimer);
                        this.detectionTimer = null;
                    }
                    this.isDetecting = false;
                },

                queueDetection(delay = 420) {
                    this.stopDetection();
                    this.detectionTimer = setTimeout(() => {
                        this.runDetection();
                    }, delay);
                },

                updateLivenessProgress() {
                    const steps = ['turn-first-side', 'recenter-after-first-turn', 'turn-opposite-side', 'recenter-final'];
                    const idx = steps.indexOf(this.challengeStep);
                    if (idx < 0) { this.livenessProgress = 0; this.livenessLabel = ''; return; }
                    this.livenessProgress = Math.round(((idx + 1) / steps.length) * 100);
                    const labels = ['Langkah 1/4', 'Langkah 2/4', 'Langkah 3/4', 'Langkah 4/4'];
                    this.livenessLabel = labels[idx];
                },

                resetLiveness(message, stage = 'align-face') {
                    this.stableFrames = 0;
                    this.baselineYaw = null;
                    this.livenessPassed = false;
                    this.canCapture = false;
                    this.challengeStep = 'turn-first-side';
                    this.firstTurnDirection = null;
                    this.leftTurnDetected = false;
                    this.rightTurnDetected = false;
                    this.recenterFrames = 0;
                    this.updateLivenessProgress();
                    this.setStage(stage, message, this.messages.livenessHint);
                },

                getGuideRect(width, height) {
                    const guideWidth = width * 0.54;
                    const guideHeight = height * 0.8;
                    return { x: (width - guideWidth) / 2, y: (height - guideHeight) / 2, width: guideWidth, height: guideHeight };
                },

                ensureCanvasSize(width, height) {
                    const canvas = this.$refs.overlay;
                    if (!canvas) return;
                    if (canvas.width !== width) canvas.width = width;
                    if (canvas.height !== height) canvas.height = height;
                },

                drawOverlay(detections = [], guideTone = 'neutral') {
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
                    const rx = guide.width / 2;
                    const ry = guide.height / 2;

                    ctx.fillStyle = 'rgba(2,6,23,0.18)';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.save();
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.beginPath();
                    ctx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.restore();

                    ctx.save();
                    ctx.setLineDash(style.dash);
                    ctx.strokeStyle = style.stroke;
                    ctx.lineWidth = 4;
                    ctx.shadowColor = style.shadow;
                    ctx.shadowBlur = 18;
                    ctx.beginPath();
                    ctx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
                    ctx.stroke();
                    ctx.restore();

                    if (detections.length > 1) {
                        for (const det of detections) {
                            const box = det.detection ? det.detection.box : det.box;
                            const fcx = box.x + box.width / 2;
                            const fcy = box.y + box.height / 2;
                            ctx.save();
                            ctx.setLineDash([8, 8]);
                            ctx.strokeStyle = tones.danger.stroke;
                            ctx.lineWidth = 3;
                            ctx.beginPath();
                            ctx.ellipse(fcx, fcy, box.width / 2, box.height / 2, 0, 0, Math.PI * 2);
                            ctx.stroke();
                            ctx.restore();
                        }
                    }
                },

                isFaceAligned(box, guide, video) {
                    const cx = box.x + box.width / 2;
                    const cy = box.y + box.height / 2;
                    const minW = video.videoWidth * 0.24;
                    const maxW = video.videoWidth * 0.7;
                    const aligned = cx >= guide.x + guide.width * 0.08 &&
                        cx <= guide.x + guide.width - guide.width * 0.08 &&
                        cy >= guide.y + guide.height * 0.08 &&
                        cy <= guide.y + guide.height - guide.height * 0.08;
                    return { aligned, tooSmall: box.width < minW, tooLarge: box.width > maxW };
                },

                getYawScore(landmarks) {
                    const avg = (pts) => pts.reduce((c, p) => ({ x: c.x + p.x, y: c.y + p.y }), { x: 0, y: 0 });
                    const leftEye = landmarks.getLeftEye();
                    const rightEye = landmarks.getRightEye();
                    const nose = landmarks.getNose();
                    const noseTip = nose[3] || nose[0];
                    const aL = avg(leftEye), aR = avg(rightEye);
                    const eyeMidX = (aL.x + aR.x) / 2;
                    const eyeDist = Math.max(Math.abs(aR.x - aL.x), 1);
                    return (noseTip.x - eyeMidX) / eyeDist;
                },

                getTurnDirection(yaw, threshold) {
                    const delta = yaw - this.baselineYaw;
                    if (delta <= -threshold) return 'left';
                    if (delta >= threshold) return 'right';
                    return null;
                },

                buildDescriptor(landmarks) {
                    const avg = (pts) => pts.reduce((c, p) => ({ x: c.x + p.x, y: c.y + p.y }), { x: 0, y: 0 });
                    const leftEye = avg(landmarks.getLeftEye());
                    const rightEye = avg(landmarks.getRightEye());
                    const eyeMidX = (leftEye.x + rightEye.x) / 2;
                    const eyeMidY = (leftEye.y + rightEye.y) / 2;
                    const eyeDist = Math.max(Math.hypot(rightEye.x - leftEye.x, rightEye.y - leftEye.y), 1);
                    const roll = Math.atan2(rightEye.y - leftEye.y, rightEye.x - leftEye.x);
                    const cos = Math.cos(-roll);
                    const sin = Math.sin(-roll);
                    const excluded = new Set([0, 1, 15, 16]);
                    const descriptor = [this.descriptorVersion];

                    landmarks.positions.forEach((point, index) => {
                        if (excluded.has(index)) return;
                        const tx = (point.x - eyeMidX) / eyeDist;
                        const ty = (point.y - eyeMidY) / eyeDist;
                        const rx = tx * cos - ty * sin;
                        const ry = tx * sin + ty * cos;
                        descriptor.push(Number(rx.toFixed(6)));
                        descriptor.push(Number(ry.toFixed(6)));
                    });

                    return descriptor;
                },

                async runDetection() {
                    const video = this.$refs.video;
                    if (!video || !this.stream || this.captureBusy) {
                        this.stopDetection();
                        return;
                    }
                    if (!video.videoWidth || !video.videoHeight) {
                        this.queueDetection(250);
                        return;
                    }
                    if (this.isDetecting) {
                        this.queueDetection(250);
                        return;
                    }

                    this.isDetecting = true;

                    try {
                        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 });
                        const detections = await faceapi.detectAllFaces(video, options).withFaceLandmarks();
                        const guide = this.getGuideRect(video.videoWidth, video.videoHeight);

                        if (!detections.length) {
                            this.drawOverlay([], 'neutral');
                            this.resetLiveness(this.messages.centerFace);
                            return;
                        }

                        if (detections.length > 1) {
                            this.drawOverlay(detections, 'danger');
                            this.resetLiveness(this.messages.tooManyFaces);
                            return;
                        }

                        const det = detections[0];
                        const box = det.detection.box;
                        const align = this.isFaceAligned(box, guide, video);

                        if (!align.aligned) {
                            this.drawOverlay([det], align.tooSmall || align.tooLarge ? 'warning' : 'neutral');
                            if (align.tooSmall) this.resetLiveness(this.messages.moveCloser);
                            else if (align.tooLarge) this.resetLiveness(this.messages.moveBack);
                            else this.resetLiveness(this.messages.alignFace);
                            return;
                        }

                        const landmarks = det.landmarks;
                        const yaw = this.getYawScore(landmarks);

                        this.drawOverlay([det], this.livenessPassed ? 'success' : 'warning');

                        if (this.livenessPassed) {
                            this.canCapture = true;
                            this.setStage('ready-to-capture', this.messages.liveConfirmed, this.messages.readyHint);
                            this.scheduleAutoCapture();
                            return;
                        }

                        if (this.stableFrames < this.requiredStableFrames) {
                            this.stableFrames += 1;
                            this.setStage('arming-liveness', this.messages.holdStill, this.messages.livenessHint);
                            return;
                        }

                        if (this.baselineYaw === null) this.baselineYaw = yaw;

                        const headTurnThreshold = 0.05;
                        const recenterThreshold = 0.12;
                        const dir = this.getTurnDirection(yaw, headTurnThreshold);
                        const reCentered = Math.abs(yaw - this.baselineYaw) < recenterThreshold;

                        if (this.challengeStep === 'turn-first-side') {
                            if (dir) {
                                this.firstTurnDirection = dir;
                                this.leftTurnDetected = this.leftTurnDetected || dir === 'left';
                                this.rightTurnDetected = this.rightTurnDetected || dir === 'right';
                                this.challengeStep = 'recenter-after-first-turn';
                                this.updateLivenessProgress();
                                this.recenterFrames = 0;
                                this.setStage('recenter-face', this.messages.holdStill, this.messages.recenterHint);
                                return;
                            }
                            this.setStage('turn-face', this.messages.passChallenge, this.messages.turnBothSidesHint);
                            return;
                        }

                        if (this.challengeStep === 'recenter-after-first-turn') {
                            if (reCentered) this.recenterFrames += 1;
                            else this.recenterFrames = 0;

                            if (this.recenterFrames >= this.requiredRecenterFrames && this.firstTurnDirection) {
                                this.challengeStep = 'turn-opposite-side';
                                this.updateLivenessProgress();
                                this.setStage('turn-opposite-face', this.messages.passChallenge, this.messages.turnOppositeHint);
                                return;
                            }
                            this.setStage('recenter-face', this.messages.holdStill, this.messages.recenterHint);
                            return;
                        }

                        if (this.challengeStep === 'turn-opposite-side') {
                            if (dir && dir !== this.firstTurnDirection) {
                                this.leftTurnDetected = this.leftTurnDetected || dir === 'left';
                                this.rightTurnDetected = this.rightTurnDetected || dir === 'right';
                                this.challengeStep = 'recenter-final';
                                this.updateLivenessProgress();
                                this.recenterFrames = 0;
                                this.setStage('recenter-face', this.messages.holdStill, this.messages.finalCenterHint);
                                return;
                            }
                            this.setStage('turn-opposite-face', this.messages.passChallenge, this.messages.turnOppositeHint);
                            return;
                        }

                        if (this.challengeStep === 'recenter-final') {
                            if (reCentered) this.recenterFrames += 1;
                            else this.recenterFrames = 0;

                            if (this.recenterFrames >= this.requiredRecenterFrames && this.leftTurnDetected && this.rightTurnDetected) {
                                this.livenessPassed = true;
                                this.livenessProgress = 100;
                                this.livenessLabel = '{{ __('Selesai') }}';
                                this.canCapture = true;
                                this.stopDetection();
                                this.setStage('ready-to-capture', this.messages.liveConfirmed, this.messages.readyHint);
                                this.scheduleAutoCapture();
                                return;
                            }
                            this.setStage('recenter-face', this.messages.holdStill, this.messages.finalCenterHint);
                            return;
                        }
                    } catch (e) {
                        this.drawOverlay([], 'danger');
                        this.setStage('error', this.messages.faceError, this.messages.livenessHint);
                    } finally {
                        this.isDetecting = false;
                        if (!this.captureBusy && !this.livenessPassed) this.queueDetection();
                    }
                },

                autoCaptureTimer: null,
                autoCaptureQueued: false,

                scheduleAutoCapture() {
                    if (this.autoCaptureQueued || this.captureBusy || !this.canCapture) return;
                    this.autoCaptureQueued = true;
                    this.countdown = 3;
                    this.countdownTimer = setInterval(() => {
                        this.countdown -= 1;
                        if (this.countdown <= 0) {
                            clearInterval(this.countdownTimer);
                            this.countdownTimer = null;
                            this.countdown = 0;
                            this.autoCaptureTimer = setTimeout(() => {
                                this.autoCaptureTimer = null;
                                this.capture();
                            }, 100);
                        }
                    }, 620);
                },

                clearAutoCapture() {
                    if (this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }
                    if (this.autoCaptureTimer) {
                        clearTimeout(this.autoCaptureTimer);
                        this.autoCaptureTimer = null;
                    }
                    this.countdown = 0;
                    this.autoCaptureQueued = false;
                },

                buttonLabel() {
                    if (this.captureBusy) return '{{ __('Menyimpan...') }}';
                    return this.canCapture ? this.messages.captureNow : this.messages.captureFace;
                },

                snapshotCanvas() {
                    const video = this.$refs.video;
                    const w = video.videoWidth;
                    const h = video.videoHeight;
                    if (!w || !h) throw new Error('Kamera belum siap');
                    const scale = Math.min(1, 360 / w);
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(w * scale));
                    canvas.height = Math.max(1, Math.round(h * scale));
                    canvas.getContext('2d', { willReadFrequently: true }).drawImage(video, 0, 0, canvas.width, canvas.height);
                    return canvas;
                },

                async captureSnapshots() {
                    const snaps = [];
                    for (let i = 0; i < 2; i++) {
                        snaps.push(this.snapshotCanvas());
                        await new Promise(r => setTimeout(r, 120));
                    }
                    return snaps;
                },

                async describeSnapshots(snaps) {
                    const captureOptions = [
                        { inputSize: 224, scoreThreshold: 0.3 },
                        { inputSize: 224, scoreThreshold: 0.2 },
                    ];
                    let lastError = null;

                    for (const snap of snaps) {
                        for (const opts of captureOptions) {
                            try {
                                const det = await faceapi
                                    .detectSingleFace(snap, new faceapi.TinyFaceDetectorOptions(opts))
                                    .withFaceLandmarks();
                                if (det?.landmarks?.positions?.length === 68) {
                                    return this.buildDescriptor(det.landmarks);
                                }
                            } catch (e) {
                                lastError = e;
                            }
                        }
                    }
                    throw lastError || new Error(this.messages.descriptorFailed);
                },

                async capture() {
                    if (!this.canCapture || this.captureBusy) return;
                    this.clearAutoCapture();
                    this.captureBusy = true;
                    this.canCapture = false;
                    this.stopDetection();
                    this.setStage('saving', this.messages.savingFace, '');

                    try {
                        const snaps = await this.captureSnapshots();
                        const descriptor = await this.describeSnapshots(snaps);

                        if (!descriptor || descriptor.length !== 129 || descriptor[0] !== this.descriptorVersion) {
                            throw new Error(this.messages.descriptorFailed);
                        }

                        const res = await fetch('/api/v1/face/register', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Authorization': 'Bearer ' + (window.Laravel?.sanctumToken || ''),
                            },
                            body: JSON.stringify({ descriptor }),
                        });

                        const json = await res.json();

                        if (res.ok) {
                            this.registrationDone = true;
                            this.cleanup();
                            Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Wajah berhasil didaftarkan') }}' });
                        } else {
                            this.registerError = json.message || '{{ __('Gagal mendaftarkan wajah') }}';
                            this.captureBusy = false;
                            this.canCapture = this.livenessPassed;
                        }
                    } catch (e) {
                        this.registerError = e.message || '{{ __('Gagal memproses wajah') }}';
                        this.captureBusy = false;
                        this.canCapture = false;
                        this.livenessPassed = false;
                        this.setStage('error', '{{ __('Gagal') }}', this.messages.livenessHint);
                    }
                },

                async resetCapture() {
                    this.cleanup();
                    this.registrationDone = false;
                    this.registerError = null;
                    this.livenessPassed = false;
                    this.baselineYaw = null;
                    this.setStage('idle', '{{ __('Siap') }}', '{{ __('Tekan Mulai Kamera untuk memulai') }}');
                },

                cleanup() {
                    this.clearAutoCapture();
                    this.stopDetection();
                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                        this.stream = null;
                    }
                    const video = this.$refs.video;
                    if (video) {
                        video.pause();
                        video.srcObject = null;
                    }
                    this.captureBusy = false;
                    this.canCapture = false;
                },
            };
        }
    </script>
</x-layouts::app.sidebar>
