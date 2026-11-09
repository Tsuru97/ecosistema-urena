@extends('layouts.master')
@section('title') Orden de Compra: {{ $purchaseOrder->folio }} @endsection

@section('content')
    <x-breadcrumb />

    {{-- Alertas de sesión --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- ══════════════════════════════════════════════════════════════
             COLUMNA PRINCIPAL (Información, Partidas e Historial de Pagos)
             ══════════════════════════════════════════════════════════════ --}}
        <div class="col-lg-8">

            @php
                $linkedSOItems = \App\Models\SpecialOrderItem::with('specialOrder.customer')->where('purchase_order_id', $purchaseOrder->id)->get();
            @endphp
            @if($linkedSOItems->isNotEmpty())
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
                    <i class="ri-shopping-bag-line fs-24 text-info me-3"></i>
                    <div>
                        <strong class="d-block text-dark mb-1">Esta Orden de Compra respalda Pedidos Especiales de clientes:</strong>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($linkedSOItems->unique('special_order_id') as $soItem)
                                <a href="{{ route('special-orders.show', $soItem->specialOrder) }}" class="badge bg-white text-info border px-2 py-1 fs-12 text-decoration-none shadow-sm" title="Ver detalle del Pedido Especial">
                                    <i class="ri-external-link-line me-1"></i> {{ $soItem->specialOrder->folio }} — Cliente: {{ $soItem->specialOrder->customer?->full_name }} ({{ $soItem->specialOrder->statusLabel() }})
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- 1. Ficha de encabezado --}}
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-primary-subtle py-3 d-flex align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0 text-primary fw-bold flex-grow-1">
                        <i class="ri-file-text-line me-2"></i>Orden de Compra {{ $purchaseOrder->folio }}
                    </h5>

                    <div class="d-flex align-items-center gap-2">
                        @if($purchaseOrder->hasInvoicePdf())
                            <a href="{{ route('purchase-orders.invoice.download', [$purchaseOrder, 'pdf']) }}" class="btn btn-sm btn-danger py-1 px-2 fw-semibold" title="Descargar PDF de la Factura">
                                <i class="ri-file-pdf-fill me-1"></i> PDF
                            </a>
                        @endif
                        @if($purchaseOrder->hasInvoiceXml())
                            <a href="{{ route('purchase-orders.invoice.download', [$purchaseOrder, 'xml']) }}" class="btn btn-sm btn-success py-1 px-2 fw-semibold" title="Descargar XML CFDI de la Factura">
                                <i class="ri-file-code-line me-1"></i> XML
                            </a>
                        @endif

                        @if($purchaseOrder->rv_folio)
                            <span class="badge bg-primary fs-12 px-2 py-1" title="Número de Factura / Recibo del Proveedor">
                                <i class="ri-receipt-line me-1"></i>RV: {{ $purchaseOrder->rv_folio }}
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#modal-update-rv" title="Editar Factura y Archivos">
                                <i class="ri-edit-line"></i>
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold py-1 px-3" data-bs-toggle="modal" data-bs-target="#modal-update-rv">
                                <i class="ri-add-line me-1"></i> Asignar Factura (PDF/XML)
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase fw-semibold">
                                Proveedor
                                @if(str_contains($purchaseOrder->notes ?? '', 'Pendiente de Asignar Proveedor'))
                                    <span class="badge bg-warning text-dark ms-1"><i class="ri-alert-line me-1"></i>Por Asignar</span>
                                @endif
                            </div>
                            <div class="fw-bold text-dark fs-15 d-flex align-items-center flex-wrap gap-1 mt-1">
                                @if($purchaseOrder->supplier)
                                    <a href="{{ route('suppliers.show', $purchaseOrder->supplier) }}" class="text-primary text-decoration-none">
                                        {{ $purchaseOrder->supplier->name }}
                                    </a>
                                @else
                                    <span class="text-danger fw-bold"><i class="ri-question-line me-1"></i>Sin Proveedor Asignado</span>
                                @endif
                                @if($purchaseOrder->status === 'pendiente')
                                    <button type="button" class="btn btn-sm btn-soft-primary py-0 px-2 fs-12 ms-1" data-bs-toggle="modal" data-bs-target="#modal-edit-supplier" title="Asignar / Cambiar Proveedor de la Orden">
                                        <i class="ri-pencil-line me-1"></i> {{ $purchaseOrder->supplier ? 'Cambiar' : 'Asignar' }}
                                    </button>
                                @endif
                            </div>
                            <small class="text-muted">RFC: {{ $purchaseOrder->supplier?->rfc ?: '—' }}</small>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase fw-semibold">Sucursal Destino</div>
                            <span class="badge bg-info-subtle text-info fs-13 mt-1">{{ $purchaseOrder->store->name }}</span>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small text-uppercase fw-semibold">Fecha Esperada Entrega</div>
                            <div class="fs-14 fw-semibold mt-1">
                                <i class="ri-calendar-event-line me-1 text-muted"></i>
                                {{ $purchaseOrder->expected_at ? $purchaseOrder->expected_at->format('d/m/Y') : 'Sin fecha definida' }}
                            </div>
                        </div>

                        <div class="col-md-4 border-top pt-3">
                            <div class="text-muted small text-uppercase fw-semibold">Creado por</div>
                            <div class="fs-13 fw-medium">{{ $purchaseOrder->creator?->name ?? '—' }}</div>
                            <div class="text-muted small">{{ $purchaseOrder->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        @if($purchaseOrder->approver)
                            <div class="col-md-4 border-top pt-3">
                                <div class="text-muted small text-uppercase fw-semibold">Autorizado por</div>
                                <div class="fs-13 fw-medium">{{ $purchaseOrder->approver->name }}</div>
                                <div class="text-muted small">{{ $purchaseOrder->approved_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        @endif
                        <div class="col-md-4 border-top pt-3">
                            <div class="text-muted small text-uppercase fw-semibold">Emisión Factura/RV</div>
                            <div class="fs-13 fw-medium">
                                {{ $purchaseOrder->issued_at ? $purchaseOrder->issued_at->format('d/m/Y') : 'Pendiente' }}
                            </div>
                        </div>

                        @if($purchaseOrder->notes)
                            <div class="col-12 border-top pt-3">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Notas / Observaciones</div>
                                <div class="fs-13 text-muted bg-light p-2 rounded border border-dashed">
                                    <i class="ri-chat-3-line me-1"></i>{{ $purchaseOrder->notes }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. Detalle de productos / partidas --}}
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light py-3 d-flex align-items-center">
                    <h6 class="card-title mb-0 flex-grow-1 fw-bold">
                        <i class="ri-shopping-basket-2-line me-2 text-primary"></i>Partidas de Compra
                    </h6>
                    <span class="badge bg-secondary-subtle text-secondary">{{ $purchaseOrder->items->count() }} artículo(s)</span>
                </div>

                {{-- Formulario para recibir la orden --}}
                @if($purchaseOrder->isAprobada() && $isDest)
                    @can('recibir-ordenes-compra')
                        <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST" id="receive-form">
                            @csrf
                    @endcan
                @endif

                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Producto / SKU</th>
                                <th class="text-end" style="width: 110px">Costo Unit.</th>
                                <th class="text-center" style="width: 90px">Solicitado</th>
                                <th class="text-center" style="width: 90px">Recibido</th>
                                <th class="text-end" style="width: 100px">Desc.</th>
                                <th class="text-end" style="width: 120px">Subtotal Neto</th>
                                @if($purchaseOrder->isAprobada() && $isDest)
                                    @can('recibir-ordenes-compra')
                                        <th class="text-center bg-success-subtle text-success" style="width: 120px">Conteo Físico *</th>
                                    @endcan
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrder->items as $item)
                                @php
                                    $grossLine  = (float) ($item->gross_amount > 0 ? $item->gross_amount : ($item->quantity_ordered * $item->cost));
                                    $discLine   = (float) ($item->discount_amount ?? 0);
                                    $netLine    = (float) ($item->net_amount > 0 ? $item->net_amount : ($grossLine - $discLine));
                                    $avLabels   = $item->variant
                                        ? $item->variant->attributeValues->map(fn($av) => '<span class="badge bg-light text-dark border me-1">' . e($av->attribute->name) . ': ' . e($av->value) . '</span>')->join('')
                                        : '';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold fs-13">{{ $item->product->name }}</div>
                                        @if($avLabels) <div class="mt-1">{!! $avLabels !!}</div> @endif
                                        <small class="text-muted">SKU: {{ $item->variant?->sku ?? $item->product->sku }}</small>
                                        @if($item->notes)
                                            <div class="small text-info mt-1"><i class="ri-chat-3-line me-1"></i>{{ $item->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold text-secondary">
                                        ${{ number_format($item->cost, 2) }}
                                        @if($purchaseOrder->status === 'pendiente')
                                            <button type="button" class="btn btn-sm btn-link text-primary p-0 ms-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-edit-cost-{{ $item->id }}"
                                                    title="Editar costo de compra de esta partida">
                                                <i class="ri-pencil-line"></i>
                                            </button>

                                            {{-- Modal pequeño para ajustar costo --}}
                                            <div class="modal fade" id="modal-edit-cost-{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                                    <form action="{{ route('purchase-orders.items.update-cost', [$purchaseOrder, $item]) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="modal-content border-0 shadow-lg text-start">
                                                            <div class="modal-header bg-primary text-white p-2 px-3">
                                                                <h6 class="modal-title text-white fw-bold"><i class="ri-price-tag-3-line me-1"></i> Ajustar Costo</h6>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body p-3">
                                                                <div class="mb-2">
                                                                    <div class="fw-bold text-dark fs-13">{{ $item->product->name }}</div>
                                                                    <small class="text-muted">SKU: {{ $item->variant?->sku ?? $item->product->sku }}</small>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold fs-12">Nuevo Costo de Compra ($) <span class="text-danger">*</span></label>
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text">$</span>
                                                                        <input type="number" name="cost" class="form-control fw-bold text-success" step="0.01" min="0.01" value="{{ number_format($item->cost, 2, '.', '') }}" required>
                                                                    </div>
                                                                    <small class="text-muted fs-11">Solicitado: <strong>{{ $item->quantity_ordered }} u.</strong></small>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer bg-light p-2 text-end">
                                                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary btn-sm px-3">Guardar Costo</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">{{ $item->quantity_ordered }}</td>
                                    <td class="text-center">
                                        @if($purchaseOrder->isRecibida() || $item->quantity_received > 0)
                                            <span class="badge {{ $item->quantity_received >= $item->quantity_ordered ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} fw-bold fs-12">
                                                {{ $item->quantity_received }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-muted small">
                                        @if($discLine > 0)
                                            <span class="text-danger">-${{ number_format($discLine, 2) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        ${{ number_format($netLine, 2) }}
                                    </td>
                                    @if($purchaseOrder->isAprobada() && $isDest)
                                        @can('recibir-ordenes-compra')
                                            <td class="bg-success-subtle">
                                                <input type="number" name="quantity_received[{{ $item->id }}]"
                                                       class="form-control form-control-sm text-center fw-bold"
                                                       style="width: 80px; margin: auto;"
                                                       value="{{ $item->quantity_ordered }}" min="0">
                                            </td>
                                        @endcan
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($purchaseOrder->isAprobada() && $isDest)
                    @can('recibir-ordenes-compra')
                        </form>
                    @endcan
                @endif
            </div>

            {{-- 3. Historial de Pagos al Proveedor --}}
            @php
                $poBalance = $purchaseOrder->pendingBalance();
                $payments  = $purchaseOrder->payments()->with('registeredBy')->orderBy('date', 'desc')->get();
            @endphp
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3 d-flex align-items-center">
                    <h6 class="card-title mb-0 flex-grow-1 fw-bold">
                        <i class="ri-history-line me-2 text-success"></i>Historial de Pagos al Proveedor
                    </h6>
                    @if($poBalance > 0)
                        <button class="btn btn-sm btn-success fw-semibold" type="button" data-bs-toggle="modal" data-bs-target="#modal-add-payment">
                            <i class="ri-add-line me-1"></i> Registrar Pago
                        </button>
                    @endif
                </div>

                <div class="card-body p-0">
                    @if($payments->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="ri-money-dollar-box-line fs-1 mb-2 d-block text-secondary"></i>
                            <p class="mb-1 fw-medium">Sin abonos o pagos registrados para esta orden.</p>
                            @if($poBalance > 0)
                                <button class="btn btn-sm btn-outline-success mt-2" type="button" data-bs-toggle="modal" data-bs-target="#modal-add-payment">
                                    <i class="ri-add-line me-1"></i> Registrar primer pago
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha Pago</th>
                                        <th>Método</th>
                                        <th>Referencia / Folio</th>
                                        <th>Registrado por</th>
                                        <th>Notas</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td class="fw-medium">{{ \Carbon\Carbon::parse($payment->date)->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    {{ ucfirst($payment->payment_method) }}
                                                </span>
                                            </td>
                                            <td class="text-muted small">{{ $payment->reference ?: '—' }}</td>
                                            <td class="text-muted small">{{ $payment->registeredBy?->name ?? 'Sistema' }}</td>
                                            <td class="text-muted small">{{ $payment->notes ?: '—' }}</td>
                                            <td class="text-end fw-bold text-success fs-14">
                                                +${{ number_format($payment->amount, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="5" class="text-end">Total Liquidado a Proveedor:</td>
                                        <td class="text-end text-success fs-15">${{ number_format($payments->sum('amount'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>{{-- /col-lg-8 --}}


        {{-- ══════════════════════════════════════════════════════════════
             COLUMNA LATERAL (Resumen Financiero + Acciones del Flujo)
             ══════════════════════════════════════════════════════════════ --}}
        <div class="col-lg-4">

            {{-- 1. Resumen Financiero Consolidado (Gestión RVs) --}}
            @php
                $grossPO   = $purchaseOrder->effectiveGrossAmount();
                $discPO    = $purchaseOrder->effectiveDiscountAmount();
                $unrecPO   = $purchaseOrder->unreceivedAmount();
                $netPO     = $purchaseOrder->effectiveNetAmount();
                $paidPO    = (float) $purchaseOrder->amount_paid;
                $debtPO    = $purchaseOrder->pendingBalance();
                $pctPaid   = $netPO > 0 ? min(100, round(($paidPO / $netPO) * 100, 1)) : 0;
                $pStatus   = $purchaseOrder->payment_status ?: ($paidPO >= $netPO ? 'pagada' : ($paidPO > 0 ? 'pago_parcial' : 'pendiente'));
                $pBadge    = match($pStatus) {
                    'pagada'       => ['label' => 'Pagada',       'class' => 'bg-success text-white'],
                    'pago_parcial' => ['label' => 'Pago Parcial', 'class' => 'bg-info text-white'],
                    default        => ['label' => 'Pendiente de Pago', 'class' => 'bg-warning text-dark'],
                };
            @endphp

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center">
                    <h6 class="card-title mb-0 text-white flex-grow-1 fw-bold">
                        <i class="ri-pie-chart-2-line me-2 text-info"></i>Resumen Financiero RV
                    </h6>
                    <span class="badge {{ $pBadge['class'] }} fs-11 px-2 py-1">{{ $pBadge['label'] }}</span>
                </div>
                <div class="card-body">

                    {{-- Importe Bruto --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Importe Solicitado (Bruto):</span>
                        <span class="fw-semibold text-secondary">${{ number_format($grossPO, 2) }}</span>
                    </div>

                    {{-- Descuentos --}}
                    @if($discPO > 0)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Descuentos Aplicados:</span>
                            <span class="text-danger fw-semibold">-${{ number_format($discPO, 2) }}</span>
                        </div>
                    @endif

                    {{-- Piezas No Recibidas / Merma --}}
                    @if($unrecPO > 0)
                        <div class="d-flex justify-content-between align-items-center mb-2" title="Descuento automático por mercancía no recibida o piezas dañadas en almacén">
                            <span class="text-danger small fw-semibold">
                                <i class="ri-error-warning-line me-1"></i>Piezas No Recibidas / Merma:
                            </span>
                            <span class="text-danger fw-bold">-${{ number_format($unrecPO, 2) }}</span>
                        </div>
                    @endif

                    <hr class="my-2">

                    {{-- Total Importe Neto --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-dark">Total Neto A Pagar (Recibido):</span>
                        <strong class="fs-4 text-dark">${{ number_format($netPO, 2) }}</strong>
                    </div>

                    {{-- Total Pagado --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-semibold">Abonado a Proveedor:</span>
                        <span class="text-success fw-bold fs-15">${{ number_format($paidPO, 2) }}</span>
                    </div>

                    {{-- Deuda Pendiente Highlight --}}
                    <div class="p-3 rounded {{ $debtPO > 0 ? 'bg-danger-subtle border border-danger-subtle' : 'bg-success-subtle border border-success-subtle' }} mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-semibold text-uppercase {{ $debtPO > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $debtPO > 0 ? 'Deuda Pendiente' : 'Orden Saldada' }}
                                </div>
                                <div class="fs-3 fw-bold {{ $debtPO > 0 ? 'text-danger' : 'text-success' }}">
                                    ${{ number_format($debtPO, 2) }}
                                </div>
                            </div>
                            <i class="{{ $debtPO > 0 ? 'ri-error-warning-line text-danger' : 'ri-checkbox-circle-line text-success' }} display-5"></i>
                        </div>
                    </div>

                    {{-- Avance de Pago --}}
                    <div class="mb-2 d-flex justify-content-between small text-muted fw-semibold">
                        <span>Avance de Liquidación</span>
                        <span>{{ $pctPaid }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar {{ $debtPO <= 0 ? 'bg-success' : 'bg-warning' }}"
                             role="progressbar"
                             style="width: {{ $pctPaid }}%"
                             aria-valuenow="{{ $pctPaid }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    @if($debtPO > 0)
                        <button type="button" class="btn btn-success w-100 fw-bold py-2 shadow-sm mb-2" data-bs-toggle="modal" data-bs-target="#modal-add-payment">
                            <i class="ri-money-dollar-circle-line me-1 fs-16 align-middle"></i> Registrar Pago al Proveedor
                        </button>
                    @endif

                    {{-- Descarga rápida de archivos de factura --}}
                    @if($purchaseOrder->hasInvoicePdf() || $purchaseOrder->hasInvoiceXml())
                        <div class="pt-2 border-top d-flex gap-2">
                            @if($purchaseOrder->hasInvoicePdf())
                                <a href="{{ route('purchase-orders.invoice.download', [$purchaseOrder, 'pdf']) }}" class="btn btn-sm btn-outline-danger flex-grow-1 fw-semibold">
                                    <i class="ri-file-pdf-fill me-1"></i> Descargar PDF
                                </a>
                            @endif
                            @if($purchaseOrder->hasInvoiceXml())
                                <a href="{{ route('purchase-orders.invoice.download', [$purchaseOrder, 'xml']) }}" class="btn btn-sm btn-outline-success flex-grow-1 fw-semibold">
                                    <i class="ri-file-code-line me-1"></i> Descargar XML
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- 2. Estado de Orden y Control del Flujo --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="ri-settings-3-line me-2 text-secondary"></i>Estado y Acciones</h6>
                </div>
                <div class="card-body d-grid gap-2">

                    @php $badge = $purchaseOrder->statusBadge(); @endphp
                    <div class="text-center border rounded p-3 bg-light-subtle mb-2">
                        <div class="fs-11 text-muted text-uppercase fw-semibold mb-1">Estatus de Almacén</div>
                        <span class="badge bg-{{ $badge['color'] }} fs-13 py-1 px-3">{{ $badge['label'] }}</span>
                    </div>

                    {{-- PENDIENTE: Autorizar o Cancelar --}}
                    @if($purchaseOrder->isPendiente())
                        @can('autorizar-ordenes-compra')
                            <form action="{{ route('purchase-orders.approve', $purchaseOrder) }}" method="POST" id="approve-form">
                                @csrf
                                <button type="submit" class="btn btn-info btn-sm w-100 py-2 fw-semibold"
                                        data-confirm="true"
                                        data-form="approve-form"
                                        data-title="Autorizar Compra"
                                        data-message="¿Deseas autorizar la orden <strong>{{ $purchaseOrder->folio }}</strong> para su adquisición?"
                                        data-btn-label="Sí, autorizar"
                                        data-btn-class="btn-info">
                                    <i class="ri-checkbox-circle-line me-1"></i> Autorizar Orden
                                </button>
                            </form>
                        @endcan
                        @can('crear-ordenes-compra')
                            <form action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" method="POST" id="cancel-form">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-2"
                                        data-confirm="true"
                                        data-form="cancel-form"
                                        data-title="Cancelar Orden de Compra"
                                        data-message="¿Estás seguro de cancelar esta orden? Esta acción no se puede deshacer."
                                        data-btn-label="Sí, cancelar"
                                        data-btn-class="btn-danger">
                                    <i class="ri-close-circle-line me-1"></i> Cancelar Orden
                                </button>
                            </form>
                        @endcan
                    @endif

                    {{-- APROBADA: Recibir (ingreso físico) --}}
                    @if($purchaseOrder->isAprobada())
                        @if($isDest)
                            @can('recibir-ordenes-compra')
                                <div class="alert alert-warning py-2 mb-2 fs-12">
                                    <i class="ri-information-line me-1"></i>
                                    Ingresa el conteo físico en la tabla y confirma la recepción.
                                </div>
                                <button type="submit" form="receive-form" class="btn btn-success btn-sm w-100 py-2 fw-semibold"
                                        data-confirm="true"
                                        data-form="receive-form"
                                        data-title="Confirmar Recepción de Compra"
                                        data-message="Al confirmar, se **sumará el stock físico** a la sucursal <strong>{{ $purchaseOrder->store->name }}</strong> y se actualizarán los **costos en catálogo**."
                                        data-btn-label="Sí, ingresar mercancía"
                                        data-btn-class="btn-success">
                                    <i class="ri-download-line me-1"></i> Confirmar Recepción
                                </button>
                            @else
                                <p class="text-muted text-center fs-12 mb-0">Sin permisos de recepción.</p>
                            @endcan
                        @else
                            <div class="alert alert-info py-2 mb-0 fs-12">
                                <i class="ri-information-line me-1"></i>
                                Esperando recepción física en <strong>{{ $purchaseOrder->store->name }}</strong>.
                            </div>
                        @endif

                        @can('crear-ordenes-compra')
                            <form action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" method="POST" class="mt-1" id="cancel-form-approved">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-2"
                                        data-confirm="true"
                                        data-form="cancel-form-approved"
                                        data-title="Cancelar Orden de Compra"
                                        data-message="¿Estás seguro de cancelar esta orden? Esta acción no se puede deshacer."
                                        data-btn-label="Sí, cancelar"
                                        data-btn-class="btn-danger">
                                    <i class="ri-close-circle-line me-1"></i> Cancelar Orden
                                </button>
                            </form>
                        @endcan
                    @endif

                    {{-- RECIBIDA / CANCELADA --}}
                    @if($purchaseOrder->isFinished())
                        <div class="text-center py-2 text-muted bg-light rounded border">
                            <i class="ri-checkbox-circle-fill fs-20 text-success d-block mb-1"></i>
                            <span class="fs-13 fw-semibold">Orden Finalizada</span>
                        </div>
                    @endif

                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-ghost-secondary btn-sm mt-2 w-100">
                        <i class="ri-arrow-left-line me-1"></i> Volver a Órdenes de Compra
                    </a>
                </div>
            </div>

        </div>{{-- /col-lg-4 --}}

    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL 1: ASIGNAR / EDITAR FOLIO RV Y ARCHIVOS (PDF Y XML)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-update-rv" tabindex="-1" aria-labelledby="modalUpdateRvLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-bold" id="modalUpdateRvLabel">
                        <i class="ri-receipt-line me-2"></i>Factura y Archivos de Proveedor (RV)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('purchase-orders.rv-folio.update', $purchaseOrder) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Folio RV / Nº de Factura <span class="text-danger">*</span></label>
                            <input type="text" name="rv_folio" class="form-control form-control-lg fw-bold"
                                   placeholder="Ej: F-109482, RV-2026-981"
                                   value="{{ old('rv_folio', $purchaseOrder->rv_folio) }}" required>
                            <div class="form-text">Número de comprobante fiscal o remisión emitido por el proveedor.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Fecha de Emisión del Documento</label>
                            <input type="date" name="issued_at" class="form-control"
                                   value="{{ old('issued_at', $purchaseOrder->issued_at ? $purchaseOrder->issued_at->format('Y-m-d') : date('Y-m-d')) }}">
                        </div>

                        <hr class="my-3">
                        <h6 class="fw-bold mb-3"><i class="ri-attachment-line me-1 text-primary"></i>Archivos de Factura Adjuntos</h6>

                        {{-- Cargar PDF --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Factura en PDF (.pdf)</label>
                            <input type="file" name="invoice_pdf" class="form-control" accept="application/pdf">
                            @if($purchaseOrder->hasInvoicePdf())
                                <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded border mt-2">
                                    <span class="text-danger fw-semibold small">
                                        <i class="ri-file-pdf-fill me-1 fs-15"></i> Archivo PDF adjunto
                                    </span>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('purchase-orders.invoice.download', [$purchaseOrder, 'pdf']) }}" class="btn btn-sm btn-outline-danger py-0 px-2 fs-12">
                                            <i class="ri-download-line me-1"></i>Descargar
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Cargar XML --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Factura en XML CFDI (.xml)</label>
                            <input type="file" name="invoice_xml" class="form-control" accept=".xml,text/xml">
                            @if($purchaseOrder->hasInvoiceXml())
                                <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded border mt-2">
                                    <span class="text-success fw-semibold small">
                                        <i class="ri-file-code-line me-1 fs-15"></i> Archivo XML adjunto
                                    </span>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('purchase-orders.invoice.download', [$purchaseOrder, 'xml']) }}" class="btn btn-sm btn-outline-success py-0 px-2 fs-12">
                                            <i class="ri-download-line me-1"></i>Descargar
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            <i class="ri-save-line me-1"></i> Guardar Factura y Archivos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         MODAL 2: REGISTRAR PAGO A PROVEEDOR
         ══════════════════════════════════════════════════════════════ --}}
    @if($poBalance > 0)
    <div class="modal fade" id="modal-add-payment" tabindex="-1" aria-labelledby="modalAddPaymentLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white fw-bold" id="modalAddPaymentLabel">
                        <i class="ri-bank-card-line me-2"></i>Registrar Pago a Proveedor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('purchase-orders.payments.add', $purchaseOrder) }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-info py-2 fs-12 mb-3">
                            <i class="ri-information-line me-1"></i>
                            Pago correspondiente a la Orden <strong>{{ $purchaseOrder->folio }}</strong> ({{ $purchaseOrder->supplier?->name ?? 'Sin Proveedor' }}).
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Monto del Pago ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="amount" class="form-control form-control-lg fw-bold text-success"
                                       step="0.01" min="0.01" max="{{ $poBalance }}"
                                       value="{{ number_format($poBalance, 2, '.', '') }}" required>
                            </div>
                            <div class="form-text">Saldo pendiente actual: <strong>${{ number_format($poBalance, 2) }}</strong></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="transferencia" selected>Transferencia Bancaria</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha de Pago</label>
                                <input type="date" name="paid_at" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Referencia / Nº de Operación</label>
                            <input type="text" name="reference" class="form-control" placeholder="Ej: No. Transferencia, Rastreo SPEI, Cheque #">
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold">Notas u Observaciones</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Comentarios adicionales del pago..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="ri-check-double-line me-1"></i> Guardar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL — Modificar / Asignar Proveedor de la Orden (en estado pendiente) --}}
    @if($purchaseOrder->status === 'pendiente')
    @php
        $draftPOsBySupplier = \App\Models\PurchaseOrder::where('store_id', $purchaseOrder->store_id)
            ->where('status', 'pendiente')
            ->where('id', '!=', $purchaseOrder->id)
            ->whereNotNull('supplier_id')
            ->pluck('folio', 'supplier_id');
    @endphp
    <div class="modal fade" id="modal-edit-supplier" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('purchase-orders.supplier.update', $purchaseOrder) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white p-3">
                        <h5 class="modal-title text-white">
                            <i class="ri-truck-line me-1"></i> Asignar / Cambiar Proveedor
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info py-2 fs-12 mb-3">
                            <i class="ri-information-line me-1"></i> Asigna un proveedor autorizado a esta orden de compra.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Seleccionar Proveedor Autorizado <span class="text-danger">*</span></label>
                            <select name="supplier_id" id="edit-supplier-select" class="form-select" required onchange="onEditSupplierChange(this)">
                                <option value="">— Selecciona un proveedor —</option>
                                @foreach($suppliers as $sup)
                                    @php $draftFolio = $draftPOsBySupplier[$sup->id] ?? null; @endphp
                                    <option value="{{ $sup->id }}"
                                            data-has-draft="{{ $draftFolio ? '1' : '0' }}"
                                            data-draft-folio="{{ $draftFolio }}"
                                            {{ $purchaseOrder->supplier_id == $sup->id ? 'selected' : '' }}>
                                        {{ $sup->name }} {{ $sup->rfc ? "({$sup->rfc})" : '' }}
                                        {{ $draftFolio ? " 🔀 [OC Borrador: {$draftFolio}]" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Opciones de fusión condicional --}}
                        <div id="po-merge-option-box" class="p-3 bg-warning-subtle rounded border border-warning mb-2" style="display: none;">
                            <h6 class="fw-bold text-warning-emphasis mb-2 fs-13">
                                <i class="ri-git-merge-line me-1"></i> Se detectó una OC Borrador Abierta
                            </h6>
                            <p class="fs-12 text-dark mb-2">
                                Ya existe la orden borrador <strong id="po-draft-folio-label"></strong> para este proveedor en esta sucursal.
                            </p>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="action" id="action_merge" value="merge" checked>
                                <label class="form-check-label fw-semibold text-dark fs-13 cursor-pointer" for="action_merge">
                                    🔀 Fusionar partidas en la OC borrador existente
                                </label>
                                <small class="text-muted d-block ms-4 fs-11">Unifica los productos de esta orden en la OC borrador existente para enviar 1 solo pedido al proveedor.</small>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="action_keep" value="keep_standalone">
                                <label class="form-check-label fw-semibold text-dark fs-13 cursor-pointer" for="action_keep">
                                    📄 Mantener como Orden de Compra independiente
                                </label>
                                <small class="text-muted d-block ms-4 fs-11">Conserva esta orden como un pedido por separado con su propio folio ({{ $purchaseOrder->folio }}).</small>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="ri-save-line me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function onEditSupplierChange(selectEl) {
        const opt = selectEl.options[selectEl.selectedIndex];
        const hasDraft = opt ? opt.getAttribute('data-has-draft') === '1' : false;
        const draftFolio = opt ? opt.getAttribute('data-draft-folio') : '';

        const box = document.getElementById('po-merge-option-box');
        const label = document.getElementById('po-draft-folio-label');

        if (hasDraft && box && label) {
            label.textContent = draftFolio;
            box.style.display = 'block';
            document.getElementById('action_merge').checked = true;
        } else if (box) {
            box.style.display = 'none';
            const keepInput = document.getElementById('action_keep');
            if (keepInput) keepInput.checked = true;
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const sel = document.getElementById('edit-supplier-select');
        if (sel) onEditSupplierChange(sel);
    });
    </script>
    @endif

@endsection
