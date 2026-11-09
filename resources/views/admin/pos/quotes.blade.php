@extends('layouts.master')
@section('title') Gestión de Cotizaciones @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <x-breadcrumb />

    {{-- KPIs de Cotizaciones --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Cotizaciones</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2">{{ $kpis['total_quotes'] ?? 0 }}</h4>
                            <span class="text-muted fs-12">Historial registrado</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="ri-file-list-3-line text-info"></i>
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
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Cotizaciones Vigentes</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-success-subtle text-success fs-12">Activas</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2 text-success">{{ $kpis['active_quotes'] ?? 0 }}</h4>
                            <span class="text-muted fs-12">Listas para conversión en caja</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="ri-calendar-check-line text-success"></i>
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
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Convertidas a Venta</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2 text-primary">{{ $kpis['converted_quotes'] ?? 0 }}</h4>
                            <span class="text-muted fs-12">Cerradas exitosamente</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                <i class="ri-shopping-cart-check-line text-primary"></i>
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
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Monto Cotizado Activo</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-3">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2 text-indigo">${{ number_format($kpis['total_quoted_amount'] ?? 0, 2) }}</h4>
                            <span class="text-muted fs-12">En prospección comercial</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                <i class="ri-money-dollar-circle-line text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Listado Principal --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-file-paper-2-line me-1"></i> Listado de Cotizaciones y Prospección
                    </h4>
                    <div class="flex-shrink-0 d-flex gap-2">
                        <a href="{{ route('pos.sales') }}" class="btn btn-soft-secondary btn-sm">
                            <i class="ri-history-line me-1"></i> Ver Ventas
                        </a>
                        <a href="{{ route('pos.index') }}" class="btn btn-primary btn-label btn-sm">
                            <i class="ri-add-line label-icon align-middle fs-16 me-2"></i> Nueva Cotización en POS
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Buscador y filtros --}}
                    <form action="{{ route('pos.quotes') }}" method="GET" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fs-12 text-muted mb-1">Buscar</label>
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control form-control-sm search" value="{{ request('search') }}" placeholder="Ej. COT-2026-0001, #15, cliente, tel. o cupón...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">Estado</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Todos los Estados</option>
                                    <option value="pendiente" {{ request('status') == 'pendiente' ? 'selected' : '' }}>Pendiente (Vigente)</option>
                                    <option value="convertida" {{ request('status') == 'convertida' ? 'selected' : '' }}>Convertida a Venta</option>
                                    <option value="vencida" {{ request('status') == 'vencida' ? 'selected' : '' }}>Vencida</option>
                                    <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-12 text-muted mb-1">Vendedor</label>
                                <select name="seller_id" class="form-select form-select-sm">
                                    <option value="">Todos los Asesores</option>
                                    @foreach($sellers as $s)
                                        <option value="{{ $s->id }}" {{ request('seller_id') == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }} {{ $s->lastname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end gap-1">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="ri-filter-2-line me-1"></i> Filtrar</button>
                                <a href="{{ route('pos.quotes') }}" class="btn btn-soft-secondary btn-sm flex-fill"><i class="ri-refresh-line"></i></a>
                            </div>
                        </div>
                    </form>

                    {{-- Tabla de Cotizaciones --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 140px;">Folio</th>
                                    <th scope="col" style="width: 130px;">Fecha Emisión</th>
                                    <th scope="col" style="width: 130px;">Vigencia Hasta</th>
                                    <th scope="col">Cliente / Prospecto</th>
                                    <th scope="col">Vendedor Asesor</th>
                                    <th scope="col" class="text-end">Monto Total</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quotes as $quote)
                                    @php
                                        $isExpired = $quote->isExpired();
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary font-monospace">{{ $quote->folio ?: ('COT-' . $quote->id) }}</span>
                                            @if($quote->is_delivery)
                                                <span class="badge bg-primary-subtle text-primary d-block mt-1 fs-10">
                                                    <i class="ri-truck-line me-1"></i>A Domicilio
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fs-12">{{ $quote->created_at->format('d/m/Y') }}</span>
                                            <small class="text-muted d-block fs-11">{{ $quote->created_at->format('H:i') }} hrs</small>
                                        </td>
                                        <td>
                                            @if($quote->expires_at)
                                                <span class="fs-12 {{ $isExpired && $quote->status === 'pendiente' ? 'text-danger fw-bold' : '' }}">
                                                    {{ $quote->expires_at->format('d/m/Y') }}
                                                </span>
                                                @if($isExpired && $quote->status === 'pendiente')
                                                    <span class="badge bg-danger-subtle text-danger d-block fs-10 mt-1">Expirada</span>
                                                @endif
                                            @else
                                                <span class="text-muted fs-12">Sin vencimiento</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($quote->customer)
                                                <span class="fw-semibold text-dark">{{ $quote->customer->full_name }}</span>
                                                @if($quote->customer->phone)
                                                    <small class="text-muted d-block"><i class="ri-phone-line me-1"></i>{{ $quote->customer->phone }}</small>
                                                @endif
                                            @elseif($quote->delivery_recipient_name)
                                                <span class="fw-semibold text-dark">{{ $quote->delivery_recipient_name }}</span>
                                            @else
                                                <span class="text-muted">Público en General</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="ri-user-star-line me-1"></i>{{ $quote->seller ? $quote->seller->name : ($quote->user ? $quote->user->name : 'N/A') }}
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace fw-bold fs-13">
                                            ${{ number_format($quote->total, 2) }}
                                            @if((float)$quote->discount_total > 0)
                                                <span class="badge bg-warning-subtle text-warning d-block mt-1 fs-10 fw-normal">
                                                    <i class="ri-price-tag-3-line me-1"></i>Regateo: -${{ number_format($quote->discount_total, 2) }}
                                                </span>
                                            @endif
                                            @if($quote->coupon_code || (float)$quote->coupon_discount > 0)
                                                <span class="badge bg-success-subtle text-success d-block mt-1 fs-10 fw-normal">
                                                    <i class="ri-ticket-2-line me-1"></i>{{ $quote->coupon_code ?: 'Cupón' }}: -${{ number_format($quote->coupon_discount, 2) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($quote->status === 'convertida')
                                                <span class="badge bg-success-subtle text-success fs-11">
                                                    <i class="ri-check-double-line me-1"></i>Convertida a Venta
                                                </span>
                                            @elseif($quote->status === 'pendiente')
                                                @if($isExpired)
                                                    <span class="badge bg-danger-subtle text-danger fs-11">Vencida</span>
                                                @else
                                                    <span class="badge bg-primary-subtle text-primary fs-11">Vigente</span>
                                                @endif
                                            @elseif($quote->status === 'cancelada')
                                                <span class="badge bg-secondary-subtle text-secondary fs-11">Cancelada</span>
                                            @else
                                                <span class="badge bg-light text-dark fs-11">{{ ucfirst($quote->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                {{-- Ver Detalles --}}
                                                <button type="button" class="btn btn-soft-info" onclick="openQuoteDetailModal({{ $quote->id }})" title="Ver detalle de artículos y condiciones">
                                                    <i class="ri-eye-line"></i>
                                                </button>

                                                {{-- Imprimir Cotización --}}
                                                <a href="{{ route('pos.quotes.print', $quote->id) }}" target="_blank" class="btn btn-soft-secondary" title="Imprimir Comprobante de Cotización">
                                                    <i class="ri-printer-line"></i>
                                                </a>

                                                {{-- Cargar al POS --}}
                                                @if($quote->status === 'convertida')
                                                    <button type="button" class="btn btn-soft-light text-muted" disabled title="Esta cotización ya fue convertida a venta">
                                                        <i class="ri-check-line"></i> Vendida
                                                    </button>
                                                @elseif($quote->status === 'cancelada')
                                                    <button type="button" class="btn btn-soft-light text-muted" disabled title="Esta cotización se encuentra cancelada">
                                                        <i class="ri-close-circle-line"></i> Cancelada
                                                    </button>
                                                @else
                                                    <a href="{{ route('pos.index', ['quote_id' => $quote->id]) }}" class="btn btn-soft-success" title="Cargar y cobrar en el POS">
                                                        <i class="ri-shopping-cart-2-line me-1"></i> Cargar al POS
                                                    </a>
                                                @endif

                                                {{-- Cancelar Cotización --}}
                                                @if($quote->status === 'pendiente')
                                                    <button type="button" class="btn btn-soft-danger" onclick="cancelQuotePrompt({{ $quote->id }}, '{{ $quote->folio ?: ('COT-' . $quote->id) }}')" title="Cancelar cotización">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-file-list-line display-4 mb-2 d-block text-secondary"></i>
                                                <h5>No se encontraron cotizaciones</h5>
                                                <p class="mb-0">No hay cotizaciones registradas que coincidan con la búsqueda.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    <div class="mt-4">
                        {{ $quotes->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Vista Rápida de Detalles de Cotización --}}
    <div class="modal fade" id="modal-quote-detail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="ri-file-list-3-line me-2"></i>Detalle de Cotización <span id="qd-folio" class="font-monospace"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light border h-100">
                                <div class="text-muted fs-11 text-uppercase fw-bold mb-1">Cliente / Prospecto</div>
                                <div class="fw-bold text-dark fs-14" id="qd-customer">—</div>
                                <div class="text-muted fs-12" id="qd-customer-extra"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light border h-100">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted fs-12">Sucursal:</span>
                                    <strong class="text-dark fs-12" id="qd-store">—</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted fs-12">Asesor:</span>
                                    <strong class="text-dark fs-12" id="qd-seller">—</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted fs-12">Emisión / Vigencia:</span>
                                    <span class="fs-12"><span id="qd-created"></span> → <strong id="qd-expires"></strong></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted fs-12">Estado:</span>
                                    <span id="qd-status-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="qd-delivery-box" class="alert alert-info py-2 px-3 mb-3 d-none">
                        <div class="fw-bold fs-12 mb-1"><i class="ri-truck-line me-1"></i> Entrega a Domicilio Programada</div>
                        <div class="fs-12" id="qd-delivery-info"></div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>Concepto / Producto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Importe</th>
                                </tr>
                            </thead>
                            <tbody id="qd-items-tbody"></tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="p-3 rounded bg-light border">
                                <div class="d-flex justify-content-between mb-1 fs-12 text-muted">
                                    <span>Subtotal (Sin IVA):</span>
                                    <span id="qd-subtotal">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 fs-12 text-muted">
                                    <span>IVA:</span>
                                    <span id="qd-tax">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 fs-12 text-warning d-none" id="qd-discount-row">
                                    <span>Descuento Regateo:</span>
                                    <span id="qd-discount">-$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 fs-12 text-success d-none" id="qd-coupon-row">
                                    <span>Cupón (<strong id="qd-coupon-code" class="font-monospace"></strong>):</span>
                                    <span id="qd-coupon-discount">-$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between pt-2 border-top fw-bold fs-15 text-primary">
                                    <span>Total Cotizado:</span>
                                    <span id="qd-total">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cerrar</button>
                    <div class="d-flex gap-2">
                        <a href="#" target="_blank" id="qd-btn-print" class="btn btn-soft-secondary btn-sm">
                            <i class="ri-printer-line me-1"></i> Imprimir
                        </a>
                        <a href="#" id="qd-btn-load-pos" class="btn btn-success btn-sm fw-bold">
                            <i class="ri-shopping-cart-2-line me-1"></i> Cargar al POS
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js" crossorigin="anonymous"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    axios.defaults.headers.common['X-CSRF-TOKEN'] = "{{ csrf_token() }}";
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    let quoteDetailModalInstance = null;

    async function openQuoteDetailModal(quoteId) {
        Swal.fire({title: 'Cargando detalles...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
        try {
            const res = await axios.get(`/pos/quotes/${quoteId}`);
            Swal.close();

            if (!res.data || !res.data.success) {
                Swal.fire('Error', 'No se pudo obtener la información de la cotización.', 'error');
                return;
            }

            const q = res.data.quote;
            document.getElementById('qd-folio').innerText = q.folio;
            document.getElementById('qd-customer').innerText = q.customer_name || 'Público en General';

            let extraCust = [];
            if (q.customer_business) extraCust.push(q.customer_business);
            if (q.customer_phone) extraCust.push(`Tel: ${q.customer_phone}`);
            document.getElementById('qd-customer-extra').innerText = extraCust.join(' | ');

            document.getElementById('qd-store').innerText = q.store_name || 'Sucursal';
            document.getElementById('qd-seller').innerText = q.seller_name || 'N/A';
            document.getElementById('qd-created').innerText = q.created_at || '';
            document.getElementById('qd-expires').innerText = q.expires_at || 'Sin vencimiento';

            const statusBadge = document.getElementById('qd-status-badge');
            if (q.raw_status === 'convertida') {
                statusBadge.innerHTML = '<span class="badge bg-success-subtle text-success">Convertida a Venta</span>';
            } else if (q.raw_status === 'cancelada') {
                statusBadge.innerHTML = '<span class="badge bg-secondary-subtle text-secondary">Cancelada</span>';
            } else if (q.status === 'vencida') {
                statusBadge.innerHTML = '<span class="badge bg-danger-subtle text-danger">Vencida</span>';
            } else {
                statusBadge.innerHTML = '<span class="badge bg-primary-subtle text-primary">Vigente</span>';
            }

            const delBox = document.getElementById('qd-delivery-box');
            const delInfo = document.getElementById('qd-delivery-info');
            if (q.is_delivery) {
                delBox.classList.remove('d-none');
                delInfo.innerHTML = `<strong>Recibe:</strong> ${q.delivery_recipient || q.customer_name} ${q.delivery_phone ? '(' + q.delivery_phone + ')' : ''}<br><strong>Dirección:</strong> ${q.delivery_address || 'Por definir'} ${q.delivery_date ? ' | <strong>Fecha:</strong> ' + q.delivery_date + ' ' + (q.delivery_time_slot || '') : ''}`;
            } else {
                delBox.classList.add('d-none');
            }

            const tbody = document.getElementById('qd-items-tbody');
            tbody.innerHTML = '';
            (q.items || []).forEach(it => {
                const origHtml = it.has_discount
                    ? `<small class="text-muted text-decoration-line-through d-block">$${it.unit_price_original.toFixed(2)}</small>`
                    : '';
                const notesHtml = it.notes
                    ? `<small class="text-muted d-block"><i class="ri-chat-3-line me-1"></i>${it.notes}</small>`
                    : '';

                tbody.innerHTML += `
                    <tr>
                        <td class="font-monospace fs-12">${it.sku}</td>
                        <td>
                            <strong>${it.name}</strong>
                            ${notesHtml}
                        </td>
                        <td class="text-center">${it.quantity}</td>
                        <td class="text-end font-monospace">${origHtml}$${it.unit_price_final.toFixed(2)}</td>
                        <td class="text-end font-monospace fw-semibold">$${it.total.toFixed(2)}</td>
                    </tr>
                `;
            });

            document.getElementById('qd-subtotal').innerText = `$${q.subtotal.toFixed(2)}`;
            document.getElementById('qd-tax').innerText = `$${q.tax_total.toFixed(2)}`;

            const discRow = document.getElementById('qd-discount-row');
            if (q.discount_total > 0) {
                document.getElementById('qd-discount').innerText = `-$${q.discount_total.toFixed(2)}`;
                discRow.classList.remove('d-none');
            } else {
                discRow.classList.add('d-none');
            }

            const coupRow = document.getElementById('qd-coupon-row');
            if (q.coupon_code || q.coupon_discount > 0) {
                document.getElementById('qd-coupon-code').innerText = q.coupon_code || 'CUPÓN';
                document.getElementById('qd-coupon-discount').innerText = `-$${q.coupon_discount.toFixed(2)}`;
                coupRow.classList.remove('d-none');
            } else {
                coupRow.classList.add('d-none');
            }

            document.getElementById('qd-total').innerText = `$${q.total.toFixed(2)}`;
            document.getElementById('qd-btn-print').href = q.print_url;

            const btnLoadPos = document.getElementById('qd-btn-load-pos');
            if (q.raw_status !== 'convertida' && q.raw_status !== 'cancelada') {
                btnLoadPos.href = q.load_pos_url;
                btnLoadPos.classList.remove('d-none');
            } else {
                btnLoadPos.classList.add('d-none');
            }

            if (!quoteDetailModalInstance) {
                quoteDetailModalInstance = new bootstrap.Modal(document.getElementById('modal-quote-detail'));
            }
            quoteDetailModalInstance.show();
        } catch (e) {
            Swal.close();
            Swal.fire('Error', 'No se pudo consultar el detalle de la cotización.', 'error');
        }
    }

    function cancelQuotePrompt(quoteId, folio) {
        Swal.fire({
            title: `¿Cancelar cotización ${folio}?`,
            text: 'Ingresa el motivo de cancelación (opcional):',
            input: 'text',
            inputPlaceholder: 'Ej. Cliente desistió de la compra / Recotización...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar cotización',
            cancelButtonText: 'Volver',
            confirmButtonColor: '#dc3545'
        }).then(async (res) => {
            if (!res.isConfirmed) return;
            try {
                const response = await axios.post(`/pos/quotes/${quoteId}/cancel`, {
                    reason: res.value || 'Cancelada desde el listado de cotizaciones'
                });
                if (response.data.success) {
                    Swal.fire({
                        title: '¡Cotización Cancelada!',
                        text: response.data.message,
                        icon: 'success',
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Atención', response.data.message || 'No se pudo cancelar.', 'warning');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.message || 'Error al cancelar la cotización.', 'error');
            }
        });
    }
</script>
@endsection
