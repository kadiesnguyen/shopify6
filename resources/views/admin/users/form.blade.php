@extends('layouts.admin')

@section('title', $user->exists ? __('admin.actions.edit') : __('admin.actions.add'))

@section('content')
    <x-admin.page-header :title="$user->exists ? __('admin.actions.edit') : __('admin.actions.add')" />

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        @foreach (['username', 'name', 'email', 'phone'] as $field)
            <div>
                <label class="mb-1 block text-sm font-medium">{{ ucfirst($field) }}</label>
                <input name="{{ $field }}" value="{{ old($field, $user->$field) }}" @if(in_array($field, ['username','name','email'])) required @endif class="w-full rounded-lg border-slate-300">
                @error($field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @endforeach

        <div>
            <label class="mb-1 block text-sm font-medium">Password</label>
            <input type="password" name="password" @if(! $user->exists) required @endif class="w-full rounded-lg border-slate-300">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
        @if ($user->exists)
            <div>
                <label class="mb-1 block text-sm font-medium">{{ ucfirst('role') }}</label>
                <div class="flex min-h-[42px] items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <x-admin.role-badge :role="$user->adminFormRole()" :shop="$user->shop" />
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.users.actions.role_locked_hint') }}</p>
            </div>
        @else
            <div>
                <label class="mb-1 block text-sm font-medium">Role</label>
                <select name="role" class="w-full rounded-lg border-slate-300">
                    @foreach (\App\Models\User::adminMemberRoleOptions() as $roleOption)
                        <option value="{{ $roleOption }}" @selected(old('role', $user->adminFormRole()) === $roleOption)>{{ __('admin.roles.'.$roleOption) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
            <div>
                <label class="mb-1 block text-sm font-medium">Status</label>
                <select name="status" class="w-full rounded-lg border-slate-300">
                    @foreach (['active', 'inactive', 'banned'] as $st)
                        <option value="{{ $st }}" @selected(old('status', $user->status ?? 'active') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
            <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
        </div>
    </form>
@endsection
