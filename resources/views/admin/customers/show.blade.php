@extends('layouts.master')
@section('title') Expediente de Cliente @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <!-- Header Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <div class="avatar-lg me-2">
                            <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24 fw-bold">
                                {{ strtoupper(substr($customer->full_name, 0, 2)) }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-1 text-dark">{{ $customer->full_name }}</h4>
                            <div class="hstack gap-3 text-muted fs-13 flex-wrap">
                                <div><i class="ri-hashtag me-1 align-middle text-primary"></i>ID: <strong>#{{ $customer->id }}</strong></div>
                                <div><i class="ri-file-paper-line me-1 align-middle text-primary"></i>RFC: <code>{{ $customer->rfc ?: 'Sin RFC' }}</code></div>
                                <div><i class="ri-calendar-event-line me-1 align-middle text-primary"></i>Registrado: {{ $customer->created_at ? $customer->created_at->format('d/m/Y') : '—' }}</div>
                                <div>
                                    <span class="badge {{ $customer->status ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                        {{ $customer->status ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="hstack gap-2">
                            @can('editar-clientes')
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary btn-sm">
                                <i class="ri-edit-line me-1"></i> Editar Cliente
                            </a>
                            @endcan
                            <a href="{{ route('customers.index') }}" class="btn btn-soft-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Volver al Catálogo
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen Financiero de Crédito -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-animate bg-light border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Límite de Crédito</p>
                                    <h4 class="mb-0 text-success">${{ number_format($customer->credit_limit, 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
                                        <i class="ri-bank-card-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-animate bg-light border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Crédito Disponible</p>
                                    <h4 class="mb-0 text-info">${{ number_format($customer->available_credit, 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info-subtle text-info rounded-2 fs-2">
                                        <i class="ri-wallet-3-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-animate bg-light border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                @php
                                    $usedCredit = max(0, $customer->credit_limit - $customer->available_credit);
                                @endphp
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Crédito Utilizado</p>
                                    <h4 class="mb-0 text-warning">${{ number_format($usedCredit, 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-2 fs-2">
                                        <i class="ri-exchange-dollar-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal: Ficha e Historiales -->
            <div class="row">
                <!-- Columna Izquierda: Información General & Domicilio Habitual -->
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="ri-information-line me-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="text-muted fs-12 d-block mb-1">Nombre Completo</label>
                                <span class="fw-semibold text-dark fs-14">{{ $customer->full_name }}</span>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted fs-12 d-block mb-1">Correo Electrónico</label>
                                @if($customer->email)
                                    <a href="mailto:{{ $customer->email }}" class="text-primary"><i class="ri-mail-line me-1"></i>{{ $customer->email }}</a>
                                @else
                                    <span class="text-muted">No registrado</span>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="text-muted fs-12 d-block mb-1">Teléfono</label>
                                @if($customer->phone)
                                    <a href="tel:{{ $customer->phone }}" class="text-dark"><i class="ri-phone-line me-1"></i>{{ $customer->phone }}</a>
                                @else
                                    <span class="text-muted">No registrado</span>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="text-muted fs-12 d-block mb-1">Razón Social</label>
                                <span class="fw-medium text-dark">{{ $customer->business_name ?: 'No especificada' }}</span>
                            </div>
                            <div class="mb-0">
                                <label class="text-muted fs-12 d-block mb-1">Dirección Fiscal / Domicilio</label>
                                <span class="text-dark fs-13">{{ $customer->legal_address ?: 'No especificada' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta de Domicilio Habitual de Entrega y GPS -->
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <i class="ri-truck-line me-2 text-primary"></i>Domicilio de Entrega
                            </h5>
                            @if($customer->hasCoordinates())
                                <span class="badge bg-success-subtle text-success">
                                    <i class="ri-map-pin-2-fill me-1"></i>GPS Activo
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-muted">Sin GPS</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="text-muted fs-12 d-block mb-1">Dirección de Entrega</label>
                                <span class="text-dark fs-13 fw-medium">
                                    {{ $customer->formatted_delivery_address ?: ($customer->legal_address ?: 'No configurada') }}
                                </span>
                            </div>

                            @if($customer->delivery_references)
                                <div class="mb-3">
                                    <label class="text-muted fs-12 d-block mb-1">Referencias de Entrega</label>
                                    <span class="text-muted fs-13 fst-italic">{{ $customer->delivery_references }}</span>
                                </div>
                            @endif

                            @if($customer->delivery_instructions)
                                <div class="mb-3">
                                    <label class="text-muted fs-12 d-block mb-1">Instrucciones Especiales</label>
                                    <span class="text-muted fs-13">{{ $customer->delivery_instructions }}</span>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="text-muted fs-12 d-block mb-1">Coordenadas GPS</label>
                                @if($customer->hasCoordinates())
                                    <code class="fs-12 text-primary">{{ $customer->delivery_lat }}, {{ $customer->delivery_lng }}</code>
                                @else
                                    <span class="text-muted fs-12">No registradas</span>
                                @endif
                            </div>

                            @if($customer->hasCoordinates())
                                <div class="mb-3">
                                    <div id="customer-show-map" style="height: 180px; width: 100%; border-radius: 6px;" class="border bg-light">
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted small">
                                            <span><i class="ri-loader-4-line ri-spin me-1"></i> Cargando mapa...</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($customer->delivery_maps_url || $customer->hasCoordinates())
                                @php
                                    $mapLink = $customer->delivery_maps_url ?: ('https://maps.google.com/?q=' . $customer->delivery_lat . ',' . $customer->delivery_lng);
                                @endphp
                                <a href="{{ $mapLink }}" target="_blank" class="btn btn-sm btn-soft-primary w-100">
                                    <i class="ri-map-pin-line me-1"></i> Abrir Ubicación en Google Maps
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Historial de Ventas y Pedidos Especiales -->
                <div class="col-lg-8">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-success mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#sales-tab" role="tab">
                                <i class="ri-shopping-bag-line me-1"></i> Ventas Recientes ({{ $customer->sales->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#orders-tab" role="tab">
                                <i class="ri-bookmark-3-line me-1"></i> Pedidos Especiales ({{ $customer->specialOrders->count() }})
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Tab Ventas -->
                        <div class="tab-pane active" id="sales-tab" role="tabpanel">
                            <div class="card">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-hover table-nowrap mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Folio Ticket</th>
                                                    <th>Fecha</th>
                                                    <th>Total</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($customer->sales as $sale)
                                                    <tr>
                                                        <td><code>{{ $sale->ticket_folio }}</code></td>
                                                        <td>{{ $sale->created_at ? $sale->created_at->format('d/m/Y H:i') : '—' }}</td>
                                                        <td class="fw-semibold">${{ number_format($sale->total, 2) }}</td>
                                                        <td>
                                                             <span class="badge bg-success-subtle text-success">
                                                                {{ ucfirst($sale->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('pos.sales.print', $sale->id) }}" class="btn btn-sm btn-soft-info" target="_blank">
                                                                <i class="ri-printer-line me-1"></i> Ticket
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center py-4 text-muted">
                                                             <i class="ri-shopping-cart-line fs-20 d-block mb-1"></i>
                                                            El cliente no registra ventas previas en el sistema.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Pedidos Especiales -->
                        <div class="tab-pane" id="orders-tab" role="tabpanel">
                            <div class="card">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-hover table-nowrap mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Folio</th>
                                                    <th>Fecha</th>
                                                    <th>Total</th>
                                                    <th>Saldo</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($customer->specialOrders as $so)
                                                    <tr>
                                                        <td><code>{{ $so->folio }}</code></td>
                                                        <td>{{ $so->created_at ? $so->created_at->format('d/m/Y') : '—' }}</td>
                                                        <td>${{ number_format($so->total, 2) }}</td>
                                                        <td class="fw-semibold text-danger">${{ number_format($so->balanceDue(), 2) }}</td>
                                                        <td>
                                                            <span class="badge bg-warning-subtle text-warning">
                                                                {{ $so->statusLabel() }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('special-orders.show', $so) }}" class="btn btn-sm btn-soft-primary">
                                                                <i class="ri-eye-line me-1"></i> Ver Pedido
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4 text-muted">
                                                            <i class="ri-bookmark-line fs-20 d-block mb-1"></i>
                                                            El cliente no tiene pedidos especiales registrados.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@if($customer->hasCoordinates())
@push('scripts')
<script>
// Google Maps JavaScript API Loader
(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    key: "{{ config('services.google_maps.key', '') }}",
    v: "weekly"
});

document.addEventListener('DOMContentLoaded', async function () {
    const mapContainer = document.getElementById('customer-show-map');
    if (!mapContainer) return;
    try {
        if (typeof google === 'undefined' || !google.maps) return;
        const pos = { lat: {{ (float)$customer->delivery_lat }}, lng: {{ (float)$customer->delivery_lng }} };
        const { Map } = await google.maps.importLibrary("maps");
        const map = new Map(mapContainer, {
            center: pos,
            zoom: 16,
            mapId: 'customer_show_delivery_map',
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });

        try {
            const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
            new AdvancedMarkerElement({ position: pos, map: map, title: "{{ addslashes($customer->full_name) }}" });
        } catch(e) {
            new google.maps.Marker({ position: pos, map: map, title: "{{ addslashes($customer->full_name) }}" });
        }
    } catch (e) {
        console.error('Error cargando mapa en show:', e);
    }
});
</script>
@endpush
@endif
