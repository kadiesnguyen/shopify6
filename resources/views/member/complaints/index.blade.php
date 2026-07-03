@extends('layouts.member')

@section('title', __('member.complaints.title'))
@section('back_url', route('member.my.index'))

@section('content')
    <h1 class="mb-4 text-lg font-bold text-gray-900">{{ __('member.complaints.title') }}</h1>

    <form method="POST" action="{{ route('member.complaints.store') }}" class="mb-6 space-y-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
        @csrf
        <div>
            <label class="mb-1 block text-sm text-gray-600">{{ __('member.complaints.subject') }}</label>
            <input type="text" name="subject" value="{{ old('subject') }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm text-gray-600">{{ __('member.complaints.body') }}</label>
            <textarea name="body" rows="4" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">{{ old('body') }}</textarea>
        </div>
        <button type="submit" class="w-full rounded-lg bg-rose-500 py-2.5 text-sm font-semibold text-white">{{ __('member.complaints.submit') }}</button>
    </form>

    @if ($complaints->isEmpty())
        <x-ui.empty-state :title="__('member.complaints.empty')" class="rounded-xl bg-gray-50 py-8" />
    @else
        <div class="space-y-3">
            @foreach ($complaints as $complaint)
                <article class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <p class="font-medium text-gray-900">{{ $complaint->subject }}</p>
                    <p class="mt-1 text-sm text-gray-600">{{ $complaint->body }}</p>
                    <p class="mt-2 text-xs text-gray-400">{{ $complaint->created_at?->format('d/m/Y H:i') }}</p>
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $complaints->links() }}</div>
    @endif
@endsection
