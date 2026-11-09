@extends('layouts.master')
@section('title') Nuevo Traspaso @endsection

@section('css')
<style>
    #items-container .item-row { background: var(--vz-card-bg); }
    #items-container .item-row:hover { background: rgba(var(--vz-primary-rgb),.03); }
    .stock-badge { font-size: 11px; }
    .variant-selector { display: none; }
    .variant-selector.visible { display: block; }
</style>
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-swap-box-line me-2"></i>
                        Nuevo Traspaso desde <strong>{{ $currentStore->name }}</strong>
                    </h4>
                    <a href="{{ route('transfers.index') }}" class="btn btn-ghost-secondary btn-sm">
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

                    <form action="{{ route('transfers.store') }}" method="POST" id="transfer-form">
                        @csrf

                        {{-- Sucursal destino --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="destination_store_id" class="form-label fw-semibold">
                                    Sucursal Destino <span class="text-danger">*</span>
                                </label>
                                <select name="destination_store_id" id="destination_store_id"
                                        class="form-select form-select-sm @error('destination_store_id') is-invalid @enderror" required>
                                    <option value="">— Seleccionar sucursal —</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ old('destination_store_id') == $store->id ? 'selected' : '' }}>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('destination_store_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($stores->isEmpty())
                                    <div class="form-text text-warning">
                                        <i class="ri-alert-line me-1"></i> No hay otras sucursales activas disponibles.
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="notes" class="form-label fw-semibold">Notas <span class="text-muted fw-normal">(opcional)</span></label>
                                <input type="text" name="notes" id="notes" class="form-control form-control-sm"
                                       value="{{ old('notes') }}" placeholder="Motivo del traspaso, instrucciones, etc.">
                            </div>
                        </div>

                        {{-- Líneas de productos --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 fw-semibold">
                                <i class="ri-list-check me-1"></i> Productos a traspasar
                            </h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-item-btn">
                                <i class="ri-add-line me-1"></i> Agregar producto
                            </button>
                        </div>

                        <div id="items-container">
                            {{-- Filas generadas por JS --}}
                        </div>

                        @error('items')
                            <div class="text-danger small mt-1 mb-3">{{ $message }}</div>
                        @enderror

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-sm" {{ $stores->isEmpty() ? 'disabled' : '' }}>
                                <i class="ri-save-line me-1"></i> Crear Traspaso
                            </button>
                            <a href="{{ route('transfers.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
@php
    $productsJson = $products->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->name,
        'sku'      => $p->sku,
        'type'     => $p->type,
        'variants' => $p->variants->map(fn($v) => [
            'id'    => $v->id,
            'sku'   => $v->sku,
            'label' => $v->attributeValues->map(fn($av) => $av->attribute->name . ': ' . $av->value)->join(' / ') ?: 'Sin atributos',
        ])->values(),
    ])->values();
@endphp
<script>
(function () {
    const products = {!! json_encode($productsJson) !!};

    const storeId    = {{ $storeId }};
    const container  = document.getElementById('items-container');
    let   rowIndex   = 0;

    function getCsrfToken() {
        const c = document.cookie.split(';').map(x => x.trim()).find(x => x.startsWith('XSRF-TOKEN='));
        return c ? decodeURIComponent(c.split('=')[1]) : '';
    }

    function buildRow(idx) {
        const row = document.createElement('div');
        row.className = 'item-row border rounded p-3 mb-2 position-relative';
        row.dataset.index = idx;

        row.innerHTML = `
            <div class="row g-2 align-items-start">
                <div class="col-md-5">
                    <label class="form-label fw-semibold fs-12">Producto *</label>
                    <select name="items[${idx}][product_id]" class="form-select form-select-sm product-sel" required>
                        <option value="">— Seleccionar —</option>
                        ${products.map(p => `<option value="${p.id}" data-type="${p.type}">${p.name} (${p.sku})</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-3 variant-selector" id="variant-box-${idx}">
                    <label class="form-label fw-semibold fs-12">Variante *</label>
                    <select name="items[${idx}][product_variant_id]" class="form-select form-select-sm variant-sel">
                        <option value="">— Seleccionar —</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold fs-12">Cantidad *</label>
                    <input type="number" name="items[${idx}][quantity_requested]"
                           class="form-control form-control-sm qty-input" min="1" value="1" required>
                </div>
                <div class="col-md-2 d-flex flex-column align-items-start gap-1">
                    <label class="form-label fw-semibold fs-12">Stock disponible</label>
                    <span class="badge bg-secondary stock-badge" id="stock-badge-${idx}">—</span>
                </div>
            </div>
            <button type="button" class="btn btn-xs btn-ghost-danger remove-btn position-absolute top-0 end-0 m-1">
                <i class="ri-close-line"></i>
            </button>
        `;

        // Producto cambia
        const prodSel = row.querySelector('.product-sel');
        const variantBox = row.querySelector(`#variant-box-${idx}`);
        const variantSel = row.querySelector('.variant-sel');
        const stockBadge = row.querySelector(`#stock-badge-${idx}`);

        prodSel.addEventListener('change', async function () {
            const pid  = this.value;
            const prod = products.find(p => p.id == pid);

            // Reset variante
            variantSel.innerHTML = '<option value="">— Seleccionar —</option>';
            variantSel.required  = false;

            if (!prod) {
                variantBox.classList.remove('visible');
                stockBadge.textContent = '—';
                stockBadge.className = 'badge bg-secondary stock-badge';
                return;
            }

            if (prod.type === 'variable' && prod.variants.length) {
                variantBox.classList.add('visible');
                variantSel.required = true;
                prod.variants.forEach(v => {
                    variantSel.appendChild(new Option(`${v.label} — ${v.sku}`, v.id));
                });
                // El stock lo carga al cambiar variante
                stockBadge.textContent = 'Selecciona variante';
            } else {
                variantBox.classList.remove('visible');
                await loadStock(stockBadge, pid, null);
            }
        });

        variantSel.addEventListener('change', async function () {
            const pid = prodSel.value;
            const vid = this.value;
            if (pid) await loadStock(stockBadge, pid, vid || null);
        });

        // Quitar fila
        row.querySelector('.remove-btn').addEventListener('click', () => row.remove());

        return row;
    }

    async function loadStock(badge, productId, variantId) {
        badge.textContent = '…';
        badge.className = 'badge bg-light text-dark stock-badge';
        try {
            const params = new URLSearchParams({ product_id: productId });
            if (variantId) params.append('variant_id', variantId);

            const r    = await fetch(`/transfers/stock?${params}`, {
                headers: { 'Accept': 'application/json', 'X-XSRF-TOKEN': getCsrfToken() },
            });
            const data = await r.json();
            const qty  = data.quantity ?? 0;

            badge.textContent = qty + ' uds.';
            badge.className   = qty > 0
                ? 'badge bg-success-subtle text-success stock-badge'
                : 'badge bg-danger-subtle text-danger stock-badge';
        } catch {
            badge.textContent = 'Error';
            badge.className = 'badge bg-warning stock-badge';
        }
    }

    document.getElementById('add-item-btn').addEventListener('click', () => {
        container.appendChild(buildRow(rowIndex++));
    });

    // Agregar la primera fila automáticamente
    container.appendChild(buildRow(rowIndex++));
})();
</script>
@endsection
