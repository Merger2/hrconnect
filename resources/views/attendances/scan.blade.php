<x-app-layout>
    <div class="user-page-shell scan-attendance-page" x-data="scanFaceCapture()"
         @begin-scan-capture.window="beginCapture($event.detail.action)">
        <div class="user-page-container user-page-container--wide">
            <section aria-labelledby="scan-attendance-title" class="user-page-surface">
                <x-user.page-header
                    :back-href="route('home')"
                    :title="__('Clock In')"
                    title-id="scan-attendance-title"
                    module="attendance"
                    class="border-b-0">
                    <x-slot name="icon">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </x-slot>
                </x-user.page-header>

                <div class="user-page-body">
                    <livewire:user.clock-in-action />
                </div>
            </section>
        </div>

        {{-- Face verification camera overlay --}}
        <div x-show="showCamera"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[90] flex flex-col bg-black/90"
             role="dialog"
             aria-modal="true"
             aria-labelledby="face-capture-title"
             x-trap.inert.noscroll="showCamera">
            <div class="flex items-center justify-between px-4 py-3">
                <h2 id="face-capture-title" class="text-lg font-bold text-white">
                    <span x-text="captureAction === 'clock_in' ? '{{ __('Face Verification') }}' : '{{ __('Face Verification') }}'"></span>
                </h2>
                <button type="button"
                        @click="cancelCapture()"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>
            </div>

            <div class="relative flex flex-1 items-center justify-center px-6 pb-20">
                <div class="relative mx-auto overflow-hidden rounded-3xl bg-black shadow-2xl"
                     style="max-width: 400px; aspect-ratio: 3/4; width: 100%;">
                    <video x-ref="video" autoplay playsinline muted
                           class="absolute inset-0 h-full w-full object-cover"></video>
                    <canvas x-ref="overlay" class="absolute inset-0 h-full w-full"></canvas>

                    {{-- Guide overlay --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="relative h-48 w-40">
                            <div class="absolute inset-0 rounded-2xl border-2 border-dashed"
                                 :class="faceDetected ? 'border-emerald-400' : 'border-white/40'">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status indicator --}}
                <div class="absolute bottom-32 left-1/2 -translate-x-1/2">
                    <div class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium shadow-lg backdrop-blur-sm"
                         :class="statusClass"
                         role="status" aria-live="polite">
                        <template x-if="captureStatus === 'loading'">
                            <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
                        </template>
                        <template x-if="captureStatus === 'success'">
                            <x-heroicon-o-check-circle class="h-4 w-4" />
                        </template>
                        <template x-if="captureStatus === 'error'">
                            <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                        </template>
                        <span x-text="statusText"></span>
                    </div>
                </div>

                {{-- Manual capture button (fallback) --}}
                <div class="absolute bottom-16 left-1/2 -translate-x-1/2">
                    <button @click="manualCapture()"
                            x-show="captureStatus === 'ready'"
                            x-cloak
                            class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-xl transition-transform hover:scale-105 active:scale-95 focus:outline-none focus:ring-4 focus:ring-white/50"
                            aria-label="{{ __('Capture') }}">
                        <div class="h-14 w-14 rounded-full border-2 border-gray-300 flex items-center justify-center">
                            <div class="h-10 w-10 rounded-full bg-gray-200"></div>
                        </div>
                    </button>
                </div>
            </div>

            {{-- Error retry --}}
            <div x-show="captureStatus === 'error'" x-cloak class="absolute bottom-4 left-1/2 -translate-x-1/2">
                <button @click="startCamera()"
                        class="rounded-full bg-white/20 px-6 py-2 text-sm font-semibold text-white backdrop-blur-sm hover:bg-white/30 transition-colors">
                    {{ __('Try Again') }}
                </button>
            </div>
        </div>

        {{-- Hidden event bridge — dispatches to the scanFaceCapture() Alpine component --}}
        <div x-data="{}"
             @trigger-face-capture.window="
                 $nextTick(() => {
                     // Dispatch to the root Alpine component (scanFaceCapture)
                     window.dispatchEvent(new CustomEvent('begin-scan-capture', {
                         detail: { action: $event.detail.action }
                     }));
                 });
             "
             @face-captured.window="
                 if (window.__faceTimeoutTimer) {
                     clearTimeout(window.__faceTimeoutTimer);
                     window.__faceTimeoutTimer = null;
                 }
             "
             aria-hidden="true"
             class="hidden"
        ></div>
    </div>

    @push('scripts')
    <script src="/assets/js/face-api.min.js"></script>
    <script>
        function scanFaceCapture() {
            return {
                showCamera: false,
                captureAction: 'clock_in',
                captureStatus: 'idle', // idle, loading, detecting, ready, success, error
                statusText: '',
                faceDetected: false,
                stream: null,
                modelLoaded: false,
                detectionTimer: null,
                _autoCaptureTimer: null,

                get statusClass() {
                    switch (this.captureStatus) {
                        case 'loading': return 'bg-slate-800/80 text-slate-200';
                        case 'detecting': return 'bg-amber-800/80 text-amber-200';
                        case 'ready': return 'bg-emerald-800/80 text-emerald-200';
                        case 'success': return 'bg-emerald-800/80 text-emerald-200';
                        case 'error': return 'bg-red-800/80 text-red-200';
                        default: return 'bg-slate-800/80 text-slate-200';
                    }
                },

                // Called from hidden event bridge
                async beginCapture(action) {
                    this.captureAction = action;
                    this.showCamera = true;
                    this.captureStatus = 'loading';
                    this.statusText = '{{ __('Preparing camera...') }}';
                    this.faceDetected = false;

                    try {
                        await this.loadModels();
                        await this.startCamera();
                        this.captureStatus = 'detecting';
                        this.statusText = '{{ __('Center your face in the guide') }}';
                        this.startDetection();
                    } catch (e) {
                        console.error('Face capture init error:', e);
                        this.captureStatus = 'error';
                        this.statusText = '{{ __('Kamera tidak tersedia. Silakan coba lagi, atau ajukan koreksi absensi ke HRD.') }}';
                    }
                },

                async loadModels() {
                    if (this.modelLoaded) return;
                    if (typeof faceapi === 'undefined') throw new Error('face-api.js not loaded');

                    const modelUrl = window.resolveRuntimeAssetUrl
                        ? window.resolveRuntimeAssetUrl('/models')
                        : window.location.origin + '/models';

                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
                        faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
                    ]);

                    this.modelLoaded = true;
                },

                async startCamera() {
                    const video = this.$refs.video;
                    if (!video) throw new Error('Video element not ready');

                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                    }

                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'user',
                            width: { ideal: 320 },
                            height: { ideal: 240 },
                            frameRate: { ideal: 15 },
                        },
                    });

                    video.srcObject = this.stream;
                    await new Promise((resolve) => {
                        if (video.readyState >= 2) { resolve(); return; }
                        video.onloadeddata = () => resolve();
                    });
                    await video.play();
                },

                startDetection() {
                    this.stopDetection();
                    this.detectionTimer = setTimeout(() => this.detect(), 300);
                },

                stopDetection() {
                    if (this.detectionTimer) {
                        clearTimeout(this.detectionTimer);
                        this.detectionTimer = null;
                    }
                },

                async detect() {
                    const video = this.$refs.video;
                    if (!video || !this.stream || !video.videoWidth) {
                        this.detectionTimer = setTimeout(() => this.detect(), 300);
                        return;
                    }

                    try {
                        const options = new faceapi.TinyFaceDetectorOptions({
                            inputSize: 160,
                            scoreThreshold: 0.5,
                        });

                        const detection = await faceapi
                            .detectSingleFace(video, options)
                            .withFaceLandmarks();

                        this.drawOverlay(detection);

                        if (detection) {
                            const box = detection.detection.box;
                            const videoWidth = video.videoWidth;
                            const minSize = videoWidth * 0.2;

                            if (box.width >= minSize) {
                                this.faceDetected = true;
                                if (this.captureStatus !== 'ready') {
                                    this.captureStatus = 'ready';
                                    this.statusText = '{{ __('Face detected!') }}';                    // Auto-capture after 500ms of stable detection
                    if (this._autoCaptureTimer) clearTimeout(this._autoCaptureTimer);
                    this._autoCaptureTimer = setTimeout(() => {
                        // Re-check detection state before capturing
                        if (this.captureStatus === 'ready' && this.faceDetected) {
                            this.captureFace(detection);
                        }
                    }, 500);
                                }
                            } else {
                                this.faceDetected = false;
                                this.statusText = '{{ __('Move closer to camera') }}';
                            }
                        } else {
                            this.faceDetected = false;
                            this.captureStatus = 'detecting';
                            this.statusText = '{{ __('Center your face in the guide') }}';
                        }

                        this.detectionTimer = setTimeout(() => this.detect(), 200);
                    } catch (e) {
                        console.error('Detection error:', e);
                        this.detectionTimer = setTimeout(() => this.detect(), 500);
                    }
                },

                drawOverlay(detection) {
                    const canvas = this.$refs.overlay;
                    const video = this.$refs.video;
                    if (!canvas || !video || !video.videoWidth) return;

                    canvas.width = canvas.offsetWidth;
                    canvas.height = canvas.offsetHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (detection) {
                        const box = detection.detection.box;
                        const scaleX = canvas.width / video.videoWidth;
                        const scaleY = canvas.height / video.videoHeight;

                        ctx.strokeStyle = this.faceDetected
                            ? window.cssVar('--color-emerald-400')
                            : window.cssVar('--color-amber-400');
                        ctx.lineWidth = 2;
                        ctx.strokeRect(
                            box.x * scaleX,
                            box.y * scaleY,
                            box.width * scaleX,
                            box.height * scaleY,
                        );
                    }
                },

                captureFace(detection) {
                    if (!detection || !detection.landmarks) return;

                    this.captureStatus = 'success';
                    this.statusText = '{{ __('Face captured!') }}';
                    this.stopDetection();

                    // Build geometry descriptor from landmarks
                    const landmarks = detection.landmarks;
                    const descriptor = this.buildGeometryDescriptor(landmarks);

                    // Stop camera
                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                        this.stream = null;
                    }

                    // Dispatch to ClockInAction
                    setTimeout(() => {
                        this.showCamera = false;
                        window.dispatchEvent(new CustomEvent('face-captured', {
                            detail: {
                                descriptor: descriptor,
                                action: this.captureAction,
                            },
                        }));
                    }, 400);
                },

                buildGeometryDescriptor(landmarks) {
                    const leftEyeCenter = this.averagePoint(landmarks.getLeftEye());
                    const rightEyeCenter = this.averagePoint(landmarks.getRightEye());
                    const eyeMidX = (leftEyeCenter.x + rightEyeCenter.x) / 2;
                    const eyeMidY = (leftEyeCenter.y + rightEyeCenter.y) / 2;
                    const eyeDistance = Math.max(this.pointDistance(leftEyeCenter, rightEyeCenter), 1);
                    const roll = Math.atan2(
                        rightEyeCenter.y - leftEyeCenter.y,
                        rightEyeCenter.x - leftEyeCenter.x
                    );
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
                },

                averagePoint(points) {
                    const total = points.reduce((c, p) => ({ x: c.x + p.x, y: c.y + p.y }), { x: 0, y: 0 });
                    return { x: total.x / points.length, y: total.y / points.length };
                },

                pointDistance(a, b) {
                    return Math.hypot(a.x - b.x, a.y - b.y);
                },

                manualCapture() {
                    // Re-run detection one more time and capture
                    this.captureStatus = 'loading';
                    this.statusText = '{{ __('Capturing...') }}';

                    const video = this.$refs.video;
                    if (!video) return;

                    const options = new faceapi.TinyFaceDetectorOptions({
                        inputSize: 224,
                        scoreThreshold: 0.3,
                    });

                    faceapi.detectSingleFace(video, options).withFaceLandmarks()
                        .then(detection => {
                            if (detection) {
                                this.captureFace(detection);
                            } else {
                                this.captureStatus = 'ready';
                                this.statusText = '{{ __('No face detected. Try again.') }}';
                            }
                        })
                        .catch(() => {
                            this.captureStatus = 'error';
                            this.statusText = '{{ __('Capture failed.') }}';
                        });
                },

                cancelCapture() {
                    this.stopDetection();
                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                        this.stream = null;
                    }
                    this.showCamera = false;
                    this.captureStatus = 'idle';
                    this.captureAction = 'clock_in';
                    this.statusText = '';

                    // Face-only: cancel = batalkan saja (tanpa fallback PIN).
                    // Timer face-verification-timeout dari ClockInAction yang
                    // menampilkan pesan error jika verifikasi tidak selesai.
                },
            };
        }                // No bridge needed — Alpine @begin-scan-capture.window handles it
    </script>
    @endpush
</x-app-layout>
