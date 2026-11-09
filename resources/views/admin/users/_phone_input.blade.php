@php
    // Lista de países con código, bandera emoji y nombre
    $countries = [
        ['code' => '52',  'flag' => '🇲🇽', 'name' => 'México'],
        ['code' => '1',   'flag' => '🇺🇸', 'name' => 'EE.UU.'],
        ['code' => '57',  'flag' => '🇨🇴', 'name' => 'Colombia'],
        ['code' => '54',  'flag' => '🇦🇷', 'name' => 'Argentina'],
        ['code' => '56',  'flag' => '🇨🇱', 'name' => 'Chile'],
        ['code' => '51',  'flag' => '🇵🇪', 'name' => 'Perú'],
        ['code' => '593', 'flag' => '🇪🇨', 'name' => 'Ecuador'],
        ['code' => '58',  'flag' => '🇻🇪', 'name' => 'Venezuela'],
        ['code' => '502', 'flag' => '🇬🇹', 'name' => 'Guatemala'],
        ['code' => '503', 'flag' => '🇸🇻', 'name' => 'El Salvador'],
        ['code' => '504', 'flag' => '🇭🇳', 'name' => 'Honduras'],
        ['code' => '507', 'flag' => '🇵🇦', 'name' => 'Panamá'],
        ['code' => '506', 'flag' => '🇨🇷', 'name' => 'Costa Rica'],
        ['code' => '34',  'flag' => '🇪🇸', 'name' => 'España'],
        ['code' => '55',  'flag' => '🇧🇷', 'name' => 'Brasil'],
    ];

    // Detectar código de país del número existente
    $currentCode = old('phone_country', '52');
    $currentPhone = old('phone_local', '');

    if (isset($phone) && $phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Intentar detectar el código del país
        $matched = false;
        // Ordenar por longitud de código descendente para matchear los más largos primero
        $sortedCountries = collect($countries)->sortByDesc(fn($c) => strlen($c['code']))->values();
        foreach ($sortedCountries as $c) {
            if (str_starts_with($phone, $c['code'])) {
                $currentCode = $c['code'];
                $currentPhone = substr($phone, strlen($c['code']));
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $currentPhone = $phone;
        }
    }
@endphp

<label for="phone_local" class="form-label">@lang('translation.phone'):</label>
<div class="input-group input-group-sm">
    <select name="phone_country" id="phone_country" class="form-select form-select-sm" style="max-width: 140px;">
        @foreach($countries as $country)
            <option value="{{ $country['code'] }}" {{ $currentCode == $country['code'] ? 'selected' : '' }}>
                {{ $country['flag'] }} +{{ $country['code'] }}
            </option>
        @endforeach
    </select>
    <input type="tel" name="phone_local" id="phone_local" class="form-control form-control-sm"
           placeholder="@lang('translation.phone_placeholder')"
           value="{{ $currentPhone }}">
</div>
<small class="text-muted">@lang('translation.phone_help')</small>
