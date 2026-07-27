import { chatComposerState } from './chat-composer';

document.addEventListener('alpine:init', () => {
    Alpine.data('adminChat', (config) => ({
        ...chatComposerState(),
        settingsOpen: false,
        displayNameOpen: false,
        filter: config.initialFilter || 'all',
        conversations: [],
        messages: [],
        activeId: null,
        activeConversation: null,
        displayName: '',
        draft: '',
        selectedIds: [],
        selectedConversationIds: [],
        loadingList: false,
        loadingOlder: false,
        hasMoreOlder: false,
        sending: false,
        pollTimer: null,
        routes: config.routes,
        labels: config.labels,
        csrf: config.csrf,
        pageSize: 40,

        get userMessageIds() {
            return this.messages.filter((m) => m.can_delete).map((m) => m.id);
        },

        init() {
            this.loadConversations();
            this.pollTimer = setInterval(() => {
                this.loadConversations(true);
                if (this.activeId) {
                    this.pollNewer();
                }
            }, 8000);
        },

        url(template, id) {
            return template.replace('__ID__', id);
        },

        setFilter(f) {
            this.filter = f;
            this.selectedConversationIds = [];
            this.loadConversations();
        },

        async loadConversations(silent = false) {
            if (!silent) {
                this.loadingList = true;
            }
            try {
                const res = await fetch(`${this.routes.conversations}?filter=${this.filter}`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                this.conversations = data.conversations || [];
                const visibleIds = this.conversations.map((item) => item.id);
                this.selectedConversationIds = this.selectedConversationIds.filter((id) => visibleIds.includes(id));
                if (this.activeId && !visibleIds.includes(this.activeId)) {
                    this.activeId = null;
                    this.activeConversation = null;
                    this.messages = [];
                    this.hasMoreOlder = false;
                }
            } finally {
                this.loadingList = false;
            }
        },

        async openConversation(id) {
            this.activeId = id;
            this.settingsOpen = false;
            this.displayNameOpen = false;
            this.selectedIds = [];
            this.messages = [];
            this.hasMoreOlder = false;
            await this.loadThread(id);
        },

        threadUrl(id, params = {}) {
            const url = new URL(this.url(this.routes.show, id), window.location.origin);
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    url.searchParams.set(key, String(value));
                }
            });

            return url.toString();
        },

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

        async loadThread(id) {
            const res = await fetch(this.threadUrl(id, { limit: this.pageSize }), {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (data.conversation) {
                this.activeConversation = data.conversation;
                this.displayName = data.conversation.admin_display_name || '';
            }
            this.messages = data.messages || [];
            this.hasMoreOlder = Boolean(data.has_more);
            this.$nextTick(() => this.scrollBottom());
        },

        async pollNewer() {
            if (!this.activeId) {
                return;
            }

            if (!this.messages.length) {
                await this.loadThread(this.activeId);
                return;
            }

            const afterId = this.messages[this.messages.length - 1].id;
            const res = await fetch(
                this.threadUrl(this.activeId, {
                    after_id: afterId,
                    limit: this.pageSize,
                }),
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
            const known = new Set(this.messages.map((m) => m.id));
            const unique = newer.filter((m) => !known.has(m.id));
            if (!unique.length) {
                return;
            }

            this.messages = [...this.messages, ...unique];
            if (stickToBottom) {
                this.$nextTick(() => this.scrollBottom());
            }
        },

        async loadOlder() {
            if (!this.activeId || this.loadingOlder || !this.hasMoreOlder || !this.messages.length) {
                return;
            }

            this.loadingOlder = true;
            const el = this.$refs.thread;
            const prevHeight = el ? el.scrollHeight : 0;
            const prevTop = el ? el.scrollTop : 0;
            const beforeId = this.messages[0].id;

            try {
                const res = await fetch(
                    this.threadUrl(this.activeId, {
                        before_id: beforeId,
                        limit: this.pageSize,
                    }),
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

                const known = new Set(this.messages.map((m) => m.id));
                const unique = older.filter((m) => !known.has(m.id));
                if (!unique.length) {
                    return;
                }

                this.messages = [...unique, ...this.messages];
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

        formatListTime(iso) {
            if (!iso) {
                return '';
            }
            const d = new Date(iso);
            return d.toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        onComposerKeydown(event) {
            this.handleComposerKeydown(event, () => this.sendMessage());
        },

        async sendMessage() {
            if (!this.activeId || this.sending) {
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
                const res = await fetch(this.url(this.routes.send, this.activeId), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body: form,
                });
                if (res.ok) {
                    const data = await res.json();
                    this.draft = '';
                    this.clearPendingImage();
                    if (data.message) {
                        const known = new Set(this.messages.map((m) => m.id));
                        if (!known.has(data.message.id)) {
                            this.messages = [...this.messages, data.message];
                        }
                    } else {
                        await this.pollNewer();
                    }
                    this.$nextTick(() => this.scrollBottom());
                    await this.loadConversations(true);
                }
            } finally {
                this.sending = false;
            }
        },

        async saveDisplayName() {
            if (!this.activeId) {
                return;
            }
            const res = await fetch(this.url(this.routes.displayName, this.activeId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ admin_display_name: this.displayName }),
            });
            if (res.ok) {
                const data = await res.json();
                this.activeConversation = data.conversation;
                await this.loadConversations(true);
            }
        },

        toggleSelectAll(checked) {
            this.selectedIds = checked ? [...this.userMessageIds] : [];
        },

        toggleSelectAllConversations(checked) {
            this.selectedConversationIds = checked ? this.conversations.map((item) => item.id) : [];
        },

        async deleteSelectedConversations() {
            if (!this.selectedConversationIds.length) {
                return;
            }
            const msg = this.labels.delete_conversations_confirm.replace(
                ':count',
                this.selectedConversationIds.length,
            );
            if (!confirm(msg)) {
                return;
            }
            const res = await fetch(this.routes.destroyConversations, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ conversation_ids: this.selectedConversationIds.map(Number) }),
            });
            if (res.ok) {
                if (this.activeId && this.selectedConversationIds.includes(this.activeId)) {
                    this.activeId = null;
                    this.activeConversation = null;
                    this.messages = [];
                    this.hasMoreOlder = false;
                    this.selectedIds = [];
                }
                this.selectedConversationIds = [];
                await this.loadConversations();
            }
        },

        async deleteSelected() {
            if (!this.selectedIds.length || !this.activeId) {
                return;
            }
            const msg = this.labels.delete_confirm.replace(':count', this.selectedIds.length);
            if (!confirm(msg)) {
                return;
            }
            const res = await fetch(this.url(this.routes.destroy, this.activeId), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ message_ids: this.selectedIds.map(Number) }),
            });
            if (res.ok) {
                const data = await res.json();
                this.messages = data.messages || [];
                this.hasMoreOlder = Boolean(data.has_more);
                this.selectedIds = [];
                this.$nextTick(() => this.scrollBottom());
                await this.loadConversations(true);
            }
        },
    }));
});
