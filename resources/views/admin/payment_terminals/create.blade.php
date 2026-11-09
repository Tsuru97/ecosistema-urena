@extends('layouts.master')
@section('title') Crear Terminal Bancaria @endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Registrar Nueva Terminal Bancaria</h4>
                    <a href="{{ route('payment-terminals.index') }}" class="btn btn-light btn-sm">Volver</a>
                </div>

                <div class="card-body">
                    <form action="{{ route('payment-terminals.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Nombre de la Terminal <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Ej. Terminal Santander Principal">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Comisión Fija ($) <span class="text-danger">*</span></label>
                                <input type="number" name="commission_fixed" step="0.01" class="form-control form-control-sm @error('commission_fixed') is-invalid @enderror" value="{{ old('commission_fixed', '0.00') }}" required>
                                @error('commission_fixed')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Comisión Porcentaje (%) <span class="text-danger">*</span></label>
                                <input type="number" name="commission_percentage" step="0.01" class="form-control form-control-sm @error('commission_percentage') is-invalid @enderror" value="{{ old('commission_percentage', '0.00') }}" required>
                                @error('commission_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch form-switch-md mt-4">
                                    <input class="form-check-input" type="checkbox" name="charge_client" id="charge_client" value="1" checked>
                                    <label class="form-check-label" for="charge_client">Cobrar Comisión al Cliente (Aumenta el total cobrado)</label>
                                </div>
                                <small class="text-muted d-block mt-1">Si se desactiva, la comisión será absorbida por la empresa y generará automáticamente un registro de Gasto.</small>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch form-switch-md mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">Terminal Activa</label>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <hr class="my-2">
                                <label class="form-label text-primary h6">Métodos de Pago Asignados por Default</label>
                                <p class="text-muted fs-12 mb-3">Selecciona qué métodos de pago con tarjeta o financiamiento están asignados a esta terminal de forma predeterminada.</p>
                                
                                <div class="row g-2">
                                    @forelse($paymentMethods as $pm)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="{{ $pm->id }}" id="pm-{{ $pm->id }}">
                                                <label class="form-check-label" for="pm-{{ $pm->id }}">
                                                    {{ $pm->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-md-12 text-muted">No hay métodos de pago no-sistema configurados.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('payment-terminals.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                            <button type="submit" class="btn btn-success btn-sm">Guardar Terminal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
