<div class="row">
    <div class="col-lg-6">
        <div class="mb-3">
            <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror form-control-sm" id="name" name="name"
                   value="{{ old('name', $store->name ?? '') }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-lg-3">
        <div class="mb-3">
            <label for="code" class="form-label">Código <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('code') is-invalid @enderror form-control-sm" id="code" name="code"
                   value="{{ old('code', $store->code ?? '') }}" maxlength="10" required
                   style="text-transform: uppercase;">
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-lg-3">
        <div class="mb-3">
            <label for="phone" class="form-label">Teléfono</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror form-control-sm" id="phone" name="phone"
                   value="{{ old('phone', $store->phone ?? '') }}">
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="mb-3">
            <label for="address" class="form-label">Dirección</label>
            <textarea class="form-control @error('address') is-invalid @enderror form-control-sm" id="address" name="address"
                      rows="2">{{ old('address', $store->address ?? '') }}</textarea>
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="mb-3">
            <label for="city" class="form-label">Ciudad</label>
            <input type="text" class="form-control @error('city') is-invalid @enderror form-control-sm" id="city" name="city"
                   value="{{ old('city', $store->city ?? '') }}">
            @error('city')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label for="state" class="form-label">Estado</label>
            <input type="text" class="form-control @error('state') is-invalid @enderror form-control-sm" id="state" name="state"
                   value="{{ old('state', $store->state ?? '') }}">
            @error('state')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <label for="zip" class="form-label">Código Postal</label>
            <input type="text" class="form-control @error('zip') is-invalid @enderror form-control-sm" id="zip" name="zip"
                   value="{{ old('zip', $store->zip ?? '') }}" maxlength="10">
            @error('zip')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- Mapa Google Maps con marcador arrastrable -->
<div class="row">
    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label">Ubicación en el Mapa</label>
            <p class="text-muted fs-12 mb-2">
                <i class="ri-information-line"></i>
                Arrastra el marcador para seleccionar la ubicación. También puedes ingresar las coordenadas manualmente.
            </p>
            <div id="store-map" style="height: 350px; border-radius: 8px; border: 1px solid #e9ebec;"></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="mb-3">
            <label for="lat" class="form-label">Latitud</label>
            <input type="number" step="0.0000001" class="form-control @error('lat') is-invalid @enderror form-control-sm" id="lat" name="lat"
                   value="{{ old('lat', $store->lat ?? '') }}" placeholder="Ej: 19.2433880">
            @error('lat')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-lg-6">
        <div class="mb-3">
            <label for="lng" class="form-label">Longitud</label>
            <input type="number" step="0.0000001" class="form-control @error('lng') is-invalid @enderror form-control-sm" id="lng" name="lng"
                   value="{{ old('lng', $store->lng ?? '') }}" placeholder="Ej: -103.7250940">
            @error('lng')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- Horarios de operación -->
