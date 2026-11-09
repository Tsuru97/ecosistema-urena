<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Entrega - {{ $sale->ticket_folio }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            font-size: 12px;
            line-height: 1.4;
            padding: 24px 0;
        }

        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: #2563eb; color: #ffffff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }

        .sheet {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 36px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            position: relative;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 16px;
        }
        .header-table td { vertical-align: top; }

        .logo-img {
            max-height: 60px;
            max-width: 180px;
            object-fit: contain;
        }

        .title-block {
            text-align: right;
        }
        .title-block h1 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .folio-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .card-grid {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }
        .card-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }
        .card-col:last-child {
            padding-right: 0;
            padding-left: 12px;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            height: 100%;
        }

        .info-row {
            margin-bottom: 6px;
            font-size: 12px;
        }
        .info-row:last-child { margin-bottom: 0; }
        .info-label {
            font-weight: 600;
            color: #475569;
            display: inline-block;
            min-width: 90px;
        }
        .info-value {
            color: #0f172a;
            font-weight: 500;
        }

        .alert-references {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 11px;
            color: #92400e;
            margin-top: 10px;
        }

        .qr-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }
        .qr-img {
            width: 90px;
            height: 90px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #fff;
            padding: 2px;
            flex-shrink: 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 11.5px;
        }
        .items-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 600;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .signature-col {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .signature-line {
            border-top: 1px solid #64748b;
            margin-top: 45px;
            margin-bottom: 6px;
        }
        .signature-title {
            font-weight: 700;
            font-size: 11px;
            color: #0f172a;
        }
        .signature-sub {
            font-size: 10px;
            color: #64748b;
        }

        @media print {
            body { background: #ffffff; padding: 0; }
            .no-print-bar { display: none !important; }
            .sheet { box-shadow: none; border-radius: 0; padding: 20px 24px; max-width: 100%; }
            @page { margin: 10mm; size: letter; }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Orden de Entrega #{{ $sale->ticket_folio }}</strong>
            <span class="text-muted" style="margin-left: 8px; font-size: 12px;">Cliente: {{ $sale->customer ? $sale->customer->full_name : ($sale->delivery_recipient_name ?: 'Público General') }}</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button class="btn btn-secondary" onclick="window.close()"><i class="ri-close-line"></i> Cerrar</button>
            <button class="btn btn-primary" onclick="window.print()"><i class="ri-printer-line"></i> Imprimir Orden</button>
        </div>
    </div>

    <div class="sheet">
        {{-- Encabezado --}}
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    @php
                        $storeLogo = $sale->store && $sale->store->logo ? asset('storage/' . $sale->store->logo) : asset('build/images/urena-logo-full.png');
                    @endphp
                    <img src="{{ $storeLogo }}" alt="Mueblería Ureña" class="logo-img" onerror="this.onerror=null; this.src='{{ asset('build/images/urena-logo-full.png') }}'">
                    <div style="margin-top: 6px; font-size: 11px; color: #64748b;">
                        <strong>{{ $sale->store ? $sale->store->name : 'Mueblería Ureña' }}</strong><br>
                        {{ $sale->store ? $sale->store->address : '' }}<br>
                        Tel: {{ $sale->store ? $sale->store->phone : '' }}
                    </div>
                </td>
                <td style="width: 50%;" class="title-block">
                    <h1>Orden de Entrega</h1>
                    <div class="folio-badge">VENTA: {{ $sale->ticket_folio }}</div>
                    <div style="margin-top: 6px; font-size: 11px; color: #64748b;">
                        <strong>Emisión:</strong> {{ now()->format('d/m/Y H:i') }}<br>
                        <strong>Cajero/Vendedor:</strong> {{ $sale->seller ? $sale->seller->name : 'N/A' }}
                    </div>
                </td>
            </tr>
        </table>

        {{-- Tarjetas Cliente y Domicilio --}}
        <div class="card-grid">
            <div class="card-col">
                <div class="section-title"><i class="ri-user-location-line"></i> Destinatario y Contacto</div>
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Cliente Titular:</span>
                        <span class="info-value"><strong>{{ $sale->customer ? $sale->customer->full_name : ($sale->delivery_recipient_name ?: 'Público en General') }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Quién Recibe:</span>
                        <span class="info-value"><strong>{{ $sale->delivery_recipient_name ?: ($sale->customer ? $sale->customer->full_name : 'El titular') }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tel. Contacto:</span>
                        <span class="info-value">
                            <i class="ri-phone-fill" style="color: #2563eb;"></i> 
                            <strong>{{ $sale->delivery_phone ?: ($sale->customer ? $sale->customer->phone : 'No especificado') }}</strong>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha Probable de Entrega:</span>
                        <span class="info-value">
                            @if($sale->delivery_date)
                                <strong style="color: #059669;">{{ \Carbon\Carbon::parse($sale->delivery_date)->format('d/m/Y') }}</strong>
                            @else
                                <span class="text-muted">Por programar</span>
                            @endif
                            @if($sale->formatted_delivery_time)
                                — <strong>{{ $sale->formatted_delivery_time }}</strong>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-col">
                <div class="section-title"><i class="ri-map-pin-2-line"></i> Domicilio de Entrega</div>
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value"><strong>{{ $sale->delivery_address ?: ($sale->customer ? $sale->customer->address : 'Dirección no registrada') }}</strong></span>
                    </div>
                    @if($sale->delivery_references)
                        <div class="alert-references">
                            <strong><i class="ri-information-line"></i> Referencias de ubicación:</strong><br>
                            {{ $sale->delivery_references }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tarjeta de Ubicación / GPS para el chofer --}}
        @php
            $mapsUrl = $sale->delivery_maps_url;
            if (!$mapsUrl && $sale->delivery_lat && $sale->delivery_lng) {
                $mapsUrl = "https://www.google.com/maps?q={$sale->delivery_lat},{$sale->delivery_lng}";
            }
        @endphp

        @if($mapsUrl)
            <div class="qr-card">
                <canvas id="qr-maps-canvas" class="qr-img" width="90" height="90"></canvas>
                <div style="flex-grow: 1;">
                    <div style="font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                        <i class="ri-google-fill" style="color: #ea4335;"></i> Geolocalización para Navegación del Repartidor
                    </div>
                    <p style="font-size: 11px; color: #475569; margin-bottom: 6px;">
                        El chofer o fletero puede escanear este código QR con la cámara de su smartphone para abrir la ruta directamente en <strong>Google Maps / Waze</strong>.
                    </p>
                    <div style="font-size: 10.5px; color: #2563eb; word-break: break-all;">
                        <i class="ri-compass-3-line"></i> Coordenadas: <strong>{{ $sale->delivery_lat }}, {{ $sale->delivery_lng }}</strong>
                    </div>
                </div>
            </div>
        @endif

        {{-- Instrucciones de entrega especiales --}}
        @if($sale->delivery_instructions)
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 10px 14px; border-radius: 6px; margin-bottom: 18px; font-size: 11px; color: #1e40af;">
                <strong><i class="ri-file-text-line"></i> Instrucciones especiales para la cuadrilla:</strong>
                <div>{{ $sale->delivery_instructions }}</div>
            </div>
        @endif

        {{-- Tabla de Artículos a Entregar --}}
        <div class="section-title"><i class="ri-box-3-line"></i> Artículos Físicos a Entregar</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">Cant.</th>
                    <th style="width: 55px; text-align: center;">Foto</th>
                    <th style="width: 110px;">Código SKU</th>
                    <th>Descripción del artículo / especificaciones</th>
                    <th style="width: 130px; text-align: center;">Revisión Salida</th>
                    <th style="width: 130px; text-align: center;">Revisión Entrega</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $physicalItems = $sale->items->filter(function($i) {
                        if (!$i->product) {
                            return false;
                        }
                        // 1. Excluir productos marcados como servicio o virtual
                        if (method_exists($i->product, 'isService') && $i->product->isService()) {
                            return false;
                        }
                        if (method_exists($i->product, 'isVirtual') && $i->product->isVirtual()) {
                            return false;
                        }
                        if (in_array($i->product->type ?? '', ['service', 'virtual'])) {
                            return false;
                        }
                        // 2. Excluir por SKU reservado para flete o servicios
                        $sku = strtoupper($i->product->sku ?? '');
                        if (in_array($sku, ['SERV-FLETE', 'SERV-ARMADO', 'FLETE', 'ARMADO', 'ENVIO', 'INSTALACION'])) {
                            return false;
                        }
                        // 3. Excluir si el concepto en notas o nombre es cargo de flete/armado cargado en comodín
                        $notes = strtoupper($i->notes ?? '');
                        $name = strtoupper($i->product->name ?? '');
                        if (str_contains($notes, 'ENTREGA EN:') || str_contains($notes, 'FLETE') || str_contains($notes, 'ARMADO EN SITIO') || str_contains($notes, 'INSTALACIÓN') || str_contains($notes, 'INSTALACION')) {
                            if ($i->product->is_wildcard || str_contains($sku, 'CUSTOM-001') || str_contains($sku, 'SERV-')) {
                                return false;
                            }
                        }
                        if (str_contains($name, 'FLETE') || str_contains($name, 'ENVÍO') || str_contains($name, 'ENVIO') || str_contains($name, 'ARMADO E INSTALACIÓN') || str_contains($name, 'ARMADO E INSTALACION')) {
                            return false;
                        }
                        // 4. Validar que sea producto físico
                        if (method_exists($i->product, 'isPhysical') && !$i->product->isPhysical()) {
                            return false;
                        }
                        return true;
                    });
                @endphp

                @forelse($physicalItems as $item)
                    @php
                        $varImg = $item->variant?->files?->first() ?? $item->product?->files?->first();
                        $thumbUrl = $varImg ? asset('storage/' . $varImg->path) : null;
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 700; font-size: 13px; color: #2563eb;">
                            {{ $item->quantity }}
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            @if($thumbUrl)
                                <img src="{{ $thumbUrl }}" alt="" style="width: 38px; height: 38px; object-fit: contain; border-radius: 4px; border: 1px solid #e2e8f0; background: #fff; padding: 1px;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                <div class="no-img-placeholder" style="display: none; width: 38px; height: 38px; border-radius: 4px; border: 1px solid #e2e8f0; background: #f8fafc; align-items: center; justify-content: center; color: #94a3b8; font-size: 15px; margin: 0 auto;">
                                    <i class="ri-image-line"></i>
                                </div>
                            @else
                                <div class="no-img-placeholder" style="display: inline-flex; width: 38px; height: 38px; border-radius: 4px; border: 1px solid #e2e8f0; background: #f8fafc; align-items: center; justify-content: center; color: #94a3b8; font-size: 15px; margin: 0 auto;">
                                    <i class="ri-image-line"></i>
                                </div>
                            @endif
                        </td>
                        <td style="font-family: monospace; font-size: 11px;">
                            {{ $item->variant ? $item->variant->sku : ($item->product ? $item->product->sku : 'N/A') }}
                        </td>
                        <td>
                            <strong style="font-size: 12px; color: #0f172a;">{{ $item->product ? $item->product->name : 'Artículo' }}</strong>
                            @if($item->variant && $item->variant->name)
                                <div style="color: #475569; font-size: 11px; margin-top: 1px;">
                                    <span style="display: inline-block; background: #f1f5f9; padding: 1px 6px; border-radius: 3px; font-weight: 600;">Variante: {{ $item->variant->name }}</span>
                                </div>
                            @endif
                            @if($item->product && !empty($item->product->description))
                                <div style="color: #64748b; font-size: 10.5px; margin-top: 3px; line-height: 1.3;">
                                    {{ Str::limit(strip_tags($item->product->description), 200) }}
                                </div>
                            @endif
                            @if($item->notes)
                                <div style="color: #2563eb; font-size: 10.5px; margin-top: 3px;">
                                    <i class="ri-chat-3-line"></i> <em>Nota: {{ $item->notes }}</em>
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center; color: #94a3b8;">
                            [ &nbsp; ] Completo
                        </td>
                        <td style="text-align: center; color: #94a3b8;">
                            [ &nbsp; ] Recibido
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 16px; font-size: 11.5px;">
                            No hay artículos físicos registrados para despacho de almacén en esta orden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Estado Financiero contra Entrega --}}
        <div style="display: table; width: 100%; margin-bottom: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px;">
            <div style="display: table-cell; width: 50%; vertical-align: middle;">
                <span style="font-weight: 600; color: #475569;">Estado de la Venta:</span>
                @if($sale->status === 'pagada')
                    <span style="display: inline-block; background: #dcfce7; color: #15803d; font-weight: 700; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 6px;">
                        ✓ TOTALMENTE LIQUIDADO
                    </span>
                @elseif($sale->status === 'apartado' && $sale->balance > 0)
                    <span style="display: inline-block; background: #fee2e2; color: #b91c1c; font-weight: 700; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 6px;">
                        ⚠️ SALDO PENDIENTE POR COBRAR
                    </span>
                @else
                    <span style="display: inline-block; background: #e2e8f0; color: #334155; font-weight: 700; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 6px;">
                        {{ strtoupper($sale->status) }}
                    </span>
                @endif
                @if($sale->delivery_cost > 0 || $sale->installation_cost > 0)
                    <div style="margin-top: 4px; font-size: 10.5px; color: #64748b;">
                        @if($sale->delivery_cost > 0)
                            <span style="margin-right: 12px;"><i class="ri-truck-line"></i> Flete: <strong>${{ number_format($sale->delivery_cost, 2) }}</strong></span>
                        @endif
                        @if($sale->installation_cost > 0)
                            <span><i class="ri-tools-line"></i> Armado: <strong>${{ number_format($sale->installation_cost, 2) }}</strong></span>
                        @endif
                    </div>
                @endif
            </div>
            <div style="display: table-cell; width: 50%; text-align: right; vertical-align: middle;">
                @if($sale->balance > 0)
                    <span style="font-size: 11px; color: #b91c1c; font-weight: 600;">Monto a cobrar en entrega:</span>
                    <strong style="font-size: 15px; color: #b91c1c; margin-left: 6px;">${{ number_format($sale->balance, 2) }}</strong>
                @else
                    <span style="font-size: 11px; color: #15803d; font-weight: 600;">Saldo a cobrar:</span>
                    <strong style="font-size: 14px; color: #15803d; margin-left: 6px;">$0.00 (No cobrar nada)</strong>
                @endif
            </div>
        </div>

        {{-- Firmas de Conformidad --}}
        <table class="signature-table">
            <tr>
                <td class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-title">Entregué de Almacén</div>
                    <div class="signature-sub">Firma de Bodega / Despacho</div>
                </td>
                <td class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-title">Cuadrilla / Repartidor</div>
                    <div class="signature-sub">Chofer responsable del traslado</div>
                </td>
                <td class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-title">Recibí de Conformidad</div>
                    <div class="signature-sub">{{ $sale->delivery_recipient_name ?: ($sale->customer ? $sale->customer->full_name : 'Nombre, Firma del Cliente y Fecha') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('qr-maps-canvas');
            if (canvas && typeof QRCode !== 'undefined') {
                const mapsUrl = @json($mapsUrl ?? '');
                if (mapsUrl) {
                    QRCode.toCanvas(canvas, mapsUrl, {
                        width: 180, // Renderizamos a 180px para nitidez HD en impresión
                        margin: 1,
                        color: {
                            dark: '#0f172a',
                            light: '#ffffff'
                        }
                    }, function(error) {
                        if (error) console.error('Error al generar código QR en canvas:', error);
                    });
                }
            }
        });
    </script>
</body>
</html>
