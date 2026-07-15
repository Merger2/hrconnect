import * as faceapi from 'face-api.js';

const MODEL_URL = '/models/av1';

const requiredNets = [
    faceapi.nets.tinyFaceDetector,
    faceapi.nets.faceLandmark68Net,
];

let backendRegistered = false;

export async function loadFaceModels() {
    // Register tfjs backend (CPU fallback) before loading models
    if (!backendRegistered) {
        try {
            await Promise.all([
                import('@tensorflow/tfjs-backend-cpu'),
                import('@tensorflow/tfjs-backend-webgl'),
            ]);
            // tfjs auto-registers CPU backend when imported
            backendRegistered = true;
        } catch (e) {
            console.warn('[face-recognition] tfjs backend registration failed:', e);
        }
    }

    const loaded = await Promise.allSettled(
        requiredNets.map(net => net.loadFromUri(MODEL_URL))
    );
    const failed = loaded.filter(r => r.status === 'rejected');
    if (failed.length > 0) {
        throw new Error(
            'Gagal memuat model wajah: ' +
            failed.map(r => r.reason?.message || 'unknown').join('; ')
        );
    }
}

export function computeEAR(landmarks) {
    const leftEye = landmarks.getLeftEye();
    const rightEye = landmarks.getRightEye();

    const leftEAR = (
        dist(leftEye[1], leftEye[5]) + dist(leftEye[2], leftEye[4])
    ) / (2 * dist(leftEye[0], leftEye[3]));

    const rightEAR = (
        dist(rightEye[1], rightEye[5]) + dist(rightEye[2], rightEye[4])
    ) / (2 * dist(rightEye[0], rightEye[3]));

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