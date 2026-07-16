import Swal from 'sweetalert2';

const modelUrl = '/models/av1';

function pointDistance(a, b) {
    return Math.hypot(a.x - b.x, a.y - b.y);
}

function averagePoint(points) {
    const total = points.reduce((carry, point) => ({
        x: carry.x + point.x,
        y: carry.y + point.y,
    }), { x: 0, y: 0 });
    return { x: total.x / points.length, y: total.y / points.length };
}

function getYawScore(landmarks) {
    const leftEyeCenter = averagePoint(landmarks.getLeftEye());
    const rightEyeCenter = averagePoint(landmarks.getRightEye());
    const nose = landmarks.getNose();
    const noseTip = nose[3] || nose[0];
    const eyeMidX = (leftEyeCenter.x + rightEyeCenter.x) / 2;
    const eyeDistance = Math.max(Math.abs(rightEyeCenter.x - leftEyeCenter.x), 1);
    return (noseTip.x - eyeMidX) / eyeDistance;
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
        const translatedX = (point.x - eyeMidX) / eyeDistance;
        const translatedY = (point.y - eyeMidY) / eyeDistance;
        descriptor.push(Number((translatedX * cos - translatedY * sin).toFixed(6)));
        descriptor.push(Number((translatedX * sin + translatedY * cos).toFixed(6)));
    });

    return descriptor;
}

