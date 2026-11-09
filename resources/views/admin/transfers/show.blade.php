@extends('layouts.master')
@section('title') Traspaso: {{ $transfer->folio }} @endsection

@section('css')
<style>
/* Timeline de estados */
.timeline-steps { display: flex; align-items: flex-start; gap: 0; }
.timeline-step  { flex: 1; text-align: center; position: relative; }
.timeline-step::before {
    content: '';
    position: absolute;
    top: 18px;
    left: 50%;
    right: -50%;
    height: 2px;
    background: var(--vz-border-color);
    z-index: 0;
}
.timeline-step:last-child::before { display: none; }
.timeline-step .step-icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 6px;
    font-size: 16px;
    position: relative; z-index: 1;
    border: 2px solid var(--vz-border-color);
    background: var(--vz-card-bg);
    color: var(--vz-secondary-color);
}
.timeline-step.done   .step-icon { background: var(--vz-success); color:#fff; border-color: var(--vz-success); }
.timeline-step.active .step-icon { background: var(--vz-primary); color:#fff; border-color: var(--vz-primary); }
.timeline-step.done::before, .timeline-step.active::before { background: var(--vz-success); }
.timeline-step .step-label { font-size: 11px; color: var(--vz-secondary-color); }
.timeline-step.done .step-label, .timeline-step.active .step-label { color: inherit; font-weight: 600; }
</style>
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-8">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Cabecera del traspaso --}}
            <div class="card mb-3">
                <div class="card-body">

                    {{-- Timeline --}}
                    @php
                        $steps = [
                            'pendiente'   => ['icon' => 'ri-file-list-line',   'label' => 'Creado'],
                            'en_transito' => ['icon' => 'ri-truck-line',        'label' => 'En Tránsito'],
                            'recibido'    => ['icon' => 'ri-checkbox-circle-line','label' => 'Recibido'],
                        ];
                        $statusOrder = ['pendiente', 'en_transito', 'recibido'];
                        $currentIdx  = array_search($transfer->status, $statusOrder);
                        $isFinished  = $transfer->isFinished();
                    @endphp
                    @if(!$transfer->isRechazado() && !$transfer->isCancelado())
                        <div class="timeline-steps mb-4">
                            @foreach($steps as $key => $step)
                                @php
                                    $stepIdx = array_search($key, $statusOrder);
                                    $isDone  = $stepIdx < ($currentIdx ?? 0) || ($transfer->isRecibido() && $stepIdx <= $currentIdx);
                                    $isAct   = $key === $transfer->status;
                                @endphp
                                <div class="timeline-step {{ $isDone ? 'done' : ($isAct ? 'active' : '') }}">
                                    <div class="step-icon"><i class="{{ $step['icon'] }}"></i></div>
                                    <div class="step-label">{{ $step['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        @php $badge = $transfer->statusBadge(); @endphp
                        <div class="alert alert-{{ $badge['color'] }} mb-3">
                            <i class="ri-information-line me-2"></i>
                            Traspaso <strong>{{ $badge['label'] }}</strong>
                            @if($transfer->isRechazado() && $transfer->rejection_reason)
                                — {{ $transfer->rejection_reason }}
                            @endif
                        </div>
                    @endif

                    {{-- Info general --}}
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="fs-11 text-muted text-uppercase fw-semibold">Folio</div>
                            <div class="fw-bold fs-16">{{ $transfer->folio }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-11 text-muted text-uppercase fw-semibold">Origen</div>
                            <span class="badge bg-warning-subtle text-warning fs-13">{{ $transfer->originStore->name }}</span>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-11 text-muted text-uppercase fw-semibold">Destino</div>
                            <span class="badge bg-info-subtle text-info fs-13">{{ $transfer->destinationStore->name }}</span>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-11 text-muted text-uppercase fw-semibold">Creado por</div>
                            <div class="fs-13">{{ $transfer->creator?->name }}</div>
                            <div class="fs-11 text-muted">{{ $transfer->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        @if($transfer->sender)
                            <div class="col-md-3">
                                <div class="fs-11 text-muted text-uppercase fw-semibold">Enviado por</div>
                                <div class="fs-13">{{ $transfer->sender->name }}</div>
                                <div class="fs-11 text-muted">{{ $transfer->sent_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        @endif
                        @if($transfer->receiver)
                            <div class="col-md-3">
                                <div class="fs-11 text-muted text-uppercase fw-semibold">{{ $transfer->isRecibido() ? 'Recibido por' : 'Rechazado por' }}</div>
                                <div class="fs-13">{{ $transfer->receiver->name }}</div>
                                <div class="fs-11 text-muted">{{ $transfer->received_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        @endif
                        @if($transfer->notes)
                            <div class="col-12">
                                <div class="fs-11 text-muted text-uppercase fw-semibold">Notas de Creación</div>
                                <div class="fs-13">{{ $transfer->notes }}</div>
                            </div>
                        @endif
                        @if($transfer->receiver_notes)
                            <div class="col-12">
                                <div class="fs-11 text-muted text-uppercase fw-semibold">Notas de Recepción</div>
                                <div class="fs-13 text-danger fw-semibold bg-danger-subtle p-2 rounded border border-danger-subtle"><i class="ri-feedback-line me-1"></i>{{ $transfer->receiver_notes }}</div>
                            </div>
                        @endif
                        @if($transfer->proofPhoto)
                            <div class="col-12">
                                <div class="fs-11 text-muted text-uppercase fw-semibold">Fotografía de Evidencia (Recepción)</div>
                                <div class="mt-2">
                                    <a href="{{ asset('storage/' . $transfer->proofPhoto->path) }}" target="_blank" class="image-popup d-inline-block">
                                        <img src="{{ asset('storage/' . $transfer->proofPhoto->path) }}" 
                                             alt="Fotografía de evidencia" 
                                             class="img-thumbnail rounded shadow-sm border" 
                                             style="max-width: 250px; max-height: 200px; object-fit: cover;">
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tabla de artículos --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-list-check me-2"></i>Productos</h6>
                </div>

                {{-- Formulario de cantidades enviadas (solo origen, estado pendiente) --}}
                @if($isOrigin && $transfer->isPendiente())
                <form action="{{ route('transfers.ship', $transfer) }}" method="POST" id="ship-form">
                    @csrf
                @endif

                {{-- Formulario de cantidades recibidas (solo destino, estado en tránsito) --}}
                @if($isDest && $transfer->isEnTransito())
                <form action="{{ route('transfers.receive', $transfer) }}" method="POST" id="receive-form" enctype="multipart/form-data">
                    @csrf
                @endif

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Solicitado</th>
                                <th class="text-center">Enviado</th>
                                <th class="text-center">Recibido</th>
                                @if($isOrigin && $transfer->isPendiente())
                                    <th class="text-center">¿Cuánto enviar?</th>
                                @endif
                                @if($isDest && $transfer->isEnTransito())
                                    <th class="text-center">¿Cuánto recibir?</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfer->items as $item)
                            @php
                                $avLabels = $item->variant
                                    ? $item->variant->attributeValues->map(fn($av) => '<span class="badge bg-light text-dark border me-1">' . e($av->attribute->name) . ': ' . e($av->value) . '</span>')->join('')
                                    : '';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold fs-13">{{ $item->product->name }}</div>
                                    @if($avLabels) <div class="mt-1">{!! $avLabels !!}</div> @endif
                                    <small class="text-muted">SKU: {{ $item->variant?->sku ?? $item->product->sku }}</small>
                                </td>
                                <td class="text-center fw-bold">{{ $item->quantity_requested }}</td>
                                <td class="text-center">
                                    <span class="{{ is_null($item->quantity_sent) ? 'text-muted' : '' }}">
                                        {{ $item->quantity_sent ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="{{ is_null($item->quantity_received) ? 'text-muted' : ($item->quantity_received < $item->quantity_sent ? 'text-warning fw-bold' : 'text-success fw-bold') }}">
                                        {{ $item->quantity_received ?? '—' }}
                                    </span>
                                </td>
                                @if($isOrigin && $transfer->isPendiente())
                                <td class="text-center">
                                    <input type="number" name="quantity_sent[{{ $item->id }}]"
                                           class="form-control form-control-sm text-center"
                                           style="width:80px;margin:auto"
                                           value="{{ $item->quantity_requested }}" min="0"
                                           max="{{ $item->quantity_requested }}">
                                </td>
                                @endif
                                @if($isDest && $transfer->isEnTransito())
                                <td class="text-center">
                                    <input type="number" name="quantity_received[{{ $item->id }}]"
                                           class="form-control form-control-sm text-center"
                                           style="width:80px;margin:auto"
                                           value="{{ $item->quantity_sent }}" min="0"
                                           max="{{ $item->quantity_sent }}">
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($isOrigin && $transfer->isPendiente())
                </form>
                @endif
                @if($isDest && $transfer->isEnTransito())
                </form>
                @endif
            </div>

        </div>

        {{-- Panel de acciones --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-settings-line me-2"></i>Acciones</h6>
                </div>
                <div class="card-body d-grid gap-2">

                    {{-- ORIGEN: Pendiente → Enviar --}}
                    @if($isOrigin && $transfer->isPendiente())
                        @canany(['autorizar-traspasos', 'gestionar-inventario'])
                        <div class="alert alert-warning py-2 mb-0">
                            <i class="ri-information-line me-1"></i>
                            Ajusta las cantidades a enviar en la tabla y confirma el envío.
                        </div>
                        <button type="submit" form="ship-form" class="btn btn-primary btn-sm"
                                data-confirm="true"
                                data-form="ship-form"
                                data-title="Confirmar Envío"
                                data-message="Al confirmar, el stock se descontará de <strong>{{ $transfer->originStore->name }}</strong>. ¿Deseas continuar?"
                                data-btn-text="Sí, enviar">
                            <i class="ri-truck-line me-1"></i> Confirmar Envío
                        </button>
                        <form action="{{ route('transfers.cancel', $transfer) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                                    data-confirm="true"
                                    data-title="Cancelar Traspaso"
                                    data-message="¿Estás seguro de cancelar este traspaso? Esta acción no se puede deshacer."
                                    data-btn-text="Sí, cancelar">
                                <i class="ri-close-circle-line me-1"></i> Cancelar Traspaso
                            </button>
                        </form>
                        @endcanany
                    @endif

                    {{-- DESTINO: En tránsito → Recibir / Rechazar --}}
                    @if($isDest && $transfer->isEnTransito())
                        @canany(['recibir-traspasos', 'gestionar-inventario'])
                        <div class="alert alert-info py-2 mb-0">
                            <i class="ri-information-line me-1"></i>
                            Confirma las cantidades recibidas en la tabla.
                        </div>

                        <div class="border rounded p-3 bg-light-subtle my-2">
                            <div class="mb-3">
                                <label for="receiver_notes" class="form-label fw-semibold fs-12 text-uppercase mb-1">Notas / Observaciones del Receptor</label>
                                <textarea name="receiver_notes" id="receiver_notes" form="receive-form"
                                          class="form-control form-control-sm" rows="3"
                                          placeholder="Describa discrepancias, daños o comentarios..."></textarea>
                            </div>
                            <div class="mb-0">
                                <label for="proof_photo" class="form-label fw-semibold fs-12 text-uppercase mb-1">Fotografía de Evidencia</label>
                                <input type="file" name="proof_photo" id="proof_photo" form="receive-form"
                                       class="form-control form-control-sm" accept="image/*">
                                <small class="text-muted fs-11">Recomendado si hay discrepancias.</small>
                            </div>
                        </div>

                        <button type="submit" form="receive-form" class="btn btn-success btn-sm w-100"
                                data-confirm="true"
                                data-form="receive-form"
                                data-title="Confirmar Recepción"
                                data-message="Al confirmar, el stock se sumará a <strong>{{ $transfer->destinationStore->name }}</strong>. ¿Deseas continuar?"
                                data-btn-text="Sí, recibir">
                            <i class="ri-checkbox-circle-line me-1"></i> Confirmar Recepción
                        </button>

                        {{-- Modal de rechazo --}}
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 mt-1" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ri-close-circle-line me-1"></i> Rechazar Traspaso
                        </button>
                        @endcanany
                    @endif

                    {{-- Estado final --}}
                    @if($transfer->isFinished())
                        @php $badge = $transfer->statusBadge(); @endphp
                        <div class="text-center py-3">
                            <i class="ri-checkbox-circle-line fs-32 text-{{ $badge['color'] }}"></i>
                            <p class="fw-semibold mt-2 mb-0">{{ $badge['label'] }}</p>
                        </div>
                    @endif

                    {{-- Global: solo puede ver --}}
                    @if(!$isOrigin && !$isDest && !$isGlobal)
                        <p class="text-muted text-center fs-13 mb-0">Sin acciones disponibles para tu sucursal.</p>
                    @endif

                    <a href="{{ route('transfers.index') }}" class="btn btn-ghost-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de rechazo --}}
    @if($isDest && $transfer->isEnTransito())
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('transfers.reject', $transfer) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-close-circle-line me-2 text-danger"></i>Rechazar Traspaso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ri-alert-line me-1"></i>
                        Al rechazar, el stock será <strong>devuelto automáticamente</strong> a {{ $transfer->originStore->name }}.
                    </div>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label fw-semibold">
                            Motivo del rechazo <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3"
                                  class="form-control form-control-sm"
                                  placeholder="Describe el motivo: producto dañado, cantidad incorrecta, etc." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="ri-close-circle-line me-1"></i> Rechazar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection
