<?php

namespace App\Http\Controllers;

use App\Services\ReportBuilderService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportBuilderService $reportService
    ) {
        $this->middleware('auth');
    }

    /**
     * Centro de Reportes y BI Personalizado (Constructor interactivo + Plantillas 1-Clic).
     * Requiere permiso: ver-reportes
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || (!$user->can('ver-reportes') && !$user->hasRole('Super Admin'))) {
            abort(403, 'No tienes el permiso [ver-reportes] asignado a tu rol para acceder al Centro de Reportes.');
        }

        $canExport    = $user->can('exportar-reportes') || $user->hasRole('Super Admin');
        $canViewCosts = $this->reportService->canViewFinancialCosts($user);
        $canGlobal    = $user->can('vista-global') || $user->hasRole(['Super Admin', 'Dirección']);

        $forcedStoreId  = !$canGlobal ? current_store_id() : null;
        $domainsCatalog = $this->reportService->getDomainsCatalog($user);
        $quickPresets   = $this->reportService->getQuickPresets();
        $filterOptions  = $this->reportService->getFilterOptions($forcedStoreId);

        $report = $this->reportService->buildReport($request->all(), $user);

        return view('admin.reports.index', compact(
            'report',
            'domainsCatalog',
            'quickPresets',
            'filterOptions',
            'canExport',
            'canViewCosts',
            'canGlobal'
        ));
    }

    /**
     * Exporta cualquier reporte personalizado o preconfigurado en 3 formatos:
     * - excel (.xls corporativo con estilos, tipos numéricos y fila de sumatorias)
     * - csv   (.csv plano UTF-8 con BOM para BI externo)
     * - pdf   (Vista Ejecutiva Imprimible / Descargable A4 con membrete, KPIs y firmas)
     *
     * Requiere permiso: exportar-reportes
     */
    public function export(Request $request)
    {
        $user = $request->user();

        if (!$user || (!$user->can('exportar-reportes') && !$user->hasRole('Super Admin'))) {
            abort(403, 'No tienes el permiso [exportar-reportes] asignado a tu rol para descargar o exportar reportes.');
        }

        $format = strtolower((string) $request->input('format', 'excel'));
        if (!in_array($format, ['excel', 'csv', 'pdf'], true)) {
            $format = 'excel';
        }

        $report    = $this->reportService->buildReport($request->all(), $user);
        $domainKey = $report['filters']['domain'] ?? 'sales';

        // Registrar trazabilidad en bitácora de auditoría si está disponible
        if (function_exists('activity')) {
            try {
                activity('reports')
                    ->causedBy($user)
                    ->withProperties([
                        'domain'      => $domainKey,
                        'format'      => $format,
                        'group_by'    => $report['filters']['group_by'] ?? 'none',
                        'date_label'  => $report['filters']['date_label'] ?? '',
                        'store_label' => $report['filters']['store_label'] ?? '',
                        'rows_count'  => count($report['rows']),
                    ])
                    ->log("Exportó reporte [{$report['domain']['short_name']}] en formato " . strtoupper($format));
            } catch (\Throwable $e) {
                // No interrumpir descarga si falla log secundario
            }
        }

        if ($format === 'pdf') {
            return view('admin.reports.pdf', compact('report'));
        }

        if ($format === 'csv') {
            return $this->streamCsvExport($report, $domainKey);
        }

        return $this->streamExcelExport($report, $domainKey);
    }

    /**
     * Genera archivo CSV UTF-8 con BOM mediante StreamedResponse.
     */
    private function streamCsvExport(array $report, string $domainKey): StreamedResponse
    {
        $filename = sprintf('reporte_%s_%s.csv', $domainKey, now()->format('Y-m-d_His'));
        $columns  = $report['columns'];
        $rows     = $report['rows'];
        $totals   = $report['totals'];

        return new StreamedResponse(function () use ($report, $columns, $rows, $totals) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para compatibilidad directa con Microsoft Excel en español
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Encabezado institucional en primeras líneas
            fputcsv($handle, ['ECOSISTEMA UREÑA — CENTRO DE REPORTES Y BI']);
            fputcsv($handle, ['Reporte:', $report['domain']['name']]);
            fputcsv($handle, ['Período:', $report['filters']['date_label'], 'Sucursal:', $report['filters']['store_label']]);
            fputcsv($handle, ['Generado el:', $report['generated_at'], 'Generado por:', $report['generated_by']]);
            fputcsv($handle, []);

            // Encabezados de columnas
            $headers = [];
            foreach ($columns as $colDef) {
                $headers[] = $colDef['label'];
            }
            fputcsv($handle, $headers);

            // Filas de datos
            foreach ($rows as $row) {
                $csvRow = [];
                foreach ($columns as $colKey => $colDef) {
                    $val  = $row[$colKey] ?? '';
                    $type = $colDef['type'] ?? 'text';

                    if ($type === 'currency' || $type === 'percent') {
                        $csvRow[] = number_format((float) $val, 2, '.', '');
                    } elseif ($type === 'integer') {
                        $csvRow[] = (int) $val;
                    } else {
                        $csvRow[] = (string) $val;
                    }
                }
                fputcsv($handle, $csvRow);
            }

            // Fila de sumatorias (Pie de tabla)
            $footerRow = [];
            $isFirst   = true;
            foreach ($columns as $colKey => $colDef) {
                if ($isFirst) {
                    $footerRow[] = 'TOTALES (' . ($totals['_count'] ?? count($rows)) . ' registros)';
                    $isFirst     = false;
                    continue;
                }
                if (isset($totals[$colKey])) {
                    $type = $colDef['type'] ?? 'text';
                    $footerRow[] = $type === 'integer'
                        ? (int) $totals[$colKey]
                        : number_format((float) $totals[$colKey], 2, '.', '');
                } else {
                    $footerRow[] = '';
                }
            }
            fputcsv($handle, $footerRow);

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    /**
     * Genera archivo Excel Corporativo (.xls) con formato de moneda, colores institucionales,
     * resumen de KPIs y fila de sumatorias al pie de tabla.
     */
    private function streamExcelExport(array $report, string $domainKey): StreamedResponse
    {
        $filename = sprintf('reporte_ejecutivo_%s_%s.xls', $domainKey, now()->format('Y-m-d_His'));
        $columns  = $report['columns'];
        $rows     = $report['rows'];
        $totals   = $report['totals'];
        $colCount = max(1, count($columns));

        return new StreamedResponse(function () use ($report, $columns, $rows, $totals, $colCount) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $title       = htmlspecialchars($report['domain']['name'], ENT_QUOTES, 'UTF-8');
            $periodLabel = htmlspecialchars($report['filters']['date_label'], ENT_QUOTES, 'UTF-8');
            $storeLabel  = htmlspecialchars($report['filters']['store_label'], ENT_QUOTES, 'UTF-8');
            $genAt       = htmlspecialchars($report['generated_at'], ENT_QUOTES, 'UTF-8');
            $genBy       = htmlspecialchars($report['generated_by'], ENT_QUOTES, 'UTF-8');

            fwrite($out, <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body, table { font-family: 'Segoe UI', Calibri, Arial, sans-serif; font-size: 11pt; }
        .corp-title { background-color: #1e293b; color: #ffffff; font-size: 14pt; font-weight: bold; padding: 10px; }
        .corp-sub   { background-color: #f1f5f9; color: #334155; font-size: 10pt; padding: 6px; border-bottom: 2px solid #cbd5e1; }
        .kpi-header { background-color: #e2e8f0; color: #1e293b; font-weight: bold; font-size: 9pt; text-transform: uppercase; border: 1px solid #cbd5e1; padding: 6px; }
        .kpi-val    { background-color: #f8fafc; color: #0f172a; font-weight: bold; font-size: 12pt; border: 1px solid #cbd5e1; padding: 6px; }
        .th-col     { background-color: #2b3a4a; color: #ffffff; font-weight: bold; font-size: 10pt; text-transform: uppercase; border: 1px solid #1e293b; padding: 8px; }
        .td-cell    { border: 1px solid #e2e8f0; padding: 6px; vertical-align: middle; }
        .td-Cancel  { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 6px; }
        .num-curr   { mso-number-format:"\$\#\,\#\#0\.00"; text-align: right; }
        .num-int    { mso-number-format:"\#\,\#\#0"; text-align: right; }
        .num-pct    { mso-number-format:"0\.0\"%\""; text-align: right; }
        .tfoot-row  { background-color: #f1f5f9; color: #0f172a; font-weight: bold; border-top: 2px solid #334155; border-bottom: 2px solid #334155; padding: 8px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="{$colCount}" class="corp-title">ECOSISTEMA UREÑA — {$title}</td>
        </tr>
        <tr>
            <td colspan="{$colCount}" class="corp-sub">
                <b>Período:</b> {$periodLabel} &nbsp;|&nbsp;
                <b>Alcance:</b> {$storeLabel} &nbsp;|&nbsp;
                <b>Generado:</b> {$genAt} por {$genBy}
            </td>
        </tr>
        <tr><td colspan="{$colCount}"></td></tr>
HTML);

            // Bloque de KPIs ejecutivos si existen
            if (!empty($report['kpis'])) {
                fwrite($out, '<tr>');
                foreach ($report['kpis'] as $kpi) {
                    $kLabel = htmlspecialchars($kpi['label'], ENT_QUOTES, 'UTF-8');
                    fwrite($out, "<td colspan=\"2\" class=\"kpi-header\">{$kLabel}</td>");
                }
                fwrite($out, '</tr><tr>');
                foreach ($report['kpis'] as $kpi) {
                    $kVal = htmlspecialchars($kpi['value'], ENT_QUOTES, 'UTF-8');
                    $kSub = htmlspecialchars($kpi['sublabel'] ?? '', ENT_QUOTES, 'UTF-8');
                    fwrite($out, "<td colspan=\"2\" class=\"kpi-val\">{$kVal} <br><span style=\"font-size:8.5pt;font-weight:normal;color:#64748b;\">{$kSub}</span></td>");
                }
                fwrite($out, '</tr><tr><td colspan="' . $colCount . '"></td></tr>');
            }

            // Cabecera de columnas
            fwrite($out, '<tr>');
            foreach ($columns as $colDef) {
                $label = htmlspecialchars($colDef['label'], ENT_QUOTES, 'UTF-8');
                fwrite($out, "<th class=\"th-col\">{$label}</th>");
            }
            fwrite($out, '</tr>');

            // Filas del reporte
            foreach ($rows as $row) {
                $rowClass = !empty($row['_is_cancelled']) ? 'td-Cancel' : 'td-cell';
                fwrite($out, '<tr>');
                foreach ($columns as $colKey => $colDef) {
                    $val  = $row[$colKey] ?? '';
                    $type = $colDef['type'] ?? 'text';

                    if ($type === 'currency') {
                        $num = number_format((float) $val, 2, '.', '');
                        fwrite($out, "<td class=\"{$rowClass} num-curr\">{$num}</td>");
                    } elseif ($type === 'integer') {
                        $num = (int) $val;
                        fwrite($out, "<td class=\"{$rowClass} num-int\">{$num}</td>");
                    } elseif ($type === 'percent') {
                        $num = number_format((float) $val, 1, '.', '');
                        fwrite($out, "<td class=\"{$rowClass} num-pct\">{$num}</td>");
                    } else {
                        $safe = htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8');
                        fwrite($out, "<td class=\"{$rowClass}\">{$safe}</td>");
                    }
                }
                fwrite($out, '</tr>');
            }

            // Pie de tabla con sumatorias
            fwrite($out, '<tr>');
            $isFirst = true;
            foreach ($columns as $colKey => $colDef) {
                if ($isFirst) {
                    $cnt = (int) ($totals['_count'] ?? count($rows));
                    fwrite($out, "<td class=\"tfoot-row\">SUMATORIA TOTAL ({$cnt} registros)</td>");
                    $isFirst = false;
                    continue;
                }

                if (isset($totals[$colKey])) {
                    $type = $colDef['type'] ?? 'text';
                    if ($type === 'integer') {
                        $num = (int) $totals[$colKey];
                        fwrite($out, "<td class=\"tfoot-row num-int\">{$num}</td>");
                    } else {
                        $num = number_format((float) $totals[$colKey], 2, '.', '');
                        fwrite($out, "<td class=\"tfoot-row num-curr\">{$num}</td>");
                    }
                } else {
                    fwrite($out, '<td class="tfoot-row"></td>');
                }
            }
            fwrite($out, '</tr></table></body></html>');

            fclose($out);
        }, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }
}
