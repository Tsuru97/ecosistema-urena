<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['domain']['short_name'] }} - Reporte Ejecutivo | Ecosistema Ureña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style id="page-orientation-style">
        @page {
            size: A4 landscape;
            margin: 12mm;
        }
    </style>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
            font-size: 12px;
        }
        .report-sheet {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            padding: 28px 32px;
            margin: 20px auto;
            max-width: 1360px;
        }
        .report-sheet.portrait-mode {
            max-width: 900px;
        }
        .kpi-card-print {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 14px;
            background-color: #f8fafc;
            height: 100%;
        }
        .table-report th {
            background-color: #1e293b !important;
            color: #ffffff !important;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 10px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .table-report td {
            padding: 6px 10px;
            font-size: 11.5px;
            vertical-align: middle;
            border-color: #e2e8f0;
        }
        .table-report tfoot td {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            font-weight: 700;
            font-size: 12px;
            border-top: 2px solid #334155 !important;
            border-bottom: 2px solid #334155 !important;
            padding: 8px 10px;
        }
        .signature-line {
            border-top: 1px solid #334155;
            width: 75%;
            margin: 48px auto 8px auto;
        }
        @media print {
            body {
                background: #ffffff !important;
                font-size: 10.5px;
            }
            .no-print {
                display: none !important;
            }
            .report-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .table-report th {
                background-color: #e2e8f0 !important;
                color: #0f172a !important;
                border: 1px solid #475569 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .table-report td {
                border: 1px solid #cbd5e1 !important;
            }
            .table-report tfoot td {
                background-color: #e2e8f0 !important;
                border: 1px solid #334155 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

{{-- Barra Superior de Acciones (No se imprime) --}}
<div class="container-fluid py-2 bg-dark text-white no-print sticky-top shadow-sm">
    <div class="container-fluid px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-file-pdf-2-line fs-18 text-danger"></i>
            <strong>Vista Ejecutiva PDF / Impresión Corporativa:</strong>
            <span class="badge bg-secondary">{{ $report['domain']['name'] }}</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('reports.index', request()->except(['format', 'autoprint'])) }}" class="btn btn-sm btn-outline-light">
                <i class="ri-arrow-left-line me-1"></i> Volver al Constructor BI
            </a>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-info active" id="btn-landscape" onclick="setOrientation('landscape')">
                    <i class="ri-artboard-line me-1"></i> Horizontal
                </button>
                <button type="button" class="btn btn-outline-info" id="btn-portrait" onclick="setOrientation('portrait')">
                    <i class="ri-file-text-line me-1"></i> Vertical
                </button>
            </div>
            @can('exportar-reportes')
            <a href="{{ route('reports.export', array_merge(request()->except(['format', 'autoprint']), ['format' => 'excel'])) }}" class="btn btn-sm btn-success fw-semibold">
                <i class="ri-file-excel-2-line me-1"></i> Descargar Excel (.xls)
            </a>
            <a href="{{ route('reports.export', array_merge(request()->except(['format', 'autoprint']), ['format' => 'csv'])) }}" class="btn btn-sm btn-info fw-semibold text-white">
                <i class="ri-file-list-3-line me-1"></i> Descargar CSV
            </a>
            @endcan
            <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="window.print()">
                <i class="ri-printer-line me-1"></i> Imprimir / Guardar como PDF
            </button>
        </div>
    </div>
</div>

