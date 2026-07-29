import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);

        this.events = {
            click: { remove: 'removeClick' },
            change: { file: 'fileChange' },
        };

        this.file = this.$('file')[0];
        this.fileName = this.$('file-name')[0];
        this.previewImage = this.$('preview-image')[0];
        this.previewFrame = this.$('preview-frame')[0];
        this.placeholder = this.$('placeholder')[0];
        this.browse = this.$('browse')[0];
        this.remove = this.$('remove')[0];
        this.removeInput = this.$('remove-input')[0];
        this.focalXInput = this.$('focal-x')[0];
        this.focalYInput = this.$('focal-y')[0];
        this.zoomInput = this.$('zoom')[0];
        this.zoomControls = this.$('zoom-controls')[0];
        this.save = this.$('save')[0];
        this.notice = this.$('notice')[0];
        this.success = this.$('success')[0];
        this.error = this.$('error')[0];
        this.dropzone = this.$('dropzone')[0];
        this.objectUrl = '';
        this.messageTimer = null;
        this.savedUrl = this.previewImage && !this.previewImage.classList.contains('hidden') ? this.previewImage.src : '';
        this.focalX = parseFloat(this.focalXInput?.value || '50') || 50;
        this.focalY = parseFloat(this.focalYInput?.value || '50') || 50;
        this.zoomMax = Math.max(1, parseFloat(this.zoomInput?.max || '1') || 1);
        this.zoom = Math.max(1, Math.min(this.zoomMax, parseFloat(this.zoomInput?.value || '1') || 1));
        this.drag = null;
        this.fileToken = 0;
        this.dirty = false;

        this.addDropEvents();
        this.addBrowseEvents();
        this.addRepositionEvents();
        this.addZoomEvents();
        this.setFocal(this.focalX, this.focalY, false);
        this.setZoom(this.zoom, false);
        this.el.addEventListener('submit', (event) => this.submit(event));
        this.setDirty(false);
    }

    message(key) {
        return this.el.dataset[key] || '';
    }

    addBrowseEvents() {
        if (!this.file) return;

        [this.placeholder, this.browse].forEach((item) => {
            if (!item) return;

            item.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                this.file.click();
            });
        });
    }

    addDropEvents() {
        if (!this.dropzone || !this.file) return;

        ['dragenter', 'dragover'].forEach((eventName) => {
            this.dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                this.el.classList.add('dragging');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            this.dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                this.el.classList.remove('dragging');
            });
        });

        this.dropzone.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;

            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.file.files = transfer.files;
            this.fileChange();
        });
    }

    validateFile(file) {
        if (!file) return '';

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            return this.message('fileTypeError');
        }

        if (file.size > 2 * 1024 * 1024) {
            return this.message('fileSizeError');
        }

        return '';
    }

    loadImageMeta(src) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve({ width: image.naturalWidth, height: image.naturalHeight });
            image.onerror = reject;
            image.src = src;
        });
    }

    getMaxZoom(width, height) {
        return Math.max(1, Math.floor((Math.min(width, height) / 600) * 100) / 100);
    }

    addRepositionEvents() {
        if (!this.previewImage || !this.previewFrame) return;

        this.previewImage.addEventListener('pointerdown', (event) => {
            if (this.previewImage.classList.contains('hidden')) return;

            event.preventDefault();
            event.stopPropagation();
            this.drag = {
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
                focalX: this.focalX,
                focalY: this.focalY,
            };
            this.previewImage.setPointerCapture(event.pointerId);
        });

        this.previewImage.addEventListener('pointermove', (event) => {
            if (!this.drag || this.drag.pointerId !== event.pointerId) return;

            const rect = this.previewFrame.getBoundingClientRect();
            const deltaX = rect.width ? ((event.clientX - this.drag.startX) / rect.width) * 100 : 0;
            const deltaY = rect.height ? ((event.clientY - this.drag.startY) / rect.height) * 100 : 0;

            this.setFocal(this.drag.focalX - deltaX, this.drag.focalY - deltaY, true);
        });

        ['pointerup', 'pointercancel'].forEach((eventName) => {
            this.previewImage.addEventListener(eventName, (event) => {
                if (!this.drag || this.drag.pointerId !== event.pointerId) return;

                this.previewImage.releasePointerCapture(event.pointerId);
                this.drag = null;
            });
        });
    }

    addZoomEvents() {
        if (!this.zoomInput) return;

        this.zoomInput.addEventListener('input', () => {
            this.setZoom(this.zoomInput.value, true);
        });
    }

    clamp(value) {
        return Math.max(0, Math.min(100, value));
    }

    setFocal(x, y, markDirty = true) {
        this.focalX = this.clamp(x);
        this.focalY = this.clamp(y);

        if (this.focalXInput) this.focalXInput.value = this.focalX.toFixed(2);
        if (this.focalYInput) this.focalYInput.value = this.focalY.toFixed(2);
        if (this.previewImage) {
            this.previewImage.style.objectPosition = `${this.focalX}% ${this.focalY}%`;
            this.previewImage.style.transformOrigin = `${this.focalX}% ${this.focalY}%`;
        }
        if (markDirty) this.setDirty(true);
    }

    setZoomMax(max) {
        this.zoomMax = Math.max(1, parseFloat(max || '1') || 1);
        if (this.zoomInput) this.zoomInput.max = this.zoomMax.toFixed(2);
        if (this.zoom > this.zoomMax) this.setZoom(this.zoomMax, false);
        this.updateZoomFill();
    }

    setZoom(value, markDirty = true) {
        this.zoom = Math.max(1, Math.min(this.zoomMax, parseFloat(value || '1') || 1));

        if (this.zoomInput) this.zoomInput.value = this.zoom.toFixed(2);
        if (this.previewImage) this.previewImage.style.transform = `scale(${this.zoom})`;
        this.updateZoomFill();
        if (markDirty) this.setDirty(true);
    }

    updateZoomFill() {
        if (!this.zoomInput) return;

        const range = this.zoomMax - 1;
        const fill = range > 0 ? ((this.zoom - 1) / range) * 100 : 0;
        this.zoomInput.style.setProperty('--image-uploader-zoom-fill', `${fill}%`);
    }

    setImageControls(hasImage) {
        [this.remove, this.browse, this.zoomControls].forEach((item) => {
            if (item) item.classList.toggle('hidden', !hasImage);
        });
    }

    setDirty(isDirty) {
        this.dirty = Boolean(isDirty);
        if (this.save) this.save.disabled = !this.dirty;
    }

    setPreview(src) {
        if (!this.previewImage || !this.placeholder) return;

        this.previewImage.src = src || '';
        this.previewImage.classList.toggle('hidden', !src);
        this.placeholder.classList.toggle('hidden', Boolean(src));
    }

    setMessage(element, message) {
        window.clearTimeout(this.messageTimer);

        [this.notice, this.success, this.error].forEach((item) => {
            if (item) item.hidden = true;
        });

        if (!element) return;

        element.textContent = message || '';
        element.hidden = false;
        this.messageTimer = window.setTimeout(function () {
            element.hidden = true;
        }, 3500);
    }

    revokeObjectUrl() {
        if (!this.objectUrl) return;

        URL.revokeObjectURL(this.objectUrl);
        this.objectUrl = '';
    }

    async fileChange() {
        if (!this.file) return;

        const file = this.file.files?.[0];
        const error = this.validateFile(file);
        const token = ++this.fileToken;
        this.revokeObjectUrl();

        if (error) {
            this.file.value = '';
            if (this.fileName) this.fileName.textContent = '';
            this.setMessage(this.error, error);
            this.setDirty(Boolean(this.savedUrl && this.removeInput?.value === '1'));
            return;
        }

        if (!file) {
            if (this.fileName) this.fileName.textContent = '';
            this.setDirty(Boolean(this.savedUrl && this.removeInput?.value === '1'));
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        let meta;
        try {
            meta = await this.loadImageMeta(objectUrl);
        } catch (err) {
            URL.revokeObjectURL(objectUrl);
            this.file.value = '';
            if (this.fileName) this.fileName.textContent = '';
            this.setMessage(this.error, this.message('imageLoadError'));
            return;
        }

        if (token !== this.fileToken) {
            URL.revokeObjectURL(objectUrl);
            return;
        }

        if (Math.min(meta.width, meta.height) < 600) {
            URL.revokeObjectURL(objectUrl);
            this.file.value = '';
            if (this.fileName) this.fileName.textContent = '';
            this.setMessage(this.error, this.message('imageDimensionsError'));
            return;
        }

        this.objectUrl = objectUrl;
        if (this.fileName) this.fileName.textContent = file.name;
        if (this.removeInput) this.removeInput.value = '0';
        this.setZoomMax(this.getMaxZoom(meta.width, meta.height));
        this.setZoom(1, false);
        this.setFocal(50, 50, false);
        this.setPreview(this.objectUrl);
        this.setImageControls(true);
        this.setDirty(true);
        this.setMessage(null, '');
    }

    removeClick(e) {
        e.preventDefault();

        const shouldRemoveSavedImage = Boolean(this.savedUrl);

        if (this.file) this.file.value = '';
        if (this.fileName) this.fileName.textContent = '';
        if (this.removeInput) this.removeInput.value = shouldRemoveSavedImage ? '1' : '0';
        this.revokeObjectUrl();
        this.setZoomMax(1);
        this.setZoom(1, false);
        this.setFocal(50, 50, false);
        this.setPreview('');
        this.setImageControls(false);
        this.setDirty(shouldRemoveSavedImage);

        if (shouldRemoveSavedImage) {
            this.setMessage(this.notice, this.message('removeNotice'));
            return;
        }

        this.setMessage(null, '');
    }

    submit(e) {
        e.preventDefault();
        if (!this.dirty) return;

        const file = this.file?.files?.[0];
        const error = this.validateFile(file);
        if (error) {
            this.setMessage(this.error, error);
            return;
        }

        const data = new FormData(this.el);
        data.set('action', this.el.dataset.action || '');
        data.set('nonce', this.el.dataset.nonce || '');

        this.el.classList.add('loading');
        this.setMessage(null, '');

        fetch(this.el.dataset.ajaxUrl || '/wp-admin/admin-ajax.php', { method: 'POST', body: data, credentials: 'same-origin' })
            .then((response) => response.text())
            .then((text) => {
                let response;
                try {
                    response = JSON.parse(text);
                } catch (error) {
                    throw new Error(this.message('saveError'));
                }

                return response;
            })
            .then((response) => {
                if (!response.success) throw new Error(response.data?.message || this.message('updateError'));

                const image = response.data?.image || {};
                const imageUrl = image.preview_url || image.url || '';
                if (this.file) this.file.value = '';
                if (this.fileName) this.fileName.textContent = image.name || '';
                if (this.removeInput) this.removeInput.value = '0';
                this.setPreview(imageUrl);
                this.setZoomMax(parseFloat(image.zoom_max || '1') || 1);
                this.setZoom(parseFloat(image.zoom || '1') || 1, false);
                this.setFocal(parseFloat(image.focal_x || '50') || 50, parseFloat(image.focal_y || '50') || 50, false);
                this.setImageControls(Boolean(imageUrl));
                this.savedUrl = imageUrl;
                this.setDirty(false);
                this.setMessage(this.success, response.data?.message || this.message('successMessage'));
            })
            .catch((err) => {
                this.setMessage(this.error, err.message);
            })
            .finally(() => {
                this.el.classList.remove('loading');
            });
    }
}
