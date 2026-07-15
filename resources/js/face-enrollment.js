import Swal from 'sweetalert2';

export default function faceEnrollment() {
    const messages = {
        loadingModels: 'Memuat model Face ID...',
        openingCamera: 'Membuka kamera...',
        centerFace: 'Posisikan wajah di dalam panduan',
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

    const modelUrl = '/models/av1';
    const previewOptions = { inputSize: 160, scoreThreshold: 0.5 };
    const captureOptions = [
        { inputSize: 224, scoreThreshold: 0.3 },
        { inputSize: 224, scoreThreshold: 0.2 },
    ];

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

    return {
        status: 'loading-models',
        statusMessage: messages.loadingModels,
        hintMessage: messages.loadingHint,
        stream: null,
        detectionTimer: null,
        isDetecting: false,
        initialized: false,
        stableFrames: 0,
        requiredStableFrames: 2,
        baselineYaw: null,
        livenessPassed: false,
        captureBusy: false,
        autoCaptureTimer: null,
        autoCaptureQueued: false,
        challengeStep: 'turn-first-side',
        firstTurnDirection: null,
        leftTurnDetected: false,
        rightTurnDetected: false,
        recenterFrames: 0,
        requiredRecenterFrames: 1,

        async init() {
            if (this.initialized) return;
            this.initialized = true;

            try {
                this.cleanup();
                await this.loadPreviewModels();
                await this.startCamera();
                this.startDetection();
            } catch (error) {
                await this.reportClientError('init', error);
                this.failHard(messages.cameraError, error);
            }
        },

        setStage(stage, message, hint = null) {
            this.status = stage;
            this.statusMessage = message;
            this.hintMessage = hint ?? '';
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

        showSpinner() {
            return ['loading-models', 'opening-camera', 'saving'].includes(this.status);
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

        canCapture() {
            return this.status === 'ready-to-capture' && !this.captureBusy;
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
                window.faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
            ]);
        },

        async retryCamera() {
            this.cleanup();
            this.initialized = false;
            await this.init();
        },

        async startCamera() {
            this.setStage('opening-camera', messages.openingCamera, messages.permissionHint);
            const video = await this.waitForRef('video');
            const getUserMedia = await this.waitForGetUserMedia();

            try {
                this.stream = await getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 360 }, frameRate: { ideal: 24, max: 24 } },
                });
            } catch (error) {
                this.stream = await getUserMedia({ video: true });
            }

            video.srcObject = this.stream;
            await new Promise((resolve) => {
                if (video.readyState >= 1) { resolve(); return; }
                video.onloadedmetadata = () => resolve();
            });
            await video.play();
            this.resetLiveness(messages.centerFace);
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

        clearAutoCapture() {
            if (this.autoCaptureTimer) {
                clearTimeout(this.autoCaptureTimer);
                this.autoCaptureTimer = null;
            }
            this.autoCaptureQueued = false;
        },

        scheduleAutoCapture() {
            if (this.autoCaptureQueued || this.captureBusy || !this.canCapture()) return;
            this.autoCaptureQueued = true;
            this.autoCaptureTimer = setTimeout(() => {
                this.autoCaptureTimer = null;
                this.capture({ manual: false });
            }, 620);
        },

        queueDetection(delay = 420) {
            this.stopDetection();
            this.detectionTimer = setTimeout(() => { this.runDetection(); }, delay);
        },

        resetLiveness(message, stage = 'align-face') {
            this.clearAutoCapture();
            this.stableFrames = 0;
            this.baselineYaw = null;
            this.livenessPassed = false;
            this.challengeStep = 'turn-first-side';
            this.firstTurnDirection = null;
            this.leftTurnDetected = false;
            this.rightTurnDetected = false;
            this.recenterFrames = 0;
            this.setStage(stage, message, messages.livenessHint);
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
            const toneStyles = {
                neutral: { stroke: 'rgba(255,255,255,0.82)', shadow: 'rgba(255,255,255,0.18)', dash: [] },
                warning: { stroke: '#f59e0b', shadow: 'rgba(245,158,11,0.26)', dash: [14, 10] },
                success: { stroke: '#38bdf8', shadow: 'rgba(56,189,248,0.28)', dash: [] },
                danger: { stroke: '#fb7185', shadow: 'rgba(251,113,133,0.28)', dash: [8, 8] },
            };
            const style = toneStyles[guideTone] || toneStyles.neutral;
            const centerX = guide.x + guide.width / 2;
            const centerY = guide.y + guide.height / 2;

            ctx.fillStyle = 'rgba(2,6,23,0.18)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.save();
            ctx.globalCompositeOperation = 'destination-out';
            ctx.beginPath();
            ctx.ellipse(centerX, centerY, guide.width / 2, guide.height / 2, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();

            ctx.save();
            ctx.setLineDash(style.dash);
            ctx.strokeStyle = style.stroke;
            ctx.lineWidth = 4;
            ctx.shadowColor = style.shadow;
            ctx.shadowBlur = 18;
            ctx.beginPath();
            ctx.ellipse(centerX, centerY, guide.width / 2, guide.height / 2, 0, 0, Math.PI * 2);
            ctx.stroke();
            ctx.restore();

            if (detections.length > 1) {
                for (const detection of detections) {
                    const box = detection.detection ? detection.detection.box : detection.box;
                    ctx.save();
                    ctx.setLineDash([8, 8]);
                    ctx.strokeStyle = toneStyles.danger.stroke;
                    ctx.lineWidth = 3;
                    ctx.beginPath();
                    ctx.ellipse(box.x + box.width / 2, box.y + box.height / 2, box.width / 2, box.height / 2, 0, 0, Math.PI * 2);
                    ctx.stroke();
                    ctx.restore();
                }
            }
        },

        isFaceAligned(box, guide, video) {
            const centerX = box.x + box.width / 2;
            const centerY = box.y + box.height / 2;
            const minWidth = video.videoWidth * 0.24;
            const maxWidth = video.videoWidth * 0.7;
            const withinGuide = centerX >= guide.x + guide.width * 0.08 &&
                centerX <= guide.x + guide.width - guide.width * 0.08 &&
                centerY >= guide.y + guide.height * 0.08 &&
                centerY <= guide.y + guide.height - guide.height * 0.08;
            return {
                aligned: withinGuide && box.width >= minWidth && box.width <= maxWidth,
                tooSmall: box.width < minWidth,
                tooLarge: box.width > maxWidth,
            };
        },

        getTurnDirection(yaw, threshold) {
            const yawDelta = yaw - this.baselineYaw;
            if (yawDelta <= -threshold) return 'left';
            if (yawDelta >= threshold) return 'right';
            return null;
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
                    this.drawOverlay([], 'neutral');
                    this.resetLiveness(messages.centerFace);
                    return;
                }

                if (detections.length > 1) {
                    this.drawOverlay(detections, 'danger');
                    this.resetLiveness(messages.tooManyFaces);
                    return;
                }

                const detection = detections[0];
                const faceBox = detection.detection.box;
                const alignment = this.isFaceAligned(faceBox, guide, video);

                if (!alignment.aligned) {
                    this.drawOverlay([detection], alignment.tooSmall || alignment.tooLarge ? 'warning' : 'neutral');
                    if (alignment.tooSmall) this.resetLiveness(messages.moveCloser);
                    else if (alignment.tooLarge) this.resetLiveness(messages.moveBack);
                    else this.resetLiveness(messages.alignFace);
                    return;
                }

                const landmarks = detection.landmarks;
                const yaw = getYawScore(landmarks);
                this.drawOverlay([detection], this.livenessPassed ? 'success' : 'warning');

                if (this.livenessPassed) {
                    this.setStage('ready-to-capture', messages.liveConfirmed, messages.readyHint);
                    this.scheduleAutoCapture();
                    return;
                }

                if (this.stableFrames < this.requiredStableFrames) {
                    this.stableFrames += 1;
                    this.setStage('arming-liveness', messages.holdStill, messages.livenessHint);
                    return;
                }

                if (this.baselineYaw === null) this.baselineYaw = yaw;

                const headTurnThreshold = 0.05;
                const recenterThreshold = 0.12;
                const headTurnDirection = this.getTurnDirection(yaw, headTurnThreshold);
                const reCentered = Math.abs(yaw - this.baselineYaw) < recenterThreshold;

                if (this.challengeStep === 'turn-first-side') {
                    if (headTurnDirection) {
                        this.firstTurnDirection = headTurnDirection;
                        this.leftTurnDetected = this.leftTurnDetected || headTurnDirection === 'left';
                        this.rightTurnDetected = this.rightTurnDetected || headTurnDirection === 'right';
                        this.challengeStep = 'recenter-after-first-turn';
                        this.recenterFrames = 0;
                        this.setStage('recenter-face', messages.holdStill, messages.recenterHint);
                        return;
                    }
                    this.setStage('turn-face', messages.passChallenge, messages.turnBothSidesHint);
                    return;
                }

                if (this.challengeStep === 'recenter-after-first-turn') {
                    if (reCentered) this.recenterFrames += 1; else this.recenterFrames = 0;
                    if (this.recenterFrames >= this.requiredRecenterFrames && this.firstTurnDirection) {
                        this.challengeStep = 'turn-opposite-side';
                        this.setStage('turn-opposite-face', messages.passChallenge, messages.turnOppositeHint);
                        return;
                    }
                    this.setStage('recenter-face', messages.holdStill, messages.recenterHint);
                    return;
                }

                if (this.challengeStep === 'turn-opposite-side') {
                    if (headTurnDirection && headTurnDirection !== this.firstTurnDirection) {
                        this.leftTurnDetected = this.leftTurnDetected || headTurnDirection === 'left';
                        this.rightTurnDetected = this.rightTurnDetected || headTurnDirection === 'right';
                        this.challengeStep = 'recenter-final';
                        this.recenterFrames = 0;
                        this.setStage('recenter-face', messages.holdStill, messages.finalCenterHint);
                        return;
                    }
                    this.setStage('turn-opposite-face', messages.passChallenge, messages.turnOppositeHint);
                    return;
                }

                if (this.challengeStep === 'recenter-final') {
                    if (reCentered) this.recenterFrames += 1; else this.recenterFrames = 0;
                    if (this.recenterFrames >= this.requiredRecenterFrames && this.leftTurnDetected && this.rightTurnDetected) {
                        this.livenessPassed = true;
                        this.stopDetection();
                        this.setStage('ready-to-capture', messages.liveConfirmed, messages.readyHint);
                        this.scheduleAutoCapture();
                        return;
                    }
                    this.setStage('recenter-face', messages.holdStill, messages.finalCenterHint);
                    return;
                }
            } catch (error) {
                await this.reportClientError('liveness', error, { status: this.status, challenge_step: this.challengeStep });
                this.drawOverlay([], 'danger');
                this.setStage('error', messages.faceError, messages.livenessHint);
            } finally {
                this.isDetecting = false;
                if (!this.captureBusy && !this.livenessPassed) this.queueDetection();
            }
        },

        snapshotCanvas() {
            const video = this.$refs.video;
            const width = video.videoWidth;
            const height = video.videoHeight;
            if (!width || !height) throw new Error('Camera frame is not ready');
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
                        const detection = await this.withTimeout(
                            window.faceapi.detectSingleFace(snapshot, new window.faceapi.TinyFaceDetectorOptions(opts)).withFaceLandmarks(),
                            4000, 'face landmark extraction'
                        );
                        if (detection?.landmarks?.positions?.length === 68) {
                            return buildFaceGeometryDescriptor(detection.landmarks);
                        }
                    } catch (error) { lastError = error; }
                }
            }
            if (lastError) throw lastError;
            throw new Error(messages.descriptorFailed);
        },

        async capture({ manual = true } = {}) {
            if (!this.canCapture()) {
                if (manual) this.setStage('turn-face', messages.passChallenge, messages.turnBothSidesHint);
                return;
            }

            this.clearAutoCapture();
            this.captureBusy = true;
            this.stopDetection();
            this.setStage('saving', messages.capturingFrames, messages.readyHint);

            try {
                const snapshots = await this.captureSnapshots();
                const descriptor = await this.describeSnapshots(snapshots);

                if (!descriptor || descriptor.length !== 129 || descriptor[0] !== 2) {
                    throw new Error(messages.descriptorFailed);
                }

                this.setStage('saving', messages.savingFace, messages.readyHint);
                await this.$wire.call('saveFaceDescriptor', descriptor);
                this.cleanup();
            } catch (error) {
                await this.reportClientError('capture', error, { descriptor_mode: 'geometry' });
                this.captureBusy = false;
                this.resetLiveness(messages.descriptorFailed);
                this.startDetection();
                Swal.fire(
                    'Gagal Face ID',
                    `${messages.faceError}<br><br><small>Error: ${error?.message || error?.name || "Unknown error"}</small>`,
                    'error'
                );
            }
        },

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
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
            this.clearAutoCapture();
            this.stopDetection();
            this.stopStream();
            this.clearOverlay();
            this.captureBusy = false;
        },

        failHard(message, error) {
            this.cleanup();
            const isPermissionError = error?.name === 'NotAllowedError' || /permission denied/i.test(error?.message || '');
            const isDeviceUnavailable = error?.name === 'NotReadableError' || error?.name === 'NotFoundError';
            const actionableMessage = isPermissionError
                ? messages.cameraPermissionError
                : (isDeviceUnavailable ? messages.cameraUnavailableError : message);

            this.setStage('error', actionableMessage, messages.permissionHint);
            this.reportClientError('fatal', error, { user_message: actionableMessage });
        },
    };
}