<div class="report-sheet" id="report-sheet-container">
    {{-- Membrete Corporativo --}}
    <div class="row align-items-center mb-4 pb-3 border-bottom border-2">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3 mb-2">
                <img src="{{ asset('build/images/urena-logo-full.png') }}" alt="Ecosistema Ureña" style="max-height: 56px; max-width: 185px; object-fit: contain;">
                <div>
                    <h4 class="fw-bold mb-0 text-uppercase" style="color: #1e293b; letter-spacing: 0.5px;">ECOSISTEMA UREÑA</h4>
                    <h6 class="fw-semibold text-secondary mb-0">{{ $report['domain']['name'] }}</h6>
                </div>
            </div>
            <p class="text-muted small mb-0">{{ $report['domain']['description'] }}</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="d-inline-block text-start bg-light border rounded p-2 px-3">
                <div class="small"><span class="text-muted">Período Analizado:</span> <strong>{{ $report['filters']['date_label'] }}</strong></div>
                <div class="small"><span class="text-muted">Alcance Sucursal:</span> <strong>{{ $report['filters']['store_label'] }}</strong></div>
                @if(($report['filters']['group_by'] ?? 'none') !== 'none')
                    <div class="small"><span class="text-muted">Agrupación:</span> <strong>{{ $report['domain']['group_by'][$report['filters']['group_by']] ?? 'Agrupado' }}</strong></div>
                @endif
                <div class="small"><span class="text-muted">Fecha de Emisión:</span> <strong>{{ $report['generated_at'] }}</strong></div>
                <div class="small"><span class="text-muted">Generado por:</span> <strong>{{ $report['generated_by'] }}</strong></div>
            </div>
        </div>
    </div>

    {{-- Resumen Ejecutivo de KPIs --}}
    @if(!empty($report['kpis']))
    <div class="row g-3 mb-4">
        @foreach($report['kpis'] as $kpi)
        <div class="col-6 col-md-3">
            <div class="kpi-card-print">
                <div class="text-muted small text-uppercase fw-semibold">{{ $kpi['label'] }}</div>
                <h4 class="fw-bold my-1 text-{{ $kpi['color'] ?? 'dark' }}">{{ $kpi['value'] }}</h4>
                <div class="small text-secondary">{{ $kpi['sublabel'] ?? '' }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tabla Principal de Datos con Pie de Tabla --}}
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-report mb-0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 42px;">#</th>
                    @foreach($report['columns'] as $colKey => $colDef)
                        @php
                            $isNumeric = in_array($colDef['type'] ?? 'text', ['currency', 'integer', 'percent'], true);
                        @endphp
                        <th class="{{ $isNumeric ? 'text-end' : '' }}">{{ $colDef['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $idx => $row)
                    <tr class="{{ !empty($row['_is_cancelled']) ? 'table-danger' : '' }}">
                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                        @foreach($report['columns'] as $colKey => $colDef)
                            @php
                                $val  = $row[$colKey] ?? '';
                                $type = $colDef['type'] ?? 'text';
                            @endphp
                            @if($type === 'currency')
                                <td class="text-end fw-semibold {{ (float)$val < 0 ? 'text-danger' : '' }}">
                                    ${{ number_format((float) $val, 2) }}
                                </td>
                            @elseif($type === 'integer')
                                <td class="text-end">{{ number_format((int) $val) }}</td>
                            @elseif($type === 'percent')
                                <td class="text-end">{{ number_format((float) $val, 1) }}%</td>
                            @else
                                <td>{{ $val }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($report['columns']) + 1 }}" class="text-center py-4 text-muted">
                            No se encontraron registros para los criterios seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($report['rows']) > 0)
            <tfoot>
                <tr>
                    <td class="text-center">Σ</td>
                    @php $firstCol = true; @endphp
                    @foreach($report['columns'] as $colKey => $colDef)
                        @if($firstCol)
                            <td>SUMATORIA TOTAL ({{ $report['totals']['_count'] ?? count($report['rows']) }} registros)</td>
                            @php $firstCol = false; @endphp
                        @elseif(isset($report['totals'][$colKey]))
                            @if(($colDef['type'] ?? 'text') === 'integer')
                                <td class="text-end">{{ number_format((int) $report['totals'][$colKey]) }}</td>
                            @else
                                <td class="text-end">${{ number_format((float) $report['totals'][$colKey], 2) }}</td>
                            @endif
                        @else
                            <td></td>
                        @endif
                    @endforeach
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- Firmas de Auditoría y Validación --}}
    <div class="row mt-5 pt-3 text-center">
        <div class="col-4">
            <div class="signature-line"></div>
            <div class="fw-bold small">{{ $report['generated_by'] }}</div>
            <div class="text-muted small">Elaboró / Emitió Reporte</div>
        </div>
        <div class="col-4">
            <div class="signature-line"></div>
            <div class="fw-bold small">Gerencia de Sucursal / Operaciones</div>
            <div class="text-muted small">Revisión y Validación Operativa</div>
        </div>
        <div class="col-4">
            <div class="signature-line"></div>
            <div class="fw-bold small">Dirección General / Contraloría</div>
            <div class="text-muted small">Autorización Financiera</div>
        </div>
    </div>

    <div class="text-center text-muted small mt-4 pt-2 border-top">
        Documento confidencial generado por el Motor de Inteligencia de Negocios de <strong>Ecosistema Ureña</strong> • Folio de Auditoría: <code>RPT-{{ strtoupper($report['domain']['key']) }}-{{ now()->format('YmdHis') }}</code>
    </div>
</div>

<script>
    function setOrientation(mode) {
        var styleTag = document.getElementById('page-orientation-style');
        var sheet = document.getElementById('report-sheet-container');
        var btnLand = document.getElementById('btn-landscape');
        var btnPort = document.getElementById('btn-portrait');

        if (mode === 'portrait') {
            styleTag.innerHTML = '@page { size: A4 portrait; margin: 12mm; }';
            sheet.classList.add('portrait-mode');
            btnPort.classList.add('active');
            btnLand.classList.remove('active');
        } else {
            styleTag.innerHTML = '@page { size: A4 landscape; margin: 12mm; }';
            sheet.classList.remove('portrait-mode');
            btnLand.classList.add('active');
            btnPort.classList.remove('active');
        }
    }

    @if(request()->boolean('autoprint'))
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.print();
        }, 350);
    });
    @endif
</script>
</body>
</html>
