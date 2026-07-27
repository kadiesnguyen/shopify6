import { chatComposerState } from './chat-composer';

function chatThreadHelpers() {
    return {
        hasMoreOlder: false,
        loadingOlder: false,
        pageSize: 40,

        isNearBottom(threshold = 96) {
            const el = this.$refs.thread;
            if (!el) {
                return true;
            }

            return el.scrollHeight - el.scrollTop - el.clientHeight < threshold;
        },

        scrollBottom() {
            if (this.$refs.thread) {
                this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight;
            }
        },

        messagesUrlWith(params = {}) {
            const url = new URL(this.messagesUrl, window.location.origin);
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    url.searchParams.set(key, String(value));
                }
            });

            return url.toString();
        },

        mergeUniqueById(existing, incoming, prepend = false) {
            const known = new Set(existing.map((m) => m.id));
            const unique = incoming.filter((m) => !known.has(m.id));
            if (!unique.length) {
                return existing;
            }

            return prepend ? [...unique, ...existing] : [...existing, ...unique];
        },

        async loadMessages(silent = false) {
            if (!silent) {
                this.loading = true;
            }
            try {
                if (silent && this.messages.length) {
                    await this.pollNewer();
                    return;
                }

                const res = await fetch(this.messagesUrlWith({ limit: this.pageSize }), {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                this.messages = data.messages || [];
                this.hasMoreOlder = Boolean(data.has_more);
                if (!silent) {
                    this.$nextTick(() => this.scrollBottom());
                }
            } finally {
                this.loading = false;
            }
        },

        async pollNewer() {
            const afterId = this.messages.length
                ? this.messages[this.messages.length - 1].id
                : null;
            const res = await fetch(
                this.messagesUrlWith({ after_id: afterId, limit: this.pageSize }),
                { headers: { Accept: 'application/json' } },
            );
            if (!res.ok) {
                return;
            }
            const data = await res.json();
            const newer = data.messages || [];
            if (!newer.length) {
                return;
            }

            const stickToBottom = this.isNearBottom();
            this.messages = this.mergeUniqueById(this.messages, newer);
            if (stickToBottom) {
                this.$nextTick(() => this.scrollBottom());
            }
        },

        async loadOlder() {
            if (this.loadingOlder || !this.hasMoreOlder || !this.messages.length) {
                return;
            }

            this.loadingOlder = true;
            const el = this.$refs.thread;
            const prevHeight = el ? el.scrollHeight : 0;
            const prevTop = el ? el.scrollTop : 0;
            const beforeId = this.messages[0].id;

            try {
                const res = await fetch(
                    this.messagesUrlWith({ before_id: beforeId, limit: this.pageSize }),
                    { headers: { Accept: 'application/json' } },
                );
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                const older = data.messages || [];
                this.hasMoreOlder = Boolean(data.has_more);
                if (!older.length) {
                    return;
                }

                this.messages = this.mergeUniqueById(this.messages, older, true);
                this.$nextTick(() => {
                    if (el) {
                        el.scrollTop = prevTop + (el.scrollHeight - prevHeight);
                    }
                });
            } finally {
                this.loadingOlder = false;
            }
        },

        onThreadScroll() {
            const el = this.$refs.thread;
            if (!el || this.loadingOlder || !this.hasMoreOlder) {
                return;
            }
            if (el.scrollTop < 80) {
                this.loadOlder();
            }
        },
    };
}

export function registerMemberChatComponents(Alpine) {
    Alpine.data('memberChatPanel', (config) => ({
        ...chatComposerState(),
        ...chatThreadHelpers(),
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

        onComposerKeydown(event) {
            this.handleComposerKeydown(event, () => this.send());
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
                    const data = await res.json();
                    this.draft = '';
                    this.clearPendingImage();
                    if (data.message) {
                        this.messages = this.mergeUniqueById(this.messages, [data.message]);
                    } else {
                        await this.pollNewer();
                    }
                    this.$nextTick(() => this.scrollBottom());
                }
            } finally {
                this.sending = false;
            }
        },
    }));

    Alpine.data('guestChatWidget', (config) => ({
        ...chatComposerState(),
        ...chatThreadHelpers(),
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
        loading: false,
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
            if (withForgotIntro && !this.messages.length) {
                this.draft = this.labels.forgot_password_intro ?? '';
            }
            this.$nextTick(() => this.scrollBottom());
        },

        close() {
            this.isOpen = false;
        },

        onComposerKeydown(event) {
            this.handleComposerKeydown(event, () => this.send());
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
                    const data = await res.json();
                    this.draft = '';
                    this.clearPendingImage();
                    if (data.message) {
                        this.messages = this.mergeUniqueById(this.messages, [data.message]);
                    } else {
                        await this.pollNewer();
                    }
                    this.$nextTick(() => this.scrollBottom());
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
