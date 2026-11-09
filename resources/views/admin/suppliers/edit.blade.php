@extends('layouts.master')
@section('title') Editar Proveedor: {{ $supplier->name }} @endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-truck-line me-2"></i>Editar Proveedor
                    </h4>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-ghost-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Volver
                    </a>
                </div>

                <div class="card-body">
                    <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3 mb-3">
                            {{-- Nombre --}}
                            <div class="col-md-8">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre o Razón Social <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       class="form-control form-control-sm @error('name') is-invalid @enderror"
                                       value="{{ old('name', $supplier->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- RFC --}}
                            <div class="col-md-4">
                                <label for="rfc" class="form-label fw-semibold">RFC / Registro Fiscal</label>
                                <input type="text" name="rfc" id="rfc"
                                       class="form-control form-control-sm @error('rfc') is-invalid @enderror"
                                       value="{{ old('rfc', $supplier->rfc) }}">
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
                                       value="{{ old('email', $supplier->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Teléfono</label>
                                <input type="text" name="phone" id="phone"
                                       class="form-control form-control-sm @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $supplier->phone) }}">
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
                                       value="{{ old('credit_days', $supplier->credit_days) }}" min="0" required>
                                @error('credit_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Estado --}}
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold">Estado</label>
                                <select name="status" id="status" class="form-select form-select-sm">
                                    <option value="1" {{ old('status', $supplier->status) ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ !old('status', $supplier->status) ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        {{-- Dirección --}}
                        <div class="mb-4">
                            <label for="address" class="form-label fw-semibold">Dirección Física</label>
                            <textarea name="address" id="address" rows="3"
                                      class="form-control form-control-sm @error('address') is-invalid @enderror">{{ old('address', $supplier->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-line me-1"></i> Actualizar Proveedor
                            </button>
                            <a href="{{ route('suppliers.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
