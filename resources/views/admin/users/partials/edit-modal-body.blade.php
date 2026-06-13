@php
    $shop = $modalUser->shop;
    $defaultAddress = $modalUser->shippingAddresses->sortByDesc('is_default')->first();
    $latestApplication = $modalUser->shopApplications->sortByDesc('created_at')->first();
    $idNumber = old('id_number', $shop?->id_number ?? $latestApplication?->id_number);
    $shippingRecipient = old('shipping_recipient_name', $defaultAddress?->recipient_name ?? $modalUser->name);
    $shippingPhone = old('shipping_phone', $defaultAddress?->phone ?? $modalUser->phone);
    $shippingCountry = old('shipping_country', $defaultAddress?->country ?? 'Việt Nam');
    $shippingState = old('shipping_state', $defaultAddress?->state ?? '');
    $shippingCity = old('shipping_city', $defaultAddress?->city ?? '');
    $shippingAddress = old('shipping_address', $defaultAddress?->address_line ?? '');
    $shippingPostalCode = old('shipping_postal_code', $defaultAddress?->postal_code ?? '');
    $shopAddress = old('address', $shop?->address ?? $latestApplication?->address);
    $shopCountry = old('country', $shop?->country ?? $latestApplication?->country);
    $selectedRole = old('role', $modalUser->adminFormRole());
@endphp

<form
    id="admin-user-edit-form"
    method="POST"
    action="{{ route('admin.users.update', $modalUser, false) }}?{{ http_build_query(array_merge($listQuery ?? request()->only(['q', 'role', 'shop_application']), ['show_edit' => $modalUser->id])) }}"
    enctype="multipart/form-data"
    class="space-y-5"
    x-data="{ role: @js($selectedRole), wasShop: @js($modalUser->isShop()) }"
