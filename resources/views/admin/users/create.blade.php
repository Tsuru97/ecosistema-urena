@extends('layouts.master')
@section('title') @lang('translation.create_user') @endsection
@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">@lang('translation.create_user')</h4>
                </div>
                <div class="card-body">
                    @if (count($errors) > 0)
                        <div class="alert alert-danger">
                            <strong>@lang('translation.error_title')</strong> @lang('translation.error_input_problems')<br><br>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">@lang('translation.name'):</label>
                                    <input type="text" name="name" id="name" class="form-control form-control-sm" placeholder="@lang('translation.enter_name')" value="{{ old('name') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastname" class="form-label">@lang('translation.lastname'):</label>
                                    <input type="text" name="lastname" id="lastname" class="form-control form-control-sm" placeholder="@lang('translation.enter_lastname')" value="{{ old('lastname') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">@lang('translation.username'):</label>
                                    <input type="text" name="username" id="username" class="form-control form-control-sm" placeholder="@lang('translation.enter_username')" value="{{ old('username') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">@lang('translation.email'):</label>
                                    <input type="email" name="email" id="email" class="form-control form-control-sm" placeholder="@lang('translation.enter_email')" value="{{ old('email') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    @include('admin.users._phone_input', ['phone' => old('phone')])
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="birthday" class="form-label">@lang('translation.birthday'):</label>
                                    <input type="date" name="birthday" id="birthday" class="form-control form-control-sm" placeholder="@lang('translation.enter_birthday')" value="{{ old('birthday') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rfc" class="form-label">@lang('translation.rfc'):</label>
                                    <input type="text" name="rfc" id="rfc" class="form-control form-control-sm" placeholder="@lang('translation.enter_rfc')" value="{{ old('rfc') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">@lang('translation.status'):</label>
                                    <select name="status" id="status" class="form-control form-control-sm">
                                        <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>@lang('translation.active')</option>
                                        <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>@lang('translation.inactive')</option>
                                    </select>
                                </div>
                            </div>
                            @hasanyrole('Dirección|Gerente|Super Admin')
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="credit_limit" class="form-label">Límite de Crédito ($):</label>
                                    <input type="number" step="0.01" name="credit_limit" id="credit_limit" class="form-control form-control-sm" placeholder="Ej: 5000.00" value="{{ old('credit_limit', '0.00') }}">
                                    <small class="form-text text-muted">Solo aplicable para clientes. Establece a 0 para no otorgar crédito.</small>
                                </div>
                            </div>
                            @endhasanyrole
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">@lang('translation.address'):</label>
                                    <textarea name="address" id="address" class="form-control form-control-sm" rows="2" placeholder="@lang('translation.enter_address')">{{ old('address') }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">@lang('translation.password'):</label>
                                    <input type="password" name="password" id="password" class="form-control form-control-sm" placeholder="@lang('translation.enter_password')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm-password" class="form-label">@lang('translation.confirm_password'):</label>
                                    <input type="password" name="confirm-password" id="confirm-password" class="form-control form-control-sm" placeholder="@lang('translation.enter_confirm_password')">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="roles" class="form-label">@lang('translation.roles'):</label>
                                    <select name="roles[]" id="roles" class="form-control form-control-sm" multiple>
                                        @foreach($roles as $role)
                                            <option value="{{ $role }}">{{ $role }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Sucursales asignadas -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stores" class="form-label">Sucursales asignadas: <span class="text-danger">*</span></label>
                                    <select name="stores[]" id="stores" class="form-select form-select-sm" multiple required>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ in_array($store->id, old('stores', [])) ? 'selected' : '' }}>
                                                {{ $store->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Mantén Ctrl para seleccionar múltiples.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="default_store" class="form-label">Sucursal principal: <span class="text-danger">*</span></label>
                                    <select name="default_store" id="default_store" class="form-select form-select-sm" required>
                                        <option value="">— Selecciona primero las sucursales —</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 text-center mt-3">
                                <button type="submit" class="btn btn-primary btn-sm">@lang('translation.submit')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const storesSelect = document.getElementById('stores');
    const defaultStoreSelect = document.getElementById('default_store');

    function syncDefaultStore() {
        const selectedOptions = Array.from(storesSelect.selectedOptions);
        const currentValue = defaultStoreSelect.value;

        defaultStoreSelect.innerHTML = '';
        if (selectedOptions.length === 0) {
            defaultStoreSelect.innerHTML = '<option value="">— Selecciona primero las sucursales —</option>';
            return;
        }

        selectedOptions.forEach(opt => {
            const newOpt = document.createElement('option');
            newOpt.value = opt.value;
            newOpt.textContent = opt.textContent.trim();
            if (opt.value == currentValue) {
                newOpt.selected = true;
            }
            defaultStoreSelect.appendChild(newOpt);
        });

        if (!defaultStoreSelect.value && defaultStoreSelect.options.length > 0) {
            defaultStoreSelect.options[0].selected = true;
        }
    }

    storesSelect.addEventListener('change', syncDefaultStore);
    syncDefaultStore();
});
</script>
@endsection
