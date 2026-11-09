@extends('layouts.master')
@section('title') Crear Método de Pago @endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Registrar Nuevo Método de Pago</h4>
                    <a href="{{ route('payment-methods.index') }}" class="btn btn-light btn-sm">Volver</a>
                </div>

                <div class="card-body">
                    <form action="{{ route('payment-methods.store') }}" method="POST" id="payment-method-form">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del Método <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Ej. Tarjeta Banamex">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Comisión Base (%) <span class="text-danger">*</span></label>
                                <input type="number" name="commission_percentage" step="0.01" class="form-control form-control-sm @error('commission_percentage') is-invalid @enderror" value="{{ old('commission_percentage', '0.00') }}" required>
                                @error('commission_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch form-switch-md mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">Método Activo</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch form-switch-md mt-4">
                                    <input class="form-check-input" type="checkbox" name="accepts_financing" id="accepts_financing" value="1" {{ old('accepts_financing') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="accepts_financing">Acepta Financiamiento / Mensualidades</label>
                                </div>
                            </div>

                            <!-- Contenedor de Mensualidades -->
                            <div class="col-md-12 {{ old('accepts_financing') ? '' : 'd-none' }}" id="financing-container">
                                <div class="card border border-dashed shadow-none">
                                    <div class="card-header bg-light d-flex align-items-center py-2">
                                        <h6 class="card-title mb-0 flex-grow-1 text-primary">Plazos y Tasas de Interés</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-rate">
                                            <i class="ri-add-line align-middle me-1"></i> Agregar Plazo
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row mb-3 g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch form-switch-md">
                                                    <input class="form-check-input" type="checkbox" name="transfer_msi_cost_to_client" id="transfer_msi_cost_to_client" value="1" {{ old('transfer_msi_cost_to_client') ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-medium text-warning" for="transfer_msi_cost_to_client">Trasladar sobretasa de MSI al cliente</label>
                                                </div>
                                                <small class="text-muted d-block ms-4">Si se activa, el costo de la sobretasa se le cobrará al cliente en el ticket aunque la Campaña MSI esté activa.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch form-switch-md">
                                                    <input class="form-check-input" type="checkbox" name="apply_iva_to_rates" id="apply_iva_to_rates" value="1" checked>
                                                    <label class="form-check-label fw-medium text-info" for="apply_iva_to_rates">Aplicar 16% de IVA a comisiones / sobretasas</label>
                                                </div>
                                                <small class="text-muted d-block ms-4">Añade automáticamente el 16% de IVA al cálculo financiero bancario.</small>
                                            </div>
                                        </div>
                                        <hr class="my-2">
                                        <div id="rates-list">
                                            <!-- Las filas de plazos se agregan aquí dinámicamente -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('payment-methods.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                            <button type="submit" class="btn btn-success btn-sm">Guardar Método</button>
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
        const toggleFinancing = document.getElementById('accepts_financing');
        const container = document.getElementById('financing-container');
        const ratesList = document.getElementById('rates-list');
        const btnAddRate = document.getElementById('btn-add-rate');
        let rowIndex = 0;

        // Toggle contenedor
        toggleFinancing.addEventListener('change', function() {
            if (this.checked) {
                container.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
            }
        });

        // Función para agregar fila de plazo
        function addRateRow(months = '', rate = '0.00') {
            const html = `
                <div class="row g-2 align-items-center mb-2 rate-row" id="rate-row-${rowIndex}">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <input type="number" name="financing_rates[${rowIndex}][months]" class="form-control form-control-sm" min="1" max="120" value="${months}" placeholder="Cantidad meses" required>
                            <span class="input-group-text">meses</span>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Interés</span>
                            <input type="number" name="financing_rates[${rowIndex}][rate]" class="form-control form-control-sm" step="0.01" min="0" max="100" value="${rate}" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-sm btn-ghost-danger btn-icon remove-rate-row" data-row-id="rate-row-${rowIndex}">
                            <i class="ri-delete-bin-line fs-15"></i>
                        </button>
                    </div>
                </div>
            `;
            ratesList.insertAdjacentHTML('beforeend', html);
            rowIndex++;
        }

        // Agregar al hacer clic
        btnAddRate.addEventListener('click', () => addRateRow());

        // Eliminar fila
        ratesList.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-rate-row');
            if (btn) {
                const rowId = btn.getAttribute('data-row-id');
                document.getElementById(rowId).remove();
            }
        });

        // Precargar filas si hubo errores de validación (old inputs)
        @if(old('financing_rates'))
            @foreach(old('financing_rates') as $r)
                addRateRow("{{ $r['months'] }}", "{{ $r['rate'] }}");
            @endforeach
        @else
            // Agregar al menos una fila por default al activar
            if (ratesList.children.length === 0) {
                addRateRow();
            }
        @endif
    });
</script>
@endsection
