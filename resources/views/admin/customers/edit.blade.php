@extends('layouts.master')
@section('title') Editar Cliente @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('customers.update', $customer) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">
                            <i class="ri-edit-line me-2"></i>Editar Cliente #{{ $customer->id }} — {{ $customer->full_name }}
                        </h4>
                        <a href="{{ route('customers.index') }}" class="btn btn-soft-secondary btn-sm">
                            <i class="ri-arrow-left-line me-1"></i> Regresar
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Sección 1: Información Personal y de Contacto -->
                        <h5 class="fs-14 text-primary mb-3">1. Datos Personales y Contacto</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $customer->user?->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="lastname" class="form-label">Apellido(s) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('lastname') is-invalid @enderror" 
                                       id="lastname" name="lastname" value="{{ old('lastname', $customer->user?->lastname) }}" required>
                                @error('lastname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $customer->user?->email) }}">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Teléfono de Contacto</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $customer->user?->phone) }}">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Sección 2: Información Fiscal -->
                        <h5 class="fs-14 text-primary mb-3">2. Datos Fiscales (Facturación)</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="rfc" class="form-label">RFC</label>
                                <input type="text" class="form-control text-uppercase @error('rfc') is-invalid @enderror" 
                                       id="rfc" name="rfc" value="{{ old('rfc', $customer->rfc) }}" maxlength="13">
                                @error('rfc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-8">
                                <label for="business_name" class="form-label">Razón Social o Nombre Fiscal</label>
                                <input type="text" class="form-control @error('business_name') is-invalid @enderror" 
                                       id="business_name" name="business_name" value="{{ old('business_name', $customer->business_name) }}">
                                @error('business_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="legal_address" class="form-label">Dirección Fiscal / Domicilio</label>
                                <textarea class="form-control @error('legal_address') is-invalid @enderror" 
                                          id="legal_address" name="legal_address" rows="2">{{ old('legal_address', $customer->legal_address) }}</textarea>
                                @error('legal_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Sección 3: Crédito y Configuración -->
                        <h5 class="fs-14 text-primary mb-3">3. Configuración de Crédito y Estado</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="credit_limit" class="form-label">Límite de Crédito ($ MXN)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control @error('credit_limit') is-invalid @enderror" 
                                           id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}">
                                </div>
                                @error('credit_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="available_credit" class="form-label">Crédito Disponible ($ MXN)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control @error('available_credit') is-invalid @enderror" 
                                           id="available_credit" name="available_credit" value="{{ old('available_credit', $customer->available_credit) }}">
                                </div>
                                @error('available_credit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label">Estado del Cliente <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="1" {{ old('status', $customer->status) ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ !old('status', $customer->status) ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Sección 4: Domicilio de Entrega Habitual y Geolocalización (Para Envíos a Domicilio) -->
                        <div class="d-flex align-items-center justify-content-between mb-3 border-top pt-4">
                            <h5 class="fs-14 text-primary mb-0">
                                <i class="ri-truck-line me-1"></i> 4. Domicilio Habitual para Envíos y Geolocalización (Opcional)
                            </h5>
                            <button type="button" class="btn btn-sm btn-soft-secondary" id="btn-copy-legal">
                                <i class="ri-file-copy-line me-1"></i> Copiar de Dirección Fiscal
                            </button>
                        </div>
                        <p class="text-muted fs-12 mb-3">
                            Esta información se autorrellenará automáticamente al generar ventas o pedidos con entrega a domicilio para este cliente.
                        </p>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="street" class="form-label">Calle y Número</label>
                                <input type="text" class="form-control @error('street') is-invalid @enderror" 
                                       id="street" name="street" value="{{ old('street', $customer->street) }}" 
                                       placeholder="Ej. Av. Francisco I. Madero Poniente #1234">
                                @error('street') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="colonia" class="form-label">Colonia / Asentamiento</label>
                                <input type="text" class="form-control @error('colonia') is-invalid @enderror" 
                                       id="colonia" name="colonia" value="{{ old('colonia', $customer->colonia) }}" 
                                       placeholder="Ej. Centro Histórico">
                                @error('colonia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="city" class="form-label">Ciudad / Municipio</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                       id="city" name="city" value="{{ old('city', $customer->city ?? 'Morelia') }}" 
                                       placeholder="Ej. Morelia">
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="state" class="form-label">Estado</label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                       id="state" name="state" value="{{ old('state', $customer->state ?? 'Michoacán') }}" 
                                       placeholder="Ej. Michoacán">
                                @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="zip" class="form-label">Código Postal</label>
                                <input type="text" class="form-control @error('zip') is-invalid @enderror" 
                                       id="zip" name="zip" value="{{ old('zip', $customer->zip) }}" 
                                       placeholder="Ej. 58000" maxlength="10">
                                @error('zip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="delivery_references" class="form-label">Referencias del Domicilio</label>
                                <textarea class="form-control @error('delivery_references') is-invalid @enderror" 
                                          id="delivery_references" name="delivery_references" rows="2" 
                                          placeholder="Ej. Casa de dos pisos blanca, portón café, entre calle Juárez y Zaragoza">{{ old('delivery_references', $customer->delivery_references) }}</textarea>
                                @error('delivery_references') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="delivery_instructions" class="form-label">Instrucciones de Entrega</label>
                                <textarea class="form-control @error('delivery_instructions') is-invalid @enderror" 
                                          id="delivery_instructions" name="delivery_instructions" rows="2" 
                                          placeholder="Ej. Tocar timbre 2 veces, llamar al llegar, entregar a vigilancia">{{ old('delivery_instructions', $customer->delivery_instructions) }}</textarea>
                                @error('delivery_instructions') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <!-- Mapa Interactivo & Geolocalización con Google Maps -->
                            <div class="col-12 mt-2">
                                <div class="card border border-light-subtle bg-light shadow-none mb-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                            <h6 class="text-uppercase text-muted fw-bold fs-12 mb-0">
                                                <i class="ri-map-2-line me-1 text-primary"></i> Geolocalización en Google Maps
                                            </h6>
                                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-locate-address">
                                                    <i class="ri-crosshair-2-line me-1"></i> Buscar Dirección en Mapa
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-current-location" title="Obtener mi ubicación GPS actual">
                                                    <i class="ri-navigation-line me-1"></i> Mi Ubicación
                                                </button>
                                                <span class="badge bg-light text-secondary border" id="delivery-coords-badge">Pin: Sin fijar</span>
                                            </div>
                                        </div>
                                        <p class="text-muted fs-11 mb-2">
                                            Haz clic o arrastra el marcador en el mapa para fijar el punto exacto de entrega, o presiona <strong>"Buscar Dirección en Mapa"</strong> para geolocalizar automáticamente la dirección capturada.
                                        </p>

                                        <div id="customer-delivery-map" style="height: 280px; width: 100%; border-radius: 8px;" class="border bg-light mb-3">
                                            <div class="d-flex align-items-center justify-content-center h-100 text-muted small">
                                                <span><i class="ri-loader-4-line ri-spin me-1"></i> Cargando mapa interactivo...</span>
                                            </div>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <label for="delivery_lat" class="form-label fs-11 fw-semibold mb-1">Latitud GPS</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Lat</span>
                                                    <input type="number" step="any" class="form-control @error('delivery_lat') is-invalid @enderror" 
                                                           id="delivery_lat" name="delivery_lat" value="{{ old('delivery_lat', $customer->delivery_lat) }}" placeholder="19.702560">
                                                </div>
                                                @error('delivery_lat') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label for="delivery_lng" class="form-label fs-11 fw-semibold mb-1">Longitud GPS</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Lng</span>
                                                    <input type="number" step="any" class="form-control @error('delivery_lng') is-invalid @enderror" 
                                                           id="delivery_lng" name="delivery_lng" value="{{ old('delivery_lng', $customer->delivery_lng) }}" placeholder="-101.192380">
                                                </div>
                                                @error('delivery_lng') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="delivery_maps_url" class="form-label fs-11 fw-semibold mb-1">Enlace a Google Maps</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text"><i class="ri-link"></i></span>
                                                    <input type="url" class="form-control @error('delivery_maps_url') is-invalid @enderror" 
                                                           id="delivery_maps_url" name="delivery_maps_url" 
                                                           value="{{ old('delivery_maps_url', $customer->delivery_maps_url) }}" 
                                                           placeholder="https://maps.google.com/?q=...">
                                                    <button type="button" class="btn btn-outline-secondary" id="btn-open-maps" title="Ver en Google Maps">
                                                        <i class="ri-external-link-line me-1"></i> Ver
                                                    </button>
                                                </div>
                                                @error('delivery_maps_url') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('customers.index') }}" class="btn btn-light me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Actualizar Cliente
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Google Maps JavaScript API Loader
(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    key: "{{ config('services.google_maps.key', '') }}",
    v: "weekly"
});

document.addEventListener('DOMContentLoaded', function () {
    // Copiar dirección fiscal a entrega
    const btnCopyLegal = document.getElementById('btn-copy-legal');
    if (btnCopyLegal) {
        btnCopyLegal.addEventListener('click', function () {
            const legal = document.getElementById('legal_address')?.value.trim();
            if (legal) {
                const streetInput = document.getElementById('street');
                if (streetInput && !streetInput.value) {
                    streetInput.value = legal;
                }
            }
        });
    }

    // Inicializar Mapa Interactivo de Entrega
    initCustomerDeliveryMap();
});

async function initCustomerDeliveryMap() {
    const mapContainer = document.getElementById('customer-delivery-map');
    if (!mapContainer) return;

    const latInput = document.getElementById('delivery_lat');
    const lngInput = document.getElementById('delivery_lng');
    const mapsUrlInput = document.getElementById('delivery_maps_url');
    const badge = document.getElementById('delivery-coords-badge');
    const btnLocate = document.getElementById('btn-locate-address');
    const btnCurrentLocation = document.getElementById('btn-current-location');
    const btnOpenMaps = document.getElementById('btn-open-maps');

    let curLat = parseFloat(latInput?.value) || 19.702560;
    let curLng = parseFloat(lngInput?.value) || -101.192380;
    const hasInitialCoords = !isNaN(parseFloat(latInput?.value)) && !isNaN(parseFloat(lngInput?.value));
    const center = { lat: curLat, lng: curLng };

    function updateDeliveryCoords(lat, lng, updateInputs = true) {
        lat = typeof lat === 'number' ? lat : parseFloat(lat);
        lng = typeof lng === 'number' ? lng : parseFloat(lng);
        if (isNaN(lat) || isNaN(lng)) return;

        if (updateInputs) {
            if (latInput) latInput.value = lat.toFixed(7);
            if (lngInput) lngInput.value = lng.toFixed(7);
        }

        if (mapsUrlInput) {
            mapsUrlInput.value = `https://maps.google.com/?q=${lat.toFixed(7)},${lng.toFixed(7)}`;
        }

        if (badge) {
            badge.innerText = `Pin: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            badge.className = 'badge bg-success-subtle text-success border border-success';
        }
    }

    if (hasInitialCoords) {
        updateDeliveryCoords(curLat, curLng, false);
    }

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

        const map = new Map(mapContainer, {
            center: center,
            zoom: hasInitialCoords ? 16 : 14,
            mapId: 'customer_edit_delivery_map',
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        let marker = null;

        function setOrMoveMarker(pos) {
            if (marker) {
                if (AdvancedMarkerElement && marker instanceof AdvancedMarkerElement) {
                    marker.position = pos;
                } else if (typeof marker.setPosition === 'function') {
                    marker.setPosition(pos);
                }
            } else {
                if (AdvancedMarkerElement) {
                    marker = new AdvancedMarkerElement({
                        position: pos,
                        map: map,
                        gmpDraggable: true,
                        title: 'Domicilio de Entrega'
                    });
                    marker.addListener('dragend', () => {
                        const p = marker.position;
                        updateDeliveryCoords(p.lat, p.lng);
                    });
                } else {
                    marker = new google.maps.Marker({
                        position: pos,
                        map: map,
                        draggable: true,
                        title: 'Domicilio de Entrega'
                    });
                    marker.addListener('dragend', (e) => {
                        updateDeliveryCoords(e.latLng.lat(), e.latLng.lng());
                    });
                }
            }
            updateDeliveryCoords(pos.lat, pos.lng);
        }

        if (hasInitialCoords) {
            setOrMoveMarker(center);
        }

        // Clic en el mapa para colocar o mover el pin
        map.addListener('click', (e) => {
            const lat = e.latLng.lat();
            const lng = e.latLng.lng();
            setOrMoveMarker({ lat, lng });
        });

        // Escuchar cambios manuales en los inputs de lat/lng
        const onManualCoordChange = () => {
            const mLat = parseFloat(latInput?.value);
            const mLng = parseFloat(lngInput?.value);
            if (!isNaN(mLat) && !isNaN(mLng)) {
                const newPos = { lat: mLat, lng: mLng };
                map.panTo(newPos);
                setOrMoveMarker(newPos);
            }
        };
        if (latInput) latInput.addEventListener('change', onManualCoordChange);
        if (lngInput) lngInput.addEventListener('change', onManualCoordChange);

        // Botón: Buscar Dirección en Mapa (Geocodificación)
        if (btnLocate) {
            btnLocate.addEventListener('click', () => {
                const street = document.getElementById('street')?.value.trim();
                const colonia = document.getElementById('colonia')?.value.trim();
                const city = document.getElementById('city')?.value.trim();
                const state = document.getElementById('state')?.value.trim();
                const fullQuery = [street, colonia, city, state, 'México'].filter(Boolean).join(', ');

                if (!fullQuery || fullQuery === 'México') {
                    alert('Ingresa al menos la calle, colonia o ciudad para ubicar en el mapa.');
                    return;
                }

                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: fullQuery }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        const loc = results[0].geometry.location;
                        const lat = loc.lat();
                        const lng = loc.lng();
                        map.setCenter(loc);
                        map.setZoom(16);
                        setOrMoveMarker({ lat, lng });
                    } else {
                        alert('No se encontró la ubicación exacta en el mapa. Puedes arrastrar o hacer clic para fijar el pin manualmente.');
                    }
                });
            });
        }

        // Botón: Mi Ubicación Actual (HTML5 Geolocation)
        if (btnCurrentLocation) {
            btnCurrentLocation.addEventListener('click', () => {
                if (navigator.geolocation) {
                    btnCurrentLocation.disabled = true;
                    btnCurrentLocation.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> Obteniendo...';
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            btnCurrentLocation.disabled = false;
                            btnCurrentLocation.innerHTML = '<i class="ri-navigation-line me-1"></i> Mi Ubicación';
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            const pos = { lat, lng };
                            map.setCenter(pos);
                            map.setZoom(16);
                            setOrMoveMarker(pos);
                        },
                        (error) => {
                            btnCurrentLocation.disabled = false;
                            btnCurrentLocation.innerHTML = '<i class="ri-navigation-line me-1"></i> Mi Ubicación';
                            alert('No se pudo obtener la ubicación actual: ' + error.message);
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                } else {
                    alert('La geolocalización no está soportada por este navegador.');
                }
            });
        }

        // Sincronizar enlace de Google Maps al pegar URL
        if (mapsUrlInput) {
            mapsUrlInput.addEventListener('change', function () {
                const val = mapsUrlInput.value.trim();
                const match = val.match(/([+-]?\d+\.?\d*),\s*([+-]?\d+\.?\d*)/);
                if (match && match[1] && match[2]) {
                    const lat = parseFloat(match[1]);
                    const lng = parseFloat(match[2]);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        const newPos = { lat, lng };
                        map.panTo(newPos);
                        setOrMoveMarker(newPos);
                    }
                }
            });
        }

        // Botón: Ver en Google Maps
        if (btnOpenMaps) {
            btnOpenMaps.addEventListener('click', function () {
                const url = mapsUrlInput?.value.trim();
                const lat = latInput?.value.trim();
                const lng = lngInput?.value.trim();
                if (url) {
                    window.open(url, '_blank');
                } else if (lat && lng) {
                    window.open(`https://maps.google.com/?q=${lat},${lng}`, '_blank');
                } else {
                    alert('Selecciona un punto en el mapa o ingresa coordenadas primero.');
                }
            });
        }

    } catch (err) {
        console.error("Error inicializando mapa de Google Maps:", err);
        mapContainer.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted small p-3 text-center"><i class="ri-alert-line fs-4 me-2 text-warning"></i><span>No se pudo cargar el mapa. Puedes ingresar coordenadas manualmente.</span></div>';
    }
}
</script>
@endpush
