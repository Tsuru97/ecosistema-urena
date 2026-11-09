@extends('layouts.master')
@section('title') Nueva Orden de Compra @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    #items-container .item-row { background: var(--vz-card-bg); }
    #items-container .item-row:hover { background: rgba(var(--vz-primary-rgb),.03); }
    .variant-selector { display: none; }
    .variant-selector.visible { display: block; }
    /* Ajustes estéticos para Choices.js en inputs del formulario */
    .choices { margin-bottom: 0; }
    .choices__inner { min-height: 37.5px; padding: 4px 8px; border-radius: 0.25rem; border: 1px solid var(--vz-input-border); background-color: var(--vz-input-bg); }
</style>
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-article-line me-2"></i>Nueva Orden de Compra
                    </h4>
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-ghost-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Volver
                    </a>
                </div>

                <div class="card-body">

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                            <button class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('purchase-orders.store') }}" method="POST" id="purchase-form">
                        @csrf

                        <div class="row g-3 mb-4">
                            {{-- Proveedor --}}
                            <div class="col-md-4">
                                <label for="supplier_id" class="form-label fw-semibold">
                                    Proveedor <span class="text-danger">*</span>
                                </label>
                                <select name="supplier_id" id="supplier_id"
                                        class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" required>
                                    <option value="">— Seleccionar proveedor —</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Sucursal destino --}}
                            <div class="col-md-4">
                                <label for="store_id" class="form-label fw-semibold">
                                    Sucursal Destino <span class="text-danger">*</span>
                                </label>
                                @if($isGlobal)
                                    <select name="store_id" id="store_id"
                                            class="form-select form-select-sm @error('store_id') is-invalid @enderror" required>
                                        <option value="">— Seleccionar sucursal —</option>
                                        @foreach($stores as $st)
                                            <option value="{{ $st->id }}" {{ old('store_id') == $st->id ? 'selected' : '' }}>
                                                {{ $st->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" name="store_id" value="{{ $activeStoreId }}">
                                    <div class="form-control form-control-sm bg-light fw-semibold">
                                        {{ \App\Models\Store::find($activeStoreId)->name }}
                                    </div>
                                @endif
                                @error('store_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Fecha Esperada --}}
                            <div class="col-md-4">
                                <label for="expected_at" class="form-label fw-semibold">Fecha Esperada de Entrega</label>
                                <input type="date" name="expected_at" id="expected_at"
                                       class="form-control form-control-sm @error('expected_at') is-invalid @enderror"
                                       value="{{ old('expected_at') }}">
                                @error('expected_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Notas --}}
                        <div class="mb-4">
                            <label for="notes" class="form-label fw-semibold">Notas / Razón de Compra <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea name="notes" id="notes" rows="2" class="form-control form-control-sm"
                                      placeholder="Instrucciones especiales para el proveedor, comentarios..."></textarea>
                        </div>

                        {{-- Líneas de productos --}}
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h6 class="mb-0 fw-semibold">
                                <i class="ri-list-check me-1"></i> Partidas de la Compra
                            </h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-item-btn">
                                <i class="ri-add-line me-1"></i> Agregar partida
                            </button>
                        </div>

                        <div id="items-container">
                            {{-- Filas dinámicas generadas por JS --}}
                        </div>

                        @error('items')
                            <div class="text-danger small mt-1 mb-3">{{ $message }}</div>
                        @enderror

                        {{-- Panel de Totales --}}
                        <div class="row justify-content-end mt-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-end border shadow-sm">
                                    <div class="fs-12 text-muted text-uppercase fw-semibold mb-1">Total General</div>
                                    <div class="fs-24 fw-bold text-success" id="total-general">$0.00</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-line me-1"></i> Crear Orden de Compra
                            </button>
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
@php
    $productsJson = $products->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->name,
        'sku'      => $p->sku,
        'type'     => $p->type,
        'cost'     => $p->cost ?: 0,
        'variants' => $p->variants->map(fn($v) => [
            'id'    => $v->id,
            'sku'   => $v->sku,
            'cost'  => $v->cost ?: 0,
            'label' => $v->attributeValues->map(fn($av) => $av->attribute->name . ': ' . $av->value)->join(' / ') ?: 'Sin atributos',
        ])->values(),
    ])->values();
@endphp
<script>
(function () {
    const products = {!! json_encode($productsJson) !!};
    const container = document.getElementById('items-container');
    const totalLabel = document.getElementById('total-general');
    let rowIndex = 0;

    // Inicializar Choices.js en el select de proveedor
    const supplierSel = document.getElementById('supplier_id');
    if (supplierSel) {
        new Choices(supplierSel, {
            searchEnabled: true,
            placeholderValue: '— Seleccionar proveedor —',
            itemSelectText: '',
            noResultsText: 'No se encontraron proveedores',
            noChoicesText: 'No hay proveedores disponibles',
        });
    }

    function buildRow(idx) {
        const row = document.createElement('div');
        row.className = 'item-row border rounded p-3 mb-2 position-relative shadow-sm';
        row.dataset.index = idx;

        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold fs-12 mb-1">Producto *</label>
                    <select name="items[${idx}][product_id]" class="form-select form-select-sm product-sel" required>
                        <option value="">— Seleccionar —</option>
                        ${products.map(p => `<option value="${p.id}" data-type="${p.type}">${p.name} (${p.sku})</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-3 variant-selector" id="variant-box-${idx}">
                    <label class="form-label fw-semibold fs-12 mb-1">Variante *</label>
                    <select name="items[${idx}][product_variant_id]" class="form-select form-select-sm variant-sel">
                        <option value="">— Seleccionar —</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold fs-12 mb-1">Cantidad *</label>
                    <input type="number" name="items[${idx}][quantity_ordered]"
                           class="form-control form-control-sm qty-input" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold fs-12 mb-1">Costo Unitario *</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" name="items[${idx}][cost]"
                               class="form-control form-control-sm cost-input" min="0" value="0.00" required>
                    </div>
                </div>
                <div class="col-md-1 text-end pe-2 pb-2">
                    <div class="fs-12 text-muted fw-semibold">Subtotal</div>
                    <div class="fw-bold fs-14 text-dark row-subtotal">$0.00</div>
                </div>
            </div>
            <button type="button" class="btn btn-xs btn-ghost-danger remove-btn position-absolute top-0 end-0 m-1">
                <i class="ri-close-line"></i>
            </button>
        `;

        // Elementos interactivos
        const prodSel = row.querySelector('.product-sel');
        const variantBox = row.querySelector(`#variant-box-${idx}`);
        const variantSel = row.querySelector('.variant-sel');
        const qtyInput = row.querySelector('.qty-input');
        const costInput = row.querySelector('.cost-input');
        const subtotalLabel = row.querySelector('.row-subtotal');

        // Inicializar Choices.js en el selector de producto de la fila
        new Choices(prodSel, {
            searchEnabled: true,
            placeholderValue: '— Seleccionar —',
            itemSelectText: '',
            noResultsText: 'No se encontraron productos',
            noChoicesText: 'No hay productos disponibles',
        });

        // Producto cambia
        prodSel.addEventListener('change', function () {
            const pid = this.value;
            const prod = products.find(p => p.id == pid);

            variantSel.innerHTML = '<option value="">— Seleccionar —</option>';
            variantSel.required = false;

            if (!prod) {
                variantBox.classList.remove('visible');
                costInput.value = '0.00';
                calculateSubtotal();
                return;
            }

            if (prod.type === 'variable' && prod.variants.length) {
                variantBox.classList.add('visible');
                variantSel.required = true;
                prod.variants.forEach(v => {
                    variantSel.appendChild(new Option(`${v.label} — ${v.sku}`, v.id));
                });
                costInput.value = '0.00';
            } else {
                variantBox.classList.remove('visible');
                costInput.value = Number(prod.cost).toFixed(2);
            }
            calculateSubtotal();
        });

        // Variante cambia
        variantSel.addEventListener('change', function () {
            const pid = prodSel.value;
            const vid = this.value;
            const prod = products.find(p => p.id == pid);
            if (prod && vid) {
                const variant = prod.variants.find(v => v.id == vid);
                if (variant) {
                    costInput.value = Number(variant.cost).toFixed(2);
                }
            }
            calculateSubtotal();
        });

        // Recalcular montos al cambiar inputs
        qtyInput.addEventListener('input', calculateSubtotal);
        costInput.addEventListener('input', calculateSubtotal);

        function calculateSubtotal() {
            const qty = Math.max(1, parseInt(qtyInput.value) || 0);
            const cost = Math.max(0.0, parseFloat(costInput.value) || 0.00);
            const sub = qty * cost;
            subtotalLabel.textContent = '$' + sub.toFixed(2);
            calculateGrandTotal();
        }

        // Quitar fila
        row.querySelector('.remove-btn').addEventListener('click', () => {
            row.remove();
            calculateGrandTotal();
        });

        return row;
    }

    function calculateGrandTotal() {
        let total = 0.00;
        container.querySelectorAll('.item-row').forEach(row => {
            const qty = Math.max(1, parseInt(row.querySelector('.qty-input').value) || 0);
            const cost = Math.max(0.0, parseFloat(row.querySelector('.cost-input').value) || 0.00);
            total += qty * cost;
        });
        totalLabel.textContent = '$' + total.toFixed(2);
    }

    document.getElementById('add-item-btn').addEventListener('click', () => {
        container.appendChild(buildRow(rowIndex++));
    });

    // Agregar la primera fila automáticamente al inicio
    container.appendChild(buildRow(rowIndex++));
})();
</script>
@endsection
