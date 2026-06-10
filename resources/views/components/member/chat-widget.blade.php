@props([
    'messagesUrl' => '',
    'sendUrl' => '',
    'brand' => config('portal.brand_name', config('landing.brand_name', 'Shopify')),
])

<div
    x-data="guestChatWidget({
        messagesUrl: @js($messagesUrl),
        sendUrl: @js($sendUrl),
        brand: @js($brand),
        csrf: @js(csrf_token()),
        labels: @js([
            'support_title' => __('chat.support_title', ['brand' => $brand]),
            'placeholder' => __('chat.placeholder'),
            'send' => __('chat.send'),
            'guest_label_placeholder' => __('chat.guest_label_placeholder'),
            'forgot_password_intro' => __('chat.forgot_password_intro'),
            'online' => __('chat.online'),
        ]),
        openOnLoad: @js(request()->boolean('open_chat') || request()->routeIs('auth.password.request')),
        forgotContext: @js(request('chat') === 'forgot' || request()->routeIs('auth.password.request')),
    })"
    x-init="init()"
    @open-guest-chat.window="open($event.detail)"
    class="contents"
>
    <div
        x-show="isOpen"
        x-cloak
        class="fixed inset-0 z-[60] flex flex-col bg-white"
        @keydown.escape.window="close()"
    >
        <header class="flex shrink-0 items-center justify-between bg-emerald-600 px-4 py-3 text-white pt-[max(0.75rem,env(safe-area-inset-top))]">
            <div class="min-w-0">
                <p class="truncate font-semibold" x-text="labels.support_title.replace(':brand', brand)"></p>
                <p class="text-xs text-emerald-100" x-text="labels.online"></p>
            </div>
            <button type="button" @click="close()" class="inline-flex size-10 items-center justify-center rounded-full hover:bg-emerald-700/80" aria-label="{{ __('member.back') }}">
                <x-member.icon name="x" class="size-5" />
            </button>
        </header>

        <div class="border-b border-gray-100 px-4 py-2">
            <input
                type="text"
                x-model="guestLabel"
                class="w-full rounded-lg border-gray-300 text-sm"
                :placeholder="labels.guest_label_placeholder"
            >
        </div>

        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4" x-ref="thread">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.sender_role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="msg.sender_role === 'user' ? 'max-w-[85%] rounded-2xl rounded-br-md bg-emerald-600 px-3 py-2 text-white' : 'max-w-[85%] rounded-2xl rounded-bl-md bg-gray-100 px-3 py-2 text-gray-900'"
                    >
                        <template x-if="msg.image_url">
                            <a :href="msg.image_url" target="_blank" class="block">
                                <img :src="msg.image_url" alt="" class="max-h-40 rounded-lg object-cover">
                            </a>
                        </template>
                        <p x-show="msg.body" class="whitespace-pre-wrap text-sm" x-text="msg.body"></p>
                        <p class="mt-1 text-[10px] opacity-70" x-text="msg.formatted_time"></p>
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="send()" class="shrink-0 border-t border-gray-100 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            <div class="flex items-end gap-2">
                <label class="inline-flex shrink-0 cursor-pointer rounded-lg border border-gray-200 p-2 text-gray-600">
                    <input type="file" accept="image/*" class="hidden" @change="onImagePick($event)">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </label>
                <textarea x-model="draft" rows="2" class="min-w-0 flex-1 resize-none rounded-lg border-gray-300 text-sm" :placeholder="labels.placeholder"></textarea>
                <button type="submit" class="shrink-0 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white" :disabled="sending" x-text="labels.send"></button>
            </div>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('guestChatWidget', (config) => ({
        messagesUrl: config.messagesUrl,
        sendUrl: config.sendUrl,
        brand: config.brand,
        csrf: config.csrf,
        labels: config.labels,
        isOpen: false,
        messages: [],
        draft: '',
        guestLabel: '',
        pendingImage: null,
        sending: false,
        pollTimer: null,

        init() {
            if (config.openOnLoad || config.forgotContext) {
                this.open({ forgot: config.forgotContext });
            }
            this.pollTimer = setInterval(() => {
                if (this.isOpen) this.loadMessages(true);
            }, 8000);
        },

        async open(detail = null) {
            const withForgotIntro = detail?.forgot === true;
            this.isOpen = true;
            await this.loadMessages();
            if (withForgotIntro && !this.messages.length) {
                this.draft = this.labels.forgot_password_intro;
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
            const res = await fetch(this.messagesUrl, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            this.messages = data.messages || [];
            if (!silent) this.$nextTick(() => this.scrollBottom());
        },

        onImagePick(e) {
            const file = e.target.files?.[0];
            if (file) this.pendingImage = file;
            e.target.value = '';
        },

        async send() {
            if (this.sending) return;
            if (!this.draft.trim() && !this.pendingImage) return;
            this.sending = true;
            const form = new FormData();
            if (this.draft.trim()) form.append('body', this.draft.trim());
            if (this.pendingImage) form.append('image', this.pendingImage);
            if (this.guestLabel.trim()) form.append('guest_label', this.guestLabel.trim());
            try {
                const res = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
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
</script>
@endpush
@endonce
