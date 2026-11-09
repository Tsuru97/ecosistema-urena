@extends('layouts.master')
@section('title') Detalle de Turno {{ $shift->folio }} @endsection

@section('content')
<x-breadcrumb />

@php
    $isClosed = $shift->isClosed();
    $diff     = $isClosed ? (float) ($shift->difference_cash ?? 0) : null;
    $totals   = $isClosed ? [
        'total_sales_cash'           => (float) $shift->total_sales_cash,
        'total_sales_card'           => (float) $shift->total_sales_card,
        'total_sales_transfer'       => (float) $shift->total_sales_transfer,
        'total_sales_other'          => (float) $shift->total_sales_other,
        'total_special_orders_cash'  => (float) $shift->total_special_orders_cash,
        'total_cash_in'              => (float) $shift->total_cash_in,
        'total_cash_out'             => (float) $shift->total_cash_out,
        'total_vault_cash'           => (float) $shift->total_vault_cash,
        'total_expenses_cash'        => (float) $shift->total_expenses_cash,
        'expected_cash'              => (float) $shift->expected_cash,
        'total_sales_amount'         => (float) $shift->total_sales_amount,
        'sales_count'                => (int) $shift->sales_count,
        'opening_amount'             => (float) $shift->opening_amount,
    ] : $liveTotals;
@endphp

