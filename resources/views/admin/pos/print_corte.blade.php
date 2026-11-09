<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de {{ strtoupper($type === 'z' ? 'Corte Z (Cierre)' : 'Corte X (Arqueo)') }} - {{ $shift->folio }}</title>

    @php
        $isTicket = ($format ?? 'a4') === 'ticket';
        $isClosed = $shift->isClosed();
        $diff     = (float) ($shift->difference_cash ?? 0);
        $t        = $liveTotals;
        $storeLogo = $shift->store?->logo ? (str_starts_with($shift->store->logo, 'http') ? $shift->store->logo : asset('storage/' . $shift->store->logo)) : null;
        $logoUrl   = $storeLogo ?: asset('build/images/urena-logo-full.png');
    @endphp

    @if(!$isTicket)
        <!-- Estilos para Formato Hoja A4 / Carta Ejecutiva (Idéntico estilo a la vista show.blade.php) -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
        <style>
            body {
                background-color: #eaedf1;
                font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                color: #212529;
                font-size: 13px;
                margin: 0;
                padding: 0;
            }
            .sheet-container {
                background: #ffffff;
                max-width: 980px;
                margin: 25px auto 40px auto;
                padding: 30px 40px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                border: 1px solid #e5e7eb;
            }

            /* Estructura de tarjetas KPI en pantalla */
            .print-kpi-grid {
                display: flex;
                gap: 12px;
            }
            .print-kpi-item {
                flex: 1;
                min-width: 0;
            }
            .card-kpi {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background-color: #f8fafc;
                padding: 14px;
                height: 100%;
            }

            /* Estructura de dos columnas comparativas en pantalla */
            .print-compare-grid {
                display: flex;
                gap: 16px;
            }
            .print-compare-item {
                flex: 1;
                min-width: 0;
            }

            .table-custom th {
                background-color: #f1f5f9 !important;
                color: #334155 !important;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 7px 10px;
                border-bottom: 2px solid #cbd5e1 !important;
            }
            .table-custom td {
                padding: 6px 10px;
                font-size: 12px;
                vertical-align: middle;
            }

            .signature-box {
                border-top: 1px solid #333;
                width: 80%;
                margin: 40px auto 5px auto;
                text-align: center;
                font-size: 11px;
                padding-top: 6px;
            }

            /* En pantalla ocultar encabezados/pies repetitivos de impresión */
            .print-running-header,
            .print-running-footer {
                display: none;
            }

            /* ══════════════════════════════════════════════════════════════
               REGLAS ESTRICTAS DE IMPRESIÓN (@media print)
               ══════════════════════════════════════════════════════════════ */
            @page {
                size: letter portrait;
                margin: 12mm 12mm 15mm 12mm;
                @top-right {
                    content: "Folio: {{ $shift->folio }}";
                    font-size: 8.5pt;
                    font-weight: bold;
                    color: #0d6efd;
                }
                @bottom-left {
                    content: "Corte: {{ $shift->folio }}  •  Sucursal: {{ $shift->store?->name }}";
                    font-size: 8pt;
                    color: #64748b;
                }
                @bottom-right {
                    content: "Hoja " counter(page) " de " counter(pages);
                    font-size: 8pt;
                    font-weight: bold;
                    color: #1e293b;
                }
            }

            @media print {
                body {
                    background-color: #fff !important;
                    font-size: 11px;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .no-print {
                    display: none !important;
                }
                .sheet-container {
                    box-shadow: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    max-width: 100% !important;
                    border: none !important;
                }

                /* Encabezado y pie de página en cada hoja impresa */
                .print-running-header {
                    display: block !important;
                    margin-bottom: 8px;
                }
                .print-running-footer {
                    display: block !important;
                    margin-top: 8px;
                }

                /* Envolver el documento en tabla para repetir headers/footers por hoja */
                .print-document-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .print-document-table > thead {
                    display: table-header-group !important;
                }
                .print-document-table > tfoot {
                    display: table-footer-group !important;
                }
                .print-document-table > tbody > tr > td {
                    padding: 0;
                    border: none;
                }

                /* SOLUCIÓN AL DESFASE Y CORTE DE TARJETAS:
                   En print reemplazamos flexbox por table-layout para que
                   las tarjetas se mantengan en una sola fila sin cortarse ni desfasarse */
                .print-kpi-grid {
                    display: table !important;
                    width: 100% !important;
                    table-layout: fixed !important;
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }
                .print-kpi-item {
                    display: table-cell !important;
                    width: 25% !important;
                    vertical-align: top !important;
                    padding: 0 4px !important;
                }
                .card-kpi {
                    border: 1px solid #cbd5e1 !important;
                    background-color: #f8fafc !important;
                    padding: 10px !important;
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }

                .print-compare-grid {
                    display: table !important;
                    width: 100% !important;
                    table-layout: fixed !important;
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }
                .print-compare-item {
                    display: table-cell !important;
                    width: 50% !important;
                    vertical-align: top !important;
                    padding: 0 6px !important;
                }
                .print-compare-item .card {
                    border: 1px solid #cbd5e1 !important;
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }

                /* Tablas en impresión: repetir thead y evitar filas cortadas a la mitad */
                .table-responsive {
                    overflow: visible !important;
                    display: block !important;
                }
                .table-custom {
                    width: 100% !important;
                }
                .table-custom thead {
                    display: table-header-group !important;
                }
                .table-custom tr {
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }

                /* Bloques que nunca deben cortarse por la mitad */
                .print-block-avoid {
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                }

                .badge {
                    border: 1px solid #94a3b8 !important;
                }
            }
        </style>
    @else
        <!-- Estilos para Formato Ticket Térmico 80mm -->
        <style>
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                font-family: 'Courier New', Courier, monospace, sans-serif;
                font-size: 12px;
                color: #000;
                background: #fff;
                margin: 0;
                padding: 10px;
                width: 78mm;
            }
            .text-center { text-align: center; }
            .text-end { text-align: right; }
            .text-start { text-align: left; }
            .fw-bold { font-weight: bold; }
            .divider { border-top: 1px dashed #000; margin: 8px 0; }
            .double-divider { border-top: 2px solid #000; margin: 8px 0; }
            table { width: 100%; border-collapse: collapse; font-size: 11px; }
            td { padding: 2px 0; }
            .badge {
                display: inline-block;
                padding: 2px 6px;
                font-size: 11px;
                border: 1px solid #000;
            }
            .signature-line {
                margin-top: 35px;
                border-top: 1px solid #000;
                text-align: center;
                font-size: 10px;
                padding-top: 4px;
            }
            @media print {
                .no-print { display: none !important; }
                body { padding: 0; }
            }
        </style>
    @endif
</head>
<body>

    {{-- Barra de Control Superior (Oculta en Impresión) --}}
    <div class="no-print bg-dark text-white py-2 px-3 mb-3 border-bottom shadow-sm">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="ri-file-chart-line fs-5 text-warning"></i>
                <span class="fw-bold">Comprobante de {{ $type === 'z' ? 'Corte Z (Cierre)' : 'Corte X (Arqueo)' }}</span>
                <span class="badge bg-secondary ms-1">{{ $shift->folio }}</span>
            </div>

            {{-- Selector de Formato (Hoja A4 vs Ticket Térmico) --}}
            <div class="d-flex align-items-center gap-2">
                <span class="text-white-50 small me-1">Formato:</span>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a4', 'autoprint' => null]) }}" class="btn btn-sm {{ !$isTicket ? 'btn-light fw-bold text-dark' : 'btn-outline-light' }}">
                    <i class="ri-file-paper-2-line me-1"></i> 📄 Hoja A4 Ejecutiva
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'ticket', 'autoprint' => null]) }}" class="btn btn-sm {{ $isTicket ? 'btn-light fw-bold text-dark' : 'btn-outline-light' }}">
                    <i class="ri-printer-line me-1"></i> 🧾 Ticket Térmico (80mm)
                </a>

                <div class="vr bg-white-50 mx-2 d-none d-md-block" style="height: 24px;"></div>

                <button onclick="window.print()" class="btn btn-sm btn-success fw-bold">
                    <i class="ri-printer-fill me-1"></i> Imprimir Documento
                </button>
                <button onclick="window.close()" class="btn btn-sm btn-secondary">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

    @if(!$isTicket)
        {{-- ══════════════════════════════════════════════════════════════
             FORMATO 1: HOJA A4 / CARTA EJECUTIVA (ESTILO DE LA VISTA POS)
             ══════════════════════════════════════════════════════════════ --}}
        <div class="sheet-container">

            {{-- Estructura de tabla para control de saltos y cabecera en cada hoja --}}
            <table class="print-document-table">
                <thead>
                    <tr>
                        <td>
                            {{-- Cabecera que se repite en cada página impresa --}}
                            <div class="print-running-header">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $logoUrl }}" style="height: 22px; max-width: 90px; object-fit: contain;">
                                        <strong class="text-primary" style="font-size: 11px;">ECOSISTEMA UREÑA</strong>
                                        <span class="text-muted" style="font-size: 10px;">— Comprobante de {{ $type === 'z' ? 'Corte Z' : 'Corte X' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary fs-11">Folio: {{ $shift->folio }}</span>
                                        <span class="text-muted" style="font-size: 10px;">{{ $shift->store?->name }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </thead>

                <tfoot>
                    <tr>
                        <td>
                            {{-- Pie que se repite en cada página impresa --}}
                            <div class="print-running-footer">
                                <div class="d-flex justify-content-between align-items-center border-top pt-1 mt-2 text-muted" style="font-size: 9.5px;">
                                    <span>Folio: <strong class="text-dark">{{ $shift->folio }}</strong> • Sucursal: {{ $shift->store?->name }} • Cajero: {{ $shift->cashier?->name }}</span>
                                    <span class="fw-bold text-dark">Documento Oficial de Arqueo</span>
                                    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tfoot>

                <tbody>
                    <tr>
                        <td>
                            {{-- ── BLOQUE 1: MEMBRETE CORPORATIVO PRINCIPAL ── --}}
                            <div class="print-block-avoid mb-4 pb-3 border-bottom">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <img src="{{ $logoUrl }}" alt="Logo Empresa" style="max-height: 56px; max-width: 170px; object-fit: contain;">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h3 class="fw-bold mb-0 text-primary" style="letter-spacing: -0.5px;">ECOSISTEMA UREÑA</h3>
                                                    <span class="badge {{ $type === 'z' ? 'bg-danger' : 'bg-info' }} fs-12 px-2 py-1">
                                                        {{ $type === 'z' ? 'CORTE Z — CIERRE DEFINITIVO' : 'CORTE X — ARQUEO PARCIAL' }}
                                                    </span>
                                                </div>
                                                <h6 class="fw-semibold text-secondary mb-0">Comprobante Oficial de Arqueo y Turno de Caja</h6>
                                            </div>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="ri-store-2-line me-1"></i> Sucursal: <strong class="text-dark">{{ $shift->store?->name ?? 'Sucursal Principal' }}</strong>
                                            <span class="mx-2">•</span>
                                            <i class="ri-map-pin-line me-1"></i> {{ $shift->store?->address ?? 'Dirección registrada' }}
                                            <span class="mx-2">•</span>
                                            <i class="ri-phone-line me-1"></i> Tel: {{ $shift->store?->phone ?? 'S/N' }}
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <i class="ri-user-3-line me-1"></i> Cajero Responsable: <strong class="text-dark">{{ $shift->cashier?->name ?? 'N/A' }}</strong>
                                            @if($shift->closedBy)
                                                <span class="mx-2">•</span>
                                                <i class="ri-shield-user-line me-1"></i> Cerrado por: <strong class="text-dark">{{ $shift->closedBy->name }}</strong>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-4 text-end">
                                        <div class="p-2 bg-light rounded border text-start">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted small">Folio:</span>
                                                <strong class="text-primary fs-14">{{ $shift->folio }}</strong>
                                            </div>
                                            <div class="small mb-1">
                                                <span class="text-muted">Apertura:</span>
                                                <strong>{{ $shift->opened_at->format('d/m/Y H:i') }} hrs</strong>
                                            </div>
                                            <div class="small">
                                                <span class="text-muted">Cierre:</span>
                                                <strong>{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') . ' hrs' : 'En operación (Arqueo)' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ── BLOQUE 2: 4 TARJETAS DE BALANCE Y ARQUEO (NO SE CORTAN) ── --}}
                            <div class="print-kpi-grid mb-4">
                                <div class="print-kpi-item">
                                    <div class="card-kpi">
                                        <span class="text-muted small d-block">Fondo Inicial</span>
                                        <h4 class="fw-bold text-dark mb-1 mt-1">${{ number_format($t['opening_amount'], 2) }}</h4>
                                        <small class="text-muted"><i class="ri-time-line me-1"></i>{{ $shift->opened_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>

                                <div class="print-kpi-item">
                                    <div class="card-kpi">
                                        <span class="text-muted small d-block">Total Venta Cobrada</span>
                                        <h4 class="fw-bold text-success mb-1 mt-1">${{ number_format($t['total_sales_amount'], 2) }}</h4>
                                        <small class="text-muted">{{ $t['sales_count'] }} ticket(s)</small>
                                    </div>
                                </div>

                                <div class="print-kpi-item">
                                    <div class="card-kpi">
                                        <span class="text-muted small d-block">Efectivo Esperado</span>
                                        <h4 class="fw-bold text-primary mb-1 mt-1">${{ number_format($t['expected_cash'], 2) }}</h4>
                                        <small class="text-muted">Fondo + Efectivo - Salidas</small>
                                    </div>
                                </div>

                                <div class="print-kpi-item">
                                    <div class="card-kpi {{ $isClosed ? (abs($diff) < 0.01 ? 'border-success' : ($diff > 0 ? 'border-info' : 'border-danger')) : '' }}">
                                        <span class="text-muted small d-block">Arqueo Físico (Resultado)</span>
                                        @if($isClosed)
                                            <div class="d-flex align-items-baseline gap-2 mt-1 mb-1">
                                                <h4 class="fw-bold mb-0 text-dark">${{ number_format($shift->actual_cash, 2) }}</h4>
                                                @if(abs($diff) < 0.01)
                                                    <span class="badge bg-success-subtle text-success">Exacto</span>
                                                @elseif($diff > 0)
                                                    <span class="badge bg-info-subtle text-info">+${{ number_format($diff, 2) }} Sobrante</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">-${{ number_format(abs($diff), 2) }} Faltante</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">Cerrado: {{ $shift->closed_at?->format('d/m/Y H:i') }}</small>
                                        @else
                                            <h4 class="fw-bold text-warning mb-1 mt-1">En Operación</h4>
                                            <small class="text-muted">Arqueo en vivo</small>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- ── BLOQUE 3: PANELES COMPARATIVOS EN PARALELO (NO SE CORTAN NI DESFASAN) ── --}}
                            <div class="print-compare-grid mb-4">
                                {{-- Columna 1: Desglose por Método de Pago --}}
                                <div class="print-compare-item">
                                    <div class="card border border-light-subtle shadow-none h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="card-title mb-0 fs-13"><i class="ri-bank-card-line me-1 text-primary"></i>Desglose de Cobros por Método</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Efectivo (Ventas y Apartados):</span>
                                                <strong class="text-dark">${{ number_format($t['total_sales_cash'], 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Efectivo (Pedidos Especiales):</span>
                                                <strong class="text-dark">${{ number_format($t['total_special_orders_cash'], 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Tarjetas (Terminales Bancarias):</span>
                                                <strong class="text-dark">${{ number_format($t['total_sales_card'], 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Transferencias Bancarias:</span>
                                                <strong class="text-dark">${{ number_format($t['total_sales_transfer'], 2) }}</strong>
                                            </div>
                                            @if(($t['total_sales_other'] ?? 0) > 0)
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Otros Métodos:</span>
                                                <strong class="text-dark">${{ number_format($t['total_sales_other'], 2) }}</strong>
                                            </div>
                                            @endif
                                            <div class="d-flex justify-content-between pt-2 fs-14 fw-bold text-success">
                                                <span>Total Cobrado:</span>
                                                <span>${{ number_format($t['total_sales_amount'], 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Columna 2: Conciliación de Efectivo en Cajón --}}
                                <div class="print-compare-item">
                                    <div class="card border border-light-subtle shadow-none h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="card-title mb-0 fs-13"><i class="ri-safe-2-line me-1 text-primary"></i>Conciliación de Efectivo en Cajón</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">(+) Fondo de Caja Inicial:</span>
                                                <strong class="text-dark">${{ number_format($t['opening_amount'], 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">(+) Ventas y Anticipos en Efectivo:</span>
                                                <strong class="text-success">${{ number_format($t['total_cash_sales_all'], 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">(+) Entradas Manuales de Efectivo:</span>
                                                <strong class="text-info">${{ number_format($t['total_cash_in'], 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">(-) Retiros / Sangrías Totales:</span>
                                                <strong class="text-danger">-${{ number_format($t['total_cash_out'], 2) }}</strong>
                                            </div>
                                            @if(($t['total_vault_cash'] ?? 0) > 0)
                                            <div class="d-flex justify-content-between py-1 ps-3 border-bottom small text-muted">
                                                <span>↳ A Bóveda / Banco:</span>
                                                <strong>-${{ number_format($t['total_vault_cash'], 2) }}</strong>
                                            </div>
                                            @endif
                                            @if(($t['total_expenses_cash'] ?? 0) > 0)
                                            <div class="d-flex justify-content-between py-1 ps-3 border-bottom small text-muted">
                                                <span>↳ Gastos Operativos de Tienda:</span>
                                                <strong>-${{ number_format($t['total_expenses_cash'], 2) }}</strong>
                                            </div>
                                            @endif
                                            <div class="d-flex justify-content-between pt-2 fs-14 fw-bold text-primary">
                                                <span>(=) Efectivo Teórico Esperado:</span>
                                                <span>${{ number_format($t['expected_cash'], 2) }}</span>
                                            </div>
                                            @if($isClosed || !is_null($shift->actual_cash))
                                            <div class="d-flex justify-content-between pt-1 border-top mt-1 fs-14 fw-bold text-dark">
                                                <span>Efectivo Físico Contado:</span>
                                                <span>${{ number_format($shift->actual_cash ?? 0, 2) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between pt-1 fs-13 fw-bold {{ abs($diff) < 0.01 ? 'text-success' : ($diff > 0 ? 'text-info' : 'text-danger') }}">
                                                <span>Diferencia de Arqueo:</span>
                                                <span>{{ $diff >= 0 ? '+' : '' }}${{ number_format($diff, 2) }} ({{ abs($diff) < 0.01 ? 'Exacto' : ($diff > 0 ? 'Sobrante' : 'Faltante') }})</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ── BLOQUE 4: TABLA DE MOVIMIENTOS MANUALES (LÍNEA TEMPORAL DEL TURNO) ── --}}
                            @if($shift->movements->count() > 0)
                            <div class="print-block-avoid mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0 fs-13">
                                        <i class="ri-exchange-dollar-line me-1 text-primary"></i>Línea Temporal de Movimientos de Efectivo ({{ $shift->movements->count() }})
                                    </h6>
                                    <span class="text-muted small">Orden cronológico • Folio: <strong>{{ $shift->folio }}</strong></span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped table-hover table-bordered table-custom mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;" class="text-center">#</th>
                                                <th style="width: 13%;">Hora / Fecha</th>
                                                <th style="width: 22%;">Tipo / Destino</th>
                                                <th style="width: 15%;" class="text-end">Monto</th>
                                                <th style="width: 27%;">Concepto / Motivo</th>
                                                <th style="width: 18%;">Registrado por</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($shift->movements as $index => $m)
                                            <tr>
                                                <td class="text-center text-muted fw-bold">#{{ $index + 1 }}</td>
                                                <td>
                                                    <span class="fw-bold text-dark">{{ $m->created_at->format('H:i:s') }}</span>
                                                    <small class="text-muted d-block" style="font-size: 9.5px;">{{ $m->created_at->format('d/m/Y') }}</small>
                                                </td>
                                                <td>
                                                    @if($m->isEntry())
                                                        <span class="badge bg-success-subtle text-success fw-bold"><i class="ri-add-circle-line me-1"></i>(+) Entrada</span>
                                                    @elseif($m->category === 'gasto_operativo')
                                                        <span class="badge bg-warning-subtle text-warning fw-bold"><i class="ri-file-list-3-line me-1"></i>(-) Gasto Tienda</span>
                                                        @if($m->expense_id)
                                                            <span class="text-muted small ms-1">(#{{ $m->expense_id }})</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger fw-bold"><i class="ri-bank-line me-1"></i>(-) Bóveda / Banco</span>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-bold {{ $m->isEntry() ? 'text-success' : 'text-danger' }}">
                                                    {{ $m->isEntry() ? '+' : '-' }}${{ number_format($m->amount, 2) }}
                                                </td>
                                                <td class="fw-semibold">
                                                    {{ $m->concept }}
                                                    @if($m->notes)
                                                        <div class="text-muted small" style="font-size: 10px;">Nota: {{ $m->notes }}</div>
                                                    @endif
                                                </td>
                                                <td>{{ $m->user?->name ?? 'N/A' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            {{-- ── BLOQUE 5: TABLA DE TICKETS DE VENTA (TABLA CONTINUACIÓN CON REPETICIÓN DE HEADERS) ── --}}
                            @if($shift->sales->count() > 0)
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0 fs-13">
                                        <i class="ri-shopping-cart-2-line me-1 text-primary"></i>Tickets de Venta Emitidos en el Turno ({{ $shift->sales->count() }})
                                    </h6>
                                    <span class="text-muted small">Línea temporal de ventas • Folio: <strong>{{ $shift->folio }}</strong></span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped table-hover table-bordered table-custom mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;" class="text-center">#</th>
                                                <th style="width: 16%;">Folio Ticket</th>
                                                <th style="width: 12%;">Hora</th>
                                                <th style="width: 23%;">Cliente</th>
                                                <th style="width: 15%;" class="text-end">Total</th>
                                                <th style="width: 19%;">Forma(s) de Pago</th>
                                                <th style="width: 10%;" class="text-center">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($shift->sales as $index => $s)
                                            <tr>
                                                <td class="text-center text-muted fw-bold">#{{ $index + 1 }}</td>
                                                <td class="fw-bold text-primary">{{ $s->ticket_folio }}</td>
                                                <td>
                                                    <span class="fw-bold text-dark">{{ $s->created_at->format('H:i:s') }}</span>
                                                </td>
                                                <td>{{ $s->customer ? $s->customer->name . ' ' . $s->customer->last_name : 'Público en General' }}</td>
                                                <td class="text-end fw-bold">${{ number_format($s->total, 2) }}</td>
                                                <td>
                                                    @foreach($s->payments as $p)
                                                        <span class="badge bg-light text-dark border me-1">
                                                            {{ $p->paymentMethod?->name ?? 'Pago' }}: ${{ number_format($p->amount, 2) }}
                                                        </span>
                                                    @endforeach
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge {{ $s->status === 'pagada' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                                        {{ ucfirst($s->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            {{-- ── BLOQUE 6: NOTAS DE APERTURA Y CIERRE ── --}}
                            @if($shift->opening_notes || $shift->closing_notes)
                            <div class="print-block-avoid mb-4">
                                <div class="row g-2">
                                    @if($shift->opening_notes)
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded border h-100">
                                            <strong class="text-dark d-block mb-1" style="font-size: 11px;"><i class="ri-key-2-line me-1 text-success"></i>Notas de Apertura de Caja:</strong>
                                            <p class="text-muted mb-0 small">{{ $shift->opening_notes }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @if($shift->closing_notes)
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded border h-100">
                                            <strong class="text-dark d-block mb-1" style="font-size: 11px;"><i class="ri-lock-line me-1 text-danger"></i>Notas de Cierre y Arqueo (Corte Z):</strong>
                                            <p class="text-muted mb-0 small">{{ $shift->closing_notes }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            {{-- ── BLOQUE 7: FIRMAS FORMALES DE CONFORMIDAD ── --}}
                            <div class="print-block-avoid mt-4 pt-2">
                                <div class="row">
                                    <div class="col-6 text-center">
                                        <div class="signature-box">
                                            <strong>{{ $shift->cashier?->name ?? 'Cajero Responsable' }}</strong><br>
                                            <span class="text-muted">Firma del Cajero / Responsable de Turno</span>
                                        </div>
                                    </div>
                                    <div class="col-6 text-center">
                                        <div class="signature-box">
                                            <strong>{{ $shift->closedBy?->name ?? 'Gerente / Auditor' }}</strong><br>
                                            <span class="text-muted">Firma de Gerencia / Auditoría de Sucursal</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-3 text-muted small" style="font-size: 10px;">
                                    Documento Oficial de Control y Arqueo de Caja • Folio: <strong>{{ $shift->folio }}</strong> • Impreso el {{ now()->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    @else
        {{-- ══════════════════════════════════════════════════════════════
             FORMATO 2: TICKET TÉRMICO (MINIPRINTER 80MM)
             ══════════════════════════════════════════════════════════════ --}}
        <div class="text-center">
            <img src="{{ $logoUrl }}" alt="Logo Empresa" style="max-height: 46px; max-width: 140px; margin-bottom: 5px; object-fit: contain;">
            <h3 style="margin: 0; font-size: 15px; text-transform: uppercase;">{{ $shift->store->name ?? 'ECOSISTEMA UREÑA' }}</h3>
            <p style="margin: 2px 0; font-size: 10px;">{{ $shift->store->address ?? 'Sucursal Principal' }}</p>
            <p style="margin: 2px 0; font-size: 10px;">Tel: {{ $shift->store->phone ?? 'S/N' }}</p>
            
            <div class="divider"></div>
            
            <div class="badge fw-bold">
                {{ $type === 'z' ? 'CORTE Z — CIERRE DEFINITIVO' : 'CORTE X — ARQUEO PARCIAL' }}
            </div>
            
            <p style="margin: 5px 0 0 0; font-size: 13px; font-weight: bold;">FOLIO: {{ $shift->folio }}</p>
        </div>

        <div class="divider"></div>

        <table>
            <tr>
                <td class="fw-bold">Cajero:</td>
                <td class="text-end">{{ $shift->cashier->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="fw-bold">Apertura:</td>
                <td class="text-end">{{ $shift->opened_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if($shift->closed_at)
            <tr>
                <td class="fw-bold">Cierre:</td>
                <td class="text-end">{{ $shift->closed_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if($shift->closedBy)
            <tr>
                <td class="fw-bold">Cerrado Por:</td>
                <td class="text-end">{{ $shift->closedBy->name }}</td>
            </tr>
            @endif
            @else
            <tr>
                <td class="fw-bold">Fecha Arqueo:</td>
                <td class="text-end">{{ now()->format('d/m/Y H:i') }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>
        <div class="fw-bold" style="font-size: 11px; margin-bottom: 4px;">DESGLOSE POR FORMA DE COBRO:</div>

        <table>
            <tr>
                <td>Efectivo (Ventas):</td>
                <td class="text-end">${{ number_format($t['total_sales_cash'], 2) }}</td>
            </tr>
            @if($t['total_special_orders_cash'] > 0)
            <tr>
                <td>Efectivo (Pedidos Especiales):</td>
                <td class="text-end">${{ number_format($t['total_special_orders_cash'], 2) }}</td>
            </tr>
            @endif
            <tr>
                <td>Tarjetas (Terminales):</td>
                <td class="text-end">${{ number_format($t['total_sales_card'], 2) }}</td>
            </tr>
            <tr>
                <td>Transferencias:</td>
                <td class="text-end">${{ number_format($t['total_sales_transfer'], 2) }}</td>
            </tr>
            @if($t['total_sales_other'] > 0)
            <tr>
                <td>Otros Métodos:</td>
                <td class="text-end">${{ number_format($t['total_sales_other'], 2) }}</td>
            </tr>
            @endif
            <tr class="fw-bold" style="border-top: 1px dotted #000;">
                <td>TOTAL VENTAS:</td>
                <td class="text-end">${{ number_format($t['total_sales_amount'], 2) }}</td>
            </tr>
            <tr>
                <td>Tickets emitidos:</td>
                <td class="text-end">{{ $t['sales_count'] }}</td>
            </tr>
        </table>

        <div class="divider"></div>
        <div class="fw-bold" style="font-size: 11px; margin-bottom: 4px;">CONCILIACIÓN DE EFECTIVO EN CAJÓN:</div>

        <table>
            <tr>
                <td>(+) Fondo Inicial:</td>
                <td class="text-end">${{ number_format($t['opening_amount'], 2) }}</td>
            </tr>
            <tr>
                <td>(+) Cobros en Efectivo:</td>
                <td class="text-end">${{ number_format($t['total_cash_sales_all'], 2) }}</td>
            </tr>
            <tr>
                <td>(+) Entradas Manuales:</td>
                <td class="text-end">${{ number_format($t['total_cash_in'], 2) }}</td>
            </tr>
            <tr>
                <td>(-) Retiros / Sangrías Totales:</td>
                <td class="text-end">-${{ number_format($t['total_cash_out'], 2) }}</td>
            </tr>
            @if(($t['total_vault_cash'] ?? 0) > 0)
            <tr style="font-size: 10px; color: #333;">
                <td style="padding-left: 8px;">↳ A Bóveda / Banco:</td>
                <td class="text-end">-${{ number_format($t['total_vault_cash'], 2) }}</td>
            </tr>
            @endif
            @if(($t['total_expenses_cash'] ?? 0) > 0)
            <tr style="font-size: 10px; color: #333;">
                <td style="padding-left: 8px;">↳ Gastos de Tienda:</td>
                <td class="text-end">-${{ number_format($t['total_expenses_cash'], 2) }}</td>
            </tr>
            @endif
            <tr class="fw-bold" style="border-top: 1px solid #000;">
                <td>(=) EFECTIVO ESPERADO:</td>
                <td class="text-end">${{ number_format($t['expected_cash'], 2) }}</td>
            </tr>

            @if($type === 'z' || !is_null($shift->actual_cash))
            <tr class="fw-bold">
                <td>EFECTIVO FÍSICO CONTADO:</td>
                <td class="text-end">${{ number_format($shift->actual_cash ?? 0, 2) }}</td>
            </tr>
            <tr class="fw-bold" style="font-size: 12px; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                <td>DIFERENCIA:</td>
                <td class="text-end">
                    @if($diff > 0)
                        +${{ number_format($diff, 2) }} (Sobrante)
                    @elseif($diff < 0)
                        -${{ number_format(abs($diff), 2) }} (Faltante)
                    @else
                        $0.00 (Cuadrado Exacto)
                    @endif
                </td>
            </tr>
            @endif
        </table>

        @if($shift->movements->count() > 0)
        <div class="divider"></div>
        <div class="fw-bold" style="font-size: 10px; margin-bottom: 2px;">MOVIMIENTOS (LÍNEA TEMPORAL):</div>
        <table style="font-size: 10px;">
            @foreach($shift->movements as $m)
            <tr>
                <td>
                    <span class="fw-bold">[{{ $m->created_at->format('H:i') }}]</span>
                    {{ strtoupper($m->type) }}
                    @if($m->category === 'traslado_boveda') [BÓVEDA]
                    @elseif($m->category === 'gasto_operativo') [GASTO]
                    @endif:
                    {{ $m->concept }}
                </td>
                <td class="text-end" style="white-space: nowrap; font-weight: bold;">{{ $m->type === 'entrada' ? '+' : '-' }}${{ number_format($m->amount, 2) }}</td>
            </tr>
            @endforeach
        </table>
        @endif

        @if($shift->closing_notes)
        <div class="divider"></div>
        <div style="font-size: 10px;">
            <span class="fw-bold">Notas de Cierre:</span><br>
            {{ $shift->closing_notes }}
        </div>
        @endif

        <div class="double-divider"></div>

        <div style="margin-top: 25px;">
            <div class="signature-line">
                {{ $shift->cashier->name ?? 'Cajero' }}<br>
                Firma del Cajero
            </div>

            <div class="signature-line" style="margin-top: 30px;">
                Firma de Gerencia / Auditoría
            </div>
        </div>

        <div class="text-center" style="margin-top: 15px; font-size: 9px; color: #555;">
            Impreso el {{ now()->format('d/m/Y H:i:s') }}<br>
            Ecosistema Ureña POS v2.0
        </div>
    @endif

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            if (window.location.search.includes('autoprint=1')) {
                window.print();
            }
        });
    </script>
</body>
</html>
