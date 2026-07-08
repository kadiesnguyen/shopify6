@props([
    'messagesUrl' => '',
    'sendUrl' => '',
    'backUrl' => '',
    'brand' => config('portal.brand_name', config('landing.brand_name', 'Shopify')),
    'prefill' => '',
])

@php
    use App\Support\SiteSettings;

    $supportTitle = SiteSettings::chatSupportTitle();
    $supportAvatarUrl = SiteSettings::chatSupportAvatarUrl();
    $supportWelcomeMessage = SiteSettings::chatSupportWelcomeMessage();
@endphp

<div
    class="portal-chat-shell"
    x-data="memberChatPanel({
        messagesUrl: @js($messagesUrl),
        sendUrl: @js($sendUrl),
        brand: @js($brand),
        csrf: @js(csrf_token()),
        prefill: @js($prefill),
        welcomeMessage: @js($supportWelcomeMessage),
        labels: @js([
            'placeholder' => __('chat.placeholder'),
            'send' => __('chat.send'),
            'online' => __('chat.online'),
            'empty' => __('chat.empty_thread'),
        ]),
    })"
    x-init="init()"
>
    <header class="portal-chat-header shrink-0 bg-emerald-600 text-white shadow-sm">
        <div class="flex items-center gap-3 px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
            <a
                href="{{ $backUrl }}"
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full hover:bg-emerald-700/80"
                aria-label="{{ __('member.back') }}"
            >
                <x-member.icon name="chevron-left" class="size-6" />
            </a>
            @if ($supportAvatarUrl)
                <img src="{{ $supportAvatarUrl }}" alt="" class="size-10 shrink-0 rounded-full border border-white/30 object-cover">
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold">{{ $supportTitle }}</p>
                <p class="text-xs text-emerald-100">{{ __('chat.online') }}</p>
            </div>
        </div>
    </header>

    <div class="portal-chat-thread px-4 py-4" x-ref="thread">
        <p class="py-8 text-center text-sm text-gray-500" x-show="!loading && messages.length === 0 && !welcomeMessage" x-cloak>
            {{ __('chat.empty_thread') }}
        </p>
        <div x-show="!loading && messages.length === 0 && welcomeMessage" class="flex justify-start" x-cloak>
            <div class="max-w-[min(85%,20rem)] rounded-2xl rounded-bl-md bg-white px-3 py-2 text-gray-900 shadow-sm ring-1 ring-gray-100">
                <p class="whitespace-pre-wrap text-sm leading-relaxed" x-text="welcomeMessage"></p>
            </div>
        </div>
        <div class="space-y-3">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.sender_role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="msg.sender_role === 'user'
                            ? 'max-w-[min(85%,20rem)] rounded-2xl rounded-br-md bg-emerald-600 px-3 py-2 text-white shadow-sm'
                            : 'max-w-[min(85%,20rem)] rounded-2xl rounded-bl-md bg-white px-3 py-2 text-gray-900 shadow-sm ring-1 ring-gray-100'"
                    >
                        <template x-if="msg.image_url">
                            <a :href="msg.image_url" target="_blank" rel="noopener" class="block">
                                <img :src="msg.image_url" alt="" class="max-h-56 w-full rounded-lg object-cover">
                            </a>
                        </template>
                        <p x-show="msg.body" class="whitespace-pre-wrap text-sm leading-relaxed" x-text="msg.body"></p>
                        <p class="mt-1 text-[10px] opacity-70" x-text="msg.formatted_time"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <form
        @submit.prevent="send()"
        class="portal-chat-composer px-4 py-3"
    >
        <div class="flex items-end gap-2">
            <label class="inline-flex shrink-0 cursor-pointer rounded-xl border border-gray-200 p-2.5 text-gray-600 hover:bg-gray-50">
                <input type="file" accept="image/*" class="hidden" @change="onImagePick($event)">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </label>
            <textarea
                x-model="draft"
                rows="2"
                class="min-w-0 flex-1 resize-none rounded-xl border-gray-300 text-sm leading-snug"
                placeholder="{{ __('chat.placeholder') }}"
                @keydown="onComposerKeydown($event)"
            ></textarea>
            <x-member.chat-send-button x-bind:disabled="sending" />
        </div>
        <x-chat.pending-image-preview />
    </form>
</div>
