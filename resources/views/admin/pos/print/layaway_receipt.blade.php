<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Apartado - {{ $sale->ticket_folio }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px 0;
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
            padding: 7px 14px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #b91c1c; color: #ffffff; }
        .btn-primary:hover { background: #991b1b; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }

        .switcher-group {
            display: inline-flex;
            background: #e2e8f0;
            padding: 3px;
            border-radius: 7px;
            gap: 2px;
        }
        .switcher-group .btn-switch {
            border: none;
            background: transparent;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            color: #475569;
        }
        .switcher-group .btn-switch.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

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
            border-bottom: 2px solid #b91c1c;
            padding-bottom: 16px;
        }
        .header-table td { vertical-align: top; }

        .logo-img {
            max-height: 55px;
            max-width: 180px;
            object-fit: contain;
        }

        .title-block { text-align: right; }
        .title-block h1 {
            font-size: 18px;
            font-weight: 800;
            color: #b91c1c;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .folio-badge {
            display: inline-block;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
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
            padding-right: 10px;
        }
        .card-col:last-child {
            padding-right: 0;
            padding-left: 10px;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            height: 100%;
        }
        .info-row {
            margin-bottom: 5px;
            font-size: 12px;
        }
        .info-row:last-child { margin-bottom: 0; }
        .info-label {
            font-weight: 600;
            color: #475569;
            display: inline-block;
            min-width: 100px;
        }
        .info-value {
            color: #0f172a;
            font-weight: 500;
        }

        /* Banner de Saldo Pendiente */
        .balance-banner {
            background: #fff1f2;
            border: 2px solid #fecdd3;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }
        .banner-col {
            display: table-cell;
            vertical-align: middle;
        }
        .banner-amount {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 11.5px;
        }
        .items-table th {
            background: #1e293b;
            color: #ffffff;
            font-weight: 600;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) td { background: #f8fafc; }

        .signatures-grid {
            display: table;
            width: 100%;
            margin-top: 36px;
        }
        .sign-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 30px;
        }
        .sign-line {
            border-top: 1px solid #64748b;
            margin-top: 50px;
            margin-bottom: 6px;
        }

        /* ── MODO TICKET 80MM ────────────────────────── */
        body.ticket-mode {
            background: #ffffff;
            padding: 0;
            font-size: 11px;
        }
        body.ticket-mode .sheet {
            max-width: 80mm !important;
            width: 80mm !important;
            padding: 10px 8px !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            margin: 0 auto;
        }
        body.ticket-mode .logo-img {
            max-height: 40px;
            max-width: 130px;
            display: block;
            margin: 0 auto 6px auto;
        }
        body.ticket-mode .header-table,
        body.ticket-mode .header-table tr,
        body.ticket-mode .header-table td {
            display: block;
            width: 100% !important;
            text-align: center !important;
        }
        body.ticket-mode .title-block {
            text-align: center !important;
            margin-top: 6px;
        }
        body.ticket-mode .card-grid {
            display: block !important;
        }
        body.ticket-mode .card-col {
            display: block !important;
            width: 100% !important;
            padding: 0 !important;
            margin-bottom: 8px;
        }
        body.ticket-mode .balance-banner {
            display: block !important;
            padding: 10px !important;
        }
        body.ticket-mode .banner-col {
            display: block !important;
            width: 100% !important;
            text-align: center !important;
        }
        body.ticket-mode .banner-amount {
            text-align: center !important;
            margin-top: 8px;
        }
        body.ticket-mode .items-table th,
        body.ticket-mode .items-table td {
            padding: 4px 3px;
            font-size: 10px;
        }
        body.ticket-mode .signatures-grid {
            display: block !important;
        }
        body.ticket-mode .sign-col {
            display: block !important;
            width: 100% !important;
            padding: 0 10px !important;
            margin-top: 20px;
        }
        body.ticket-mode .sign-line {
            margin-top: 30px;
        }

        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .no-print-bar { display: none !important; }
            .sheet { box-shadow: none !important; border-radius: 0 !important; }
            
            body:not(.ticket-mode) .sheet {
                padding: 15px 20px !important;
                max-width: 100% !important;
            }
            body:not(.ticket-mode) @page {
                size: letter portrait;
                margin: 8mm;
            }

            body.ticket-mode .sheet {
                width: 80mm !important;
                max-width: 80mm !important;
                padding: 4mm !important;
            }
            body.ticket-mode @page {
                size: 80mm auto;
                margin: 2mm;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Comprobante de Apartado / Anticipo</strong>
            <span style="color: #64748b; margin-left: 8px;">Folio: <strong>{{ $sale->ticket_folio }}</strong></span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <div class="switcher-group">
                <button type="button" class="btn-switch active" id="btn-mode-letter" onclick="setFormat('letter')"><i class="ri-file-paper-2-line"></i> Carta / A4</button>
                <button type="button" class="btn-switch" id="btn-mode-ticket" onclick="setFormat('ticket')"><i class="ri-receipt-line"></i> Ticket 80mm</button>
            </div>
            <button class="btn btn-secondary" onclick="window.close()"><i class="ri-close-line"></i> Cerrar</button>
            <button class="btn btn-primary" onclick="window.print()"><i class="ri-printer-line"></i> Imprimir</button>
        </div>
    </div>

    <div class="sheet">
        {{-- Encabezado --}}
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    @php
                        $storeLogo = $sale->store && $sale->store->logo ? asset('storage/' . $sale->store->logo) : asset('build/images/urena-logo-full.png');
                    @endphp
                    <img src="{{ $storeLogo }}" alt="Mueblería Ureña" class="logo-img" onerror="this.onerror=null; this.src='{{ asset('build/images/urena-logo-full.png') }}'">
                    <div style="margin-top: 6px; font-size: 11px; color: #475569;">
                        <strong>{{ $sale->store ? $sale->store->name : 'Mueblería Ureña' }}</strong><br>
                        {{ $sale->store ? $sale->store->address : '' }}<br>
                        Tel: {{ $sale->store ? $sale->store->phone : '' }}
                    </div>
                </td>
                <td style="width: 45%;" class="title-block">
                    <h1>Comprobante de Apartado</h1>
                    <div class="folio-badge">TICKET: {{ $sale->ticket_folio }}</div>
                    <div style="margin-top: 8px; font-size: 11px; color: #475569;">
                        <strong>Fecha de Registro:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}<br>
                        <strong>Cajero en Turno:</strong> {{ $sale->cashier ? $sale->cashier->name : 'Caja Principal' }}<br>
                        <strong>Vendedor:</strong> {{ $sale->seller ? $sale->seller->name : ($sale->cashier ? $sale->cashier->name : 'General') }}
                    </div>
                </td>
            </tr>
        </table>

        {{-- Estado Financiero Destacado (Banner de Saldo) --}}
        <div class="balance-banner">
            <div class="banner-col" style="width: 60%;">
                <div style="font-size: 13px; font-weight: 800; color: #991b1b; text-transform: uppercase;">
                    <i class="ri-alarm-warning-line"></i> Mercancía Reservada en Bodega
                </div>
                <div style="font-size: 11px; color: #7f1d1d; margin-top: 2px;">
                    El cliente cuenta con mercancía retenida a su nombre hasta liquidar la totalidad de su saldo.
                </div>
                <div style="font-size: 11px; margin-top: 6px; color: #475569;">
                    Total de la Venta: <strong style="font-family: 'JetBrains Mono', monospace;">${{ number_format($sale->total, 2) }}</strong>
                    @if(($sale->coupon_discount ?? 0) > 0)
                        | Cupón ({{ $sale->coupon_code ?: 'PROMO' }}): <strong style="font-family: 'JetBrains Mono', monospace; color: #059669;">-${{ number_format($sale->coupon_discount, 2) }}</strong>
                    @endif
                    | Total Abonado: <strong style="font-family: 'JetBrains Mono', monospace; color: #059669;">${{ number_format($sale->amount_paid, 2) }}</strong>
                </div>
            </div>
            <div class="banner-col banner-amount" style="width: 40%;">
                <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase;">Saldo Pendiente por Liquidar:</div>
                <div style="font-size: 24px; font-weight: 800; color: #b91c1c;">
                    ${{ number_format($sale->balance, 2) }}
                </div>
            </div>
        </div>

        {{-- Datos de Cliente --}}
        <div class="card-grid">
            <div class="card-col">
                <div class="section-title"><i class="ri-user-line"></i> Datos del Titular</div>
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Cliente:</span>
                        <span class="info-value"><strong>{{ $sale->customer ? $sale->customer->full_name : ($sale->delivery_recipient_name ?: 'Público en General') }}</strong></span>
                    </div>
                    @if($sale->customer && $sale->customer->rfc)
                        <div class="info-row">
                            <span class="info-label">RFC:</span>
                            <span class="info-value">{{ $sale->customer->rfc }}</span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value">{{ $sale->customer ? $sale->customer->phone : ($sale->delivery_phone ?: 'No registrado') }}</span>
                    </div>
                </div>
            </div>

            <div class="card-col">
                <div class="section-title"><i class="ri-truck-line"></i> Modalidad de Entrega Programada</div>
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Tipo Entrega:</span>
                        <span class="info-value">
                            @if($sale->hasDelivery())
                                <strong style="color: #2563eb;"><i class="ri-truck-fill"></i> A Domicilio (al liquidar)</strong>
                            @else
                                <strong style="color: #059669;"><i class="ri-store-2-fill"></i> En Tienda (al liquidar)</strong>
                            @endif
                        </span>
                    </div>
                    @if($sale->hasDelivery())
                        @if($sale->delivery_recipient_name)
                            <div class="info-row">
                                <span class="info-label">Destinatario:</span>
                                <span class="info-value">{{ $sale->delivery_recipient_name }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">Dirección:</span>
                            <span class="info-value">{{ $sale->delivery_address ?: ($sale->customer ? $sale->customer->address : '') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Fecha Probable de Entrega:</span>
                            <span class="info-value">
                                <strong>{{ $sale->delivery_date ? \Carbon\Carbon::parse($sale->delivery_date)->format('d/m/Y') : 'Por programar' }}</strong>
                                {{ $sale->formatted_delivery_time ? '— ' . $sale->formatted_delivery_time : '' }}
                            </span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Estatus Entrega:</span>
                        <span class="info-value" style="color: #ea580c; font-weight: 700;">Pendiente de Liquidación</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Artículos Apartados --}}
        <div class="section-title"><i class="ri-box-3-line"></i> Artículos Apartados y Reservados</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45px; text-align: center;">Cant.</th>
                    <th style="width: 110px;">SKU</th>
                    <th>Descripción / Especificaciones</th>
                    <th style="width: 90px; text-align: right;">P. Unit</th>
                    <th style="width: 90px; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td style="text-align: center; font-weight: 700; color: #b91c1c;">{{ $item->quantity }}</td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-size: 11px;">
                            {{ $item->variant ? $item->variant->sku : ($item->product ? $item->product->sku : 'N/A') }}
                        </td>
                        <td>
                            <strong>{{ $item->product ? $item->product->name : 'Artículo' }}</strong>
                            @if($item->variant && $item->variant->name)
                                <span style="background: #f1f5f9; padding: 1px 5px; border-radius: 3px; font-size: 10px; color: #475569; margin-left: 4px;">{{ $item->variant->name }}</span>
                            @endif
                            @if($item->notes)
                                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">
                                    <em>{{ $item->notes }}</em>
                                </div>
                            @endif
                        </td>
                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace;">${{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align: right; font-weight: 700; font-family: 'JetBrains Mono', monospace;">${{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Historial de Anticipos y Abonos Registrados --}}
        <div class="section-title"><i class="ri-history-line"></i> Historial de Anticipos y Abonos Registrados</div>
        <table class="items-table" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="width: 50px;"># Pago</th>
                    <th style="width: 120px;">Fecha / Hora</th>
                    <th>Método / Forma de Pago</th>
                    <th>Referencia</th>
                    <th style="width: 100px; text-align: right;">Monto Abonado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sale->payments as $idx => $p)
                    <tr>
                        <td style="font-weight: 700;">{{ $idx + 1 }}</td>
                        <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <strong>{{ $p->paymentMethod ? $p->paymentMethod->name : 'Pago' }}</strong>
                            @if($p->terminal)
                                <span style="font-size: 10px; color: #64748b;">(Terminal: {{ $p->terminal->name }})</span>
                            @endif
                        </td>
                        <td style="font-size: 11px; color: #64748b;">{{ $p->reference ?: '-' }}</td>
                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #059669;">
                            ${{ number_format($p->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 10px;">Sin pagos registrados aún.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Cláusula de Apartado y Firmas --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; font-size: 10.5px; color: #64748b; line-height: 1.4;">
            <strong>Términos del Sistema de Apartado:</strong>
            1. Los artículos descritos en este comprobante quedan reservados en bodega a nombre del cliente por un periodo máximo de 30 días naturales a partir de su emisión.<br>
            2. Para recoger o programar el flete de la mercancía, el cliente deberá liquidar el 100% del saldo pendiente ($ {{ number_format($sale->balance, 2) }}).<br>
            3. Conserve este comprobante original para cualquier abono subsecuente o entrega final.
        </div>

        <div class="signatures-grid">
            <div class="sign-col">
                <div class="sign-line"></div>
                <strong style="font-size: 11px;">Firma de Aceptación del Cliente</strong><br>
                <span style="font-size: 10px; color: #64748b;">Acepto condiciones de apartado y saldo pendiente</span>
            </div>
            <div class="sign-col">
                <div class="sign-line"></div>
                <strong style="font-size: 11px;">{{ $sale->cashier ? $sale->cashier->name : 'Cajero Responsable' }}</strong><br>
                <span style="font-size: 10px; color: #64748b;">Caja / Control de Apartados</span>
            </div>
        </div>
    </div>

    <script>
        function setFormat(mode) {
            const body = document.body;
            const btnLetter = document.getElementById('btn-mode-letter');
            const btnTicket = document.getElementById('btn-mode-ticket');

            if (mode === 'ticket') {
                body.classList.add('ticket-mode');
                btnTicket.classList.add('active');
                btnLetter.classList.remove('active');
            } else {
                body.classList.remove('ticket-mode');
                btnLetter.classList.add('active');
                btnTicket.classList.remove('active');
            }
        }
    </script>
</body>
</html>