{{-- Cabecera Principal del Turno --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                @php
                    $storeLogo = $shift->store?->logo ? (str_starts_with($shift->store->logo, 'http') ? $shift->store->logo : asset('storage/' . $shift->store->logo)) : null;
                    $logoUrl = $storeLogo ?: asset('build/images/urena-logo-full.png');
                @endphp
                <img src="{{ $logoUrl }}" alt="Logo Empresa" style="max-height: 52px; max-width: 150px; object-fit: contain;">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h3 class="fw-bold mb-0 text-dark">{{ $shift->folio }}</h3>
                        @if($shift->isOpen())
                            <span class="badge bg-success fs-13 py-1 px-3">
                                <i class="ri-checkbox-circle-line me-1"></i> Turno Abierto
                            </span>
                        @else
                            <span class="badge bg-secondary fs-13 py-1 px-3">
                                <i class="ri-lock-line me-1"></i> Turno Cerrado (Corte Z)
                            </span>
                        @endif
                    </div>
                    <div class="text-muted small">
                        <i class="ri-store-2-line me-1"></i> Sucursal: <strong class="text-dark">{{ $shift->store?->name ?? 'N/A' }}</strong>
                        <span class="mx-2">•</span>
                        <i class="ri-user-3-line me-1"></i> Cajero: <strong class="text-dark">{{ $shift->cashier?->name ?? 'N/A' }}</strong>
                        @if($shift->closedBy)
                            <span class="mx-2">•</span>
                            <i class="ri-shield-user-line me-1"></i> Cerró: <strong class="text-dark">{{ $shift->closedBy->name }}</strong>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('cash-shifts.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-arrow-left-line me-1"></i> Volver al Historial
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.open('{{ route('cash-shifts.print', $shift->id) }}?type={{ $isClosed ? 'z' : 'x' }}&format=ticket', '_blank', 'width=450,height=650')" title="Imprimir formato mini-ticket para impresora térmica">
                    <i class="ri-printer-line me-1"></i> Ticket 80mm
                </button>
                <button type="button" class="btn btn-primary btn-sm fw-bold" onclick="window.open('{{ route('cash-shifts.print', $shift->id) }}?type={{ $isClosed ? 'z' : 'x' }}&format=a4', '_blank')" title="Imprimir comprobante ejecutivo en formato Hoja A4 / Carta">
                    <i class="ri-file-text-line me-1"></i> Imprimir Hoja A4
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Tarjetas de Balance y Arqueo --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <span class="text-muted small d-block">Fondo Inicial de Caja</span>
                <h4 class="fw-bold text-dark mb-1 mt-1">${{ number_format($totals['opening_amount'], 2) }}</h4>
                <small class="text-muted"><i class="ri-time-line me-1"></i>{{ $shift->opened_at->format('d/m/Y H:i') }}</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <span class="text-muted small d-block">Total Venta Cobrada</span>
                <h4 class="fw-bold text-success mb-1 mt-1">${{ number_format($totals['total_sales_amount'], 2) }}</h4>
                <small class="text-muted">{{ $totals['sales_count'] }} ticket(s) de venta</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <span class="text-muted small d-block">Efectivo Calculado / Esperado</span>
                <h4 class="fw-bold text-primary mb-1 mt-1">${{ number_format($totals['expected_cash'], 2) }}</h4>
                <small class="text-muted">Fondo + Efectivo cobrado + Entradas - Sangrías</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <span class="text-muted small d-block">Arqueo Físico (Resultado)</span>
                @if($isClosed)
                    <div class="d-flex align-items-baseline gap-2 mt-1 mb-1">
                        <h4 class="fw-bold mb-0 text-dark">${{ number_format($shift->actual_cash, 2) }}</h4>
                        @if(abs($diff) < 0.01)
                            <span class="badge bg-success-subtle text-success">Exacto</span>
                        @elseif($diff > 0)
                            <span class="badge bg-info-subtle text-info">+${{ number_format($diff, 2) }} Sobrante</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">-${{ number_format(abs($diff), 2) }} Faltante</span>
                        @endif
                    </div>
                    <small class="text-muted">Cerrado el {{ $shift->closed_at?->format('d/m/Y H:i') }}</small>
                @else
                    <h4 class="fw-bold text-warning mb-1 mt-1">En Operación</h4>
                    <small class="text-muted">Pendiente de cierre y conteo</small>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Desglose por Formas de Cobro y Flujo de Cajón --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light py-2">
                <h6 class="card-title mb-0 fs-13"><i class="ri-bank-card-line me-1 text-primary"></i>Desglose de Cobros por Método</h6>
            </div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Efectivo (Ventas y Apartados):</span>
                    <strong class="text-dark">${{ number_format($totals['total_sales_cash'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Efectivo (Pedidos Especiales):</span>
                    <strong class="text-dark">${{ number_format($totals['total_special_orders_cash'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Tarjetas (Terminales Bancarias):</span>
                    <strong class="text-dark">${{ number_format($totals['total_sales_card'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Transferencias Bancarias:</span>
                    <strong class="text-dark">${{ number_format($totals['total_sales_transfer'], 2) }}</strong>
                </div>
                @if(($totals['total_sales_other'] ?? 0) > 0)
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Otros Métodos:</span>
                    <strong class="text-dark">${{ number_format($totals['total_sales_other'], 2) }}</strong>
                </div>
                @endif
                <div class="d-flex justify-content-between pt-2 fs-15 fw-bold text-success">
                    <span>Total Cobrado:</span>
                    <span>${{ number_format($totals['total_sales_amount'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light py-2">
                <h6 class="card-title mb-0 fs-13"><i class="ri-safe-2-line me-1 text-primary"></i>Conciliación de Efectivo en Cajón</h6>
            </div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">(+) Fondo de Caja Inicial:</span>
                    <strong class="text-dark">${{ number_format($totals['opening_amount'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">(+) Ventas y Anticipos en Efectivo:</span>
                    <strong class="text-success">${{ number_format($totals['total_sales_cash'] + $totals['total_special_orders_cash'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">(+) Entradas Manuales de Efectivo:</span>
                    <strong class="text-info">${{ number_format($totals['total_cash_in'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">(-) Retiros / Sangrías Totales:</span>
                    <strong class="text-danger">-${{ number_format($totals['total_cash_out'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 ps-3 border-bottom small text-muted">
                    <span>↳ A Bóveda / Banco:</span>
                    <strong>-${{ number_format($totals['total_vault_cash'] ?? 0, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 ps-3 border-bottom small text-muted">
                    <span>↳ Gastos Operativos de Tienda:</span>
                    <strong>-${{ number_format($totals['total_expenses_cash'] ?? 0, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between pt-2 fs-15 fw-bold text-primary">
                    <span>(=) Efectivo Teórico Esperado:</span>
                    <span>${{ number_format($totals['expected_cash'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pestañas de Detalle --}}
@php
    $activeTab = request()->query('tab', 'movements');
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-header p-0 border-bottom">
        <ul class="nav nav-tabs nav-tabs-custom nav-success p-2 pb-0 mb-0" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'movements' ? 'active' : '' }} fw-semibold" data-bs-toggle="tab" href="#tab-movements" role="tab">
                    <i class="ri-exchange-dollar-line me-1"></i> Movimientos de Efectivo ({{ $shift->movements->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'sales' ? 'active' : '' }} fw-semibold" data-bs-toggle="tab" href="#tab-sales" role="tab">
                    <i class="ri-shopping-cart-2-line me-1"></i> Ventas del Turno ({{ $shift->sales->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'special-orders' ? 'active' : '' }} fw-semibold" data-bs-toggle="tab" href="#tab-special-orders" role="tab">
                    <i class="ri-bookmark-3-line me-1"></i> Anticipos Pedidos Especiales ({{ $shift->specialOrderPayments->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'notes' ? 'active' : '' }} fw-semibold" data-bs-toggle="tab" href="#tab-notes" role="tab">
                    <i class="ri-chat-1-line me-1"></i> Notas y Observaciones
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content">
            {{-- Tab 1: Movimientos de Efectivo --}}
            <div class="tab-pane {{ $activeTab === 'movements' ? 'active' : '' }}" id="tab-movements" role="tabpanel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary fw-semibold fs-12">
                            <i class="ri-history-line me-1"></i>Línea de Tiempo del Turno
                        </span>
                        <span class="text-muted small sort-label">
                            Mostrando eventos en orden {{ ($sort ?? 'asc') === 'asc' ? 'cronológico (desde apertura hasta el cierre)' : 'inverso (más recientes primero)' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Ordenar por:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'asc', 'tab' => 'movements']) }}" 
                               onclick="toggleSortInstant('movements', 'asc', event)"
                               class="btn btn-sort-asc {{ ($sort ?? 'asc') === 'asc' ? 'btn-primary' : 'btn-outline-secondary' }}" 
                               title="Línea temporal natural: del inicio al final del turno">
                                <i class="ri-sort-asc me-1"></i> Cronológico (Apertura ➔ Cierre)
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'desc', 'tab' => 'movements']) }}" 
                               onclick="toggleSortInstant('movements', 'desc', event)"
                               class="btn btn-sort-desc {{ ($sort ?? 'asc') === 'desc' ? 'btn-primary' : 'btn-outline-secondary' }}" 
                               title="Más recientes primero">
                                <i class="ri-sort-desc me-1"></i> Recientes primero
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th style="width: 130px;">Hora / Fecha</th>
                                <th>Tipo / Destino</th>
                                <th class="text-end">Monto</th>
                                <th>Concepto / Motivo</th>
                                <th>Notas Adicionales</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-movements">
                            @forelse($shift->movements as $index => $m)
                            <tr data-timestamp="{{ $m->created_at ? $m->created_at->timestamp : 0 }}" data-id="{{ $m->id }}">
                                <td class="text-center text-muted fw-bold row-num">#{{ $index + 1 }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $m->created_at ? $m->created_at->format('H:i:s') : '—' }}</span>
                                    <small class="text-muted d-block" style="font-size: 11px;">{{ $m->created_at ? $m->created_at->format('d/m/Y') : '' }}</small>
                                </td>
                                <td>
                                    @if($m->isEntry())
                                        <span class="badge bg-success-subtle text-success fw-bold"><i class="ri-add-circle-line me-1"></i>(+) Entrada</span>
                                    @elseif($m->category === 'gasto_operativo')
                                        <span class="badge bg-warning-subtle text-warning fw-bold"><i class="ri-file-list-3-line me-1"></i>(-) Gasto Tienda</span>
                                        @if($m->expense_id)
                                            <a href="{{ route('expenses.index') }}?search={{ urlencode($m->concept) }}" target="_blank" class="d-block text-primary small mt-1" title="Ver en Módulo de Gastos" style="font-size: 11px;">
                                                <i class="ri-external-link-line me-1"></i>Gasto #{{ $m->expense_id }}
                                            </a>
                                        @endif
                                    @else
                                        <span class="badge bg-danger-subtle text-danger fw-bold"><i class="ri-bank-line me-1"></i>(-) Bóveda / Banco</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $m->isEntry() ? 'text-success' : 'text-danger' }}">
                                    {{ $m->isEntry() ? '+' : '-' }}${{ number_format($m->amount, 2) }}
                                </td>
                                <td class="fw-semibold">{{ $m->concept }}</td>
                                <td class="text-muted small">{{ $m->notes ?? '—' }}</td>
                                <td>{{ $m->user?->name ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No se registraron entradas manuales ni retiros en este turno.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab 2: Ventas del Turno --}}
            <div class="tab-pane {{ $activeTab === 'sales' ? 'active' : '' }}" id="tab-sales" role="tabpanel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success fw-semibold fs-12">
                            <i class="ri-shopping-cart-2-line me-1"></i>Historial de Tickets
                        </span>
                        <span class="text-muted small sort-label">
                            {{ $shift->sales->count() }} venta(s) • Orden {{ ($sort ?? 'asc') === 'asc' ? 'cronológico' : 'reciente' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Ordenar por:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'asc', 'tab' => 'sales']) }}" 
                               onclick="toggleSortInstant('sales', 'asc', event)"
                               class="btn btn-sort-asc {{ ($sort ?? 'asc') === 'asc' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ri-sort-asc me-1"></i> Cronológico
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'desc', 'tab' => 'sales']) }}" 
                               onclick="toggleSortInstant('sales', 'desc', event)"
                               class="btn btn-sort-desc {{ ($sort ?? 'asc') === 'desc' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ri-sort-desc me-1"></i> Recientes
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Folio Ticket</th>
                                <th>Hora / Fecha</th>
                                <th>Cliente</th>
                                <th class="text-end">Total Venta</th>
                                <th class="text-end">Saldo (Apartado)</th>
                                <th>Forma(s) de Pago</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-sales">
                            @forelse($shift->sales as $index => $s)
                            <tr data-timestamp="{{ $s->created_at ? $s->created_at->timestamp : 0 }}" data-id="{{ $s->id }}">
                                <td class="text-center text-muted fw-bold row-num">#{{ $index + 1 }}</td>
                                <td class="fw-bold text-primary">{{ $s->ticket_folio }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $s->created_at ? $s->created_at->format('H:i:s') : '—' }}</span>
                                    <small class="text-muted d-block" style="font-size: 11px;">{{ $s->created_at ? $s->created_at->format('d/m/Y') : '' }}</small>
                                </td>
                                <td>{{ $s->customer ? $s->customer->name . ' ' . $s->customer->last_name : 'Público en General' }}</td>
                                <td class="text-end fw-bold">${{ number_format($s->total, 2) }}</td>
                                <td class="text-end {{ $s->balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    ${{ number_format($s->balance, 2) }}
                                </td>
                                <td>
                                    @foreach($s->payments as $p)
                                        <span class="badge bg-light text-dark border me-1">
                                            {{ $p->paymentMethod?->name ?? 'Pago' }}: ${{ number_format($p->amount, 2) }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    @if($s->status === 'pagada')
                                        <span class="badge bg-success-subtle text-success">Pagada</span>
                                    @elseif($s->status === 'apartado')
                                        <span class="badge bg-warning-subtle text-warning">Apartado</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($s->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No se registraron ventas en este turno.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab 3: Anticipos de Pedidos Especiales --}}
            <div class="tab-pane {{ $activeTab === 'special-orders' ? 'active' : '' }}" id="tab-special-orders" role="tabpanel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info-subtle text-info fw-semibold fs-12">
                            <i class="ri-bookmark-3-line me-1"></i>Anticipos de Pedidos Especiales
                        </span>
                        <span class="text-muted small sort-label">
                            {{ $shift->specialOrderPayments->count() }} anticipo(s) • Orden {{ ($sort ?? 'asc') === 'asc' ? 'cronológico' : 'reciente' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Ordenar por:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'asc', 'tab' => 'special-orders']) }}" 
                               onclick="toggleSortInstant('special-orders', 'asc', event)"
                               class="btn btn-sort-asc {{ ($sort ?? 'asc') === 'asc' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ri-sort-asc me-1"></i> Cronológico
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'desc', 'tab' => 'special-orders']) }}" 
                               onclick="toggleSortInstant('special-orders', 'desc', event)"
                               class="btn btn-sort-desc {{ ($sort ?? 'asc') === 'desc' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="ri-sort-desc me-1"></i> Recientes
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Hora / Fecha</th>
                                <th>Folio Pedido</th>
                                <th>Cliente</th>
                                <th class="text-end">Anticipo / Abono</th>
                                <th>Método de Pago</th>
                                <th>Referencia / Notas</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-special-orders">
                            @forelse($shift->specialOrderPayments as $index => $sop)
                            <tr data-timestamp="{{ $sop->created_at ? $sop->created_at->timestamp : 0 }}" data-id="{{ $sop->id }}">
                                <td class="text-center text-muted fw-bold row-num">#{{ $index + 1 }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $sop->created_at ? $sop->created_at->format('H:i:s') : '—' }}</span>
                                    <small class="text-muted d-block" style="font-size: 11px;">{{ $sop->created_at ? $sop->created_at->format('d/m/Y') : '' }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('special-orders.show', $sop->special_order_id) }}" class="fw-bold text-primary">
                                        {{ $sop->specialOrder?->folio ?? 'PEDIDO #' . $sop->special_order_id }}
                                    </a>
                                </td>
                                <td>{{ $sop->specialOrder?->customer?->full_name ?? 'N/A' }}</td>
                                <td class="text-end fw-bold text-success">${{ number_format($sop->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $sop->paymentMethod?->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $sop->notes ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No se recibieron anticipos de pedidos especiales durante este turno.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab 4: Notas y Observaciones --}}
            <div class="tab-pane {{ $activeTab === 'notes' ? 'active' : '' }}" id="tab-notes" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="ri-key-2-line me-1 text-success"></i>Notas de Apertura de Caja</h6>
                            <p class="text-muted mb-0">{{ $shift->opening_notes ?: 'Sin observaciones registradas al momento de abrir el turno.' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="ri-lock-line me-1 text-danger"></i>Notas de Cierre y Arqueo (Corte Z)</h6>
                            <p class="text-muted mb-0">{{ $shift->closing_notes ?: 'Sin observaciones registradas al momento de cerrar el turno.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function toggleSortInstant(target, direction, event) {
        if (event) {
            event.preventDefault();
        }

        const tbody = document.getElementById('tbody-' + target);
        if (!tbody) {
            if (event && event.currentTarget && event.currentTarget.href) {
                window.location.href = event.currentTarget.href;
            }
            return;
        }

        const rows = Array.from(tbody.querySelectorAll('tr[data-timestamp]'));
        if (rows.length === 0) return;

        rows.sort((a, b) => {
            const timeA = parseInt(a.dataset.timestamp || '0', 10);
            const timeB = parseInt(b.dataset.timestamp || '0', 10);
            const idA = parseInt(a.dataset.id || '0', 10);
            const idB = parseInt(b.dataset.id || '0', 10);

            if (timeA !== timeB) {
                return direction === 'asc' ? timeA - timeB : timeB - timeA;
            }
            return direction === 'asc' ? idA - idB : idB - idA;
        });

        rows.forEach((row, idx) => {
            tbody.appendChild(row);
            const numCell = row.querySelector('.row-num');
            if (numCell) {
                numCell.textContent = '#' + (idx + 1);
            }
        });

        const tabPane = document.getElementById('tab-' + target);
        if (tabPane) {
            const btnAsc = tabPane.querySelector('.btn-sort-asc');
            const btnDesc = tabPane.querySelector('.btn-sort-desc');

            if (btnAsc && btnDesc) {
                if (direction === 'asc') {
                    btnAsc.classList.remove('btn-outline-secondary');
                    btnAsc.classList.add('btn-primary');
                    btnDesc.classList.remove('btn-primary');
                    btnDesc.classList.add('btn-outline-secondary');
                } else {
                    btnDesc.classList.remove('btn-outline-secondary');
                    btnDesc.classList.add('btn-primary');
                    btnAsc.classList.remove('btn-primary');
                    btnAsc.classList.add('btn-outline-secondary');
                }
            }

            const label = tabPane.querySelector('.sort-label');
            if (label) {
                if (target === 'movements') {
                    label.textContent = direction === 'asc'
                        ? 'Mostrando eventos en orden cronológico (desde apertura hasta el cierre)'
                        : 'Mostrando eventos en orden inverso (más recientes primero)';
                } else if (target === 'sales') {
                    label.textContent = `${rows.length} venta(s) • Orden ${direction === 'asc' ? 'cronológico' : 'reciente'}`;
                } else if (target === 'special-orders') {
                    label.textContent = `${rows.length} anticipo(s) • Orden ${direction === 'asc' ? 'cronológico' : 'reciente'}`;
                }
            }
        }

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', direction);
            url.searchParams.set('tab', target);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Soporte de pestaña vía hash o query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const hash = window.location.hash;

        let targetHref = null;
        if (hash) {
            targetHref = hash;
        } else if (tabParam) {
            targetHref = '#tab-' + tabParam;
        }

        if (targetHref) {
            const triggerEl = document.querySelector(`a[data-bs-toggle="tab"][href="${targetHref}"]`);
            if (triggerEl && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                const tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }

        // Mantener URL actualizada al cambiar de pestaña
        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', (e) => {
                const href = e.target.getAttribute('href');
                if (href) {
                    const tabName = href.replace('#tab-', '');
                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.set('tab', tabName);
                        window.history.replaceState({}, '', url.toString());
                    } catch (err) {}
                }
            });
        });
    });
</script>
@endsection
