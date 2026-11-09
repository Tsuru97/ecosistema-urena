@extends('layouts.master')
@section('title')
    @lang('translation.profile')
@endsection
@section('content')

<x-breadcrumb />

<div class="row">
    <div class="col-xxl-3">
        <!-- Tarjeta de Perfil & Avatar -->
        <div class="card">
            <div class="card-body p-4 text-center">
                {{-- Componente File Upload (Modo Avatar) --}}
                <x-file-uploader :model="$user" type="avatar" layout="avatar" accept="image/jpeg, image/png, image/webp" />

                <h5 class="fs-16 mb-1">{{ $user->name }} {{ $user->lastname }}</h5>
                <p class="text-muted mb-0">{{ $user->roles->first()->name ?? 'Usuario' }}</p>
                @if($user->status)
                    <span class="badge bg-success-subtle text-success mt-2">Activo</span>
                @else
                    <span class="badge bg-danger-subtle text-danger mt-2">Inactivo</span>
                @endif
            </div>
        </div>
        <!--end card-->
    </div>
    <!--end col-->

    <div class="col-xxl-9">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails" role="tab">
                            <i class="ri-user-line me-1 align-bottom"></i> Datos Personales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#changePassword" role="tab">
                            <i class="ri-lock-line me-1 align-bottom"></i> Seguridad
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#sessions" role="tab">
                            <i class="ri-macbook-line me-1 align-bottom"></i> Sesiones
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="tab-content">
                    <!-- Pestaña 1: Detalles Personales -->
                    <div class="tab-pane active" id="personalDetails" role="tabpanel">
                        <form action="{{ route('profile.update') }}" method="POST" id="personalDetailsForm">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" name="name" id="name" value="{{ old('name', $user->name) }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="lastname" class="form-label">Apellidos</label>
                                        <input type="text" class="form-control form-control-sm" name="lastname" id="lastname" value="{{ old('lastname', $user->lastname) }}">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control form-control-sm" name="email" id="email" value="{{ old('email', $user->email) }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Teléfono</label>
                                        <input type="text" class="form-control form-control-sm" name="phone" id="phone" value="{{ old('phone', $user->phone) }}">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="rfc" class="form-label">RFC</label>
                                        <input type="text" class="form-control form-control-sm" name="rfc" id="rfc" value="{{ old('rfc', $user->rfc) }}" maxlength="13">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="birthday" class="form-label">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control form-control-sm" name="birthday" id="birthday" value="{{ old('birthday', $user->birthday ? $user->birthday->format('Y-m-d') : '') }}">
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="mb-3 pb-2">
                                        <label for="address" class="form-label">Domicilio Completo</label>
                                        <textarea class="form-control form-control-sm" name="address" id="address" rows="3">{{ old('address', $user->address) }}</textarea>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i> Guardar Cambios</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Pestaña 2: Seguridad -->
                    <div class="tab-pane" id="changePassword" role="tabpanel">
                        <form action="{{ route('profile.password') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row g-2">
                                <div class="col-lg-4">
                                    <div>
                                        <label for="current_password" class="form-label">Contraseña Actual <span class="text-danger">*</span></label>
                                        <div class="position-relative auth-pass-inputgroup mb-3">
                                            <input type="password" class="form-control pe-5 password-input form-control-sm" name="current_password" id="current_password" required>
                                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon btn-sm" type="button" onclick="togglePassword('current_password', this)"><i class="ri-eye-fill align-middle"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div>
                                        <label for="password" class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                                        <div class="position-relative auth-pass-inputgroup mb-3">
                                            <input type="password" class="form-control pe-5 password-input form-control-sm" name="password" id="password" required>
                                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon btn-sm" type="button" onclick="togglePassword('password', this)"><i class="ri-eye-fill align-middle"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div>
                                        <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                                        <div class="position-relative auth-pass-inputgroup mb-3">
                                            <input type="password" class="form-control pe-5 password-input form-control-sm" name="password_confirmation" id="password_confirmation" required>
                                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon btn-sm" type="button" onclick="togglePassword('password_confirmation', this)"><i class="ri-eye-fill align-middle"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 mt-4">
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-success btn-sm"><i class="ri-lock-password-line me-1"></i> Actualizar Contraseña</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Pestaña 3: Sesiones -->
                    <div class="tab-pane" id="sessions" role="tabpanel">
                        <div class="mb-4">
                            <h5 class="card-title">Sesiones Activas</h5>
                            <p class="text-muted">Revoca el acceso en dispositivos donde hayas iniciado sesión previamente.</p>
                        </div>
                        
                        @forelse($sessions as $session)
                        <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                            <div class="flex-shrink-0 avatar-sm">
                                <div class="avatar-title bg-light text-primary rounded-3 fs-18 material-shadow">
                                    @if(str_contains(strtolower($session->user_agent), 'mobile'))
                                        <i class="ri-smartphone-line"></i>
                                    @else
                                        <i class="ri-macbook-line"></i>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>{{ Str::limit($session->user_agent, 40) }}</h6>
                                <p class="text-muted mb-0">
                                    IP: {{ $session->ip_address }} 
                                    - Última actividad: {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                                    @if($session->id === request()->session()->getId())
                                        <span class="badge bg-success-subtle text-success ms-1">Este dispositivo</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                @if($session->id !== request()->session()->getId())
                                <form action="{{ route('profile.session.destroy', $session->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de cerrar la sesión en este dispositivo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-soft-danger">Cerrar Sesión</button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @empty
                            <p class="text-muted">No se encontraron otras sesiones.</p>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal OTP Verification -->
<div class="modal fade" id="otpVerificationModal" tabindex="-1" aria-labelledby="otpVerificationModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-soft-primary p-3">
                <h5 class="modal-title" id="otpVerificationModalLabel">Verificación de Seguridad Requerida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCloseOtpModal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mb-4">
                    <i class="ri-shield-keyhole-line text-primary" style="font-size: 72px;"></i>
                </div>
                <div class="mt-4">
                    <h4 class="mb-3">Código de Verificación</h4>
                    <p class="text-muted mb-4">Hemos enviado un código de 6 dígitos a tu correo electrónico <span class="fw-medium">{{ $user->email }}</span> y a tu WhatsApp. Ingrésalo para autorizar los cambios en tu perfil.</p>
                    
                    <form id="otpFormValidation">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-3" id="otp-container">
                            <input type="text" class="form-control text-center otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px;">
                            <input type="text" class="form-control text-center otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px;">
                            <input type="text" class="form-control text-center otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px;">
                            <input type="text" class="form-control text-center otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px;">
                            <input type="text" class="form-control text-center otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px;">
                            <input type="text" class="form-control text-center otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px;">
                        </div>
                        <input type="hidden" id="full-otp-code" name="otp_code">
                        <div class="text-danger mb-3 d-none" id="otp-error-msg">El código ingresado es incorrecto. Intenta de nuevo.</div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-success w-100 btn-sm" id="btnVerifyOtp">Verificar Código y Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    // Mostrar/Ocultar contraseñas
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ri-eye-fill');
            icon.classList.add('ri-eye-off-fill');
        } else {
            input.type = 'password';
            icon.classList.remove('ri-eye-off-fill');
            icon.classList.add('ri-eye-fill');
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const otpInputs = document.querySelectorAll(".otp-input");
        const fullOtpInput = document.getElementById("full-otp-code");
        let currentPendingForm = null;

        // Lógica de Inputs del OTP (Saltar al siguiente, Borrar al anterior)
        otpInputs.forEach((input, index) => {
            input.addEventListener("input", (e) => {
                if (input.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                updateFullOtp();
            });

            input.addEventListener("keydown", (e) => {
                if (e.key === "Backspace" && input.value === "" && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
            
            // Permitir pegar el código completo
            input.addEventListener("paste", (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, 6).replace(/[^0-9]/g, '');
                if (pastedData) {
                    [...pastedData].forEach((char, i) => {
                        if(otpInputs[i]) {
                            otpInputs[i].value = char;
                        }
                    });
                    if(pastedData.length < 6) {
                        otpInputs[pastedData.length].focus();
                    } else {
                        otpInputs[5].focus();
                        otpInputs[5].blur();
                    }
                    updateFullOtp();
                }
            });
        });

        function updateFullOtp() {
            let code = "";
            otpInputs.forEach(input => code += input.value);
            fullOtpInput.value = code;
        }

        // Interceptar formularios sensibles
        const formsToProtect = [
            { id: "personalDetailsForm" },
            { id: "securityForm" } // NOTA: El form de pass no tiene un ID actualmente, lo manejaré por selector
        ];

        const securityForm = document.querySelector('form[action="{{ route('profile.password') }}"]');
        const personalForm = document.getElementById('personalDetailsForm');

        function requireVerification(e, formElement) {
            e.preventDefault();
            currentPendingForm = formElement;

            // Bloquear botón para evitar spam
            const submitBtn = formElement.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
            submitBtn.disabled = true;

            // 1. Llamar endpoint para generar y enviar OTP
            fetch('{{ route("profile.generate-otp") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Limpiar OTP anterior si hay
                    otpInputs.forEach(input => input.value = "");
                    fullOtpInput.value = "";
                    document.getElementById('otp-error-msg').classList.add('d-none');
                    
                    // Mostrar modal OTP
                    var myModal = new bootstrap.Modal(document.getElementById('otpVerificationModal'), {
                        backdrop: 'static'
                    });
                    myModal.show();
                    
                    // Foco al primer input tras abrir modal
                    document.getElementById('otpVerificationModal').addEventListener('shown.bs.modal', function () {
                        otpInputs[0].focus();
                    }, { once: true });
                } else {
                    alert(data.message || 'Error al generar el código de verificación.');
                }
            })
            .catch(error => {
                console.error('Error OTP:', error);
                alert('Ocurrió un error al contactar al servidor.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        // Intercepción: Formulario Datos Personales (solo si cambia el email)
        if(personalForm) {
            personalForm.addEventListener('submit', function(e) {
                // Si el form ya tiene el input oculto (fue validado), dejamos que pase normal
                if (this.querySelector('input[name="otp_code"]')) {
                    return true;
                }

                const originalEmail = "{{ $user->email }}";
                const newEmail = document.getElementById('email').value;
                if (originalEmail !== newEmail) {
                    e.preventDefault();
                    requireVerification(e, this);
                }
            });
        }

        // Intercepción: Formulario Seguridad (siempre, ya que cambia la contraseña)
        if(securityForm) {
            securityForm.addEventListener('submit', function(e) {
                // Si el form ya tiene el input oculto (fue validado), dejamos que pase normal
                if (this.querySelector('input[name="otp_code"]')) {
                    return true;
                }

                e.preventDefault();
                requireVerification(e, this);
            });
        }

        // Verificar el OTP ingresado en el backend
        document.getElementById('btnVerifyOtp').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;
            const code = fullOtpInput.value;

            if (code.length < 6) {
                return;
            }

            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verificando...';
            btn.disabled = true;

            fetch('{{ route("profile.validate-otp") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ otp_code: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Crear un input oculto en el formulario original con el código OTP validado
                    // para que el controlador backend pueda verificarlo nuevamente por seguridad
                    let hiddenOtpInput = document.createElement("input");
                    hiddenOtpInput.setAttribute("type", "hidden");
                    hiddenOtpInput.setAttribute("name", "otp_code");
                    hiddenOtpInput.setAttribute("value", code);
                    currentPendingForm.appendChild(hiddenOtpInput);
                    
                    // Enviar form original
                    currentPendingForm.submit();
                } else {
                    document.getElementById('otp-error-msg').innerText = data.message || 'El código ingresado es incorrecto.';
                    document.getElementById('otp-error-msg').classList.remove('d-none');
                    // Limpiar campos
                    otpInputs.forEach(input => input.value = "");
                    fullOtpInput.value = "";
                    otpInputs[0].focus();
                }
            })
            .catch(error => {
                console.error('Error Validate OTP:', error);
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    });
</script>
@endsection
