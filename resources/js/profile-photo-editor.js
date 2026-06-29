export default (config = {}) => ({
    currentPhotoUrl: config.initialPhotoUrl || null,
    uploadError: null,
    uploading: false,
    cropModalOpen: false,
    zoom: 1.1,
    offsetX: 0,
    offsetY: 0,
    baseScale: 1,
    image: null,
    dragging: false,
    dragStartX: 0,
    dragStartY: 0,
    dragOffsetX: 0,
    dragOffsetY: 0,

    handleFileChange(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) {
            this.uploadError = 'Format file harus PNG atau JPG.';
            return;
        }
        this.uploadError = null;

        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                this.image = img;
                this.resetCropState();
                this.cropModalOpen = true;
                this.$nextTick(() => this.renderCanvas());
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    },

    resetCropState() {
        if (!this.image) return;
        const canvasSize = 320;
        this.baseScale = Math.max(canvasSize / this.image.width, canvasSize / this.image.height);
        this.zoom = 1.1;
        this.offsetX = 0;
        this.offsetY = 0;
    },

    renderCanvas() {
        const canvas = this.$refs.cropCanvas;
        if (!canvas || !this.image) return;
        const ctx = canvas.getContext('2d');
        const size = canvas.width;
        ctx.clearRect(0, 0, size, size);

        const scale = this.baseScale * this.zoom;
        const drawW = this.image.width * scale;
        const drawH = this.image.height * scale;
        const cx = size / 2 + this.offsetX;
        const cy = size / 2 + this.offsetY;

        ctx.save();
        ctx.beginPath();
        ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(this.image, cx - drawW / 2, cy - drawH / 2, drawW, drawH);
        ctx.restore();
    },

    startDrag(event) {
        const p = event.targetTouches?.[0] || event;
        this.dragging = true;
        this.dragStartX = p.clientX;
        this.dragStartY = p.clientY;
        this.dragOffsetX = this.offsetX;
        this.dragOffsetY = this.offsetY;
        event.preventDefault();
    },

    onDrag(event) {
        if (!this.dragging) return;
        const p = event.targetTouches?.[0] || event;
        this.offsetX = this.dragOffsetX + (p.clientX - this.dragStartX);
        this.offsetY = this.dragOffsetY + (p.clientY - this.dragStartY);
        this.renderCanvas();
    },

    stopDrag() {
        this.dragging = false;
    },

    saveCroppedPhoto() {
        const canvas = this.$refs.cropCanvas;
        if (!canvas) return;
        this.uploading = true;

        canvas.toBlob((blob) => {
            const file = new File([blob], 'profile-photo.jpg', { type: 'image/jpeg', lastModified: Date.now() });
            this.$wire.upload('photo', file, () => {
                this.cropModalOpen = false;
                this.uploading = false;
            }, () => {
                this.uploadError = 'Gagal mengupload foto.';
                this.uploading = false;
            });
        }, 'image/jpeg', 0.92);
    },
});
