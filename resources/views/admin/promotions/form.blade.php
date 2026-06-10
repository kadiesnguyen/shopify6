@extends('layouts.admin')

@section('title', $promotion->exists ? __('admin.actions.edit') : __('admin.actions.add'))

@section('content')
    <form method="POST" action="{{ $promotion->exists ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}" class="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf @if($promotion->exists) @method('PUT') @endif
        <input name="title" value="{{ old('title', $promotion->title) }}" required placeholder="Title" class="w-full rounded-lg border-slate-300">
        <textarea name="description" rows="3" class="w-full rounded-lg border-slate-300">{{ old('description', $promotion->description) }}</textarea>
        <div class="grid grid-cols-2 gap-4">
            <input type="date" name="start_date" value="{{ old('start_date', optional($promotion->start_date)->format('Y-m-d')) }}" class="rounded-lg border-slate-300">
            <input type="date" name="end_date" value="{{ old('end_date', optional($promotion->end_date)->format('Y-m-d')) }}" class="rounded-lg border-slate-300">
        </div>
        <select name="status" class="w-full rounded-lg border-slate-300"><option value="active">active</option><option value="inactive">inactive</option></select>
        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
    </form>
@endsection