>
    @csrf
    @method('PUT')

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.columns.role') }}</label>
            <select name="role" x-model="role" class="w-full rounded-lg border-slate-300 text-sm">
                @foreach (\App\Models\User::adminMemberRoleOptions() as $roleOption)
                    <option value="{{ $roleOption }}">{{ __('admin.roles.'.$roleOption) }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ __('admin.users.actions.role_change_hint') }}</p>
            <p
                x-show="wasShop && role === 'member'"
                x-cloak
                class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-900"
            >
                {{ __('admin.users.actions.role_downgrade_pending_orders_warning') }}
            </p>
            @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Email *</label>
            <input type="email" name="email" value="{{ old('email', $modalUser->email) }}" required placeholder="email@example.com" class="w-full rounded-lg border-slate-300 text-sm">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <input type="hidden" name="username" value="{{ old('username', $modalUser->username) }}">
    <input type="hidden" name="user_code" value="{{ old('user_code', $modalUser->user_code) }}">
    <input type="hidden" name="status" value="{{ old('status', $modalUser->status) }}">

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.password_leave_blank') }}</label>
            <input type="password" name="password" placeholder="{{ __('admin.users.actions.password_leave_blank') }}" class="w-full rounded-lg border-slate-300 text-sm">
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
                {{ __('admin.users.actions.fund_password') }}
                <span class="font-normal text-slate-500">({{ $modalUser->hasPaymentPassword() ? __('admin.users.actions.password_set') : __('admin.users.actions.password_not_set') }})</span>
            </label>
            <input type="password" name="payment_password" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="{{ __('admin.users.actions.payment_password_leave_blank') }}" class="w-full rounded-lg border-slate-300 text-sm">
            <p class="mt-1 text-xs text-slate-500">{{ __('admin.users.actions.payment_password_digits_hint') }}</p>
            @error('payment_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.confirm_payment_password') }}</label>
            <input type="password" name="payment_password_confirmation" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="{{ __('admin.users.actions.confirm_payment_password') }}" class="w-full rounded-lg border-slate-300 text-sm sm:max-w-xs">
            @error('payment_password_confirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.real_name') }}</label>
            <input type="text" name="name" value="{{ old('name', $modalUser->name) }}" required placeholder="{{ __('admin.users.actions.real_name') }}" class="w-full rounded-lg border-slate-300 text-sm">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.shop_applications.phone') }}</label>
            <input type="text" name="phone" value="{{ old('phone', $modalUser->phone) }}" class="w-full rounded-lg border-slate-300 text-sm">
            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <h4 class="text-sm font-semibold text-slate-900">{{ __('admin.users.actions.shipping_title') }}</h4>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin.users.actions.shipping_hint') }}</p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shipping_recipient') }}</label>
                <input type="text" name="shipping_recipient_name" value="{{ $shippingRecipient }}" placeholder="{{ __('member.shipping.recipient_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('shipping_recipient_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shipping_phone') }}</label>
                <input type="text" name="shipping_phone" value="{{ $shippingPhone }}" placeholder="{{ __('member.shipping.phone_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('shipping_phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shipping_country') }}</label>
                <input type="text" name="shipping_country" value="{{ $shippingCountry }}" placeholder="{{ __('member.shipping.country_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('shipping_country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shipping_state') }}</label>
                <input type="text" name="shipping_state" value="{{ $shippingState }}" placeholder="{{ __('member.shipping.state_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('shipping_state')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shipping_city') }}</label>
                <input type="text" name="shipping_city" value="{{ $shippingCity }}" placeholder="{{ __('member.shipping.city_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('shipping_city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shipping_postal_code') }}</label>
                <input type="text" name="shipping_postal_code" value="{{ $shippingPostalCode }}" placeholder="{{ __('admin.users.actions.shipping_postal_code') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('shipping_postal_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shipping_address') }}</label>
                <input type="text" name="shipping_address" value="{{ $shippingAddress }}" placeholder="{{ __('member.shipping.address_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('shipping_address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <fieldset
        class="space-y-5 border-0 p-0"
        x-show="role === 'shop_personal' || role === 'shop_business'"
        x-cloak
        :disabled="role !== 'shop_personal' && role !== 'shop_business'"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shop_name') }}</label>
                <input type="text" name="shop_name" value="{{ old('shop_name', $shop?->name) }}" placeholder="{{ __('admin.users.actions.shop_name_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('shop_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shop_followers') }}</label>
                <input type="number" min="0" name="followers" value="{{ old('followers', $shop?->followers ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('followers')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.credit_score') }}</label>
                <input type="number" min="0" name="credit_score" value="{{ old('credit_score', $shop?->credit_score ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('credit_score')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.shop_stars') }}</label>
                <input type="number" min="0" max="5" step="0.1" name="star_rating" value="{{ old('star_rating', $shop?->star_rating ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('star_rating')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.id_number') }}</label>
                <input type="text" name="id_number" value="{{ $idNumber }}" placeholder="{{ __('admin.users.actions.id_number_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('id_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.address') }}</label>
                <input type="text" name="address" value="{{ $shopAddress }}" placeholder="{{ __('admin.users.actions.address_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('admin.users.actions.country') }}</label>
                <input type="text" name="country" value="{{ $shopCountry }}" placeholder="{{ __('admin.users.actions.country_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm">
                @error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="min-w-0">
                <x-admin.image-upload-field
                    name="logo"
                    :label="__('admin.shop_applications.logo')"
                    :preview="$shop?->displayLogoUrl()"
                />
                @error('logo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="min-w-0">
                <x-admin.image-upload-field
                    name="id_front"
                    :label="__('admin.shop_applications.id_front')"
                    :preview="$shop?->documentUrl($shop?->id_front ?? $latestApplication?->id_front)"
                />
                @error('id_front')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="min-w-0">
                <x-admin.image-upload-field
                    name="id_back"
                    :label="__('admin.shop_applications.id_back')"
                    :preview="$shop?->documentUrl($shop?->id_back ?? $latestApplication?->id_back)"
                />
                @error('id_back')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <h4 class="text-sm font-semibold text-slate-900">{{ __('admin.users.actions.buff_stats_title') }}</h4>
            <p class="mt-1 text-xs text-slate-500">{{ __('admin.users.actions.buff_my_page_hint', ['user' => $userLabel]) }}</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['name' => 'display_pending_orders', 'label' => __('admin.users.actions.display_pending_orders')],
                    ['name' => 'display_delivering_orders', 'label' => __('admin.users.actions.display_delivering_orders')],
                    ['name' => 'display_received_orders', 'label' => __('admin.users.actions.display_received_orders')],
                    ['name' => 'display_completed_orders', 'label' => __('admin.users.actions.display_completed_orders')],
                    ['name' => 'display_total_income', 'label' => __('admin.users.actions.display_total_income'), 'step' => '0.01'],
                    ['name' => 'display_balance', 'label' => __('admin.users.actions.display_balance'), 'step' => '0.01'],
                ] as $field)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-700">{{ $field['label'] }}</label>
                        <input
                            type="number"
                            min="0"
                            step="{{ $field['step'] ?? '1' }}"
                            name="{{ $field['name'] }}"
                            value="{{ old($field['name'], $shop?->{$field['name']}) }}"
                            placeholder="{{ __('admin.users.actions.auto_calculated') }}"
                            class="w-full rounded-lg border-slate-300 text-sm"
                        >
                        @error($field['name'])<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <h4 class="text-sm font-semibold text-slate-900">{{ __('admin.users.actions.buff_dashboard_title') }}</h4>
            <p class="mt-1 text-xs text-slate-500">{{ __('admin.users.actions.buff_dashboard_hint', ['user' => $userLabel]) }}</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['name' => 'display_total_sales', 'label' => __('admin.users.actions.display_total_sales'), 'step' => '0.01'],
                    ['name' => 'display_total_profit', 'label' => __('admin.users.actions.display_total_profit'), 'step' => '0.01'],
                    ['name' => 'display_orders_today', 'label' => __('admin.users.actions.display_orders_today')],
                    ['name' => 'display_sales_today', 'label' => __('admin.users.actions.display_sales_today'), 'step' => '0.01'],
                    ['name' => 'display_profit_today', 'label' => __('admin.users.actions.display_profit_today'), 'step' => '0.01'],
                    ['name' => 'display_visitors_today', 'label' => __('admin.users.actions.display_visitors_today')],
                    ['name' => 'display_visitors_7d', 'label' => __('admin.users.actions.display_visitors_7d')],
                    ['name' => 'display_visitors_30d', 'label' => __('admin.users.actions.display_visitors_30d')],
                ] as $field)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-700">{{ $field['label'] }}</label>
                        <input
                            type="number"
                            min="0"
                            step="{{ $field['step'] ?? '1' }}"
                            name="{{ $field['name'] }}"
                            value="{{ old($field['name'], $shop?->{$field['name']}) }}"
                            placeholder="{{ __('admin.users.actions.auto_calculated') }}"
                            class="w-full rounded-lg border-slate-300 text-sm"
                        >
                        @error($field['name'])<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </section>
    </fieldset>

    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
        <a href="{{ $closeUrl }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('admin.actions.cancel') }}</a>
        <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">{{ __('admin.actions.save') }}</button>
    </div>
</form>
