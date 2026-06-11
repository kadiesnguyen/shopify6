@props([
    'enabled' => false,
    'action',
    'onLabel' => __('admin.methods.yes'),
    'offLabel' => __('admin.methods.no'),
])

<form method="POST" action="{{ $action }}" class="inline">
    @csrf
    @method('PATCH')
    <button
        type="submit"
        role="switch"
        aria-checked="{{ $enabled ? 'true' : 'false' }}"
        aria-label="{{ $enabled ? __('admin.methods.turn_off') : __('admin.methods.turn_on') }}"
        @class([
            'admin-power-switch',
            'admin-power-switch--on' => $enabled,
            'admin-power-switch--off' => ! $enabled,
        ])
    >
        <span class="admin-power-switch__label" data-on="{{ $onLabel }}" data-off="{{ $offLabel }}"></span>
        <span class="admin-power-switch__handle"></span>
    </button>
</form>
