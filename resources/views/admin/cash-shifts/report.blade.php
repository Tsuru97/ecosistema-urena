<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Cortes de Caja - Ecosistema Ureña</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
            background-color: #f4f6f9;
            font-size: 13px;
        }
        .report-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            padding: 30px;
            margin: 20px auto;
            max-width: 1200px;
        }
        .table-custom th {
            background-color: #2b3a4a !important;
            color: #fff !important;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
        }
        .table-custom td {
            padding: 7px 10px;
            font-size: 12px;
            vertical-align: middle;
        }
        .kpi-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            background-color: #f8fafc;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 50px auto 10px auto;
        }
        @media print {
            body {
                background: #fff !important;
                font-size: 11px;
            }
            .no-print {
                display: none !important;
            }
            .report-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .table-custom th {
                background-color: #f1f5f9 !important;
                color: #000 !important;
                border: 1px solid #000 !important;
            }
            .table-custom td {
                border: 1px solid #cbd5e1 !important;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

{{-- Barra de Acciones Superior (Oculta en Impresión) --}}
<div class="container-fluid py-2 bg-dark text-white no-print mb-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <i class="ri-file-chart-line me-1"></i> <strong>Reporte Ejecutivo de Auditoría de Cajas</strong>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cash-shifts.index') }}" class="btn btn-sm btn-outline-light">
                <i class="ri-arrow-left-line me-1"></i> Volver al Listado
            </a>
            <a href="{{ route('cash-shifts.export', request()->query()) }}" class="btn btn-sm btn-success fw-bold">
                <i class="ri-file-excel-2-line me-1"></i> Descargar Excel (CSV)
            </a>
            <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="window.print()">
                <i class="ri-printer-line me-1"></i> Imprimir / Guardar PDF
            </button>
        </div>
    </div>
</div>

<div class="report-card">
    {{-- Membrete Corporativo --}}
    <div class="row align-items-center mb-4 pb-3 border-bottom">
        <div class="col-8">
            <div class="d-flex align-items-center gap-3 mb-2">
                <img src="{{ asset('build/images/urena-logo-full.png') }}" alt="Logo Empresa" style="max-height: 55px; max-width: 180px; object-fit: contain;">
                <div>
                    <h3 class="fw-bold mb-0 text-primary">ECOSISTEMA UREÑA</h3>
                    <h5 class="fw-semibold text-secondary mb-0">Reporte Consolidado de Cortes de Caja y Auditoría de Arqueos</h5>
                </div>
            </div>
            <div class="text-muted small mt-1">
                Fecha de Emisión: <strong>{{ now()->format('d/m/Y H:i:s') }}</strong> | Generado por: <strong>{{ auth()->user()->name }}</strong>
            </div>
        </div>
        <div class="col-4 text-end">
            <div class="badge bg-light text-dark border p-2 text-start">
                <div class="small text-muted">Parámetros del Reporte:</div>
                <div class="fw-bold">Periodo: {{ request('period') ?: (request('date_from') ? request('date_from') . ' al ' . request('date_to') : 'Histórico Completo') }}</div>
                <div class="small">Sucursal: {{ request('store_id') && request('store_id') !== 'all' ? \App\Models\Store::find(request('store_id'))?->name : 'Todas las sucursales' }}</div>
            </div>
        </div>
    </div>

    {{-- Resumen Financiero Ejecutivo --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kpi-box">
                <div class="text-muted small text-uppercase fw-semibold">Turnos Auditados</div>
                <h3 class="fw-bold text-dark my-1">{{ $summary['total_shifts'] }}</h3>
                <div class="small text-muted">
                    <span class="text-success">{{ $summary['closed_shifts'] }} cerrados</span> • 
                    <span class="text-warning">{{ $summary['open_shifts'] }} en curso</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-box">
                <div class="text-muted small text-uppercase fw-semibold">Total Venta Cobrada</div>
                <h3 class="fw-bold text-success my-1">${{ number_format($summary['total_sales_amount'], 2) }}</h3>
                <div class="small text-muted">{{ $summary['total_sales_count'] }} tickets emitidos</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-box">
                <div class="text-muted small text-uppercase fw-semibold">Efectivo Contado vs Esperado</div>
                <h3 class="fw-bold text-primary my-1">${{ number_format($summary['total_actual_cash'], 2) }}</h3>
                <div class="small text-muted">Esperado: ${{ number_format($summary['total_expected_cash'], 2) }}</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-box {{ $summary['net_diff'] < -0.01 ? 'border-danger' : ($summary['net_diff'] > 0.01 ? 'border-info' : 'border-success') }}">
                <div class="text-muted small text-uppercase fw-semibold">Diferencia Neta Arqueo</div>
                <h3 class="fw-bold my-1 {{ $summary['net_diff'] < -0.01 ? 'text-danger' : ($summary['net_diff'] > 0.01 ? 'text-info' : 'text-success') }}">
                    {{ $summary['net_diff'] >= 0 ? '+' : '' }}${{ number_format($summary['net_diff'], 2) }}
                </h3>
                <div class="small fw-semibold {{ $summary['diff_shifts_count'] > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $summary['diff_shifts_count'] }} turno(s) con descuadre
                </div>
            </div>
        </div>
    </div>

    {{-- Desglose Comparativo en Dos Columnas --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr><th colspan="2" class="fw-bold">Ingresos por Forma de Cobro</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Efectivo (Ventas y Apartados)</td>
                        <td class="text-end fw-semibold">${{ number_format($summary['total_sales_cash'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Efectivo (Anticipos Pedidos Especiales)</td>
                        <td class="text-end fw-semibold">${{ number_format($summary['total_special_orders'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Tarjetas (Terminales Bancarias)</td>
                        <td class="text-end fw-semibold">${{ number_format($summary['total_sales_card'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Transferencias Bancarias</td>
                        <td class="text-end fw-semibold">${{ number_format($summary['total_sales_transfer'], 2) }}</td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <td>Gran Total Recaudado</td>
                        <td class="text-end text-success">${{ number_format($summary['total_sales_amount'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="col-md-6">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr><th colspan="2" class="fw-bold">Conciliación y Flujo de Efectivo Físico</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>(+) Fondos de Caja Iniciales</td>
                        <td class="text-end fw-semibold">${{ number_format($summary['total_opening'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>(+) Cobros Totales en Efectivo</td>
                        <td class="text-end text-success fw-semibold">${{ number_format($summary['total_sales_cash'] + $summary['total_special_orders'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>(+) Entradas Manuales de Efectivo</td>
                        <td class="text-end text-info fw-semibold">${{ number_format($summary['total_cash_in'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>(-) Retiros / Sangrías Totales</td>
                        <td class="text-end text-danger fw-semibold">-${{ number_format($summary['total_cash_out'], 2) }}</td>
                    </tr>
                    @if(($summary['total_vault_cash'] ?? 0) > 0)
                    <tr class="text-muted small">
                        <td class="ps-3">↳ A Bóveda / Banco</td>
                        <td class="text-end">-${{ number_format($summary['total_vault_cash'], 2) }}</td>
                    </tr>
                    @endif
                    @if(($summary['total_expenses_cash'] ?? 0) > 0)
                    <tr class="text-muted small">
                        <td class="ps-3">↳ Gastos Operativos de Tienda</td>
                        <td class="text-end">-${{ number_format($summary['total_expenses_cash'], 2) }}</td>
                    </tr>
                    @endif
                    <tr class="table-light fw-bold">
                        <td>(=) Efectivo Teórico Esperado</td>
                        <td class="text-end text-primary">${{ number_format($summary['total_expected_cash'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabla de Detalle Individual de Turnos --}}
    <h5 class="fw-bold mb-3"><i class="ri-list-check-2 me-1"></i>Detalle Cronológico de Cortes de Caja</h5>
    <div class="table-responsive mb-4">
        <table class="table table-striped table-hover table-bordered table-custom mb-0">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Sucursal</th>
                    <th>Cajero</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th class="text-end">Fondo</th>
                    <th class="text-end">Cobrado</th>
                    <th class="text-end">Esperado</th>
                    <th class="text-end">Físico</th>
                    <th class="text-center">Diferencia</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $s)
                @php
                    $d = (float) ($s->difference_cash ?? 0);
                @endphp
                <tr>
                    <td class="fw-bold text-primary">{{ $s->folio }}</td>
                    <td>{{ $s->store?->name ?? 'N/A' }}</td>
                    <td>{{ $s->cashier?->name ?? 'N/A' }}</td>
                    <td>{{ $s->opened_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $s->closed_at ? $s->closed_at->format('d/m/Y H:i') : 'En curso' }}</td>
                    <td class="text-end">${{ number_format($s->opening_amount, 2) }}</td>
                    <td class="text-end fw-bold text-success">${{ number_format($s->total_sales_amount, 2) }}</td>
                    <td class="text-end">${{ number_format($s->expected_cash, 2) }}</td>
                    <td class="text-end fw-bold">{{ $s->isClosed() ? '$' . number_format($s->actual_cash, 2) : '—' }}</td>
                    <td class="text-center">
                        @if($s->isClosed())
                            @if(abs($d) < 0.01)
                                <span class="badge bg-success text-white">Exacto</span>
                            @elseif($d > 0)
                                <span class="badge bg-info text-dark">+${{ number_format($d, 2) }}</span>
                            @else
                                <span class="badge bg-danger text-white">-${{ number_format(abs($d), 2) }}</span>
                            @endif
                        @else
                            <span class="text-muted small">Pendiente</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $s->isOpen() ? 'bg-warning text-dark' : 'bg-secondary' }}">
                            {{ ucfirst($s->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">
                        No se encontraron turnos de caja para los criterios seleccionados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Firmas de Conformidad y Autorización --}}
    <div class="row mt-5 pt-3">
        <div class="col-4 text-center">
            <div class="signature-line"></div>
            <strong>Elaboró / Auditor</strong><br>
            <small class="text-muted">{{ auth()->user()->name }}</small>
        </div>
        <div class="col-4 text-center">
            <div class="signature-line"></div>
            <strong>Gerencia de Operaciones</strong><br>
            <small class="text-muted">Revisión y Cuadre de Bóveda</small>
        </div>
        <div class="col-4 text-center">
            <div class="signature-line"></div>
            <strong>Dirección General</strong><br>
            <small class="text-muted">Aprobación Final</small>
        </div>
    </div>
</div>

</body>
</html>
