<div
    x-show="pendingImagePreview"
    x-cloak
    class="mt-2 flex items-start gap-2 rounded-lg border border-gray-200 bg-gray-50 p-2"
>
    <img :src="pendingImagePreview" alt="" class="max-h-24 max-w-32 shrink-0 rounded-lg object-cover">
    <div class="min-w-0 flex-1">
        <p class="truncate text-xs text-gray-600" x-text="pendingImage?.name"></p>
        <button
            type="button"
            @click="clearPendingImage()"
            class="mt-1 text-xs font-medium text-red-600 hover:text-red-700"
        >
            {{ __('chat.remove_image') }}
        </button>
    </div>
</div>
