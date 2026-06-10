@extends('layouts.admin')

@section('title', $category->exists ? __('admin.actions.edit') : __('admin.actions.add'))

@section('content')
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="max-w-md space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf @if($category->exists) @method('PUT') @endif
        <input name="name" value="{{ old('name', $category->name) }}" required placeholder="Name" class="w-full rounded-lg border-slate-300">
        <select name="status" class="w-full rounded-lg border-slate-300">
            <option value="active" @selected(old('status', $category->status ?? 'active') === 'active')>active</option>
            <option value="inactive" @selected(old('status', $category->status) === 'inactive')>inactive</option>
        </select>
        <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full rounded-lg border-slate-300" placeholder="Sort order">
        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
    </form>
@endsection
