async function shrinkChatImage(file) {
    if (typeof createImageBitmap !== 'function') {
        return file;
    }

    let bitmap;
    try {
        bitmap = await createImageBitmap(file);
    } catch {
        return file;
    }

    const maxEdge = 800;
    const edge = Math.max(bitmap.width, bitmap.height);
    const scale = edge > maxEdge ? maxEdge / edge : 1;
    const width = Math.max(1, Math.round(bitmap.width * scale));
    const height = Math.max(1, Math.round(bitmap.height * scale));
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d');
    context.fillStyle = '#fff';
    context.fillRect(0, 0, width, height);
    context.drawImage(bitmap, 0, 0, width, height);
    bitmap.close?.();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.6));
    if (!blob) {
        return file;
    }

    return new File([blob], 'chat.jpg', { type: 'image/jpeg' });
}

export function chatComposerState() {
    return {
        pendingImage: null,
        pendingImagePreview: null,

        isDesktopComposer() {
            return window.matchMedia('(min-width: 768px)').matches;
        },

        handleComposerKeydown(event, submit) {
            if (!this.isDesktopComposer() || event.key !== 'Enter' || event.shiftKey) {
                return;
            }

            event.preventDefault();
            submit();
        },

        async onImagePick(event) {
            this.clearPendingImage();
            const file = event.target.files?.[0];
            event.target.value = '';

            if (!file) {
                return;
            }

            const shrunk = await shrinkChatImage(file);
            this.pendingImage = shrunk;
            this.pendingImagePreview = URL.createObjectURL(shrunk);
        },

        clearPendingImage() {
            if (this.pendingImagePreview) {
                URL.revokeObjectURL(this.pendingImagePreview);
            }

            this.pendingImagePreview = null;
            this.pendingImage = null;
        },
    };
}
