@extends('layouts.master-without-nav')
@section('title') @lang('translation.verification') @endsection
@section('content')
    <div class="auth-page-wrapper pt-5">
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>
        </div>
        <div class="auth-page-content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4 text-center">
                                @if($type === 'email')
                                    <div class="avatar-lg mx-auto mt-2">
                                        <div class="avatar-title bg-light text-success display-3 rounded-circle">
                                            <i class="ri-mail-check-line"></i>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-2">
                                        <h4>Verificar Correo Electrónico</h4>
                                        <p class="text-muted">Hola <strong>{{ $user->name }}</strong>, haz clic en el botón para confirmar tu correo electrónico <strong>{{ $user->email }}</strong>.</p>
                                        <form action="{{ $confirmUrl }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-success w-md mt-3 btn-sm">
                                                <i class="ri-check-line me-1"></i> Confirmar Verificación
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <div class="avatar-lg mx-auto mt-2">
                                        <div class="avatar-title bg-light text-success display-3 rounded-circle">
                                            <i class="ri-whatsapp-line"></i>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-2">
                                        <h4>Verificar Número de Teléfono</h4>
                                        <p class="text-muted">Hola <strong>{{ $user->name }}</strong>, haz clic en el botón para confirmar tu número de teléfono.</p>
                                        <form action="{{ $confirmUrl }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-success w-md mt-3 btn-sm">
                                                <i class="ri-check-line me-1"></i> Confirmar Verificación
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
