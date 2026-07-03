@extends('layouts.member')

@section('title', __('member.reviews.title'))
@section('back_url', route('member.my.index'))

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.reviews.title') }}</h1>

    @if ($reviews->isEmpty())
        <x-ui.empty-state :title="__('member.reviews.empty')" class="rounded-xl bg-gray-50 py-12" />
    @else
        <div class="space-y-3">
            @foreach ($reviews as $review)
                <article class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <p class="font-medium text-gray-900">{{ $review->product?->name }}</p>
                    <p class="mt-1 text-amber-500">{{ str_repeat('★', $review->rating) }}</p>
                    @if ($review->body)
                        <p class="mt-2 text-sm text-gray-600">{{ $review->body }}</p>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $reviews->links() }}</div>
    @endif
@endsection
