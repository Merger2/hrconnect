import * as faceapi from 'face-api.js';

const MODEL_URL = '/models/av1';

// All face detection and recognition models needed for the pipeline
const requiredNets = [
    faceapi.nets.tinyFaceDetector,
    faceapi.nets.faceLandmark68Net,
    faceapi.nets.faceRecognitionNet,
    faceapi.nets.faceLandmark68TinyNet,
];

let backendInitialized = false;
let backendReadyPromise = null;

// Robustly preload TensorFlow.js runtime with minimal dependencies
async function ensureBackendReady() {
    if (backendInitialized) return Promise.resolve();
    if (backendReadyPromise) return await backendReadyPromise;

    backendReadyPromise = (async () => {
        try {
            // Load TensorFlow.js backends. They auto-register on import.
            await Promise.all([
                import('@tensorflow/tfjs-backend-cpu'),
                import('@tensorflow/tfjs-backend-webgl'),
            ]);

            // Pick the best available backend and activate it.
            if (faceapi.tf) {
                const hasWebgl = faceapi.tf.engine()?.registry?.['webgl'] !== undefined;
                const preferred = hasWebgl ? 'webgl' : 'cpu';
                try {
                    await faceapi.tf.setBackend(preferred);
                    await faceapi.tf.ready();
                } catch (be) {
                    console.warn('[face-recognition] setBackend(' + preferred + ') failed, falling back to cpu:', be);
                    await faceapi.tf.setBackend('cpu');
                    await faceapi.tf.ready();
                }
            }
            backendInitialized = true;
        } catch (e) {
            console.warn('[face-recognition] Backend registration failed:', e);
            // Last-resort: try CPU backend directly so tensor ops still work.
            try {
                if (faceapi.tf) {
                    await faceapi.tf.setBackend('cpu');
                    await faceapi.tf.ready();
                }
            } catch (_) { /* no backend available */ }
            backendInitialized = true;
        }
    })();
    return await backendReadyPromise;
}

// Load all face models with robust error handling
export async function loadFaceModels() {
    await ensureBackendReady();

    const loaded = await Promise.allSettled(
        requiredNets.map(net => net.loadFromUri(MODEL_URL))
    );
    const failed = loaded.filter(r => r.status === 'rejected');
    if (failed.length > 0) {
        throw new Error('Gagal memuat model wajah: ' + failed.map(r => r.reason?.message || 'unknown').join('; '));
    }
}

// Utility functions for facial landmark and descriptor calculations
export function computeEAR(landmarks) {
    const leftEye = landmarks.getLeftEye();
    const rightEye = landmarks.getRightEye();
    const leftEAR = (dist(leftEye[1], leftEye[5]) + dist(leftEye[2], leftEye[4])) / (2 * dist(leftEye[0], leftEye[3]));
    const rightEAR = (dist(rightEye[1], rightEye[5]) + dist(rightEye[2], rightEye[4])) / (2 * dist(rightEye[0], rightEye[3]));
    return (leftEAR + rightEAR) / 2;
}

function dist(a, b) {
    return Math.sqrt((a.x - b.x) ** 2 + (a.y - b.y) ** 2);
}

export function computeDescriptorVariance(descriptors) {
    if (descriptors.length < 2) return 0;
    const n = descriptors.length;
    const dim = descriptors[0].length;
    let sum = 0;
    let count = 0;
    for (let i = 0; i < n; i++) {
        for (let j = i + 1; j < n; j++) {
            let d = 0;
            for (let k = 0; k < dim; k++) {
                d += (descriptors[i][k] - descriptors[j][k]) ** 2;
            }
            sum += Math.sqrt(d);
            count++;
        }
    }
    return sum / count;
}

export function euclideanDistance(a, b) {
    let sum = 0;
    for (let i = 0; i < a.length; i++) {
        sum += (a[i] - b[i]) ** 2;
    }
    return Math.sqrt(sum);
}

export { faceapi, MODEL_URL };