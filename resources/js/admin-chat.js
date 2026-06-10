document.addEventListener('alpine:init', () => {
    Alpine.data('adminChat', (config) => ({
        filter: config.initialFilter || 'all',
        conversations: [],
        messages: [],
        activeId: null,
        activeConversation: null,
        displayName: '',
        draft: '',
        pendingImage: null,
        selectedIds: [],
        selectedConversationIds: [],
        loadingList: false,
        sending: false,
        pollTimer: null,
        routes: config.routes,
        labels: config.labels,
        csrf: config.csrf,

        get userMessageIds() {
            return this.messages.filter((m) => m.can_delete).map((m) => m.id);
        },

        init() {
            this.loadConversations();
            this.pollTimer = setInterval(() => {
                this.loadConversations(true);
                if (this.activeId) {
                    this.loadThread(this.activeId, true);
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
                }
            } finally {
                this.loadingList = false;
            }
        },

        async openConversation(id) {
            this.activeId = id;
            this.selectedIds = [];
            await this.loadThread(id);
        },

        async loadThread(id, silent = false) {
            const res = await fetch(this.url(this.routes.show, id), {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            this.activeConversation = data.conversation;
            this.displayName = data.conversation.admin_display_name || '';
            this.messages = data.messages || [];
            if (!silent) {
                this.$nextTick(() => {
                    if (this.$refs.thread) {
                        this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight;
                    }
                });
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

        onImagePick(e) {
            const file = e.target.files?.[0];
            if (file) {
                this.pendingImage = file;
            }
            e.target.value = '';
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
                    this.draft = '';
                    this.pendingImage = null;
                    await this.loadThread(this.activeId);
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
                this.selectedIds = [];
                await this.loadConversations(true);
            }
        },
    }));
});
