import { chatComposerState } from './chat-composer';

export function registerMemberChatComponents(Alpine) {
    Alpine.data('memberChatPanel', (config) => ({
        ...chatComposerState(),
        messagesUrl: config.messagesUrl,
        sendUrl: config.sendUrl,
        csrf: config.csrf,
        welcomeMessage: config.welcomeMessage ?? null,
        labels: config.labels ?? {},
        messages: [],
        draft: config.prefill || '',
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
            if (! silent) {
                this.loading = true;
            }
            try {
                const res = await fetch(this.messagesUrl, { headers: { Accept: 'application/json' } });
                if (! res.ok) {
                    return;
                }
                const data = await res.json();
                this.messages = data.messages || [];
                if (! silent) {
                    this.$nextTick(() => this.scrollBottom());
                }
            } finally {
                this.loading = false;
            }
        },

        onComposerKeydown(event) {
            this.handleComposerKeydown(event, () => this.send());
        },

        async send() {
            if (this.sending) {
                return;
            }
            if (! this.draft.trim() && ! this.pendingImage) {
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
                    this.clearPendingImage();
                    await this.loadMessages();
                }
            } finally {
                this.sending = false;
            }
        },
    }));

    Alpine.data('guestChatWidget', (config) => ({
        ...chatComposerState(),
        messagesUrl: config.messagesUrl,
        sendUrl: config.sendUrl,
        brand: config.brand,
        supportTitle: config.supportTitle,
        supportAvatarUrl: config.supportAvatarUrl,
        welcomeMessage: config.welcomeMessage ?? null,
        csrf: config.csrf,
        labels: config.labels ?? {},
        isOpen: false,
        messages: [],
        draft: '',
        guestLabel: '',
        sending: false,
        pollTimer: null,

        init() {
            if (config.openOnLoad || config.forgotContext) {
                this.open({ forgot: config.forgotContext });
            }
            this.pollTimer = setInterval(() => {
                if (this.isOpen) {
                    this.loadMessages(true);
                }
            }, 8000);
        },

        async open(detail = null) {
            const withForgotIntro = detail?.forgot === true;
            this.isOpen = true;
            await this.loadMessages();
            if (withForgotIntro && ! this.messages.length) {
                this.draft = this.labels.forgot_password_intro ?? '';
            }
            this.$nextTick(() => this.scrollBottom());
        },

        close() {
            this.isOpen = false;
        },

        scrollBottom() {
            if (this.$refs.thread) {
                this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight;
            }
        },

        async loadMessages(silent = false) {
            const res = await fetch(this.messagesUrl, { headers: { Accept: 'application/json' } });
            if (! res.ok) {
                return;
            }
            const data = await res.json();
            this.messages = data.messages || [];
            if (! silent) {
                this.$nextTick(() => this.scrollBottom());
            }
        },

        onComposerKeydown(event) {
            this.handleComposerKeydown(event, () => this.send());
        },

        async send() {
            if (this.sending) {
                return;
            }
            if (! this.draft.trim() && ! this.pendingImage) {
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
            if (this.guestLabel.trim()) {
                form.append('guest_label', this.guestLabel.trim());
            }
            try {
                const res = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body: form,
                });
                if (res.ok) {
                    this.draft = '';
                    this.clearPendingImage();
                    await this.loadMessages();
                }
            } finally {
                this.sending = false;
            }
        },
    }));
}

document.addEventListener('alpine:init', () => {
    registerMemberChatComponents(window.Alpine);
});
