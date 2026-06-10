@props([
    'methods',
    'indexRoute',
    'destroyRoute',
    'showSort' => true,
])

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.name') }}</th>
                    @if ($showSort)
                        <th class="px-4 py-3 text-left">{{ __('admin.methods.sort_order') }}</th>
                    @endif
                    <th class="px-4 py-3 text-left">{{ __('admin.methods.enabled') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('admin.columns.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($methods as $method)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">{{ $method->name }}</td>
                        @if ($showSort)
                            <td class="px-4 py-3">{{ $method->sort_order ?? 0 }}</td>
                        @endif
                        <td class="px-4 py-3">
                            @if ($method->status === 'active')
                                <span class="text-emerald-600">{{ __('admin.methods.yes') }}</span>
                            @else
                                <span class="text-slate-400">{{ __('admin.methods.no') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route($indexRoute, ['edit' => $method->id]) }}" class="inline-flex rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    {{ __('admin.actions.edit') }}
                                </a>
                                <form method="POST" action="{{ route($destroyRoute, $method) }}" onsubmit="return confirm('{{ __('admin.actions.confirm_delete_item') }}')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex rounded-lg border border-red-200 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                        {{ __('admin.actions.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $showSort ? 4 : 3 }}" class="px-4 py-8 text-center text-slate-500">{{ __('admin.methods.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $methods->links() }}</div>
</div>
