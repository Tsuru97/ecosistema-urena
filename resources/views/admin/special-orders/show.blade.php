@extends('layouts.master')
@section('title') Pedido Especial {{ $specialOrder->folio }} @endsection

@section('content')
<x-breadcrumb />

<div class="row g-4">

    {{-- Columna principal --}}
    <div class="col-lg-8">

        {{-- Header del pedido --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <div class="flex-grow-1">
                        <h4 class="mb-1 fw-bold">{{ $specialOrder->folio }}</h4>
                        <span class="badge {{ $specialOrder->statusBadgeClass() }} fs-6 mb-2">{{ $specialOrder->statusLabel() }}</span>
                        <div class="text-muted small">Creado: {{ $specialOrder->created_at->format('d/m/Y H:i') }} por {{ $specialOrder->seller?->name }}</div>
                        @if($specialOrder->notes)
                        <div class="mt-2 p-2 bg-light rounded border-start border-3 border-info">
                            <i class="ri-chat-3-line text-info me-1"></i><em>{{ $specialOrder->notes }}</em>
                        </div>
                        @endif
                    </div>
                    <div class="text-end">
                        @if($specialOrder->expected_delivery_date)
                        <div class="small text-muted">Entrega esperada</div>
                        <div class="fw-semibold {{ $specialOrder->expected_delivery_date->isPast() && !$specialOrder->isDelivered() ? 'text-danger' : '' }}">
                            {{ $specialOrder->expected_delivery_date->format('d/m/Y') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Ítems del pedido --}}
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ri-box-3-line me-2 text-primary"></i>Productos del Pedido</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-center">Recibido</th>
                                <th class="text-center">Pendiente</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($specialOrder->items as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @php
                                            $varImg = $item->variant?->files?->first() ?? $item->product?->files?->first();
                                            $thumb = $varImg ? asset('storage/' . $varImg->path) : null;
                                        @endphp
                                        @if($thumb)
                                            <img src="{{ $thumb }}" alt="" class="rounded border p-1" style="width: 42px; height: 42px; object-fit: contain;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="rounded border bg-light text-muted align-items-center justify-content-center flex-shrink-0" style="display: none; width: 42px; height: 42px;">
                                                <i class="ri-image-line fs-18"></i>
                                            </div>
                                        @else
                                            <div class="rounded border bg-light text-muted d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                                                <i class="ri-image-line fs-18"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-medium">{{ $item->product?->name }}</div>
                                            @if($item->variant)
                                                <small class="text-muted d-block">{{ $item->variant->name }}</small>
                                            @endif
                                            @if($item->purchaseOrder)
                                                <div class="mt-1">
                                                    <a href="{{ route('purchase-orders.show', $item->purchaseOrder) }}" class="badge bg-primary-subtle text-primary border text-decoration-none" title="Ver Orden de Compra respaldante">
                                                        <i class="ri-file-list-3-line me-1"></i>OC: {{ $item->purchaseOrder->folio }} ({{ ucfirst($item->purchaseOrder->status) }})
                                                    </a>
                                                </div>
                                            @endif
                                            @if($item->notes)
                                                <div class="small text-info mt-1"><i class="ri-chat-3-line me-1"></i>{{ $item->notes }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center fw-semibold">{{ $item->quantity }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $item->quantity_received >= $item->quantity ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $item->quantity_received }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($item->pendingQuantity() > 0)
                                        <span class="badge bg-warning text-dark">{{ $item->pendingQuantity() }}</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">✓</span>
                                    @endif
                                </td>
                                <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end fw-semibold">${{ number_format($item->lineTotal(), 2) }}</td>
                                <td class="text-center">
                                    @if($item->pendingQuantity() > 0 && !$specialOrder->isDelivered() && !$specialOrder->isCancelled())
                                    <form method="POST" action="{{ route('special-orders.items.receive', [$specialOrder, $item]) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <div class="input-group input-group-sm" style="width:130px;">
                                            <input type="number" name="quantity_received" class="form-control text-center" value="{{ $item->pendingQuantity() }}" min="1" max="{{ $item->pendingQuantity() }}">
                                            <button type="submit" class="btn btn-sm btn-success" title="Marcar como recibido">
                                                <i class="ri-check-line"></i>
                                            </button>
                                        </div>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagos / Abonos del cliente --}}
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h6 class="card-title mb-0 flex-grow-1"><i class="ri-bank-card-line me-2 text-success"></i>Abonos del Cliente</h6>
                @if(!$specialOrder->isDelivered() && !$specialOrder->isCancelled() && $specialOrder->balanceDue() > 0)
                <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#formPayment">
                    <i class="ri-add-line me-1"></i> Registrar Abono
                </button>
                @endif
            </div>

            {{-- Formulario de abono colapsable --}}
            <div class="collapse" id="formPayment">
                <div class="card-body border-bottom bg-light">
                    <form method="POST" action="{{ route('special-orders.payments.add', $specialOrder) }}">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                           max="{{ $specialOrder->balanceDue() }}"
                                           placeholder="{{ number_format($specialOrder->balanceDue(), 2) }}" required>
                                </div>
                                <div class="form-text">Saldo: ${{ number_format($specialOrder->balanceDue(), 2) }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                                <select name="payment_method_id" class="form-select form-select-sm" required>
                                    @foreach(\App\Models\PaymentMethod::where('is_active', true)->get() as $pm)
                                        <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Terminal Bancaria</label>
                                <select name="payment_terminal_id" class="form-select form-select-sm">
                                    <option value="">— Ninguna / Efectivo —</option>
                                    @foreach(\App\Models\PaymentTerminal::where('is_active', true)->get() as $pt)
                                        <option value="{{ $pt->id }}">{{ $pt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Notas</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Referencia de transferencia, etc.">
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#formPayment">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                    <i class="ri-save-line me-1"></i> Guardar Abono
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                @if($specialOrder->payments->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="ri-money-dollar-box-line fs-2 mb-2 d-block"></i>Sin abonos registrados.
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Método</th>
                                <th>Caja / Turno</th>
                                <th>Notas</th>
                                <th>Registrado por</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($specialOrder->payments as $payment)
                            <tr>
                                <td class="text-muted small">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $payment->paymentMethod?->name }}</span>
                                    @if($payment->paymentTerminal)
                                        <span class="badge bg-info-subtle text-info border fs-10 d-block mt-1">{{ $payment->paymentTerminal->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->cashShift)
                                        <span class="badge bg-primary-subtle text-primary border"><i class="ri-safe-2-line me-1"></i>{{ $payment->cashShift->folio }}</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Sin turno</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $payment->notes ?: '—' }}</td>
                                <td class="text-muted small">{{ $payment->registeredBy?->name }}</td>
                                <td class="text-end fw-semibold text-success">${{ number_format($payment->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

    </div>{{-- /col-lg-8 --}}

    {{-- Columna lateral --}}
    <div class="col-lg-4">

        {{-- Resumen financiero --}}
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ri-pie-chart-line me-2 text-info"></i>Resumen Financiero</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total del pedido:</span>
                    <strong class="fs-5">${{ number_format($specialOrder->total, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total abonado:</span>
                    <span class="text-success fw-semibold">${{ number_format($specialOrder->totalPaid(), 2) }}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-semibold">Saldo pendiente:</span>
                    <span class="fw-bold fs-5 {{ $specialOrder->balanceDue() > 0 ? 'text-danger' : 'text-success' }}">
                        ${{ number_format($specialOrder->balanceDue(), 2) }}
                    </span>
                </div>
                {{-- Barra de progreso de pago --}}
                <div class="mb-1 d-flex justify-content-between small text-muted">
                    <span>Avance de pago</span>
                    <span>{{ $specialOrder->depositPercent() }}%</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar {{ $specialOrder->balanceDue() <= 0 ? 'bg-success' : 'bg-warning' }}"
                         style="width: {{ min(100, $specialOrder->depositPercent()) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Cliente --}}
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ri-user-line me-2 text-primary"></i>Cliente</h6>
            </div>
            <div class="card-body">
                <div class="fw-semibold fs-6">{{ $specialOrder->customer?->name }} {{ $specialOrder->customer?->last_name }}</div>
                @if($specialOrder->customer?->phone)
                    <div class="text-muted small"><i class="ri-phone-line me-1"></i>{{ $specialOrder->customer->phone }}</div>
                @endif
                @if($specialOrder->customer?->email)
                    <div class="text-muted small"><i class="ri-mail-line me-1"></i>{{ $specialOrder->customer->email }}</div>
                @endif
            </div>
        </div>

        {{-- Logística y Entrega a Domicilio --}}
        @if($specialOrder->hasDelivery())
        @php
            $soDelStatus = $specialOrder->delivery_status ?: 'pendiente';
            $soDelBadgeCls = match($soDelStatus) {
                'entregado' => 'bg-success',
                'en_ruta'   => 'bg-info',
                'cancelado' => 'bg-danger',
                default     => 'bg-primary',
            };
            $soDelBadgeLabel = match($soDelStatus) {
                'entregado' => '✓ Entregado',
                'en_ruta'   => '🚚 En Ruta',
                'cancelado' => 'Cancelado',
                default     => 'Pendiente',
            };
            $soEffDate = $specialOrder->delivery_date ?: $specialOrder->expected_delivery_date;
        @endphp
        <div class="card mb-4 border border-primary-subtle shadow-sm">
            <div class="card-header bg-primary-subtle py-2 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 text-primary fs-13"><i class="ri-truck-line me-2"></i>Entrega a Domicilio</h6>
                <span class="badge {{ $soDelBadgeCls }}" id="so-del-status-badge">{{ $soDelBadgeLabel }}</span>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="text-muted small d-block">Receptor / Destinatario:</span>
                    <strong>{{ $specialOrder->delivery_recipient_name ?: ($specialOrder->customer ? $specialOrder->customer->full_name : 'No especificado') }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted small d-block">Teléfono:</span>
                    <span><i class="ri-phone-line me-1 text-muted"></i>{{ $specialOrder->delivery_phone ?: ($specialOrder->customer ? $specialOrder->customer->phone : 'No registrado') }}</span>
                </div>
                <div class="mb-2">
                    <span class="text-muted small d-block">Dirección de Entrega:</span>
                    <span class="fw-medium text-dark">{{ $specialOrder->delivery_address ?: 'Dirección no especificada' }}</span>
                </div>
                @if($specialOrder->delivery_references)
                <div class="alert alert-warning py-1 px-2 mb-2 fs-11">
                    <strong>Ref:</strong> {{ $specialOrder->delivery_references }}
                </div>
                @endif
                <div class="mb-2">
                    <span class="text-muted small d-block">Fecha y Hora Programada:</span>
                    <strong class="text-primary" id="so-del-date-display">{{ $soEffDate ? \Carbon\Carbon::parse($soEffDate)->format('d/m/Y') : 'Por programar' }}</strong>
                    <small class="text-muted d-block fs-11" id="so-del-time-display"><i class="ri-time-line me-1"></i>{{ $specialOrder->formatted_delivery_time ?: 'Hora por definir' }}</small>
                </div>

                <div id="so-del-evidence-card" class="p-2 bg-success-subtle border border-success rounded mb-2 {{ $specialOrder->delivery_evidence_url ? '' : 'd-none' }}">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                        <span class="text-success fw-bold fs-12"><i class="ri-checkbox-circle-fill me-1"></i>Orden Firmada Registrada</span>
                        <a href="{{ $specialOrder->delivery_evidence_url ?: '#' }}" target="_blank" id="so-del-evidence-link" class="btn btn-xs btn-success py-0 px-2">Ver Comprobante</a>
                    </div>
                    <div id="so-del-evidence-preview" class="text-center mt-1">
                        @if($specialOrder->delivery_evidence_url)
                            @if($specialOrder->isEvidencePdf())
                                <a href="{{ $specialOrder->delivery_evidence_url }}" target="_blank" class="badge bg-white text-danger border p-2 text-decoration-none">
                                    <i class="ri-file-pdf-2-fill me-1"></i>Abrir PDF Firmado
                                </a>
                            @else
                                <a href="{{ $specialOrder->delivery_evidence_url }}" target="_blank">
                                    <img src="{{ $specialOrder->delivery_evidence_url }}" alt="Comprobante" class="img-thumbnail rounded" style="max-height: 140px; object-fit: contain;">
                                </a>
                            @endif
                        @endif
                    </div>
                </div>

                @if($specialOrder->delivery_cost > 0)
                <div class="d-flex justify-content-between mb-2 border-top pt-2">
                    <span class="text-muted small">Costo de Envío:</span>
                    <strong class="text-dark">${{ number_format($specialOrder->delivery_cost, 2) }}</strong>
                </div>
                @endif

                {{-- Formulario para modificar Fecha/Hora, Estado de Entrega y Subir Comprobante Firmado --}}
                <div class="border-top pt-3 mt-2">
                    <h6 class="text-uppercase text-primary fw-bold fs-11 mb-2">
                        <i class="ri-calendar-check-line me-1"></i> Programación y Registro de Entrega
                    </h6>
                    <form id="form-so-delivery-update" enctype="multipart/form-data" data-no-loader="true" onsubmit="submitSpecialOrderDeliveryUpdate(event)">
                        <div class="mb-2">
                            <label class="form-label fs-11 fw-semibold mb-1">Estado del Envío</label>
                            <select class="form-select form-select-sm" id="so-form-delivery-status" name="delivery_status" required onchange="toggleSoEvidenceUpload(this.value)">
                                <option value="pendiente" {{ $soDelStatus === 'pendiente' ? 'selected' : '' }}>Pendiente de Salida</option>
                                <option value="en_ruta" {{ $soDelStatus === 'en_ruta' ? 'selected' : '' }}>🚚 En Ruta de Entrega</option>
                                <option value="entregado" {{ $soDelStatus === 'entregado' ? 'selected' : '' }}>✓ Entregado (Con Comprobante)</option>
                                <option value="cancelado" {{ $soDelStatus === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-7">
                                <label class="form-label fs-11 fw-semibold mb-1">Fecha Promesa</label>
                                <input type="date" class="form-control form-control-sm" id="so-form-delivery-date" name="delivery_date" value="{{ $soEffDate ? \Carbon\Carbon::parse($soEffDate)->format('Y-m-d') : '' }}" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label fs-11 fw-semibold mb-1">Hora</label>
                                <input type="time" class="form-control form-control-sm" id="so-form-delivery-time" name="delivery_time" value="{{ $specialOrder->delivery_time ? substr($specialOrder->delivery_time, 0, 5) : '11:00' }}" required>
                            </div>
                        </div>
                        <div class="mb-2" id="so-evidence-upload-wrapper" style="display: {{ $soDelStatus === 'entregado' ? 'block' : 'none' }};">
                            <div class="p-2 border border-primary border-dashed rounded bg-primary-subtle bg-opacity-10">
                                <label class="form-label fw-bold text-primary fs-11 mb-1">
                                    <i class="ri-file-upload-line me-1"></i> Comprobante Firmado (Foto/PDF) <span class="text-danger" id="so-evidence-req-star" style="display: {{ $specialOrder->delivery_evidence_url ? 'none' : 'inline' }};">*</span>
                                </label>
                                <input type="file" class="form-control form-control-sm" id="so-form-evidence-file" name="evidence_file" accept=".jpg,.jpeg,.png,.webp,.pdf">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fs-11 fw-semibold mb-1">Instrucciones / Notas de Entrega</label>
                            <textarea class="form-control form-control-sm" id="so-form-delivery-instructions" name="delivery_instructions" rows="2" placeholder="Observaciones de entrega...">{{ $specialOrder->delivery_instructions }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success w-100 mb-2" id="btn-save-so-delivery">
                            <i class="ri-save-line me-1"></i> Guardar Cambios de Entrega
                        </button>
                    </form>
                </div>

                <div class="d-grid gap-2 mt-2 border-top pt-2">
                    <a href="{{ route('special-orders.delivery-order', $specialOrder) }}" target="_blank" class="btn btn-sm btn-primary">
                        <i class="ri-printer-line me-1"></i> Imprimir Orden para Chofer
                    </a>
                    <a href="{{ route('deliveries.calendar') }}" class="btn btn-sm btn-soft-info">
                        <i class="ri-calendar-todo-line me-1"></i> Ver en Agenda de Envíos
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Cambio de estado --}}
        @if(!$specialOrder->isDelivered() && !$specialOrder->isCancelled())
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ri-refresh-line me-2 text-warning"></i>Cambiar Estado</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('special-orders.status', $specialOrder) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <select name="status" class="form-select form-select-sm">
                            @foreach(['pending'=>'Pendiente','ordered'=>'En Pedido OC','partial'=>'Recibido Parcial','ready'=>'Listo para Entrega','delivered'=>'Entregado','cancelled'=>'Cancelado'] as $v => $l)
                                <option value="{{ $v }}" {{ $specialOrder->status === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($specialOrder->balanceDue() > 0)
                    <div class="alert alert-warning py-2 small mb-2">
                        <i class="ri-alert-line me-1"></i> No puede marcarse como <strong>Entregado</strong> con saldo pendiente.
                    </div>
                    @endif
                    <button type="submit" class="btn btn-sm btn-warning w-100 fw-semibold">
                        <i class="ri-save-line me-1"></i> Guardar Estado
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>{{-- /col-lg-4 --}}
</div>

{{-- Mensajes flash --}}
@if(session('success'))
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div class="toast show align-items-center text-white bg-success border-0">
        <div class="d-flex">
            <div class="toast-body"><i class="ri-check-line me-2"></i>{{ session('success') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div class="toast show align-items-center text-white bg-danger border-0">
        <div class="d-flex">
            <div class="toast-body"><i class="ri-error-warning-line me-2"></i>{{ session('error') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endif
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        let soHasEvidence = {{ $specialOrder->delivery_evidence_url ? 'true' : 'false' }};

        function toggleSoEvidenceUpload(statusVal) {
            const wrapper = document.getElementById('so-evidence-upload-wrapper');
            const reqStar = document.getElementById('so-evidence-req-star');
            if (!wrapper) return;
            if (statusVal === 'entregado') {
                wrapper.style.display = 'block';
                if (reqStar) reqStar.style.display = soHasEvidence ? 'none' : 'inline';
            } else {
                wrapper.style.display = 'none';
            }
        }

        function submitSpecialOrderDeliveryUpdate(e) {
            e.preventDefault();
            const status = document.getElementById('so-form-delivery-status').value;
            const fileInput = document.getElementById('so-form-evidence-file');

            if (status === 'entregado' && !soHasEvidence && (!fileInput.files || fileInput.files.length === 0)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Comprobante Requerido',
                    text: 'Para registrar el envío como ENTREGADO debes adjuntar una foto o PDF de la orden de entrega firmada.'
                });
                return;
            }

            const formData = new FormData();
            formData.append('delivery_status', status);
            formData.append('delivery_date', document.getElementById('so-form-delivery-date').value);
            formData.append('delivery_time', document.getElementById('so-form-delivery-time').value);
            formData.append('delivery_instructions', document.getElementById('so-form-delivery-instructions').value);

            if (fileInput.files && fileInput.files.length > 0) {
                formData.append('evidence_file', fileInput.files[0]);
            }

            const btn = document.getElementById('btn-save-so-delivery');
            const prevHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Guardando...';

            fetch(`{{ url('deliveries/special_order/' . $specialOrder->id . '/status') }}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
                        const badge = document.getElementById('so-del-status-badge');
                        const badgeMap = {
                            'pendiente': { text: 'Pendiente', cls: 'badge bg-primary' },
                            'en_ruta':   { text: '🚚 En Ruta', cls: 'badge bg-info' },
                            'entregado': { text: '✓ Entregado', cls: 'badge bg-success' },
                            'cancelado': { text: 'Cancelado', cls: 'badge bg-danger' }
                        };
                        const st = badgeMap[props.delivery_status || status] || badgeMap['pendiente'];
                        if (badge) {
                            badge.className = st.cls;
                            badge.innerText = st.text;
                        }

                        if (props.delivery_date_formatted) {
                            document.getElementById('so-del-date-display').innerText = props.delivery_date_formatted;
                        }
                        if (props.delivery_time_formatted) {
                            document.getElementById('so-del-time-display').innerHTML = `<i class="ri-time-line me-1"></i>${props.delivery_time_formatted}`;
                        }

                        if (props.has_evidence && props.evidence_url) {
                            soHasEvidence = true;
                            const evCard = document.getElementById('so-del-evidence-card');
                            const evLink = document.getElementById('so-del-evidence-link');
                            const evPrev = document.getElementById('so-del-evidence-preview');
                            if (evCard) evCard.classList.remove('d-none');
                            if (evLink) evLink.href = props.evidence_url;
                            if (evPrev) {
                                evPrev.innerHTML = props.is_evidence_pdf
                                    ? `<a href="${props.evidence_url}" target="_blank" class="badge bg-white text-danger border p-2 text-decoration-none"><i class="ri-file-pdf-2-fill me-1"></i>Abrir PDF Firmado</a>`
                                    : `<a href="${props.evidence_url}" target="_blank"><img src="${props.evidence_url}" alt="Comprobante" class="img-thumbnail rounded" style="max-height: 140px; object-fit: contain;"></a>`;
                            }
                            fileInput.value = '';
                            toggleSoEvidenceUpload(props.delivery_status || status);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
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
                    console.error(err);
                    Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
                });
        }
    </script>
@endpush
