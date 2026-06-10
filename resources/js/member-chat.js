document.addEventListener('alpine:init', () => {
    Alpine.data('memberChatPanel', (config) => ({
        messagesUrl: config.messagesUrl,
        sendUrl: config.sendUrl,
        csrf: config.csrf,
        labels: config.labels,
        messages: [],
        draft: config.prefill || '',
        pendingImage: null,
        sending: false,
        loading: true,
        pollTimer: null,

        init() {
            this.loadMessages();
            this.pollTimer = setInterval(() => this.loadMessages(true), 8000);
        },

        scrollBottom() {
            if (this.$refs.thread) {
                this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight;
            }
        },

        async loadMessages(silent = false) {
            if (!silent) {
                this.loading = true;
            }
            try {
                const res = await fetch(this.messagesUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                this.messages = data.messages || [];
                if (!silent) {
                    this.$nextTick(() => this.scrollBottom());
                }
            } finally {
                this.loading = false;
            }
        },

        onImagePick(e) {
            const file = e.target.files?.[0];
            if (file) {
                this.pendingImage = file;
            }
            e.target.value = '';
        },

        async send() {
            if (this.sending) {
                return;
            }
            if (!this.draft.trim() && !this.pendingImage) {
                return;
            }
            this.sending = true;
            const form = new FormData();
            if (this.draft.trim()) {
                form.append('body', this.draft.trim());
            }
            if (this.pendingImage) {
                form.append('image', this.pendingImage);
            }
            try {
                const res = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body: form,
                });
                if (res.ok) {
                    this.draft = '';
                    this.pendingImage = null;
                    await this.loadMessages();
                }
            } finally {
                this.sending = false;
            }
        },
    }));
});
