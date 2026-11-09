<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pagos - {{ $sale->ticket_folio }}</title>
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
        .btn-primary { background: #2563eb; color: #ffffff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-outline { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; }
        .btn-outline:hover, .btn-outline.active { background: #0f172a; color: #ffffff; border-color: #0f172a; }

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

        /* Formato Hoja Carta / A4 */
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
            border-bottom: 2px solid #0f172a;
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
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .folio-tag {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-family: 'JetBrains Mono', monospace;
            border: 1px solid #bfdbfe;
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

        .card-grid {
            display: table;
            width: 100%;
            margin-bottom: 16px;
        }
        .card-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .card-col:first-child { padding-right: 10px; }
        .card-col:last-child { padding-left: 10px; }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 11.5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .info-label { color: #64748b; font-weight: 500; }
        .info-value { font-weight: 600; color: #0f172a; text-align: right; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11.5px;
        }
        .items-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 600;
            text-align: left;
            padding: 7px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .items-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td { background: #f8fafc; }

        .kpi-row {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }
        .kpi-col {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .kpi-col:first-child { border-radius: 6px 0 0 6px; }
        .kpi-col:last-child { border-radius: 0 6px 6px 0; }
        .kpi-label { font-size: 10.5px; font-weight: 600; text-transform: uppercase; color: #64748b; }
        .kpi-val { font-size: 16px; font-weight: 800; font-family: 'JetBrains Mono', monospace; margin-top: 2px; }

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
            border-top: 1px solid #475569;
            margin-bottom: 4px;
        }

        /* ── MODO TICKET 80MM ── */
        body.ticket-mode .sheet {
            max-width: 80mm !important;
            padding: 10px 8px !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            background: #ffffff;
            font-size: 10.5px;
        }
        body.ticket-mode .header-table,
        body.ticket-mode .card-grid,
        body.ticket-mode .kpi-row,
        body.ticket-mode .signatures-grid {
            display: block !important;
            width: 100% !important;
        }
        body.ticket-mode .card-col,
        body.ticket-mode .kpi-col,
        body.ticket-mode .sign-col {
            display: block !important;
            width: 100% !important;
            padding: 0 !important;
            margin-bottom: 8px !important;
        }
        body.ticket-mode .title-block { text-align: center !important; margin-top: 4px; }
        body.ticket-mode .items-table th,
        body.ticket-mode .items-table td {
            padding: 4px 2px;
            font-size: 9.5px;
        }

        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .no-print-bar { display: none !important; }
            .sheet { box-shadow: none !important; border-radius: 0 !important; }
            body:not(.ticket-mode) .sheet { padding: 18px 20px; max-width: 100%; }
            body.ticket-mode .sheet { max-width: 80mm !important; padding: 4px 6px !important; }
            @page { margin: 8mm; }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div style="display: flex; gap: 10px; align-items: center;">
            <div class="switcher-group">
                <button type="button" class="btn-switch active" id="btn-mode-sheet" onclick="setMode('sheet')">
                    <i class="ri-file-text-line"></i> Hoja Carta
                </button>
                <button type="button" class="btn-switch" id="btn-mode-ticket" onclick="setMode('ticket')">
                    <i class="ri-receipt-line"></i> Ticket 80mm
                </button>
            </div>
            <span class="text-muted" style="font-size: 11.5px;">Venta: <strong>{{ $sale->ticket_folio }}</strong></span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button class="btn btn-secondary" onclick="window.close()"><i class="ri-close-line"></i> Cerrar</button>
            <button class="btn btn-primary" onclick="window.print()"><i class="ri-printer-line"></i> Imprimir</button>
        </div>
    </div>

    <div class="sheet" id="print-sheet">
        {{-- Encabezado --}}
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    @php
                        $storeLogo = $sale->store && $sale->store->logo ? asset('storage/' . $sale->store->logo) : asset('build/images/urena-logo-full.png');
                    @endphp
                    <img src="{{ $storeLogo }}" alt="Logo" class="logo-img" onerror="this.onerror=null; this.src='{{ asset('build/images/urena-logo-full.png') }}'">
                    <div style="font-size: 11px; color: #475569; margin-top: 6px; line-height: 1.3;">
                        <strong>{{ $sale->store ? $sale->store->name : 'Mueblería Ureña' }}</strong><br>
                        {{ $sale->store ? $sale->store->address : 'Sucursal Matriz' }}<br>
                        Tel: {{ $sale->store ? $sale->store->phone : 'Sin teléfono' }}
                    </div>
                </td>
                <td class="title-block" style="width: 45%;">
                    <h1>Historial de Pagos</h1>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 4px;">Estado de Cuenta & Abonos</div>
                    <div class="folio-tag">{{ $sale->ticket_folio }}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 6px;">
                        Fecha Venta: <strong>{{ $sale->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Resumen Financiero KPIs --}}
        <div class="kpi-row">
            <div class="kpi-col">
                <div class="kpi-label">Total Venta</div>
                <div class="kpi-val" style="color: #0f172a;">${{ number_format($sale->total, 2) }}</div>
            </div>
            <div class="kpi-col">
                <div class="kpi-label">Total Abonado</div>
                <div class="kpi-val" style="color: #16a34a;">${{ number_format($sale->amount_paid, 2) }}</div>
            </div>
            <div class="kpi-col">
                <div class="kpi-label">Saldo Pendiente</div>
                <div class="kpi-val" style="color: {{ $sale->balance > 0.01 ? '#dc2626' : '#16a34a' }};">
                    ${{ number_format($sale->balance, 2) }}
                </div>
            </div>
        </div>

        {{-- Datos de la Cuenta y Cliente --}}
        <div class="card-grid">
            <div class="card-col">
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Cliente:</span>
                        <span class="info-value">{{ $sale->customer ? $sale->customer->full_name : ($sale->delivery_recipient_name ?: 'Público en General') }}</span>
                    </div>
                    @if($sale->customer && $sale->customer->phone)
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value">{{ $sale->customer->phone }}</span>
                    </div>
                    @endif
                    @if($sale->customer && $sale->customer->rfc)
                    <div class="info-row">
                        <span class="info-label">RFC:</span>
                        <span class="info-value">{{ $sale->customer->rfc }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-col">
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Estado de Cuenta:</span>
                        <span class="info-value">
                            @if($sale->balance <= 0.01)
                                <span style="color: #16a34a; font-weight: 700;">100% SALDADA</span>
                            @else
                                <span style="color: #dc2626; font-weight: 700;">APARTADO CON SALDO</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Vendedor Asesor:</span>
                        <span class="info-value">{{ $sale->seller ? $sale->seller->name : 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Último Abono:</span>
                        <span class="info-value">{{ $sale->payments->last() ? $sale->payments->last()->created_at->format('d/m/Y H:i') : '—' }}</span>
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
            </div>
        </div>

        {{-- Tabla Cronológica de Pagos y Abonos --}}
        <div class="section-title"><i class="ri-history-line"></i> Desglose Cronológico de Pagos y Abonos</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">#</th>
                    <th style="width: 120px;">Fecha y Hora</th>
                    <th>Método / Forma</th>
                    <th>Terminal / Ref.</th>
                    <th>Turno / Cajero</th>
                    <th style="width: 95px; text-align: right;">Monto Abonado</th>
                    <th style="width: 95px; text-align: right;">Saldo Restante</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $runningBalance = (float)$sale->total;
                @endphp
                @forelse($sale->payments as $index => $payment)
                    @php
                        $runningBalance = max(0, $runningBalance - (float)$payment->amount);
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 700;">{{ $index + 1 }}</td>
                        <td style="font-size: 11px;">
                            {{ $payment->created_at->format('d/m/Y') }}
                            <span style="color: #64748b; font-size: 10px;">{{ $payment->created_at->format('H:i') }}</span>
                        </td>
                        <td>
                            <strong>{{ $payment->paymentMethod ? $payment->paymentMethod->name : 'Pago' }}</strong>
                        </td>
                        <td style="font-size: 11px;">
                            @if($payment->terminal)
                                <span style="background: #e0f2fe; color: #0369a1; padding: 1px 4px; border-radius: 3px; font-size: 10px;">{{ $payment->terminal->name }}</span>
                            @endif
                            @if($payment->reference)
                                <span style="color: #64748b;">Ref: {{ $payment->reference }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="font-size: 11px;">
                            @if($payment->cashShift)
                                <span style="font-family: 'JetBrains Mono', monospace; font-size: 10px; color: #1d4ed8;">{{ $payment->cashShift->folio }}</span><br>
                            @endif
                            <span style="color: #475569;">{{ $payment->cashier ? $payment->cashier->name : 'Caja' }}</span>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #16a34a; font-family: 'JetBrains Mono', monospace;">
                            ${{ number_format($payment->amount, 2) }}
                        </td>
                        <td style="text-align: right; font-weight: 600; font-family: 'JetBrains Mono', monospace; color: {{ $runningBalance > 0.01 ? '#dc2626' : '#16a34a' }};">
                            ${{ number_format($runningBalance, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 14px;">Sin abonos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Firmas --}}
        <div class="signatures-grid">
            <div class="sign-col">
                <div class="sign-line"></div>
                <div style="font-weight: 700; font-size: 11px;">Cajero / Receptor</div>
                <div style="font-size: 10px; color: #64748b;">{{ auth()->user()?->name ?? 'Cajero Autorizado' }}</div>
            </div>
            <div class="sign-col">
                <div class="sign-line"></div>
                <div style="font-weight: 700; font-size: 11px;">Cliente Receptor</div>
                <div style="font-size: 10px; color: #64748b;">Firma de conformidad de saldo</div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 24px; font-size: 10px; color: #94a3b8;">
            Documento emitido el {{ now()->format('d/m/Y H:i:s') }} — Mueblería Ureña
        </div>
    </div>

    <script>
        function setMode(mode) {
            const body = document.body;
            const btnSheet = document.getElementById('btn-mode-sheet');
            const btnTicket = document.getElementById('btn-mode-ticket');

            if (mode === 'ticket') {
                body.classList.add('ticket-mode');
                btnTicket.classList.add('active');
                btnSheet.classList.remove('active');
            } else {
                body.classList.remove('ticket-mode');
                btnSheet.classList.add('active');
                btnTicket.classList.remove('active');
            }
        }
    </script>
</body>
</html>
