@extends('layouts.master')
@section('title') Ajuste de Inventario @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-equalizer-line me-2"></i>
                        {{ $product ? 'Ajustar Stock: ' . $product->name : 'Ajuste Manual de Inventario' }}
                    </h4>
                    <a href="{{ route('inventory.index') }}" class="btn btn-ghost-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Volver
                    </a>
                </div>

                <div class="card-body">

                    {{-- ── PRODUCTO SIMPLE: resumen de stock ──────────────────────── --}}
                    @if($inventory)
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <div class="fs-11 text-muted text-uppercase fw-semibold mb-1">Stock Actual</div>
                                    <div class="fs-28 fw-bold {{ $inventory->isOutOfStock() ? 'text-danger' : ($inventory->isAlert() ? 'text-warning' : 'text-success') }}">
                                        {{ $inventory->quantity }}
                                    </div>
                                    <div class="fs-12 text-muted">unidades</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <div class="fs-11 text-muted text-uppercase fw-semibold mb-1">Stock Mínimo</div>
                                    <div class="fs-28 fw-bold text-secondary">{{ $inventory->min_stock }}</div>
                                    <div class="fs-12 text-muted">unidades de seguridad</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <div class="fs-11 text-muted text-uppercase fw-semibold mb-1">Estado</div>
                                    <div class="mt-2">
                                        @if($inventory->isOutOfStock())
                                            <span class="badge bg-danger fs-13">Agotado</span>
                                        @elseif($inventory->isAlert())
                                            <span class="badge bg-warning text-dark fs-13">Stock Crítico</span>
                                        @else
                                            <span class="badge bg-success fs-13">Normal</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ── PRODUCTO VARIABLE: tabla de variantes ─────────────────── --}}
                    @if($product && $product->isVariable() && $variantInventories->isNotEmpty())
                        <div class="mb-4">
                            <h6 class="text-muted fw-semibold mb-3">
                                <i class="ri-stack-line me-1"></i> Stock por variante
                            </h6>
                            <div class="table-responsive rounded border mb-4">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Variante</th>
                                            <th class="text-center" style="width:90px">Stock</th>
                                            <th class="text-center" style="width:90px">Mínimo</th>
                                            <th class="text-center" style="width:80px">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($variantInventories as $vi)
                                        @php
                                            $avLabels = $vi->variant->attributeValues->map(fn($av) =>
                                                '<span class="badge bg-light text-dark border me-1">'
                                                . ($av->color_hex ? '<span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:' . $av->color_hex . ';vertical-align:middle"></span>' : '')
                                                . e($av->attribute->name ?? '') . ': ' . e($av->value)
                                                . '</span>'
                                            )->implode('');
                                        @endphp
                                        <tr>
                                            <td>
                                                <div>{!! $avLabels ?: '<span class="text-muted fst-italic fs-12">Sin atributos</span>' !!}</div>
                                                <small class="text-muted">SKU: {{ $vi->variant->sku }}</small>
                                            </td>
                                            <td class="text-center fw-bold {{ $vi->isOutOfStock() ? 'text-danger' : ($vi->isAlert() ? 'text-warning' : 'text-success') }}">
                                                {{ $vi->quantity }}
                                            </td>
                                            <td class="text-center text-muted">{{ $vi->min_stock }}</td>
                                            <td class="text-center">
                                                @if($vi->isOutOfStock())
                                                    <span class="badge bg-danger-subtle text-danger">Agotado</span>
                                                @elseif($vi->isAlert())
                                                    <span class="badge bg-warning-subtle text-warning">Crítico</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success">OK</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ri-error-warning-line me-2"></i>
                            <strong>No se pudo registrar el ajuste:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- ── FORMULARIO ───────────────────────────────────────────── --}}
                    <form action="{{ route('inventory.adjust.store') }}" method="POST">
                        @csrf

                        {{-- Sucursal (Solo si está en vista global) --}}
                        @if($isGlobal)
                            <div class="mb-3">
                                <label for="store_id" class="form-label fw-semibold">
                                    Sucursal <span class="text-danger">*</span>
                                </label>
                                <select name="store_id" id="store_id_select"
                                        class="form-select form-select-sm @error('store_id') is-invalid @enderror" required>
                                    <option value="">— Seleccionar sucursal —</option>
                                    @foreach($stores as $s)
                                        <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('store_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        {{-- Producto --}}
                        <div class="mb-3">
                            <label for="product_id" class="form-label fw-semibold">
                                Producto <span class="text-danger">*</span>
                            </label>
                            @if($product)
                                <input type="hidden" name="product_id" id="product_id" value="{{ $product->id }}">
                                <div class="form-control bg-light form-control-sm">
                                    <strong>{{ $product->name }}</strong>
                                    <span class="text-muted ms-2">SKU: {{ $product->sku }}</span>
                                    @if($product->isVariable())
                                        <span class="badge bg-primary-subtle text-primary ms-2">Variable</span>
                                    @endif
                                </div>
                            @else
                                <select name="product_id" id="product_id"
                                        class="form-select @error('product_id') is-invalid @enderror form-select-sm" required>
                                    <option value="">— Seleccionar producto —</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}"
                                                data-type="{{ $p->type }}"
                                                {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }} (SKU: {{ $p->sku }})
                                            {{ $p->isVariable() ? '📦 Variable' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('product_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        {{-- Variante (solo si el producto seleccionado es variable) --}}
                        <div class="mb-3" id="variant-selector" style="{{ ($product && $product->isVariable()) ? '' : 'display:none' }}">
                            <label for="product_variant_id" class="form-label fw-semibold">
                                Variante <span class="text-danger">*</span>
                            </label>
                            @if($product && $product->isVariable())
                                <select name="product_variant_id" id="product_variant_id"
                                        class="form-select form-select-sm @error('product_variant_id') is-invalid @enderror" required>
                                    <option value="">— Seleccionar variante —</option>
                                    @foreach($variantInventories as $vi)
                                        @php
                                            $label = $vi->variant->attributeValues->map(fn($av) => $av->attribute->name . ': ' . $av->value)->implode(' / ');
                                        @endphp
                                        <option value="{{ $vi->variant->id }}"
                                                {{ old('product_variant_id') == $vi->variant->id ? 'selected' : '' }}>
                                            {{ $label ?: 'Sin atributos' }} — SKU: {{ $vi->variant->sku }}
                                            (stock: {{ $vi->quantity }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <select name="product_variant_id" id="product_variant_id"
                                        class="form-select form-select-sm">
                                    <option value="">— Cargando variantes... —</option>
                                </select>
                            @endif
                            @error('product_variant_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Bodega / Ubicación --}}
                        <div class="mb-3">
                            <label for="warehouse_id" class="form-label fw-semibold">
                                Bodega / Ubicación de Sucursal <span class="text-danger">*</span>
                            </label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm" required>
                                @php
                                    $whs = $storeId ? \App\Models\Warehouse::where('store_id', $storeId)->get() : \App\Models\Warehouse::orderBy('name')->get();
                                @endphp
                                @foreach($whs as $w)
                                    <option value="{{ $w->id }}" {{ $w->is_default ? 'selected' : '' }}>
                                        {{ $w->name }} {{ $w->type === 'exhibition' ? '(Piso de Exhibición)' : '(Almacén)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tipo de movimiento --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de Ajuste <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type" id="type_entrada"
                                           value="entrada" {{ old('type', 'entrada') === 'entrada' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="type_entrada">
                                        <i class="ri-arrow-up-circle-line text-success me-1"></i>
                                        <strong>Entrada</strong> — Sumar stock
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type" id="type_salida"
                                           value="salida" {{ old('type') === 'salida' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="type_salida">
                                        <i class="ri-arrow-down-circle-line text-danger me-1"></i>
                                        <strong>Salida</strong> — Restar stock
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="type" id="type_ajuste"
                                           value="ajuste" {{ old('type') === 'ajuste' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="type_ajuste">
                                        <i class="ri-edit-line text-info me-1"></i>
                                        <strong>Ajuste</strong> — Corrección manual
                                    </label>
                                </div>
                            </div>
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            {{-- Cantidad --}}
                            <div class="col-md-6">
                                <label for="quantity" class="form-label fw-semibold">
                                    Cantidad <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="quantity" id="quantity"
                                       class="form-control @error('quantity') is-invalid @enderror form-control-sm"
                                       value="{{ old('quantity', 1) }}"
                                       min="1" required>
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Stock mínimo --}}
                            <div class="col-md-6">
                                <label for="min_stock" class="form-label fw-semibold">
                                    Stock Mínimo de Seguridad
                                    <span class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <input type="number" name="min_stock" id="min_stock"
                                       class="form-control @error('min_stock') is-invalid @enderror form-control-sm"
                                       value="{{ old('min_stock', $inventory?->min_stock ?? 0) }}"
                                       min="0">
                                <div class="form-text">Define cuándo debe aparecer una alerta de stock crítico.</div>
                                @error('min_stock')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Notas / Razón --}}
                        <div class="mb-4">
                            <label for="notes" class="form-label fw-semibold">Razón / Notas</label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="form-control @error('notes') is-invalid @enderror form-control-sm"
                                      placeholder="Ej: Recuento físico de inventario, merma por daño, etc.">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-line me-1"></i> Registrar Ajuste
                            </button>
                            <a href="{{ route('inventory.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
<script>
(function () {
    const storeSel    = document.getElementById('store_id_select');
    const productSel  = document.getElementById('product_id');
    const variantBox  = document.getElementById('variant-selector');
    const variantSel  = document.getElementById('product_variant_id');

    // Recargar página al cambiar sucursal (para cargar inventario actual del producto)
    if (storeSel) {
        storeSel.addEventListener('change', function () {
            const storeId = this.value;
            const pid = '{{ $product ? $product->id : "" }}' || productSel?.value || '';
            const path = pid ? `/inventory/adjust/${pid}` : '/inventory/adjust';
            window.location.href = `${path}?store_id=${storeId}`;
        });
    }

    if (!productSel || productSel.tagName === 'INPUT') return; // producto fijo (ya está seleccionado desde PHP)

    // Inicializar Choices.js de forma programática
    const choices = new Choices(productSel, {
        searchEnabled: true,
        placeholderValue: '— Seleccionar producto —',
        itemSelectText: '',
        noResultsText: 'No se encontraron productos',
        noChoicesText: 'No hay productos disponibles',
    });

    function getCsrfToken() {
        const c = document.cookie.split(';').map(x => x.trim()).find(x => x.startsWith('XSRF-TOKEN='));
        return c ? decodeURIComponent(c.split('=')[1]) : '';
    }

    productSel.addEventListener('change', async function () {
        const opt  = this.options[this.selectedIndex];
        const type = opt?.dataset.type;
        const pid  = this.value;

        if (type === 'variable' && pid) {
            variantBox.style.display = '';
            variantSel.required = true;
            variantSel.innerHTML = '<option value="">Cargando...</option>';

            try {
                const storeId = storeSel?.value || '{{ $storeId }}';
                const r    = await fetch(`/products/${pid}/variants?store_id=${storeId}`, {
                    headers: { 'X-XSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
                });
                const data = await r.json();

                variantSel.innerHTML = '<option value="">— Seleccionar variante —</option>';
                (data.variants ?? data).forEach(v => {
                    const label = (v.attributes ?? []).map(a => `${a.attr}: ${a.value}`).join(' / ') || 'Sin atributos';
                    const opt   = new Option(`${label} — SKU: ${v.sku}  (stock: ${v.stock ?? '?'})`, v.id);
                    variantSel.appendChild(opt);
                });
            } catch {
                variantSel.innerHTML = '<option value="">Error al cargar variantes</option>';
            }
        } else {
            variantBox.style.display = 'none';
            variantSel.required = false;
            variantSel.innerHTML = '<option value=""></option>';
        }
    });
})();
</script>@endsection
