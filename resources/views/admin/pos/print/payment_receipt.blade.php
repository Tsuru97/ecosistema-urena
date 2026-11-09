<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Abono - #{{ $payment->id }} ({{ $sale->ticket_folio }})</title>
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
            max-width: 650px;
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
        .btn-primary { background: #059669; color: #ffffff; }
        .btn-primary:hover { background: #047857; }
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
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 32px 36px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            position: relative;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #059669;
            padding-bottom: 14px;
        }
        .header-table td { vertical-align: top; }

        .logo-img {
            max-height: 50px;
            max-width: 160px;
            object-fit: contain;
        }

        .title-block { text-align: right; }
        .title-block h1 {
            font-size: 17px;
            font-weight: 800;
            color: #059669;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .folio-badge {
            display: inline-block;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 16px;
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
            min-width: 130px;
        }
        .info-value {
            color: #0f172a;
            font-weight: 500;
        }

        /* Tabla de Desglose de Abono */
        .abono-box {
            background: #ecfdf5;
            border: 2px solid #a7f3d0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 18px;
        }
        .abono-table {
            width: 100%;
            border-collapse: collapse;
        }
        .abono-table td {
            padding: 6px 4px;
            font-size: 13px;
        }

        .signatures-grid {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        .sign-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 20px;
        }
        .sign-line {
            border-top: 1px solid #64748b;
            margin-top: 45px;
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
        body.ticket-mode .info-card {
            padding: 8px;
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
            <strong>Recibo de Abono #{{ $payment->id }}</strong>
            <span style="color: #64748b; margin-left: 8px;">Ticket: <strong>{{ $sale->ticket_folio }}</strong></span>
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
                    <h1>Recibo de Abono</h1>
                    <div class="folio-badge">RECIBO: #{{ $payment->id }}</div>
                    <div style="margin-top: 6px; font-size: 11px; color: #475569;">
                        <strong>Ticket de Venta:</strong> {{ $sale->ticket_folio }}<br>
                        <strong>Fecha / Hora:</strong> {{ $payment->created_at->format('d/m/Y H:i') }}<br>
                        <strong>Cajero:</strong> {{ $payment->cashier ? $payment->cashier->name : ($sale->cashier ? $sale->cashier->name : 'Caja Principal') }}
                    </div>
                </td>
            </tr>
        </table>

        {{-- Datos del Cliente y Apartado --}}
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Cliente Titular:</span>
                <span class="info-value"><strong>{{ $sale->customer ? $sale->customer->full_name : ($sale->delivery_recipient_name ?: 'Público en General') }}</strong></span>
            </div>
            @if($sale->customer && $sale->customer->phone)
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value">{{ $sale->customer->phone }}</span>
                </div>
            @endif
            <div class="info-row">
                <span class="info-label">Total de la Operación:</span>
                <span class="info-value" style="font-family: 'JetBrains Mono', monospace; font-weight: 700;">${{ number_format($sale->total, 2) }}</span>
            </div>
            @if($sale->hasDelivery() || $sale->delivery_date)
                <div class="info-row">
                    <span class="info-label">Fecha Probable de Entrega:</span>
                    <span class="info-value">
                        <strong>{{ $sale->delivery_date ? \Carbon\Carbon::parse($sale->delivery_date)->format('d/m/Y') : 'Por programar' }}</strong>
                        {{ $sale->formatted_delivery_time ? '— ' . $sale->formatted_delivery_time : '' }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Desglose del Abono --}}
        @php
            $previousBalance = (float)$sale->balance + (float)$payment->amount;
        @endphp
        <div class="abono-box">
            <table class="abono-table">
                <tr>
                    <td style="color: #475569; font-weight: 600;">Saldo Anterior al Abono:</td>
                    <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 600;">
                        ${{ number_format($previousBalance, 2) }}
                    </td>
                </tr>
                <tr style="border-top: 1px dashed #a7f3d0; border-bottom: 1px dashed #a7f3d0;">
                    <td style="color: #065f46; font-size: 15px; font-weight: 800; padding: 10px 4px;">
                        <i class="ri-checkbox-circle-line"></i> MONTO ABONADO:
                    </td>
                    <td style="text-align: right; color: #047857; font-size: 18px; font-weight: 800; font-family: 'JetBrains Mono', monospace; padding: 10px 4px;">
                        ${{ number_format($payment->amount, 2) }}
                    </td>
                </tr>
                <tr>
                    <td style="color: #991b1b; font-weight: 700; padding-top: 8px;">
                        NUEVO SALDO PENDIENTE:
                    </td>
                    <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-size: 15px; font-weight: 800; color: {{ $sale->balance <= 0.01 ? '#059669' : '#b91c1c' }}; padding-top: 8px;">
                        @if($sale->balance <= 0.01)
                            $0.00 (LIQUIDADO ✓)
                        @else
                            ${{ number_format($sale->balance, 2) }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Detalles del Pago --}}
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Forma de Pago:</span>
                <span class="info-value"><strong>{{ $payment->paymentMethod ? $payment->paymentMethod->name : 'Pago Registrado' }}</strong></span>
            </div>
            @if($payment->terminal)
                <div class="info-row">
                    <span class="info-label">Terminal Bancaria:</span>
                    <span class="info-value">{{ $payment->terminal->name }}</span>
                </div>
            @endif
            @if($payment->reference)
                <div class="info-row">
                    <span class="info-label">Referencia / Folio:</span>
                    <span class="info-value">{{ $payment->reference }}</span>
                </div>
            @endif
            @if($payment->notes)
                <div class="info-row">
                    <span class="info-label">Observaciones:</span>
                    <span class="info-value"><em>{{ $payment->notes }}</em></span>
                </div>
            @endif
            <div class="info-row">
                <span class="info-label">Estatus Venta:</span>
                <span class="info-value">
                    @if($sale->status === 'pagada')
                        <strong style="color: #059669;">✓ TOTALMENTE LIQUIDADO - LISTO PARA ENTREGA</strong>
                    @else
                        <strong style="color: #ea580c;">APARTADO ACTIVO (Saldo Pendiente)</strong>
                    @endif
                </span>
            </div>
        </div>

        {{-- Firmas --}}
        <div class="signatures-grid">
            <div class="sign-col">
                <div class="sign-line"></div>
                <strong style="font-size: 11px;">Firma del Cliente</strong><br>
                <span style="font-size: 10px; color: #64748b;">Recibí comprobante de abono</span>
            </div>
            <div class="sign-col">
                <div class="sign-line"></div>
                <strong style="font-size: 11px;">{{ $payment->cashier ? $payment->cashier->name : 'Caja' }}</strong><br>
                <span style="font-size: 10px; color: #64748b;">Cajero / Cobro recibido</span>
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
