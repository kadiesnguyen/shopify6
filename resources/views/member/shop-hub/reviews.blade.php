@extends('layouts.member')

@section('title', __('member.shop_hub.reviews_title'))
@section('back_url', route('member.shop-hub.index'))

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.shop_hub.reviews_title') }}</h1>

    @if ($reviews->isEmpty())
        <p class="rounded-xl bg-white px-4 py-8 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-100">
            {{ __('member.shop_hub.reviews_empty') }}
        </p>
    @else
        <div class="space-y-3">
            @foreach ($reviews as $review)
                <article class="rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between gap-2">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $review->product?->name ?? '—' }}</p>
                        <span class="text-sm text-amber-500">{{ str_repeat('★', (int) $review->rating) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $review->user?->name }}</p>
                    @if ($review->body)
                        <p class="mt-2 text-sm text-gray-700">{{ $review->body }}</p>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-4">{{ $reviews->links() }}</div>
    @endif
@endsection
