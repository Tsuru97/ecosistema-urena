@extends('layouts.master')
@section('title') Órdenes de Compra y Gestión RVs @endsection

@section('css')
<style>
    .status-tabs .nav-link        { border-radius: 20px; padding: 4px 14px; font-size: 13px; }
    .status-tabs .nav-link.active { font-weight: 600; }
    .kpi-card                      { border: 1px solid rgba(0,0,0,0.08); transition: transform 0.15s ease; }
    .kpi-card:hover                { transform: translateY(-2px); }
</style>
@endsection

@section('content')
    <x-breadcrumb />

    {{-- ══════════════════════════════════════════════════════════════
         MÉTRICAS GLOBALES (KPIs - Fila 4 y 5 del Excel "Resumen")
         ══════════════════════════════════════════════════════════════ --}}
    {{-- Banner de aviso si los KPIs están adaptados a un filtro --}}
    @if($kpis['is_filtered'])
        <div class="alert alert-info border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-center justify-content-between fs-13">
            <div>
                <i class="ri-filter-3-line me-2 fs-15 text-info"></i>
                <strong>Métricas de Búsqueda Activa:</strong> Mostrando cálculos adaptados para el filtro aplicado ({{ $purchaseOrders->total() }} registros).
            </div>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-soft-info py-0 px-2 fw-semibold">
                <i class="ri-refresh-line me-1"></i> Ver {{ $kpis['scope_label'] }}
            </a>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════
         MÉTRICAS GLOBALES / CONTEXTUALES (KPIs - Fila 4 y 5 del Excel)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        {{-- 1. TOTAL NETO --}}
        <div class="col-6 col-lg">
            <div class="card kpi-card border-primary border-opacity-25 h-100 mb-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-xs rounded bg-primary-subtle flex-shrink-0">
                            <i class="ri-shopping-bag-3-line text-primary fs-5 d-flex align-items-center justify-content-center h-100"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fs-18 fw-bold text-primary text-truncate">${{ number_format($kpis['current']['total_purchased'], 2) }}</div>
                            <div class="text-muted small text-truncate">Total Neto (Facturado)</div>
                            @if($kpis['is_filtered'])
                                <div class="text-muted fs-11 text-truncate border-top pt-1 mt-1 opacity-75">
                                    {{ $kpis['scope_label'] }}: ${{ number_format($kpis['baseline']['total_purchased'], 2) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. TOTAL PAGADO --}}
        <div class="col-6 col-lg">
            <div class="card kpi-card border-success border-opacity-25 h-100 mb-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-xs rounded bg-success-subtle flex-shrink-0">
                            <i class="ri-checkbox-circle-line text-success fs-5 d-flex align-items-center justify-content-center h-100"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fs-18 fw-bold text-success text-truncate">${{ number_format($kpis['current']['total_paid'], 2) }}</div>
                            <div class="text-muted small text-truncate">Total Pagado</div>
                            @if($kpis['is_filtered'])
                                <div class="text-muted fs-11 text-truncate border-top pt-1 mt-1 opacity-75">
                                    {{ $kpis['scope_label'] }}: ${{ number_format($kpis['baseline']['total_paid'], 2) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. SALDO TOTAL PENDIENTE --}}
        <div class="col-6 col-lg">
            <div class="card kpi-card border-danger border-opacity-25 h-100 mb-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-xs rounded bg-danger-subtle flex-shrink-0">
                            <i class="ri-money-dollar-circle-line text-danger fs-5 d-flex align-items-center justify-content-center h-100"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fs-18 fw-bold text-danger text-truncate">${{ number_format($kpis['current']['pending_debt'], 2) }}</div>
                            <div class="text-muted small text-truncate">Saldo Pendiente</div>
                            @if($kpis['is_filtered'])
                                <div class="text-muted fs-11 text-truncate border-top pt-1 mt-1 opacity-75">
                                    {{ $kpis['scope_label'] }}: ${{ number_format($kpis['baseline']['pending_debt'], 2) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. % EFICIENCIA DE PAGO --}}
        <div class="col-6 col-lg">
            <div class="card kpi-card border-info border-opacity-25 h-100 mb-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-xs rounded bg-info-subtle flex-shrink-0">
                            <i class="ri-line-chart-line text-info fs-5 d-flex align-items-center justify-content-center h-100"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fs-18 fw-bold text-info text-truncate">{{ $kpis['current']['payment_efficiency'] }}%</div>
                            <div class="text-muted small text-truncate">% Eficiencia Pago</div>
                            @if($kpis['is_filtered'])
                                <div class="text-muted fs-11 text-truncate border-top pt-1 mt-1 opacity-75">
                                    {{ $kpis['scope_label'] }}: {{ $kpis['baseline']['payment_efficiency'] }}%
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. RVs / FACTURAS PENDIENTES --}}
        <div class="col-6 col-lg">
            <div class="card kpi-card border-warning border-opacity-25 h-100 mb-0 shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-xs rounded bg-warning-subtle flex-shrink-0">
                            <i class="ri-time-line text-warning fs-5 d-flex align-items-center justify-content-center h-100"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fs-18 fw-bold text-warning text-truncate">{{ $kpis['current']['pending_payment_count'] }}</div>
                            <div class="text-muted small text-truncate">RVs Pendientes</div>
                            @if($kpis['is_filtered'])
                                <div class="text-muted fs-11 text-truncate border-top pt-1 mt-1 opacity-75">
                                    {{ $kpis['scope_label'] }}: {{ $kpis['baseline']['pending_payment_count'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

            <div class="card border-0 shadow-sm">
                <div class="card-header align-items-center d-flex flex-wrap gap-2 py-3 bg-light">
                    <h4 class="card-title mb-0 flex-grow-1 fw-bold">
                        <i class="ri-article-line me-2 text-primary"></i>Gestión y Control de RVs / Facturación
                    </h4>
                    @can('crear-ordenes-compra')
                        @if($isGlobal || $activeStoreId)
                            <a href="{{ route('purchase-orders.create') }}" class="btn btn-success btn-sm btn-label waves-effect fw-semibold">
                                <i class="ri-add-line label-icon align-middle me-2"></i> Nueva Orden de Compra
                            </a>
                        @endif
                    @endcan
                </div>

                <div class="card-body">

                    {{-- Filtros Avanzados para Gestión de RVs --}}
                    <form action="{{ route('purchase-orders.index') }}" method="GET" class="mb-4 p-3 bg-light rounded border border-light-subtle">
                        <div class="row g-2 align-items-end">
                            {{-- Búsqueda por Folio / RV --}}
                            <div class="col-md-3">
                                <label class="form-label fs-12 mb-1 fw-semibold text-muted">Buscar Folio / RV</label>
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm"
                                           value="{{ $search }}" placeholder="Ej: OC-2026-0001, F-1029...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>

                            {{-- Seleccionar Proveedor --}}
                            <div class="col-md-3">
                                <label class="form-label fs-12 mb-1 fw-semibold text-muted">Proveedor</label>
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">— Todos los Proveedores —</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}" {{ $supplierId == $sup->id ? 'selected' : '' }}>
                                            {{ $sup->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Estado de Pago --}}
                            <div class="col-md-2">
                                <label class="form-label fs-12 mb-1 fw-semibold text-muted">Estatus Pago</label>
                                <select name="payment_status" class="form-select form-select-sm">
                                    <option value="">— Todos —</option>
                                    <option value="pendiente" {{ $paymentStatus === 'pendiente' ? 'selected' : '' }}>Sin Pagos</option>
                                    <option value="pago_parcial" {{ $paymentStatus === 'pago_parcial' ? 'selected' : '' }}>Pago Parcial</option>
                                    <option value="pagada" {{ $paymentStatus === 'pagada' ? 'selected' : '' }}>Pagada (Saldada)</option>
                                </select>
                            </div>

                            {{-- Factura Adjunta --}}
                            <div class="col-md-2">
                                <label class="form-label fs-12 mb-1 fw-semibold text-muted">Factura Adjunta</label>
                                <select name="has_invoice" class="form-select form-select-sm">
                                    <option value="">— Todas —</option>
                                    <option value="with" {{ $hasInvoice === 'with' ? 'selected' : '' }}>Con Factura / XML</option>
                                    <option value="without" {{ $hasInvoice === 'without' ? 'selected' : '' }}>Sin Factura</option>
                                </select>
                            </div>

                            {{-- Rango Fecha Desde --}}
                            <div class="col-md-2">
                                <label class="form-label fs-12 mb-1 fw-semibold text-muted">Fecha Desde</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                            </div>

                            {{-- Rango Fecha Hasta --}}
                            <div class="col-md-2">
                                <label class="form-label fs-12 mb-1 fw-semibold text-muted">Fecha Hasta</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                            </div>

                            {{-- Preservar estado de tab logístico --}}
                            @if($status)
                                <input type="hidden" name="status" value="{{ $status }}">
                            @endif

                            {{-- Botones de Acción --}}
                            <div class="col-md-4 d-flex gap-2 align-self-end mt-2 mt-md-0">
                                <button type="submit" class="btn btn-primary btn-sm fw-semibold px-3">
                                    <i class="ri-filter-3-line me-1"></i> Aplicar Filtros
                                </button>
                                @if($search || $paymentStatus || $supplierId || $dateFrom || $dateTo || $hasInvoice)
                                    <a href="{{ route('purchase-orders.index', array_filter(['status' => $status])) }}" class="btn btn-soft-secondary btn-sm fw-semibold">
                                        <i class="ri-close-line me-1"></i> Limpiar Filtros
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    {{-- Tabs de estado logístico --}}
                    <ul class="nav status-tabs gap-1 mb-3">
                        @php
                            $tabs = [
                                ''          => ['label' => 'Todos',       'icon' => 'ri-list-check'],
                                'pendiente' => ['label' => 'Pendientes',  'icon' => 'ri-time-line'],
                                'aprobada'  => ['label' => 'Aprobadas',   'icon' => 'ri-checkbox-circle-line'],
                                'recibida'  => ['label' => 'Recibidas',   'icon' => 'ri-truck-line'],
                                'cancelada' => ['label' => 'Canceladas',  'icon' => 'ri-close-circle-line'],
                            ];
                        @endphp
                        @foreach($tabs as $val => $tab)
                            <li class="nav-item">
                                <a href="{{ route('purchase-orders.index', array_merge(request()->except('status', 'page'), $val ? ['status' => $val] : [])) }}"
                                   class="nav-link {{ $status === $val ? 'active btn-primary text-white' : 'btn-outline-secondary' }}">
                                    <i class="{{ $tab['icon'] }} me-1"></i>
                                    {{ $tab['label'] }}
                                    @if($val && ($counts[$val] ?? 0) > 0)
                                        <span class="badge bg-white text-dark ms-1">{{ $counts[$val] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    {{-- ══════════════════════════════════════════════════════════
                         TABLA PRINCIPAL DE FOLIOS (Columnas A - L del Excel)
                         ══════════════════════════════════════════════════════════ --}}
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-striped table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio / RV</th>
                                    <th>Fechas (OC / Factura)</th>
                                    <th>Proveedor</th>
                                    <th class="text-center">Items</th>
                                    <th class="text-end">Imp. Bruto</th>
                                    <th class="text-end">Descuento</th>
                                    <th class="text-end">Imp. Neto</th>
                                    <th class="text-end">Monto Pagado</th>
                                    <th class="text-end">Saldo Pendiente</th>
                                    <th class="text-center">Estatus OC</th>
                                    <th class="text-center">Estatus Pago</th>
                                    <th class="text-center">Unidades</th>
                                    <th style="width:50px" class="text-center">Ver</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseOrders as $order)
                                    @php
                                        $badge      = $order->statusBadge();
                                        $grossTotal = $order->effectiveGrossAmount();
                                        $discTotal  = $order->effectiveDiscountAmount();
                                        $netTotal   = $order->effectiveNetAmount();
                                        $paid       = (float) $order->amount_paid;
                                        $debt       = $order->pendingBalance();
                                        
                                        $pStatus    = $order->payment_status ?: ($paid >= $netTotal ? 'pagada' : ($paid > 0 ? 'pago_parcial' : 'pendiente'));
                                        $pBadge     = match($pStatus) {
                                            'pagada'       => ['label' => 'PAGADA',       'class' => 'bg-success text-white'],
                                            'pago_parcial' => ['label' => 'PAGO PARCIAL', 'class' => 'bg-info text-white'],
                                            default        => ['label' => 'PENDIENTE',    'class' => 'bg-warning text-dark'],
                                        };

                                        $uStatus    = $order->calculatedUnitStatus();
                                        $uBadge     = match($uStatus) {
                                            'completa'   => ['label' => 'COMPLETA',   'class' => 'bg-success-subtle text-success border border-success-subtle'],
                                            default      => ['label' => 'INCOMPLETA', 'class' => 'bg-warning-subtle text-warning border border-warning-subtle'],
                                        };
                                    @endphp
                                    <tr>
                                        {{-- Folio (A) --}}
                                        <td>
                                            <a href="{{ route('purchase-orders.show', $order) }}" class="fw-bold text-primary">
                                                {{ $order->folio }}
                                            </a>
                                            @if($order->rv_folio)
                                                <div class="small text-muted fw-medium">
                                                    <i class="ri-receipt-line me-1 text-secondary"></i>{{ $order->rv_folio }}
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Fechas (B) --}}
                                        <td class="fs-12">
                                            <div title="Fecha de Creación de la Orden">
                                                <span class="text-muted small">OC:</span> <span class="fw-semibold text-dark">{{ $order->created_at->format('d/m/Y') }}</span>
                                            </div>
                                            @if($order->issued_at)
                                                <div class="fs-11 text-success fw-medium" title="Fecha de Emisión de la Factura">
                                                    <i class="ri-receipt-line me-1"></i>Factura: {{ $order->issued_at->format('d/m/Y') }}
                                                </div>
                                            @elseif($order->rv_folio || $order->hasInvoicePdf() || $order->hasInvoiceXml())
                                                <div class="fs-11 text-info fw-medium" title="Factura registrada / subida">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Factura subida
                                                </div>
                                            @else
                                                <div class="fs-11 text-muted opacity-75">
                                                    <i class="ri-file-line me-1"></i>Sin Factura
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Proveedor (C) --}}
                                        <td>
                                            <div class="fw-semibold fs-13 text-dark d-flex align-items-center flex-wrap gap-1">
                                                {{ $order->supplier?->name ?? '⚠️ Sin Proveedor' }}
                                                @if(str_contains($order->notes ?? '', 'Pendiente de Asignar Proveedor') || !$order->supplier_id)
                                                    <span class="badge bg-warning text-dark fs-10 px-1" title="Requiere asignación explícita de proveedor por compras"><i class="ri-alert-line me-1"></i>Por Asignar</span>
                                                @endif
                                            </div>
                                            <small class="text-muted fs-11">{{ $order->store->name }}</small>
                                        </td>

                                        {{-- Cant. Items / Unidades (D) --}}
                                        <td class="text-center">
                                            @php
                                                $totalUnits = $order->items->sum('quantity_ordered');
                                                $lineCount  = $order->items_count ?? $order->items->count();
                                            @endphp
                                            <span class="badge bg-light text-dark border px-2 fs-12" title="{{ $lineCount }} producto(s) en {{ $totalUnits }} unidad(es) solicitadas">
                                                {{ $totalUnits }} u. <small class="text-muted">({{ $lineCount }} prod)</small>
                                            </span>
                                        </td>

                                        {{-- Importe Bruto (E) --}}
                                        <td class="text-end text-muted small">
                                            ${{ number_format($grossTotal, 2) }}
                                        </td>

                                        {{-- Total Descuento (F) --}}
                                        <td class="text-end text-danger small">
                                            @if($discTotal > 0)
                                                -${{ number_format($discTotal, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>

                                        {{-- Importe Neto (G) --}}
                                        <td class="text-end fw-bold text-dark fs-13">
                                            ${{ number_format($netTotal, 2) }}
                                        </td>

                                        {{-- Monto Pagado (H) --}}
                                        <td class="text-end text-success fw-semibold fs-13">
                                            ${{ number_format($paid, 2) }}
                                        </td>

                                        {{-- Saldo Pendiente (I) --}}
                                        <td class="text-end fw-bold fs-13 {{ $debt > 0 ? 'text-danger' : 'text-success' }}">
                                            ${{ number_format($debt, 2) }}
                                        </td>

                                        {{-- Estatus General OC --}}
                                        <td class="text-center">
                                            <span class="badge bg-{{ $badge['color'] }} px-2 py-1 fs-11">{{ strtoupper($badge['label']) }}</span>
                                        </td>

                                        {{-- Estatus Pago (K) --}}
                                        <td class="text-center">
                                            <span class="badge {{ $pBadge['class'] }} px-2 py-1 fs-11">{{ $pBadge['label'] }}</span>
                                        </td>

                                        {{-- Estatus Unidades (L) --}}
                                        <td class="text-center">
                                            <span class="badge {{ $uBadge['class'] }} px-2 py-1 fs-11">{{ $uBadge['label'] }}</span>
                                        </td>

                                        {{-- Acciones --}}
                                        <td class="text-center">
                                            <a href="{{ route('purchase-orders.show', $order) }}"
                                               class="btn btn-sm btn-soft-primary btn-icon" title="Ver Ficha Completa">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center py-5">
                                            <i class="ri-article-line fs-24 d-block mb-2 text-muted"></i>
                                            <p class="text-muted mb-0">No hay órdenes de compra ni facturas registradas.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $purchaseOrders->appends(request()->all())->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
