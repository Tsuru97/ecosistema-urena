@extends('layouts.master')
@section('title') Punto de Venta @endsection

@section('css')
    <!-- SweetAlert2 -->
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" crossorigin="anonymous" />
    <style>
        .pos-cart-table {
            max-height: 500px;
            overflow-y: auto;
        }
        .search-results {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            background: var(--vz-card-bg);
            border: 1px solid var(--vz-border-color);
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .search-item:hover {
            background-color: var(--vz-light);
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    <!-- Alerta de Contexto de Sucursal -->
    @if($isGlobalAdmin)
        <div class="alert alert-danger alert-additional fade show mb-3" role="alert">
            <div class="alert-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <i class="ri-error-warning-line fs-24 align-middle text-danger"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading text-danger fw-bold mb-1">Contexto de Sucursal Global Activo</h5>
                        <p class="mb-0 text-muted">No es posible realizar ventas desde el contexto Global. Por favor, <strong>selecciona una sucursal específica en el selector de la barra superior</strong> para poder habilitar la caja.</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0 me-3">
                            <div class="avatar-title bg-info-subtle text-info rounded fs-20">
                                <i class="ri-store-2-line"></i>
                            </div>
                        </div>
                        <div>
                            <h5 class="fs-14 fw-bold mb-0 text-dark">{{ $currentStore->name }}</h5>
                            <small class="text-muted"><i class="ri-map-pin-line me-1"></i>{{ $currentStore->address ?? 'Sucursal activa' }}</small>
                        </div>
                    </div>

                    {{-- Widget de Turno de Caja --}}
                    <div class="d-flex flex-wrap align-items-center gap-2" id="cash-shift-widget">
                        <div id="cs-closed-state" class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger fs-12 py-2 px-3 shadow-sm">
                                <i class="ri-lock-line me-1"></i> Caja Cerrada
                            </span>
                            <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm" onclick="cashShiftManager.openShiftModal()">
                                <i class="ri-key-2-line me-1"></i> Abrir Turno de Caja
                            </button>
                            <a href="{{ route('cash-shifts.index') }}" class="btn btn-sm btn-soft-secondary shadow-sm" title="Historial de Cortes de Caja">
                                <i class="ri-history-line me-1"></i> Cortes
                            </a>
                        </div>

                        <div id="cs-open-state" class="d-none align-items-center flex-wrap gap-2">
                            <div class="text-end me-2">
                                <span class="badge bg-success fs-12 py-1 px-2 mb-1 shadow-sm">
                                    <i class="ri-checkbox-circle-line me-1"></i> <span id="cs-folio-display">TURNO</span>
                                </span>
                                <div class="text-muted" style="font-size: 11px;">
                                    Fondo: <strong class="text-dark">$<span id="cs-opening-display">0.00</span></strong> | 
                                    Apertura: <span id="cs-opened-display">--</span>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm shadow-sm">
                                <button type="button" class="btn btn-soft-primary" onclick="cashShiftManager.openMovementModal()" title="Entrada / Retiro de efectivo">
                                    <i class="ri-exchange-dollar-line me-1"></i> Movimiento
                                </button>
                                <button type="button" class="btn btn-soft-info" onclick="cashShiftManager.openCorteXModal()" title="Arqueo Parcial">
                                    <i class="ri-file-chart-line me-1"></i> Corte X
                                </button>
                                <button type="button" class="btn btn-soft-danger" onclick="cashShiftManager.openCorteZModal()" title="Cerrar Turno de Caja">
                                    <i class="ri-lock-2-line me-1"></i> Corte Z
                                </button>
                                <a href="{{ route('cash-shifts.index') }}" class="btn btn-soft-secondary" title="Historial de Cortes de Caja">
                                    <i class="ri-history-line me-1"></i> Cortes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <!-- Búsqueda -->
        <div class="col-lg-12 mb-3">
            <div class="d-flex gap-2">
                <div class="flex-grow-1 position-relative">
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                        <input type="text" id="pos-search" class="form-control form-control-lg" placeholder="Escanea código de barras o busca por nombre/SKU..." autofocus autocomplete="off">
                    </div>
                    <div id="search-results" class="search-results d-none">
                        <div class="list-group list-group-flush" id="search-results-list"></div>
                    </div>
                </div>
                <button class="btn btn-warning btn-lg px-3 text-nowrap shadow-sm" id="btn-wildcard-product" onclick="posCart.openWildcardModal()" title="Agregar concepto o producto personalizado" @if($isGlobalAdmin) disabled @endif>
                    <i class="ri-edit-2-line me-1"></i> Personalizado
                </button>
                <button class="btn btn-outline-primary btn-lg px-3 text-nowrap shadow-sm" id="btn-delivery-config" onclick="posCart.openDeliveryModal()" title="Configurar Envío / Pedido a Domicilio" @if($isGlobalAdmin) disabled @endif>
                    <i class="ri-truck-line me-1"></i> <span id="btn-delivery-label">Envío a Domicilio</span>
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Carrito -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header align-items-center d-flex flex-wrap gap-2">
                    <div class="d-flex align-items-center flex-grow-1 flex-wrap gap-2">
                        <h4 class="card-title mb-0">Carrito de Compra</h4>
                        <span id="loaded-quote-badge" class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2 d-none" style="font-size: 0.78rem;">
                            <i class="ri-file-list-3-line align-middle me-1"></i>Cotización: <strong id="loaded-quote-folio-text" class="font-monospace"></strong>
                            <a href="javascript:void(0)" class="text-danger ms-1" onclick="posCart.unlinkLoadedQuote()" title="Desvincular folio de cotización"><i class="ri-close-circle-fill align-middle"></i></a>
                        </span>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-1">
                        <button type="button" class="btn btn-sm btn-soft-info" id="btn-open-parked-sales" onclick="posCart.openParkedSalesModal()" title="Ver ventas en espera / pausadas">
                            <i class="ri-pause-circle-line align-middle me-1"></i>En Espera
                            <span id="parked-sales-count-badge" class="badge bg-info text-white ms-1 d-none">0</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft-warning" id="btn-park-current-sale" onclick="posCart.parkCurrentSalePrompt()" title="Poner la venta actual en espera para atender otra operación sin perder datos">
                            <i class="ri-bookmark-line align-middle me-1"></i>Pausar Venta
                        </button>
                        <a href="{{ route('pos.quotes') }}" class="btn btn-sm btn-soft-primary" title="Ver historial de cotizaciones">
                            <i class="ri-file-search-line align-middle me-1"></i>Cotizaciones
                        </a>
                        <button class="btn btn-sm btn-soft-danger" onclick="posCart.clear()">
                            <i class="ri-delete-bin-line align-middle me-1"></i>Vaciar Carrito
                        </button>
                    </div>
                </div>
                <!-- Banner de Entrega a Domicilio -->
                <div id="delivery-cart-banner" class="alert alert-success d-flex align-items-center justify-content-between mx-3 mt-3 mb-1 py-2 px-3 d-none">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-truck-line fs-20 text-success"></i>
                        <div>
                            <span class="fw-semibold text-success" id="delivery-banner-text">Envío a Domicilio Configurado</span>
                            <small class="d-block text-muted" id="delivery-banner-subtext"></small>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-soft-primary" onclick="posCart.openDeliveryModal()" title="Editar datos de entrega"><i class="ri-edit-line"></i> Editar</button>
                        <button type="button" class="btn btn-sm btn-soft-danger" onclick="posCart.removeDelivery()" title="Quitar envío"><i class="ri-close-line"></i></button>
                    </div>
                </div>
                <div class="card-body p-0 pos-cart-table">
                    <table class="table table-borderless table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Tarifa</th>
                                <th>Precio</th>
                                <th style="width: 150px;">Cantidad</th>
                                <th>Subtotal</th>
                                <th style="width: 50px;" title="Nota interna del ítem"><i class="ri-chat-3-line"></i></th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                            <!-- JS renderiza aquí -->
                            <tr id="empty-cart-msg">
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="ri-shopping-cart-2-line fs-1 mb-2"></i><br>
                                    El carrito está vacío
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Resumen y Cobro -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resumen de Venta</h5>
                </div>
                <div class="card-body">
                    <input type="hidden" id="store_select" value="{{ $activeStoreId }}">

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0 fw-semibold text-dark">
                                <i class="ri-user-star-line text-info me-1"></i>Vendedor Comisionista
                            </label>
                            <span class="badge bg-info-subtle text-info fs-11"><i class="ri-percent-line"></i> Comisión</span>
                        </div>
                        <select class="form-select" id="seller_select">
                            @foreach($sellers ?? [] as $s)
                                <option value="{{ $s->id }}" {{ $s->id == auth()->id() ? 'selected' : '' }}>
                                    {{ $s->name }} {{ $s->lastname }} {{ $s->id == auth()->id() ? '(Tú)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0 fw-semibold text-dark">
                                <i class="ri-user-3-line text-success me-1"></i>Cliente Titular
                            </label>
                            <button class="btn btn-sm btn-link p-0 text-success fw-medium shadow-none fs-12" type="button" onclick="posCart.openCreateCustomerModal()">
                                <i class="ri-user-add-line align-middle"></i> Crear Cliente
                            </button>
                        </div>
                        <select class="form-select" id="customer_select">
                            <option value="">Público en General</option>
                            @foreach($defaultCustomers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->full_name }}{{ $customer->phone ? ' — Tel: ' . $customer->phone : '' }} {{ $customer->rfc ? "({$customer->rfc})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cupón de Descuento -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0 fw-semibold text-dark">
                                <i class="ri-ticket-line text-primary me-1"></i>Cupón de Descuento
                            </label>
                            <span class="text-muted fs-11" id="coupon-status-text">Opcional</span>
                        </div>
                        <div id="coupon-input-container">
                            <div class="input-group input-group-sm">
                                <input type="text" id="pos-coupon-code" class="form-control text-uppercase font-monospace" placeholder="Ingresa código de cupón..." maxlength="50" onkeydown="if(event.key==='Enter'){event.preventDefault();posCart.applyCoupon();}">
                                <button class="btn btn-outline-primary shadow-none" type="button" id="btn-apply-coupon" onclick="posCart.applyCoupon()">
                                    <i class="ri-check-line"></i> Aplicar
                                </button>
                            </div>
                        </div>
                        <div id="coupon-applied-badge" class="alert alert-success d-flex align-items-center justify-content-between py-2 px-2 mb-0 mt-1 d-none" style="font-size: 0.82rem;">
                            <div class="pe-2">
                                <div>
                                    <i class="ri-checkbox-circle-fill text-success me-1"></i>
                                    <strong id="applied-coupon-code" class="font-monospace text-dark"></strong>: 
                                    <span id="applied-coupon-desc" class="fw-bold text-success"></span>
                                </div>
                                <div id="applied-coupon-split" class="text-muted fs-11 mt-1 d-none"></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 shadow-none fs-16" onclick="posCart.removeCoupon()" title="Quitar cupón">
                                <i class="ri-close-circle-line"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Desglose de Pagos Múltiples -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-semibold text-primary">Detalle de Pagos</label>
                            <button class="btn btn-sm btn-outline-primary py-1 px-2 shadow-none" type="button" onclick="posCart.openPaymentsModal()">
                                <i class="ri-bank-card-line align-middle"></i> Configurar Pagos
                            </button>
                        </div>
                        <div id="sidebar-payments-summary" class="bg-light p-2 rounded border" style="font-size: 0.8rem;">
                            <div class="text-muted text-center py-2">Sin pagos personalizados (se liquidará en Efectivo al cobrar).</div>
                        </div>
                    </div>

                    <hr class="my-2">

                    <!-- Indicadores y Desglose Financiero -->
                    <div class="d-flex justify-content-between mb-1 text-muted fs-13">
                        <span>Subtotal (Base):</span>
                        <span class="fw-medium text-dark" id="summary-subtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 text-danger fs-13 d-none" id="summary-discount-row">
                        <span>Descuento Regateo:</span>
                        <span class="fw-medium" id="summary-discount">-$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 text-success fs-13 d-none" id="summary-coupon-row">
                        <span>Descuento de Cupón:</span>
                        <span class="fw-medium" id="summary-coupon">-$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 text-muted fs-13" id="summary-iva-row">
                        <span>IVA ({{ $taxIvaRate ?? 16 }}%):</span>
                        <span class="fw-medium text-dark" id="summary-iva">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 text-muted fs-13 d-none" id="summary-ieps-row">
                        <span>IEPS:</span>
                        <span class="fw-medium text-dark" id="summary-ieps">$0.00</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-1 text-warning fs-13 d-none" id="summary-interest-row">
                        <span>Intereses Financiamiento:</span>
                        <span class="fw-medium" id="summary-interest">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 text-info fs-13 d-none" id="summary-commission-row">
                        <span>Comisiones Terminal:</span>
                        <span class="fw-medium" id="summary-commission">$0.00</span>
                    </div>
                    
                    <hr class="my-2">

                    <div class="d-flex justify-content-between align-items-center fs-4 fw-bold mb-2 text-primary">
                        <span>Total Venta:</span>
                        <span id="summary-total">$0.00</span>
                    </div>

                    <!-- Indicadores de Cobro y Saldo Pendiente -->
                    <div class="p-2 rounded bg-light border mb-2">
                        <div class="d-flex justify-content-between fs-13 mb-1">
                            <span class="text-muted">Total Abonado / Pagado:</span>
                            <span class="fw-bold text-success" id="summary-paid">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fs-13 mb-1 d-none" id="summary-balance-row">
                            <span class="text-danger fw-semibold">Saldo Restante:</span>
                            <span class="fw-bold text-danger" id="summary-balance">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fs-13 mb-1 d-none" id="summary-change-row">
                            <span class="text-info fw-semibold">Cambio:</span>
                            <span class="fw-bold text-info" id="summary-change">$0.00</span>
                        </div>
                    </div>

                    <!-- Estado de la Operación (Liquidada vs Apartado) -->
                    <div id="sidebar-sale-status" class="mb-3 d-none"></div>

                    <!-- Alerta de Apartado dinámico -->
                    <div class="alert alert-warning py-2 px-3 mb-3 d-none" id="sidebar-layaway-status">
                        <i class="ri-alert-line me-1 align-middle"></i> Se guardará como **Apartado** (Restante: <strong id="sidebar-layaway-balance">$0.00</strong>)
                    </div>

                    <button class="btn btn-success w-100 btn-lg mb-2" id="btn-checkout" onclick="posCart.checkout()">
                        <i class="ri-bank-card-line me-1"></i> Completar Venta
                    </button>
                    <button class="btn btn-soft-primary w-100 mb-2 shadow-sm" id="btn-quote" onclick="posCart.saveQuote()">
                        <i class="ri-file-text-line me-1"></i> Guardar como Cotización
                    </button>
                </div>
            </div>

            {{-- ── Panel: Pedidos Especiales Listos para Entrega ─────────── --}}
            @php
                $readyOrders = \App\Models\SpecialOrder::where('status', 'ready')
                    ->where('store_id', session('selected_store_id') ?? 0)
                    ->with('customer')
                    ->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->get();
            @endphp
            @if($readyOrders->isNotEmpty())
            <div class="card border-success border-opacity-50 mt-3">
                <div class="card-header bg-success-subtle py-2 d-flex align-items-center">
                    <i class="ri-checkbox-circle-line text-success me-2 fs-5"></i>
                    <span class="fw-semibold text-success">Pedidos Listos para Entrega</span>
                    <span class="badge bg-success ms-auto">{{ $readyOrders->count() }}</span>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($readyOrders as $ro)
                    <a href="{{ route('special-orders.show', $ro) }}" target="_blank"
                       class="list-group-item list-group-item-action py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold text-dark small">{{ $ro->folio }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">
                                    {{ $ro->customer?->full_name }}
                                </div>
                            </div>
                            @if($ro->balanceDue() > 0)
                                <span class="badge bg-warning text-dark">${{ number_format($ro->balanceDue(), 2) }}</span>
                            @else
                                <span class="badge bg-success-subtle text-success">Saldado</span>
                            @endif
                        </div>
                    </a>
                    @endforeach
                    <a href="{{ route('special-orders.index', ['status' => 'ready']) }}" class="list-group-item list-group-item-action text-center text-primary py-2 small">
                        Ver todos <i class="ri-arrow-right-line ms-1"></i>
                    </a>
                </div>
            </div>
            @endif

        </div>
    </div>

    <!-- Modal de Venta Exitosa -->
    <div class="modal fade" id="modal-success" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-body text-center p-5">
                    <div class="text-success mb-4">
                        <i class="ri-checkbox-circle-fill display-1"></i>
                    </div>
                    <h4 class="mb-3">¡Transacción Exitosa!</h4>
                    <p class="text-muted mb-1">Folio generado: <strong id="success-folio" class="fs-5 text-dark"></strong></p>
                    
                    <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total a pagar:</span>
                            <strong id="success-total"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-warning">
                            <span id="success-paid-label">Monto Recibido:</span>
                            <strong id="success-paid"></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-success" id="success-change-wrapper">
                            <span>Cambio:</span>
                            <strong id="success-change"></strong>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 d-flex flex-wrap justify-content-center gap-2">
                        <button type="button" class="btn btn-primary" id="btn-print-ticket">
                            <i class="ri-printer-line align-middle me-1"></i> Imprimir Ticket
                        </button>
                        <button type="button" class="btn btn-soft-info d-none" id="btn-print-delivery-order">
                            <i class="ri-truck-line align-middle me-1"></i> Imprimir Orden de Entrega
                        </button>
                        <button type="button" class="btn btn-warning fw-bold d-none" id="btn-restore-last-parked" data-bs-dismiss="modal" onclick="posCart.restoreMostRecentParkedSale()">
                            <i class="ri-play-circle-line align-middle me-1"></i> Retomar Venta en Espera
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" onclick="posCart.startNewSale()">
                            <i class="ri-refresh-line align-middle me-1"></i> Nueva Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Cerrar modal-success -->

    <!-- Modal OTP POS Verification -->
    <div class="modal fade" id="posOtpVerificationModal" tabindex="-1" aria-labelledby="posOtpVerificationModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-soft-primary p-3">
                    <h5 class="modal-title" id="posOtpVerificationModalLabel">Autorización Requerida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnPosCloseOtpModal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-4">
                        <i class="ri-shield-keyhole-line text-warning" style="font-size: 72px;"></i>
                    </div>
                    <div class="mt-2">
                        <h4 class="mb-3">Autorizar Precio Personalizado</h4>
                        <p class="text-muted mb-4">Se requiere código OTP del administrador para definir precios personalizados. Hemos enviado un código a su correo y WhatsApp.</p>
                        
                        <form id="posOtpFormValidation">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3" id="pos-otp-container">
                                <input type="text" class="form-control text-center pos-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center pos-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center pos-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center pos-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center pos-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center pos-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                            </div>
                            <input type="hidden" id="pos-full-otp-code" name="otp_code">
                            <div class="text-danger mb-3 d-none" id="pos-otp-error-msg">El código ingresado es incorrecto o expiró. Intenta de nuevo.</div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-success w-100 btn-sm" id="btnPosVerifyOtp">Confirmar Autorización</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Pagos Múltiples -->
    <div class="modal fade" id="posPaymentsModal" tabindex="-1" aria-labelledby="posPaymentsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-soft-primary p-3">
                    <h5 class="modal-title text-primary" id="posPaymentsModalLabel">
                        <i class="ri-bank-card-line me-1"></i> Desglose de Pagos Múltiples y Terminales
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 mb-3" style="font-size: 0.85rem;">
                        <i class="ri-information-line me-2"></i>Registra los diferentes cobros recibidos. Si el total abonado es inferior al total de la compra, la transacción se registrará automáticamente como un **Apartado (Pago parcial)**.
                    </div>

                    <div class="table-responsive mb-3" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-bordered table-sm align-middle text-nowrap mb-0" id="table-multiple-payments">
                            <thead class="table-light">
                                <tr>
                                    <th>Método de Pago <span class="text-danger">*</span></th>
                                    <th>Terminal Bancaria</th>
                                    <th>Monto Base ($) <span class="text-danger">*</span></th>
                                    <th>Plazo / Financiamiento</th>
                                    <th>Recargo Financiamiento ($)</th>
                                    <th>Comisión Terminal ($)</th>
                                    <th>Monto Final Cobrado ($)</th>
                                    <th>Referencia / Notas</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="payments-tbody">
                                <!-- Filas de pago dinámicas -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button type="button" class="btn btn-outline-primary btn-sm btn-label" id="btn-add-payment-row">
                            <i class="ri-add-line label-icon align-middle me-2"></i> Agregar Otro Pago
                        </button>
                        
                        <div class="text-end">
                            <span class="text-muted fs-13">Subtotal de Compra: <strong class="text-dark" id="modal-subtotal-items">$0.00</strong></span>
                        </div>
                    </div>

                    <!-- Panel de Totales y Saldos -->
                    <div class="row g-3 bg-light p-3 rounded border">
                        <div class="col-md-3 border-end">
                            <div class="p-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.75rem;">Intereses Financiamiento:</span>
                                <h5 class="text-warning mb-0" id="modal-total-interest">$0.00</h5>
                            </div>
                        </div>
                        <div class="col-md-3 border-end">
                            <div class="p-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.75rem;">Comisiones Terminal (Cliente):</span>
                                <h5 class="text-info mb-0" id="modal-total-commissions">$0.00</h5>
                            </div>
                        </div>
                        <div class="col-md-3 border-end">
                            <div class="p-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.75rem;">Gran Total Venta:</span>
                                <h4 class="text-primary mb-0" id="modal-grand-total">$0.00</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-2">
                                <span class="text-muted d-block mb-1" style="font-size: 0.75rem;">Total Registrado / Diferencia:</span>
                                <h5 class="mb-1" id="modal-total-payments-added">$0.00</h5>
                                <span class="badge" id="modal-remaining-badge">Restante: $0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success btn-sm" id="btn-confirm-payments">Confirmar Pagos</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Creación Rápida de Clientes -->
    <div class="modal fade" id="posCreateCustomerModal" tabindex="-1" aria-labelledby="posCreateCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-soft-success p-3">
                    <h5 class="modal-title text-success" id="posCreateCustomerModalLabel">Registrar Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="posCreateCustomerForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="name" required placeholder="Ej. Juan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control form-control-sm" name="last_name" placeholder="Ej. Pérez">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control form-control-sm" name="phone" placeholder="Ej. 3312345678">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control form-control-sm" name="email" placeholder="Ej. juan@correo.com">
                            </div>
                            <div class="col-md-12">
                                <hr class="my-2 text-muted">
                                <h6 class="text-primary mb-2">Datos de Facturación (Opcional)</h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">RFC</label>
                                <input type="text" class="form-control form-control-sm" name="rfc" placeholder="Ej. XAXX010101000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Razón Social</label>
                                <input type="text" class="form-control form-control-sm" name="business_name" placeholder="Ej. Juan Pérez S.A.">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Dirección Fiscal</label>
                                <textarea class="form-control form-control-sm" name="legal_address" rows="2" placeholder="Calle, Número, Colonia, CP..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <hr class="my-2 text-muted">
                                <h6 class="text-primary mb-2"><i class="ri-truck-line me-1"></i> Domicilio Habitual para Envíos (Opcional)</h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Calle y Número</label>
                                <input type="text" class="form-control form-control-sm" name="street" placeholder="Ej. Calle Morelos #123">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Colonia</label>
                                <input type="text" class="form-control form-control-sm" name="colonia" placeholder="Ej. Centro">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control form-control-sm" name="city" value="Morelia" placeholder="Ej. Morelia">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <input type="text" class="form-control form-control-sm" name="state" value="Michoacán" placeholder="Ej. Michoacán">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">C.P.</label>
                                <input type="text" class="form-control form-control-sm" name="zip" placeholder="Ej. 58000">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Referencias de Entrega</label>
                                <input type="text" class="form-control form-control-sm" name="delivery_references" placeholder="Ej. Fachada blanca, portón café">
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success btn-sm" id="btn-save-pos-customer">Guardar Cliente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL: PRODUCTO PERSONALIZADO (COMODÍN)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-wildcard" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="ri-edit-2-line me-2"></i>Producto Personalizado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- Paso 1: Datos del producto --}}
                    <div id="wildcard-step-1">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre del concepto <span class="text-danger">*</span></label>
                            <input type="text" id="wildcard-name" class="form-control" placeholder="Ej. Mesa comedor roble 180cm 6 sillas" maxlength="200">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción / Especificaciones</label>
                            <textarea id="wildcard-description" class="form-control" rows="3" placeholder="Materiales, dimensiones, acabados, instrucciones especiales..."></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-7">
                                <label class="form-label fw-semibold">Precio unitario <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" id="wildcard-price" class="form-control" min="0.01" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="form-label fw-semibold">Cantidad</label>
                                <input type="number" id="wildcard-quantity" class="form-control" min="1" value="1">
                            </div>
                        </div>
                        <div class="alert alert-warning d-flex align-items-center mt-3 py-2" role="alert">
                            <i class="ri-shield-keyhole-line me-2 fs-5"></i>
                            <small>El precio libre requiere autorización OTP de un supervisor.</small>
                        </div>
                    </div>

                    {{-- Paso 2: OTP --}}
                    <div id="wildcard-step-2" class="d-none">
                        <div class="text-center mb-3">
                            <i class="ri-shield-check-line text-warning fs-1"></i>
                            <p class="mb-1 fw-semibold">Autorización requerida</p>
                            <small class="text-muted">Se ha enviado un código OTP al <strong>Supervisor / Dirección</strong> por correo y WhatsApp. Pide el código y éntralo abajo.</small>
                        </div>
                        <div class="d-flex justify-content-center gap-2 mb-3" id="wildcard-otp-inputs">
                            @for($i=0;$i<6;$i++)
                            <input type="text" maxlength="1" class="form-control text-center fw-bold fs-5 p-2 wildcard-otp-digit" style="width:44px;height:44px;">
                            @endfor
                        </div>
                        <input type="hidden" id="wildcard-otp-full">
                        <div id="wildcard-otp-error" class="text-danger text-center small d-none"></div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light d-none" id="btn-wildcard-back" onclick="posCart.wildcardBack()">
                        <i class="ri-arrow-left-line me-1"></i> Atrás
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning fw-bold" id="btn-wildcard-next" onclick="posCart.wildcardNext()">
                        Solicitar OTP <i class="ri-arrow-right-line ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-success fw-bold d-none" id="btn-wildcard-confirm" onclick="posCart.wildcardConfirmOtp()">
                        <i class="ri-check-line me-1"></i> Confirmar y Agregar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL: NOTA POR ÍTEM DEL CARRITO
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-item-note" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><i class="ri-chat-3-line me-2 text-info"></i>Nota del ítem</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="item-note-index">
                    <textarea id="item-note-text" class="form-control" rows="4" placeholder="Instrucciones especiales, color, talla, acabado..."></textarea>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-info text-white" onclick="posCart.saveItemNote()">
                        <i class="ri-save-line me-1"></i> Guardar Nota
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL: PEDIDO ESPECIAL (sin stock)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-special-order" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="ri-file-list-3-line me-2"></i>Generar Pedido Especial</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-start py-2 mb-3">
                        <i class="ri-information-line me-2 mt-1 flex-shrink-0"></i>
                        <small>Este producto no tiene stock disponible. Al generar un Pedido Especial se registra el anticipo del cliente y queda en seguimiento hasta la recepción y entrega.</small>
                    </div>

                    {{-- Info del producto --}}
                    <div class="bg-light rounded p-3 mb-3" id="special-order-product-info">
                        <div class="d-flex align-items-center gap-3">
                            <i class="ri-box-3-line fs-2 text-primary"></i>
                            <div>
                                <div class="fw-bold" id="so-product-name">—</div>
                                <div class="text-muted small" id="so-product-sku"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- Título personalizado para productos comodín --}}
                        <div class="col-12" id="so-custom-title-col" style="display: none;">
                            <label class="form-label fw-semibold">Nombre del Mueble / Trabajo Personalizado <span class="text-danger">*</span></label>
                            <input type="text" id="so-custom-name" class="form-control" placeholder="Ej. Comedor de Caoba 8 Sillas con labrado especial">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" id="so-quantity" class="form-control" min="1" value="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Precio Venta Unit. <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" id="so-unit-price" class="form-control" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4" id="so-expected-date-col">
                            <label class="form-label fw-semibold">Fecha Promesa de Entrega</label>
                            <input type="date" id="so-expected-date" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span> <small class="text-muted">(requerido para el encargo)</small></label>
                            <select id="so-customer-select" class="form-select">
                                <option value="">— Selecciona un cliente —</option>
                                @foreach($defaultCustomers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->full_name }}{{ $customer->phone ? ' — Tel: ' . $customer->phone : '' }} {{ $customer->rfc ? "({$customer->rfc})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Anticipo ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" id="so-deposit" class="form-control" min="0" step="0.01" value="0">
                            </div>
                            <div class="form-text text-warning fw-semibold fs-11" id="so-deposit-hint"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Método de Pago</label>
                            <select id="so-payment-method" class="form-select">
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4" id="so-terminal-col" style="display: none;">
                            <label class="form-label fw-semibold">Terminal Bancaria</label>
                            <select id="so-payment-terminal" class="form-select">
                                <option value="">— Selecciona Terminal —</option>
                                @foreach($paymentTerminals as $pt)
                                    <option value="{{ $pt->id }}">{{ $pt->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas / Personalización</label>
                            <textarea id="so-notes" class="form-control" rows="2" placeholder="Especificaciones del mueble, color, tela, dimensiones..."></textarea>
                        </div>

                        {{-- Sección de Entrega a Domicilio en Pedido Especial --}}
                        <div class="col-12 mt-2">
                            <div class="card border border-light-subtle shadow-none mb-0">
                                <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="so-is-delivery" onchange="posCart.toggleSoDelivery(this.checked)">
                                        <label class="form-check-label fw-bold text-dark" for="so-is-delivery">
                                            <i class="ri-truck-line text-primary me-1"></i> ¿Requiere Entrega a Domicilio?
                                        </label>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary fs-11" id="so-delivery-badge" style="display: none;">Logística Activada</span>
                                </div>
                                <div class="card-body p-3" id="so-delivery-fields" style="display: none;">
                                    <!-- Pregunta: Domicilio Habitual vs Otro Domicilio -->
                                    <div class="card border border-info-subtle bg-info-subtle bg-opacity-10 mb-3 shadow-none">
                                        <div class="card-body p-2 d-flex align-items-center justify-content-between">
                                            <div>
                                                <span class="fw-bold fs-12 text-dark">
                                                    <i class="ri-home-gear-line text-primary me-1"></i> ¿El envío será a otro domicilio?
                                                </span>
                                                <div class="text-muted fs-11" id="so-delivery-address-status-text">
                                                    Por defecto se autorrellenan los datos y coordenadas habituales del cliente.
                                                </div>
                                            </div>
                                            <div class="form-check form-switch form-switch-md mb-0">
                                                <input class="form-check-input" type="checkbox" id="so-delivery-is-other-address" onchange="posCart.toggleSoDeliveryOtherAddress(this.checked)">
                                                <label class="form-check-label fw-semibold fs-11 text-nowrap" for="so-delivery-is-other-address">Otro Domicilio</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contacto de Entrega -->
                                    <h6 class="text-uppercase text-muted fw-bold fs-11 mb-2">
                                        <i class="ri-user-location-line me-1"></i> 1. Contacto de Entrega
                                    </h6>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-7">
                                            <label class="form-label fs-11 fw-semibold mb-1">Nombre de quien recibe <span class="text-danger">*</span></label>
                                            <input type="text" id="so-del-recipient" class="form-control form-control-sm" placeholder="Nombre completo del receptor">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fs-11 fw-semibold mb-1">Teléfono de contacto <span class="text-danger">*</span></label>
                                            <input type="tel" id="so-del-phone" class="form-control form-control-sm" placeholder="Teléfono 10 dígitos">
                                        </div>
                                    </div>

                                    <!-- Dirección Estructurada -->
                                    <h6 class="text-uppercase text-muted fw-bold fs-11 mb-2">
                                        <i class="ri-map-pin-2-line me-1"></i> 2. Dirección de Destino
                                    </h6>
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-8">
                                            <label class="form-label fs-11 fw-semibold mb-1">Calle y Número (Ext. e Int.) <span class="text-danger">*</span></label>
                                            <input type="text" id="so-del-street" class="form-control form-control-sm" placeholder="Ej. Av. Tecnológico #450 Int. 3B">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fs-11 fw-semibold mb-1">Código Postal</label>
                                            <input type="text" id="so-del-zip" class="form-control form-control-sm" placeholder="Ej. 28000">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-4">
                                            <label class="form-label fs-11 fw-semibold mb-1">Colonia / Fraccionamiento <span class="text-danger">*</span></label>
                                            <input type="text" id="so-del-colonia" class="form-control form-control-sm" placeholder="Ej. Centro">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fs-11 fw-semibold mb-1">Ciudad / Municipio</label>
                                            <input type="text" id="so-del-city" class="form-control form-control-sm" placeholder="Ej. Colima">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fs-11 fw-semibold mb-1">Estado</label>
                                            <input type="text" id="so-del-state" class="form-control form-control-sm" placeholder="Ej. Colima">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fs-11 fw-semibold mb-1">Referencias del Domicilio</label>
                                        <input type="text" id="so-del-references" class="form-control form-control-sm" placeholder="Ej. Casa verde de 2 pisos, frente al parque, portón blanco">
                                    </div>

                                    <!-- Google Maps Pin -->
                                    <h6 class="text-uppercase text-muted fw-bold fs-11 mb-2">
                                        <i class="ri-map-2-line me-1"></i> 3. Geolocalización en Google Maps
                                    </h6>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-so-locate-address" onclick="posCart.locateSoDeliveryAddressOnMap()">
                                            <i class="ri-crosshair-2-line me-1"></i> Buscar Dirección en Mapa
                                        </button>
                                        <span class="badge bg-light text-secondary border" id="so-delivery-coords-badge">Pin: Sin fijar</span>
                                    </div>

                                    <div id="so-delivery-map" style="height: 220px; width: 100%; border-radius: 8px;" class="border bg-light mb-2">
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted small">
                                            <span><i class="ri-loader-4-line ri-spin me-1"></i> Cargando mapa interactivo...</span>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Lat</span>
                                                <input type="number" step="any" id="so-del-lat" class="form-control" placeholder="19.2433">
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Lng</span>
                                                <input type="number" step="any" id="so-del-lng" class="form-control" placeholder="-103.7250">
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <a id="so-delivery-maps-link" href="#" target="_blank" class="btn btn-sm btn-outline-secondary w-100 d-none" title="Abrir en Google Maps"><i class="ri-external-link-line"></i> Ver</a>
                                        </div>
                                    </div>

                                    <!-- Costos y Programación -->
                                    <h6 class="text-uppercase text-muted fw-bold fs-11 mb-2">
                                        <i class="ri-money-dollar-circle-line me-1"></i> 4. Costos y Programación
                                    </h6>
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6">
                                            <label class="form-label fs-11 fw-semibold mb-1">Costo de Flete ($)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" id="so-del-cost" class="form-control form-control-sm" min="0" step="0.01" value="0.00" oninput="posCart.updateSpecialOrderSummary()">
                                            </div>
                                            <small class="text-muted fs-11">Se agregará al total del pedido especial.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fs-11 fw-semibold mb-1">Fecha Promesa de Entrega</label>
                                            <input type="date" id="so-del-date" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label fs-11 fw-semibold mb-1">Hora Específica de Entrega</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><i class="ri-time-line"></i></span>
                                                <input type="time" id="so-del-time" class="form-control form-control-sm" value="11:00">
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('so-del-time').value='10:00'">10:00 AM</button>
                                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('so-del-time').value='12:00'">12:00 PM</button>
                                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('so-del-time').value='14:00'">02:00 PM</button>
                                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('so-del-time').value='16:00'">04:00 PM</button>
                                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('so-del-time').value='18:00'">06:00 PM</button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fs-11 fw-semibold mb-1">Instrucciones al Chofer</label>
                                            <input type="text" id="so-del-instructions" class="form-control form-control-sm" placeholder="Ej. Llamar antes, casa portón negro">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="so-summary" class="mt-3 p-3 bg-light rounded d-none border">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total del pedido:</span><strong id="so-total-display">$0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between text-success mb-1">
                            <span>Anticipo a abonar:</span><strong id="so-deposit-display">$0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between text-info mb-1" id="so-commission-row" style="display: none;">
                            <span>Comisión por Terminal:</span><strong id="so-commission-display">$0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between text-primary mb-1 fw-bold" id="so-today-total-row" style="display: none;">
                            <span>Total a Cobrar Hoy:</span><strong id="so-today-total-display">$0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between text-danger fw-bold border-top pt-2 mt-1">
                            <span>Balance pendiente a la entrega:</span><strong id="so-balance-display">$0.00</strong>
                        </div>
                        <div class="alert alert-danger py-1 px-2 mt-2 mb-0 d-none fs-12" id="so-deposit-error">
                            <i class="ri-error-warning-line me-1"></i> El anticipo no cumple con el 20% mínimo obligatorio.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-bold" id="btn-create-special-order" onclick="posCart.submitSpecialOrder()">
                        <i class="ri-file-list-3-line me-1"></i> Crear Pedido Especial
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL: PEDIDO A DOMICILIO CON GOOGLE MAPS
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-delivery-order" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="ri-truck-line me-2"></i> Configurar Pedido a Domicilio
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-soft-primary py-2 mb-3">
                        <small><i class="ri-information-line me-1"></i>Captura los datos de destino y geolocalización. Se generará la <strong>Orden de Entrega</strong> con mapa GPS para el chofer.</small>
                    </div>

                    <!-- Pregunta: Domicilio Habitual vs Otro Domicilio -->
                    <div class="card border border-info-subtle bg-info-subtle bg-opacity-10 mb-3 shadow-none">
                        <div class="card-body p-2 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-bold fs-13 text-dark">
                                    <i class="ri-home-gear-line text-primary me-1"></i> ¿El envío será a otro domicilio?
                                </span>
                                <div class="text-muted fs-11" id="delivery-address-status-text">
                                    Por defecto se autorrellenan los datos y coordenadas habituales del cliente.
                                </div>
                            </div>
                            <div class="form-check form-switch form-switch-md mb-0">
                                <input class="form-check-input" type="checkbox" id="delivery-is-other-address" onchange="posCart.toggleDeliveryOtherAddress(this.checked)">
                                <label class="form-check-label fw-semibold text-nowrap" for="delivery-is-other-address">Otro Domicilio</label>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Receptor -->
                    <h6 class="text-uppercase text-muted fw-bold fs-12 mb-2"><i class="ri-user-location-line me-1"></i> 1. Contacto de Entrega</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label form-label-sm fw-semibold">Nombre de quien recibe <span class="text-danger">*</span></label>
                            <input type="text" id="delivery-recipient-name" class="form-control form-control-sm" placeholder="Nombre completo">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label form-label-sm fw-semibold">Teléfono de contacto <span class="text-danger">*</span></label>
                            <input type="tel" id="delivery-phone" class="form-control form-control-sm" placeholder="10 dígitos">
                        </div>
                    </div>

                    <!-- Dirección Estructurada -->
                    <h6 class="text-uppercase text-muted fw-bold fs-12 mb-2"><i class="ri-map-pin-2-line me-1"></i> 2. Dirección de Destino</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-8">
                            <label class="form-label form-label-sm fw-semibold">Calle y Número (Ext. e Int.) <span class="text-danger">*</span></label>
                            <input type="text" id="delivery-street" class="form-control form-control-sm" placeholder="Ej. Av. Tecnológico #450 Int. 3B">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Código Postal</label>
                            <input type="text" id="delivery-zip" class="form-control form-control-sm" placeholder="Ej. 28000">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Colonia / Fraccionamiento <span class="text-danger">*</span></label>
                            <input type="text" id="delivery-colonia" class="form-control form-control-sm" placeholder="Ej. Centro">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Ciudad / Municipio</label>
                            <input type="text" id="delivery-city" class="form-control form-control-sm" placeholder="Ej. Colima">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Estado</label>
                            <input type="text" id="delivery-state" class="form-control form-control-sm" placeholder="Ej. Colima">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Referencias del Domicilio</label>
                        <input type="text" id="delivery-references" class="form-control form-control-sm" placeholder="Ej. Casa verde de 2 pisos, frente al parque, portón blanco">
                    </div>

                    <!-- Google Maps Pin -->
                    <h6 class="text-uppercase text-muted fw-bold fs-12 mb-2"><i class="ri-map-2-line me-1"></i> 3. Geolocalización en Google Maps</h6>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-locate-address" onclick="posCart.locateDeliveryAddressOnMap()">
                            <i class="ri-crosshair-2-line me-1"></i> Buscar Dirección en Mapa
                        </button>
                        <span class="badge bg-light text-secondary border" id="delivery-coords-badge">Pin: Sin fijar</span>
                    </div>

                    <div id="delivery-map" style="height: 250px; width: 100%; border-radius: 8px;" class="border bg-light mb-2">
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted small">
                            <span><i class="ri-loader-4-line ri-spin me-1"></i> Cargando mapa interactivo...</span>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Lat</span>
                                <input type="number" step="any" id="delivery-lat" class="form-control" placeholder="19.2433">
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Lng</span>
                                <input type="number" step="any" id="delivery-lng" class="form-control" placeholder="-103.7250">
                            </div>
                        </div>
                        <div class="col-2">
                            <a id="delivery-maps-link" href="#" target="_blank" class="btn btn-sm btn-outline-secondary w-100 d-none" title="Abrir en Google Maps"><i class="ri-external-link-line"></i> Ver</a>
                        </div>
                    </div>

                    <!-- Costos y Programación -->
                    <h6 class="text-uppercase text-muted fw-bold fs-12 mb-2"><i class="ri-money-dollar-circle-line me-1"></i> 4. Costos y Programación de Reparto</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Costo de Flete / Envío ($)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" id="delivery-cost" class="form-control" value="0.00" placeholder="0.00">
                            </div>
                            <small class="text-muted fs-11">Se agregará al ticket de venta.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Costo de Armado / Instalación ($)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" id="delivery-installation-cost" class="form-control" value="0.00" placeholder="0.00">
                            </div>
                            <small class="text-muted fs-11">Se agregará al ticket de venta.</small>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Fecha Promesa de Entrega</label>
                            <input type="date" id="delivery-date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm fw-semibold">Hora Específica de Entrega</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ri-time-line"></i></span>
                                <input type="time" id="delivery-time" class="form-control form-control-sm" value="11:00">
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('delivery-time').value='10:00'">10:00 AM</button>
                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('delivery-time').value='12:00'">12:00 PM</button>
                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('delivery-time').value='14:00'">02:00 PM</button>
                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('delivery-time').value='16:00'">04:00 PM</button>
                                <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1 fs-10" onclick="document.getElementById('delivery-time').value='18:00'">06:00 PM</button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm fw-semibold">Instrucciones Especiales para el Chofer / Repartidor</label>
                        <textarea id="delivery-instructions" class="form-control form-control-sm" rows="2" placeholder="Ej. Subir por escaleras a segundo piso, llevar cobijas protectoras, avisar 30 min antes..."></textarea>
                    </div>

                    <div class="form-check form-check-sm mb-0 p-2 bg-light rounded border" id="delivery-save-profile-container" style="display: none;">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="delivery-update-customer-profile" checked>
                        <label class="form-check-label fs-12 text-dark fw-medium" for="delivery-update-customer-profile">
                            <i class="ri-save-3-line text-success me-1"></i> Guardar estos datos y coordenadas en el expediente del cliente para futuros pedidos
                        </label>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-soft-danger d-none" id="btn-remove-delivery" onclick="posCart.removeDelivery()">
                            <i class="ri-delete-bin-line me-1"></i> Quitar Envío
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success fw-bold" id="btn-save-delivery" onclick="posCart.saveDelivery()">
                            <i class="ri-check-line me-1"></i> Guardar y Aplicar a Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL: CONFIGURAR SERVICIO / CONCEPTO LIBRE AL CARRITO
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-service-config" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning-subtle text-warning">
                    <h5 class="modal-title fw-bold">
                        <i class="ri-service-line me-2"></i> Concepto Libre / Servicio al Carrito
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="service-product-id">
                    <input type="hidden" id="service-is-wildcard">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Concepto / Nombre del Servicio <span class="text-danger">*</span></label>
                        <input type="text" id="service-concept-name" class="form-control form-control-lg" placeholder="Ej. Flete Foráneo, Armado Especial, Reparación...">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Precio Unitario ($) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" id="service-concept-price" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Cantidad</label>
                            <input type="number" min="1" id="service-concept-qty" class="form-control form-control-lg text-center" value="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notas / Especificaciones</label>
                        <textarea id="service-concept-notes" class="form-control" rows="2" placeholder="Detalles visibles en el ticket..."></textarea>
                    </div>

                    <div class="alert alert-light border p-2 d-flex align-items-center justify-content-between">
                        <small class="text-muted"><i class="ri-hammer-line me-1"></i>¿Es un mueble sobre diseño para fabricación?</small>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="posCart.switchToSpecialOrderFromService()">
                            Pedido Especial
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning fw-bold text-dark" onclick="posCart.confirmAddService()">
                        <i class="ri-shopping-cart-line me-1"></i> Agregar al Carrito
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ══════════════════════════════════════════════════════════════
         MODAL 1: APERTURA DE TURNO DE CAJA
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-open-cash-shift" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="ri-key-2-line me-2"></i>Apertura de Turno de Caja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 mb-3">
                        <small><i class="ri-information-line me-1"></i>Ingresa el <strong>fondo de efectivo inicial</strong> con el que comienzas el turno para dar cambio a los clientes.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Fondo Inicial de Caja <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.50" min="0" id="open-cs-amount" class="form-control fw-bold fs-4 text-center" placeholder="0.00" autofocus>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-soft-secondary flex-grow-1" onclick="document.getElementById('open-cs-amount').value = 500">$500</button>
                            <button type="button" class="btn btn-sm btn-soft-secondary flex-grow-1" onclick="document.getElementById('open-cs-amount').value = 1000">$1,000</button>
                            <button type="button" class="btn btn-sm btn-soft-secondary flex-grow-1" onclick="document.getElementById('open-cs-amount').value = 1500">$1,500</button>
                            <button type="button" class="btn btn-sm btn-soft-secondary flex-grow-1" onclick="document.getElementById('open-cs-amount').value = 2000">$2,000</button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Notas u Observaciones de Apertura (opcional)</label>
                        <textarea id="open-cs-notes" class="form-control" rows="2" placeholder="Ej. Billetes de baja denominación entregados por gerencia"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success fw-bold px-4" id="btn-submit-open-shift" onclick="cashShiftManager.submitOpenShift()">
                        <i class="ri-check-line me-1"></i> Iniciar Turno
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL 2: MOVIMIENTO DE EFECTIVO (ENTRADA / SANGRÍA)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-cash-movement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="ri-exchange-dollar-line me-2"></i>Movimiento de Efectivo en Caja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Movimiento <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="cs-mov-type" id="cs-mov-in" value="entrada" checked onchange="cashShiftManager.toggleMovementType()">
                                <label class="btn btn-outline-success w-100 fw-bold py-2" for="cs-mov-in">
                                    <i class="ri-add-circle-line me-1"></i> (+) Entrada
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="cs-mov-type" id="cs-mov-out" value="salida" onchange="cashShiftManager.toggleMovementType()">
                                <label class="btn btn-outline-danger w-100 fw-bold py-2" for="cs-mov-out">
                                    <i class="ri-indeterminate-circle-line me-1"></i> (-) Retiro / Sangría
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Selector de Finalidad del Retiro (Bóveda vs Gasto) --}}
                    <div class="mb-3" id="cs-mov-category-wrapper" style="display: none;">
                        <label class="form-label fw-bold small text-muted text-uppercase">Finalidad de la Salida <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="cs-mov-category" id="cs-cat-vault" value="traslado_boveda" checked onchange="cashShiftManager.toggleExpenseHint()">
                                <label class="btn btn-outline-secondary w-100 p-2 text-start h-100" for="cs-cat-vault">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="ri-bank-line fs-16 me-1 text-primary"></i>
                                        <strong class="fs-13">A Bóveda / Banco</strong>
                                    </div>
                                    <span class="text-muted d-block lh-sm" style="font-size: 11px;">Resguardo interno de efectivo. NO es gasto contable.</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="cs-mov-category" id="cs-cat-expense" value="gasto_operativo" onchange="cashShiftManager.toggleExpenseHint()">
                                <label class="btn btn-outline-warning w-100 p-2 text-start h-100" for="cs-cat-expense">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="ri-file-list-3-line fs-16 me-1 text-warning"></i>
                                        <strong class="fs-13 text-dark">Gasto de Tienda</strong>
                                    </div>
                                    <span class="text-muted d-block lh-sm" style="font-size: 11px;">Agua, insumos, limpieza. Se suma a Gastos empresariales.</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-2 alert alert-warning py-1 px-2 small mb-0 d-none" id="cs-expense-hint">
                            <i class="ri-information-line me-1"></i> Este retiro se registrará automáticamente en el <strong>Módulo de Gastos</strong>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Monto <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.50" min="0.01" id="cs-mov-amount" class="form-control fw-bold fs-5" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Concepto / Motivo <span class="text-danger">*</span></label>
                        <input type="text" id="cs-mov-concept" class="form-control" placeholder="Ej. Aportación de cambio extra, Sangría para bóveda, Pago de garrafón">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Notas Adicionales</label>
                        <textarea id="cs-mov-notes" class="form-control" rows="2" placeholder="Observaciones complementarias"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-bold" id="btn-submit-movement" onclick="cashShiftManager.submitMovement()">
                        <i class="ri-save-line me-1"></i> Registrar Movimiento
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL 3: CORTE X (ARQUEO PARCIAL INFORMATIVO)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-corte-x" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold"><i class="ri-file-chart-line me-2"></i>Corte X — Arqueo Parcial de Turno</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <span class="badge bg-info-subtle text-info fs-12" id="cx-folio">TURNO-XXXX</span>
                            <small class="text-muted ms-2">Cajero: <strong id="cx-cashier">--</strong></small>
                        </div>
                        <small class="text-muted">Apertura: <strong id="cx-opened">--</strong></small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border border-light-subtle shadow-none h-100">
                                <div class="card-header bg-light py-2">
                                    <h6 class="card-title mb-0 fs-13"><i class="ri-bank-card-line me-1"></i>Ventas por Forma de Cobro</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Efectivo (Ventas):</span><strong id="cx-sales-cash">$0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2" id="cx-so-cash-row">
                                        <span>Efectivo (Pedidos Especiales):</span><strong id="cx-so-cash">$0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tarjetas (Terminales):</span><strong id="cx-sales-card">$0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Transferencias:</span><strong id="cx-sales-transfer">$0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 border-top fw-bold text-dark fs-14">
                                        <span>Total Cobrado:</span><span class="text-success" id="cx-sales-total">$0.00</span>
                                    </div>
                                    <div class="text-muted small mt-1">Tickets emitidos: <span id="cx-sales-count">0</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border border-light-subtle shadow-none h-100">
                                <div class="card-header bg-light py-2">
                                    <h6 class="card-title mb-0 fs-13"><i class="ri-safe-2-line me-1"></i>Efectivo en Cajón</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>(+) Fondo Inicial:</span><strong id="cx-opening">$0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>(+) Total Cobros Efectivo:</span><strong id="cx-cash-all">$0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span>(+) Entradas de Efectivo:</span><strong id="cx-cash-in">$0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 text-danger">
                                        <span>(-) Retiros / Sangrías:</span><strong id="cx-cash-out">-$0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 ps-2 text-muted small">
                                        <span>↳ A Bóveda / Banco:</span><span id="cx-vault-cash">$0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 ps-2 text-muted small">
                                        <span>↳ Gastos de Tienda:</span><span id="cx-expenses-cash">$0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 border-top fw-bold fs-15 bg-light-subtle p-2 rounded">
                                        <span>(=) Efectivo Esperado:</span><span class="text-primary" id="cx-expected-cash">$0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="cashShiftManager.printCurrentCorte('x', 'ticket')" title="Imprimir Ticket Térmico 80mm">
                        <i class="ri-printer-line me-1"></i> Ticket 80mm
                    </button>
                    <button type="button" class="btn btn-primary fw-bold" onclick="cashShiftManager.printCurrentCorte('x', 'a4')" title="Imprimir Comprobante Oficial en Hoja A4">
                        <i class="ri-file-text-line me-1"></i> Imprimir Hoja A4
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL 4: CORTE Z (CIERRE DEFINITIVO Y ARQUEO FÍSICO)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-corte-z" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="ri-lock-2-line me-2"></i>Corte Z — Cierre Definitivo de Turno</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 mb-3">
                        <i class="ri-alert-line me-1"></i> Esta acción <strong>cerrará el turno de caja definitivamente</strong>. Cuenta físicamente el efectivo presente en tu cajón e ingrésalo abajo.
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <span class="text-muted small d-block">Efectivo Calculado por Sistema:</span>
                                <h3 class="fw-bold text-primary mb-0 mt-1" id="cz-expected-display">$0.00</h3>
                                <small class="text-muted" id="cz-summary-breakdown">Fondo + Ventas en efectivo + Entradas - Retiros</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <label class="form-label fw-bold text-dark small mb-1">Efectivo Físico Contado en Cajón <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.50" min="0" id="cz-actual-cash" class="form-control fw-bold fs-4 text-end" placeholder="0.00" oninput="cashShiftManager.recalcDifference()">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Caja de Diferencia (Sobrante / Faltante / Exacto) --}}
                    <div class="p-3 rounded border mb-3 text-center" id="cz-diff-box" style="background-color: #f8f9fa;">
                        <span class="text-muted fs-12 fw-semibold text-uppercase">Resultado del Arqueo (Diferencia):</span>
                        <div class="fs-3 fw-bold my-1" id="cz-diff-display">$0.00</div>
                        <div class="small fw-semibold" id="cz-diff-status">Ingresa el efectivo contado para calcular la diferencia</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Observaciones o Notas de Cierre</label>
                        <textarea id="cz-notes" class="form-control" rows="2" placeholder="Motivo de sobrante/faltante, justificaciones o notas para supervisión..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger fw-bold px-4" id="btn-submit-corte-z" onclick="cashShiftManager.submitCorteZ()">
                        <i class="ri-lock-line me-1"></i> Cerrar Caja Definitivamente
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL: SELECTOR DE MODO DE CARGA DE COTIZACIÓN (SIN INTERVENIR VENTA ACTIVA)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-quote-load-mode" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="ri-Instance-line ri-stack-line me-2"></i>Venta Pendiente Detectada en el POS
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info d-flex align-items-start py-2 px-3 mb-3">
                        <i class="ri-information-line fs-4 me-2 text-info flex-shrink-0"></i>
                        <div class="fs-13">
                            Actualmente tienes productos o información cargada en el Punto de Venta. Elige cómo deseas cargar la cotización <strong id="qlm-quote-folio" class="font-monospace"></strong> para <strong>no perder ni afectar tu venta pendiente</strong>:
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-light h-100">
                                <div class="text-uppercase text-muted fw-bold fs-11 mb-1">
                                    <i class="ri-shopping-cart-2-line me-1 text-warning"></i>Venta Actual en Pantalla
                                </div>
                                <div class="fw-bold text-dark fs-14 mb-1" id="qlm-current-customer">Público en General</div>
                                <div class="d-flex justify-content-between text-muted fs-12">
                                    <span>Artículos en carrito: <strong class="text-dark" id="qlm-current-items">0</strong></span>
                                    <span>Total aprox.: <strong class="text-primary" id="qlm-current-total">$0.00</strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border border-primary border-opacity-25 rounded p-3 bg-primary-subtle bg-opacity-10 h-100">
                                <div class="text-uppercase text-primary fw-bold fs-11 mb-1">
                                    <i class="ri-file-list-3-line me-1"></i>Cotización a Cargar (<span id="qlm-quote-folio-card" class="font-monospace"></span>)
                                </div>
                                <div class="fw-bold text-dark fs-14 mb-1" id="qlm-quote-customer">Público en General</div>
                                <div class="d-flex justify-content-between text-muted fs-12">
                                    <span>Artículos cotizados: <strong class="text-dark" id="qlm-quote-items">0</strong></span>
                                    <span>Total cotizado: <strong class="text-success" id="qlm-quote-total">$0.00</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success text-start p-3 border-2 shadow-sm" onclick="posCart.proceedAfterLoadMode('park_and_load')">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0 me-3">
                                        <div class="avatar-title bg-success text-white rounded-circle fs-18">
                                            <i class="ri-bookmark-3-line"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold fs-14 text-dark">
                                            1. Poner venta actual en espera y abrir cotización en limpio
                                            <span class="badge bg-success ms-1 fs-10">Recomendado</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Guarda intacta tu venta actual (productos, cliente, vendedor, pagos y envío) en <strong>"Ventas en Espera"</strong> y abre la cotización por separado. Podrás retomar tu venta pendiente con 1 clic en cualquier momento.
                                        </small>
                                    </div>
                                </div>
                                <i class="ri-arrow-right-s-line fs-20 text-success ms-2"></i>
                            </div>
                        </button>

                        <button type="button" class="btn btn-outline-primary text-start p-3" onclick="posCart.proceedAfterLoadMode('merge')">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0 me-3">
                                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-18">
                                            <i class="ri-add-box-line"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold fs-14 text-dark">
                                            2. Agregar / Fusionar los productos de la cotización a la venta actual
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Conserva los productos y la información que ya tienes en el POS y suma los artículos de la cotización al mismo carrito.
                                        </small>
                                    </div>
                                </div>
                                <i class="ri-arrow-right-s-line fs-20 text-primary ms-2"></i>
                            </div>
                        </button>

                        <button type="button" class="btn btn-outline-danger text-start p-3" onclick="posCart.proceedAfterLoadMode('replace')">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0 me-3">
                                        <div class="avatar-title bg-danger-subtle text-danger rounded-circle fs-18">
                                            <i class="ri-refresh-line"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold fs-14 text-dark">
                                            3. Reemplazar carrito actual con la cotización
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Descarta los productos cargados actualmente en pantalla y los sustituye por los de la cotización.
                                        </small>
                                    </div>
                                </div>
                                <i class="ri-arrow-right-s-line fs-20 text-danger ms-2"></i>
                            </div>
                        </button>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar y seguir en mi venta actual</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL: GESTOR DE VENTAS EN ESPERA (PARKED SALES)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-parked-sales" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="ri-pause-circle-line me-2"></i>Ventas en Espera / Pausadas en Caja
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <p class="text-muted fs-13 mb-0">
                            Puedes pausar múltiples ventas o cotizaciones y retomarlas cuando el cliente esté listo para pagar, sin perder productos ni configuraciones.
                        </p>
                        <button type="button" class="btn btn-sm btn-warning fw-semibold" onclick="posCart.parkCurrentSalePrompt()">
                            <i class="ri-bookmark-line align-middle me-1"></i> Pausar Venta Actual
                        </button>
                    </div>

                    <div id="parked-sales-list-container" class="d-flex flex-column gap-2" style="max-height: 420px; overflow-y: auto;">
                        <!-- Renderizado dinámicamente por JS -->
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL: DETECTOR DE DISCREPANCIAS DE PRECIOS EN COTIZACIÓN
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-quote-price-discrepancies" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="ri-price-tag-3-line me-2"></i>Diferencia de Precios Detectada en Cotización
                    </h5>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning mb-3">
                        <i class="ri-alert-line me-1"></i> Algunos artículos en la cotización <strong id="disc-quote-folio"></strong> presentan variaciones respecto al precio vigente actual del catálogo de esta sucursal.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Artículo</th>
                                    <th class="text-end" style="width: 130px;">Precio Cotizado</th>
                                    <th class="text-end" style="width: 140px;">Precio Catálogo Actual</th>
                                    <th class="text-center" style="width: 110px;">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody id="disc-table-body"></tbody>
                        </table>
                    </div>
                    <p class="mt-3 mb-0 fs-12 text-muted">
                        Selecciona si deseas respetar los precios que fueron pactados en la cotización original o si prefieres actualizar el carrito a las tarifas vigentes del catálogo:
                    </p>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning fw-bold" id="btn-respect-quote-prices" onclick="posCart.applyQuoteOption('quoted')">
                            <i class="ri-check-line me-1"></i> Respetar Precios Cotizados
                        </button>
                        <button type="button" class="btn btn-primary fw-bold" id="btn-update-quote-prices" onclick="posCart.applyQuoteOption('catalog')">
                            <i class="ri-refresh-line me-1"></i> Actualizar a Precios de Catálogo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


@section('script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js" crossorigin="anonymous"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" crossorigin="anonymous"></script>
<script>
    const posApiRoutes = {
        search: "{{ route('api.pos.search') }}",
        calculate: "{{ route('api.pos.calculate') }}",
        checkout: "{{ route('api.pos.checkout') }}",
        saveQuote: "{{ route('api.pos.quotes.save') }}",
        compareQuote: "{{ url('api/pos/quotes') }}",
        validateCoupon: "{{ route('api.pos.coupons.validate') }}",
        customers: "{{ url('api/pos/customers') }}"
    };

    const csrfToken = "{{ csrf_token() }}";

    // Mapa de Clientes con Información de Entrega y Geolocalización
    const posCustomersMap = {
        @foreach($defaultCustomers as $cust)
            "{{ $cust->id }}": {
                id: {{ $cust->id }},
                name: @json($cust->full_name),
                recipient_name: @json($cust->full_name),
                phone: @json($cust->phone ?? ($cust->user?->phone ?? '')),
                street: @json($cust->street ?? ''),
                colonia: @json($cust->colonia ?? ''),
                city: @json($cust->city ?? ''),
                state: @json($cust->state ?? ''),
                zip: @json($cust->zip ?? ''),
                references: @json($cust->delivery_references ?? ''),
                instructions: @json($cust->delivery_instructions ?? ''),
                lat: {{ $cust->delivery_lat !== null ? (float)$cust->delivery_lat : 'null' }},
                lng: {{ $cust->delivery_lng !== null ? (float)$cust->delivery_lng : 'null' }},
                maps_url: @json($cust->delivery_maps_url ?? ''),
                legal_address: @json($cust->legal_address ?? '')
            },
        @endforeach
    };
    
    // Configuración global Axios
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // ══════════════════════════════════════════════════════════════
    // GESTOR DE TURNOS Y CORTES DE CAJA (CASH SHIFT MANAGER)
    // ══════════════════════════════════════════════════════════════
    const cashShiftManager = {
        hasActiveShift: false,
        currentShift: null,
        currentTotals: null,

        _openModal: null,
        _movementModal: null,
        _corteXModal: null,
        _corteZModal: null,

        init: async function() {
            try {
                const res = await axios.get('/cash-shifts/current');
                if (res.data.success) {
                    this.hasActiveShift = res.data.has_active_shift;
                    this.currentShift   = res.data.shift || null;
                    this.currentTotals  = res.data.totals || null;
                    this.updateUI();
                }
            } catch (e) {
                console.error("Error al verificar turno de caja:", e);
            }
        },

        updateUI: function() {
            const closedState = document.getElementById('cs-closed-state');
            const openState   = document.getElementById('cs-open-state');
            if (!closedState || !openState) return;

            if (this.hasActiveShift && this.currentShift) {
                closedState.classList.add('d-none');
                openState.classList.remove('d-none');
                openState.classList.add('d-flex');

                document.getElementById('cs-folio-display').innerText   = this.currentShift.folio;
                document.getElementById('cs-opening-display').innerText = parseFloat(this.currentShift.opening_amount || 0).toFixed(2);
                document.getElementById('cs-opened-display').innerText  = this.currentShift.opened_at;
            } else {
                closedState.classList.remove('d-none');
                openState.classList.add('d-none');
                openState.classList.remove('d-flex');
            }
        },

        openShiftModal: function() {
            document.getElementById('open-cs-amount').value = '';
            document.getElementById('open-cs-notes').value  = '';
            if (!this._openModal) this._openModal = new bootstrap.Modal(document.getElementById('modal-open-cash-shift'));
            this._openModal.show();
            setTimeout(() => document.getElementById('open-cs-amount').focus(), 400);
        },

        submitOpenShift: async function() {
            const amount = parseFloat(document.getElementById('open-cs-amount').value);
            const notes  = document.getElementById('open-cs-notes').value.trim();

            if (isNaN(amount) || amount < 0) {
                Swal.fire('Atención', 'Ingresa un monto válido para el fondo inicial de caja (ej. $500, $1,000).', 'warning');
                return;
            }

            const btn = document.getElementById('btn-submit-open-shift');
            btn.disabled = true; btn.innerText = 'Abriendo...';

            try {
                const res = await axios.post('/cash-shifts/open', {
                    opening_amount: amount,
                    opening_notes:  notes
                });

                if (res.data.success) {
                    this._openModal.hide();
                    await this.init();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Turno de Caja Abierto!',
                        text: res.data.message,
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', res.data.message || 'No se pudo abrir el turno.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Error al comunicarse con el servidor.', 'error');
            } finally {
                btn.disabled = false; btn.innerText = 'Iniciar Turno';
            }
        },

        toggleMovementType: function() {
            const isOut = document.getElementById('cs-mov-out')?.checked;
            const catWrapper = document.getElementById('cs-mov-category-wrapper');
            const hint = document.getElementById('cs-expense-hint');
            if (catWrapper) {
                catWrapper.style.display = isOut ? 'block' : 'none';
            }
            if (hint) {
                const isExpense = document.getElementById('cs-cat-expense')?.checked;
                hint.classList.toggle('d-none', !(isOut && isExpense));
            }
        },

        toggleExpenseHint: function() {
            const isOut = document.getElementById('cs-mov-out')?.checked;
            const isExpense = document.getElementById('cs-cat-expense')?.checked;
            const hint = document.getElementById('cs-expense-hint');
            if (hint) {
                hint.classList.toggle('d-none', !(isOut && isExpense));
            }
        },

        openMovementModal: function() {
            if (!this.hasActiveShift) {
                this.openShiftModal();
                return;
            }
            document.getElementById('cs-mov-amount').value  = '';
            document.getElementById('cs-mov-concept').value = '';
            document.getElementById('cs-mov-notes').value   = '';
            document.getElementById('cs-mov-in').checked    = true;
            if (document.getElementById('cs-cat-vault')) document.getElementById('cs-cat-vault').checked = true;
            this.toggleMovementType();

            if (!this._movementModal) this._movementModal = new bootstrap.Modal(document.getElementById('modal-cash-movement'));
            this._movementModal.show();
        },

        submitMovement: async function() {
            const type    = document.querySelector('input[name="cs-mov-type"]:checked')?.value || 'entrada';
            let category  = null;
            if (type === 'salida') {
                category = document.querySelector('input[name="cs-mov-category"]:checked')?.value || 'traslado_boveda';
            } else {
                category = 'fondo_extra';
            }
            const amount  = parseFloat(document.getElementById('cs-mov-amount').value);
            const concept = document.getElementById('cs-mov-concept').value.trim();
            const notes   = document.getElementById('cs-mov-notes').value.trim();

            if (isNaN(amount) || amount <= 0) {
                Swal.fire('Atención', 'Ingresa un monto válido mayor a 0.', 'warning');
                return;
            }
            if (!concept) {
                Swal.fire('Atención', 'Por favor ingresa el concepto o motivo del movimiento.', 'warning');
                return;
            }

            const btn = document.getElementById('btn-submit-movement');
            btn.disabled = true; btn.innerText = 'Guardando...';

            try {
                const res = await axios.post('/cash-shifts/movement', {
                    type:     type,
                    category: category,
                    amount:   amount,
                    concept:  concept,
                    notes:    notes
                });

                if (res.data.success) {
                    this._movementModal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Movimiento Registrado',
                        text: res.data.message,
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', res.data.message || 'No se pudo registrar el movimiento.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Error al registrar movimiento.', 'error');
            } finally {
                btn.disabled = false; btn.innerText = 'Registrar Movimiento';
            }
        },

        openCorteXModal: async function() {
            if (!this.hasActiveShift) {
                this.openShiftModal();
                return;
            }

            try {
                const res = await axios.get('/cash-shifts/corte-x');
                if (res.data.success) {
                    const s = res.data.shift;
                    const t = res.data.totals;

                    document.getElementById('cx-folio').innerText          = s.folio;
                    document.getElementById('cx-cashier').innerText        = s.cashier_name;
                    document.getElementById('cx-opened').innerText         = s.opened_at;

                    document.getElementById('cx-sales-cash').innerText     = `$${parseFloat(t.total_sales_cash).toFixed(2)}`;
                    document.getElementById('cx-so-cash').innerText        = `$${parseFloat(t.total_special_orders_cash).toFixed(2)}`;
                    document.getElementById('cx-sales-card').innerText     = `$${parseFloat(t.total_sales_card).toFixed(2)}`;
                    document.getElementById('cx-sales-transfer').innerText = `$${parseFloat(t.total_sales_transfer).toFixed(2)}`;
                    document.getElementById('cx-sales-total').innerText    = `$${parseFloat(t.total_sales_amount).toFixed(2)}`;
                    document.getElementById('cx-sales-count').innerText    = t.sales_count;

                    document.getElementById('cx-opening').innerText        = `$${parseFloat(t.opening_amount).toFixed(2)}`;
                    document.getElementById('cx-cash-all').innerText       = `$${parseFloat(t.total_cash_sales_all).toFixed(2)}`;
                    document.getElementById('cx-cash-in').innerText        = `$${parseFloat(t.total_cash_in).toFixed(2)}`;
                    document.getElementById('cx-cash-out').innerText       = `-$${parseFloat(t.total_cash_out).toFixed(2)}`;
                    const vaultEl = document.getElementById('cx-vault-cash');
                    if (vaultEl) vaultEl.innerText = `$${parseFloat(t.total_vault_cash || 0).toFixed(2)}`;
                    const expEl = document.getElementById('cx-expenses-cash');
                    if (expEl) expEl.innerText = `$${parseFloat(t.total_expenses_cash || 0).toFixed(2)}`;
                    document.getElementById('cx-expected-cash').innerText  = `$${parseFloat(t.expected_cash).toFixed(2)}`;

                    if (!this._corteXModal) this._corteXModal = new bootstrap.Modal(document.getElementById('modal-corte-x'));
                    this._corteXModal.show();
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar el Arqueo preliminar (Corte X).', 'error');
            }
        },

        printCurrentCorte: function(type = 'x', format = 'a4') {
            if (!this.currentShift) return;
            if (format === 'ticket') {
                window.open(`/cash-shifts/${this.currentShift.id}/print?type=${type}&format=ticket&autoprint=1`, '_blank', 'width=450,height=650');
            } else {
                window.open(`/cash-shifts/${this.currentShift.id}/print?type=${type}&format=a4&autoprint=1`, '_blank');
            }
        },

        openCorteZModal: async function() {
            if (!this.hasActiveShift) {
                this.openShiftModal();
                return;
            }

            try {
                const res = await axios.get('/cash-shifts/corte-x');
                if (res.data.success) {
                    this._corteZExpected = parseFloat(res.data.totals.expected_cash || 0);
                    document.getElementById('cz-expected-display').innerText = `$${this._corteZExpected.toFixed(2)}`;
                    document.getElementById('cz-actual-cash').value          = '';
                    document.getElementById('cz-notes').value                = '';
                    this.recalcDifference();

                    if (!this._corteZModal) this._corteZModal = new bootstrap.Modal(document.getElementById('modal-corte-z'));
                    this._corteZModal.show();
                    setTimeout(() => document.getElementById('cz-actual-cash').focus(), 400);
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudieron calcular los totales para el cierre.', 'error');
            }
        },

        recalcDifference: function() {
            const actual = parseFloat(document.getElementById('cz-actual-cash').value);
            const diffEl = document.getElementById('cz-diff-display');
            const statEl = document.getElementById('cz-diff-status');
            const boxEl  = document.getElementById('cz-diff-box');

            if (isNaN(actual)) {
                diffEl.innerText = '$0.00';
                diffEl.className = 'fs-3 fw-bold my-1 text-muted';
                statEl.innerText = 'Ingresa el efectivo contado para calcular la diferencia';
                statEl.className = 'small fw-semibold text-muted';
                boxEl.style.backgroundColor = '#f8f9fa';
                return;
            }

            const diff = actual - (this._corteZExpected || 0);
            if (Math.abs(diff) < 0.01) {
                diffEl.innerText = '$0.00';
                diffEl.className = 'fs-3 fw-bold my-1 text-success';
                statEl.innerText = '✓ Cuadrado Exacto (Sin diferencias)';
                statEl.className = 'small fw-semibold text-success';
                boxEl.style.backgroundColor = '#d1e7dd';
            } else if (diff > 0) {
                diffEl.innerText = `+$${diff.toFixed(2)}`;
                diffEl.className = 'fs-3 fw-bold my-1 text-info';
                statEl.innerText = '▲ Sobrante de Efectivo';
                statEl.className = 'small fw-semibold text-info';
                boxEl.style.backgroundColor = '#cff4fc';
            } else {
                diffEl.innerText = `-$${Math.abs(diff).toFixed(2)}`;
                diffEl.className = 'fs-3 fw-bold my-1 text-danger';
                statEl.innerText = '▼ Faltante de Efectivo';
                statEl.className = 'small fw-semibold text-danger';
                boxEl.style.backgroundColor = '#f8d7da';
            }
        },

        submitCorteZ: async function() {
            const actual = parseFloat(document.getElementById('cz-actual-cash').value);
            const notes  = document.getElementById('cz-notes').value.trim();

            if (isNaN(actual) || actual < 0) {
                Swal.fire('Atención', 'Por favor ingresa el monto de efectivo físico contado en el cajón.', 'warning');
                return;
            }

            const diff = actual - (this._corteZExpected || 0);
            let confirmHtml = `Efectivo esperado: <strong>$${this._corteZExpected.toFixed(2)}</strong><br>Efectivo contado: <strong>$${actual.toFixed(2)}</strong><br>`;
            if (Math.abs(diff) >= 0.01) {
                confirmHtml += `Diferencia: <strong class="${diff > 0 ? 'text-info' : 'text-danger'}">${diff > 0 ? '+$' : '-$'}${Math.abs(diff).toFixed(2)}</strong><br>`;
            }
            confirmHtml += `<br>¿Estás seguro de cerrar el turno de caja definitivamente?`;

            const confirm = await Swal.fire({
                title: 'Confirmar Corte Z',
                html: confirmHtml,
                icon: Math.abs(diff) >= 0.01 ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar caja',
                cancelButtonText: 'Revisar conteo',
                confirmButtonColor: '#dc3545'
            });

            if (!confirm.isConfirmed) return;

            const btn = document.getElementById('btn-submit-corte-z');
            btn.disabled = true; btn.innerText = 'Cerrando...';

            try {
                const res = await axios.post('/cash-shifts/corte-z', {
                    actual_cash:   actual,
                    closing_notes: notes
                });

                if (res.data.success) {
                    const closedShiftId = res.data.shift_id;
                    this._corteZModal.hide();
                    await this.init();

                    Swal.fire({
                        icon: 'success',
                        title: '¡Corte Z Realizado!',
                        html: `${res.data.message}<br><br>Se abrirá el comprobante oficial para impresión.`,
                        confirmButtonText: 'Imprimir Comprobante A4'
                    }).then(() => {
                        window.open(`/cash-shifts/${closedShiftId}/print?type=z&format=a4&autoprint=1`, '_blank');
                    });
                } else {
                    Swal.fire('Error', res.data.message || 'No se pudo cerrar la caja.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Error al procesar el Corte Z.', 'error');
            } finally {
                btn.disabled = false; btn.innerText = 'Cerrar Caja Definitivamente';
            }
        }
    };

    // Inicializar select2 con buscador para cliente venta normal y modal pedido especial
    $(document).ready(function() {
        cashShiftManager.init();

        $('#customer_select').select2({
            width: '100%'
        });
        $('#seller_select').select2({
            width: '100%',
            placeholder: '— Selecciona un vendedor comisionista —'
        });
        $('#so-customer-select').select2({
            dropdownParent: $('#modal-special-order'),
            width: '100%',
            placeholder: '— Selecciona un cliente —'
        });
    });

    const posCart = {
        items: [],
        payments: [],
        hasCustomPayments: false,
        lastCalcData: null,
        paymentMethods: @json($paymentMethods),
        paymentTerminals: @json($paymentTerminals),
        cartTotal: 0,
        deliveryData: null,
        appliedCoupon: null,
        loadedQuoteId: null,
        loadedQuoteFolio: null,
        parkedSales: [],
        _pendingQuoteData: null,
        _pendingQuoteLoadMode: 'replace',
        _skipInitialSelectRestore: false,
        _parkedSalesModal: null,
        _quoteLoadModeModal: null,
        _deliveryModal: null,
        _serviceModal: null,
        _deliveryMap: null,
        _deliveryMarker: null,
        _soDeliveryMap: null,
        _soDeliveryMarker: null,
        storeDefaultLat: {{ $currentStore && $currentStore->lat ? $currentStore->lat : 19.2433880 }},
        storeDefaultLng: {{ $currentStore && $currentStore->lng ? $currentStore->lng : -103.7250940 }},
        msiCampaignActive: {{ \App\Models\Setting::get('msi_campaign_active', false, $activeStoreId) ? 'true' : 'false' }},
        taxIvaRate: {{ $taxIvaRate ?? 16 }},
        _calcId: 0,
        
        init: function() {
            const currentStoreId = "{{ $activeStoreId }}";
            const lastStoreId = localStorage.getItem('pos_active_store_id');
            
            if (lastStoreId && lastStoreId !== currentStoreId) {
                // La sucursal cambió, vaciar carrito y pagos
                this.items = [];
                this.payments = [];
                this.hasCustomPayments = false;
                this.deliveryData = null;
                this.loadedQuoteId = null;
                this.loadedQuoteFolio = null;
                localStorage.removeItem('pos_delivery_data');
                localStorage.removeItem('pos_cart_payments');
                localStorage.removeItem('pos_has_custom_payments');
                localStorage.removeItem('pos_customer_id');
                localStorage.removeItem('pos_seller_id');
                localStorage.removeItem('pos_loaded_quote');
                this.saveToStorage();
            }
            localStorage.setItem('pos_active_store_id', currentStoreId);

            this.currentStoreId = currentStoreId;
            this.bindEvents();
            this.loadFromStorage();
            this.render();
            if (this.items.length > 0) {
                this.recalculate();
            }
            this.checkStoreSelection();
            this.initOtpModal();
            this.initPaymentsModal();
            this.updateDeliveryUI();
            this.updateLoadedQuoteUI();
            this.updateParkedSalesUI();

            // Cargar cotización si viene indicada en los parámetros de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const quoteId = urlParams.get('quote_id');
            if (quoteId) {
                this.loadQuote(quoteId);
            }
        },

        checkStoreSelection: function() {
            const storeSelect = document.getElementById('store_select');
            const searchInput = document.getElementById('pos-search');
            if (!storeSelect || !storeSelect.value || storeSelect.value === 'global') {
                searchInput.disabled = true;
                searchInput.placeholder = "Por favor selecciona una sucursal en el selector superior para comenzar...";
                document.getElementById('search-results').classList.add('d-none');
            } else {
                searchInput.disabled = false;
                searchInput.placeholder = "Escanea código de barras o busca por nombre/SKU...";
            }
        },

        initOtpModal: function() {
            const posOtpInputs = document.querySelectorAll(".pos-otp-input");
            const posFullOtpInput = document.getElementById("pos-full-otp-code");
            const posOtpModalEl = document.getElementById('posOtpVerificationModal');
            
            if (posOtpModalEl) {
                this.posOtpModal = new bootstrap.Modal(posOtpModalEl, {
                    backdrop: 'static'
                });

                // Lógica de Inputs del OTP
                posOtpInputs.forEach((input, index) => {
                    input.addEventListener("input", (e) => {
                        if (input.value.length === 1 && index < posOtpInputs.length - 1) {
                            posOtpInputs[index + 1].focus();
                        }
                        let code = "";
                        posOtpInputs.forEach(inp => code += inp.value);
                        posFullOtpInput.value = code;
                    });

                    input.addEventListener("keydown", (e) => {
                        if (e.key === "Backspace" && input.value === "" && index > 0) {
                            posOtpInputs[index - 1].focus();
                        }
                    });
                    
                    input.addEventListener("paste", (e) => {
                        e.preventDefault();
                        const pastedData = e.clipboardData.getData('text').slice(0, 6).replace(/[^0-9]/g, '');
                        if (pastedData) {
                            [...pastedData].forEach((char, i) => {
                                if(posOtpInputs[i]) {
                                    posOtpInputs[i].value = char;
                                }
                            });
                            if(pastedData.length < 6) {
                                posOtpInputs[pastedData.length].focus();
                            } else {
                                posOtpInputs[5].focus();
                                posOtpInputs[5].blur();
                            }
                            let code = "";
                            posOtpInputs.forEach(inp => code += inp.value);
                            posFullOtpInput.value = code;
                        }
                    });
                });

                // Acción Verificar
                document.getElementById('btnPosVerifyOtp').addEventListener('click', async () => {
                    const otpCode = posFullOtpInput.value;
                    if (otpCode.length < 6) {
                        document.getElementById('pos-otp-error-msg').innerText = 'Debes ingresar los 6 dígitos.';
                        document.getElementById('pos-otp-error-msg').classList.remove('d-none');
                        return;
                    }

                    try {
                        const response = await fetch('/user/profile/validate-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ otp_code: otpCode })
                        });
                        const data = await response.json();

                        if (data.success) {
                            this.posOtpModal.hide();
                            this.isOtpAuthorized = true;
                            
                            // Guardar el precio definitivo ya que fue autorizado
                            const index = this.otpIndexTarget;
                            const item = this.items[index];
                            if (item) {
                                item.price_type = 'personalizado';
                                item.adjusted_price = this.tempCustomPrice;
                                item.price = this.tempCustomPrice;
                                this.saveToStorage();
                                this.recalculate();
                            }
                        } else {
                            document.getElementById('pos-otp-error-msg').innerText = data.message || 'El código ingresado es incorrecto.';
                            document.getElementById('pos-otp-error-msg').classList.remove('d-none');
                        }
                    } catch(err) {
                        document.getElementById('pos-otp-error-msg').innerText = 'Error al conectar con el servidor.';
                        document.getElementById('pos-otp-error-msg').classList.remove('d-none');
                    }
                });

                // Cerrar modal
                document.getElementById('btnPosCloseOtpModal').addEventListener('click', () => {
                    this.render(); // Revertir select en la UI
                });
                
                posOtpModalEl.addEventListener('hidden.bs.modal', () => {
                    if (!this.isOtpAuthorized) {
                        this.render();
                    }
                });
            }
        },

        initPaymentsModal: function() {
            const paymentsModalEl = document.getElementById('posPaymentsModal');
            if (paymentsModalEl) {
                this.paymentsModal = new bootstrap.Modal(paymentsModalEl);
            }

            document.getElementById('btn-add-payment-row').addEventListener('click', () => {
                this.addPaymentRow();
            });

            document.getElementById('btn-confirm-payments').addEventListener('click', () => {
                this.confirmPayments();
            });

            // Listeners dinámicos en la tabla
            const tbody = document.getElementById('payments-tbody');
            tbody.addEventListener('change', (e) => {
                if (e.target.classList.contains('payment-method-select')) {
                    const rowId = e.target.closest('tr').id;
                    this.onMethodChange(rowId);
                }
                if (e.target.classList.contains('payment-terminal-select')) {
                    const rowId = e.target.closest('tr').id;
                    this.onTerminalChange(rowId, e.target);
                }
                this.calculatePaymentsTotals();
            });

            tbody.addEventListener('input', (e) => {
                if (e.target.classList.contains('payment-amount-input')) {
                    this.calculatePaymentsTotals();
                }
            });

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-remove-payment-row');
                if (btn) {
                    const row = btn.closest('tr');
                    row.remove();
                    this.calculatePaymentsTotals();
                }
            });
        },

        openPaymentsModal: function() {
            if (this.items.length === 0) {
                Swal.fire('Atención', 'Agrega productos al carrito antes de configurar los pagos.', 'warning');
                return;
            }

            const tbody = document.getElementById('payments-tbody');
            tbody.innerHTML = '';

            // Si no hay pagos configurados, inicializar con el total neto del carrito (descontando cupón) en Efectivo
            const netCartTotal = Math.max(0, this.cartTotal - this.getCouponDiscountAmount());
            if (this.payments.length === 0) {
                // Buscar id de Efectivo
                const cashMethod = this.paymentMethods.find(pm => pm.name.toLowerCase().includes('efectivo'));
                const defaultMethodId = cashMethod ? cashMethod.id : (this.paymentMethods[0]?.id || '');
                
                this.addPaymentRow(defaultMethodId, '', netCartTotal.toFixed(2), 0, '', '');
            } else {
                this.payments.forEach(p => {
                    this.addPaymentRow(p.payment_method_id, p.payment_terminal_id, p.amount, p.msi_applied, p.reference, p.notes);
                });
            }

            this.paymentsModal.show();
            this.calculatePaymentsTotals();
        },

        addPaymentRow: function(methodId = '', terminalId = '', amount = '', msiApplied = 0, reference = '', notes = '') {
            const tbody = document.getElementById('payments-tbody');
            const rowId = 'payment-row-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            
            // Construir select de métodos
            let methodOptions = '<option value="">Seleccione método</option>';
            this.paymentMethods.forEach(pm => {
                methodOptions += `<option value="${pm.id}" ${pm.id == methodId ? 'selected' : ''}>${pm.name}</option>`;
            });

            const tr = document.createElement('tr');
            tr.id = rowId;
            tr.innerHTML = `
                <td>
                    <select class="form-select form-select-sm payment-method-select" required>
                        ${methodOptions}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm payment-terminal-select" disabled>
                        <option value="">N/A</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm payment-amount-input text-end" step="0.01" min="0.01" value="${amount}" placeholder="0.00" required style="width: 110px;">
                </td>
                <td>
                    <select class="form-select form-select-sm payment-msi-select" disabled>
                        <option value="0">Contado</option>
                    </select>
                </td>
                <td class="text-end fw-medium text-warning payment-interest-cell">$0.00</td>
                <td class="text-end fw-medium text-info payment-commission-cell">$0.00</td>
                <td class="text-end fw-bold text-primary payment-row-total-cell">$0.00</td>
                <td>
                    <input type="text" class="form-control form-control-sm payment-reference-input" value="${reference || ''}" placeholder="Ref. / Notas..." style="width: 130px;">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-ghost-danger btn-icon btn-remove-payment-row">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);

            // Cargar datos iniciales de terminal y mensualidades si se pasaron argumentos
            this.onMethodChange(rowId, terminalId, msiApplied);
        },

        onMethodChange: function(rowId, selectedTerminalId = '', selectedMsi = 0) {
            const row = document.getElementById(rowId);
            if (!row) return;

            const methodSelect = row.querySelector('.payment-method-select');
            const terminalSelect = row.querySelector('.payment-terminal-select');
            const msiSelect = row.querySelector('.payment-msi-select');

            const methodId = parseInt(methodSelect.value);
            const method = this.paymentMethods.find(pm => pm.id === methodId);

            if (!method) {
                terminalSelect.innerHTML = '<option value="">N/A</option>';
                terminalSelect.disabled = true;
                msiSelect.innerHTML = '<option value="0">Contado</option>';
                msiSelect.disabled = true;
                return;
            }

            // 1. Configurar mensualidades
            if (method.accepts_financing && method.financing_rates) {
                let msiOptions = '<option value="0">Contado</option>';
                const rates = typeof method.financing_rates === 'string' ? JSON.parse(method.financing_rates) : method.financing_rates;
                
                if (Array.isArray(rates)) {
                    rates.forEach(r => {
                        const label = `${r.months} meses (tasa: ${r.rate}%)`;
                        msiOptions += `<option value="${r.months}" ${r.months == selectedMsi ? 'selected' : ''}>${label}</option>`;
                    });
                }
                msiSelect.innerHTML = msiOptions;
                msiSelect.disabled = false;
            } else {
                msiSelect.innerHTML = '<option value="0">Contado</option>';
                msiSelect.disabled = true;
            }

            // 2. Configurar terminales bancarias
            const isSystemMethod = method.is_system || method.name.toLowerCase().includes('efectivo') || method.name.toLowerCase().includes('transferencia') || method.name.toLowerCase().includes('cheque');
            
            if (!isSystemMethod) {
                let terminalOptions = '<option value="">Seleccione terminal</option>';
                
                this.paymentTerminals.forEach(term => {
                    const isDefault = term.payment_methods.some(m => m.id === method.id);
                    const suffix = isDefault ? ' (Asignada)' : ' (No asignada)';
                    terminalOptions += `<option value="${term.id}" ${term.id == selectedTerminalId ? 'selected' : ''}>${term.name}${suffix}</option>`;
                });
                
                terminalSelect.innerHTML = terminalOptions;
                terminalSelect.disabled = false;
            } else {
                terminalSelect.innerHTML = '<option value="">N/A</option>';
                terminalSelect.disabled = true;
            }
        },

        onTerminalChange: function(rowId, selectElement) {
            const row = document.getElementById(rowId);
            if (!row) return;

            const methodSelect = row.querySelector('.payment-method-select');
            const methodId = parseInt(methodSelect.value);
            const method = this.paymentMethods.find(pm => pm.id === methodId);

            const terminalId = parseInt(selectElement.value);
            const terminal = this.paymentTerminals.find(t => t.id === terminalId);

            if (terminal && method) {
                const isAssigned = terminal.payment_methods.some(pm => pm.id === method.id);
                if (!isAssigned) {
                    Swal.fire({
                        title: 'Advertencia',
                        text: `Esta terminal (${terminal.name}) no tiene asignado el método (${method.name}) por default. ¿Deseas continuar bajo tu propia responsabilidad?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Cancelar',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            selectElement.value = '';
                            this.calculatePaymentsTotals();
                        }
                    });
                }
            }
        },

        calculatePaymentsTotals: function() {
            let totalInterest = 0;
            let totalCommissions = 0;
            let totalFinalSum = 0;

            const rows = document.querySelectorAll('#payments-tbody tr');
            rows.forEach(row => {
                const methodSelect = row.querySelector('.payment-method-select');
                const terminalSelect = row.querySelector('.payment-terminal-select');
                const amountInput = row.querySelector('.payment-amount-input');
                const msiSelect = row.querySelector('.payment-msi-select');

                const interestCell = row.querySelector('.payment-interest-cell');
                const commissionCell = row.querySelector('.payment-commission-cell');
                const rowTotalCell = row.querySelector('.payment-row-total-cell');

                const methodId = parseInt(methodSelect.value);
                const method = this.paymentMethods.find(pm => pm.id === methodId);
                const baseAmount = parseFloat(amountInput.value) || 0;

                let interestVal = 0;
                let commissionVal = 0;
                let rowTotal = baseAmount;

                if (method && baseAmount > 0) {
                    const applyIva = method.apply_iva_to_rates !== undefined ? method.apply_iva_to_rates : true;
                    const ivaMult = applyIva ? (1 + (this.taxIvaRate / 100)) : 1.0;

                    // 1. Calcular sobretasa de financiamiento (con IVA)
                    const msi = parseInt(msiSelect.value) || 0;
                    let infoInterest = 0;
                    if (msi > 0 && method.accepts_financing && method.financing_rates) {
                        const rates = typeof method.financing_rates === 'string' ? JSON.parse(method.financing_rates) : method.financing_rates;
                        const rateObj = rates ? rates.find(r => r.months == msi) : null;
                        const rate = rateObj ? parseFloat(rateObj.rate) : 0;
                        infoInterest = (baseAmount * (rate / 100)) * ivaMult;
                    }

                    if (infoInterest > 0) {
                        if (method.transfer_msi_cost_to_client) {
                            // Se traslada al cliente
                            rowTotal += infoInterest;
                            totalInterest += infoInterest;
                            interestCell.innerHTML = `<strong class="text-warning">$${infoInterest.toFixed(2)}</strong> <small class="text-muted d-block">(Trasladado + IVA)</small>`;
                        } else {
                            // Informativo para el cliente
                            const monthlyEst = (baseAmount + infoInterest) / msi;
                            interestCell.innerHTML = `<span class="badge bg-light text-muted" title="Cobrado por el banco al cliente">Informativo: $${infoInterest.toFixed(2)} ($${monthlyEst.toFixed(2)}/mes)</span>`;
                        }
                    } else {
                        interestCell.innerText = '$0.00';
                    }

                    // 2. Calcular comisiones de terminal (con IVA del 16% si aplica)
                    const terminalId = parseInt(terminalSelect.value);
                    const terminal = this.paymentTerminals.find(t => t.id === terminalId);

                    if (terminal) {
                        const fixed = parseFloat(terminal.commission_fixed) || 0;
                        const percent = parseFloat(terminal.commission_percentage) || 0;
                        
                        const rawComm = fixed + (rowTotal * (percent / 100));
                        commissionVal = rawComm * ivaMult;

                        if (terminal.charge_client) {
                            rowTotal += commissionVal;
                            totalCommissions += commissionVal;
                            commissionCell.innerText = `$${commissionVal.toFixed(2)}`;
                        } else {
                            commissionCell.innerText = `$0.00 (Empresa: $${commissionVal.toFixed(2)})`;
                        }
                    } else {
                        commissionCell.innerText = '$0.00';
                    }
                } else {
                    interestCell.innerText = '$0.00';
                    commissionCell.innerText = '$0.00';
                }

                rowTotalCell.innerText = `$${rowTotal.toFixed(2)}`;
                totalFinalSum += rowTotal;
            });

            const couponDiscount = this.getCouponDiscountAmount();
            const netCartTotal = Math.max(0, this.cartTotal - couponDiscount);
            const grandTotalVenta = netCartTotal + totalInterest + totalCommissions;

            document.getElementById('modal-subtotal-items').innerText = couponDiscount > 0
                ? `$${netCartTotal.toFixed(2)} (Cupón: -$${couponDiscount.toFixed(2)})`
                : `$${this.cartTotal.toFixed(2)}`;
            document.getElementById('modal-total-interest').innerText = `$${totalInterest.toFixed(2)}`;
            document.getElementById('modal-total-commissions').innerText = `$${totalCommissions.toFixed(2)}`;
            document.getElementById('modal-grand-total').innerText = `$${grandTotalVenta.toFixed(2)}`;

            const badge = document.getElementById('modal-remaining-badge');
            
            if (Math.abs(totalFinalSum - grandTotalVenta) < 0.02) {
                document.getElementById('modal-total-payments-added').innerText = `$${totalFinalSum.toFixed(2)}`;
                badge.innerText = 'Cobro Completado (Liquidado)';
                badge.className = 'badge bg-success';
            } else if (totalFinalSum < grandTotalVenta) {
                const diff = grandTotalVenta - totalFinalSum;
                document.getElementById('modal-total-payments-added').innerText = `$${totalFinalSum.toFixed(2)}`;
                badge.innerText = `Falta por abonar: $${diff.toFixed(2)} (Se guardará como Apartado)`;
                badge.className = 'badge bg-warning';
            } else {
                const excess = totalFinalSum - grandTotalVenta;
                document.getElementById('modal-total-payments-added').innerText = `$${totalFinalSum.toFixed(2)}`;
                badge.innerText = `Exceso de pago: +$${excess.toFixed(2)}`;
                badge.className = 'badge bg-danger';
            }
        },

        confirmPayments: function() {
            const rows = document.querySelectorAll('#payments-tbody tr');
            const newPayments = [];
            let totalAbonado = 0;
            let hasError = false;

            rows.forEach(row => {
                const methodSelect = row.querySelector('.payment-method-select');
                const terminalSelect = row.querySelector('.payment-terminal-select');
                const amountInput = row.querySelector('.payment-amount-input');
                const msiSelect = row.querySelector('.payment-msi-select');
                const referenceInput = row.querySelector('.payment-reference-input');

                const methodId = parseInt(methodSelect.value);
                const amount = parseFloat(amountInput.value) || 0;

                if (!methodId || amount <= 0) {
                    hasError = true;
                    return;
                }

                totalAbonado += amount;

                newPayments.push({
                    payment_method_id: methodId,
                    payment_terminal_id: parseInt(terminalSelect.value) || null,
                    amount: amount,
                    msi_applied: parseInt(msiSelect.value) || 0,
                    reference: referenceInput.value.trim()
                });
            });

            if (hasError || newPayments.length === 0) {
                Swal.fire('Datos incompletos', 'Por favor verifica que todos los pagos tengan un método y un monto mayor a 0.', 'warning');
                return;
            }

            this.payments = newPayments;
            this.hasCustomPayments = true;
            this.saveToStorage();
            this.paymentsModal.hide();
            this.updateTotalsWithPayments();
        },

        resetPaymentsToDefault: function() {
            this.hasCustomPayments = false;
            this.payments = [];
            localStorage.removeItem('pos_cart_payments');
            localStorage.removeItem('pos_has_custom_payments');
            this.saveToStorage();
            if (this.lastCalcData) {
                this.updateTotalsUI(this.lastCalcData);
            } else {
                this.recalculate();
            }
        },

        updateTotalsWithPayments: function() {
            let totalInterest = 0;
            let totalCommissions = 0;
            let totalAmountPaid = 0;

            this.payments.forEach(p => {
                const pm = this.paymentMethods.find(m => m.id === p.payment_method_id);
                if (!pm) return;

                const applyIva = pm.apply_iva_to_rates !== undefined ? pm.apply_iva_to_rates : true;
                const ivaMult = applyIva ? (1 + (this.taxIvaRate / 100)) : 1.0;

                let rowInterest = 0;
                let rowCommission = 0;

                if (p.msi_applied > 0 && pm.transfer_msi_cost_to_client && pm.accepts_financing && pm.financing_rates) {
                    const rates = typeof pm.financing_rates === 'string' ? JSON.parse(pm.financing_rates) : pm.financing_rates;
                    const rateObj = rates ? rates.find(r => r.months == p.msi_applied) : null;
                    const rate = rateObj ? parseFloat(rateObj.rate) : 0;
                    rowInterest = (p.amount * (rate / 100)) * ivaMult;
                }

                totalInterest += rowInterest;
                const basePlusInt = p.amount + rowInterest;

                if (p.payment_terminal_id) {
                    const terminal = this.paymentTerminals.find(t => t.id === p.payment_terminal_id);
                    if (terminal) {
                        const fixed = parseFloat(terminal.commission_fixed) || 0;
                        const percent = parseFloat(terminal.commission_percentage) || 0;
                        
                        const rawComm = fixed + (basePlusInt * (percent / 100));
                        rowCommission = rawComm * ivaMult;

                        if (terminal.charge_client) {
                            totalCommissions += rowCommission;
                            totalAmountPaid += basePlusInt + rowCommission;
                        } else {
                            totalAmountPaid += basePlusInt;
                        }
                    } else {
                        totalAmountPaid += basePlusInt;
                    }
                } else {
                    totalAmountPaid += basePlusInt;
                }
            });

            // Descuento de cupón si está aplicado
            let couponDiscount = 0;
            if (this.appliedCoupon) {
                if (this.appliedCoupon.discount_type === 'percentage') {
                    couponDiscount = (this.cartTotal * (parseFloat(this.appliedCoupon.discount_value) / 100));
                    if (this.appliedCoupon.max_discount_amount && couponDiscount > parseFloat(this.appliedCoupon.max_discount_amount)) {
                        couponDiscount = parseFloat(this.appliedCoupon.max_discount_amount);
                    }
                } else {
                    couponDiscount = Math.min(this.cartTotal, parseFloat(this.appliedCoupon.discount_value));
                }
            }
            const netCartTotal = Math.max(0, this.cartTotal - couponDiscount);

            const grandTotal = netCartTotal + totalInterest + totalCommissions;
            const summaryTotalEl = document.getElementById('summary-total');
            if (summaryTotalEl) {
                summaryTotalEl.innerText = `$${grandTotal.toFixed(2)}`;
            }

            const couponRow = document.getElementById('summary-coupon-row');
            const summaryCoupon = document.getElementById('summary-coupon');
            if (couponRow && summaryCoupon) {
                if (couponDiscount > 0) {
                    summaryCoupon.innerText = `-$${couponDiscount.toFixed(2)}`;
                    couponRow.classList.remove('d-none');
                } else {
                    couponRow.classList.add('d-none');
                }
            }

            const interestRow = document.getElementById('summary-interest-row');
            if (interestRow) {
                if (totalInterest > 0) {
                    document.getElementById('summary-interest').innerText = `$${totalInterest.toFixed(2)}`;
                    interestRow.classList.remove('d-none');
                } else {
                    interestRow.classList.add('d-none');
                }
            }

            const commissionRow = document.getElementById('summary-commission-row');
            if (commissionRow) {
                if (totalCommissions > 0) {
                    document.getElementById('summary-commission').innerText = `$${totalCommissions.toFixed(2)}`;
                    commissionRow.classList.remove('d-none');
                } else {
                    commissionRow.classList.add('d-none');
                }
            }

            // Indicadores de Abono y Saldo Restante
            const summaryPaid = document.getElementById('summary-paid');
            if (summaryPaid) {
                summaryPaid.innerText = `$${totalAmountPaid.toFixed(2)}`;
            }

            const balanceRow = document.getElementById('summary-balance-row');
            const summaryBalance = document.getElementById('summary-balance');
            const changeRow = document.getElementById('summary-change-row');
            const summaryChange = document.getElementById('summary-change');
            const saleStatus = document.getElementById('sidebar-sale-status');
            const layawayStatus = document.getElementById('sidebar-layaway-status');

            const diff = grandTotal - totalAmountPaid;

            if (this.payments.length > 0 && diff > 0.01) {
                // Apartado / Pago parcial
                if (balanceRow && summaryBalance) {
                    summaryBalance.innerText = `$${diff.toFixed(2)}`;
                    balanceRow.classList.remove('d-none');
                }
                if (changeRow) changeRow.classList.add('d-none');

                if (layawayStatus) {
                    document.getElementById('sidebar-layaway-balance').innerText = `$${diff.toFixed(2)}`;
                    layawayStatus.classList.remove('d-none');
                }
                if (saleStatus) {
                    saleStatus.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning w-100 py-2 fs-12 mb-3 text-wrap';
                    saleStatus.innerHTML = `<i class="ri-time-line me-1"></i> Apartado (Restante: $${diff.toFixed(2)})`;
                    saleStatus.classList.remove('d-none');
                }
            } else if (this.payments.length > 0 && diff < -0.01) {
                // Sobrepago (cambio al cliente)
                const excess = Math.abs(diff);
                if (balanceRow) balanceRow.classList.add('d-none');
                if (changeRow && summaryChange) {
                    summaryChange.innerText = `$${excess.toFixed(2)}`;
                    changeRow.classList.remove('d-none');
                }
                if (layawayStatus) layawayStatus.classList.add('d-none');
                if (saleStatus) {
                    saleStatus.className = 'badge bg-success-subtle text-success border border-success w-100 py-2 fs-12 mb-3';
                    saleStatus.innerHTML = `<i class="ri-checkbox-circle-line me-1"></i> Venta Liquidada (Cambio: $${excess.toFixed(2)})`;
                    saleStatus.classList.remove('d-none');
                }
            } else {
                // Liquidado exacto
                if (balanceRow && summaryBalance) {
                    summaryBalance.innerText = '$0.00';
                    balanceRow.classList.add('d-none');
                }
                if (changeRow) changeRow.classList.add('d-none');
                if (layawayStatus) layawayStatus.classList.add('d-none');
                if (saleStatus) {
                    if (this.cartTotal > 0) {
                        saleStatus.className = 'badge bg-success-subtle text-success border border-success w-100 py-2 fs-12 mb-3';
                        saleStatus.innerHTML = '<i class="ri-checkbox-circle-line me-1"></i> Venta Liquidada';
                        saleStatus.classList.remove('d-none');
                    } else {
                        saleStatus.classList.add('d-none');
                    }
                }
            }

            this.renderPaymentsSummary();
        },

        renderPaymentsSummary: function() {
            const summaryDiv = document.getElementById('sidebar-payments-summary');
            if (!this.payments || this.payments.length === 0 || !this.hasCustomPayments) {
                summaryDiv.innerHTML = '<div class="text-muted text-center py-2">Sin pagos personalizados (se liquidará en Efectivo al cobrar).</div>';
                return;
            }

            let html = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted fs-11 fw-semibold text-uppercase">Pagos Configurados:</span>
                    <button type="button" class="btn btn-link text-danger p-0 text-decoration-none fs-11" onclick="posCart.resetPaymentsToDefault()" title="Restablecer a pago único en Efectivo">
                        <i class="ri-refresh-line"></i> Restablecer
                    </button>
                </div>
                <ul class="list-group list-group-flush mb-0">
            `;
            this.payments.forEach((p, idx) => {
                const pm = this.paymentMethods.find(m => m.id === p.payment_method_id);
                const terminal = p.payment_terminal_id ? this.paymentTerminals.find(t => t.id === p.payment_terminal_id) : null;
                const msiText = p.msi_applied > 0 ? ` (${p.msi_applied}m)` : '';
                const termText = terminal ? ` [${terminal.name}]` : '';
                
                html += `
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1 border-0">
                        <span class="text-truncate me-2" style="max-width: 140px;" title="${pm ? pm.name : 'N/A'}${msiText}${termText}">
                            <i class="ri-bank-card-fill text-primary me-1"></i>${pm ? pm.name : 'N/A'}${msiText}${termText}
                        </span>
                        <strong class="text-dark">$${p.amount.toFixed(2)}</strong>
                    </li>
                `;
            });
            html += '</ul>';
            summaryDiv.innerHTML = html;
        },

        getCouponDiscountAmount: function() {
            if (!this.appliedCoupon || this.cartTotal <= 0) return 0;
            const val = parseFloat(this.appliedCoupon.discount_value) || 0;
            if (this.appliedCoupon.discount_type === 'percentage') {
                let disc = Math.round((this.cartTotal * (val / 100)) * 100) / 100;
                const maxAmt = parseFloat(this.appliedCoupon.max_discount_amount) || 0;
                if (maxAmt > 0 && disc > maxAmt) {
                    disc = maxAmt;
                }
                return Math.max(0, Math.min(this.cartTotal, disc));
            }
            return Math.max(0, Math.min(this.cartTotal, val));
        },

        syncDefaultPaymentWithNetTotal: function() {
            if (this.hasCustomPayments) return;
            const netCartTotal = Math.max(0, this.cartTotal - this.getCouponDiscountAmount());
            if (netCartTotal > 0) {
                const cashMethod = this.paymentMethods.find(pm => pm.name.toLowerCase().includes('efectivo'));
                const defaultMethodId = cashMethod ? cashMethod.id : (this.paymentMethods[0]?.id || '');
                this.payments = [{
                    payment_method_id: defaultMethodId,
                    payment_terminal_id: null,
                    amount: netCartTotal,
                    msi_applied: 0,
                    reference: ''
                }];
            } else {
                this.payments = [];
            }
        },

        revalidateAppliedCoupon: async function() {
            if (!this.appliedCoupon || this.items.length === 0 || this.cartTotal <= 0) return;

            try {
                const payload = {
                    code: this.appliedCoupon.code,
                    subtotal: this.cartTotal,
                    cart_total: this.cartTotal,
                    customer_id: $('#customer_select').val() || document.getElementById('customer_select')?.value || null,
                    seller_id: $('#seller_select').val() || document.getElementById('seller_select')?.value || null,
                    store_id: document.getElementById('store_select')?.value || null
                };

                const res = await axios.post(posApiRoutes.validateCoupon, payload);
                if (res.data && (res.data.success || res.data.valid) && res.data.coupon) {
                    const discountAmt = parseFloat(res.data.discount_amount ?? res.data.coupon.discount_amount ?? 0);
                    this.appliedCoupon = {
                        id: res.data.coupon.id,
                        code: res.data.coupon.code,
                        name: res.data.coupon.name,
                        discount_type: res.data.coupon.discount_type,
                        discount_value: parseFloat(res.data.coupon.discount_value) || 0,
                        max_discount_amount: res.data.coupon.max_discount_amount ? parseFloat(res.data.coupon.max_discount_amount) : null,
                        min_purchase: parseFloat(res.data.coupon.min_purchase) || 0,
                        discount_amount: discountAmt,
                        description: res.data.coupon.description || res.data.coupon.name || res.data.coupon.code,
                        seller_id: res.data.coupon.seller_id || null,
                        seller_name: res.data.coupon.seller_name || null,
                        customer_id: res.data.coupon.customer_id || null,
                        store_id: res.data.coupon.store_id || null,
                        commission_info: res.data.commission_split || res.data.split_preview || null
                    };
                    this.saveToStorage();
                    this.syncDefaultPaymentWithNetTotal();
                    this.updateCouponUI();
                    this.updateTotalsWithPayments();
                } else {
                    const reason = res.data?.message || 'El cupón ya no cumple las condiciones de la venta.';
                    this.removeCoupon();
                    Swal.fire('Cupón Retirado', reason, 'warning');
                }
            } catch (err) {
                const reason = err.response?.data?.message || 'El cupón aplicado dejó de cumplir con las condiciones de la venta.';
                this.removeCoupon();
                Swal.fire('Cupón Retirado', reason, 'warning');
            }
        },

        applyCoupon: async function() {
            const input = document.getElementById('pos-coupon-code');
            const code = input ? input.value.trim().toUpperCase() : '';
            if (!code) {
                Swal.fire('Atención', 'Por favor ingresa un código de cupón.', 'warning');
                return;
            }

            if (this.items.length === 0) {
                Swal.fire('Atención', 'Agrega productos al carrito antes de aplicar un cupón.', 'warning');
                return;
            }

            const btn = document.getElementById('btn-apply-coupon');
            if (btn) btn.disabled = true;

            try {
                const payload = {
                    code: code,
                    subtotal: this.cartTotal,
                    cart_total: this.cartTotal,
                    customer_id: $('#customer_select').val() || document.getElementById('customer_select')?.value || null,
                    seller_id: $('#seller_select').val() || document.getElementById('seller_select')?.value || null,
                    store_id: document.getElementById('store_select')?.value || null
                };

                const res = await axios.post(posApiRoutes.validateCoupon, payload);
                if (res.data && (res.data.success || res.data.valid) && res.data.coupon) {
                    const discountAmt = parseFloat(res.data.discount_amount ?? res.data.coupon.discount_amount ?? 0);
                    this.appliedCoupon = {
                        id: res.data.coupon.id,
                        code: res.data.coupon.code,
                        name: res.data.coupon.name,
                        discount_type: res.data.coupon.discount_type,
                        discount_value: parseFloat(res.data.coupon.discount_value) || 0,
                        max_discount_amount: res.data.coupon.max_discount_amount ? parseFloat(res.data.coupon.max_discount_amount) : null,
                        min_purchase: parseFloat(res.data.coupon.min_purchase) || 0,
                        discount_amount: discountAmt,
                        description: res.data.coupon.description || res.data.coupon.name || res.data.coupon.code,
                        seller_id: res.data.coupon.seller_id || null,
                        seller_name: res.data.coupon.seller_name || null,
                        customer_id: res.data.coupon.customer_id || null,
                        store_id: res.data.coupon.store_id || null,
                        commission_info: res.data.commission_split || res.data.split_preview || null
                    };
                    this.saveToStorage();
                    this.syncDefaultPaymentWithNetTotal();
                    this.updateCouponUI();
                    this.updateTotalsWithPayments();
                    
                    let splitHtml = '';
                    if (this.appliedCoupon.commission_info && this.appliedCoupon.commission_info.is_split) {
                        splitHtml = `<div class="mt-2 small text-info"><i class="ri-user-shared-line me-1"></i>${this.appliedCoupon.commission_info.message || ''}</div>`;
                    }

                    Swal.fire({
                        title: '¡Cupón Aplicado!',
                        html: `Se aplicó un descuento de <strong>$${discountAmt.toFixed(2)}</strong> (${this.appliedCoupon.code})${splitHtml}`,
                        icon: 'success',
                        timer: 2500,
                        showConfirmButton: false
                    });
                    if (input) input.value = '';
                } else {
                    Swal.fire('Cupón Inválido', res.data.message || 'El cupón ingresado no es aplicable a esta venta.', 'error');
                }
            } catch (err) {
                const msg = err.response?.data?.message || 'Error al validar el cupón en el servidor.';
                Swal.fire('Cupón no aplicable', msg, 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        },

        removeCoupon: function() {
            this.appliedCoupon = null;
            localStorage.removeItem('pos_applied_coupon');
            this.syncDefaultPaymentWithNetTotal();
            this.updateCouponUI();
            this.updateTotalsWithPayments();
        },

        updateCouponUI: function() {
            const inputContainer = document.getElementById('coupon-input-container');
            const badge = document.getElementById('coupon-applied-badge');
            const statusText = document.getElementById('coupon-status-text');
            const codeEl = document.getElementById('applied-coupon-code');
            const descEl = document.getElementById('applied-coupon-desc');
            const splitEl = document.getElementById('applied-coupon-split');
            const summaryRow = document.getElementById('summary-coupon-row');
            const summaryEl = document.getElementById('summary-coupon');

            if (this.appliedCoupon) {
                if (inputContainer) inputContainer.classList.add('d-none');
                if (badge) badge.classList.remove('d-none');
                if (statusText) {
                    statusText.innerText = 'Activo';
                    statusText.className = 'badge bg-success-subtle text-success fs-10';
                }
                if (codeEl) codeEl.innerText = this.appliedCoupon.code;
                
                const discountAmt = this.getCouponDiscountAmount();
                const discVal = parseFloat(this.appliedCoupon.discount_value) || 0;
                const discountTxt = this.appliedCoupon.discount_type === 'percentage'
                    ? `-${discVal}% (-$${discountAmt.toFixed(2)})`
                    : `-$${discountAmt.toFixed(2)}`;

                if (descEl) descEl.innerText = discountTxt;

                if (splitEl) {
                    const commInfo = this.appliedCoupon.commission_info;
                    if (commInfo && (commInfo.has_commission || commInfo.is_split)) {
                        splitEl.classList.remove('d-none');
                        const ownerName = commInfo.owner_seller_name || commInfo.coupon_owner_name || this.appliedCoupon.seller_name || 'Vendedor del cupón';
                        const summary = commInfo.split_summary || (commInfo.is_split ? `${commInfo.owner_split_pct}% Prospección / ${commInfo.closing_split_pct}% Cierre` : '100% Venta Directa');
                        splitEl.innerHTML = `<i class="ri-user-shared-line me-1"></i> Comisiona: <strong>${ownerName}</strong> (${summary})`;
                    } else if (this.appliedCoupon.seller_name) {
                        splitEl.classList.remove('d-none');
                        splitEl.innerHTML = `<i class="ri-user-shared-line me-1"></i> Promovido por: <strong>${this.appliedCoupon.seller_name}</strong>`;
                    } else {
                        splitEl.classList.add('d-none');
                    }
                }

                if (summaryRow && summaryEl) {
                    if (discountAmt > 0) {
                        summaryRow.classList.remove('d-none');
                        summaryEl.innerText = `-$${discountAmt.toFixed(2)}`;
                    } else {
                        summaryRow.classList.add('d-none');
                    }
                }
            } else {
                if (inputContainer) inputContainer.classList.remove('d-none');
                if (badge) badge.classList.add('d-none');
                if (statusText) {
                    statusText.innerText = 'Opcional';
                    statusText.className = 'text-muted fs-11';
                }
                if (summaryRow) summaryRow.classList.add('d-none');
                if (splitEl) splitEl.classList.add('d-none');
            }
        },

        loadFromStorage: function() {
            const saved = localStorage.getItem('pos_cart_items');
            if (saved) {
                try {
                    this.items = JSON.parse(saved);
                } catch (e) {
                    this.items = [];
                }
            }
            const savedDelivery = localStorage.getItem('pos_delivery_data');
            if (savedDelivery) {
                try {
                    this.deliveryData = JSON.parse(savedDelivery);
                } catch (e) {
                    this.deliveryData = null;
                }
            }
            const savedPayments = localStorage.getItem('pos_cart_payments');
            const savedHasCustom = localStorage.getItem('pos_has_custom_payments');
            if (savedPayments && savedHasCustom === 'true') {
                try {
                    this.payments = JSON.parse(savedPayments);
                    this.hasCustomPayments = true;
                } catch (e) {
                    this.payments = [];
                    this.hasCustomPayments = false;
                }
            } else {
                this.payments = [];
                this.hasCustomPayments = false;
            }

            const savedCust = localStorage.getItem('pos_customer_id');
            const savedCustName = localStorage.getItem('pos_customer_name');
            if (savedCust) {
                this.ensureCustomerOptionExists(savedCust, savedCustName);
                setTimeout(() => {
                    if (!this._skipInitialSelectRestore) {
                        $('#customer_select').val(savedCust).trigger('change');
                    }
                }, 80);
            }

            const savedSeller = localStorage.getItem('pos_seller_id');
            if (savedSeller) {
                setTimeout(() => {
                    if (!this._skipInitialSelectRestore) {
                        $('#seller_select').val(savedSeller).trigger('change');
                    }
                }, 80);
            }

            const savedLoadedQuote = localStorage.getItem('pos_loaded_quote');
            if (savedLoadedQuote) {
                try {
                    const parsedQ = JSON.parse(savedLoadedQuote);
                    this.loadedQuoteId = parsedQ.id || null;
                    this.loadedQuoteFolio = parsedQ.folio || null;
                } catch (e) {
                    this.loadedQuoteId = null;
                    this.loadedQuoteFolio = null;
                }
            } else {
                this.loadedQuoteId = null;
                this.loadedQuoteFolio = null;
            }

            const savedParkedSales = localStorage.getItem('pos_parked_sales');
            if (savedParkedSales) {
                try {
                    const parsedParked = JSON.parse(savedParkedSales);
                    this.parkedSales = Array.isArray(parsedParked) ? parsedParked : [];
                } catch (e) {
                    this.parkedSales = [];
                }
            } else {
                this.parkedSales = [];
            }

            const savedCoupon = localStorage.getItem('pos_applied_coupon');
            if (savedCoupon) {
                try {
                    this.appliedCoupon = JSON.parse(savedCoupon);
                    this.updateCouponUI();
                } catch (e) {
                    this.appliedCoupon = null;
                    this.updateCouponUI();
                }
            } else {
                this.appliedCoupon = null;
                this.updateCouponUI();
            }

            this.updateDeliveryUI();
            this.updateLoadedQuoteUI();
            this.updateParkedSalesUI();
        },

        saveToStorage: function() {
            localStorage.setItem('pos_cart_items', JSON.stringify(this.items));
            if (this.deliveryData) {
                localStorage.setItem('pos_delivery_data', JSON.stringify(this.deliveryData));
            } else {
                localStorage.removeItem('pos_delivery_data');
            }

            if (this.hasCustomPayments && this.payments && this.payments.length > 0) {
                localStorage.setItem('pos_cart_payments', JSON.stringify(this.payments));
                localStorage.setItem('pos_has_custom_payments', 'true');
            } else {
                localStorage.removeItem('pos_cart_payments');
                localStorage.removeItem('pos_has_custom_payments');
            }

            const customerVal = $('#customer_select').val();
            if (customerVal) {
                localStorage.setItem('pos_customer_id', customerVal);
                const custText = $('#customer_select option:selected').text();
                if (custText) localStorage.setItem('pos_customer_name', custText.trim());
            } else {
                localStorage.removeItem('pos_customer_id');
                localStorage.removeItem('pos_customer_name');
            }

            const sellerVal = $('#seller_select').val();
            if (sellerVal) {
                localStorage.setItem('pos_seller_id', sellerVal);
            } else {
                localStorage.removeItem('pos_seller_id');
            }

            if (this.appliedCoupon) {
                localStorage.setItem('pos_applied_coupon', JSON.stringify(this.appliedCoupon));
            } else {
                localStorage.removeItem('pos_applied_coupon');
            }

            if (this.loadedQuoteId) {
                localStorage.setItem('pos_loaded_quote', JSON.stringify({
                    id: this.loadedQuoteId,
                    folio: this.loadedQuoteFolio || ('COT-' + this.loadedQuoteId)
                }));
            } else {
                localStorage.removeItem('pos_loaded_quote');
            }

            localStorage.setItem('pos_parked_sales', JSON.stringify(this.parkedSales || []));
        },

        bindEvents: function() {
            const searchInput = document.getElementById('pos-search');
            let searchTimeout;

            // Escáner y búsqueda manual
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    document.getElementById('search-results').classList.add('d-none');
                    return;
                }

                searchTimeout = setTimeout(() => {
                    this.searchProduct(query);
                }, 300); // Debounce
            });

            // Ocultar resultados al hacer click fuera
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#pos-search') && !e.target.closest('#search-results')) {
                    document.getElementById('search-results').classList.add('d-none');
                }
            });

            // Guardar cliente en almacenamiento al cambiar y sincronizar datos de entrega si aplica
            $('#customer_select').on('change', async () => {
                this.saveToStorage();
                const newCustId = $('#customer_select').val();
                if (this.deliveryData && !this.deliveryData.is_other_address && newCustId) {
                    const cust = await this.getCustomerData(newCustId);
                    if (cust) {
                        const street = cust.street || cust.legal_address || '';
                        const colonia = cust.colonia || '';
                        const zip = cust.zip || '';
                        const city = cust.city || 'Morelia';
                        const state = cust.state || 'Michoacán';
                        const fullAddr = [street, colonia ? `Col. ${colonia}` : '', zip ? `C.P. ${zip}` : '', city, state].filter(Boolean).join(', ');

                        this.deliveryData.recipient_name = cust.name || cust.recipient_name || this.deliveryData.recipient_name;
                        this.deliveryData.phone = cust.phone || this.deliveryData.phone;
                        this.deliveryData.street = street;
                        this.deliveryData.colonia = colonia;
                        this.deliveryData.city = city;
                        this.deliveryData.state = state;
                        this.deliveryData.zip = zip;
                        this.deliveryData.address = fullAddr;
                        this.deliveryData.references = cust.references || this.deliveryData.references;
                        this.deliveryData.instructions = cust.instructions || this.deliveryData.instructions;
                        if (cust.lat && cust.lng) {
                            this.deliveryData.lat = cust.lat;
                            this.deliveryData.lng = cust.lng;
                            this.deliveryData.maps_url = cust.maps_url || `https://www.google.com/maps?q=${cust.lat},${cust.lng}`;
                        }
                        this.updateDeliveryUI();
                    }
                }
                if (this.appliedCoupon && this.items.length > 0 && this.cartTotal > 0) {
                    this.revalidateAppliedCoupon();
                }
            });

            // Guardar vendedor en almacenamiento al cambiar y actualizar vista previa de comisión de cupón
            $('#seller_select').on('change', () => {
                this.saveToStorage();
                if (this.appliedCoupon && this.items.length > 0 && this.cartTotal > 0) {
                    this.revalidateAppliedCoupon();
                }
            });

            const couponInput = document.getElementById('pos-coupon-code');
            if (couponInput) {
                couponInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.applyCoupon();
                    }
                });
            }
        },

        searchProduct: async function(query) {
            const storeId = document.getElementById('store_select').value;
            if (!storeId) {
                Swal.fire('Atención', 'Por favor selecciona una sucursal primero.', 'warning');
                return;
            }
            try {
                const response = await axios.get(posApiRoutes.search, { 
                    params: { 
                        q: query,
                        store_id: storeId
                    } 
                });
                const results = response.data.data;
                
                if (results.length === 1 && (results[0].sku === query || query.length > 5)) {
                    // Si es resultado único (ej. por escáner), agregar directo
                    this.addItem(results[0]);
                    document.getElementById('pos-search').value = '';
                    document.getElementById('search-results').classList.add('d-none');
                } else {
                    // Mostrar lista
                    this.renderSearchResults(results);
                }
            } catch (error) {
                console.error("Error buscando producto:", error);
            }
        },

        renderSearchResults: function(results) {
            const list = document.getElementById('search-results-list');
            list.innerHTML = '';

            if (results.length === 0) {
                list.innerHTML = '<div class="p-3 text-muted">No se encontraron productos.</div>';
            } else {
                results.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'list-group-item search-item d-flex align-items-center';

                    // Badge de stock
                    let stockBadge = '';
                    let actionIcon = '';
                    let subText = '';

                    if (item.is_virtual) {
                        stockBadge = `<span class="badge bg-info-subtle text-info ms-2"><i class="ri-service-line me-1"></i>Servicio / Virtual</span>`;
                        actionIcon = `<i class="ri-add-circle-line fs-4 text-success ms-2" title="Agregar al carrito"></i>`;
                    } else if (item.is_wildcard) {
                        stockBadge = `<span class="badge bg-warning-subtle text-warning ms-2"><i class="ri-edit-2-line me-1"></i>Personalizado</span>`;
                        actionIcon = `<i class="ri-add-circle-line fs-4 text-warning ms-2" title="Agregar concepto al carrito"></i>`;
                        subText = '<small class="text-warning"><i class="ri-arrow-right-s-line"></i> Clic para configurar concepto libre</small>';
                    } else if (item.stock <= 0) {
                        stockBadge = `<span class="badge bg-danger-subtle text-danger ms-2">Sin stock</span>`;
                        actionIcon = `<i class="ri-file-list-3-line fs-4 text-primary ms-2" title="Crear Pedido Especial"></i>`;
                        subText = '<small class="text-primary"><i class="ri-arrow-right-s-line"></i> Clic para generar Pedido Especial</small>';
                    } else if (item.only_exhibition) {
                        stockBadge = `<span class="badge bg-warning-subtle text-warning ms-2" title="Solo disponible en Piso de Exhibición">⚠️ Solo exhibición (${item.stock})</span>`;
                        actionIcon = `<i class="ri-add-circle-line fs-4 text-success ms-2" title="Agregar al carrito"></i>`;
                    } else {
                        let details = `Bodega: ${item.stock_warehouse}`;
                        if (item.stock_exhibition > 0) details += ` | Exhib: ${item.stock_exhibition}`;
                        stockBadge = `<span class="badge bg-success-subtle text-success ms-2" title="${details}">Disp: ${item.stock}</span>`;
                        actionIcon = `<i class="ri-add-circle-line fs-4 text-success ms-2" title="Agregar al carrito"></i>`;
                    }

                    const imgHtml = item.image
                        ? `<img src="${item.image}" alt="" class="avatar-sm rounded me-3 object-fit-cover" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';"><div class="avatar-sm rounded me-3 bg-light text-muted align-items-center justify-content-center flex-shrink-0" style="display:none;"><i class="ri-image-line fs-20"></i></div>`
                        : `<div class="avatar-sm rounded me-3 bg-light text-muted d-flex align-items-center justify-content-center flex-shrink-0"><i class="ri-image-line fs-20"></i></div>`;

                    div.innerHTML = `
                        ${imgHtml}
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${item.name} ${stockBadge}</h6>
                            <p class="mb-0 text-muted small">SKU: ${item.sku} - $${parseFloat(item.price).toFixed(2)}</p>
                            ${subText}
                        </div>
                        ${actionIcon}
                    `;
                    // Todos los productos son clickeables (stock 0 abre Pedido Especial)
                    div.style.cursor = 'pointer';
                    div.addEventListener('click', () => {
                        document.getElementById('pos-search').value = '';
                        document.getElementById('search-results').classList.add('d-none');
                        document.getElementById('pos-search').focus();
                        this.addItem(item); // addItem ya maneja el flujo (stock 0 → modal PE)
                    });
                    list.appendChild(div);
                });
            }

            document.getElementById('search-results').classList.remove('d-none');
        },

        addItem: function(product) {
            const isVirtual = !!(product.is_virtual || product.type === 'service' || product.type === 'virtual');
            const isWildcard = !!(product.is_wildcard || product.type === 'personalizado');

            // Si es un producto comodín (CUSTOM-001) o servicio libre con precio <= 0: abrir modal para asignar precio y notas
            if (isWildcard || (isVirtual && parseFloat(product.price || 0) <= 0)) {
                this.openServiceConfigModal(product);
                return;
            }

            // Para productos físicos sin stock: ofrecer Pedido Especial
            if (!isVirtual && product.stock <= 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin stock disponible',
                    html: `<strong>${product.name}</strong> no tiene unidades disponibles.<br><br>¿Deseas generar un <strong>Pedido Especial</strong> (con anticipo del cliente)?`,
                    showCancelButton: true,
                    confirmButtonText: '📋 Pedido Especial',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0d6efd',
                }).then(result => {
                    if (result.isConfirmed) {
                        this.openSpecialOrderModal(product);
                    }
                });
                return;
            }

            const existing = this.items.find(i => i.variant_id === product.variant_id && i.id === product.id);

            if (existing) {
                // Virtuales no tienen límite de cantidad
                if (!isVirtual) {
                    const availableStock = existing.stock || product.stock || 0;
                    if (availableStock > 0 && existing.quantity >= availableStock) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock máximo alcanzado',
                            html: `Solo hay <strong>${availableStock}</strong> unidad(es) disponible(s) de <em>${existing.name}</em>.`,
                            confirmButtonText: 'Entendido'
                        });
                        return;
                    }
                }
                existing.quantity++;
            } else {
                // Aviso si solo hay stock en exhibición (pero sí se puede vender)
                if (!isVirtual && product.only_exhibition) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Solo disponible en Exhibición',
                        html: `<strong>${product.name}</strong> únicamente tiene stock en el <strong>Piso de Exhibición</strong> (${product.stock_exhibition} unidad(es)). No hay existencias en bodega.`,
                        confirmButtonText: 'Agregar de todas formas',
                        showCancelButton: true,
                        cancelButtonText: 'Cancelar'
                    }).then(result => {
                        if (result.isConfirmed) {
                            this.items.push({ ...product, quantity: 1, price_type: 'venta' });
                            this.saveToStorage();
                            this.recalculate();
                        }
                    });
                    return;
                }
                this.items.push({ ...product, quantity: 1, price_type: 'venta' });
            }
            this.saveToStorage();
            this.recalculate();
        },

        updateQuantity: function(index, newQty) {
            newQty = parseInt(newQty);
            if (newQty <= 0) {
                this.removeItem(index);
                return;
            }
            if (this.items[index]) {
                const item = this.items[index];
                const isVirtual = item.is_virtual || item.is_wildcard;
                const maxStock = item.stock || 0;
                if (!isVirtual && maxStock > 0 && newQty > maxStock) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cantidad no disponible',
                        html: `Solo hay <strong>${maxStock}</strong> unidad(es) disponible(s) de <em>${item.name}</em>.`,
                        confirmButtonText: 'Entendido',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    newQty = maxStock;
                }
                item.quantity = newQty;
                this.saveToStorage();
                this.recalculate();
            }
        },

        changePriceType: function(index, newType) {
            const item = this.items[index];
            if (!item) return;

            if (newType === 'personalizado') {
                const baseSuggested = item.original_price || parseFloat(item.price);
                Swal.fire({
                    title: 'Definir Precio Personalizado',
                    input: 'number',
                    inputLabel: `Precio base de referencia: $${baseSuggested.toFixed(2)}`,
                    inputValue: item.adjusted_price || item.price,
                    inputAttributes: {
                        min: 0,
                        step: '0.01'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Guardar y Solicitar Autorización',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    inputValidator: (value) => {
                        if (!value || value < 0) return 'Ingresa un precio válido';
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.tempCustomPrice = parseFloat(result.value);
                        this.isOtpAuthorized = false;
                        this.otpIndexTarget = index;

                        Swal.fire({
                            title: 'Generando Código OTP...',
                            text: 'Solicitando autorización al administrador.',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('/user/profile/generate-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ action: 'pos_custom_price' })
                        })
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                document.querySelectorAll(".pos-otp-input").forEach(input => input.value = "");
                                document.getElementById("pos-full-otp-code").value = "";
                                document.getElementById('pos-otp-error-msg').classList.add('d-none');
                                
                                this.posOtpModal.show();
                                
                                setTimeout(() => {
                                    document.querySelectorAll(".pos-otp-input")[0].focus();
                                }, 500);
                            } else {
                                Swal.fire('Error', data.message || 'Error al generar el código OTP.', 'error');
                                this.render();
                            }
                        })
                        .catch(err => {
                            Swal.close();
                            Swal.fire('Error', 'Ocurrió un error al contactar al servidor.', 'error');
                            this.render();
                        });
                    } else {
                        this.render(); // Revertir select en la UI
                    }
                });
            } else {
                item.price_type = newType;
                delete item.adjusted_price;
                this.saveToStorage();
                this.recalculate();
            }
        },

        showMsiBreakdown: function(index) {
            const item = this.items[index];
            if (!item || !item.price_options) {
                Swal.fire('Info', 'Información de financiamiento no disponible en este momento.', 'info');
                return;
            }

            const currentType = item.price_type || 'venta';
            const opts = item.price_options[currentType];
            if (!opts) return;

            let breakdownHtml = `
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Plazo</th>
                                <th>Pago Mensual</th>
                                <th>Total Financiado</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            // Agregar pago de contado
            breakdownHtml += `
                <tr>
                    <td>Contado</td>
                    <td class="fw-semibold">$${parseFloat(item.price).toFixed(2)}</td>
                    <td>$${parseFloat(item.price).toFixed(2)}</td>
                </tr>
            `;

            // MSI Opciones
            [3, 6, 9, 12].forEach(m => {
                const opt = opts[m];
                if (opt) {
                    const price = opt.price;
                    const monthly = price / m;
                    breakdownHtml += `
                        <tr>
                            <td>${m} meses</td>
                            <td class="fw-semibold text-primary">$${monthly.toFixed(2)}</td>
                            <td>$${price.toFixed(2)}</td>
                        </tr>
                    `;
                }
            });

            breakdownHtml += `
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-center text-muted" style="font-size: 0.8rem;">
                    Precio Contado: <strong>$${opts[0] ? opts[0].price.toFixed(2) : parseFloat(item.price).toFixed(2)}</strong>
                </div>
            `;

            Swal.fire({
                title: `Financiamiento para: ${item.name}`,
                html: breakdownHtml,
                confirmButtonText: 'Cerrar'
            });
        },

        openCreateCustomerModal: function() {
            document.getElementById('posCreateCustomerForm').reset();
            
            if (!this.customerModal) {
                const modalEl = document.getElementById('posCreateCustomerModal');
                this.customerModal = new bootstrap.Modal(modalEl);
                
                document.getElementById('posCreateCustomerForm').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const btnSave = document.getElementById('btn-save-pos-customer');
                    btnSave.disabled = true;
                    btnSave.innerText = 'Guardando...';
                    
                    const formData = new FormData(e.target);
                    const payload = Object.fromEntries(formData.entries());
                    
                    try {
                        const response = await axios.post('/api/pos/customers', payload);
                        if (response.data.success) {
                            Swal.fire('¡Éxito!', response.data.message, 'success');
                            this.customerModal.hide();
                            
                            const newCust = response.data.customer;
                            const phoneSuffix = newCust.phone ? ' — Tel: ' + newCust.phone : '';
                            const rfcSuffix = newCust.rfc ? ' (' + newCust.rfc + ')' : '';
                            const custBaseName = newCust.full_name || ((newCust.user?.name || '') + ' ' + (newCust.user?.lastname || '')).trim() || newCust.business_name || ('Cliente #' + newCust.id);
                            const custLabel = custBaseName + phoneSuffix + rfcSuffix;

                            // Registrar en posCustomersMap para autofill inmediato
                            posCustomersMap[newCust.id] = {
                                id: newCust.id,
                                name: custBaseName,
                                recipient_name: custBaseName,
                                phone: newCust.phone || '',
                                street: newCust.street || '',
                                colonia: newCust.colonia || '',
                                city: newCust.city || 'Morelia',
                                state: newCust.state || 'Michoacán',
                                zip: newCust.zip || '',
                                references: newCust.delivery_references || '',
                                instructions: newCust.delivery_instructions || '',
                                lat: newCust.delivery_lat ? parseFloat(newCust.delivery_lat) : null,
                                lng: newCust.delivery_lng ? parseFloat(newCust.delivery_lng) : null,
                                maps_url: newCust.delivery_maps_url || '',
                                legal_address: newCust.legal_address || ''
                            };

                            const option1 = new Option(custLabel, newCust.id, true, true);
                            const option2 = new Option(custLabel, newCust.id, true, true);
                            $('#customer_select').append(option1).trigger('change');
                            $('#so-customer-select').append(option2).trigger('change');
                        } else {
                            Swal.fire('Error', response.data.message || 'No se pudo crear el cliente.', 'error');
                        }
                    } catch (err) {
                        console.error(err);
                        const msg = err.response?.data?.message || 'Error de red o servidor al guardar el cliente.';
                        Swal.fire('Error', msg, 'error');
                    } finally {
                        btnSave.disabled = false;
                        btnSave.innerText = 'Guardar Cliente';
                    }
                });
            }
            
            this.customerModal.show();
        },

        removeItem: function(index) {
            this.items.splice(index, 1);
            this.saveToStorage();
            this.recalculate();
        },

        clear: function() {
            if (this.items.length === 0 && !this.deliveryData && !this.hasCustomPayments && !this.loadedQuoteId) return;
            Swal.fire({
                title: '¿Vaciar carrito?',
                text: 'Se eliminarán todos los productos y la configuración de cobro y entrega del carrito actual.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, vaciar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.resetCurrentCartSilent();
                }
            });
        },

        recalculate: async function() {
            // Render optimista local primero
            this.render(); 
            
            if (this.items.length === 0) {
                this.cartTotal = 0;
                this.payments = [];
                this.hasCustomPayments = false;
                localStorage.removeItem('pos_cart_payments');
                localStorage.removeItem('pos_has_custom_payments');
                this.updateTotalsUI({subtotal: 0, iva_total: 0, ieps_total: 0, tax_total: 0, discount_total: 0, total: 0});
                return;
            }

            const currentCalc = ++this._calcId;

            try {
                // Llamada silenciosa para que el backend valide reglas de negocio y precios
                const payload = {
                    items: this.items,
                    customer_id: $('#customer_select').val() || document.getElementById('customer_select')?.value || null,
                    msi: 0,
                    store_id: document.getElementById('store_select').value
                };
                const response = await axios.post(posApiRoutes.calculate, payload);
                
                if (this._calcId === currentCalc) {
                    this.items = response.data.items; // Actualiza con desglose real
                    this.saveToStorage();
                    this.render();
                    
                    this.cartTotal = parseFloat(response.data.total) || 0;
                    this.updateTotalsUI(response.data);
                }
            } catch (error) {
                console.error("Error calculando totales:", error);
            }
        },

        updateTotalsUI: function(data) {
            this.lastCalcData = data;
            
            const subtotalVal = parseFloat(data.subtotal) || 0;
            const ivaVal = parseFloat(data.iva_total !== undefined ? data.iva_total : data.tax_total) || 0;
            const iepsVal = parseFloat(data.ieps_total || 0) || 0;
            const discountVal = parseFloat(data.discount_total) || 0;

            const subEl = document.getElementById('summary-subtotal');
            if (subEl) subEl.innerText = `$${subtotalVal.toFixed(2)}`;
            
            const ivaEl = document.getElementById('summary-iva');
            if (ivaEl) ivaEl.innerText = `$${ivaVal.toFixed(2)}`;
            
            const iepsRow = document.getElementById('summary-ieps-row');
            const iepsEl = document.getElementById('summary-ieps');
            if (iepsRow && iepsEl) {
                if (iepsVal > 0) {
                    iepsEl.innerText = `$${iepsVal.toFixed(2)}`;
                    iepsRow.classList.remove('d-none');
                } else {
                    iepsRow.classList.add('d-none');
                }
            }

            const taxEl = document.getElementById('summary-tax');
            if (taxEl) taxEl.innerText = `$${(ivaVal + iepsVal).toFixed(2)}`;

            const discountRow = document.getElementById('summary-discount-row');
            const discountEl = document.getElementById('summary-discount');
            if (discountEl) discountEl.innerText = `-$${discountVal.toFixed(2)}`;
            if (discountRow) {
                if (discountVal > 0) {
                    discountRow.classList.remove('d-none');
                } else {
                    discountRow.classList.add('d-none');
                }
            }
            
            // Validar compra mínima del cupón si el carrito fue modificado
            if (this.appliedCoupon && this.items.length > 0 && this.cartTotal > 0) {
                const minPurch = parseFloat(this.appliedCoupon.min_purchase) || 0;
                if (minPurch > 0 && this.cartTotal < minPurch) {
                    const removedCode = this.appliedCoupon.code;
                    this.appliedCoupon = null;
                    localStorage.removeItem('pos_applied_coupon');
                    Swal.fire('Cupón Retirado', `El cupón ${removedCode} requiere una compra mínima de $${minPurch.toFixed(2)} y fue retirado al reducirse el carrito.`, 'warning');
                }
            }

            // Auto-ajustar a contado completo ÚNICAMENTE si el usuario NO ha configurado pagos específicos
            this.syncDefaultPaymentWithNetTotal();
            this.updateCouponUI();
            this.updateTotalsWithPayments();
        },

        render: function() {
            const tbody = document.getElementById('cart-items');
            tbody.innerHTML = '';

            if (this.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">El carrito está vacío</td></tr>';
                return;
            }

            this.items.forEach((item, index) => {
                const tr = document.createElement('tr');
                const currentType = item.price_type || 'venta';

                // Mostrar alerta si el precio está rebajado por regateo
                let priceWarning = '';
                let discountText = '';
                if (item.original_price && item.price < item.original_price) {
                    priceWarning = `<i class="ri-error-warning-line text-warning ms-1" title="Precio rebajado. Original: $${item.original_price.toFixed(2)}"></i>`;
                    if (currentType === 'personalizado') {
                        const diff = item.original_price - item.price;
                        discountText = `<span class="badge bg-success-subtle text-success ms-2">Desc: -$${diff.toFixed(2)}</span>`;
                    }
                }

                // Badge de origen de stock
                let stockBadge = '';
                const isVirtual = item.is_virtual || item.is_wildcard;
                const maxStock = isVirtual ? 9999 : (item.stock || 0);
                const isOverStock = !isVirtual && maxStock > 0 && item.quantity > maxStock;

                if (isVirtual) {
                    if (item.is_delivery_charge) {
                        stockBadge = `<span class="badge bg-success-subtle text-success ms-1">🚚 Flete</span>`;
                    } else if (item.is_installation_charge) {
                        stockBadge = `<span class="badge bg-primary-subtle text-primary ms-1">🔧 Instalación</span>`;
                    } else if (item.is_wildcard) {
                        stockBadge = `<span class="badge bg-warning-subtle text-warning ms-1">✏️ Personalizado</span>`;
                    } else {
                        stockBadge = `<span class="badge bg-info-subtle text-info ms-1">⚡ Servicio</span>`;
                    }
                } else if (item.only_exhibition) {
                    stockBadge = `<span class="badge bg-warning-subtle text-warning ms-1" title="Stock en Piso de Exhibición: ${item.stock_exhibition || 0}">⚠️ Exhibición</span>`;
                } else if (maxStock === 0) {
                    stockBadge = `<span class="badge bg-danger-subtle text-danger ms-1">Sin stock</span>`;
                }
                if (isOverStock) {
                    stockBadge += `<span class="badge bg-danger ms-1">Excede stock (max ${maxStock})</span>`;
                }

                // Desglose de impuestos
                let taxInfo = '';
                if (item.subtotal !== undefined) {
                    const subUnit = parseFloat(item.subtotal / item.quantity) || 0;
                    const ivaUnit = parseFloat((item.iva_amount !== undefined ? item.iva_amount : (item.tax_amount || 0)) / item.quantity) || 0;
                    const iepsUnit = parseFloat((item.ieps_amount || 0) / item.quantity) || 0;
                    if (iepsUnit > 0) {
                        taxInfo = `<div class="text-muted" style="font-size: 0.75rem;">Sub: $${subUnit.toFixed(2)} | IVA: $${ivaUnit.toFixed(2)} | IEPS: $${iepsUnit.toFixed(2)}</div>`;
                    } else {
                        taxInfo = `<div class="text-muted" style="font-size: 0.75rem;">Sub: $${subUnit.toFixed(2)} | IVA: $${ivaUnit.toFixed(2)}</div>`;
                    }
                }

                // Resaltar fila si excede stock
                if (isOverStock) {
                    tr.classList.add('table-danger');
                }

                // Mostrar nombre personalizado si es comodín
                const displayName = item.custom_name || item.name;

                // Imagen o ícono para intangibles/servicios/físicos
                let fallbackIcon = 'ri-image-line';
                let fallbackBg = 'bg-light text-muted';
                if (item.is_delivery_charge) {
                    fallbackIcon = 'ri-truck-line';
                    fallbackBg = 'bg-success-subtle text-success';
                } else if (item.is_installation_charge) {
                    fallbackIcon = 'ri-tools-line';
                    fallbackBg = 'bg-primary-subtle text-primary';
                } else if (item.is_virtual) {
                    fallbackIcon = 'ri-service-line';
                    fallbackBg = 'bg-info-subtle text-info';
                } else if (item.is_wildcard) {
                    fallbackIcon = 'ri-edit-2-line';
                    fallbackBg = 'bg-warning-subtle text-warning';
                }

                const imageHtml = item.image
                    ? `<img src="${item.image}" alt="" class="avatar-xs rounded me-2 object-fit-cover" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';"><div class="avatar-xs rounded me-2 ${fallbackBg} align-items-center justify-content-center flex-shrink-0" style="display:none;"><i class="${fallbackIcon} fs-16"></i></div>`
                    : `<div class="avatar-xs rounded me-2 ${fallbackBg} d-flex align-items-center justify-content-center flex-shrink-0"><i class="${fallbackIcon} fs-16"></i></div>`;

                // Botón de nota por ítem
                const noteIcon = item.notes
                    ? `<button class="btn btn-sm btn-ghost-info p-1" onclick="posCart.openItemNoteModal(${index})" title="${item.notes.replace(/"/g,'&quot;')}"><i class="ri-chat-3-fill text-info"></i></button>`
                    : `<button class="btn btn-sm btn-ghost-secondary p-1 opacity-50" onclick="posCart.openItemNoteModal(${index})" title="Agregar nota"><i class="ri-chat-3-line"></i></button>`;

                tr.innerHTML = `
                    <td>
                        <div class="d-flex align-items-center">
                            ${imageHtml}
                            <div>
                                <h6 class="mb-0 fs-13">${displayName}${discountText}</h6>
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <small class="text-muted">${item.sku || 'N/A'}</small>
                                    ${stockBadge}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="width: 140px;">
                        ${isVirtual ? '<span class="badge bg-light text-secondary border">Virtual</span>' : `
                        <select class="form-select form-select-sm" onchange="posCart.changePriceType(${index}, this.value)">
                            <option value="venta" ${currentType === 'venta' ? 'selected' : ''}>Venta</option>
                            <option value="lista" ${currentType === 'lista' ? 'selected' : ''}>Lista</option>
                            <option value="rebajado" ${currentType === 'rebajado' ? 'selected' : ''}>Rebajado</option>
                            <option value="outlet" ${currentType === 'outlet' ? 'selected' : ''}>Outlet</option>
                            <option value="personalizado" ${currentType === 'personalizado' ? 'selected' : ''}>Personalizado</option>
                        </select>`}
                    </td>
                    <td class="text-end">
                        <div class="fw-medium d-flex justify-content-end align-items-center">
                            $${parseFloat(item.price).toFixed(2)} ${priceWarning}
                            <button class="btn btn-sm btn-ghost-info p-1 ms-1" onclick="posCart.showMsiBreakdown(${index})" title="Ver Financiamiento / MSI"><i class="ri-information-line"></i></button>
                        </div>
                        ${taxInfo}
                    </td>
                    <td style="width: 120px;">
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary" type="button" onclick="posCart.updateQuantity(${index}, ${item.quantity - 1})">-</button>
                            <input type="number" class="form-control text-center px-1 ${isOverStock ? 'is-invalid' : ''}" value="${item.quantity}" min="1" max="${isVirtual ? 9999 : (maxStock > 0 ? maxStock : 9999)}" onchange="posCart.updateQuantity(${index}, this.value)">
                            <button class="btn btn-outline-secondary" type="button" onclick="posCart.updateQuantity(${index}, ${item.quantity + 1})">+</button>
                        </div>
                    </td>
                    <td class="text-end fw-bold text-primary">$${(item.price * item.quantity).toFixed(2)}</td>
                    <td class="text-center">${noteIcon}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-soft-danger btn-icon" onclick="posCart.removeItem(${index})"><i class="ri-delete-bin-line"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        },

        // ────────────────────────────────────────────────────────
        // CONCEPTOS LIBRES, SERVICIOS Y COMODÍN
        // ────────────────────────────────────────────────────────

        openWildcardModal: function() {
            const wildcardProduct = {
                id: {{ \App\Models\Product::where('is_wildcard', true)->value('id') ?? \App\Models\Product::where('sku', 'CUSTOM-001')->value('id') ?? 'null' }},
                name: 'Concepto Personalizado',
                sku: 'CUSTOM-001',
                price: 0,
                cost: 0,
                is_wildcard: true,
                is_virtual: true
            };
            this.openServiceConfigModal(wildcardProduct);
        },

        openServiceConfigModal: function(product) {
            this._currentServiceProduct = product || {
                id: {{ \App\Models\Product::where('is_wildcard', true)->value('id') ?? \App\Models\Product::where('sku', 'CUSTOM-001')->value('id') ?? 'null' }},
                name: 'Concepto Personalizado',
                sku: 'CUSTOM-001',
                price: 0,
                cost: 0,
                is_wildcard: true,
                is_virtual: true
            };

            document.getElementById('service-product-id').value = this._currentServiceProduct.id || '';
            document.getElementById('service-is-wildcard').value = this._currentServiceProduct.is_wildcard ? '1' : '0';
            document.getElementById('service-concept-name').value = this._currentServiceProduct.is_wildcard ? '' : (this._currentServiceProduct.name || '');
            document.getElementById('service-concept-price').value = parseFloat(this._currentServiceProduct.price || 0) > 0 ? parseFloat(this._currentServiceProduct.price).toFixed(2) : '';
            document.getElementById('service-concept-qty').value = 1;
            document.getElementById('service-concept-notes').value = '';

            if (!this._serviceModal) {
                this._serviceModal = new bootstrap.Modal(document.getElementById('modal-service-config'));
            }
            this._serviceModal.show();
            setTimeout(() => {
                const nameInput = document.getElementById('service-concept-name');
                if (nameInput.value) {
                    document.getElementById('service-concept-price').focus();
                } else {
                    nameInput.focus();
                }
            }, 300);
        },

        switchToSpecialOrderFromService: function() {
            if (this._serviceModal) this._serviceModal.hide();
            const product = this._currentServiceProduct || {
                id: {{ \App\Models\Product::where('is_wildcard', true)->value('id') ?? \App\Models\Product::where('sku', 'CUSTOM-001')->value('id') ?? 'null' }},
                name: 'Trabajo / Mueble Personalizado',
                sku: 'CUSTOM-001',
                price: 0,
                cost: 0,
                is_wildcard: true
            };
            this.openSpecialOrderModal(product);
        },

        confirmAddService: function() {
            const name = document.getElementById('service-concept-name').value.trim();
            const price = parseFloat(document.getElementById('service-concept-price').value);
            const qty = parseInt(document.getElementById('service-concept-qty').value) || 1;
            const notes = document.getElementById('service-concept-notes').value.trim();

            if (!name) {
                Swal.fire('Requerido', 'Por favor ingresa el nombre o concepto del servicio.', 'warning');
                return;
            }
            if (isNaN(price) || price < 0) {
                Swal.fire('Requerido', 'Por favor ingresa un precio válido mayor o igual a cero.', 'warning');
                return;
            }

            const baseProduct = this._currentServiceProduct || {};
            const wildcardId = {{ \App\Models\Product::where('is_wildcard', true)->value('id') ?? \App\Models\Product::where('sku', 'CUSTOM-001')->value('id') ?? 'null' }};
            const item = {
                id: baseProduct.id || wildcardId,
                name: name,
                sku: baseProduct.sku || 'CUSTOM-001',
                price: price,
                adjusted_price: price,
                price_type: 'venta',
                quantity: qty,
                is_virtual: true,
                is_wildcard: !!baseProduct.is_wildcard,
                notes: notes || null,
                stock: 9999
            };

            this.items.push(item);
            this.saveToStorage();
            this.recalculate();

            if (this._serviceModal) this._serviceModal.hide();

            Swal.fire({
                icon: 'success',
                title: 'Agregado al carrito',
                text: `${name} ($${price.toFixed(2)}) se agregó correctamente.`,
                timer: 1500,
                showConfirmButton: false
            });
        },

        // ────────────────────────────────────────────────────────
        // PEDIDO A DOMICILIO CON GOOGLE MAPS & AUTORRELLENO
        // ────────────────────────────────────────────────────────

        getCustomerData: async function(customerId) {
            if (!customerId) return null;
            if (posCustomersMap[customerId]) {
                return posCustomersMap[customerId];
            }
            try {
                const res = await axios.get(`${posApiRoutes.customers}/${customerId}`);
                if (res.data && res.data.success && res.data.customer) {
                    const c = res.data.customer;
                    posCustomersMap[customerId] = {
                        id: c.id,
                        name: c.name ? `${c.name} ${c.last_name || ''}`.trim() : (c.business_name || `Cliente #${c.id}`),
                        recipient_name: c.name ? `${c.name} ${c.last_name || ''}`.trim() : (c.business_name || `Cliente #${c.id}`),
                        phone: c.phone || '',
                        street: c.street || '',
                        colonia: c.colonia || '',
                        city: c.city || 'Morelia',
                        state: c.state || 'Michoacán',
                        zip: c.zip || '',
                        references: c.delivery_references || '',
                        instructions: c.delivery_instructions || '',
                        lat: c.delivery_lat ? parseFloat(c.delivery_lat) : null,
                        lng: c.delivery_lng ? parseFloat(c.delivery_lng) : null,
                        maps_url: c.delivery_maps_url || '',
                        legal_address: c.legal_address || ''
                    };
                    return posCustomersMap[customerId];
                }
            } catch (e) {
                console.warn('No se pudo cargar datos del cliente:', e);
            }
            return null;
        },

        fillDeliveryFormWithCustomer: function(cust) {
            if (!cust) return;
            const recInput = document.getElementById('delivery-recipient-name');
            const phoneInput = document.getElementById('delivery-phone');
            const streetInput = document.getElementById('delivery-street');
            const zipInput = document.getElementById('delivery-zip');
            const coloniaInput = document.getElementById('delivery-colonia');
            const cityInput = document.getElementById('delivery-city');
            const stateInput = document.getElementById('delivery-state');
            const refInput = document.getElementById('delivery-references');
            const instInput = document.getElementById('delivery-instructions');
            const statusText = document.getElementById('delivery-address-status-text');

            if (recInput) recInput.value = cust.recipient_name || cust.name || '';
            if (phoneInput) phoneInput.value = cust.phone || '';
            if (streetInput) streetInput.value = cust.street || cust.legal_address || '';
            if (zipInput) zipInput.value = cust.zip || '';
            if (coloniaInput) coloniaInput.value = cust.colonia || '';
            if (cityInput) cityInput.value = cust.city || 'Morelia';
            if (stateInput) stateInput.value = cust.state || 'Michoacán';
            if (refInput) refInput.value = cust.references || '';
            if (instInput) instInput.value = cust.instructions || '';

            if (cust.lat && cust.lng) {
                this.updateDeliveryCoords(cust.lat, cust.lng);
                if (this._deliveryMap) {
                    const pos = { lat: cust.lat, lng: cust.lng };
                    this._deliveryMap.panTo(pos);
                    if (this._deliveryMarker) {
                        if (this._deliveryMarker.position !== undefined) this._deliveryMarker.position = pos;
                        else if (typeof this._deliveryMarker.setPosition === 'function') this._deliveryMarker.setPosition(pos);
                    }
                }
            } else {
                document.getElementById('delivery-lat').value = '';
                document.getElementById('delivery-lng').value = '';
                const badge = document.getElementById('delivery-coords-badge');
                if (badge) {
                    badge.innerText = 'Pin: Sin fijar';
                    badge.className = 'badge bg-light text-secondary border';
                }
                const link = document.getElementById('delivery-maps-link');
                if (link) link.classList.add('d-none');
            }

            if (statusText) {
                const label = cust.name || cust.recipient_name || 'Cliente';
                statusText.innerHTML = `<span class="text-success"><i class="ri-check-line me-1"></i>Datos habituales autorrellenados de <strong>${label}</strong></span>`;
            }
        },

        toggleDeliveryOtherAddress: async function(isOther) {
            const statusText = document.getElementById('delivery-address-status-text');
            const saveProfileCont = document.getElementById('delivery-save-profile-container');
            const custId = $('#customer_select').val() || document.getElementById('customer_select')?.value;

            if (isOther) {
                if (statusText) {
                    statusText.innerHTML = '<span class="text-warning"><i class="ri-edit-line me-1"></i>Envío a otro domicilio: Captura los datos de destino manualmente.</span>';
                }
                if (saveProfileCont) saveProfileCont.style.display = 'none';

                // Limpiar campos de dirección y geolocalización para captura manual
                document.getElementById('delivery-street').value = '';
                document.getElementById('delivery-zip').value = '';
                document.getElementById('delivery-colonia').value = '';
                document.getElementById('delivery-city').value = 'Morelia';
                document.getElementById('delivery-state').value = 'Michoacán';
                document.getElementById('delivery-references').value = '';
                document.getElementById('delivery-instructions').value = '';
                document.getElementById('delivery-lat').value = '';
                document.getElementById('delivery-lng').value = '';

                const badge = document.getElementById('delivery-coords-badge');
                if (badge) {
                    badge.innerText = 'Pin: Sin fijar';
                    badge.className = 'badge bg-light text-secondary border';
                }
                const link = document.getElementById('delivery-maps-link');
                if (link) link.classList.add('d-none');
            } else {
                if (saveProfileCont) saveProfileCont.style.display = custId ? 'block' : 'none';
                if (custId) {
                    const cust = await this.getCustomerData(custId);
                    if (cust) {
                        this.fillDeliveryFormWithCustomer(cust);
                    }
                } else if (statusText) {
                    statusText.innerText = 'Por defecto se autorrellenan los datos y coordenadas habituales del cliente.';
                }
            }
        },

        openDeliveryModal: async function() {
            if (!this._deliveryModal) {
                this._deliveryModal = new bootstrap.Modal(document.getElementById('modal-delivery-order'));
            }

            const otherAddressSwitch = document.getElementById('delivery-is-other-address');
            const saveProfileCont = document.getElementById('delivery-save-profile-container');
            const statusText = document.getElementById('delivery-address-status-text');
            const custId = $('#customer_select').val() || document.getElementById('customer_select')?.value;

            // Si ya hay datos configurados en este carrito
            if (this.deliveryData) {
                const isOther = !!this.deliveryData.is_other_address;
                if (otherAddressSwitch) otherAddressSwitch.checked = isOther;

                document.getElementById('delivery-recipient-name').value = this.deliveryData.recipient_name || '';
                document.getElementById('delivery-phone').value = this.deliveryData.phone || '';
                document.getElementById('delivery-street').value = this.deliveryData.street || '';
                document.getElementById('delivery-zip').value = this.deliveryData.zip || '';
                document.getElementById('delivery-colonia').value = this.deliveryData.colonia || '';
                document.getElementById('delivery-city').value = this.deliveryData.city || '';
                document.getElementById('delivery-state').value = this.deliveryData.state || '';
                document.getElementById('delivery-references').value = this.deliveryData.references || '';
                document.getElementById('delivery-lat').value = this.deliveryData.lat || '';
                document.getElementById('delivery-lng').value = this.deliveryData.lng || '';
                document.getElementById('delivery-cost').value = (parseFloat(this.deliveryData.delivery_cost) || 0).toFixed(2);
                document.getElementById('delivery-installation-cost').value = (parseFloat(this.deliveryData.installation_cost) || 0).toFixed(2);
                document.getElementById('delivery-date').value = this.deliveryData.date || '';
                const delTimeEl = document.getElementById('delivery-time');
                if (delTimeEl) delTimeEl.value = this.deliveryData.time || '11:00';
                document.getElementById('delivery-instructions').value = this.deliveryData.instructions || '';
                document.getElementById('btn-remove-delivery').classList.remove('d-none');

                if (this.deliveryData.lat && this.deliveryData.lng) {
                    this.updateDeliveryCoords(this.deliveryData.lat, this.deliveryData.lng, false);
                }

                if (saveProfileCont) saveProfileCont.style.display = (!isOther && custId) ? 'block' : 'none';
                if (statusText) {
                    statusText.innerHTML = isOther
                        ? '<span class="text-warning"><i class="ri-edit-line me-1"></i>Configurado para entrega a otro domicilio alternativo.</span>'
                        : '<span class="text-success"><i class="ri-check-line me-1"></i>Configurado con domicilio habitual del cliente.</span>';
                }
            } else {
                // Nuevo envío: si hay cliente seleccionado, autorrellenar automáticamente
                if (otherAddressSwitch) otherAddressSwitch.checked = false;
                if (saveProfileCont) saveProfileCont.style.display = custId ? 'block' : 'none';
                document.getElementById('btn-remove-delivery').classList.add('d-none');

                if (custId) {
                    const cust = await this.getCustomerData(custId);
                    if (cust) {
                        this.fillDeliveryFormWithCustomer(cust);
                    }
                } else {
                    document.getElementById('delivery-recipient-name').value = '';
                    document.getElementById('delivery-phone').value = '';
                    document.getElementById('delivery-street').value = '';
                    document.getElementById('delivery-zip').value = '';
                    document.getElementById('delivery-colonia').value = '';
                    document.getElementById('delivery-city').value = 'Morelia';
                    document.getElementById('delivery-state').value = 'Michoacán';
                    document.getElementById('delivery-references').value = '';
                    document.getElementById('delivery-instructions').value = '';
                    document.getElementById('delivery-lat').value = '';
                    document.getElementById('delivery-lng').value = '';
                    if (statusText) statusText.innerText = 'Selecciona un cliente para autorrellenar o ingresa los datos manualmente.';
                }
            }

            this._deliveryModal.show();

            // Inicializar el mapa después de que el modal sea visible
            setTimeout(() => {
                this.initDeliveryMap();
            }, 350);
        },

        initDeliveryMap: async function() {
            const mapContainer = document.getElementById('delivery-map');
            if (!mapContainer) return;

            let curLat = parseFloat(document.getElementById('delivery-lat').value) || this.storeDefaultLat || 19.2433880;
            let curLng = parseFloat(document.getElementById('delivery-lng').value) || this.storeDefaultLng || -103.7250940;
            const center = { lat: curLat, lng: curLng };

            try {
                if (typeof google === 'undefined' || !google.maps) {
                    mapContainer.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted small p-3 text-center"><i class="ri-map-pin-line fs-4 me-2 text-warning"></i><span>API de Google Maps no conectada. Puedes ingresar las coordenadas manualmente.</span></div>';
                    return;
                }

                const { Map } = await google.maps.importLibrary("maps");
                let AdvancedMarkerElement = null;
                try {
                    const markerLib = await google.maps.importLibrary("marker");
                    AdvancedMarkerElement = markerLib.AdvancedMarkerElement;
                } catch(e) {
                    console.log("AdvancedMarkerElement no disponible, usando Marker estándar.");
                }

                this._deliveryMap = new Map(mapContainer, {
                    center: center,
                    zoom: 15,
                    mapId: 'delivery_order_map',
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                });

                if (AdvancedMarkerElement) {
                    this._deliveryMarker = new AdvancedMarkerElement({
                        position: center,
                        map: this._deliveryMap,
                        gmpDraggable: true,
                        title: 'Punto de Entrega'
                    });
                    this._deliveryMarker.addListener('dragend', () => {
                        const pos = this._deliveryMarker.position;
                        this.updateDeliveryCoords(pos.lat, pos.lng);
                    });
                } else {
                    this._deliveryMarker = new google.maps.Marker({
                        position: center,
                        map: this._deliveryMap,
                        draggable: true,
                        title: 'Punto de Entrega'
                    });
                    this._deliveryMarker.addListener('dragend', (e) => {
                        this.updateDeliveryCoords(e.latLng.lat(), e.latLng.lng());
                    });
                }

                this._deliveryMap.addListener('click', (e) => {
                    const lat = e.latLng.lat();
                    const lng = e.latLng.lng();
                    if (AdvancedMarkerElement && this._deliveryMarker) {
                        this._deliveryMarker.position = { lat, lng };
                    } else if (this._deliveryMarker) {
                        this._deliveryMarker.setPosition(e.latLng);
                    }
                    this.updateDeliveryCoords(lat, lng);
                });

                // Escuchadores en inputs manuales de lat/lng
                const latInput = document.getElementById('delivery-lat');
                const lngInput = document.getElementById('delivery-lng');
                const onManualCoordChange = () => {
                    const mLat = parseFloat(latInput.value);
                    const mLng = parseFloat(lngInput.value);
                    if (!isNaN(mLat) && !isNaN(mLng)) {
                        const newPos = { lat: mLat, lng: mLng };
                        if (this._deliveryMap) this._deliveryMap.panTo(newPos);
                        if (AdvancedMarkerElement && this._deliveryMarker) this._deliveryMarker.position = newPos;
                        else if (this._deliveryMarker) this._deliveryMarker.setPosition(newPos);
                        this.updateDeliveryCoords(mLat, mLng, false);
                    }
                };
                latInput.onchange = onManualCoordChange;
                lngInput.onchange = onManualCoordChange;

                if (document.getElementById('delivery-lat').value && document.getElementById('delivery-lng').value) {
                    this.updateDeliveryCoords(curLat, curLng, false);
                }

            } catch (err) {
                console.error("Error inicializando mapa de entrega:", err);
                mapContainer.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted small p-3 text-center"><i class="ri-alert-line fs-4 me-2 text-warning"></i><span>No se pudo cargar el mapa. Puedes ingresar dirección y coordenadas manualmente.</span></div>';
            }
        },

        updateDeliveryCoords: function(lat, lng, updateInputs = true) {
            lat = typeof lat === 'number' ? lat : parseFloat(lat);
            lng = typeof lng === 'number' ? lng : parseFloat(lng);
            if (isNaN(lat) || isNaN(lng)) return;

            if (updateInputs) {
                document.getElementById('delivery-lat').value = lat.toFixed(7);
                document.getElementById('delivery-lng').value = lng.toFixed(7);
            }

            const badge = document.getElementById('delivery-coords-badge');
            if (badge) {
                badge.innerText = `Pin: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                badge.className = 'badge bg-success-subtle text-success border border-success';
            }

            const link = document.getElementById('delivery-maps-link');
            if (link) {
                link.href = `https://www.google.com/maps?q=${lat.toFixed(7)},${lng.toFixed(7)}`;
                link.classList.remove('d-none');
            }
        },

        locateDeliveryAddressOnMap: async function() {
            const street = document.getElementById('delivery-street').value.trim();
            const colonia = document.getElementById('delivery-colonia').value.trim();
            const city = document.getElementById('delivery-city').value.trim();
            const state = document.getElementById('delivery-state').value.trim();

            const fullQuery = [street, colonia, city, state, 'México'].filter(Boolean).join(', ');
            if (!fullQuery || fullQuery === 'México') {
                Swal.fire('Atención', 'Ingresa al menos la calle, colonia o ciudad para ubicar en el mapa.', 'info');
                return;
            }

            try {
                if (typeof google === 'undefined' || !google.maps) {
                    Swal.fire('Aviso', 'El servicio de Google Maps no está disponible en este momento.', 'warning');
                    return;
                }

                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: fullQuery }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        const loc = results[0].geometry.location;
                        const lat = loc.lat();
                        const lng = loc.lng();

                        if (this._deliveryMap) {
                            this._deliveryMap.setCenter(loc);
                            this._deliveryMap.setZoom(16);
                        }
                        if (this._deliveryMarker) {
                            if (this._deliveryMarker.position !== undefined) {
                                this._deliveryMarker.position = { lat, lng };
                            } else if (typeof this._deliveryMarker.setPosition === 'function') {
                                this._deliveryMarker.setPosition(loc);
                            }
                        }
                        this.updateDeliveryCoords(lat, lng);
                    } else {
                        Swal.fire('No encontrado', 'No se encontró la ubicación exacta en el mapa. Puedes arrastrar el marcador manualmente al punto deseado.', 'warning');
                    }
                });
            } catch (e) {
                console.error("Geocoding error:", e);
                Swal.fire('Error', 'No se pudo geocodificar la dirección.', 'error');
            }
        },

        formatTime12h: function(time24) {
            if (!time24) return null;
            const parts = time24.split(':');
            if (parts.length < 2) return time24;
            let h = parseInt(parts[0], 10);
            const m = parts[1];
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return `${String(h).padStart(2, '0')}:${m} ${ampm}`;
        },

        saveDelivery: function() {
            const recipientName = document.getElementById('delivery-recipient-name').value.trim();
            const phone = document.getElementById('delivery-phone').value.trim();
            const street = document.getElementById('delivery-street').value.trim();
            const zip = document.getElementById('delivery-zip').value.trim();
            const colonia = document.getElementById('delivery-colonia').value.trim();
            const city = document.getElementById('delivery-city').value.trim();
            const state = document.getElementById('delivery-state').value.trim();
            const references = document.getElementById('delivery-references').value.trim();
            const lat = document.getElementById('delivery-lat').value.trim();
            const lng = document.getElementById('delivery-lng').value.trim();
            const deliveryCost = parseFloat(document.getElementById('delivery-cost').value) || 0;
            const installationCost = parseFloat(document.getElementById('delivery-installation-cost').value) || 0;
            const date = document.getElementById('delivery-date').value;
            const time = document.getElementById('delivery-time')?.value || '11:00';
            const timeSlot = this.formatTime12h(time);
            const instructions = document.getElementById('delivery-instructions').value.trim();

            if (!recipientName) {
                Swal.fire('Requerido', 'Ingresa el nombre de la persona que recibirá la entrega.', 'warning');
                return;
            }
            if (!street) {
                Swal.fire('Requerido', 'Ingresa la calle y número de destino.', 'warning');
                return;
            }

            const addressParts = [street];
            if (colonia) addressParts.push(`Col. ${colonia}`);
            if (zip) addressParts.push(`C.P. ${zip}`);
            if (city) addressParts.push(city);
            if (state) addressParts.push(state);
            const fullAddress = addressParts.join(', ');

            const mapsUrl = (lat && lng) ? `https://www.google.com/maps?q=${lat},${lng}` : null;
            const isOtherAddress = document.getElementById('delivery-is-other-address')?.checked || false;
            const updateCustomerProfile = document.getElementById('delivery-update-customer-profile')?.checked || false;
            const currentCustomerId = $('#customer_select').val() || document.getElementById('customer_select')?.value;

            this.deliveryData = {
                is_delivery: true,
                is_other_address: isOtherAddress,
                recipient_name: recipientName,
                phone: phone,
                address: fullAddress,
                street: street,
                zip: zip,
                colonia: colonia,
                city: city,
                state: state,
                references: references,
                lat: lat ? parseFloat(lat) : null,
                lng: lng ? parseFloat(lng) : null,
                maps_url: mapsUrl,
                delivery_cost: deliveryCost,
                installation_cost: installationCost,
                date: date || null,
                time: time || null,
                time_slot: timeSlot,
                instructions: instructions
            };

            // Si se marcó actualizar perfil habitual del cliente y no es otro domicilio
            if (!isOtherAddress && updateCustomerProfile && currentCustomerId) {
                axios.post(`${posApiRoutes.customers}/${currentCustomerId}/delivery`, {
                    street: street,
                    colonia: colonia,
                    city: city,
                    state: state,
                    zip: zip,
                    delivery_references: references,
                    delivery_instructions: instructions,
                    delivery_lat: lat ? parseFloat(lat) : null,
                    delivery_lng: lng ? parseFloat(lng) : null,
                    delivery_maps_url: mapsUrl
                }).then(res => {
                    if (res.data && res.data.success && posCustomersMap[currentCustomerId]) {
                        Object.assign(posCustomersMap[currentCustomerId], {
                            street, colonia, city, state, zip,
                            references, instructions,
                            lat: lat ? parseFloat(lat) : null,
                            lng: lng ? parseFloat(lng) : null,
                            maps_url: mapsUrl
                        });
                    }
                }).catch(err => {
                    console.warn('No se pudo guardar la dirección en el perfil del cliente:', err);
                });
            }

            // Sincronizar conceptos en el carrito: Flete
            const fleteProductId = {{ isset($fleteProduct) && $fleteProduct ? $fleteProduct->id : (\App\Models\Product::where('sku', 'SERV-FLETE')->value('id') ?? 'null') }};
            const fleteIndex = this.items.findIndex(i => i.is_delivery_charge || i.sku === 'SERV-FLETE');
            if (deliveryCost > 0) {
                const fleteItem = {
                    id: fleteProductId,
                    sku: 'SERV-FLETE',
                    name: 'Flete / Envío a Domicilio',
                    price: deliveryCost,
                    adjusted_price: deliveryCost,
                    price_type: 'venta',
                    quantity: 1,
                    is_virtual: true,
                    is_service: true,
                    is_delivery_charge: true,
                    notes: `Entrega en: ${street}${colonia ? ', Col. ' + colonia : ''}`,
                    stock: 9999
                };
                if (fleteIndex >= 0) {
                    this.items[fleteIndex] = fleteItem;
                } else {
                    this.items.push(fleteItem);
                }
            } else if (fleteIndex >= 0) {
                this.items.splice(fleteIndex, 1);
            }

            // Sincronizar conceptos en el carrito: Instalación
            const armadoProductId = {{ isset($armadoProduct) && $armadoProduct ? $armadoProduct->id : (\App\Models\Product::where('sku', 'SERV-ARMADO')->value('id') ?? 'null') }};
            const armadoIndex = this.items.findIndex(i => i.is_installation_charge || i.sku === 'SERV-ARMADO');
            if (installationCost > 0) {
                const armadoItem = {
                    id: armadoProductId,
                    sku: 'SERV-ARMADO',
                    name: 'Servicio de Armado e Instalación',
                    price: installationCost,
                    adjusted_price: installationCost,
                    price_type: 'venta',
                    quantity: 1,
                    is_virtual: true,
                    is_service: true,
                    is_installation_charge: true,
                    notes: 'Instalación y armado en sitio',
                    stock: 9999
                };
                if (armadoIndex >= 0) {
                    this.items[armadoIndex] = armadoItem;
                } else {
                    this.items.push(armadoItem);
                }
            } else if (armadoIndex >= 0) {
                this.items.splice(armadoIndex, 1);
            }

            this.saveToStorage();
            this.recalculate();
            this.updateDeliveryUI();

            if (this._deliveryModal) this._deliveryModal.hide();

            Swal.fire({
                icon: 'success',
                title: 'Pedido a Domicilio Configurado',
                html: `Se programó la entrega para <strong>${recipientName}</strong>.<br>Cargos agregados al ticket: <strong>$${(deliveryCost + installationCost).toFixed(2)}</strong>`,
                timer: 2500,
                showConfirmButton: false
            });
        },

        removeDelivery: function() {
            this.items = this.items.filter(i => !i.is_delivery_charge && !i.is_installation_charge);
            this.deliveryData = null;
            localStorage.removeItem('pos_delivery_data');

            this.saveToStorage();
            this.recalculate();
            this.updateDeliveryUI();

            if (this._deliveryModal) this._deliveryModal.hide();

            Swal.fire({
                icon: 'info',
                title: 'Envío Removido',
                text: 'Se retiraron los datos de entrega y cargos de flete del carrito.',
                timer: 1500,
                showConfirmButton: false
            });
        },

        updateDeliveryUI: function() {
            const btn = document.getElementById('btn-delivery-config');
            const banner = document.getElementById('delivery-cart-banner');
            const label = document.getElementById('btn-delivery-label');

            if (this.deliveryData && this.deliveryData.is_delivery) {
                const totalDelivery = (parseFloat(this.deliveryData.delivery_cost) || 0) + (parseFloat(this.deliveryData.installation_cost) || 0);
                if (btn) {
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-success');
                    if (label) label.innerText = `🚚 Envío ($${totalDelivery.toFixed(2)})`;
                }
                if (banner) {
                    banner.classList.remove('d-none');
                    document.getElementById('delivery-banner-text').innerText = `Envío a: ${this.deliveryData.recipient_name} - ${this.deliveryData.street || this.deliveryData.address}`;
                    const subParts = [];
                    if (this.deliveryData.date) subParts.push(`Fecha: ${this.deliveryData.date}`);
                    if (this.deliveryData.time_slot) subParts.push(`Turno: ${this.deliveryData.time_slot}`);
                    subParts.push(`Total Envío: $${totalDelivery.toFixed(2)}`);
                    document.getElementById('delivery-banner-subtext').innerText = subParts.join(' | ');
                }
            } else {
                if (btn) {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-primary');
                    if (label) label.innerText = 'Envío a Domicilio';
                }
                if (banner) {
                    banner.classList.add('d-none');
                }
            }
        },

        // ────────────────────────────────────────────────────────
        // NOTAS POR ÍTEM
        // ────────────────────────────────────────────────────────

        openItemNoteModal: function(index) {
            document.getElementById('item-note-index').value = index;
            document.getElementById('item-note-text').value  = this.items[index]?.notes || '';
            if (!this._itemNoteModal) this._itemNoteModal = new bootstrap.Modal(document.getElementById('modal-item-note'));
            this._itemNoteModal.show();
        },

        saveItemNote: function() {
            const index = parseInt(document.getElementById('item-note-index').value);
            const note  = document.getElementById('item-note-text').value.trim();
            if (this.items[index] !== undefined) {
                this.items[index].notes = note || null;
                this.saveToStorage();
                this.render();
            }
            this._itemNoteModal.hide();
        },

        // ────────────────────────────────────────────────────────
        // PEDIDO ESPECIAL
        // ────────────────────────────────────────────────────────

        openSpecialOrderModal: function(product) {
            this._soProduct = product;

            const isWildcard = !!(product.is_wildcard || product.type === 'personalizado');
            const customTitleCol = document.getElementById('so-custom-title-col');

            if (isWildcard) {
                if (customTitleCol) customTitleCol.style.display = 'block';
                document.getElementById('so-product-name').innerText = 'Trabajo / Mueble Personalizado (A Medida)';
                document.getElementById('so-product-sku').innerText  = 'CUSTOM-001';
                document.getElementById('so-custom-name').value      = '';
            } else {
                if (customTitleCol) customTitleCol.style.display = 'none';
                document.getElementById('so-product-name').innerText = product.name;
                document.getElementById('so-product-sku').innerText  = product.sku || '';
            }

            document.getElementById('so-quantity').value         = 1;
            document.getElementById('so-unit-price').value       = parseFloat(product.price || 0).toFixed(2);
            document.getElementById('so-notes').value            = '';
            document.getElementById('so-expected-date').value    = '';

            // Escuchar cambio en método de pago y terminal
            const pmSelect   = document.getElementById('so-payment-method');
            const termSelect = document.getElementById('so-payment-terminal');

            if (pmSelect) {
                pmSelect.removeEventListener('change', () => this.onSpecialOrderPaymentMethodChange());
                pmSelect.addEventListener('change', () => this.onSpecialOrderPaymentMethodChange());
            }
            if (termSelect) {
                termSelect.removeEventListener('change', () => this.onSpecialOrderTerminalChange());
                termSelect.addEventListener('change', () => this.onSpecialOrderTerminalChange());
            }

            // Escuchar cambios en los inputs para recalcular y validar anticipo 20%
            const updateSummary = () => this.updateSpecialOrderSummary();
            ['so-quantity','so-unit-price','so-deposit'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.removeEventListener('input', updateSummary);
                    el.addEventListener('input', updateSummary);
                    el.removeEventListener('change', updateSummary);
                    el.addEventListener('change', updateSummary);
                }
            });

            // Establecer precio inicial y calcular anticipo 20% mínimo por defecto
            const initPrice = parseFloat(product.price || 0);
            const minDep    = (initPrice * 0.20).toFixed(2);
            document.getElementById('so-deposit').value = minDep;

            // Auto-sincronizar el cliente previamente seleccionado en la pantalla del POS
            const currentCustomer = $('#customer_select').val();
            if (currentCustomer) {
                $('#so-customer-select').val(currentCustomer).trigger('change');
            }

            // Reset entrega a domicilio
            const soDelToggle = document.getElementById('so-is-delivery');
            if (soDelToggle) soDelToggle.checked = false;
            const soOtherToggle = document.getElementById('so-delivery-is-other-address');
            if (soOtherToggle) soOtherToggle.checked = false;
            this.toggleSoDelivery(false);

            // Escuchar cambio de cliente en pedido especial para rellenar datos de entrega si está activo
            if (!this._soCustomerBound) {
                this._soCustomerBound = true;
                $('#so-customer-select').on('change', async () => {
                    const isDeliveryActive = document.getElementById('so-is-delivery')?.checked;
                    const isOther = document.getElementById('so-delivery-is-other-address')?.checked;
                    const custId = $('#so-customer-select').val();
                    if (isDeliveryActive && !isOther && custId) {
                        const cust = await this.getCustomerData(custId);
                        if (cust) {
                            this.fillSoDeliveryWithCustomer(cust);
                        }
                    }
                });
            }

            this.onSpecialOrderPaymentMethodChange();
            this.updateSpecialOrderSummary();

            if (!this._soModal) this._soModal = new bootstrap.Modal(document.getElementById('modal-special-order'));
            this._soModal.show();
        },

        onSpecialOrderPaymentMethodChange: function() {
            const pmId = document.getElementById('so-payment-method').value;
            const pm   = this.paymentMethods.find(m => m.id == pmId);

            const terminalCol    = document.getElementById('so-terminal-col');
            const terminalSelect = document.getElementById('so-payment-terminal');

            if (pm && (pm.type === 'tarjeta' || pm.type === 'transferencia' || (pm.name && pm.name.toLowerCase().includes('tarjeta')))) {
                if (terminalCol) terminalCol.style.display = 'block';
                let termOptions = '<option value="">— Selecciona Terminal —</option>';
                this.paymentTerminals.forEach(term => {
                    termOptions += `<option value="${term.id}">${term.name}</option>`;
                });
                if (terminalSelect) terminalSelect.innerHTML = termOptions;
            } else {
                if (terminalCol) terminalCol.style.display = 'none';
                if (terminalSelect) {
                    terminalSelect.innerHTML = '<option value="">— N/A —</option>';
                    terminalSelect.value     = '';
                }
            }

            this.updateSpecialOrderSummary();
        },

        onSpecialOrderTerminalChange: function() {
            const termSelect = document.getElementById('so-payment-terminal');
            if (!termSelect) return;

            const termId = parseInt(termSelect.value);
            if (!termId) {
                this.updateSpecialOrderSummary();
                return;
            }

            const pmId   = parseInt(document.getElementById('so-payment-method').value);
            const method = this.paymentMethods.find(m => m.id === pmId);
            const terminal = this.paymentTerminals.find(t => t.id === termId);

            if (terminal && method) {
                const isAssigned = terminal.payment_methods ? terminal.payment_methods.some(pm => pm.id === method.id) : false;
                if (!isAssigned) {
                    Swal.fire({
                        title: 'Terminal No Asignada',
                        text: `Esta terminal (${terminal.name}) no tiene asignado el método (${method.name}) por defecto. ¿Deseas continuar bajo tu propia responsabilidad?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Cambiar terminal',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            termSelect.value = '';
                            this.updateSpecialOrderSummary();
                        } else {
                            this.updateSpecialOrderSummary();
                        }
                    });
                    return;
                }
            }
            this.updateSpecialOrderSummary();
        },

        fillSoDeliveryWithCustomer: function(cust) {
            if (!cust) return;
            const recInput = document.getElementById('so-del-recipient');
            const phoneInput = document.getElementById('so-del-phone');
            const streetInput = document.getElementById('so-del-street');
            const zipInput = document.getElementById('so-del-zip');
            const coloniaInput = document.getElementById('so-del-colonia');
            const cityInput = document.getElementById('so-del-city');
            const stateInput = document.getElementById('so-del-state');
            const refInput = document.getElementById('so-del-references');
            const instInput = document.getElementById('so-del-instructions');
            const statusText = document.getElementById('so-delivery-address-status-text');

            if (recInput) recInput.value = cust.recipient_name || cust.name || '';
            if (phoneInput) phoneInput.value = cust.phone || '';
            if (streetInput) streetInput.value = cust.street || cust.legal_address || '';
            if (zipInput) zipInput.value = cust.zip || '';
            if (coloniaInput) coloniaInput.value = cust.colonia || '';
            if (cityInput) cityInput.value = cust.city || 'Morelia';
            if (stateInput) stateInput.value = cust.state || 'Michoacán';
            if (refInput) refInput.value = cust.references || '';
            if (instInput) instInput.value = cust.instructions || '';

            if (cust.lat && cust.lng) {
                this.updateSoDeliveryCoords(cust.lat, cust.lng);
                if (this._soDeliveryMap) {
                    const pos = { lat: cust.lat, lng: cust.lng };
                    this._soDeliveryMap.panTo(pos);
                    if (this._soDeliveryMarker) {
                        if (this._soDeliveryMarker.position !== undefined) this._soDeliveryMarker.position = pos;
                        else if (typeof this._soDeliveryMarker.setPosition === 'function') this._soDeliveryMarker.setPosition(pos);
                    }
                }
            } else {
                const latInput = document.getElementById('so-del-lat');
                const lngInput = document.getElementById('so-del-lng');
                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';
                const badge = document.getElementById('so-delivery-coords-badge');
                if (badge) {
                    badge.innerText = 'Pin: Sin fijar';
                    badge.className = 'badge bg-light text-secondary border';
                }
                const link = document.getElementById('so-delivery-maps-link');
                if (link) link.classList.add('d-none');
            }

            if (statusText) {
                const label = cust.name || cust.recipient_name || 'Cliente';
                statusText.innerHTML = `<span class="text-success"><i class="ri-check-line me-1"></i>Datos habituales autorrellenados de <strong>${label}</strong></span>`;
            }
        },

        toggleSoDeliveryOtherAddress: async function(isOther) {
            const statusText = document.getElementById('so-delivery-address-status-text');
            const custId = $('#so-customer-select').val() || $('#customer_select').val();

            if (isOther) {
                if (statusText) {
                    statusText.innerHTML = '<span class="text-warning"><i class="ri-edit-line me-1"></i>Envío a otro domicilio: Captura los datos de destino manualmente.</span>';
                }
                const street = document.getElementById('so-del-street');
                if (street) street.value = '';
                const zip = document.getElementById('so-del-zip');
                if (zip) zip.value = '';
                const col = document.getElementById('so-del-colonia');
                if (col) col.value = '';
                const city = document.getElementById('so-del-city');
                if (city) city.value = 'Morelia';
                const st = document.getElementById('so-del-state');
                if (st) st.value = 'Michoacán';
                const ref = document.getElementById('so-del-references');
                if (ref) ref.value = '';
                const inst = document.getElementById('so-del-instructions');
                if (inst) inst.value = '';
                const lat = document.getElementById('so-del-lat');
                if (lat) lat.value = '';
                const lng = document.getElementById('so-del-lng');
                if (lng) lng.value = '';

                const badge = document.getElementById('so-delivery-coords-badge');
                if (badge) {
                    badge.innerText = 'Pin: Sin fijar';
                    badge.className = 'badge bg-light text-secondary border';
                }
                const link = document.getElementById('so-delivery-maps-link');
                if (link) link.classList.add('d-none');
            } else {
                if (custId) {
                    const cust = await this.getCustomerData(custId);
                    if (cust) {
                        this.fillSoDeliveryWithCustomer(cust);
                    }
                } else if (statusText) {
                    statusText.innerText = 'Por defecto se autorrellenan los datos y coordenadas habituales del cliente.';
                }
            }
        },

        toggleSoDelivery: async function(checked) {
            const fields = document.getElementById('so-delivery-fields');
            const badge  = document.getElementById('so-delivery-badge');
            if (fields) fields.style.display = checked ? 'block' : 'none';
            if (badge)  badge.style.display  = checked ? 'inline-block' : 'none';
            if (checked) {
                const isOther = document.getElementById('so-delivery-is-other-address')?.checked;
                if (!isOther) {
                    const custId = $('#so-customer-select').val() || $('#customer_select').val();
                    if (custId) {
                        const cust = await this.getCustomerData(custId);
                        if (cust) {
                            this.fillSoDeliveryWithCustomer(cust);
                        }
                    }
                }
                setTimeout(() => {
                    this.initSoDeliveryMap();
                }, 350);
            }
            this.updateSpecialOrderSummary();
        },

        initSoDeliveryMap: async function() {
            const mapContainer = document.getElementById('so-delivery-map');
            if (!mapContainer) return;

            let curLat = parseFloat(document.getElementById('so-del-lat')?.value) || this.storeDefaultLat || 19.2433880;
            let curLng = parseFloat(document.getElementById('so-del-lng')?.value) || this.storeDefaultLng || -103.7250940;
            const center = { lat: curLat, lng: curLng };

            try {
                if (typeof google === 'undefined' || !google.maps) {
                    mapContainer.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted small p-3 text-center"><i class="ri-map-pin-line fs-4 me-2 text-warning"></i><span>API de Google Maps no conectada. Puedes ingresar las coordenadas manualmente.</span></div>';
                    return;
                }

                const { Map } = await google.maps.importLibrary("maps");
                let AdvancedMarkerElement = null;
                try {
                    const markerLib = await google.maps.importLibrary("marker");
                    AdvancedMarkerElement = markerLib.AdvancedMarkerElement;
                } catch(e) {}

                this._soDeliveryMap = new Map(mapContainer, {
                    center: center,
                    zoom: 15,
                    mapId: 'so_delivery_order_map',
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                });

                if (AdvancedMarkerElement) {
                    this._soDeliveryMarker = new AdvancedMarkerElement({
                        position: center,
                        map: this._soDeliveryMap,
                        gmpDraggable: true,
                        title: 'Punto de Entrega Pedido Especial'
                    });
                    this._soDeliveryMarker.addListener('dragend', () => {
                        const pos = this._soDeliveryMarker.position;
                        this.updateSoDeliveryCoords(pos.lat, pos.lng);
                    });
                } else {
                    this._soDeliveryMarker = new google.maps.Marker({
                        position: center,
                        map: this._soDeliveryMap,
                        draggable: true,
                        title: 'Punto de Entrega Pedido Especial'
                    });
                    this._soDeliveryMarker.addListener('dragend', (e) => {
                        this.updateSoDeliveryCoords(e.latLng.lat(), e.latLng.lng());
                    });
                }

                this._soDeliveryMap.addListener('click', (e) => {
                    const lat = e.latLng.lat();
                    const lng = e.latLng.lng();
                    if (AdvancedMarkerElement && this._soDeliveryMarker) {
                        this._soDeliveryMarker.position = { lat, lng };
                    } else if (this._soDeliveryMarker) {
                        this._soDeliveryMarker.setPosition(e.latLng);
                    }
                    this.updateSoDeliveryCoords(lat, lng);
                });

                // Escuchadores en inputs manuales de lat/lng
                const latInput = document.getElementById('so-del-lat');
                const lngInput = document.getElementById('so-del-lng');
                const onManualCoordChange = () => {
                    const mLat = parseFloat(latInput.value);
                    const mLng = parseFloat(lngInput.value);
                    if (!isNaN(mLat) && !isNaN(mLng)) {
                        const newPos = { lat: mLat, lng: mLng };
                        if (this._soDeliveryMap) this._soDeliveryMap.panTo(newPos);
                        if (AdvancedMarkerElement && this._soDeliveryMarker) this._soDeliveryMarker.position = newPos;
                        else if (this._soDeliveryMarker) this._soDeliveryMarker.setPosition(newPos);
                        this.updateSoDeliveryCoords(mLat, mLng, false);
                    }
                };
                if (latInput) latInput.onchange = onManualCoordChange;
                if (lngInput) lngInput.onchange = onManualCoordChange;

                if (document.getElementById('so-del-lat')?.value && document.getElementById('so-del-lng')?.value) {
                    this.updateSoDeliveryCoords(curLat, curLng, false);
                }

            } catch (err) {
                console.error("Error inicializando mapa de entrega en Pedido Especial:", err);
                mapContainer.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted small p-3 text-center"><i class="ri-alert-line fs-4 me-2 text-warning"></i><span>No se pudo cargar el mapa. Puedes ingresar dirección y coordenadas manualmente.</span></div>';
            }
        },

        updateSoDeliveryCoords: function(lat, lng, updateInputs = true) {
            lat = typeof lat === 'number' ? lat : parseFloat(lat);
            lng = typeof lng === 'number' ? lng : parseFloat(lng);
            if (isNaN(lat) || isNaN(lng)) return;

            if (updateInputs) {
                const latInput = document.getElementById('so-del-lat');
                const lngInput = document.getElementById('so-del-lng');
                if (latInput) latInput.value = lat.toFixed(7);
                if (lngInput) lngInput.value = lng.toFixed(7);
            }

            const badge = document.getElementById('so-delivery-coords-badge');
            if (badge) {
                badge.innerText = `Pin: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                badge.className = 'badge bg-success-subtle text-success border border-success';
            }

            const link = document.getElementById('so-delivery-maps-link');
            if (link) {
                link.href = `https://www.google.com/maps?q=${lat.toFixed(7)},${lng.toFixed(7)}`;
                link.classList.remove('d-none');
            }
        },

        locateSoDeliveryAddressOnMap: async function() {
            const street = document.getElementById('so-del-street')?.value.trim() || '';
            const colonia = document.getElementById('so-del-colonia')?.value.trim() || '';
            const city = document.getElementById('so-del-city')?.value.trim() || '';
            const state = document.getElementById('so-del-state')?.value.trim() || '';

            const fullQuery = [street, colonia, city, state, 'México'].filter(Boolean).join(', ');
            if (!fullQuery || fullQuery === 'México') {
                Swal.fire('Atención', 'Ingresa al menos la calle, colonia o ciudad para ubicar en el mapa.', 'info');
                return;
            }

            try {
                if (typeof google === 'undefined' || !google.maps) {
                    Swal.fire('Aviso', 'El servicio de Google Maps no está disponible en este momento.', 'warning');
                    return;
                }

                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: fullQuery }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        const loc = results[0].geometry.location;
                        const lat = loc.lat();
                        const lng = loc.lng();

                        if (this._soDeliveryMap) {
                            this._soDeliveryMap.setCenter(loc);
                            this._soDeliveryMap.setZoom(16);
                        }
                        if (this._soDeliveryMarker) {
                            if (this._soDeliveryMarker.position !== undefined) {
                                this._soDeliveryMarker.position = { lat, lng };
                            } else if (typeof this._soDeliveryMarker.setPosition === 'function') {
                                this._soDeliveryMarker.setPosition(loc);
                            }
                        }
                        this.updateSoDeliveryCoords(lat, lng);
                    } else {
                        Swal.fire('No encontrado', 'No se encontró la ubicación exacta en el mapa. Puedes arrastrar el marcador manualmente al punto deseado.', 'warning');
                    }
                });
            } catch (e) {
                console.error("Geocoding error en pedido especial:", e);
                Swal.fire('Error', 'No se pudo geocodificar la dirección.', 'error');
            }
        },

        updateSpecialOrderSummary: function() {
            const qty     = parseInt(document.getElementById('so-quantity').value) || 1;
            const price   = parseFloat(document.getElementById('so-unit-price').value) || 0;
            const dep     = parseFloat(document.getElementById('so-deposit').value)  || 0;
            const isDel   = document.getElementById('so-is-delivery')?.checked || false;
            const delCost = isDel ? (parseFloat(document.getElementById('so-del-cost')?.value) || 0) : 0;
            const tot     = (qty * price) + delCost;

            const minDep = Math.round(tot * 0.20 * 100) / 100;
            document.getElementById('so-deposit-hint').innerText = `Mínimo obligatorio (20%): $${minDep.toFixed(2)}`;

            // Calcular comisiones de terminal bancaria si aplica
            let commission = 0;
            const termId   = parseInt(document.getElementById('so-payment-terminal')?.value || 0);
            const terminal = termId ? this.paymentTerminals.find(t => t.id === termId) : null;
            if (terminal) {
                const commRate = parseFloat(terminal.commission_percentage || 0);
                if (commRate > 0) commission = Math.round(dep * (commRate / 100) * 100) / 100;
            }

            const commRow   = document.getElementById('so-commission-row');
            const todayRow  = document.getElementById('so-today-total-row');
            const todayDisp = document.getElementById('so-today-total-display');
            const commDisp  = document.getElementById('so-commission-display');

            if (commission > 0) {
                if (commRow) commRow.style.display = 'flex';
                if (commDisp) commDisp.innerText = `+$${commission.toFixed(2)}`;
                if (todayRow) todayRow.style.display = 'flex';
                if (todayDisp) todayDisp.innerText = `$${(dep + commission).toFixed(2)}`;
            } else {
                if (commRow) commRow.style.display = 'none';
                if (todayRow) todayRow.style.display = 'none';
            }

            document.getElementById('so-total-display').innerText   = `$${tot.toFixed(2)}`;
            document.getElementById('so-deposit-display').innerText = `$${dep.toFixed(2)}`;
            document.getElementById('so-balance-display').innerText = `$${Math.max(0, tot - dep).toFixed(2)}`;
            document.getElementById('so-summary').classList.remove('d-none');

            // Validar anticipo mínimo del 20%
            const btnSubmit = document.getElementById('btn-create-special-order');
            const errorDiv  = document.getElementById('so-deposit-error');
            const depInput  = document.getElementById('so-deposit');

            if (dep < (minDep - 0.01)) {
                if (depInput) depInput.classList.add('is-invalid');
                if (errorDiv) {
                    errorDiv.classList.remove('d-none');
                    errorDiv.innerText = `El anticipo ingresado ($${dep.toFixed(2)}) es inferior al 20% mínimo obligatorio ($${minDep.toFixed(2)}).`;
                }
                if (btnSubmit) btnSubmit.disabled = true;
            } else {
                if (depInput) depInput.classList.remove('is-invalid');
                if (errorDiv) errorDiv.classList.add('d-none');
                if (btnSubmit) btnSubmit.disabled = false;
            }
        },

        submitSpecialOrder: async function() {
            if (!cashShiftManager.hasActiveShift) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Caja Cerrada',
                    text: 'Debes abrir tu turno de caja con el fondo inicial antes de registrar anticipos de pedidos especiales.',
                    confirmButtonText: 'Abrir Caja Ahora',
                    confirmButtonColor: '#198754'
                }).then(result => {
                    if (result.isConfirmed) cashShiftManager.openShiftModal();
                });
                return;
            }

            const customerId = document.getElementById('so-customer-select').value;
            const qty        = parseInt(document.getElementById('so-quantity').value)    || 0;
            const unitPrice  = parseFloat(document.getElementById('so-unit-price').value) || 0;
            const unitCost   = parseFloat(document.getElementById('so-unit-cost')?.value) || 0;
            const customName = document.getElementById('so-custom-name')?.value.trim() || null;
            const deposit    = parseFloat(document.getElementById('so-deposit').value)    || 0;
            const pmId       = document.getElementById('so-payment-method').value;
            const termId     = document.getElementById('so-payment-terminal')?.value || null;
            const notes      = document.getElementById('so-notes').value.trim();
            const expDate    = document.getElementById('so-expected-date').value;

            const isWildcard = !!(this._soProduct?.is_wildcard || this._soProduct?.type === 'personalizado');

            if (isWildcard && !customName) {
                Swal.fire('Atención', 'Ingresa el nombre o título del producto personalizado.', 'warning');
                return;
            }

            if (!customerId) { Swal.fire('Requerido', 'Selecciona un cliente para el pedido especial.', 'warning'); return; }
            if (!qty || !unitPrice) { Swal.fire('Requerido', 'Ingresa cantidad y precio unitario.', 'warning'); return; }

            const isDel   = document.getElementById('so-is-delivery')?.checked || false;
            const delCost = isDel ? (parseFloat(document.getElementById('so-del-cost')?.value) || 0) : 0;
            const total   = (qty * unitPrice) + delCost;
            const minDep  = Math.round(total * 0.20 * 100) / 100;

            if (deposit < (minDep - 0.01)) {
                Swal.fire('Anticipo Insuficiente', `El anticipo mínimo requerido (20%) considerando flete es de $${minDep.toFixed(2)}.`, 'warning');
                return;
            }

            const btn = document.getElementById('btn-create-special-order');
            btn.disabled = true; btn.innerText = 'Guardando...';

            try {
                const payload = {
                    product_id:         this._soProduct?.id,
                    product_variant_id: this._soProduct?.variant_id || null,
                    customer_id:        customerId,
                    quantity:           qty,
                    unit_price:         unitPrice,
                    unit_cost:          unitCost,
                    custom_name:        customName,
                    amount_paid:        deposit,
                    payment_method_id:  pmId || null,
                    payment_terminal_id: termId || null,
                    notes:              notes,
                    expected_delivery_date: expDate || null,
                    store_id:           document.getElementById('store_select').value,
                };

                if (isDel) {
                    payload.is_delivery = true;
                    payload.delivery_recipient_name = document.getElementById('so-del-recipient')?.value.trim() || null;
                    payload.delivery_phone = document.getElementById('so-del-phone')?.value.trim() || null;

                    const street = document.getElementById('so-del-street')?.value.trim() || '';
                    const colonia = document.getElementById('so-del-colonia')?.value.trim() || '';
                    const zip = document.getElementById('so-del-zip')?.value.trim() || '';
                    const city = document.getElementById('so-del-city')?.value.trim() || '';
                    const state = document.getElementById('so-del-state')?.value.trim() || '';
                    const fullAddr = [street, colonia, zip ? `C.P. ${zip}` : '', city, state].filter(Boolean).join(', ');

                    payload.delivery_address = fullAddr || null;
                    payload.delivery_references = document.getElementById('so-del-references')?.value.trim() || null;
                    payload.delivery_cost = delCost;
                    const soTime = document.getElementById('so-del-time')?.value || '11:00';
                    payload.delivery_date = document.getElementById('so-del-date')?.value || expDate || null;
                    payload.delivery_time = soTime || null;
                    payload.delivery_time_slot = this.formatTime12h(soTime) || null;
                    payload.delivery_instructions = document.getElementById('so-del-instructions')?.value.trim() || null;

                    const latVal = parseFloat(document.getElementById('so-del-lat')?.value);
                    const lngVal = parseFloat(document.getElementById('so-del-lng')?.value);
                    if (!isNaN(latVal) && !isNaN(lngVal)) {
                        payload.delivery_lat = latVal;
                        payload.delivery_lng = lngVal;
                        payload.delivery_maps_url = `https://www.google.com/maps?q=${latVal},${lngVal}`;
                    }
                }

                const res = await axios.post('/api/pos/special-order', payload);
                if (res.data.success) {
                    this._soModal.hide();
                    let htmlMsg = `Folio: <strong>${res.data.folio}</strong><br>Balance pendiente: <strong>$${parseFloat(res.data.balance_due).toFixed(2)}</strong>`;
                    if (res.data.is_delivery) {
                        htmlMsg += `<br><span class="badge bg-primary mt-2"><i class="ri-truck-line me-1"></i> Programado para Entrega a Domicilio</span>`;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: '¡Pedido Especial Creado!',
                        html: htmlMsg,
                        showCancelButton: !!(res.data.is_delivery && res.data.delivery_order_url),
                        confirmButtonText: 'Aceptar',
                        cancelButtonText: '<i class="ri-printer-line me-1"></i> Imprimir Orden Entrega',
                        cancelButtonColor: '#2563eb'
                    }).then((r) => {
                        if (r.dismiss === Swal.DismissReason.cancel && res.data.delivery_order_url) {
                            window.open(res.data.delivery_order_url, '_blank');
                        }
                    });
                } else {
                    Swal.fire('Error', res.data.message || 'No se pudo crear el pedido.', 'error');
                }
            } catch(e) {
                Swal.fire('Error', e.response?.data?.message || 'Error de red.', 'error');
            } finally {
                btn.disabled = false; btn.innerText = 'Crear Pedido Especial';
            }
        },

        checkout: async function() {
            if (!cashShiftManager.hasActiveShift) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Caja Cerrada',
                    text: 'Debes abrir tu turno de caja con el fondo inicial antes de registrar ventas o cobros.',
                    confirmButtonText: 'Abrir Caja Ahora',
                    confirmButtonColor: '#198754'
                }).then(result => {
                    if (result.isConfirmed) cashShiftManager.openShiftModal();
                });
                return;
            }

            if (this.items.length === 0) {
                Swal.fire('Carrito vacío', 'Agrega productos antes de cobrar.', 'warning');
                return;
            }

            // Descuento de cupón si aplica
            let couponDiscount = 0;
            if (this.appliedCoupon) {
                if (this.appliedCoupon.discount_type === 'percentage') {
                    couponDiscount = (this.cartTotal * (parseFloat(this.appliedCoupon.discount_value) / 100));
                    if (this.appliedCoupon.max_discount_amount && couponDiscount > parseFloat(this.appliedCoupon.max_discount_amount)) {
                        couponDiscount = parseFloat(this.appliedCoupon.max_discount_amount);
                    }
                } else {
                    couponDiscount = Math.min(this.cartTotal, parseFloat(this.appliedCoupon.discount_value));
                }
            }
            const netCartTotal = Math.max(0, this.cartTotal - couponDiscount);

            // Auto-garantizar método de pago si el carrito tiene total y no hay pagos configurados
            if (this.payments.length === 0 && netCartTotal > 0) {
                const cashMethod = this.paymentMethods.find(pm => pm.name.toLowerCase().includes('efectivo'));
                const defaultMethodId = cashMethod ? cashMethod.id : (this.paymentMethods[0]?.id || null);
                if (defaultMethodId) {
                    this.payments = [{
                        payment_method_id: defaultMethodId,
                        payment_terminal_id: null,
                        amount: netCartTotal,
                        msi_applied: 0,
                        reference: ''
                    }];
                }
            } else if (!this.hasCustomPayments && this.payments.length === 1 && (!this.payments[0].amount || this.payments[0].amount <= 0)) {
                this.payments[0].amount = netCartTotal;
            }

            // Calcular totales actuales de los pagos
            let totalInterest = 0;
            let totalCommissions = 0;
            let totalAmountPaid = 0;

            this.payments.forEach(p => {
                const pm = this.paymentMethods.find(m => m.id === p.payment_method_id);
                if (!pm) return;

                let rowInterest = 0;
                let rowCommission = 0;

                if (p.msi_applied > 0 && !this.msiCampaignActive && pm.accepts_financing && pm.financing_rates) {
                    const rates = typeof pm.financing_rates === 'string' ? JSON.parse(pm.financing_rates) : pm.financing_rates;
                    const rateObj = rates ? rates.find(r => r.months == p.msi_applied) : null;
                    const rate = rateObj ? parseFloat(rateObj.rate) : 0;
                    rowInterest = p.amount * (rate / 100);
                }

                totalInterest += rowInterest;
                const basePlusInt = p.amount + rowInterest;

                if (p.payment_terminal_id) {
                    const terminal = this.paymentTerminals.find(t => t.id === p.payment_terminal_id);
                    if (terminal) {
                        const fixed = parseFloat(terminal.commission_fixed) || 0;
                        const percent = parseFloat(terminal.commission_percentage) || 0;
                        
                        rowCommission = fixed + (basePlusInt * (percent / 100));

                        if (terminal.charge_client) {
                            totalCommissions += rowCommission;
                            totalAmountPaid += basePlusInt + rowCommission;
                        } else {
                            totalAmountPaid += basePlusInt;
                        }
                    } else {
                        totalAmountPaid += basePlusInt;
                    }
                } else {
                    totalAmountPaid += basePlusInt;
                }
            });

            const grandTotal = netCartTotal + totalInterest + totalCommissions;
            const isLayaway = totalAmountPaid < grandTotal;

            if (isLayaway) {
                const confirm = await Swal.fire({
                    title: 'Falta dinero para cubrir el total',
                    text: `El total abonado ($${totalAmountPaid.toFixed(2)}) es menor al total requerido de la venta ($${grandTotal.toFixed(2)}). ¿Deseas proceder? Esta venta será considerada un APARTADO y los pagos brindados serán el anticipo inicial.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, registrar Apartado',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false
                });
                if (!confirm.isConfirmed) return;
            } else {
                const confirm = await Swal.fire({
                    title: 'Confirmar Venta',
                    text: '¿Deseas procesar esta transacción?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cobrar'
                });
                if (!confirm.isConfirmed) return;
            }

            // Procesar
            Swal.fire({title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
            
            try {
                const payload = {
                    items: this.items,
                    customer_id: $('#customer_select').val() || document.getElementById('customer_select')?.value || null,
                    seller_id: $('#seller_select').val() || document.getElementById('seller_select')?.value || null,
                    payments: this.payments,
                    is_layaway: isLayaway,
                    store_id: document.getElementById('store_select').value,
                    delivery: this.deliveryData || null,
                    coupon_code: this.appliedCoupon ? this.appliedCoupon.code : null,
                    coupon_id: this.appliedCoupon ? this.appliedCoupon.id : null,
                    quote_id: this.loadedQuoteId || null
                };

                const res = await axios.post(posApiRoutes.checkout, payload);
                if (res.data.success) {
                    Swal.close();
                    
                    // Llenar datos del Modal de éxito
                    document.getElementById('success-folio').innerText = res.data.ticket_folio;
                    document.getElementById('success-total').innerText = `$${grandTotal.toFixed(2)}`;
                    document.getElementById('success-paid').innerText = `$${totalAmountPaid.toFixed(2)}`;
                    
                    if (!isLayaway && totalAmountPaid > grandTotal) {
                        const change = totalAmountPaid - grandTotal;
                        document.getElementById('success-change').innerText = `$${change.toFixed(2)}`;
                        document.getElementById('success-change-wrapper').classList.remove('d-none');
                    } else {
                        document.getElementById('success-change-wrapper').classList.add('d-none');
                    }
                    
                    // Configurar botón de imprimir ticket
                    document.getElementById('btn-print-ticket').onclick = () => {
                        window.open(`/pos/sales/${res.data.sale_id}/print`, '_blank');
                    };

                    // Configurar botón de imprimir orden de entrega si aplica
                    const btnDeliveryOrder = document.getElementById('btn-print-delivery-order');
                    if (res.data.is_delivery && res.data.delivery_order_url) {
                        btnDeliveryOrder.classList.remove('d-none');
                        btnDeliveryOrder.onclick = () => {
                            window.open(res.data.delivery_order_url, '_blank');
                        };
                    } else {
                        btnDeliveryOrder.classList.add('d-none');
                    }

                    // Mostrar botón de Retomar Venta en Espera si hay ventas pausadas en esta sucursal
                    const btnRestoreParked = document.getElementById('btn-restore-last-parked');
                    const storeParked = this.getStoreParkedSales();
                    if (btnRestoreParked) {
                        if (storeParked.length > 0) {
                            btnRestoreParked.classList.remove('d-none');
                            btnRestoreParked.innerHTML = `<i class="ri-play-circle-line align-middle me-1"></i> Retomar Venta en Espera (${storeParked.length})`;
                        } else {
                            btnRestoreParked.classList.add('d-none');
                        }
                    }

                    // Mostrar Modal de éxito
                    const successModal = new bootstrap.Modal(document.getElementById('modal-success'));
                    successModal.show();
                    
                    // Limpiar Carrito, pagos, cotización vinculada y envío
                    this.resetCurrentCartSilent();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo completar la venta',
                        text: res.data.message || 'Ocurrió un error inesperado al procesar la venta.',
                        confirmButtonColor: '#d33'
                    });
                }
            } catch (error) {
                console.error('Error al procesar checkout:', error);

                // Validación de Línea de Crédito Insuficiente en Apartados (Requiere OTP de Superior)
                if (error.response?.data?.requires_credit_otp) {
                    Swal.fire({
                        title: 'Línea de Crédito Insuficiente',
                        html: `El saldo pendiente de este apartado (<strong>$${parseFloat(error.response.data.required_credit || 0).toFixed(2)}</strong>) excede la línea de crédito disponible del cliente ($${parseFloat(error.response.data.available_credit || 0).toFixed(2)}).<br><br>¿Deseas solicitar autorización con código OTP de un superior para continuar?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<i class="ri-key-2-line me-1"></i> Autorizar con OTP de Superior',
                        cancelButtonText: 'Cancelar Operación',
                        confirmButtonColor: '#d97706'
                    }).then((res) => {
                        if (res.isConfirmed) {
                            posCart.promptCreditOtp();
                        }
                    });
                    return;
                }

                const errorMsg = error.response?.data?.message 
                              || error.response?.data?.error 
                              || error.message 
                              || 'Hubo un problema al procesar el cobro.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error al procesar la venta',
                    text: errorMsg,
                    confirmButtonColor: '#d33'
                });
            }
        },

        promptCreditOtp: function() {
            Swal.fire({
                title: 'Autorización OTP de Superior',
                text: 'Ingresa el código OTP de 6 dígitos emitido para supervisión:',
                input: 'text',
                inputAttributes: {
                    maxlength: 6,
                    autocapitalize: 'off',
                    autocorrect: 'off',
                    placeholder: '123456'
                },
                showCancelButton: true,
                confirmButtonText: 'Validar y Continuar',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: async (otpCode) => {
                    if (!otpCode || otpCode.length < 6) {
                        Swal.showValidationMessage('El código OTP debe ser de 6 dígitos.');
                        return false;
                    }
                    try {
                        const response = await fetch('/user/profile/validate-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ otp_code: otpCode })
                        });
                        const data = await response.json();
                        if (!data.success) {
                            throw new Error(data.message || 'Código OTP incorrecto o expirado.');
                        }
                        return data;
                    } catch (err) {
                        Swal.showValidationMessage(`Error: ${err.message}`);
                    }
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: '¡Autorizado por Superior!',
                        text: 'Línea de crédito autorizada temporalmente para esta operación.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        this.checkout();
                    });
                }
            });
        },

        startNewSale: function() {
            this.deliveryData = null;
            localStorage.removeItem('pos_delivery_data');
            this.updateDeliveryUI();
            document.getElementById('pos-search').focus();
        },

        // ────────────────────────────────────────────────────────
        // GESTIÓN DE VENTAS EN ESPERA (PARKED SALES) Y COTIZACIONES
        // ────────────────────────────────────────────────────────

        hasActiveCartState: function() {
            return (this.items && this.items.length > 0) ||
                   !!this.deliveryData ||
                   !!this.appliedCoupon ||
                   (this.hasCustomPayments && this.payments && this.payments.length > 0);
        },

        getStoreParkedSales: function() {
            const storeId = String(this.currentStoreId || document.getElementById('store_select')?.value || '');
            return (this.parkedSales || []).filter(p => !p.store_id || String(p.store_id) === storeId);
        },

        ensureCustomerOptionExists: function(customerId, customerName) {
            if (!customerId) return;
            const selectEl = document.getElementById('customer_select');
            if (!selectEl) return;
            const exists = Array.from(selectEl.options).some(opt => String(opt.value) === String(customerId));
            if (!exists) {
                const label = customerName || `Cliente #${customerId}`;
                const newOpt = new Option(label, customerId, false, false);
                $('#customer_select').append(newOpt);
            }
        },

        updateLoadedQuoteUI: function() {
            const badge = document.getElementById('loaded-quote-badge');
            const folioEl = document.getElementById('loaded-quote-folio-text');
            if (!badge || !folioEl) return;

            if (this.loadedQuoteId) {
                folioEl.innerText = this.loadedQuoteFolio || ('COT-' + this.loadedQuoteId);
                badge.classList.remove('d-none');
            } else {
                folioEl.innerText = '';
                badge.classList.add('d-none');
            }
        },

        unlinkLoadedQuote: function() {
            this.loadedQuoteId = null;
            this.loadedQuoteFolio = null;
            localStorage.removeItem('pos_loaded_quote');
            this.updateLoadedQuoteUI();
            this.saveToStorage();
        },

        resetCurrentCartSilent: function() {
            this._skipInitialSelectRestore = true;
            this.items = [];
            this.payments = [];
            this.hasCustomPayments = false;
            this.deliveryData = null;
            this.appliedCoupon = null;
            this.loadedQuoteId = null;
            this.loadedQuoteFolio = null;
            this.cartTotal = 0;
            localStorage.removeItem('pos_delivery_data');
            localStorage.removeItem('pos_cart_payments');
            localStorage.removeItem('pos_has_custom_payments');
            localStorage.removeItem('pos_customer_id');
            localStorage.removeItem('pos_customer_name');
            localStorage.removeItem('pos_applied_coupon');
            localStorage.removeItem('pos_loaded_quote');
            $('#customer_select').val('').trigger('change');
            this.updateCouponUI();
            this.updateLoadedQuoteUI();
            this.saveToStorage();
            this.recalculate();
            this.updateDeliveryUI();
        },

        createSnapshotFromCurrentCart: function(customLabel = null) {
            const custId = $('#customer_select').val() || localStorage.getItem('pos_customer_id') || '';
            const custText = custId
                ? ($('#customer_select option:selected').text().trim() || localStorage.getItem('pos_customer_name') || 'Cliente Registrado')
                : 'Público en General';

            const sellerId = $('#seller_select').val() || localStorage.getItem('pos_seller_id') || '';
            const sellerText = sellerId
                ? ($('#seller_select option:selected').text().trim() || 'Vendedor')
                : 'Vendedor';

            const calcTotal = this.items.reduce((sum, it) => sum + ((parseFloat(it.price) || 0) * (parseInt(it.quantity) || 1)), 0);
            const couponDisc = this.getCouponDiscountAmount();
            const netTotal = Math.max(0, (this.cartTotal > 0 ? this.cartTotal : calcTotal) - couponDisc);

            const now = new Date();
            const timeStr = now.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
            const dateStr = now.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit' });

            return {
                parked_id: 'PARK-' + Date.now() + '-' + Math.random().toString(36).substring(2, 6).toUpperCase(),
                store_id: this.currentStoreId || document.getElementById('store_select')?.value || null,
                label: customLabel || (this.loadedQuoteFolio ? `Cotización ${this.loadedQuoteFolio}` : custText),
                created_at: `${dateStr} ${timeStr}`,
                items: JSON.parse(JSON.stringify(this.items || [])),
                payments: JSON.parse(JSON.stringify(this.payments || [])),
                hasCustomPayments: !!this.hasCustomPayments,
                deliveryData: this.deliveryData ? JSON.parse(JSON.stringify(this.deliveryData)) : null,
                appliedCoupon: this.appliedCoupon ? JSON.parse(JSON.stringify(this.appliedCoupon)) : null,
                customer_id: custId || null,
                customer_name: custText,
                seller_id: sellerId || null,
                seller_name: sellerText,
                loadedQuoteId: this.loadedQuoteId || null,
                loadedQuoteFolio: this.loadedQuoteFolio || null,
                estimated_total: netTotal
            };
        },

        parkCurrentSaleSilent: function(customLabel = null) {
            if (!this.hasActiveCartState()) return null;
            const snapshot = this.createSnapshotFromCurrentCart(customLabel);
            this.parkedSales.unshift(snapshot);
            localStorage.setItem('pos_parked_sales', JSON.stringify(this.parkedSales));
            this.updateParkedSalesUI();
            return snapshot;
        },

        parkCurrentSalePrompt: function() {
            if (!this.hasActiveCartState()) {
                Swal.fire('Carrito Vacío', 'No hay artículos ni información activa en el carrito para poner en espera.', 'info');
                return;
            }

            const custId = $('#customer_select').val();
            const defaultLabel = this.loadedQuoteFolio
                ? `${this.loadedQuoteFolio} - ${custId ? $('#customer_select option:selected').text().trim() : 'Público en General'}`
                : (custId ? $('#customer_select option:selected').text().trim() : 'Cliente en mostrador');

            Swal.fire({
                title: 'Poner Venta en Espera',
                html: 'Tu venta actual se guardará con todos sus productos, precios, cliente, vendedor, cupón y envío para que puedas atender otra operación o cotización sin perder nada.',
                input: 'text',
                inputLabel: 'Referencia o nota identificadora (opcional):',
                inputValue: defaultLabel,
                inputPlaceholder: 'Ej. Señora del comedor gris / Cotización pendiente...',
                showCancelButton: true,
                confirmButtonText: '<i class="ri-bookmark-line me-1"></i> Guardar en Espera y Limpiar Caja',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f59e0b'
            }).then((res) => {
                if (!res.isConfirmed) return;
                const label = (res.value || '').trim() || defaultLabel;
                this.parkCurrentSaleSilent(label);
                this.resetCurrentCartSilent();

                Swal.fire({
                    title: '¡Venta Puesta en Espera!',
                    text: 'La caja quedó libre. Puedes retomar esta venta en cualquier momento desde el botón "En Espera".',
                    icon: 'success',
                    timer: 2200,
                    showConfirmButton: false
                });
            });
        },

        updateParkedSalesUI: function() {
            const storeParked = this.getStoreParkedSales();
            const badge = document.getElementById('parked-sales-count-badge');
            if (badge) {
                if (storeParked.length > 0) {
                    badge.innerText = storeParked.length;
                    badge.classList.remove('d-none');
                } else {
                    badge.innerText = '0';
                    badge.classList.add('d-none');
                }
            }

            const container = document.getElementById('parked-sales-list-container');
            if (!container) return;

            if (storeParked.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted border rounded bg-light-subtle">
                        <i class="ri-pause-circle-line display-5 text-muted d-block mb-2"></i>
                        <p class="mb-0 fw-semibold">No hay ventas en espera en esta sucursal</p>
                        <small>Cuando pauses una venta o cargues una cotización poniendo tu venta activa en espera, aparecerá aquí.</small>
                    </div>
                `;
                return;
            }

            let html = '';
            storeParked.forEach((p) => {
                const totalUnits = (p.items || []).reduce((acc, it) => acc + (parseInt(it.quantity) || 1), 0);
                const itemsPreview = (p.items || []).slice(0, 3).map(it => `${it.quantity}x ${it.custom_name || it.name}`).join(', ')
                    + ((p.items || []).length > 3 ? ` (+${p.items.length - 3} más)` : '');

                let badges = '';
                if (p.loadedQuoteFolio) {
                    badges += `<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1"><i class="ri-file-list-3-line me-1"></i>${p.loadedQuoteFolio}</span>`;
                }
                if (p.appliedCoupon && p.appliedCoupon.code) {
                    badges += `<span class="badge bg-success-subtle text-success border border-success-subtle me-1"><i class="ri-ticket-2-line me-1"></i>${p.appliedCoupon.code}</span>`;
                }
                if (p.deliveryData && p.deliveryData.is_delivery) {
                    badges += `<span class="badge bg-info-subtle text-info border border-info-subtle me-1"><i class="ri-truck-line me-1"></i>Envío</span>`;
                }
                if (p.hasCustomPayments) {
                    badges += `<span class="badge bg-warning-subtle text-warning border border-warning-subtle me-1"><i class="ri-bank-card-line me-1"></i>Pagos config.</span>`;
                }

                html += `
                    <div class="card border shadow-none mb-0">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                                        <span class="fw-bold text-dark fs-14">${p.label || p.customer_name || 'Venta en Espera'}</span>
                                        <small class="text-muted ms-1"><i class="ri-time-line me-1"></i>${p.created_at || ''}</small>
                                    </div>
                                    <div class="text-muted fs-12 mb-1">
                                        <i class="ri-user-3-line me-1"></i>Cliente: <strong class="text-dark">${p.customer_name || 'Público en General'}</strong>
                                        <span class="mx-1">|</span>
                                        <i class="ri-shopping-bag-line me-1"></i><strong>${totalUnits}</strong> art. (${(p.items || []).length} líneas)
                                    </div>
                                    <div class="text-muted fs-11 text-truncate" style="max-width: 480px;" title="${(itemsPreview || '').replace(/"/g, '&quot;')}">
                                        ${itemsPreview || 'Sin productos'}
                                    </div>
                                    ${badges ? `<div class="mt-2">${badges}</div>` : ''}
                                </div>
                                <div class="text-end">
                                    <div class="fs-16 fw-bold text-primary mb-2">$${parseFloat(p.estimated_total || 0).toFixed(2)}</div>
                                    <div class="d-flex flex-wrap justify-content-end gap-1">
                                        <button type="button" class="btn btn-sm btn-success fw-semibold" onclick="posCart.restoreParkedSale('${p.parked_id}')" title="Cargar esta venta al POS (si tienes algo en pantalla se pondrá en espera automáticamente)">
                                            <i class="ri-play-fill align-middle me-1"></i>Retomar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-soft-primary" onclick="posCart.mergeParkedSaleIntoCurrent('${p.parked_id}')" title="Sumar los productos de esta venta en espera al carrito actual">
                                            <i class="ri-add-box-line align-middle me-1"></i>Fusionar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-soft-danger btn-icon" onclick="posCart.deleteParkedSale('${p.parked_id}')" title="Eliminar venta en espera">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        },

        openParkedSalesModal: function() {
            this.updateParkedSalesUI();
            if (!this._parkedSalesModal) {
                const el = document.getElementById('modal-parked-sales');
                if (el) this._parkedSalesModal = new bootstrap.Modal(el);
            }
            if (this._parkedSalesModal) this._parkedSalesModal.show();
        },

        restoreParkedSale: function(parkedId) {
            const idx = (this.parkedSales || []).findIndex(p => p.parked_id === parkedId);
            if (idx === -1) return;

            const targetSale = this.parkedSales[idx];
            // Remover primero la venta que vamos a restaurar
            this.parkedSales.splice(idx, 1);

            let autoParkedCurrent = false;
            if (this.hasActiveCartState()) {
                this.parkCurrentSaleSilent();
                autoParkedCurrent = true;
            }

            this._skipInitialSelectRestore = true;
            this.items = targetSale.items || [];
            this.payments = targetSale.payments || [];
            this.hasCustomPayments = !!targetSale.hasCustomPayments;
            this.deliveryData = targetSale.deliveryData || null;
            this.appliedCoupon = targetSale.appliedCoupon || null;
            this.loadedQuoteId = targetSale.loadedQuoteId || null;
            this.loadedQuoteFolio = targetSale.loadedQuoteFolio || null;

            if (targetSale.customer_id) {
                this.ensureCustomerOptionExists(targetSale.customer_id, targetSale.customer_name);
                $('#customer_select').val(targetSale.customer_id).trigger('change');
            } else {
                $('#customer_select').val('').trigger('change');
            }

            if (targetSale.seller_id) {
                $('#seller_select').val(targetSale.seller_id).trigger('change');
            }

            this.updateCouponUI();
            this.updateLoadedQuoteUI();
            this.updateParkedSalesUI();
            this.saveToStorage();
            this.render();
            this.recalculate();
            this.updateDeliveryUI();

            if (this._parkedSalesModal) {
                this._parkedSalesModal.hide();
            }

            Swal.fire({
                title: '¡Venta Retomada!',
                html: autoParkedCurrent
                    ? `Se restauró <strong>${targetSale.label || 'la venta en espera'}</strong>.<br><small class="text-muted">La operación que tenías en pantalla se guardó automáticamente en "Ventas en Espera".</small>`
                    : `Se restauró <strong>${targetSale.label || 'la venta en espera'}</strong> en el carrito.`,
                icon: 'success',
                timer: 2400,
                showConfirmButton: false
            });
        },

        restoreMostRecentParkedSale: function() {
            const storeParked = this.getStoreParkedSales();
            if (storeParked.length === 0) return;
            this.restoreParkedSale(storeParked[0].parked_id);
        },

        mergeParkedSaleIntoCurrent: function(parkedId) {
            const idx = (this.parkedSales || []).findIndex(p => p.parked_id === parkedId);
            if (idx === -1) return;

            const targetSale = this.parkedSales[idx];
            this.parkedSales.splice(idx, 1);

            (targetSale.items || []).forEach(pItem => {
                const isVirtual = !!(pItem.is_virtual || pItem.is_wildcard);
                const existing = !isVirtual ? this.items.find(i =>
                    i.id === pItem.id &&
                    i.variant_id === pItem.variant_id &&
                    (i.price_type || 'venta') === (pItem.price_type || 'venta') &&
                    Math.abs(parseFloat(i.price || 0) - parseFloat(pItem.price || 0)) < 0.01
                ) : null;

                if (existing) {
                    existing.quantity = (parseInt(existing.quantity) || 1) + (parseInt(pItem.quantity) || 1);
                } else {
                    this.items.push(pItem);
                }
            });

            if (!$('#customer_select').val() && targetSale.customer_id) {
                this.ensureCustomerOptionExists(targetSale.customer_id, targetSale.customer_name);
                $('#customer_select').val(targetSale.customer_id).trigger('change');
            }
            if (!this.appliedCoupon && targetSale.appliedCoupon) {
                this.appliedCoupon = targetSale.appliedCoupon;
                this.updateCouponUI();
            }
            if (!this.deliveryData && targetSale.deliveryData) {
                this.deliveryData = targetSale.deliveryData;
            }
            if (!this.loadedQuoteId && targetSale.loadedQuoteId) {
                this.loadedQuoteId = targetSale.loadedQuoteId;
                this.loadedQuoteFolio = targetSale.loadedQuoteFolio;
                this.updateLoadedQuoteUI();
            }

            this.updateParkedSalesUI();
            this.saveToStorage();
            this.render();
            this.recalculate();
            this.updateDeliveryUI();

            if (this._parkedSalesModal) {
                this._parkedSalesModal.hide();
            }

            Swal.fire({
                title: '¡Productos Fusionados!',
                text: `Se sumaron los artículos de "${targetSale.label || 'Venta en espera'}" al carrito actual.`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        },

        deleteParkedSale: function(parkedId) {
            const idx = (this.parkedSales || []).findIndex(p => p.parked_id === parkedId);
            if (idx === -1) return;
            const target = this.parkedSales[idx];

            Swal.fire({
                title: '¿Eliminar venta en espera?',
                text: `Se descartará "${target.label || 'Venta en espera'}" de la lista de espera.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((res) => {
                if (!res.isConfirmed) return;
                this.parkedSales.splice(idx, 1);
                localStorage.setItem('pos_parked_sales', JSON.stringify(this.parkedSales));
                this.updateParkedSalesUI();
            });
        },

        saveQuote: async function() {
            if (this.items.length === 0) {
                Swal.fire('Carrito Vacío', 'Agrega al menos un artículo al carrito para poder generar una cotización.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Guardar Cotización',
                text: '¿Deseas registrar esta cotización formal válida por 30 días?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar cotización',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#4f46e5'
            }).then(async (confirm) => {
                if (!confirm.isConfirmed) return;

                Swal.fire({title: 'Generando cotización...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});

                try {
                    const payload = {
                        store_id: document.getElementById('store_select')?.value || null,
                        items: this.items,
                        customer_id: $('#customer_select').val() || document.getElementById('customer_select')?.value || null,
                        seller_id: $('#seller_select').val() || document.getElementById('seller_select')?.value || null,
                        delivery: this.deliveryData || null,
                        coupon_code: this.appliedCoupon ? this.appliedCoupon.code : null,
                        coupon_id: this.appliedCoupon ? this.appliedCoupon.id : null
                    };

                    const res = await axios.post(posApiRoutes.saveQuote, payload);
                    if (res.data.success) {
                        Swal.fire({
                            title: '¡Cotización Guardada!',
                            html: `Se generó la cotización <strong>${res.data.folio}</strong>.<br>Vigencia de 30 días naturales.`,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: '<i class="ri-printer-line me-1"></i> Imprimir Cotización',
                            cancelButtonText: 'Limpiar y Continuar',
                            confirmButtonColor: '#4f46e5'
                        }).then((choice) => {
                            if (choice.isConfirmed && res.data.print_url) {
                                window.open(res.data.print_url, '_blank');
                            }
                            this.resetCurrentCartSilent();
                        });
                    } else {
                        Swal.fire('Error', res.data.message || 'No se pudo guardar la cotización.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', e.response?.data?.message || 'Error de comunicación al guardar la cotización.', 'error');
                }
            });
        },

        cleanQuoteUrlParam: function() {
            const url = new URL(window.location);
            if (url.searchParams.has('quote_id')) {
                url.searchParams.delete('quote_id');
                window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ''));
            }
        },

        loadQuote: async function(quoteId) {
            Swal.fire({title: 'Cargando cotización...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
            try {
                const res = await axios.get(`${posApiRoutes.compareQuote}/${quoteId}/compare`);
                Swal.close();
                this.cleanQuoteUrlParam();

                if (!res.data || !res.data.success) {
                    Swal.fire('Atención', res.data?.message || 'No se encontró la cotización indicada.', 'warning');
                    return;
                }

                this._pendingQuoteData = res.data;
                this._pendingQuoteLoadMode = 'replace';

                // Verificar si el POS tiene actualmente una venta activa con productos o configuración
                if (this.hasActiveCartState()) {
                    const custId = $('#customer_select').val() || localStorage.getItem('pos_customer_id');
                    const curCustName = custId
                        ? ($('#customer_select option:selected').text().trim() || localStorage.getItem('pos_customer_name') || 'Cliente seleccionado')
                        : 'Público en General';
                    const curUnits = (this.items || []).reduce((acc, it) => acc + (parseInt(it.quantity) || 1), 0);
                    const curTotal = this.cartTotal > 0
                        ? Math.max(0, this.cartTotal - this.getCouponDiscountAmount())
                        : (this.items || []).reduce((acc, it) => acc + ((parseFloat(it.price) || 0) * (parseInt(it.quantity) || 1)), 0);

                    const quoteUnits = (res.data.items || []).reduce((acc, it) => acc + (parseInt(it.quantity) || 1), 0);

                    document.getElementById('qlm-quote-folio').innerText = res.data.folio || ('COT-' + res.data.quote_id);
                    document.getElementById('qlm-quote-folio-card').innerText = res.data.folio || ('COT-' + res.data.quote_id);
                    document.getElementById('qlm-current-customer').innerText = this.loadedQuoteFolio
                        ? `${curCustName} (${this.loadedQuoteFolio})`
                        : curCustName;
                    document.getElementById('qlm-current-items').innerText = `${curUnits} unidad(es)`;
                    document.getElementById('qlm-current-total').innerText = `$${curTotal.toFixed(2)}`;

                    document.getElementById('qlm-quote-customer').innerText = res.data.customer_name || 'Público en General';
                    document.getElementById('qlm-quote-items').innerText = `${quoteUnits} unidad(es)`;
                    document.getElementById('qlm-quote-total').innerText = `$${parseFloat(res.data.quote_total || 0).toFixed(2)}`;

                    if (!this._quoteLoadModeModal) {
                        const modalEl = document.getElementById('modal-quote-load-mode');
                        if (modalEl) this._quoteLoadModeModal = new bootstrap.Modal(modalEl);
                    }
                    if (this._quoteLoadModeModal) {
                        this._quoteLoadModeModal.show();
                        return;
                    }
                }

                this.proceedAfterLoadMode('replace');
            } catch (e) {
                Swal.close();
                this.cleanQuoteUrlParam();
                const errMsg = e.response?.data?.message || 'No se pudo cargar la cotización solicitada.';
                Swal.fire('No se pudo cargar la cotización', errMsg, 'error');
            }
        },

        proceedAfterLoadMode: function(mode) {
            this._pendingQuoteLoadMode = mode || 'replace';

            if (this._quoteLoadModeModal) {
                this._quoteLoadModeModal.hide();
            } else {
                const modeModalEl = document.getElementById('modal-quote-load-mode');
                const inst = modeModalEl ? bootstrap.Modal.getInstance(modeModalEl) : null;
                if (inst) inst.hide();
            }

            const data = this._pendingQuoteData;
            if (!data) return;

            if (data.has_discrepancies && data.discrepancies && data.discrepancies.length > 0) {
                document.getElementById('disc-quote-folio').innerText = data.folio;
                const tbody = document.getElementById('disc-table-body');
                tbody.innerHTML = '';

                data.discrepancies.forEach(d => {
                    const diffSign = d.difference > 0 ? `+$${d.difference.toFixed(2)}` : `-$${Math.abs(d.difference).toFixed(2)}`;
                    const diffColor = d.difference > 0 ? 'text-danger fw-bold' : 'text-success fw-bold';

                    tbody.innerHTML += `
                        <tr>
                            <td>
                                <strong>${d.name}</strong>
                                <small class="text-muted d-block font-monospace">${d.sku}</small>
                            </td>
                            <td class="text-end font-monospace">$${d.quoted_price.toFixed(2)}</td>
                            <td class="text-end font-monospace">$${d.current_price.toFixed(2)}</td>
                            <td class="text-center font-monospace ${diffColor}">${diffSign}</td>
                        </tr>
                    `;
                });

                const modal = new bootstrap.Modal(document.getElementById('modal-quote-price-discrepancies'));
                modal.show();
            } else {
                this.applyQuoteOption('quoted');
            }
        },

        applyQuoteOption: function(choice) {
            if (!this._pendingQuoteData) return;
            const data = this._pendingQuoteData;
            const loadMode = this._pendingQuoteLoadMode || 'replace';

            const discModalEl = document.getElementById('modal-quote-price-discrepancies');
            const discModal = discModalEl ? bootstrap.Modal.getInstance(discModalEl) : null;
            if (discModal) discModal.hide();

            this._skipInitialSelectRestore = true;

            let finalItems = [];
            (data.items || []).forEach(item => {
                let pItem = { ...item };
                if (choice === 'catalog' && item.current_catalog_price) {
                    pItem.price = item.current_catalog_price;
                    pItem.adjusted_price = item.current_catalog_price;
                    pItem.price_type = 'venta';
                } else {
                    pItem.price = item.price;
                    pItem.adjusted_price = item.adjusted_price;
                    // Conservar el tipo de precio real ('venta' si coincide con catálogo, 'personalizado' solo si tuvo descuento/discrepancia)
                    pItem.price_type = item.price_type || 'venta';
                }
                finalItems.push(pItem);
            });

            let parkedSnapshot = null;
            if (loadMode === 'park_and_load' && this.hasActiveCartState()) {
                parkedSnapshot = this.parkCurrentSaleSilent();
            }

            if (loadMode === 'merge') {
                // Fusionar artículos con el carrito actual sin perder lo que ya estaba cargado
                finalItems.forEach(qItem => {
                    const isVirtual = !!(qItem.is_virtual || qItem.is_wildcard);
                    const existing = !isVirtual ? this.items.find(i =>
                        i.id === qItem.id &&
                        i.variant_id === qItem.variant_id &&
                        (i.price_type || 'venta') === (qItem.price_type || 'venta') &&
                        Math.abs(parseFloat(i.price || 0) - parseFloat(qItem.price || 0)) < 0.01
                    ) : null;

                    if (existing) {
                        existing.quantity = (parseInt(existing.quantity) || 1) + (parseInt(qItem.quantity) || 1);
                    } else {
                        this.items.push(qItem);
                    }
                });

                // Solo asignar cliente/cupón/entrega de la cotización si la venta actual no tenía uno definido
                if (!$('#customer_select').val() && data.customer_id) {
                    this.ensureCustomerOptionExists(data.customer_id, data.customer_name);
                    $('#customer_select').val(data.customer_id).trigger('change');
                }
                if (!this.appliedCoupon && data.coupon) {
                    this.appliedCoupon = {
                        id: data.coupon.id || null,
                        code: data.coupon.code || '',
                        name: data.coupon.name || data.coupon.code || '',
                        discount_type: data.coupon.discount_type || 'fixed',
                        discount_value: parseFloat(data.coupon.discount_value ?? data.coupon.discount ?? 0) || 0,
                        max_discount_amount: data.coupon.max_discount_amount ? parseFloat(data.coupon.max_discount_amount) : null,
                        min_purchase: parseFloat(data.coupon.min_purchase) || 0,
                        discount_amount: parseFloat(data.coupon.discount_amount ?? data.coupon.discount ?? 0) || 0,
                        description: data.coupon.description || data.coupon.name || data.coupon.code || '',
                        seller_id: data.coupon.seller_id || null,
                        seller_name: data.coupon.seller_name || null,
                        customer_id: data.coupon.customer_id || null,
                        store_id: data.coupon.store_id || null,
                        commission_info: data.coupon.commission_info || null
                    };
                    this.updateCouponUI();
                }
                if (!this.deliveryData && data.delivery && data.delivery.is_delivery) {
                    this.deliveryData = data.delivery;
                    localStorage.setItem('pos_delivery_data', JSON.stringify(this.deliveryData));
                }
            } else {
                // Modo 'park_and_load' o 'replace': cargar cotización en limpio
                this.items = finalItems;
                this.payments = [];
                this.hasCustomPayments = false;
                localStorage.removeItem('pos_cart_payments');
                localStorage.removeItem('pos_has_custom_payments');

                if (data.customer_id) {
                    this.ensureCustomerOptionExists(data.customer_id, data.customer_name);
                    $('#customer_select').val(data.customer_id).trigger('change');
                } else {
                    $('#customer_select').val('').trigger('change');
                }

                if (data.seller_id) {
                    $('#seller_select').val(data.seller_id).trigger('change');
                }

                if (data.coupon) {
                    this.appliedCoupon = {
                        id: data.coupon.id || null,
                        code: data.coupon.code || '',
                        name: data.coupon.name || data.coupon.code || '',
                        discount_type: data.coupon.discount_type || 'fixed',
                        discount_value: parseFloat(data.coupon.discount_value ?? data.coupon.discount ?? 0) || 0,
                        max_discount_amount: data.coupon.max_discount_amount ? parseFloat(data.coupon.max_discount_amount) : null,
                        min_purchase: parseFloat(data.coupon.min_purchase) || 0,
                        discount_amount: parseFloat(data.coupon.discount_amount ?? data.coupon.discount ?? 0) || 0,
                        description: data.coupon.description || data.coupon.name || data.coupon.code || '',
                        seller_id: data.coupon.seller_id || null,
                        seller_name: data.coupon.seller_name || null,
                        customer_id: data.coupon.customer_id || null,
                        store_id: data.coupon.store_id || null,
                        commission_info: data.coupon.commission_info || null
                    };
                    this.updateCouponUI();
                } else {
                    this.appliedCoupon = null;
                    localStorage.removeItem('pos_applied_coupon');
                    this.updateCouponUI();
                }

                if (data.delivery && data.delivery.is_delivery) {
                    this.deliveryData = data.delivery;
                    localStorage.setItem('pos_delivery_data', JSON.stringify(this.deliveryData));
                } else {
                    this.deliveryData = null;
                    localStorage.removeItem('pos_delivery_data');
                }
            }

            this.loadedQuoteId = data.quote_id || null;
            this.loadedQuoteFolio = data.folio || null;
            this._pendingQuoteData = null;

            this.updateLoadedQuoteUI();
            this.updateParkedSalesUI();
            this.saveToStorage();
            this.render();
            this.recalculate();
            this.updateDeliveryUI();

            let successHtml = `Se cargaron los artículos de la cotización <strong>${data.folio}</strong> al carrito.`;
            if (parkedSnapshot) {
                successHtml += `<br><div class="mt-2 p-2 rounded bg-warning-subtle text-dark small"><i class="ri-bookmark-3-line me-1"></i>Tu venta anterior (<strong>${parkedSnapshot.label}</strong>) quedó resguardada en <strong>"En Espera"</strong>.</div>`;
            } else if (loadMode === 'merge') {
                successHtml = `Se fusionaron los artículos de la cotización <strong>${data.folio}</strong> con tu venta actual.`;
            }

            Swal.fire({
                title: '¡Cotización Cargada!',
                html: successHtml,
                icon: 'success',
                timer: parkedSnapshot ? 3200 : 2200,
                showConfirmButton: false
            });

            this.cleanQuoteUrlParam();
        }
    };

    // Google Maps JavaScript API Loader
    (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
        key: "{{ config('services.google_maps.key', '') }}",
        v: "weekly"
    });

    // Inicializar POS
    document.addEventListener('DOMContentLoaded', () => {
        posCart.init();
    });

</script>
@endsection
