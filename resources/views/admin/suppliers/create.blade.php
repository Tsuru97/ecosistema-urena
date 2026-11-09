@extends('layouts.master')
@section('title') Nuevo Proveedor @endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-truck-line me-2"></i>Registrar Nuevo Proveedor
                    </h4>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-ghost-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Volver
                    </a>
                </div>

                <div class="card-body">
                    <!-- Banner de Detección Dinámica de Usuarios -->
                    <div id="user-detection-alert" class="alert alert-info border-0 shadow-sm mb-4 d-none">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-info-subtle text-info rounded me-3 d-flex align-items-center justify-content-center">
                                <i class="ri-user-search-line fs-20"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading mb-1 fs-14">
                                    <i class="ri-lightbulb-line text-warning me-1"></i> Cuenta de Usuario Existente Detectada
                                </h6>
                                <p class="mb-2 fs-13 text-muted" id="user-detection-text"></p>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-info" id="btn-autofill">
                                        <i class="ri-magic-line me-1"></i> Autorrellenar Teléfono y RFC
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-dismiss-detection">
                                        Ingresar Datos Personalizados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('suppliers.store') }}" method="POST">
                        @csrf

                        <div class="row g-3 mb-3">
                            {{-- Nombre --}}
                            <div class="col-md-8">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre o Razón Social <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       class="form-control form-control-sm @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="Ej: Distribuidora de Calzado S.A. de C.V." required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- RFC --}}
                            <div class="col-md-4">
                                <label for="rfc" class="form-label fw-semibold">RFC / Registro Fiscal</label>
                                <input type="text" name="rfc" id="rfc"
                                       class="form-control form-control-sm @error('rfc') is-invalid @enderror"
                                       value="{{ old('rfc') }}" placeholder="Ej: XAXX010101000">
                                @error('rfc')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            {{-- Email --}}
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                                <input type="email" name="email" id="email"
                                       class="form-control form-control-sm @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" placeholder="Ej: contacto@proveedor.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Teléfono</label>
                                <input type="text" name="phone" id="phone"
                                       class="form-control form-control-sm @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" placeholder="Ej: 555-123-4567">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            {{-- Días de crédito --}}
                            <div class="col-md-6">
                                <label for="credit_days" class="form-label fw-semibold">
                                    Días de Crédito <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="credit_days" id="credit_days"
                                       class="form-control form-control-sm @error('credit_days') is-invalid @enderror"
                                       value="{{ old('credit_days', 0) }}" min="0" required>
                                <div class="form-text">Registra 0 si las compras con este proveedor son al contado.</div>
                                @error('credit_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Dirección --}}
                        <div class="mb-4">
                            <label for="address" class="form-label fw-semibold">Dirección Física</label>
                            <textarea name="address" id="address" rows="3"
                                      class="form-control form-control-sm @error('address') is-invalid @enderror"
                                      placeholder="Calle, Número, Colonia, Ciudad, Estado, C.P.">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-line me-1"></i> Guardar Proveedor
                            </button>
                            <a href="{{ route('suppliers.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const emailInput = document.getElementById('email');
    const alertBox = document.getElementById('user-detection-alert');
    const alertText = document.getElementById('user-detection-text');
    const btnAutofill = document.getElementById('btn-autofill');
    const btnDismiss = document.getElementById('btn-dismiss-detection');
    let detectedUser = null;

    if (!emailInput) return;

    let debounceTimer;
    function checkEmailNow() {
        clearTimeout(debounceTimer);
        const email = emailInput.value.trim();
        if (email.length < 5 || !email.includes('@')) {
            alertBox.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch('/users/check-email?email=' + encodeURIComponent(email), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => {
                    if (!res.ok) {
                        console.warn('Check email status:', res.status);
                        return null;
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('Check email response:', data);
                    if (data && data.exists && data.user) {
                        detectedUser = data.user;
                        let rolesText = data.user.roles && data.user.roles.length ? data.user.roles.join(', ') : 'Sin rol';
                        alertText.innerHTML = 'Se encontró la cuenta de <strong>' + data.user.full_name + '</strong> (' + data.user.email + ') [Roles: <span class="badge bg-secondary">' + rolesText + '</span>]. ¿Deseas vincular esta cuenta a este proveedor?';
                        alertBox.classList.remove('d-none');
                    } else {
                        alertBox.classList.add('d-none');
                    }
                })
                .catch(err => {
                    console.error('Error al consultar email:', err);
                    alertBox.classList.add('d-none');
                });
        }, 300);
    }

    ['input', 'change', 'blur'].forEach(evt => {
        emailInput.addEventListener(evt, checkEmailNow);
    });

    if (emailInput.value.trim().length >= 5) {
        checkEmailNow();
    }

    btnAutofill.addEventListener('click', function () {
        if (!detectedUser) return;
        const nameInput = document.getElementById('name');
        if (nameInput && !nameInput.value && detectedUser.full_name) {
            nameInput.value = detectedUser.full_name;
        }
        if (detectedUser.phone) document.getElementById('phone').value = detectedUser.phone;
        const rfcInput = document.getElementById('rfc');
        if (rfcInput && detectedUser.rfc) rfcInput.value = detectedUser.rfc;
        
        alertBox.classList.add('d-none');
    });

    btnDismiss.addEventListener('click', function () {
        alertBox.classList.add('d-none');
    });
});
</script>
@endpush
