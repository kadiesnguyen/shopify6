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

        onImagePick(event) {
            this.clearPendingImage();
            const file = event.target.files?.[0];

            if (file) {
                this.pendingImage = file;
                this.pendingImagePreview = URL.createObjectURL(file);
            }

            event.target.value = '';
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
