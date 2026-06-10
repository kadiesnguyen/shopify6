@extends('layouts.member')

@section('title', __('member.notifications.title'))
@section('back_url', route('member.home'))

@section('content')
    @if ($notifications->isEmpty())
        <x-ui.empty-state :title="__('member.notifications.empty')" class="bg-white" />
    @else
        <div class="space-y-3">
            @foreach ($notifications as $notification)
                <article @class(['rounded-xl border bg-white p-4 shadow-sm', 'border-brand/30' => ! $notification->read_at, 'border-slate-200' => $notification->read_at])>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="font-semibold text-slate-900">{{ $notification->title }}</h2>
                            @if ($notification->body)
                                <p class="mt-1 text-sm text-slate-600">{{ $notification->body }}</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if (! $notification->read_at)
                            <form method="POST" action="{{ route('member.notifications.read', $notification) }}">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-brand">{{ __('member.notifications.mark_read') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
@endsection
