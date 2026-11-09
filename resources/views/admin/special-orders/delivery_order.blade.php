<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Entrega - Pedido Especial {{ $specialOrder->folio }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            letter-spacing: 0.5px;
            color: #475569;
            background: #f8fafc;
            border-left: 3px solid #2563eb;
            padding: 4px 8px;
            margin: 16px 0 8px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 11.5px;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
            width: 140px;
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
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .prod-thumb {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: 2px;
        }

        .no-img-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 4px;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 18px;
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
            <strong>Orden de Entrega Pedido Especial #{{ $specialOrder->folio }}</strong>
            <span class="text-muted" style="margin-left: 8px; font-size: 12px;">Cliente: {{ $specialOrder->customer ? $specialOrder->customer->full_name : ($specialOrder->delivery_recipient_name ?: 'Público General') }}</span>
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
                <td style="width: 55%;">
                    @php
                        $storeLogo = $specialOrder->store && $specialOrder->store->logo ? asset('storage/' . $specialOrder->store->logo) : asset('build/images/urena-logo-full.png');
                    @endphp
                    <img src="{{ $storeLogo }}" alt="Logo" class="logo-img" onerror="this.onerror=null; this.src='{{ asset('build/images/urena-logo-full.png') }}'">
                    <div style="font-size: 11px; color: #475569; margin-top: 6px; line-height: 1.3;">
                        <strong>{{ $specialOrder->store ? $specialOrder->store->name : 'Mueblería Ureña' }}</strong><br>
                        {{ $specialOrder->store ? $specialOrder->store->address : 'Sucursal Matriz' }}<br>
                        Tel: {{ $specialOrder->store ? $specialOrder->store->phone : 'Sin teléfono' }}
                    </div>
                </td>
                <td class="title-block" style="width: 45%;">
                    <h1>Orden de Entrega</h1>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 4px;">Logística & Transporte (Pedido Especial)</div>
                    <div class="folio-badge">{{ $specialOrder->folio }}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 6px;">
                        Fecha Emisión: <strong>{{ $specialOrder->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Datos de Programación y Saldo --}}
        <div style="display: table; width: 100%; margin-bottom: 12px;">
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 12px;">
                <div class="section-title"><i class="ri-calendar-event-line"></i> Programación de Envío</div>
                <table class="info-table">
                    <tr>
                        <td class="info-label">Fecha Probable de Entrega:</td>
                        <td class="info-value">
                            <strong style="color: #2563eb; font-size: 13px;">
                                {{ $specialOrder->delivery_date ? \Carbon\Carbon::parse($specialOrder->delivery_date)->format('d/m/Y') : ($specialOrder->expected_delivery_date ? $specialOrder->expected_delivery_date->format('d/m/Y') : 'Por coordinar') }}
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="info-label">Hora Específica:</td>
                        <td class="info-value"><strong>{{ $specialOrder->formatted_delivery_time ?: 'Por definir' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label">Vendedor Asesor:</td>
                        <td class="info-value">{{ $specialOrder->seller ? $specialOrder->seller->name : 'Caja' }}</td>
                    </tr>
                </table>
            </div>
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 12px;">
                <div class="section-title"><i class="ri-money-dollar-circle-line"></i> Estado de Cobro</div>
                <table class="info-table">
                    <tr>
                        <td class="info-label">Total del Pedido:</td>
                        <td class="info-value"><strong>${{ number_format($specialOrder->total, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label">Total Abonado:</td>
                        <td class="info-value" style="color: #16a34a;">${{ number_format($specialOrder->totalPaid(), 2) }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Saldo Pendiente:</td>
                        <td class="info-value">
                            @if($specialOrder->balanceDue() > 0.01)
                                <strong style="color: #dc2626; font-size: 13px;">${{ number_format($specialOrder->balanceDue(), 2) }} (COBRAR EN DESTINO)</strong>
                            @else
                                <strong style="color: #16a34a; font-size: 13px;">$0.00 (TOTALMENTE SALDADO)</strong>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Datos del Destinatario y Dirección --}}
        <div class="section-title"><i class="ri-map-pin-user-line"></i> Destino y Contacto de Entrega</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Receptor / Cliente:</td>
                <td class="info-value">
                    <strong>{{ $specialOrder->delivery_recipient_name ?: ($specialOrder->customer ? $specialOrder->customer->full_name : 'No especificado') }}</strong>
                </td>
                <td class="info-label" style="width: 120px;">Teléfono:</td>
                <td class="info-value">
                    <strong>{{ $specialOrder->delivery_phone ?: ($specialOrder->customer ? $specialOrder->customer->phone : 'No registrado') }}</strong>
                </td>
            </tr>
            <tr>
                <td class="info-label">Dirección Completa:</td>
                <td class="info-value" colspan="3">
                    <span style="font-size: 12.5px; font-weight: 600; color: #0f172a;">
                        {{ $specialOrder->delivery_address ?: ($specialOrder->customer ? $specialOrder->customer->address : 'Dirección pendiente') }}
                    </span>
                </td>
            </tr>
            @if($specialOrder->delivery_instructions)
            <tr>
                <td class="info-label">Instrucciones:</td>
                <td class="info-value" colspan="3">
                    <em>{{ $specialOrder->delivery_instructions }}</em>
                </td>
            </tr>
            @endif
        </table>

        @if($specialOrder->delivery_references)
            <div class="alert-references">
                <strong><i class="ri-alert-line me-1"></i> Referencias de Ubicación / Acceso:</strong><br>
                {{ $specialOrder->delivery_references }}
            </div>
        @endif

        {{-- Geolocalización QR --}}
        @php
            $mapsUrl = $specialOrder->delivery_maps_url ?: (($specialOrder->delivery_lat && $specialOrder->delivery_lng) ? "https://www.google.com/maps?q={$specialOrder->delivery_lat},{$specialOrder->delivery_lng}" : null);
        @endphp

        @if($mapsUrl)
            <div class="qr-card" style="margin-top: 12px;">
                <canvas id="qr-canvas" class="qr-img"></canvas>
                <div>
                    <div style="font-weight: 700; font-size: 12px; color: #0f172a; margin-bottom: 2px;">
                        <i class="ri-qr-code-line text-primary"></i> Navegación GPS Google Maps
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 6px;">
                        El chofer o repartidor puede escanear este código con su smartphone para abrir la ruta directamente en Google Maps.
                    </div>
                    <div style="font-family: monospace; font-size: 10px; color: #2563eb; word-break: break-all;">
                        {{ $mapsUrl }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Artículos Físicos a Entregar --}}
        <div class="section-title"><i class="ri-box-3-line"></i> Artículos Físicos a Entregar</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">Cant.</th>
                    <th style="width: 55px; text-align: center;">Foto</th>
                    <th style="width: 120px;">SKU / Código</th>
                    <th>Descripción del Producto / Especificaciones</th>
                    <th style="width: 110px; text-align: center;">Salida Bodega</th>
                    <th style="width: 110px; text-align: center;">Recibido Cliente</th>
                </tr>
            </thead>
            <tbody>
                @forelse($specialOrder->items as $item)
                    @php
                        $varImg = $item->variant?->files?->first() ?? $item->product?->files?->first();
                        $imageUrl = $varImg ? asset('storage/' . $varImg->path) : null;
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 700; font-size: 13px; color: #2563eb;">
                            {{ $item->quantity }}
                        </td>
                        <td style="text-align: center;">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="" class="prod-thumb" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                <div class="no-img-placeholder" style="display: none;"><i class="ri-image-line"></i></div>
                            @else
                                <div class="no-img-placeholder"><i class="ri-image-line"></i></div>
                            @endif
                        </td>
                        <td style="font-family: monospace; font-size: 11px;">
                            {{ $item->variant ? $item->variant->sku : ($item->product ? $item->product->sku : 'N/A') }}
                        </td>
                        <td>
                            <strong>{{ $item->product ? $item->product->name : 'Artículo' }}</strong>
                            @if($item->variant && $item->variant->name)
                                <span style="background: #e2e8f0; padding: 1px 5px; border-radius: 3px; font-size: 10px; color: #334155; margin-left: 4px;">{{ $item->variant->name }}</span>
                            @endif
                            @if($item->notes)
                                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">
                                    <em>{{ $item->notes }}</em>
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center; color: #94a3b8; font-size: 10px;">
                            [ &nbsp; ] OK
                        </td>
                        <td style="text-align: center; color: #94a3b8; font-size: 10px;">
                            [ &nbsp; ] Conforme
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b; padding: 12px;">Sin artículos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Firmas de Control y Conformidad --}}
        <table class="signature-table">
            <tr>
                <td class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-title">Entrega en Bodega</div>
                    <div class="signature-sub">Nombre, Fecha y Firma</div>
                </td>
                <td class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-title">Chofer / Repartidor</div>
                    <div class="signature-sub">Nombre y Firma de Conforme</div>
                </td>
                <td class="signature-col">
                    <div class="signature-line"></div>
                    <div class="signature-title">Cliente Receptor</div>
                    <div class="signature-sub">Firma de Recibido a Entera Satisfacción</div>
                </td>
            </tr>
        </table>
    </div>

    @if($mapsUrl)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            QRCode.toCanvas(document.getElementById('qr-canvas'), "{{ $mapsUrl }}", {
                width: 90,
                margin: 1,
                color: { dark: '#0f172a', light: '#ffffff' }
            }, function (error) {
                if (error) console.error(error);
            });
        });
    </script>
    @endif

</body>
</html>
