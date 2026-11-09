@extends('layouts.master')
@section('title') Inventario por Sucursal @endsection

@section('css')
<style>
    /* Fila padre del producto */
    .inv-product-row { cursor: pointer; }
    .inv-product-row:hover { background: rgba(var(--vz-primary-rgb), .03); }

    /* Filas de variantes (colapsadas por defecto) */
    .inv-variant-rows { display: none; }
    .inv-variant-rows.show { display: table-row-group; }
    .inv-variant-row td { background: rgba(var(--vz-primary-rgb), .02); }
    .inv-variant-row td:first-child { padding-left: 2.5rem; }

    /* Indicador de expansión */
    .expand-icon { transition: transform .2s; display: inline-block; }
    .expanded .expand-icon { transform: rotate(90deg); }

    /* Min-stock inline input */
    .min-stock-form { display: inline-flex; align-items: center; gap: 4px; }
</style>
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">

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

            {{-- Banner de alertas --}}
            @if($alertCount > 0)
                <div class="alert alert-warning d-flex align-items-center gap-3 mb-3" role="alert">
                    <i class="ri-alarm-warning-line fs-22 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <strong>{{ $alertCount }} entrada(s) con stock crítico</strong> están por debajo del mínimo de seguridad.
                        @if(!$alertOnly)
                            <a href="{{ route('inventory.index', array_merge(request()->all(), ['alerts' => 1])) }}" class="alert-link ms-2">
                                Ver solo alertas →
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header align-items-center d-flex flex-wrap gap-2">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-stack-line me-2"></i>
                        Inventario
                        @if($alertOnly)
                            <span class="badge bg-warning ms-2">Solo alertas</span>
                        @endif
                    </h4>
                    @can('gestionar-inventario')
                    <a href="{{ route('inventory.adjust') }}" class="btn btn-success btn-label btn-sm waves-effect waves-light">
                        <i class="ri-add-line label-icon align-middle me-2"></i> Ajuste de Stock
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    {{-- Filtros --}}
                    <form action="{{ route('inventory.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm"
                                           value="{{ $search }}" placeholder="Buscar por nombre o SKU...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Todas las Bodegas / Exhibición</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }} {{ $wh->type === 'exhibition' ? '(Exhibición)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-search-line me-1"></i> Buscar
                                </button>
                                @if($search || $alertOnly || $warehouseId)
                                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="ri-close-line me-1"></i> Limpiar
                                    </a>
                                @endif
                                <a href="{{ route('inventory.index', array_merge(request()->except('alerts'), $alertOnly ? [] : ['alerts' => 1])) }}"
                                   class="btn btn-sm {{ $alertOnly ? 'btn-warning' : 'btn-outline-warning' }}">
                                    <i class="ri-alarm-warning-line me-1"></i>
                                    {{ $alertOnly ? 'Ver todo' : 'Solo alertas' }}
                                    @if($alertCount > 0 && !$alertOnly)
                                        <span class="badge bg-danger ms-1">{{ $alertCount }}</span>
                                    @endif
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="inv-table">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU / Producto</th>
                                    @if($isGlobal)
                                        <th>Sucursal</th>
                                    @endif
                                    <th>Bodega / Ubicación</th>
                                    <th class="text-center" style="width:90px">Físico</th>
                                    <th class="text-center" style="width:90px">Apartado</th>
                                    <th class="text-center" style="width:100px">Disponible</th>
                                    <th class="text-center" style="width:100px">Mínimo</th>
                                    <th class="text-center" style="width:100px">Estado</th>
                                    <th style="width:110px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grouped as $groupKey => $rows)
                                    @php
                                        $firstRow   = $rows->first();
                                        $product    = $firstRow->product;
                                        $isVariable = $rows->count() > 1 || $rows->first()->product_variant_id !== null;

                                        // Para producto simple, la única fila es la del producto
                                        $simpleRow  = $isVariable ? null : $firstRow;
                                        $variantRows = $isVariable ? $rows : collect();

                                        // Totales para la fila resumen del producto variable
                                        $totalStock = $variantRows->sum('quantity');
                                        $hasAlert   = $variantRows->some(fn($r) => $r->isAlert());
                                        $hasOut     = $variantRows->some(fn($r) => $r->isOutOfStock());
                                        $groupId    = 'grp-' . $groupKey;
                                    @endphp

                                    {{-- ── Fila producto (simple o cabecera de variable) ── --}}
                                    <tr class="inv-product-row {{ $isVariable ? '' : ($simpleRow->isOutOfStock() ? 'table-danger' : ($simpleRow->isAlert() ? 'table-warning' : '')) }}"
                                        id="row-{{ $groupKey }}"
                                        @if($isVariable) onclick="toggleGroup('{{ $groupId }}')" @endif>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($isVariable)
                                                    <span class="expand-icon text-muted">›</span>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold fs-14">{{ $product->name }}</div>
                                                    <small class="text-muted">SKU: {{ $product->sku }}
                                                        @if($isVariable)
                                                            &nbsp;·&nbsp;
                                                            <span class="badge bg-primary-subtle text-primary">{{ $variantRows->count() }} variantes</span>
                                                        @endif
                                                        @if($product->brand)
                                                            &nbsp;·&nbsp; {{ $product->brand->name }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        @if($isGlobal)
                                            <td><span class="badge bg-info-subtle text-info">{{ $firstRow->store->name }}</span></td>
                                        @endif

                                        <td>
                                            <span class="badge {{ ($firstRow->warehouse->type ?? '') === 'exhibition' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $firstRow->warehouse->name ?? 'Bodega Principal' }}
                                            </span>
                                        </td>

                                        {{-- Físico --}}
                                        <td class="text-center">
                                            <span class="fw-medium fs-14 text-dark">
                                                {{ $isVariable ? $variantRows->sum('quantity') : $simpleRow->quantity }}
                                            </span>
                                        </td>

                                        {{-- Apartado --}}
                                        <td class="text-center">
                                            <span class="badge bg-warning-subtle text-warning fs-13">
                                                {{ $isVariable ? $variantRows->sum('reserved_quantity') : $simpleRow->reserved_quantity }}
                                            </span>
                                        </td>

                                        {{-- Disponible --}}
                                        <td class="text-center">
                                            <span class="fw-bold fs-15 {{ ($isVariable ? $variantRows->sum('available_quantity') <= 0 : $simpleRow->isOutOfStock()) ? 'text-danger' : 'text-success' }}">
                                                {{ $isVariable ? $variantRows->sum('available_quantity') : $simpleRow->available_quantity }}
                                            </span>
                                        </td>

                                        {{-- Mínimo --}}
                                        <td class="text-center">
                                            @if($isVariable)
                                                <span class="text-muted fs-12">—</span>
                                            @else
                                                @can('gestionar-inventario')
                                                <form class="min-stock-form"
                                                      action="{{ route('inventory.min-stock', $simpleRow->id) }}"
                                                      method="POST">
                                                    @csrf @method('PATCH')
                                                    <input type="number" name="min_stock"
                                                           class="form-control form-control-sm text-center p-1"
                                                           style="width:60px"
                                                           value="{{ $simpleRow->min_stock }}" min="0"
                                                           data-no-loader="true">
                                                    <button type="submit" class="btn btn-xs btn-outline-secondary btn-sm p-1" title="Guardar">
                                                        <i class="ri-save-line"></i>
                                                    </button>
                                                </form>
                                                @else
                                                    {{ $simpleRow->min_stock }}
                                                @endcan
                                            @endif
                                        </td>

                                        {{-- Estado --}}
                                        <td class="text-center">
                                            @if($isVariable)
                                                @if($hasOut)
                                                    <span class="badge bg-danger">Agotado</span>
                                                @elseif($hasAlert)
                                                    <span class="badge bg-warning text-dark"><i class="ri-alarm-warning-line me-1"></i>Crítico</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success">Normal</span>
                                                @endif
                                            @else
                                                @if($simpleRow->isOutOfStock())
                                                    <span class="badge bg-danger">Agotado</span>
                                                @elseif($simpleRow->isAlert())
                                                    <span class="badge bg-warning text-dark"><i class="ri-alarm-warning-line me-1"></i>Stock crítico</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success">Normal</span>
                                                @endif
                                            @endif
                                        </td>

                                        {{-- Acciones --}}
                                        <td>
                                            <div class="hstack gap-1">
                                                @can('gestionar-inventario')
                                                <a href="{{ route('inventory.adjust', ['product' => $product->id, 'store_id' => $firstRow->store_id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Ajustar stock">
                                                    <i class="ri-equalizer-line"></i>
                                                </a>
                                                @endcan
                                                <a href="{{ route('inventory.movements', $product->id) }}"
                                                   class="btn btn-sm btn-outline-secondary" title="Ver movimientos">
                                                    <i class="ri-history-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- ── Filas de variantes (colapsadas) ────────────── --}}
                                    @if($isVariable)
                                    <tbody class="inv-variant-rows" id="{{ $groupId }}">
                                        @foreach($variantRows as $vr)
                                        @php
                                            $avLabels = $vr->variant
                                                ? $vr->variant->attributeValues->map(fn($av) =>
                                                    ($av->color_hex ? '<span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:' . $av->color_hex . ';vertical-align:middle"></span>' : '')
                                                    . e($av->value)
                                                  )->implode(' / ')
                                                : '';
                                        @endphp
                                        <tr class="inv-variant-row {{ $vr->isOutOfStock() ? 'table-danger' : ($vr->isAlert() ? 'table-warning' : '') }}">
                                            <td>
                                                <div>
                                                    <span class="text-muted me-1">↳</span>
                                                    {!! $avLabels ?: '<em class="text-muted">Sin atributos</em>' !!}
                                                </div>
                                                <small class="text-muted" style="padding-left:1.2rem">SKU: {{ $vr->variant?->sku ?? '—' }}</small>
                                            </td>
                                            @if($isGlobal)<td></td>@endif
                                            <td>
                                                <span class="badge {{ ($vr->warehouse->type ?? '') === 'exhibition' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                                    {{ $vr->warehouse->name ?? 'Bodega Principal' }}
                                                </span>
                                            </td>
                                            <td class="text-center fw-medium">{{ $vr->quantity }}</td>
                                            <td class="text-center"><span class="badge bg-warning-subtle text-warning">{{ $vr->reserved_quantity }}</span></td>
                                            <td class="text-center fw-bold {{ $vr->isOutOfStock() ? 'text-danger' : 'text-success' }}">{{ $vr->available_quantity }}</td>
                                            <td class="text-center">
                                                @can('gestionar-inventario')
                                                <form class="min-stock-form"
                                                      action="{{ route('inventory.min-stock', $vr->id) }}"
                                                      method="POST">
                                                    @csrf @method('PATCH')
                                                    <input type="number" name="min_stock"
                                                           class="form-control form-control-sm text-center p-1"
                                                           style="width:60px"
                                                           value="{{ $vr->min_stock }}" min="0"
                                                           data-no-loader="true">
                                                    <button type="submit" class="btn btn-xs btn-outline-secondary btn-sm p-1" title="Guardar">
                                                        <i class="ri-save-line"></i>
                                                    </button>
                                                </form>
                                                @else
                                                    {{ $vr->min_stock }}
                                                @endcan
                                            </td>
                                            <td class="text-center">
                                                @if($vr->isOutOfStock())
                                                    <span class="badge bg-danger">Agotado</span>
                                                @elseif($vr->isAlert())
                                                    <span class="badge bg-warning text-dark">Crítico</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success">Normal</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{-- Las acciones están en la fila padre --}}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    @endif

                                @empty
                                    <tr>
                                        <td colspan="{{ $isGlobal ? 6 : 5 }}" class="text-center py-5">
                                            <i class="ri-stack-line fs-24 d-block mb-2 text-muted"></i>
                                            <p class="text-muted mb-0">
                                                @if($alertOnly)
                                                    No hay productos con alertas de stock crítico. ✅
                                                @else
                                                    No hay registros de inventario todavía.
                                                    @can('gestionar-inventario')
                                                        <a href="{{ route('inventory.adjust') }}">Registrar stock inicial</a>
                                                    @endcan
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $inventories->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
function toggleGroup(groupId) {
    const tbody = document.getElementById(groupId);
    if (!tbody) return;
    const isOpen = tbody.classList.toggle('show');

    // Rotar el ícono de la fila padre
    const parentRow = tbody.previousElementSibling;
    parentRow?.classList.toggle('expanded', isOpen);
}
</script>
@endsection
