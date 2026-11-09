@extends('layouts.master')
@section('title') Historial de Ventas y Apartados @endsection

@section('content')
    <x-breadcrumb />

    {{-- Tarjetas KPI Superiores --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Ventas Hoy</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-arrow-right-up-line fs-13 align-middle"></i> Activas
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2">${{ number_format($kpis['total_today'] ?? 0, 2) }}</h4>
                            <span class="text-muted fs-12">Monto total vendido hoy</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="ri-money-dollar-circle-line text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Recaudado Hoy en Caja</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2 text-primary">${{ number_format($kpis['collected_today'] ?? 0, 2) }}</h4>
                            <span class="text-muted fs-12">Ingresos cobrados hoy (pagos/abonos)</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                <i class="ri-bank-card-line text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Apartados Activos</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-warning-subtle text-warning fs-12">{{ $kpis['layaways_count'] ?? 0 }} órdenes</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2 text-warning">${{ number_format($kpis['layaways_balance'] ?? 0, 2) }}</h4>
                            <span class="text-muted fs-12">Saldo total pendiente por liquidar</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                <i class="ri-time-line text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Ventas Canceladas</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2 text-danger">{{ $kpis['cancelled_count'] ?? 0 }}</h4>
                            <span class="text-muted fs-12">Operaciones canceladas / stock liberado</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-3">
                                <i class="ri-close-circle-line text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Contenido Principal --}}
    <div class="row">
        <div class="col-lg-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-history-line me-1"></i> Historial y Seguimiento Comercial de Ventas
                    </h4>
                    <div class="flex-shrink-0 d-flex gap-2">
                        <a href="{{ route('pos.quotes') }}" class="btn btn-soft-info btn-sm">
                            <i class="ri-file-list-3-line me-1"></i> Ver Cotizaciones
                        </a>
                        <a href="{{ route('pos.index') }}" class="btn btn-success btn-label btn-sm">
                            <i class="ri-store-2-line label-icon align-middle fs-16 me-2"></i> Nueva Venta en POS
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Buscador y filtros --}}
                    <form action="{{ route('pos.sales') }}" method="GET" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">Búsqueda rápida</label>
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control form-control-sm search" value="{{ request('search') }}" placeholder="Ej. VTA-2026-0001, #42, cliente o teléfono...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-12 text-muted mb-1">Estado de Venta</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Todos los Estados</option>
                                    <option value="pagada" {{ request('status') == 'pagada' ? 'selected' : '' }}>Pagada</option>
                                    <option value="apartado" {{ request('status') == 'apartado' ? 'selected' : '' }}>Apartado</option>
                                    <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-12 text-muted mb-1">Vendedor Comisionista</label>
                                <select name="seller_id" class="form-select form-select-sm">
                                    <option value="">Todos los Vendedores</option>
                                    @foreach($sellers as $s)
                                        <option value="{{ $s->id }}" {{ request('seller_id') == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }} {{ $s->lastname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-12 text-muted mb-1">Desde</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-12 text-muted mb-1">Hasta</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-1 d-flex align-items-end gap-1">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Filtrar"><i class="ri-filter-2-line"></i></button>
                                <a href="{{ route('pos.sales') }}" class="btn btn-soft-secondary btn-sm flex-fill" title="Limpiar"><i class="ri-refresh-line"></i></a>
                            </div>
                        </div>
                    </form>

                    {{-- Tabla de Ventas --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 130px;">Folio</th>
                                    <th scope="col" style="width: 140px;">Fecha / Hora</th>
                                    <th scope="col">Cliente Titular</th>
                                    <th scope="col">Vendedor / Cajero</th>
                                    <th scope="col">Sucursal</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col" class="text-end">Saldo Pendiente</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center" style="width: 160px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                    <tr id="sale-row-{{ $sale->id }}">
                                        <td>
                                            <span class="fw-bold text-primary font-monospace">{{ $sale->ticket_folio }}</span>
                                            @if($sale->hasDelivery())
                                                @php
                                                    $delStatus = $sale->delivery_status ?: 'pendiente';
                                                    $delBadgeCls = match($delStatus) {
                                                        'entregado' => 'bg-success-subtle text-success border border-success-subtle',
                                                        'en_ruta'   => 'bg-info-subtle text-info border border-info-subtle',
                                                        'cancelado' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                                        default     => 'bg-primary-subtle text-primary',
                                                    };
                                                    $delBadgeLabel = match($delStatus) {
                                                        'entregado' => '✓ Entregado',
                                                        'en_ruta'   => '🚚 En Ruta',
                                                        'cancelado' => 'Envío Cancelado',
                                                        default     => 'A Domicilio',
                                                    };
                                                @endphp
                                                <span id="sale-del-badge-{{ $sale->id }}" class="badge {{ $delBadgeCls }} d-block mt-1" style="font-size: 10px;" title="Entrega a Domicilio">
                                                    <i class="ri-truck-line me-1"></i>{{ $delBadgeLabel }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fs-12">{{ $sale->created_at->format('d/m/Y') }}</span>
                                            <small class="text-muted d-block fs-11">{{ $sale->created_at->format('H:i') }} hrs</small>
                                        </td>
                                        <td>
                                            @if($sale->customer)
                                                <span class="fw-semibold text-dark">{{ $sale->customer->full_name }}</span>
                                                @if($sale->customer->phone)
                                                    <small class="text-muted d-block"><i class="ri-phone-line me-1"></i>{{ $sale->customer->phone }}</small>
                                                @endif
                                            @elseif($sale->delivery_recipient_name)
                                                <span class="fw-semibold text-dark">{{ $sale->delivery_recipient_name }}</span>
                                                <small class="text-muted d-block"><i class="ri-truck-line me-1"></i>(Destinatario)</small>
                                            @else
                                                <span class="text-muted">Público en General</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <span class="badge bg-info-subtle text-info me-1" title="Vendedor Comisionista">
                                                    <i class="ri-user-star-line me-1"></i>{{ $sale->seller ? $sale->seller->name : 'N/A' }}
                                                </span>
                                            </div>
                                            @if($sale->cashier && (!$sale->seller || $sale->seller_id != $sale->user_id))
                                                <small class="text-muted d-block mt-1 fs-11">
                                                    Cajero: {{ $sale->cashier->name }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>{{ $sale->store ? $sale->store->name : 'Principal' }}</td>
                                        <td class="text-end">
                                            <span class="fw-bold font-monospace fs-13">${{ number_format($sale->total, 2) }}</span>
                                            @if($sale->discount_total > 0)
                                                <div class="text-success mt-1" style="font-size: 0.72rem; line-height: 1.1;">
                                                    Desc: -${{ number_format($sale->discount_total, 2) }}
                                                    @if($sale->quote && $sale->quote->discountAuthorization && $sale->quote->discountAuthorization->authorizer)
                                                        <br><span class="text-muted" style="font-size: 0.68rem;">(Aut: {{ $sale->quote->discountAuthorization->authorizer->name }})</span>
                                                    @endif
                                                </div>
                                            @endif
                                            @if(($sale->coupon_discount ?? 0) > 0)
                                                <div class="text-primary mt-1" style="font-size: 0.72rem; line-height: 1.1;">
                                                    <i class="ri-ticket-2-line me-1"></i>{{ $sale->coupon_code ?: 'Cupón' }}: -${{ number_format($sale->coupon_discount, 2) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($sale->balance > 0.01)
                                                <span class="fw-bold text-danger font-monospace fs-13">${{ number_format($sale->balance, 2) }}</span>
                                                <small class="text-muted d-block fs-11">Por liquidar</small>
                                            @else
                                                <span class="text-muted font-monospace">$0.00</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($sale->status === 'pagada')
                                                <span class="badge bg-success-subtle text-success fs-11">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Pagada
                                                </span>
                                            @elseif($sale->status === 'apartado')
                                                <span class="badge bg-warning-subtle text-warning fs-11">
                                                    <i class="ri-time-line me-1"></i>Apartado
                                                </span>
                                            @elseif($sale->status === 'cancelada')
                                                <span class="badge bg-danger-subtle text-danger fs-11">
                                                    <i class="ri-close-circle-line me-1"></i>Cancelada
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary fs-11">{{ ucfirst($sale->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                {{-- Ver Detalles Modal --}}
                                                <button type="button" class="btn btn-soft-info" title="Ver Detalles Completos" onclick="openSaleDetails({{ $sale->id }})">
                                                    <i class="ri-eye-line"></i>
                                                </button>

                                                {{-- Imprimir Orden / Anticipo --}}
                                                @if($sale->status === 'apartado' || $sale->balance > 0.01)
                                                    <a href="{{ route('pos.sales.print-anticipo', $sale->id) }}" target="_blank" class="btn btn-soft-warning" title="Imprimir Comprobante de Apartado / Anticipo (Venta con Saldo Pendiente)">
                                                        <i class="ri-printer-line"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('pos.sales.print-sale', $sale->id) }}" target="_blank" class="btn btn-soft-secondary" title="Imprimir Orden de Venta (Venta Saldada)">
                                                        <i class="ri-printer-line"></i>
                                                    </a>
                                                @endif

                                                {{-- Historial de Pagos --}}
                                                <a href="{{ route('pos.sales.payments.print-history', $sale->id) }}" target="_blank" class="btn btn-soft-info" title="Imprimir Historial de Pagos y Abonos">
                                                    <i class="ri-file-list-3-line"></i>
                                                </a>

                                                {{-- Orden de Entrega Chofer --}}
                                                @if($sale->hasDelivery())
                                                    <a href="{{ route('admin.pos.delivery-order', $sale->id) }}" target="_blank" class="btn btn-soft-primary" title="Imprimir Orden de Entrega (Chofer)">
                                                        <i class="ri-truck-line"></i>
                                                    </a>
                                                @endif

                                                {{-- Abonar a Apartado --}}
                                                @can('registrar-abonos-ventas')
                                                @if($sale->status === 'apartado' && $sale->balance > 0.01)
                                                    <button type="button" class="btn btn-soft-success" title="Registrar Abono a Apartado" 
                                                            onclick="openAddPaymentModal({{ $sale->id }}, '{{ $sale->ticket_folio }}', {{ $sale->balance }}, '{{ $sale->customer ? addslashes($sale->customer->full_name) : addslashes($sale->delivery_recipient_name ?: 'Público General') }}')">
                                                        <i class="ri-money-dollar-circle-line"></i>
                                                    </button>
                                                @endif
                                                @endcan

                                                {{-- Cancelar Venta --}}
                                                @can('anular-ventas')
                                                @if($sale->status !== 'cancelada')
                                                    <button type="button" class="btn btn-soft-danger" title="Cancelar Venta y Restituir Inventario" onclick="cancelSale({{ $sale->id }}, '{{ $sale->ticket_folio }}')">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-inbox-line display-4 mb-2 d-block text-secondary"></i>
                                                <h5>No se encontraron ventas</h5>
                                                <p class="mb-0">No hay ventas registradas que coincidan con los filtros seleccionados.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    <div class="mt-4">
                        {{ $sales->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: VER DETALLES DE VENTA --}}
    <div class="modal fade" id="modal-sale-details" tabindex="-1" aria-labelledby="modalSaleDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="modalSaleDetailsLabel">
                        <i class="ri-file-text-line me-1 text-primary"></i> Detalle de Venta: <span id="md-ticket-folio" class="text-primary font-monospace fw-bold"></span>
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <a id="md-btn-print-payments-header" href="#" target="_blank" class="btn btn-sm btn-soft-info" title="Imprimir Historial Completo de Pagos (Disponible con o sin saldo)">
                            <i class="ri-file-list-3-line me-1"></i> Imprimir Historial de Pagos
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    {{-- Nav Tabs --}}
                    <ul class="nav nav-tabs nav-tabs-custom nav-success p-3 pb-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-general" role="tab">
                                <i class="ri-dashboard-line me-1"></i> Resumen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-items" role="tab">
                                <i class="ri-box-3-line me-1"></i> Artículos (<span id="md-items-count">0</span>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-payments" role="tab">
                                <i class="ri-bank-card-line me-1"></i> Pagos y Abonos (<span id="md-payments-count">0</span>)
                            </a>
                        </li>
                        <li class="nav-item" id="tab-delivery-header" style="display: none;">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-delivery" role="tab">
                                <i class="ri-truck-line me-1"></i> Entrega a Domicilio
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content p-4">
                        {{-- Tab 1: General --}}
                        <div class="tab-pane active" id="tab-general" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border border-light-subtle shadow-none mb-0">
                                        <div class="card-body">
                                            <h6 class="fs-12 text-muted text-uppercase fw-bold mb-3">Información de Operación</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Fecha y Hora:</span>
                                                <span id="md-created-at" class="fw-medium"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Sucursal:</span>
                                                <span id="md-store-name" class="fw-medium"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Cajero:</span>
                                                <span id="md-cashier-name" class="fw-medium"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Vendedor Comisionista:</span>
                                                <span id="md-seller-name" class="fw-semibold text-info"></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Estado Actual:</span>
                                                <span id="md-status-badge"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border border-light-subtle shadow-none mb-0">
                                        <div class="card-body">
                                            <h6 class="fs-12 text-muted text-uppercase fw-bold mb-3">Cliente</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Titular:</span>
                                                <strong id="md-customer-name"></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Teléfono:</span>
                                                <span id="md-customer-phone"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Línea de Crédito:</span>
                                                <span id="md-customer-credit" class="fw-bold text-primary"></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Modalidad:</span>
                                                <span id="md-delivery-badge"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card bg-light border-0 mb-0">
                                        <div class="card-body">
                                            <div class="row text-center g-2">
                                                <div class="col-3">
                                                    <span class="text-muted fs-12 d-block">Subtotal</span>
                                                    <span id="md-subtotal" class="fw-bold font-monospace fs-14"></span>
                                                </div>
                                                <div class="col-3">
                                                    <span class="text-muted fs-12 d-block">Impuestos</span>
                                                    <span id="md-taxes" class="fw-bold font-monospace fs-14"></span>
                                                </div>
                                                <div class="col-3">
                                                    <span class="text-muted fs-12 d-block">Total Venta</span>
                                                    <span id="md-total" class="fw-bold font-monospace fs-15 text-primary"></span>
                                                </div>
                                                <div class="col-3">
                                                    <span class="text-muted fs-12 d-block">Saldo Pendiente</span>
                                                    <span id="md-balance" class="fw-bold font-monospace fs-15 text-danger"></span>
                                                </div>
                                            </div>
                                            <div id="md-coupon-banner" class="mt-2 pt-2 border-top text-center d-none"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 2: Artículos --}}
                        <div class="tab-pane" id="tab-items" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">Cant.</th>
                                            <th style="width: 110px;">SKU</th>
                                            <th>Descripción / Variante</th>
                                            <th class="text-end" style="width: 100px;">P. Unit</th>
                                            <th class="text-end" style="width: 100px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="md-items-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Tab 3: Pagos y Abonos --}}
                        <div class="tab-pane" id="tab-payments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted fs-12">Historial cronológico de todos los pagos y abonos registrados.</span>
                                <a id="md-btn-print-payments" href="#" target="_blank" class="btn btn-sm btn-soft-primary">
                                    <i class="ri-printer-line me-1"></i> Imprimir Historial de Pagos
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Fecha / Hora</th>
                                            <th>Método de Pago</th>
                                            <th>Terminal / MSI</th>
                                            <th>Referencia</th>
                                            <th class="text-end">Monto</th>
                                            <th class="text-center" style="width: 60px;">Recibo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="md-payments-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Tab 4: Entrega --}}
                        <div class="tab-pane" id="tab-delivery" role="tabpanel">
                            <div class="card border border-light-subtle shadow-none mb-3">
                                <div class="card-header bg-light-subtle py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-12 text-uppercase text-muted">
                                        <i class="ri-map-pin-user-line me-1 text-primary"></i> Datos de Envío a Domicilio
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="md-del-status-badge" class="badge bg-warning-subtle text-warning">Pendiente</span>
                                        <a href="{{ route('deliveries.calendar') }}" class="btn btn-xs btn-soft-primary py-0 px-2 fs-11">
                                            <i class="ri-calendar-todo-line me-1"></i> Agenda de Envíos
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <span class="text-muted fs-12 d-block">Destinatario que recibe:</span>
                                            <strong id="md-del-recipient"></strong>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="text-muted fs-12 d-block">Teléfono de contacto:</span>
                                            <strong id="md-del-phone"></strong>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted fs-12 d-block">Dirección de entrega:</span>
                                            <span id="md-del-address" class="fw-medium"></span>
                                        </div>
                                        <div class="col-12" id="md-del-ref-row">
                                            <span class="text-muted fs-12 d-block">Referencias:</span>
                                            <div id="md-del-references" class="alert alert-warning py-2 mb-0 fs-12"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="text-muted fs-12 d-block">Fecha y hora programada:</span>
                                            <span id="md-del-date" class="fw-bold text-primary"></span>
                                        </div>
                                        <div class="col-md-6" id="md-del-maps-col">
                                            <span class="text-muted fs-12 d-block">Geolocalización:</span>
                                            <a id="md-del-maps-link" href="#" target="_blank" class="btn btn-sm btn-soft-danger mt-1">
                                                <i class="ri-google-fill me-1"></i> Abrir en Google Maps
                                            </a>
                                        </div>
                                        <div class="col-12" id="md-del-evidence-col" style="display: none;">
                                            <div class="p-3 bg-success-subtle border border-success rounded">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                    <div>
                                                        <h6 class="text-success fw-bold mb-0 fs-13">
                                                            <i class="ri-checkbox-circle-fill me-1"></i> Envío Entregado y Respaldado con Orden Firmada
                                                        </h6>
                                                        <small class="text-muted fs-11" id="md-del-evidence-meta"></small>
                                                    </div>
                                                    <a id="md-del-evidence-link" href="#" target="_blank" class="btn btn-sm btn-success">
                                                        <i class="ri-external-link-line me-1"></i> Ver / Descargar Comprobante
                                                    </a>
                                                </div>
                                                <div id="md-del-evidence-preview" class="text-center mt-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Formulario para modificar Fecha/Hora, Estado de Entrega y Subir Comprobante Firmado --}}
                            <div class="card border border-primary-subtle shadow-none mb-0">
                                <div class="card-header bg-primary-subtle py-2">
                                    <h6 class="card-title mb-0 fs-12 fw-bold text-primary text-uppercase">
                                        <i class="ri-calendar-check-line me-1"></i> Modificar Fecha / Hora, Registrar Entrega y Subir Comprobante
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form id="form-sale-delivery-update" enctype="multipart/form-data" data-no-loader="true" onsubmit="submitSaleDeliveryUpdate(event)">
                                        <input type="hidden" id="md-del-sale-id" name="sale_id">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fs-12 fw-semibold">Estado del Envío <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="md-form-delivery-status" name="delivery_status" required onchange="toggleSaleEvidenceUpload(this.value)">
                                                    <option value="pendiente">Pendiente de Salida</option>
                                                    <option value="en_ruta">🚚 En Ruta de Entrega</option>
                                                    <option value="entregado">✓ Entregado (Con Comprobante)</option>
                                                    <option value="cancelado">Cancelado</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fs-12 fw-semibold">Fecha Promesa de Entrega <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-control-sm" id="md-form-delivery-date" name="delivery_date" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fs-12 fw-semibold">Hora Específica <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control form-control-sm" id="md-form-delivery-time" name="delivery_time" required>
                                            </div>

                                            <div class="col-12" id="md-evidence-upload-wrapper" style="display: none;">
                                                <div class="p-3 border border-primary border-dashed rounded bg-primary-subtle bg-opacity-10">
                                                    <label for="md-form-evidence-file" class="form-label fw-bold text-primary fs-13 mb-1">
                                                        <i class="ri-file-upload-line me-1"></i> Comprobante / Orden de Entrega Firmada (Foto o PDF) <span class="text-danger" id="md-evidence-req-star">*</span>
                                                    </label>
                                                    <p class="text-muted fs-11 mb-2">
                                                        Adjunta la fotografía (<code>JPG</code>, <code>PNG</code>, <code>WEBP</code>) o documento <code>PDF</code> de la orden de entrega firmada por el cliente.
                                                    </p>
                                                    <input type="file" class="form-control form-control-sm" id="md-form-evidence-file" name="evidence_file" accept=".jpg,.jpeg,.png,.webp,.pdf" onchange="previewSaleEvidenceFile(this)">
                                                    <div id="md-new-evidence-preview" class="mt-2 d-none"></div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fs-12 fw-semibold">Notas / Instrucciones de Entrega</label>
                                                <textarea class="form-control form-control-sm" id="md-form-delivery-instructions" name="delivery_instructions" rows="2" placeholder="Observaciones del envío o recepción..."></textarea>
                                            </div>

                                            <div class="col-12 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-success btn-sm" id="btn-save-sale-delivery">
                                                    <i class="ri-save-line me-1"></i> Guardar Cambios de Entrega
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <div>
                        {{-- Botón Historial de Pagos (Siempre disponible: tanto para ventas saldadas como pendientes de liquidar) --}}
                        <a id="md-btn-print-payments-footer" href="#" target="_blank" class="btn btn-info btn-sm text-white" title="Imprimir Historial Completo de Pagos (disponible siempre, saldada o no)">
                            <i class="ri-file-list-3-line me-1"></i> Imprimir Historial de Pagos
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <a id="md-btn-print" href="#" target="_blank" class="btn btn-secondary btn-sm">
                            <i class="ri-printer-line me-1"></i> Imprimir Documento
                        </a>
                        <a id="md-btn-delivery" href="#" target="_blank" class="btn btn-primary btn-sm" style="display: none;">
                            <i class="ri-truck-line me-1"></i> Imprimir Orden Chofer
                        </a>
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: REGISTRAR ABONO A APARTADO --}}
    <div class="modal fade" id="modal-add-payment" tabindex="-1" aria-labelledby="modalAddPaymentLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-add-payment" onsubmit="submitAddPayment(event)">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="modalAddPaymentLabel">
                            <i class="ri-money-dollar-circle-line me-1"></i> Registrar Abono a Apartado
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="pay-sale-id" name="sale_id">

                        <div class="alert alert-info py-2 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fs-12 text-muted d-block">Ticket: <strong id="pay-ticket-folio" class="text-dark"></strong></span>
                                    <span class="fs-12 text-muted">Cliente: <strong id="pay-customer-name" class="text-dark"></strong></span>
                                </div>
                                <div class="text-end">
                                    <span class="fs-11 text-muted d-block text-uppercase">Saldo Pendiente</span>
                                    <span id="pay-current-balance" class="fs-18 fw-bold text-danger font-monospace"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="pay-amount" class="form-label mb-0 fw-bold">Monto del Abono ($) <span class="text-danger">*</span></label>
                                <button type="button" id="btn-quick-liquidate" class="btn btn-sm btn-link text-success p-0 fw-semibold fs-12">
                                    Liquidar Total
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0.01" class="form-control font-monospace fs-16 fw-bold" id="pay-amount" name="amount" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pay-method" class="form-label fw-bold">Método de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" id="pay-method" name="payment_method_id" required onchange="toggleTerminalSelect()">
                                <option value="">Seleccione método...</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->id }}" data-requires-terminal="{{ $pm->requires_terminal ? '1' : '0' }}">
                                        {{ $pm->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="group-terminal" style="display: none;">
                            <label for="pay-terminal" class="form-label fw-bold">Terminal Bancaria</label>
                            <select class="form-select" id="pay-terminal" name="payment_terminal_id">
                                <option value="">Seleccione terminal...</option>
                                @foreach($paymentTerminals as $pt)
                                    <option value="{{ $pt->id }}">{{ $pt->name }} ({{ $pt->serial_number }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="pay-reference" class="form-label">Referencia / Folio de Transacción</label>
                            <input type="text" class="form-control" id="pay-reference" name="reference" placeholder="Opcional: No. autorización voucher o cheque">
                        </div>

                        <div class="mb-3">
                            <label for="pay-notes" class="form-label">Notas Adicionales</label>
                            <textarea class="form-control" id="pay-notes" name="notes" rows="2" placeholder="Observaciones sobre este abono..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btn-submit-payment" class="btn btn-success">
                            <i class="ri-check-line me-1"></i> Confirmar y Registrar Abono
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        // Token CSRF global
        const csrfToken = '{{ csrf_token() }}';
        const deliveriesBaseUrl = "{{ url('deliveries') }}";
        let currentModalSaleHasEvidence = false;

        /**
         * Actualiza visualmente la sección de entrega del modal y el badge de la fila en la tabla
         */
        function renderSaleDeliveryDetails(saleId, meta, instructionsText) {
            if (!meta) return;
            currentModalSaleHasEvidence = !!meta.has_evidence;

            const statusBadge = document.getElementById('md-del-status-badge');
            const statusMap = {
                'pendiente': { text: 'Pendiente', cls: 'badge bg-warning-subtle text-warning', rowText: 'A Domicilio', rowCls: 'badge bg-primary-subtle text-primary d-block mt-1' },
                'en_ruta':   { text: '🚚 En Ruta', cls: 'badge bg-info-subtle text-info', rowText: '🚚 En Ruta', rowCls: 'badge bg-info-subtle text-info border border-info-subtle d-block mt-1' },
                'entregado': { text: '✓ Entregado', cls: 'badge bg-success-subtle text-success', rowText: '✓ Entregado', rowCls: 'badge bg-success-subtle text-success border border-success-subtle d-block mt-1' },
                'cancelado': { text: 'Cancelado', cls: 'badge bg-danger-subtle text-danger', rowText: 'Envío Cancelado', rowCls: 'badge bg-danger-subtle text-danger border border-danger-subtle d-block mt-1' }
            };
            const st = statusMap[meta.delivery_status] || statusMap['pendiente'];
            if (statusBadge) {
                statusBadge.className = st.cls;
                statusBadge.innerText = st.text;
            }

            // Actualizar badge de la fila en la tabla sin recargar página
            const rowBadge = document.getElementById(`sale-del-badge-${saleId}`);
            if (rowBadge) {
                rowBadge.className = st.rowCls;
                rowBadge.innerHTML = `<i class="ri-truck-line me-1"></i>${st.rowText}`;
            }

            // Fecha y hora formateada
            const dateEl = document.getElementById('md-del-date');
            if (dateEl) {
                dateEl.innerText = meta.delivery_date_formatted
                    ? `${meta.delivery_date_formatted}${meta.delivery_time_formatted ? ' a las ' + meta.delivery_time_formatted : ''}`
                    : 'Por programar';
            }

            // Comprobante / Evidencia existente
            const evidenceCol = document.getElementById('md-del-evidence-col');
            const evidenceLink = document.getElementById('md-del-evidence-link');
            const evidenceMeta = document.getElementById('md-del-evidence-meta');
            const evidencePreview = document.getElementById('md-del-evidence-preview');

            if (meta.has_evidence && meta.evidence_url && evidenceCol && evidenceLink) {
                evidenceCol.style.display = 'block';
                evidenceLink.href = meta.evidence_url;

                let metaStr = '';
                if (meta.delivered_at_formatted) metaStr += `Entregado el: ${meta.delivered_at_formatted}`;
                if (meta.delivered_by_name) metaStr += ` · Registrado por: ${meta.delivered_by_name}`;
                if (evidenceMeta) evidenceMeta.innerText = metaStr || 'Comprobante de entrega verificado.';

                if (evidencePreview) {
                    if (meta.is_evidence_pdf) {
                        evidencePreview.innerHTML = `
                            <div class="p-2 bg-white rounded border d-inline-flex align-items-center gap-2">
                                <i class="ri-file-pdf-2-fill fs-24 text-danger"></i>
                                <div class="text-start">
                                    <span class="fw-bold fs-12 d-block text-dark">Orden de Entrega Firmada (PDF)</span>
                                    <a href="${meta.evidence_url}" target="_blank" class="fs-11 text-primary">Abrir documento PDF</a>
                                </div>
                            </div>`;
                    } else {
                        evidencePreview.innerHTML = `
                            <a href="${meta.evidence_url}" target="_blank">
                                <img src="${meta.evidence_url}" alt="Comprobante de Entrega" class="img-thumbnail rounded shadow-sm" style="max-height: 180px; object-fit: contain;">
                            </a>`;
                    }
                }
            } else if (evidenceCol) {
                evidenceCol.style.display = 'none';
                if (evidencePreview) evidencePreview.innerHTML = '';
            }

            // Poblar campos del formulario
            document.getElementById('md-del-sale-id').value = saleId;
            document.getElementById('md-form-delivery-status').value = meta.delivery_status || 'pendiente';
            document.getElementById('md-form-delivery-date').value = meta.delivery_date_input || '';
            document.getElementById('md-form-delivery-time').value = meta.delivery_time_input || '11:00';
            if (instructionsText !== undefined) {
                document.getElementById('md-form-delivery-instructions').value = instructionsText || '';
            }

            // Limpiar selector de archivo nuevo
            const fileInput = document.getElementById('md-form-evidence-file');
            if (fileInput) fileInput.value = '';
            const newPreview = document.getElementById('md-new-evidence-preview');
            if (newPreview) {
                newPreview.classList.add('d-none');
                newPreview.innerHTML = '';
            }

            toggleSaleEvidenceUpload(meta.delivery_status || 'pendiente');
        }

        function toggleSaleEvidenceUpload(statusVal) {
            const wrapper = document.getElementById('md-evidence-upload-wrapper');
            const reqStar = document.getElementById('md-evidence-req-star');
            if (!wrapper) return;

            if (statusVal === 'entregado') {
                wrapper.style.display = 'block';
                if (reqStar) reqStar.style.display = currentModalSaleHasEvidence ? 'none' : 'inline';
            } else {
                wrapper.style.display = 'none';
            }
        }

        function previewSaleEvidenceFile(input) {
            const newPreview = document.getElementById('md-new-evidence-preview');
            if (!newPreview) return;
            const file = input.files && input.files[0];
            if (!file) {
                newPreview.classList.add('d-none');
                newPreview.innerHTML = '';
                return;
            }
            newPreview.classList.remove('d-none');
            if (file.type === 'application/pdf') {
                newPreview.innerHTML = `
                    <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center">
                        <i class="ri-file-pdf-2-line fs-20 me-2 text-danger"></i>
                        <div>
                            <strong class="fs-12 d-block">Documento PDF seleccionado:</strong>
                            <span class="fs-11">${file.name} (${(file.size / 1024).toFixed(1)} KB)</span>
                        </div>
                    </div>`;
            } else if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    newPreview.innerHTML = `
                        <div class="border rounded p-2 bg-white text-center">
                            <span class="fs-11 text-muted d-block mb-1">Vista previa de comprobante:</span>
                            <img src="${e.target.result}" alt="Vista previa" class="img-fluid rounded shadow-sm" style="max-height: 160px;">
                        </div>`;
                };
                reader.readAsDataURL(file);
            }
        }

        function submitSaleDeliveryUpdate(e) {
            e.preventDefault();
            const saleId = document.getElementById('md-del-sale-id').value;
            const status = document.getElementById('md-form-delivery-status').value;
            const fileInput = document.getElementById('md-form-evidence-file');

            if (status === 'entregado' && !currentModalSaleHasEvidence && (!fileInput.files || fileInput.files.length === 0)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Comprobante Requerido',
                    text: 'Para registrar el envío como ENTREGADO es necesario adjuntar la foto o PDF de la orden de entrega firmada.'
                });
                return;
            }

            const formData = new FormData();
            formData.append('delivery_status', status);
            formData.append('delivery_date', document.getElementById('md-form-delivery-date').value);
            formData.append('delivery_time', document.getElementById('md-form-delivery-time').value);
            formData.append('delivery_instructions', document.getElementById('md-form-delivery-instructions').value);

            if (fileInput.files && fileInput.files.length > 0) {
                formData.append('evidence_file', fileInput.files[0]);
            }

            const btn = document.getElementById('btn-save-sale-delivery');
            const prevHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Guardando...';

            fetch(`${deliveriesBaseUrl}/sale/${saleId}/status`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
                .then(async res => {
                    const data = await res.json();
                    return { status: res.status, data };
                })
                .then(({ status: httpStatus, data }) => {
                    btn.disabled = false;
                    btn.removeAttribute('data-submitting');
                    btn.innerHTML = prevHtml;

                    if (httpStatus >= 200 && httpStatus < 300 && data.success) {
                        const props = data.event?.extendedProps || {};
                        renderSaleDeliveryDetails(saleId, {
                            delivery_status:         props.delivery_status || status,
                            delivery_date_input:     props.delivery_date || document.getElementById('md-form-delivery-date').value,
                            delivery_date_formatted: props.delivery_date_formatted || document.getElementById('md-form-delivery-date').value,
                            delivery_time_input:     props.delivery_time || document.getElementById('md-form-delivery-time').value,
                            delivery_time_formatted: props.delivery_time_formatted || document.getElementById('md-form-delivery-time').value,
                            has_evidence:            props.has_evidence ?? currentModalSaleHasEvidence,
                            evidence_url:            props.evidence_url || null,
                            is_evidence_pdf:         props.is_evidence_pdf || false,
                            delivered_at_formatted:  props.delivered_at || null,
                            delivered_by_name:       props.delivered_by_name || null,
                        }, props.instructions ?? document.getElementById('md-form-delivery-instructions').value);

                        Swal.fire({
                            icon: 'success',
                            title: '¡Entrega Actualizada!',
                            text: data.message,
                            timer: 2200,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Atención', data.message || 'Verifica los datos ingresados.', 'warning');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.removeAttribute('data-submitting');
                    btn.innerHTML = prevHtml;
                    console.error('Error actualizando entrega:', err);
                    Swal.fire('Error', 'No se pudo procesar la actualización de entrega.', 'error');
                });
        }

        /**
         * Abre el modal con todos los detalles de una venta
         */
        function openSaleDetails(saleId) {
            fetch(`/pos/sales/${saleId}`, {
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire('Error', data.message || 'No se pudo cargar la venta', 'error');
                    return;
                }

                const s = data.sale;

                // Datos de Cabecera
                document.getElementById('md-ticket-folio').innerText = s.ticket_folio;
                document.getElementById('md-created-at').innerText = new Date(s.created_at).toLocaleString('es-MX');
                document.getElementById('md-store-name').innerText = s.store ? s.store.name : 'Sucursal Principal';
                document.getElementById('md-cashier-name').innerText = s.cashier ? s.cashier.name : 'Caja';
                document.getElementById('md-seller-name').innerText = s.seller ? s.seller.name : (s.cashier ? s.cashier.name : 'N/A');
                
                // Status Badge
                let stHtml = '';
                if (s.status === 'pagada') {
                    stHtml = '<span class="badge bg-success">Pagada</span>';
                } else if (s.status === 'apartado') {
                    stHtml = '<span class="badge bg-warning">Apartado</span>';
                } else if (s.status === 'cancelada') {
                    stHtml = '<span class="badge bg-danger">Cancelada</span>';
                } else {
                    stHtml = `<span class="badge bg-secondary">${s.status}</span>`;
                }
                document.getElementById('md-status-badge').innerHTML = stHtml;

                // Cliente
                document.getElementById('md-customer-name').innerText = s.customer ? (s.customer.name + ' ' + (s.customer.lastname || '')) : (s.delivery_recipient_name || 'Público en General');
                document.getElementById('md-customer-phone').innerText = s.customer ? (s.customer.phone || 'No registrado') : (s.delivery_phone || 'No registrado');
                
                if (s.customer && s.customer.available_credit !== undefined) {
                    document.getElementById('md-customer-credit').innerText = '$' + parseFloat(s.customer.available_credit).toFixed(2);
                } else {
                    document.getElementById('md-customer-credit').innerText = 'N/A';
                }

                // Modalidad
                document.getElementById('md-delivery-badge').innerHTML = s.is_delivery 
                    ? '<span class="badge bg-primary"><i class="ri-truck-line me-1"></i>A Domicilio</span>'
                    : '<span class="badge bg-info"><i class="ri-store-2-line me-1"></i>En Tienda</span>';

                // Totales
                document.getElementById('md-subtotal').innerText = '$' + parseFloat(s.subtotal).toFixed(2);
                document.getElementById('md-taxes').innerText = '$' + parseFloat(s.tax_total || 0).toFixed(2);
                document.getElementById('md-total').innerText = '$' + parseFloat(s.total).toFixed(2);
                document.getElementById('md-balance').innerText = '$' + parseFloat(s.balance).toFixed(2);

                const couponBanner = document.getElementById('md-coupon-banner');
                if (couponBanner) {
                    const coupDisc = parseFloat(s.coupon_discount || 0);
                    const manDisc = parseFloat(s.discount_total || 0);
                    const parts = [];
                    if (manDisc > 0) {
                        parts.push(`<span class="badge bg-success-subtle text-success fs-12"><i class="ri-price-tag-3-line me-1"></i>Descuento Regateo: -$${manDisc.toFixed(2)}</span>`);
                    }
                    if (coupDisc > 0) {
                        parts.push(`<span class="badge bg-primary-subtle text-primary fs-12"><i class="ri-ticket-2-line me-1"></i>Cupón (${s.coupon_code || 'PROMO'}): -$${coupDisc.toFixed(2)}</span>`);
                    }
                    if (parts.length > 0) {
                        couponBanner.innerHTML = parts.join(' ');
                        couponBanner.classList.remove('d-none');
                    } else {
                        couponBanner.innerHTML = '';
                        couponBanner.classList.add('d-none');
                    }
                }

                // Artículos
                const itemsTbody = document.getElementById('md-items-tbody');
                itemsTbody.innerHTML = '';
                document.getElementById('md-items-count').innerText = s.items ? s.items.length : 0;
                
                (s.items || []).forEach(item => {
                    const sku = item.variant ? item.variant.sku : (item.product ? item.product.sku : 'N/A');
                    const name = item.product ? item.product.name : 'Artículo';
                    const varName = item.variant ? `<span class="badge bg-light text-dark ms-1">${item.variant.name}</span>` : '';
                    const notes = item.notes ? `<div class="text-muted fs-11 mt-1"><em>${item.notes}</em></div>` : '';

                    itemsTbody.innerHTML += `
                        <tr>
                            <td class="text-center fw-bold text-primary">${item.quantity}</td>
                            <td class="font-monospace fs-11">${sku}</td>
                            <td><strong>${name}</strong>${varName}${notes}</td>
                            <td class="text-end font-monospace">$${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td class="text-end font-monospace fw-bold">$${parseFloat(item.total).toFixed(2)}</td>
                        </tr>
                    `;
                });

                // Pagos
                const paymentsTbody = document.getElementById('md-payments-tbody');
                paymentsTbody.innerHTML = '';
                document.getElementById('md-payments-count').innerText = s.payments ? s.payments.length : 0;

                (s.payments || []).forEach((p, idx) => {
                    const date = new Date(p.created_at).toLocaleString('es-MX');
                    const pmName = p.payment_method ? p.payment_method.name : 'Pago';
                    const termName = p.terminal ? `<span class="badge bg-light text-muted">${p.terminal.name}</span>` : '-';
                    const ref = p.reference || '-';
                    const printReceiptUrl = `/pos/sales/${s.id}/payments/${p.id}/print`;

                    paymentsTbody.innerHTML += `
                        <tr>
                            <td>${idx + 1}</td>
                            <td class="fs-12">${date}</td>
                            <td><strong>${pmName}</strong></td>
                            <td>${termName}</td>
                            <td class="fs-11 text-muted">${ref}</td>
                            <td class="text-end font-monospace fw-bold text-success">$${parseFloat(p.amount).toFixed(2)}</td>
                            <td class="text-center">
                                <a href="${printReceiptUrl}" target="_blank" class="btn btn-sm btn-soft-secondary btn-icon" title="Imprimir Recibo de Abono">
                                    <i class="ri-printer-line"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });

                // Entrega Tab
                const tabDelHeader = document.getElementById('tab-delivery-header');
                const btnDelivery = document.getElementById('md-btn-delivery');
                if (s.is_delivery) {
                    tabDelHeader.style.display = 'block';
                    btnDelivery.style.display = 'inline-block';
                    btnDelivery.href = data.delivery_order_url;

                    document.getElementById('md-del-recipient').innerText = s.delivery_recipient_name || 'El titular';
                    document.getElementById('md-del-phone').innerText = s.delivery_phone || 'N/A';
                    document.getElementById('md-del-address').innerText = s.delivery_address || 'No especificada';
                    
                    if (s.delivery_references) {
                        document.getElementById('md-del-ref-row').style.display = 'block';
                        document.getElementById('md-del-references').innerText = s.delivery_references;
                    } else {
                        document.getElementById('md-del-ref-row').style.display = 'none';
                    }

                    const fallbackDateInput = s.delivery_date ? String(s.delivery_date).substring(0, 10) : '';
                    const meta = data.delivery_meta || {
                        delivery_status:         s.delivery_status || 'pendiente',
                        delivery_date_input:     fallbackDateInput,
                        delivery_date_formatted: fallbackDateInput || 'Por programar',
                        delivery_time_input:     s.delivery_time ? String(s.delivery_time).substring(0, 5) : '11:00',
                        delivery_time_formatted: s.delivery_time_slot || (s.delivery_time ? String(s.delivery_time).substring(0, 5) + ' hrs' : ''),
                        has_evidence:            !!s.delivery_evidence_path,
                        evidence_url:            s.delivery_evidence_path ? `/storage/${s.delivery_evidence_path.replace(/^\/+/, '')}` : null,
                        is_evidence_pdf:         s.delivery_evidence_path ? s.delivery_evidence_path.toLowerCase().endsWith('.pdf') : false,
                        delivered_at_formatted:  s.delivered_at ? new Date(s.delivered_at).toLocaleString('es-MX') : null,
                        delivered_by_name:       null
                    };

                    renderSaleDeliveryDetails(s.id, meta, s.delivery_instructions || '');

                    const mapsUrl = s.delivery_maps_url || ((s.delivery_lat && s.delivery_lng) ? `https://www.google.com/maps?q=${s.delivery_lat},${s.delivery_lng}` : null);
                    if (mapsUrl) {
                        document.getElementById('md-del-maps-col').style.display = 'block';
                        document.getElementById('md-del-maps-link').href = mapsUrl;
                    } else {
                        document.getElementById('md-del-maps-col').style.display = 'none';
                    }
                } else {
                    tabDelHeader.style.display = 'none';
                    btnDelivery.style.display = 'none';
                }

                // Botón de Impresión de Venta / Anticipo (Solo se permite imprimir ticket de venta si ya está saldada)
                const btnPrint = document.getElementById('md-btn-print');
                const hasPendingBalance = (s.status === 'apartado' || parseFloat(s.balance) > 0.01);
                btnPrint.href = data.print_url || (hasPendingBalance ? `/pos/sales/${s.id}/print-anticipo` : `/pos/sales/${s.id}/print-sale`);
                if (hasPendingBalance) {
                    btnPrint.className = 'btn btn-warning btn-sm';
                    btnPrint.innerHTML = '<i class="ri-printer-line me-1"></i> Imprimir Comprobante de Apartado';
                    btnPrint.title = 'Venta no saldada. Solo se permite imprimir comprobante de apartado/anticipo';
                } else {
                    btnPrint.className = 'btn btn-secondary btn-sm';
                    btnPrint.innerHTML = '<i class="ri-printer-line me-1"></i> Imprimir Ticket de Venta';
                    btnPrint.title = 'Venta 100% saldada';
                }

                // Botones Imprimir Historial de Pagos (Header, Tab 3 y Footer del modal)
                const paymentsPrintUrl = data.print_payments_url || `/pos/sales/${s.id}/print-payments`;
                const btnPrintPayments = document.getElementById('md-btn-print-payments');
                if (btnPrintPayments) {
                    btnPrintPayments.href = paymentsPrintUrl;
                }
                const btnPrintPaymentsFooter = document.getElementById('md-btn-print-payments-footer');
                if (btnPrintPaymentsFooter) {
                    btnPrintPaymentsFooter.href = paymentsPrintUrl;
                }
                const btnPrintPaymentsHeader = document.getElementById('md-btn-print-payments-header');
                if (btnPrintPaymentsHeader) {
                    btnPrintPaymentsHeader.href = paymentsPrintUrl;
                }

                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-sale-details'));
                modal.show();
            })
            .catch(err => {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            });
        }

        /**
         * Abre el modal para registrar un abono
         */
        function openAddPaymentModal(saleId, folio, balance, customerName) {
            document.getElementById('pay-sale-id').value = saleId;
            document.getElementById('pay-ticket-folio').innerText = folio;
            document.getElementById('pay-customer-name').innerText = customerName;
            document.getElementById('pay-current-balance').innerText = '$' + parseFloat(balance).toFixed(2);
            
            const payInput = document.getElementById('pay-amount');
            payInput.value = '';
            payInput.max = balance;

            document.getElementById('btn-quick-liquidate').onclick = function() {
                payInput.value = parseFloat(balance).toFixed(2);
            };

            document.getElementById('form-add-payment').reset();
            document.getElementById('pay-sale-id').value = saleId;
            document.getElementById('pay-current-balance').innerText = '$' + parseFloat(balance).toFixed(2);

            const modal = new bootstrap.Modal(document.getElementById('modal-add-payment'));
            modal.show();
        }

        function toggleTerminalSelect() {
            const select = document.getElementById('pay-method');
            const selectedOpt = select.options[select.selectedIndex];
            const requiresTerm = selectedOpt ? selectedOpt.getAttribute('data-requires-terminal') === '1' : false;
            document.getElementById('group-terminal').style.display = requiresTerm ? 'block' : 'none';
        }

        /**
         * Procesa el abono mediante AJAX
         */
        function submitAddPayment(e) {
            e.preventDefault();
            const saleId = document.getElementById('pay-sale-id').value;
            const btn = document.getElementById('btn-submit-payment');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Procesando...';

            const formData = new FormData(document.getElementById('form-add-payment'));

            fetch(`/pos/sales/${saleId}/payments`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-check-line me-1"></i> Confirmar y Registrar Abono';

                if (!data.success) {
                    Swal.fire('Atención', data.message || 'No se pudo registrar el abono.', 'warning');
                    return;
                }

                bootstrap.Modal.getInstance(document.getElementById('modal-add-payment')).hide();

                Swal.fire({
                    title: '¡Abono Registrado!',
                    text: data.message,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ri-printer-line me-1"></i> Imprimir Recibo de Abono',
                    cancelButtonText: 'Cerrar',
                    confirmButtonColor: '#059669'
                }).then((result) => {
                    if (result.isConfirmed && data.print_url) {
                        window.open(data.print_url, '_blank');
                    }
                    window.location.reload();
                });
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-check-line me-1"></i> Confirmar y Registrar Abono';
                Swal.fire('Error', 'Ocurrió un problema de red al guardar el abono.', 'error');
            });
        }

        /**
         * Cancela una venta restituyendo inventario y crédito
         */
        function cancelSale(saleId, folio) {
            Swal.fire({
                title: `¿Cancelar Venta ${folio}?`,
                text: 'Esta acción restituirá las existencias en inventario (o liberará la reserva del almacén) y liberará la línea de crédito ocupada.',
                icon: 'warning',
                input: 'textarea',
                inputPlaceholder: 'Indique el motivo de la cancelación...',
                inputAttributes: {
                    'aria-label': 'Motivo de la cancelación'
                },
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6e7881',
                confirmButtonText: '<i class="ri-delete-bin-line me-1"></i> Sí, cancelar venta',
                cancelButtonText: 'No, mantener activa',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debe ingresar un motivo para justificar la cancelación.';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    fetch(`/pos/sales/${saleId}/cancel`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ reason: result.value })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Cancelada', data.message, 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'No se pudo cancelar la venta.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'Fallo al procesar la cancelación en el servidor.', 'error');
                    });
                }
            });
        }
    </script>
@endsection