@php
    $days = [0 => 'Lunes', 1 => 'Martes', 2 => 'Miércoles', 3 => 'Jueves', 4 => 'Viernes', 5 => 'Sábado', 6 => 'Domingo'];
    $existingSchedules = isset($store) && $store->relationLoaded('schedules')
        ? $store->schedules->keyBy('day_of_week')
        : collect();
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="mb-3">
            <label class="form-label">Horarios de Operación</label>
            <p class="text-muted fs-12 mb-2">
                <i class="ri-information-line"></i>
                Configura los horarios de apertura y cierre para cada día. Activa "Receso" para horario partido.
            </p>
            <div class="table-responsive">
                <table class="table table-bordered table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 140px;">Día</th>
                            <th style="width: 80px;" class="text-center">Abierto</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th style="width: 80px;" class="text-center">Receso</th>
                            <th>Inicio Receso</th>
                            <th>Fin Receso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $dayNum => $dayName)
                            @php
                                $schedule = $existingSchedules->get($dayNum);
                                $isOpen = old("schedules.{$dayNum}.is_open", $schedule ? $schedule->is_open : ($dayNum < 6));
                                $openTime = old("schedules.{$dayNum}.open_time", $schedule ? \Carbon\Carbon::parse($schedule->open_time)->format('H:i') : '09:00');
                                $closeTime = old("schedules.{$dayNum}.close_time", $schedule ? \Carbon\Carbon::parse($schedule->close_time)->format('H:i') : '18:00');
                                $hasBreak = old("schedules.{$dayNum}.has_break", $schedule && $schedule->break_start ? true : false);
                                $breakStart = old("schedules.{$dayNum}.break_start", $schedule && $schedule->break_start ? \Carbon\Carbon::parse($schedule->break_start)->format('H:i') : '14:00');
                                $breakEnd = old("schedules.{$dayNum}.break_end", $schedule && $schedule->break_end ? \Carbon\Carbon::parse($schedule->break_end)->format('H:i') : '15:00');
                            @endphp
                            <tr class="{{ !$isOpen ? 'table-light' : '' }}">
                                <td class="fw-medium">{{ $dayName }}</td>
                                <td class="text-center">
                                    <div class="form-check form-switch form-switch-md d-flex justify-content-center mb-0" dir="ltr">
                                        <input type="checkbox" class="form-check-input schedule-toggle"
                                               name="schedules[{{ $dayNum }}][is_open]" value="1"
                                               data-day="{{ $dayNum }}"
                                               {{ $isOpen ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm schedule-field-{{ $dayNum }}"
                                           name="schedules[{{ $dayNum }}][open_time]"
                                           value="{{ $openTime }}"
                                           {{ !$isOpen ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm schedule-field-{{ $dayNum }}"
                                           name="schedules[{{ $dayNum }}][close_time]"
                                           value="{{ $closeTime }}"
                                           {{ !$isOpen ? 'disabled' : '' }}>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center mb-0" dir="ltr">
                                        <input type="checkbox" class="form-check-input break-toggle schedule-field-{{ $dayNum }}"
                                               name="schedules[{{ $dayNum }}][has_break]" value="1"
                                               data-day="{{ $dayNum }}"
                                               {{ $hasBreak ? 'checked' : '' }}
                                               {{ !$isOpen ? 'disabled' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm break-field-{{ $dayNum }}"
                                           name="schedules[{{ $dayNum }}][break_start]"
                                           value="{{ $breakStart }}"
                                           {{ (!$isOpen || !$hasBreak) ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm break-field-{{ $dayNum }}"
                                           name="schedules[{{ $dayNum }}][break_end]"
                                           value="{{ $breakEnd }}"
                                           {{ (!$isOpen || !$hasBreak) ? 'disabled' : '' }}>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
        key: "{{ config('services.google_maps.key', '') }}",
        v: "weekly"
    });

    let map, marker;

    async function initStoreMap() {
        const { Map } = await google.maps.importLibrary("maps");
        const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

        const defaultLat = parseFloat(document.getElementById('lat').value) || 19.2433880;
        const defaultLng = parseFloat(document.getElementById('lng').value) || -103.7250940;
        const center = { lat: defaultLat, lng: defaultLng };

        map = new Map(document.getElementById('store-map'), {
            center: center,
            zoom: 15,
            mapId: 'store_location_map',
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
        });

        marker = new AdvancedMarkerElement({
            position: center,
            map: map,
            gmpDraggable: true,
            title: 'Ubicación de la sucursal'
        });

        // Al arrastrar el marcador → actualizar inputs
        marker.addListener('dragend', function() {
            const pos = marker.position;
            document.getElementById('lat').value = pos.lat.toFixed(7);
            document.getElementById('lng').value = pos.lng.toFixed(7);
        });

        // Click en el mapa → mover marcador
        map.addListener('click', function(event) {
            marker.position = event.latLng;
            document.getElementById('lat').value = event.latLng.lat().toFixed(7);
            document.getElementById('lng').value = event.latLng.lng().toFixed(7);
        });

        // Cambio manual en inputs → mover marcador
        document.getElementById('lat').addEventListener('change', updateMarkerFromInputs);
        document.getElementById('lng').addEventListener('change', updateMarkerFromInputs);
    }

    function updateMarkerFromInputs() {
        const lat = parseFloat(document.getElementById('lat').value);
        const lng = parseFloat(document.getElementById('lng').value);
        if (!isNaN(lat) && !isNaN(lng)) {
            const pos = { lat: lat, lng: lng };
            marker.position = pos;
            map.panTo(pos);
        }
    }

    // Inicializar cuando el DOM esté listo
    initStoreMap();

    // Schedule toggles — habilitar/deshabilitar inputs de horario
    document.querySelectorAll('.schedule-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const day = this.dataset.day;
            const fields = document.querySelectorAll('.schedule-field-' + day);
            const breakFields = document.querySelectorAll('.break-field-' + day);
            const row = this.closest('tr');

            fields.forEach(function(input) {
                input.disabled = !toggle.checked;
            });

            // Si se cierra el día, deshabilitar también los campos de receso
            if (!toggle.checked) {
                breakFields.forEach(function(input) {
                    input.disabled = true;
                });
                row.classList.add('table-light');
            } else {
                // Si se abre, solo habilitar break fields si el toggle de receso está activo
                const breakToggle = row.querySelector('.break-toggle');
                breakFields.forEach(function(input) {
                    input.disabled = !breakToggle.checked;
                });
                row.classList.remove('table-light');
            }
        });
    });

    // Break toggles — habilitar/deshabilitar inputs de receso
    document.querySelectorAll('.break-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const day = this.dataset.day;
            const breakFields = document.querySelectorAll('.break-field-' + day);
            breakFields.forEach(function(input) {
                input.disabled = !toggle.checked;
            });
        });
    });
</script>
@endsection