export default function clockIn(config = {}) {
    const messages = {
        loadingModels: 'Memuat model wajah...',
        openingCamera: 'Membuka kamera...',
        centerFace: 'Posisikan wajah di kamera',
        holdStill: 'Tahan posisi sebentar',
        passChallenge: 'Tolehkan kepala perlahan',
        liveConfirmed: 'Wajah nyata terkonfirmasi. Siap merekam.',
        savingFace: 'Menyimpan Face ID...',
        capturingFrames: 'Merekam frame wajah...',
        tooManyFaces: 'Hanya satu wajah yang terdeteksi',
        moveCloser: 'Mendekatlah sedikit ke kamera',
        moveBack: 'Mundur sedikit dari kamera',
        alignFace: 'Posisikan wajah di tengah panduan',
        descriptorFailed: 'Gagal merekam wajah. Tetap diam dan coba lagi.',
        cameraError: 'Kamera belum bisa diakses.',
        cameraPermissionError: 'Akses kamera ditolak. Klik Izinkan pada notifikasi browser, lalu tekan Coba Lagi.',
        cameraUnavailableError: 'Kamera sedang dipakai aplikasi lain atau tidak tersedia. Tutup aplikasi lain, lalu coba lagi.',
        faceError: 'Tidak dapat memverifikasi wajah saat ini. Silakan coba lagi.',
        captureFace: 'Rekam Wajah',
        captureNow: 'Rekam sekarang',
        permissionHint: 'Izinkan akses kamera untuk melanjutkan',
        livenessHint: 'Hadap ke depan, lalu ikuti petunjuk menoleh.',
        readyHint: 'Siap. Merekam otomatis...',
        loadingHint: 'Menyiapkan deteksi wajah',
        turnBothSidesHint: 'Tolehkan kepala ke satu sisi dulu.',
        turnOppositeHint: 'Sekarang tolehkan ke sisi lainnya.',
        recenterHint: 'Hadap ke depan lagi sebelum menoleh berikutnya.',
        finalCenterHint: 'Hadap ke depan untuk menyelesaikan verifikasi.',
        guideCenterTitle: 'Hadap ke depan',
        guideCenterCopy: 'Posisikan wajah di dalam panduan sampai muncul petunjuk berikutnya.',
        guideTurnTitle: 'Tolehkan kepala',
        guideTurnFirstCopy: 'Toleh perlahan ke satu sisi. Tidak perlu tekan tombol.',
        guideTurnOppositeCopy: 'Sekarang toleh perlahan ke sisi lainnya.',
        guideRecenterTitle: 'Kembali ke tengah',
        guideRecenterCopy: 'Hadap ke depan sebentar agar kami bisa konfirmasi gerakan.',
        guideReadyTitle: 'Wajah terverifikasi',
        guideReadyCopy: 'Tahan posisi. Kami merekam otomatis.',
        guideErrorTitle: 'Coba sekali lagi',
        guideErrorCopy: 'Pastikan satu wajah terlihat dan ikuti petunjuk gerakan.',
    };

    const previewOptions = { inputSize: 160, scoreThreshold: 0.5 };
    const captureOptions = [
        { inputSize: 224, scoreThreshold: 0.3 },
        { inputSize: 224, scoreThreshold: 0.2 },
    ];

    return {
        status: 'loading-models',
        statusMessage: messages.loadingModels,
        hintMessage: messages.loadingHint,
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
        // PIN fallback state (for employees without face data)
        hasFaceEnrolled: config.hasFaceEnrolled ?? false,
        pinRequired: !(config.hasFaceEnrolled ?? false),
        pinDigits: ['', '', '', '', '', ''],
        get pin() { return this.pinDigits.join(''); },
        gps: { latitude: null, longitude: null, accuracy: null },
        gpsSamples: [],
        gpsVariance: null,
        geoStatus: 'Mendeteksi...',
        locationName: 'Mendapatkan lokasi...',
        geoError: false,
        geoPermissionDenied: false,

        async init() {
            if (this.initialized) return;
            this.initialized = true;
            this.hasFaceEnrolled = config.hasFaceEnrolled ?? false;
            this.pinRequired = !this.hasFaceEnrolled;
            this.collectGpsSamples();

            if (this.pinRequired) {
                // Face not enrolled → skip camera entirely, show PIN immediately
                this.setStage('pin-mode', 'Face ID belum terdaftar', 'Gunakan PIN 6 digit untuk absen');
                return;
            }

            // Face enrolled → normal camera flow
            try {
                this.cleanup();
                await window.loadFaceModels();
                await this.startCamera();
                this.startDetection();
            } catch (error) {
                this.failHard(error.message || 'Tidak dapat mengakses kamera.');
            }
        },

        setStage(stage, message, hint = null) {
            this.status = stage;
            this.statusMessage = message;
            if (hint !== null) this.hintMessage = hint;
        },

        showSpinner() { return ['loading-models', 'opening-camera', 'saving'].includes(this.status); },
        canCapture() { return this.status === 'ready-to-capture' && !this.clockingIn && !this.captureBusy; },

        async loadModels() {
                    if (typeof window.faceapi === 'undefined') throw new Error('face-api.js unavailable');
                    await Promise.all([
                        window.faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
                        window.faceapi.nets.faceLandmark68TinyNet.loadFromUri(modelUrl),
                    ]);
                },

        async startCamera() {
            this.setStage('opening-camera', messages.openingCamera, messages.permissionHint);
            const video = this.$refs.video;
            const getUserMedia = navigator.mediaDevices?.getUserMedia?.bind(navigator.mediaDevices);
            if (!getUserMedia) throw new Error('Camera API not available');
            try {
                this.stream = await getUserMedia({ video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 360 }, frameRate: { ideal: 24 } } });
            } catch {
                this.stream = await getUserMedia({ video: true });
            }
            video.srcObject = this.stream;
            await new Promise((resolve) => {
                if (video.readyState >= 1) { resolve(); return; }
                video.onloadedmetadata = () => resolve();
            });
            await video.play();
            this.setStage('align-face', 'Posisikan wajah di kamera', 'Hadap ke depan');
        },

        startDetection() { this.stopDetection(); this.queueDetection(120); },
        stopDetection() {
            if (this.detectionTimer) {
                clearTimeout(this.detectionTimer);
                this.detectionTimer = null;
            }
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
            ctx.translate(cx, cy);
            ctx.rotate(-Math.PI / 2);
            ctx.beginPath();
            ctx.ellipse(0, 0, guide.width / 2, guide.height / 2, 0, 0, Math.PI * 2);
            ctx.strokeStyle = style.stroke;
            ctx.lineWidth = 3;
            ctx.setLineDash(style.dash);
            ctx.shadowColor = style.shadow;
            ctx.shadowBlur = 12;
            ctx.stroke();
            ctx.restore();
        },

        async runDetection() {
            if (!this.stream || this.isDetecting) return;
            this.isDetecting = true;
            const video = this.$refs.video;
            if (!video || video.readyState < 2) { this.isDetecting = false; this.queueDetection(); return; }
            const detections = await window.faceapi.detectAllFaces(video, new window.faceapi.TinyFaceDetectorOptions(previewOptions))
                .withFaceLandmarks(true);
            if (!detections || detections.length === 0) {
                this.setStage('align-face', 'Wajah tidak terdeteksi', 'Posisikan wajah di dalam panduan');
                this.isDetecting = false;
                this.queueDetection();
                return;
            }
            if (detections.length > 1) {
                this.setStage('align-face', messages.tooManyFaces, 'Hanya satu wajah yang diperbolehkan');
                this.isDetecting = false;
                this.queueDetection();
                return;
            }
            const detection = detections[0];
            const landmarks = detection.landmarks;
            const yawScore = getYawScore(landmarks);
            const yawAbs = Math.abs(yawScore);
            if (this.status === 'align-face') {
                if (yawAbs < 0.12) {
                    this.setStage('turn-face', messages.turnBothSidesHint, messages.guideTurnFirstCopy);
                    this.baselineYaw = yawScore;
                    this.firstTurnDirection = null;
                    this.leftTurnDetected = false;
                    this.rightTurnDetected = false;
                } else if (yawAbs > 0.25) {
                    this.setStage('align-face', messages.alignFace, 'Posisikan wajah di tengah panduan');
                }
            } else if (this.status === 'turn-face') {
                if (this.firstTurnDirection === null) {
                    this.firstTurnDirection = yawScore > 0 ? 'right' : 'left';
                }
                if (this.firstTurnDirection === 'right' && yawScore > 0.15) this.rightTurnDetected = true;
                if (this.firstTurnDirection === 'left' && yawScore < -0.15) this.leftTurnDetected = true;
                if (this.firstTurnDirection === 'right' && this.rightTurnDetected && yawScore < 0.15) {
                    this.setStage('recenter-face', messages.recenterHint, messages.guideRecenterCopy);
                    this.recenterFrames = 0;
                } else if (this.firstTurnDirection === 'left' && this.leftTurnDetected && yawScore > -0.15) {
                    this.setStage('recenter-face', messages.recenterHint, messages.guideRecenterCopy);
                    this.recenterFrames = 0;
                }
            } else if (this.status === 'recenter-face') {
                this.recenterFrames++;
                if (this.recenterFrames >= this.requiredRecenterFrames) {
                    this.setStage('turn-opposite-face', messages.turnOppositeHint, messages.guideTurnOppositeCopy);
                }
            } else if (this.status === 'turn-opposite-face') {
                if (this.firstTurnDirection === 'right' && yawScore < -0.15) this.leftTurnDetected = true;
                if (this.firstTurnDirection === 'left' && yawScore > 0.15) this.rightTurnDetected = true;
                if ((this.firstTurnDirection === 'right' && this.leftTurnDetected) || (this.firstTurnDirection === 'left' && this.rightTurnDetected)) {
                    this.setStage('arming-liveness', messages.livenessHint, messages.guideReadyCopy);
                    this.livenessPassed = true;
                }
            } else if (this.status === 'arming-liveness') {
                if (yawAbs < 0.1) {
                    this.setStage('ready-to-capture', messages.liveConfirmed, messages.readyHint);
                    this.startAutoCapture();
                }
            } else if (this.status === 'ready-to-capture') {
                if (yawAbs > 0.15) {
                    this.setStage('align-face', messages.alignFace, 'Kembali ke posisi tengah');
                    this.clearAutoCapture();
                }
            }
            this.isDetecting = false;
            if (this.status !== 'error' && this.status !== 'saving') this.queueDetection();
        },

        stableFrames: 0,
        requiredStableFrames: 2,
        baselineYaw: null,
        firstTurnDirection: null,
        leftTurnDetected: false,
        rightTurnDetected: false,
        recenterFrames: 0,
        requiredRecenterFrames: 1,
        autoCaptureTimer: null,
        autoCaptureQueued: false,
        challengeStep: 'turn-first-side',

        startAutoCapture() {
            this.clearAutoCapture();
            this.autoCaptureQueued = true;
            this.autoCaptureTimer = setTimeout(() => {
                if (this.status === 'ready-to-capture' && !this.captureBusy) {
                    this.capture({ manual: false });
                }
            }, 800);
        },

        clearAutoCapture() {
            if (this.autoCaptureTimer) {
                clearTimeout(this.autoCaptureTimer);
                this.autoCaptureTimer = null;
            }
            this.autoCaptureQueued = false;
        },

        async capture({ manual = false } = {}) {
            if (this.status !== 'ready-to-capture' && !manual) return;
            if (this.captureBusy) return;
            this.captureBusy = true;
            this.clearAutoCapture();

            try {
                const video = this.$refs.video;
                const canvas = this.$refs.overlay;
                if (!video || video.readyState < 2) throw new Error('Video not ready');

                // Capture multiple frames with different options and average
                const descriptors = [];
                for (const opts of captureOptions) {
                    const detect = await window.faceapi.detectAllFaces(video, new window.faceapi.TinyFaceDetectorOptions(opts)).withFaceLandmarks(true);
                    if (detect.length > 0) {
                        descriptors.push(buildFaceGeometryDescriptor(detect[0].landmarks));
                    }
                }
                if (descriptors.length === 0) throw new Error(messages.descriptorFailed);

                // Average descriptors
                const dim = descriptors[0].length;
                const avg = [];
                for (let i = 0; i < dim; i++) {
                    let sum = 0;
                    for (const d of descriptors) sum += d[i];
                    avg.push(Number((sum / descriptors.length).toFixed(6)));
                }

                const descriptor = [2, ...avg];

                this.setStage('saving', messages.savingFace, messages.readyHint);
                await this.$wire.call('saveFaceDescriptor', descriptor);
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data wajah tersimpan.',
                    timer: 2000,
                    showConfirmButton: false
                });
                this.cleanup();
            } catch (error) {
                await this.reportClientError('capture', error, { descriptor_mode: 'geometry' });
                this.captureBusy = false;
                this.setStage('align-face', messages.descriptorFailed, 'Tetap diam dan coba lagi');
                this.startDetection();
                Swal.fire(
                    'Gagal Face ID',
                    `${messages.faceError}<br><br><small>Error: ${error?.message || error?.name || "Unknown error"}</small>`,
                    'error'
                );
            }
        },

        resetLiveness(message) {
            this.setStage('align-face', message || messages.centerFace, messages.guideCenterCopy);
            this.stableFrames = 0;
            this.requiredStableFrames = 2;
            this.baselineYaw = null;
            this.firstTurnDirection = null;
            this.leftTurnDetected = false;
            this.rightTurnDetected = false;
            this.recenterFrames = 0;
            this.requiredRecenterFrames = 1;
            this.clearAutoCapture();
            this.livenessPassed = false;
            this.challengeStep = 'turn-first-side';
        },

        guideTitle() {
            if (this.status === 'ready-to-capture' || this.status === 'saving') return messages.guideReadyTitle;
            if (this.status === 'error') return messages.guideErrorTitle;
            if (this.status === 'turn-face' || this.status === 'turn-opposite-face') return messages.guideTurnTitle;
            if (this.status === 'recenter-face') return messages.guideRecenterTitle;
            return messages.guideCenterTitle;
        },

        guideInstruction() {
            if (this.status === 'ready-to-capture' || this.status === 'saving') return messages.guideReadyCopy;
            if (this.status === 'error') return messages.guideErrorCopy;
            if (this.status === 'turn-opposite-face') return messages.guideTurnOppositeCopy;
            if (this.status === 'turn-face') return messages.guideTurnFirstCopy;
            if (this.status === 'recenter-face') return messages.guideRecenterCopy;
            return messages.guideCenterCopy;
        },

        buttonLabel() {
            if (this.status === 'saving') return messages.savingFace;
            return this.status === 'ready-to-capture' ? messages.captureNow : messages.captureFace;
        },

        withTimeout(promise, timeoutMs, label) {
            return Promise.race([
                promise,
                new Promise((_, reject) => {
                    setTimeout(() => reject(new Error(`${label} timed out after ${timeoutMs}ms`)), timeoutMs);
                }),
            ]);
        },

        async waitForRef(name, timeoutMs = 2200) {
            const startedAt = Date.now();
            while (Date.now() - startedAt < timeoutMs) {
                await this.$nextTick();
                if (this.$refs?.[name]) return this.$refs[name];
                await new Promise(r => setTimeout(r, 50));
            }
            throw new TypeError(`${name} element is not ready`);
        },

        async waitForGetUserMedia(timeoutMs = 2200) {
            const startedAt = Date.now();
            while (Date.now() - startedAt < timeoutMs) {
                const getUserMedia = navigator.mediaDevices?.getUserMedia?.bind(navigator.mediaDevices);
                if (getUserMedia) return getUserMedia;
                await new Promise(r => setTimeout(r, 50));
            }
            throw new TypeError('Camera API is not available');
        },

        async loadPreviewModels() {
            if (typeof window.faceapi === 'undefined') {
                throw new Error('face-api.js is unavailable');
            }
            await Promise.all([
                window.faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
                window.faceapi.nets.faceLandmark68TinyNet.loadFromUri(modelUrl),
            ]);
        },

        collectGpsSamples() {
            this.geoError = false;
            this.geoPermissionDenied = false;
            if (!navigator.geolocation) {
                this.geoStatus = 'Tidak Ada';
                this.geoError = true;
                this.locationName = 'Browser tidak mendukung geolocation';
                return;
            }
            this.geoStatus = 'Mendeteksi...';
            this.locationName = 'Mendapatkan lokasi...';
            const sample = (i) => {
                if (i >= 3) {
                    const lats = this.gpsSamples.map(s => s.latitude);
                    const lngs = this.gpsSamples.map(s => s.longitude);
                    const avgLat = lats.reduce((a, b) => a + b) / lats.length;
                    const avgLng = lngs.reduce((a, b) => a + b) / lngs.length;
                    const latVar = lats.reduce((s, v) => s + (v - avgLat) ** 2, 0) / lats.length;
                    const lngVar = lngs.reduce((s, v) => s + (v - avgLng) ** 2, 0) / lngs.length;
                    this.gpsVariance = Math.sqrt(latVar + lngVar);
                    this.geoStatus = this.gpsVariance < 0.000001 ? 'GPS Mencurigakan' : 'Dalam Radius';
                    this.gps = this.gpsSamples[this.gpsSamples.length - 1];
                    this.geoError = false;
                    this.geoPermissionDenied = false;
                    this.locationName = `${this.gps.latitude.toFixed(5)}, ${this.gps.longitude.toFixed(5)} — akurasi ${this.gps.accuracy}m`;
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    pos => { this.gpsSamples.push({ latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: Math.round(pos.coords.accuracy) }); setTimeout(() => sample(i + 1), 1500); },
                    (err) => {
                        if (err.code === err.PERMISSION_DENIED) {
                            this.geoPermissionDenied = true;
                            this.geoStatus = 'Izin Ditolak';
                        } else if (err.code === err.POSITION_UNAVAILABLE) {
                            this.geoError = true;
                            this.geoStatus = 'Lokasi Tidak Tersedia';
                        } else if (err.code === err.TIMEOUT) {
                            this.geoError = true;
                            this.geoStatus = 'Waktu Habis';
                        } else {
                            this.geoError = true;
                            this.geoStatus = 'Error GPS';
                        }
                        this.locationName = this.geoPermissionDenied
                            ? 'Izin lokasi ditolak. Tekan tombol di bawah untuk mencoba lagi.'
                            : 'Gagal mendapatkan lokasi. Pastikan GPS aktif.';
                    },
                    { enableHighAccuracy: true, timeout: 10000 },
                );
            };
            sample(0);
        },

        async retryGeo() {
            this.gpsSamples = [];
            this.gps = { latitude: null, longitude: null, accuracy: null };
            this.collectGpsSamples();
        },

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            if (this.$refs?.video) {
                this.$refs.video.pause?.();
                this.$refs.video.srcObject = null;
            }
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

        // PIN Fallback methods
        handlePinInput(e, i) {
            const val = e.target.value;
            if (val && i < 6) this.$refs.pinInputs.children[i - 1].focus();
        },
        clearPin() { this.pinDigits = Array(6).fill(''); },

        async submitPinClockIn() {
            if (this.pin.length !== 6 || this.clockingIn) return;
            // GPS not available — allow clock-in anyway with null coordinates
            // Server-side will enforce geofence / WFA mode
            this.clockingIn = true;
            try {
                const payload = {
                    pin: this.pin,
                    latitude: this.gps?.latitude ?? null,
                    longitude: this.gps?.longitude ?? null,
                    accuracy: this.gps?.accuracy ?? null,
                    is_wfa: this.wfaMode,
                };
                const res = await fetch('/api/v1/attendance/clock-in', {
                    method: 'POST',
                    headers: { ...window.apiHeaders(), 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                if (res.ok) {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Absen PIN berhasil!' });
                    setTimeout(() => window.location.href = '/attendance', 1000);
                } else {
                    Livewire.dispatch('toast', { variant: 'error', text: json.message || 'PIN salah atau gagal absen.' });
                    this.clearPin();
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Koneksi gagal' });
            } finally { this.clockingIn = false; }
        },

        async doClockIn() {
            // Face-based clock-in
            if (this.clockingIn || this.captureBusy) return;
            this.clockingIn = true;

            try {
                const video = this.$refs.video;
                if (!video || video.readyState < 2) {
                    Livewire.dispatch('toast', { variant: 'error', text: 'Kamera belum siap.' });
                    this.clockingIn = false;
                    return;
                }

                // Detect face and build descriptor
                const opts = captureOptions[0];
                const detect = await window.faceapi.detectAllFaces(video, new window.faceapi.TinyFaceDetectorOptions(opts)).withFaceLandmarks(true);
                if (!detect || detect.length === 0) {
                    Livewire.dispatch('toast', { variant: 'error', text: 'Wajah tidak terdeteksi. Posisikan wajah di kamera.' });
                    this.clockingIn = false;
                    return;
                }
                if (detect.length > 1) {
                    Livewire.dispatch('toast', { variant: 'error', text: 'Hanya satu wajah yang diperbolehkan.' });
                    this.clockingIn = false;
                    return;
                }

                const descriptor = buildFaceGeometryDescriptor(detect[0].landmarks);

                const payload = {
                    descriptor: descriptor,
                    latitude: this.gps?.latitude ?? null,
                    longitude: this.gps?.longitude ?? null,
                    accuracy: this.gps?.accuracy ?? null,
                    is_wfa: this.wfaMode,
                };

                const res = await fetch('/api/v1/attendance/clock-in', {
                    method: 'POST',
                    headers: { ...window.apiHeaders(), 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                const json = await res.json();

                if (res.ok) {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Absen berhasil!' });
                    setTimeout(() => window.location.href = '/attendance', 1000);
                } else {
                    // Wait, if still detecting face we just got an error
                    const errMsg = json.message || 'Wajah tidak dikenali. Coba lagi.';
                    Livewire.dispatch('toast', { variant: 'error', text: errMsg });
                }
            } catch (error) {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal absen: ' + (error.message || 'Koneksi terputus') });
                await this.reportClientError('clock-in', error, { descriptor_mode: 'geometry' });
            } finally {
                this.clockingIn = false;
                this.captureBusy = false;
            }
        },

        failHard(message) {
            this.cleanup();
            this.setStage('error', message, 'Muat ulang halaman untuk mencoba lagi');
        },

        formatError(error) {
            if (!error) return 'Unknown error';
            if (typeof error === 'string') return error;
            return error.message || error.name || JSON.stringify(error);
        },

        async reportClientError(stage, error, context = {}) {
            try {
                await this.$wire.call('reportClientError', stage, this.formatError(error), context);
            } catch (_) {}
        },

        async retryCamera() {
            this.cleanup();
            this.initialized = false;
            await this.init();
        },
    };
}