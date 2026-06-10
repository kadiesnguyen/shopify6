@props(['placeholder', 'value' => request('q')])

<form method="GET" class="mb-4">
    @foreach (request()->except(['q', 'page']) as $key => $val)
        @if (is_string($val))
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
        @endif
    @endforeach
    <input type="search" name="q" value="{{ $value }}" placeholder="{{ $placeholder }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-brand focus:ring-brand">
</form>
