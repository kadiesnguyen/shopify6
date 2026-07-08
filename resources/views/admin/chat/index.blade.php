@extends('layouts.admin')

@section('title', __('chat.admin_title'))
@section('admin_chat_page', '1')

@section('content')
    <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-hidden">
        <section class="shrink-0 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <form
                method="POST"
                action="{{ route('admin.chat.settings.update') }}"
                enctype="multipart/form-data"
                class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
            >
                @csrf

                <div class="flex min-w-0 items-center gap-3 sm:w-auto">
                    <div class="size-12 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100">
                        @if ($chatSupportAvatarUrl)
                            <img src="{{ $chatSupportAvatarUrl }}" alt="" class="size-full object-cover">
                        @else
                            <div class="flex size-full items-center justify-center text-[10px] text-slate-400">CSKH</div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-700">{{ __('chat.support_avatar') }}</p>
                        <label class="mt-1 inline-flex cursor-pointer items-center rounded-md bg-brand px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-brand-dark">
                            {{ __('admin.users.actions.choose_image') }}
                            <input type="file" name="chat_support_avatar" accept="image/*" class="hidden">
                        </label>
                        @if ($chatSupportAvatarUrl)
                            <label class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500">
                                <input type="checkbox" name="remove_chat_support_avatar" value="1" class="rounded border-slate-300">
                                <span>{{ __('chat.remove_support_avatar') }}</span>
                            </label>
                        @endif
                        @error('chat_support_avatar')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="min-w-0 flex flex-1 flex-col gap-3 sm:min-w-[14rem]">
                    <div>
                        <label for="chat_support_title" class="mb-1 block text-xs font-medium text-slate-700">{{ __('chat.support_display_name') }}</label>
                        <input
                            id="chat_support_title"
                            type="text"
                            name="chat_support_title"
                            value="{{ old('chat_support_title', $chatSupportTitle) }}"
                            placeholder="{{ $chatSupportTitleDefault }}"
                            class="w-full rounded-lg border-slate-300 text-sm"
                        >
                        @error('chat_support_title')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="chat_support_welcome_message" class="mb-1 block text-xs font-medium text-slate-700">{{ __('chat.support_welcome_message') }}</label>
                        <textarea
                            id="chat_support_welcome_message"
                            name="chat_support_welcome_message"
                            rows="3"
                            placeholder="{{ __('chat.support_welcome_message_placeholder') }}"
                            class="w-full rounded-lg border-slate-300 text-sm"
                        >{{ old('chat_support_welcome_message', $chatSupportWelcomeMessage) }}</textarea>
                        @error('chat_support_welcome_message')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="w-full shrink-0 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark sm:w-auto">
                    {{ __('chat.save_settings') }}
                </button>
            </form>
        </section>

        <div
        class="admin-chat-shell flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        x-data="adminChat({
            initialFilter: @js($initialFilter),
            routes: {
                conversations: @js(route('admin.chat.conversations')),
                destroyConversations: @js(route('admin.chat.conversations.destroy')),
                show: @js(url('/admin/chat/conversations/__ID__')),
                displayName: @js(url('/admin/chat/conversations/__ID__/display-name')),
                send: @js(url('/admin/chat/conversations/__ID__/messages')),
                destroy: @js(url('/admin/chat/conversations/__ID__/messages')),
            },
            labels: @js([
                'tab_all' => __('chat.tab_all'),
                'tab_read' => __('chat.tab_read'),
                'tab_unread' => __('chat.tab_unread'),
                'no_conversations' => __('chat.no_conversations'),
                'select_conversation' => __('chat.select_conversation'),
                'placeholder' => __('chat.placeholder'),
                'send' => __('chat.send'),
                'attach_image' => __('chat.attach_image'),
                'display_name' => __('chat.display_name'),
                'display_name_hint' => __('chat.display_name_hint'),
                'save_name' => __('chat.save_name'),
                'delete_selected' => __('chat.delete_selected'),
                'delete_users' => __('chat.delete_users'),
                'select_all_conversations' => __('chat.select_all_conversations'),
                'delete_conversations_confirm' => __('chat.delete_conversations_confirm'),
                'select_all_user' => __('chat.select_all_user'),
                'delete_confirm' => __('chat.delete_confirm'),
                'back_to_list' => __('chat.back_to_list'),
                'image_message' => __('chat.image_message'),
                'loading' => __('chat.loading'),
            ]),
            csrf: @js(csrf_token()),
        })"
        x-init="init()"
    >
        <div class="flex min-h-0 flex-1 flex-col md:flex-row">
            {{-- Conversation list --}}
            <aside
                class="flex min-h-0 w-full flex-col border-b border-slate-200 md:w-80 md:border-b-0 md:border-r"
                :class="activeId && 'hidden md:flex'"
            >
                <div class="border-b border-slate-100 p-3">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('chat.admin_title') }}</h2>
                    <div class="mt-2 flex gap-1">
                        @foreach (['all' => 'tab_all', 'unread' => 'tab_unread', 'read' => 'tab_read'] as $key => $label)
                            <button
                                type="button"
                                @click="setFilter('{{ $key }}')"
                                :class="filter === '{{ $key }}' ? 'bg-brand text-white' : 'bg-slate-100 text-slate-600'"
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                            >{{ __('chat.'.$label) }}</button>
                        @endforeach
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-2" x-show="conversations.length">
                        <label class="flex items-center gap-2 text-xs text-slate-600">
                            <input
                                type="checkbox"
                                class="rounded border-slate-300"
                                @change="toggleSelectAllConversations($event.target.checked)"
                                :checked="selectedConversationIds.length && selectedConversationIds.length === conversations.length"
                            >
                            <span x-text="labels.select_all_conversations"></span>
                        </label>
                        <button
                            type="button"
                            x-show="selectedConversationIds.length"
                            @click="deleteSelectedConversations()"
                            class="rounded-lg bg-red-600 px-2.5 py-1 text-xs font-medium text-white"
                        >
                            <span x-text="labels.delete_users"></span> (<span x-text="selectedConversationIds.length"></span>)
                        </button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto">
                    <template x-if="loadingList">
                        <p class="p-4 text-sm text-slate-500" x-text="labels.loading"></p>
                    </template>
                    <template x-if="!loadingList && conversations.length === 0">
                        <p class="p-4 text-sm text-slate-500" x-text="labels.no_conversations"></p>
                    </template>
                    <template x-for="item in conversations" :key="item.id">
                        <div
                            class="flex w-full items-start gap-2 border-b border-slate-50 px-3 py-3 hover:bg-slate-50"
                            :class="activeId === item.id && 'bg-brand/5'"
                        >
                            <input
                                type="checkbox"
                                class="mt-3 shrink-0 rounded border-slate-300"
                                :value="item.id"
                                x-model="selectedConversationIds"
                                @click.stop
                            >
                            <button
                                type="button"
                                @click="openConversation(item.id)"
                                class="flex min-w-0 flex-1 items-start gap-3 text-left"
                            >
                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-600" x-text="item.admin_label.charAt(0).toUpperCase()"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="truncate font-medium text-slate-900" x-text="item.admin_label"></span>
                                        <span class="shrink-0 text-[10px] text-slate-400" x-text="formatListTime(item.last_message_at)"></span>
                                    </span>
                                    <span class="mt-0.5 line-clamp-1 text-xs text-slate-500" x-text="item.last_message_preview || '—'"></span>
                                    <span x-show="item.unread_count > 0" class="mt-1 inline-flex rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-medium text-white" x-text="item.unread_count"></span>
                                </span>
                            </button>
                        </div>
                    </template>
                </div>
            </aside>

            {{-- Thread (x-show keeps composer in DOM so Enter keydown binds reliably) --}}
            <section class="flex min-h-0 min-w-0 flex-1 flex-col" :class="!activeId && 'hidden md:flex'">
                <div
                    x-show="!activeId"
                    x-cloak
                    class="hidden flex-1 items-center justify-center p-6 text-sm text-slate-500 md:flex"
                    x-text="labels.select_conversation"
                ></div>

                <div x-show="activeId" x-cloak class="flex min-h-0 flex-1 flex-col">
                        <div class="flex shrink-0 items-center gap-2 border-b border-slate-100 px-3 py-2">
                            <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="activeId = null">
                                ← <span x-text="labels.back_to_list"></span>
                            </button>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-900" x-text="activeConversation?.admin_label"></p>
                                <p class="truncate text-xs text-slate-500" x-text="activeConversation?.user_email || activeConversation?.guest_label || ''"></p>
                            </div>
                        </div>

                        <div class="shrink-0 border-b border-slate-100 bg-slate-50 px-3 py-2">
                            <label class="text-xs font-medium text-slate-600" x-text="labels.display_name"></label>
                            <p class="text-[10px] text-slate-400" x-text="labels.display_name_hint"></p>
                            <div class="mt-1 flex gap-2">
                                <input type="text" x-model="displayName" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm">
                                <button type="button" @click="saveDisplayName()" class="shrink-0 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-medium text-white" x-text="labels.save_name"></button>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-3 py-2" x-show="userMessageIds.length">
                            <label class="flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" @change="toggleSelectAll($event.target.checked)" class="rounded border-slate-300">
                                <span x-text="labels.select_all_user"></span>
                            </label>
                            <button
                                type="button"
                                x-show="selectedIds.length"
                                @click="deleteSelected()"
                                class="rounded-lg bg-red-600 px-2.5 py-1 text-xs font-medium text-white"
                            >
                                <span x-text="labels.delete_selected"></span> (<span x-text="selectedIds.length"></span>)
                            </button>
                        </div>

                        <div class="admin-chat-thread space-y-3 p-3" x-ref="thread">
                            <template x-for="msg in messages" :key="msg.id">
                                <div :class="msg.sender_role === 'admin' ? 'flex justify-end' : 'flex justify-start gap-2'">
                                    <input
                                        x-show="msg.can_delete"
                                        type="checkbox"
                                        class="mt-2 rounded border-slate-300"
                                        :value="msg.id"
                                        x-model="selectedIds"
                                    >
                                    <div
                                        :class="msg.sender_role === 'admin' ? 'max-w-[85%] rounded-2xl rounded-br-md bg-brand px-3 py-2 text-white' : 'max-w-[85%] rounded-2xl rounded-bl-md bg-slate-100 px-3 py-2 text-slate-900'"
                                    >
                                        <template x-if="msg.image_url">
                                            <a :href="msg.image_url" target="_blank" class="block">
                                                <img :src="msg.image_url" alt="" class="max-h-48 rounded-lg object-cover">
                                            </a>
                                        </template>
                                        <p x-show="msg.body" class="whitespace-pre-wrap text-sm" x-text="msg.body"></p>
                                        <p class="mt-1 text-[10px] opacity-70" x-text="msg.formatted_time"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <form @submit.prevent="sendMessage()" class="admin-chat-composer p-3">
                            <div class="flex items-end gap-2">
                                <label class="inline-flex shrink-0 cursor-pointer rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50">
                                    <input type="file" accept="image/*" class="hidden" @change="onImagePick($event)">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </label>
                                <textarea
                                    x-model="draft"
                                    rows="2"
                                    class="min-w-0 flex-1 resize-none rounded-lg border-slate-300 text-sm"
                                    :placeholder="labels.placeholder"
                                    @keydown.enter="onComposerKeydown($event)"
                                ></textarea>
                                <button type="submit" class="shrink-0 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white" :disabled="sending" x-text="labels.send"></button>
                            </div>
                            <x-chat.pending-image-preview />
                        </form>
                </div>
            </section>
        </div>
    </div>
@endsection
