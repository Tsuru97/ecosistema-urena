@extends('layouts.master')
@section('title')
    Agenda y Calendario de Envíos
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <x-breadcrumb title="Agenda de Envíos a Domicilio" />

    <!-- Tarjetas de Resumen Operativo -->
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate mb-0 border-start border-primary border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Programados para Hoy</p>
                            <h4 class="mb-0 text-primary" id="stat-today">{{ $stats['today'] ?? 0 }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                <i class="ri-calendar-check-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate mb-0 border-start border-warning border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Pendientes de Salida</p>
                            <h4 class="mb-0 text-warning" id="stat-pending">{{ $stats['pending'] ?? 0 }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                <i class="ri-time-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate mb-0 border-start border-info border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">En Ruta de Entrega</p>
                            <h4 class="mb-0 text-info" id="stat-in-route">{{ $stats['in_route'] ?? 0 }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                <i class="ri-truck-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate mb-0 border-start border-success border-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Entregados (Con Respaldo)</p>
                            <h4 class="mb-0 text-success" id="stat-delivered">{{ $stats['delivered'] ?? 0 }}</h4>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                <i class="ri-checkbox-circle-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="row">
                <!-- Columna Izquierda (Basada en apps-calendar.blade.php) -->
                <div class="col-xl-3">
                    <div class="card card-h-100">
                        <div class="card-body">
                            <a href="{{ route('pos.index') }}" class="btn btn-primary w-100 mb-3">
                                <i class="ri-add-circle-line me-1"></i> Programar Nuevo Envío en POS
                            </a>

                            <!-- Filtros del Calendario -->
                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-uppercase text-muted mb-1">Filtrar por Estado</label>
                                <select id="filter-delivery-status" class="form-select form-select-sm mb-2">
                                    <option value="all">Todos los Estados (Incluye Entregados)</option>
                                    <option value="pendiente">Solo Pendientes</option>
                                    <option value="en_ruta">Solo En Ruta</option>
                                    <option value="entregado">Solo Entregados</option>
                                    <option value="cancelado">Cancelados</option>
                                </select>

                                <label class="form-label fs-11 fw-bold text-uppercase text-muted mb-1">Filtrar por Tipo</label>
                                <select id="filter-delivery-type" class="form-select form-select-sm">
                                    <option value="all">Ventas y Pedidos Especiales</option>
                                    <option value="sale">Solo Ventas Normales</option>
                                    <option value="special_order">Solo Pedidos Especiales</option>
                                </select>
                            </div>

                            <!-- Leyenda de Estados -->
                            <div id="external-events">
                                <p class="text-muted fs-12 fw-semibold mb-2">Código de Colores en Calendario</p>
                                <div class="external-event fc-event bg-primary-subtle text-primary mb-2 py-1 px-2 rounded" data-class="bg-primary-subtle">
                                    <i class="mdi mdi-checkbox-blank-circle font-size-11 me-2"></i>Venta Normal (Pendiente)
                                </div>
                                <div class="external-event fc-event bg-warning-subtle text-warning mb-2 py-1 px-2 rounded" data-class="bg-warning-subtle">
                                    <i class="mdi mdi-checkbox-blank-circle font-size-11 me-2"></i>Pedido Especial (Pendiente)
                                </div>
                                <div class="external-event fc-event bg-info-subtle text-info mb-2 py-1 px-2 rounded" data-class="bg-info-subtle">
                                    <i class="mdi mdi-checkbox-blank-circle font-size-11 me-2"></i>En Ruta de Entrega
                                </div>
                                <div class="external-event fc-event bg-success-subtle text-success mb-0 py-1 px-2 rounded" data-class="bg-success-subtle">
                                    <i class="ri-checkbox-circle-fill me-2"></i>Entregado (Orden Firmada)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Próximas Entregas -->
                    <div>
                        <h5 class="mb-1">Próximas Entregas</h5>
                        <p class="text-muted fs-12">Agenda de envíos programados por fecha y hora</p>
                        <div class="pe-2 me-n1 mb-3" data-simplebar style="height: 380px">
                            <div id="upcoming-event-list">
                                @forelse($upcomingDeliveries as $item)
                                    @php
                                        $badgeClass = match($item['status']) {
                                            'entregado' => 'bg-success-subtle text-success',
                                            'en_ruta'   => 'bg-info-subtle text-info',
                                            'cancelado' => 'bg-danger-subtle text-danger',
                                            default     => $item['entity_type'] === 'special_order' ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary',
                                        };
                                        $statusText = match($item['status']) {
                                            'entregado' => '✓ Entregado',
                                            'en_ruta'   => 'En Ruta',
                                            'cancelado' => 'Cancelado',
                                            default     => 'Pendiente',
                                        };
                                    @endphp
                                    <div class="card mb-2 shadow-none border upcoming-delivery-card" style="cursor: pointer;" onclick="openEventFromSidebar('{{ $item['event_id'] }}')">
                                        <div class="card-body p-2">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="badge {{ $badgeClass }} fs-10">{{ $statusText }}</span>
                                                <small class="text-muted fw-semibold fs-11">
                                                    <i class="ri-time-line me-1 text-primary"></i>{{ $item['date_formatted'] }} · {{ $item['time_formatted'] }}
                                                </small>
                                            </div>
                                            <h6 class="mb-1 fs-13 text-dark fw-semibold">{{ $item['folio'] }} — {{ $item['recipient_name'] }}</h6>
                                            <p class="text-muted mb-0 fs-11 text-truncate" title="{{ $item['address'] }}">
                                                <i class="ri-map-pin-line me-1"></i>{{ $item['address'] }}
                                            </p>
                                            @if($item['has_evidence'])
                                                <div class="mt-1">
                                                    <span class="badge bg-success-subtle text-success fs-10">
                                                        <i class="ri-file-shield-2-line me-1"></i>Respaldo Firmado Adjunto
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-4 text-muted">
                                        <i class="ri-calendar-event-line fs-24 d-block mb-1"></i>
                                        No hay entregas próximas programadas.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body bg-info-subtle">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="ri-shield-check-line text-info fs-24"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fs-14">Control de Entregas</h6>
                                    <p class="text-muted mb-0 fs-12">
                                        Haz clic en cualquier envío del calendario para consultar su ubicación en Google Maps, imprimir la orden de entrega o subir la <strong>foto/PDF firmado</strong> para marcarlo como entregado.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- end col-->

                <!-- Columna Derecha: Calendario Principal -->
                <div class="col-xl-9">
                    <div class="card card-h-100">
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div><!-- end col -->
            </div>
            <!--end row-->

            <div style="clear:both"></div>

            <!-- Modal de Detalle, Reprogramación y Evidencia de Entrega -->
            <div class="modal fade" id="event-modal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header p-3 bg-info-subtle">
                            <div>
                                <h5 class="modal-title fw-bold mb-0" id="modal-title">Detalle de Envío a Domicilio</h5>
                                <small class="text-muted" id="modal-subtitle">Folio y Estado</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                        </div>
                        <div class="modal-body p-4">
                            <!-- Información del Envío -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded border h-100">
                                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-2">
                                            <i class="ri-user-location-line me-1 text-primary"></i> Destinatario y Contacto
                                        </h6>
                                        <div class="mb-2">
                                            <span class="text-muted fs-11 d-block">Recibe:</span>
                                            <strong class="fs-14 text-dark" id="detail-recipient-name">—</strong>
                                        </div>
                                        <div class="mb-2">
                                            <span class="text-muted fs-11 d-block">Cliente Titular:</span>
                                            <span class="fs-13" id="detail-customer-name">—</span>
                                        </div>
                                        <div class="mb-0">
                                            <span class="text-muted fs-11 d-block">Teléfono:</span>
                                            <span class="fs-13 fw-semibold" id="detail-phone">—</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded border h-100">
                                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-2">
                                            <i class="ri-calendar-todo-line me-1 text-primary"></i> Programación y Artículos
                                        </h6>
                                        <div class="mb-2">
                                            <span class="text-muted fs-11 d-block">Fecha y Hora Programada:</span>
                                            <strong class="fs-14 text-primary" id="detail-schedule-tag">—</strong>
                                        </div>
                                        <div class="mb-2">
                                            <span class="text-muted fs-11 d-block">Artículos a Entregar:</span>
                                            <span class="fs-12 text-dark" id="detail-items-summary">—</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center pt-1 border-top">
                                            <span class="text-muted fs-11">Sucursal: <strong id="detail-store-name">—</strong></span>
                                            <span class="badge fs-11" id="detail-status-badge">Pendiente</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dirección y Mapa -->
                            <div class="p-3 bg-light rounded border mb-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h6 class="text-uppercase text-muted fs-11 fw-bold mb-1">
                                            <i class="ri-map-pin-2-line me-1 text-danger"></i> Domicilio de Entrega
                                        </h6>
                                        <p class="mb-1 fw-semibold text-dark fs-13" id="detail-address">—</p>
                                        <p class="mb-1 text-muted fs-12 fst-italic" id="detail-references"></p>
                                        <p class="mb-0 text-info fs-12" id="detail-instructions"></p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="#" target="_blank" id="btn-detail-maps" class="btn btn-sm btn-outline-primary d-none">
                                            <i class="ri-map-pin-line me-1"></i> Ver en Google Maps
                                        </a>
                                        <a href="#" target="_blank" id="btn-detail-print-order" class="btn btn-sm btn-soft-dark">
                                            <i class="ri-printer-line me-1"></i> Imprimir Orden de Entrega
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta de Evidencia Registrada (Visible si ya tiene foto o PDF firmado) -->
                            <div id="existing-evidence-card" class="p-3 bg-success-subtle border border-success rounded mb-3 d-none">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <div>
                                        <h6 class="text-success fw-bold mb-0 fs-13">
                                            <i class="ri-checkbox-circle-fill me-1"></i> Envío Entregado y Respaldado con Orden Firmada
                                        </h6>
                                        <small class="text-muted fs-11" id="existing-evidence-meta"></small>
                                    </div>
                                    <a href="#" target="_blank" id="btn-view-full-evidence" class="btn btn-sm btn-success">
                                        <i class="ri-external-link-line me-1"></i> Abrir / Descargar Orden Firmada
                                    </a>
                                </div>
                                <div id="existing-evidence-preview" class="text-center mt-2"></div>
                            </div>

                            <!-- Formulario de Actualización de Estado, Horario y Subida de Orden Firmada -->
                            <form id="form-delivery-update" enctype="multipart/form-data" data-no-loader="true">
                                <input type="hidden" id="form-entity-type" name="entity_type">
                                <input type="hidden" id="form-entity-id" name="entity_id">

                                <h6 class="text-uppercase text-muted fs-11 fw-bold mb-2 border-top pt-3">
                                    <i class="ri-settings-4-line me-1"></i> Actualizar Estado / Horario / Registrar Entrega
                                </h6>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fs-12 fw-semibold">Estado del Envío <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="form-delivery-status" name="delivery_status" required>
                                            <option value="pendiente">Pendiente de Salida</option>
                                            <option value="en_ruta">🚚 En Ruta de Entrega</option>
                                            <option value="entregado">✓ Entregado (Con Orden Firmada)</option>
                                            <option value="cancelado">Cancelado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fs-12 fw-semibold">Fecha Promesa de Entrega</label>
                                        <input type="date" class="form-control form-control-sm" id="form-delivery-date" name="delivery_date" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fs-12 fw-semibold">Hora Específica</label>
                                        <input type="time" class="form-control form-control-sm" id="form-delivery-time" name="delivery_time" required>
                                    </div>

                                    <!-- Bloque para adjuntar Foto o PDF de la Orden de Entrega Firmada -->
                                    <div class="col-12" id="evidence-upload-wrapper" style="display: none;">
                                        <div class="p-3 border border-primary border-dashed rounded bg-primary-subtle bg-opacity-10">
                                            <label for="form-evidence-file" class="form-label fw-bold text-primary fs-13 mb-1">
                                                <i class="ri-file-upload-line me-1"></i> Respaldo de Orden de Entrega Firmada (Foto o PDF) <span class="text-danger" id="evidence-required-star">*</span>
                                            </label>
                                            <p class="text-muted fs-11 mb-2">
                                                Sube una fotografía clara (<code>JPG</code>, <code>PNG</code>, <code>WEBP</code>) o el escaneo en <code>PDF</code> de la orden de entrega firmada de conformidad por el cliente.
                                            </p>
                                            <input type="file" class="form-control form-control-sm" id="form-evidence-file" name="evidence_file" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                            <div id="new-evidence-preview" class="mt-2 d-none"></div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fs-12 fw-semibold">Notas / Instrucciones de Entrega</label>
                                        <textarea class="form-control form-control-sm" id="form-delivery-instructions" name="delivery_instructions" rows="2" placeholder="Observaciones del envío o recepción..."></textarea>
                                    </div>
                                </div>

                                <div class="hstack gap-2 justify-content-end mt-4">
                                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="submit" class="btn btn-success btn-sm" id="btn-save-delivery-update">
                                        <i class="ri-save-line me-1"></i> Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div> <!-- end modal-content-->
                </div> <!-- end modal dialog-->
            </div> <!-- end modal-->
        </div>
    </div> <!-- end row-->
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/fullcalendar/index.global.min.js') }}"></script>
    <script>
        let deliveryCalendar = null;
        let deliveryModal = null;
        let currentSelectedEvent = null;
        const deliveriesBaseUrl = "{{ url('deliveries') }}";

        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('event-modal');
            deliveryModal = new bootstrap.Modal(modalEl, { keyboard: false });

            const calendarEl = document.getElementById('calendar');
            const statusFilterEl = document.getElementById('filter-delivery-status');
            const typeFilterEl = document.getElementById('filter-delivery-type');
            const formStatusSelect = document.getElementById('form-delivery-status');
            const evidenceFileInput = document.getElementById('form-evidence-file');
            const newEvidencePreview = document.getElementById('new-evidence-preview');

            function getInitialView() {
                if (window.innerWidth >= 768 && window.innerWidth < 1200) {
                    return 'timeGridWeek';
                } else if (window.innerWidth <= 768) {
                    return 'listMonth';
                } else {
                    return 'dayGridMonth';
                }
            }

            deliveryCalendar = new FullCalendar.Calendar(calendarEl, {
                timeZone: 'local',
                locale: 'es',
                editable: true,
                droppable: false,
                selectable: false,
                navLinks: true,
                initialView: getInitialView(),
                themeSystem: 'bootstrap',
                slotMinTime: '08:00:00',
                slotMaxTime: '21:00:00',
                allDaySlot: false,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana (Horas)',
                    day: 'Día (Horas)',
                    list: 'Lista Agenda'
                },
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: 'short',
                    hour12: true
                },
                windowResize: function () {
                    const newView = getInitialView();
                    deliveryCalendar.changeView(newView);
                },
                events: function (fetchInfo, successCallback, failureCallback) {
                    const params = new URLSearchParams({
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr,
                        status: statusFilterEl.value,
                        type: typeFilterEl.value,
                        _t: Date.now()
                    });

                    fetch(`{{ route('deliveries.events') }}?` + params.toString(), {
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(res => res.json())
                        .then(data => successCallback(data))
                        .catch(err => {
                            console.error('Error cargando eventos de envíos:', err);
                            failureCallback(err);
                        });
                },
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    currentSelectedEvent = info.event;
                    populateDeliveryModal(info.event);
                    deliveryModal.show();
                },
                eventDrop: function (info) {
                    handleEventReschedule(info);
                }
            });

            deliveryCalendar.render();

            // Recargar al cambiar filtros
            statusFilterEl.addEventListener('change', () => deliveryCalendar.refetchEvents());
            typeFilterEl.addEventListener('change', () => deliveryCalendar.refetchEvents());

            // Mostrar/Ocultar bloque de subida de evidencia cuando el estado es "entregado"
            formStatusSelect.addEventListener('change', function () {
                toggleEvidenceUploadBlock(this.value);
            });

            // Previsualización local de archivo seleccionado (Foto o PDF)
            evidenceFileInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) {
                    newEvidencePreview.classList.add('d-none');
                    newEvidencePreview.innerHTML = '';
                    return;
                }
                newEvidencePreview.classList.remove('d-none');
                if (file.type === 'application/pdf') {
                    newEvidencePreview.innerHTML = `
                        <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center">
                            <i class="ri-file-pdf-2-line fs-20 me-2 text-danger"></i>
                            <div>
                                <strong class="fs-12 d-block">Documento PDF listo para subir:</strong>
                                <span class="fs-11">${file.name} (${(file.size / 1024).toFixed(1)} KB)</span>
                            </div>
                        </div>`;
                } else if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        newEvidencePreview.innerHTML = `
                            <div class="border rounded p-2 bg-white text-center">
                                <span class="fs-11 text-muted d-block mb-1">Vista previa de foto de orden firmada:</span>
                                <img src="${e.target.result}" alt="Vista previa" class="img-fluid rounded shadow-sm" style="max-height: 180px;">
                            </div>`;
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Envío del formulario de actualización y evidencia
            const formUpdate = document.getElementById('form-delivery-update');
            formUpdate.addEventListener('submit', function (e) {
                e.preventDefault();
                submitDeliveryUpdate();
            });
        });

        function toggleEvidenceUploadBlock(statusValue) {
            const wrapper = document.getElementById('evidence-upload-wrapper');
            const hasExisting = currentSelectedEvent?.extendedProps?.has_evidence || false;
            const reqStar = document.getElementById('evidence-required-star');

            if (statusValue === 'entregado') {
                wrapper.style.display = 'block';
                if (reqStar) reqStar.style.display = hasExisting ? 'none' : 'inline';
            } else {
                wrapper.style.display = 'none';
            }
        }

        function populateDeliveryModal(eventObj) {
            const props = eventObj.extendedProps;

            document.getElementById('modal-title').innerText = `${props.folio} — ${props.recipient_name}`;
            document.getElementById('modal-subtitle').innerText = `${props.type_label} · ${props.store_name}`;

            document.getElementById('detail-recipient-name').innerText = props.recipient_name || '—';
            document.getElementById('detail-customer-name').innerText = props.customer_name || '—';
            document.getElementById('detail-phone').innerHTML = props.phone
                ? `<a href="tel:${props.phone}" class="text-primary text-decoration-none"><i class="ri-phone-line me-1"></i>${props.phone}</a>`
                : 'No registrado';

            document.getElementById('detail-schedule-tag').innerText = `${props.delivery_date_formatted} a las ${props.delivery_time_formatted}`;
            document.getElementById('detail-items-summary').innerText = props.items_summary || '—';
            document.getElementById('detail-store-name').innerText = props.store_name || '—';

            // Badge de estado
            const statusBadge = document.getElementById('detail-status-badge');
            const statusMap = {
                'pendiente': { text: 'Pendiente', cls: 'badge bg-warning-subtle text-warning' },
                'en_ruta':   { text: '🚚 En Ruta', cls: 'badge bg-info-subtle text-info' },
                'entregado': { text: '✓ Entregado', cls: 'badge bg-success-subtle text-success' },
                'cancelado': { text: 'Cancelado', cls: 'badge bg-danger-subtle text-danger' }
            };
            const st = statusMap[props.delivery_status] || statusMap['pendiente'];
            statusBadge.className = st.cls;
            statusBadge.innerText = st.text;

            // Dirección
            document.getElementById('detail-address').innerText = props.address || 'Sin dirección';
            document.getElementById('detail-references').innerText = props.references ? `Referencias: ${props.references}` : '';
            document.getElementById('detail-instructions').innerText = props.instructions ? `Instrucciones: ${props.instructions}` : '';

            // Botones Maps y Orden de Entrega
            const btnMaps = document.getElementById('btn-detail-maps');
            if (props.maps_url) {
                btnMaps.href = props.maps_url;
                btnMaps.classList.remove('d-none');
            } else {
                btnMaps.classList.add('d-none');
            }

            const btnPrintOrder = document.getElementById('btn-detail-print-order');
            if (props.delivery_order_url) {
                btnPrintOrder.href = props.delivery_order_url;
                btnPrintOrder.classList.remove('d-none');
            } else {
                btnPrintOrder.classList.add('d-none');
            }

            // Evidencia existente
            const existingCard = document.getElementById('existing-evidence-card');
            const existingPreview = document.getElementById('existing-evidence-preview');
            const existingMeta = document.getElementById('existing-evidence-meta');
            const btnFullEvidence = document.getElementById('btn-view-full-evidence');

            if (props.has_evidence && props.evidence_url) {
                existingCard.classList.remove('d-none');
                btnFullEvidence.href = props.evidence_url;
                let metaText = '';
                if (props.delivered_at) metaText += `Entregado el: ${props.delivered_at}`;
                if (props.delivered_by_name) metaText += ` · Registrado por: ${props.delivered_by_name}`;
                existingMeta.innerText = metaText || 'Respaldo de entrega verificado.';

                if (props.is_evidence_pdf) {
                    existingPreview.innerHTML = `
                        <div class="p-3 bg-white rounded border d-inline-flex align-items-center gap-2">
                            <i class="ri-file-pdf-2-fill fs-28 text-danger"></i>
                            <div class="text-start">
                                <span class="fw-bold fs-13 d-block text-dark">Orden de Entrega Firmada (PDF)</span>
                                <a href="${props.evidence_url}" target="_blank" class="fs-11 text-primary">Haga clic para visualizar el documento PDF</a>
                            </div>
                        </div>`;
                } else {
                    existingPreview.innerHTML = `
                        <a href="${props.evidence_url}" target="_blank">
                            <img src="${props.evidence_url}" alt="Orden de Entrega Firmada" class="img-thumbnail rounded shadow-sm" style="max-height: 220px; object-fit: contain;">
                        </a>`;
                }
            } else {
                existingCard.classList.add('d-none');
                existingPreview.innerHTML = '';
            }

            // Poblar formulario de edición
            document.getElementById('form-entity-type').value = props.entity_type;
            document.getElementById('form-entity-id').value = props.entity_id;
            document.getElementById('form-delivery-status').value = props.delivery_status || 'pendiente';
            document.getElementById('form-delivery-date').value = props.delivery_date || '';
            document.getElementById('form-delivery-time').value = props.delivery_time || '11:00';
            document.getElementById('form-delivery-instructions').value = props.instructions || '';

            // Limpiar input file nuevo
            const fileInput = document.getElementById('form-evidence-file');
            fileInput.value = '';
            const newPreview = document.getElementById('new-evidence-preview');
            newPreview.classList.add('d-none');
            newPreview.innerHTML = '';

            toggleEvidenceUploadBlock(props.delivery_status || 'pendiente');
        }

        function openEventFromSidebar(eventId) {
            if (!deliveryCalendar) return;
            const ev = deliveryCalendar.getEventById(eventId);
            if (ev) {
                currentSelectedEvent = ev;
                deliveryCalendar.gotoDate(ev.start);
                populateDeliveryModal(ev);
                deliveryModal.show();
            }
        }

        /**
         * Actualiza en sitio el evento dentro de FullCalendar sin duplicarlo en una fuente paralela.
         */
        function applyUpdatedEventToCalendar(eventData) {
            if (!deliveryCalendar || !eventData || !eventData.id) return;
            const existingEvent = deliveryCalendar.getEventById(eventData.id);
            if (!existingEvent) return;

            const statusFilter = document.getElementById('filter-delivery-status')?.value || 'all';
            const typeFilter = document.getElementById('filter-delivery-type')?.value || 'all';
            const evStatus = eventData.extendedProps?.delivery_status || 'pendiente';
            const evType = eventData.extendedProps?.entity_type || '';

            const matchesStatus = (statusFilter === 'all' || statusFilter === evStatus);
            const matchesType = (typeFilter === 'all' || typeFilter === evType);

            if (!matchesStatus || !matchesType) {
                existingEvent.remove();
                return;
            }

            existingEvent.setProp('title', eventData.title);
            existingEvent.setProp('classNames', (eventData.className || '').split(' ').filter(Boolean));
            existingEvent.setProp('editable', !!eventData.editable);

            if (eventData.start && eventData.end) {
                existingEvent.setDates(eventData.start, eventData.end);
            }

            if (eventData.extendedProps) {
                Object.entries(eventData.extendedProps).forEach(([key, val]) => {
                    existingEvent.setExtendedProp(key, val);
                });
            }

            currentSelectedEvent = existingEvent;
        }

        /**
         * Actualiza dinámicamente las 4 tarjetas superiores de KPIs y la lista lateral de Próximas Entregas.
         */
        function updateDashboardSummary(summary) {
            if (!summary) return;

            if (summary.stats) {
                const elToday = document.getElementById('stat-today');
                const elPending = document.getElementById('stat-pending');
                const elInRoute = document.getElementById('stat-in-route');
                const elDelivered = document.getElementById('stat-delivered');

                if (elToday) elToday.innerText = summary.stats.today ?? 0;
                if (elPending) elPending.innerText = summary.stats.pending ?? 0;
                if (elInRoute) elInRoute.innerText = summary.stats.in_route ?? 0;
                if (elDelivered) elDelivered.innerText = summary.stats.delivered ?? 0;
            }

            if (Array.isArray(summary.upcomingDeliveries)) {
                const listEl = document.getElementById('upcoming-event-list');
                if (!listEl) return;

                if (summary.upcomingDeliveries.length === 0) {
                    listEl.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="ri-calendar-event-line fs-24 d-block mb-1"></i>
                            No hay entregas próximas programadas.
                        </div>`;
                    return;
                }

                listEl.innerHTML = summary.upcomingDeliveries.map(item => {
                    let badgeClass = item.entity_type === 'special_order'
                        ? 'bg-warning-subtle text-warning'
                        : 'bg-primary-subtle text-primary';
                    let statusText = 'Pendiente';

                    if (item.status === 'entregado') {
                        badgeClass = 'bg-success-subtle text-success';
                        statusText = '✓ Entregado';
                    } else if (item.status === 'en_ruta') {
                        badgeClass = 'bg-info-subtle text-info';
                        statusText = 'En Ruta';
                    } else if (item.status === 'cancelado') {
                        badgeClass = 'bg-danger-subtle text-danger';
                        statusText = 'Cancelado';
                    }

                    const evidenceBadge = item.has_evidence ? `
                        <div class="mt-1">
                            <span class="badge bg-success-subtle text-success fs-10">
                                <i class="ri-file-shield-2-line me-1"></i>Respaldo Firmado Adjunto
                            </span>
                        </div>` : '';

                    const safeAddress = (item.address || '').replace(/"/g, '&quot;');

                    return `
                        <div class="card mb-2 shadow-none border upcoming-delivery-card" style="cursor: pointer;" onclick="openEventFromSidebar('${item.event_id}')">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge ${badgeClass} fs-10">${statusText}</span>
                                    <small class="text-muted fw-semibold fs-11">
                                        <i class="ri-time-line me-1 text-primary"></i>${item.date_formatted} · ${item.time_formatted}
                                    </small>
                                </div>
                                <h6 class="mb-1 fs-13 text-dark fw-semibold">${item.folio} — ${item.recipient_name}</h6>
                                <p class="text-muted mb-0 fs-11 text-truncate" title="${safeAddress}">
                                    <i class="ri-map-pin-line me-1"></i>${item.address}
                                </p>
                                ${evidenceBadge}
                            </div>
                        </div>`;
                }).join('');
            }
        }

        function handleEventReschedule(info) {
            const ev = info.event;
            const props = ev.extendedProps;
            const newStart = ev.start;

            const yyyy = newStart.getFullYear();
            const mm = String(newStart.getMonth() + 1).padStart(2, '0');
            const dd = String(newStart.getDate()).padStart(2, '0');
            const hh = String(newStart.getHours()).padStart(2, '0');
            const min = String(newStart.getMinutes()).padStart(2, '0');

            const newDate = `${yyyy}-${mm}-${dd}`;
            const newTime = `${hh}:${min}`;

            Swal.fire({
                title: '¿Reprogramar Entrega?',
                html: `Mover el envío <strong>${props.folio}</strong> para el día <strong>${dd}/${mm}/${yyyy}</strong> a las <strong>${newTime} hrs</strong>.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, reprogramar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    info.revert();
                    return;
                }

                fetch(`${deliveriesBaseUrl}/${props.entity_type}/${props.entity_id}/reschedule`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        delivery_date: newDate,
                        delivery_time: newTime
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (data.event) applyUpdatedEventToCalendar(data.event);
                            if (data.summary) updateDashboardSummary(data.summary);
                            deliveryCalendar.refetchEvents();
                            Swal.fire({
                                icon: 'success',
                                title: 'Reprogramado',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            info.revert();
                            Swal.fire('Error', data.message || 'No se pudo reprogramar.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        info.revert();
                        Swal.fire('Error', 'Ocurrió un error al reprogramar el envío.', 'error');
                    });
            });
        }

        function submitDeliveryUpdate() {
            const type = document.getElementById('form-entity-type').value;
            const id = document.getElementById('form-entity-id').value;
            const status = document.getElementById('form-delivery-status').value;
            const fileInput = document.getElementById('form-evidence-file');
            const hasExisting = currentSelectedEvent?.extendedProps?.has_evidence || false;

            if (status === 'entregado' && !hasExisting && (!fileInput.files || fileInput.files.length === 0)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Orden Firmada Requerida',
                    text: 'Para registrar el envío como ENTREGADO debes adjuntar la fotografía o el PDF de la orden de entrega firmada.'
                });
                return;
            }

            const formData = new FormData();
            formData.append('delivery_status', status);
            formData.append('delivery_date', document.getElementById('form-delivery-date').value);
            formData.append('delivery_time', document.getElementById('form-delivery-time').value);
            formData.append('delivery_instructions', document.getElementById('form-delivery-instructions').value);

            if (fileInput.files && fileInput.files.length > 0) {
                formData.append('evidence_file', fileInput.files[0]);
            }

            const btnSave = document.getElementById('btn-save-delivery-update');
            const prevHtml = btnSave.innerHTML;
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> Guardando...';

            fetch(`${deliveriesBaseUrl}/${type}/${id}/status`, {
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
                    btnSave.disabled = false;
                    btnSave.removeAttribute('data-submitting');
                    btnSave.innerHTML = prevHtml;

                    if (httpStatus >= 200 && httpStatus < 300 && data.success) {
                        deliveryModal.hide();
                        if (data.event) applyUpdatedEventToCalendar(data.event);
                        if (data.summary) updateDashboardSummary(data.summary);
                        deliveryCalendar.refetchEvents();
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
                    btnSave.disabled = false;
                    btnSave.removeAttribute('data-submitting');
                    btnSave.innerHTML = prevHtml;
                    console.error('Error actualizando envío:', err);
                    Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
                });
        }
    </script>
@endpush
