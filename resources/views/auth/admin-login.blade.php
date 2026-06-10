<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.admin_portal') }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-admin-sidebar px-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg">
        <h1 class="text-xl font-semibold text-slate-900">Admin Login</h1>

        <form method="POST" action="{{ route('admin.login') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', old('login')) }}" required autofocus
                    class="w-full rounded-lg border-slate-300">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('login')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" required
                    autocomplete="current-password"
                    class="w-full rounded-lg border-slate-300">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex cursor-pointer items-start gap-2.5">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    value="1"
                    @checked(old('remember', true))
                    class="mt-0.5 rounded border-slate-300 text-cyan-500 focus:ring-cyan-500"
                >
                <span class="text-sm leading-snug text-slate-600">
                    {{ __('auth.remember_me') }}
                    <span class="block text-xs text-slate-400">{{ __('auth.remember_me_hint', ['hours' => config('auth.remember_minutes') / 60]) }}</span>
                </span>
            </label>

            <x-ui.button type="submit" class="w-full">Login</x-ui.button>
        </form>
    </div>
</body>
</html>
