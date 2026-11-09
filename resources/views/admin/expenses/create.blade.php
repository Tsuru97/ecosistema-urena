@extends('layouts.master')
@section('title') Registrar Gasto Manual @endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Registrar Gasto Manual</h4>
                    <a href="{{ route('expenses.index') }}" class="btn btn-light btn-sm">Volver</a>
                </div>

                <div class="card-body">
                    <form action="{{ route('expenses.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Concepto del Gasto <span class="text-danger">*</span></label>
                                <input type="text" name="concept" class="form-control form-control-sm @error('concept') is-invalid @enderror" value="{{ old('concept') }}" required placeholder="Ej. Pago de luz sucursal centro">
                                @error('concept')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Monto ($) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required placeholder="0.00">
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha del Gasto <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control form-control-sm @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Notas Adicionales</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Información o detalles complementarios del gasto..."></textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('expenses.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                            <button type="submit" class="btn btn-danger btn-sm">Registrar Gasto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
