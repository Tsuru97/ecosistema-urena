@extends('layouts.master')

@section('title')
    Centro de Reportes y BI Personalizado
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Analítica e Inteligencia de Negocios
        @endslot
        @slot('title')
            Centro de Reportes y Constructor BI
        @endslot
    @endcomponent

    @php
        $currentDomainKey = $report['filters']['domain'] ?? 'sales';
        $currentGroupBy   = $report['filters']['group_by'] ?? 'none';
        $currentPreset    = $report['filters']['date_preset'] ?? 'this_month';
        $activeColKeys    = array_keys($report['columns']);
        $exportQuery      = request()->query();
        if (empty($exportQuery['domain'])) {
            $exportQuery['domain'] = $currentDomainKey;
        }
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════════════
         1. ENCABEZADO EJECUTIVO Y BARRA DE EXPORTACIÓN MULTIFORMATO
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="row mb-3 pb-1">
        <div class="col-12">
            <div class="d-flex align-items-lg-center flex-lg-row flex-column justify-content-between gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-{{ $report['domain']['color'] ?? 'primary' }}-subtle text-{{ $report['domain']['color'] ?? 'primary' }} fs-12 px-2 py-1">
                            <i class="{{ $report['domain']['icon'] ?? 'ri-bar-chart-box-line' }} align-middle me-1"></i>
                            Dominio Activo: {{ $report['domain']['short_name'] }}
                        </span>
                        <span class="badge bg-light text-body border fs-12 px-2 py-1">
                            <i class="ri-calendar-event-line align-middle me-1 text-primary"></i>
                            {{ $report['filters']['date_label'] }}
                        </span>
                        <span class="badge bg-light text-body border fs-12 px-2 py-1">
                            <i class="ri-store-2-line align-middle me-1 text-info"></i>
                            {{ $report['filters']['store_label'] }}
                        </span>
                    </div>
                    <h4 class="fs-18 fw-semibold mb-1 mt-2">{{ $report['domain']['name'] }}</h4>
                    <p class="text-muted mb-0 fs-13">{{ $report['domain']['description'] }}</p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    {{-- Mis Plantillas Personalizadas Guardadas (localStorage) --}}
                    <div class="dropdown">
                        <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" id="savedTemplatesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ri-bookmark-3-line align-middle me-1"></i> Mis Reportes Guardados
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 290px;" aria-labelledby="savedTemplatesDropdown">
                            <div class="px-2 py-1 border-bottom mb-1 d-flex justify-content-between align-items-center">
                                <span class="fw-semibold fs-12 text-uppercase text-muted">Plantillas Personalizadas</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-primary fw-semibold text-decoration-none" onclick="promptSaveCurrentTemplate()">
                                    <i class="ri-add-line"></i> Guardar Actual
                                </button>
                            </div>
                            <div id="saved-templates-list">
                                <div class="text-muted small px-2 py-2 text-center">No tienes plantillas guardadas aún.</div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('root') }}" class="btn btn-soft-primary btn-sm">
                        <i class="ri-dashboard-2-line align-middle me-1"></i> Ir al Dashboard
                    </a>

                    @if($canExport)
                        <div class="btn-group" role="group" aria-label="Exportar Reporte">
                            <a href="{{ route('reports.export', array_merge($exportQuery, ['format' => 'excel'])) }}"
                               class="btn btn-success btn-sm fw-medium">
                                <i class="ri-file-excel-2-line align-middle me-1"></i> Descargar Excel (.xls)
                            </a>
                            <a href="{{ route('reports.export', array_merge($exportQuery, ['format' => 'csv'])) }}"
                               class="btn btn-info btn-sm fw-medium text-white">
                                <i class="ri-file-list-3-line align-middle me-1"></i> CSV
                            </a>
                            <a href="{{ route('reports.export', array_merge($exportQuery, ['format' => 'pdf'])) }}"
                               target="_blank"
                               class="btn btn-danger btn-sm fw-medium">
                                <i class="ri-file-pdf-2-line align-middle me-1"></i> PDF Ejecutivo / Imprimir
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         2. CATÁLOGO DE REPORTES EJECUTIVOS DE 1-CLIC (QUICK PRESETS)
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed py-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="ri-flashlight-line text-warning fs-18"></i>
                <h6 class="card-title mb-0 fs-14 fw-semibold">Catálogo de Reportes Ejecutivos de 1-Clic</h6>
                <span class="badge bg-light text-muted border">Plantillas Corporativas Preconfiguradas</span>
            </div>
            <button class="btn btn-sm btn-ghost-secondary py-0" type="button" data-bs-toggle="collapse" data-bs-target="#quickPresetsCollapse" aria-expanded="true">
                <i class="ri-arrow-up-s-line"></i> Mostrar / Ocultar
            </button>
        </div>
        <div class="collapse show" id="quickPresetsCollapse">
            <div class="card-body py-3">
                <div class="row g-2">
                    @foreach($quickPresets as $preset)
                        @php
                            $isPresetActive = ($currentDomainKey === $preset['domain'] && $currentGroupBy === $preset['group_by'] && ($report['filters']['status'] ?? 'all') === ($preset['params']['status'] ?? 'all'));
                        @endphp
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="border rounded p-2 px-3 h-100 d-flex flex-column justify-content-between {{ $isPresetActive ? 'border-primary bg-primary-subtle bg-opacity-10' : 'bg-light bg-opacity-50' }}">
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <div class="avatar-xs flex-shrink-0">
                                        <span class="avatar-title bg-{{ $preset['color'] }}-subtle text-{{ $preset['color'] }} rounded fs-16">
                                            <i class="{{ $preset['icon'] }}"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <a href="{{ route('reports.index', $preset['params']) }}" class="text-body fw-semibold fs-13 d-block text-truncate stretched-link-custom">
                                            {{ $preset['title'] }}
                                        </a>
                                        <span class="text-muted fs-11 d-block text-truncate">{{ $preset['subtitle'] }}</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-1 border-top border-top-dashed">
                                    <a href="{{ route('reports.index', $preset['params']) }}" class="btn btn-xs btn-soft-{{ $preset['color'] }} py-0 px-2 fs-11">
                                        <i class="ri-play-circle-line align-middle me-1"></i> Cargar en Pantalla
                                    </a>
                                    @if($canExport)
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('reports.export', array_merge($preset['params'], ['format' => 'excel'])) }}"
                                               class="btn btn-xs btn-soft-success py-0 px-1 fs-11" title="Descargar Excel Directo">
                                                <i class="ri-file-excel-2-line"></i> Excel
                                            </a>
                                            <a href="{{ route('reports.export', array_merge($preset['params'], ['format' => 'pdf'])) }}"
                                               target="_blank"
                                               class="btn btn-xs btn-soft-danger py-0 px-1 fs-11" title="Abrir PDF Ejecutivo">
                                                <i class="ri-file-pdf-line"></i> PDF
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         3. CONSTRUCTOR DE REPORTES PERSONALIZADOS (CUSTOM REPORT BUILDER)
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="card mb-4">
        <div class="card-header bg-light-subtle border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="ri-equalizer-line text-primary fs-18"></i>
                <h5 class="card-title mb-0 fs-15">Constructor de Reportes Personalizados</h5>
                <span class="badge bg-primary-subtle text-primary">7 Dominios BI • Agrupación Dinámica • Selector de Columnas</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-soft-info btn-sm" data-bs-toggle="collapse" data-bs-target="#columnPickerCollapse" aria-expanded="false">
                    <i class="ri-layout-column-line align-middle me-1"></i> Personalizar Columnas (<span id="selected-cols-counter">{{ count($activeColKeys) }}</span>)
                </button>
                <a href="{{ route('reports.index', ['domain' => $currentDomainKey]) }}" class="btn btn-ghost-secondary btn-sm">
                    <i class="ri-refresh-line align-middle me-1"></i> Limpiar Filtros
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('reports.index') }}" method="GET" id="custom-report-form">
                <div class="row g-3">
                    {{-- Paso 1: Dominio de Datos --}}
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label fw-semibold fs-12 text-uppercase text-muted mb-1">
                            1. Dominio Analítico (Fuente)
                        </label>
                        <select name="domain" id="select-domain" class="form-select border-primary-subtle fw-medium">
                            @foreach($domainsCatalog as $dKey => $dMeta)
                                <option value="{{ $dKey }}" {{ $currentDomainKey === $dKey ? 'selected' : '' }}>
                                    {{ $dMeta['short_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Paso 2: Agrupación Dinámica --}}
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label fw-semibold fs-12 text-uppercase text-muted mb-1">
                            2. Nivel de Agrupación (Group By)
                        </label>
                        <select name="group_by" id="select-group-by" class="form-select">
                            @foreach($report['domain']['group_by'] as $gKey => $gLabel)
                                <option value="{{ $gKey }}" {{ $currentGroupBy === $gKey ? 'selected' : '' }}>
                                    {{ $gLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Paso 3: Período Predefinido --}}
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold fs-12 text-uppercase text-muted mb-1">
                            3. Período Temporal
                        </label>
                        <select name="date_preset" id="select-date-preset" class="form-select">
                            <option value="today"        {{ $currentPreset === 'today' ? 'selected' : '' }}>Hoy</option>
                            <option value="yesterday"    {{ $currentPreset === 'yesterday' ? 'selected' : '' }}>Ayer</option>
                            <option value="last_7_days"  {{ $currentPreset === 'last_7_days' ? 'selected' : '' }}>Últimos 7 días</option>
                            <option value="this_month"   {{ $currentPreset === 'this_month' ? 'selected' : '' }}>Este Mes (Actual)</option>
                            <option value="last_month"   {{ $currentPreset === 'last_month' ? 'selected' : '' }}>Mes Anterior</option>
                            <option value="this_quarter" {{ $currentPreset === 'this_quarter' ? 'selected' : '' }}>Trimestre Actual</option>
                            <option value="this_year"    {{ $currentPreset === 'this_year' ? 'selected' : '' }}>Año en Curso ({{ now()->year }})</option>
                            <option value="all_time"     {{ $currentPreset === 'all_time' ? 'selected' : '' }}>Histórico Completo</option>
                            <option value="custom"       {{ $currentPreset === 'custom' ? 'selected' : '' }}>Rango Personalizado...</option>
                        </select>
                    </div>

                    {{-- Fechas Desde / Hasta --}}
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold fs-12 text-uppercase text-muted mb-1">Fecha Inicial</label>
                        <input type="date" name="date_from" id="input-date-from" class="form-control"
                               value="{{ $report['filters']['date_from'] }}">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fw-semibold fs-12 text-uppercase text-muted mb-1">Fecha Final</label>
                        <input type="date" name="date_to" id="input-date-to" class="form-control"
                               value="{{ $report['filters']['date_to'] }}">
                    </div>

                    {{-- Filtros Dimensionales Secundarios --}}
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fs-12 text-muted mb-1">Sucursal</label>
                        <select name="store_id" class="form-select form-select-sm" {{ !$canGlobal ? 'disabled' : '' }}>
                            @if($canGlobal)
                                <option value="all">Todas las Sucursales</option>
                            @endif
                            @foreach($filterOptions['stores'] as $st)
                                <option value="{{ $st->id }}" {{ (int)($report['filters']['store_id'] ?? 0) === (int)$st->id ? 'selected' : '' }}>
                                    {{ $st->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fs-12 text-muted mb-1">Vendedor / Usuario</label>
                        <select name="seller_id" class="form-select form-select-sm">
                            <option value="all">Todos los Vendedores</option>
                            @foreach($filterOptions['sellers'] as $sel)
                                <option value="{{ $sel->id }}" {{ (int)($report['filters']['seller_id'] ?? 0) === (int)$sel->id ? 'selected' : '' }}>
                                    {{ $sel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fs-12 text-muted mb-1">Tipo Operación / Origen</label>
                        <select name="operation_type" class="form-select form-select-sm">
                            <option value="all"     {{ ($report['filters']['operation_type'] ?? 'all') === 'all' ? 'selected' : '' }}>Todos (Consolidado)</option>
                            <option value="pos"     {{ ($report['filters']['operation_type'] ?? '') === 'pos' ? 'selected' : '' }}>Solo Ventas POS</option>
                            <option value="layaway" {{ ($report['filters']['operation_type'] ?? '') === 'layaway' ? 'selected' : '' }}>Solo Apartados POS</option>
                            <option value="special" {{ ($report['filters']['operation_type'] ?? '') === 'special' ? 'selected' : '' }}>Solo Pedidos Especiales</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fs-12 text-muted mb-1">Estado / Condición</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="all"              {{ ($report['filters']['status'] ?? 'all') === 'all' ? 'selected' : '' }}>Todos los Estados</option>
                            <option value="active"           {{ ($report['filters']['status'] ?? '') === 'active' ? 'selected' : '' }}>Solo Activos (Sin Cancelados)</option>
                            <option value="with_balance"     {{ ($report['filters']['status'] ?? '') === 'with_balance' ? 'selected' : '' }}>Con Saldo Pendiente</option>
                            <option value="cancelled"        {{ ($report['filters']['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Solo Cancelados</option>
                            <option value="pending_delivery" {{ ($report['filters']['status'] ?? '') === 'pending_delivery' ? 'selected' : '' }}>Pendientes de Entrega</option>
                            <option value="alert"            {{ ($report['filters']['status'] ?? '') === 'alert' ? 'selected' : '' }}>Inventario en Alerta Mínimo</option>
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fs-12 text-muted mb-1">Proveedor / Método Pago</label>
                        <div class="d-flex gap-1">
                            <select name="supplier_id" class="form-select form-select-sm" title="Filtrar por Proveedor">
                                <option value="all">Proveedor: Todos</option>
                                @foreach($filterOptions['suppliers'] as $sup)
                                    <option value="{{ $sup->id }}" {{ (int)($report['filters']['supplier_id'] ?? 0) === (int)$sup->id ? 'selected' : '' }}>
                                        {{ $sup->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="payment_method_id" class="form-select form-select-sm" title="Filtrar por Método de Pago">
                                <option value="all">Pago: Todos</option>
                                @foreach($filterOptions['payment_methods'] as $pm)
                                    <option value="{{ $pm->id }}" {{ (int)($report['filters']['payment_method_id'] ?? 0) === (int)$pm->id ? 'selected' : '' }}>
                                        {{ $pm->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label fs-12 text-muted mb-1">Búsqueda Rápida</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Folio, cliente, SKU..."
                                   value="{{ $report['filters']['search'] ?? '' }}">
                            <button type="submit" class="btn btn-primary fw-semibold">
                                <i class="ri-search-eye-line align-middle me-1"></i> Generar
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Selector Interactivo de Columnas Personalizadas --}}
                <div class="collapse mt-3 pt-3 border-top border-top-dashed" id="columnPickerCollapse">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                        <div>
                            <span class="fw-semibold fs-13 text-body">
                                <i class="ri-layout-column-line text-info align-middle me-1"></i>
                                Selección de Columnas para Vista y Exportación (Excel / CSV / PDF)
                            </span>
                            <small class="text-muted ms-2">(Aplica cuando la agrupación está en "Sin agrupar")</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-xs btn-soft-primary" onclick="toggleAllColumns(true)">
                                Seleccionar Todas
                            </button>
                            <button type="button" class="btn btn-xs btn-soft-secondary" onclick="resetDefaultColumns()">
                                Columnas por Defecto
                            </button>
                        </div>
                    </div>
                    <div class="row g-2" id="columns-checkbox-container">
                        @foreach($report['available_columns'] as $colKey => $colDef)
                            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                                <div class="form-check form-check-primary bg-light rounded p-2 px-3 border">
                                    <input class="form-check-input column-checkbox ms-0 me-2"
                                           type="checkbox"
                                           name="columns[]"
                                           value="{{ $colKey }}"
                                           id="col-chk-{{ $colKey }}"
                                           data-default="{{ !empty($colDef['default']) ? '1' : '0' }}"
                                           {{ in_array($colKey, $activeColKeys, true) ? 'checked' : '' }}
                                           onchange="updateSelectedColumnsCounter()">
                                    <label class="form-check-label fs-12 fw-medium text-truncate d-block" for="col-chk-{{ $colKey }}">
                                        {{ $colDef['label'] }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-check-double-line align-middle me-1"></i> Aplicar Columnas y Actualizar Reporte
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         4. BANDA EJECUTIVA DE KPIS DEL REPORTE ACTUAL (VELZON CRM-WIDGET)
    ══════════════════════════════════════════════════════════════════════════ --}}
    @if(!empty($report['kpis']))
    <div class="row">
        <div class="col-12">
            <div class="card crm-widget mb-4">
                <div class="card-body p-0">
                    <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-0">
                        @foreach($report['kpis'] as $kIdx => $kpi)
                            <div class="col {{ $kIdx < count($report['kpis']) - 1 ? 'border-end' : '' }}">
                                <div class="py-3 px-3">
                                    <h5 class="text-muted text-uppercase fs-12 fw-semibold mb-2">
                                        {{ $kpi['label'] }}
                                    </h5>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <span class="avatar-sm d-inline-block">
                                                <span class="avatar-title bg-{{ $kpi['color'] ?? 'primary' }}-subtle text-{{ $kpi['color'] ?? 'primary' }} rounded fs-22">
                                                    <i class="{{ $kpi['icon'] ?? 'ri-bar-chart-2-line' }}"></i>
                                                </span>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h3 class="mb-0 fw-bold fs-20">{{ $kpi['value'] }}</h3>
                                            <p class="text-muted mb-0 fs-12 mt-1">{{ $kpi['sublabel'] ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════════
         5. GRÁFICA ANALÍTICA DINÁMICA DEL REPORTE
    ══════════════════════════════════════════════════════════════════════════ --}}
    @if(!empty($report['chart']['categories']))
    <div class="card mb-4">
        <div class="card-header border-bottom-dashed py-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="ri-bar-chart-grouped-line text-primary fs-18"></i>
                <h5 class="card-title mb-0 fs-14 fw-semibold">{{ $report['chart']['title'] ?? 'Visualización Analítica del Reporte' }}</h5>
            </div>
            <button class="btn btn-sm btn-ghost-secondary py-0" type="button" data-bs-toggle="collapse" data-bs-target="#reportChartCollapse" aria-expanded="true">
                <i class="ri-eye-line me-1"></i> Mostrar / Ocultar Gráfica
            </button>
        </div>
        <div class="collapse show" id="reportChartCollapse">
            <div class="card-body">
                <div id="dynamic-report-chart" data-colors='["--vz-primary", "--vz-success", "--vz-info"]' class="apex-charts" dir="ltr"></div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════════
         6. TABLA DE RESULTADOS CON PIE DE TABLA (SUMATORIAS) Y EXPORTACIÓN
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0 fs-15">
                    <i class="ri-table-2 text-primary align-middle me-1"></i>
                    Listado Detallado del Reporte
                    <span class="badge bg-primary-subtle text-primary ms-1">{{ count($report['rows']) }} registros</span>
                </h5>
                <small class="text-muted">
                    Incluye sumatoria automática al pie de tabla. Haz clic en cualquier folio para auditar el documento origen.
                </small>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="search-box">
                    <input type="text" id="client-table-filter" class="form-control form-control-sm" placeholder="Filtrar en esta tabla...">
                    <i class="ri-search-line search-icon"></i>
                </div>
                @if($canExport)
                    <a href="{{ route('reports.export', array_merge($exportQuery, ['format' => 'excel'])) }}"
                       class="btn btn-soft-success btn-sm">
                        <i class="ri-file-excel-2-line align-middle me-1"></i> Excel (.xls)
                    </a>
                    <a href="{{ route('reports.export', array_merge($exportQuery, ['format' => 'csv'])) }}"
                       class="btn btn-soft-info btn-sm">
                        <i class="ri-file-list-3-line align-middle me-1"></i> CSV
                    </a>
                    <a href="{{ route('reports.export', array_merge($exportQuery, ['format' => 'pdf', 'autoprint' => 1])) }}"
                       target="_blank"
                       class="btn btn-soft-danger btn-sm">
                        <i class="ri-printer-line align-middle me-1"></i> Imprimir PDF
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive table-card mb-0" style="max-height: 620px; overflow-y: auto;">
                <table class="table table-hover table-striped align-middle table-nowrap mb-0" id="bi-report-table">
                    <thead class="table-light text-muted sticky-top" style="z-index: 2;">
                        <tr>
                            <th style="width: 46px;" class="text-center">#</th>
                            @foreach($report['columns'] as $colKey => $colDef)
                                @php
                                    $isNum = in_array($colDef['type'] ?? 'text', ['currency', 'integer', 'percent'], true);
                                @endphp
                                <th class="{{ $isNum ? 'text-end' : '' }}">{{ $colDef['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $rIdx => $row)
                            <tr class="report-data-row {{ !empty($row['_is_cancelled']) ? 'table-danger bg-opacity-25' : '' }}">
                                <td class="text-center text-muted fs-12">{{ $rIdx + 1 }}</td>
                                @foreach($report['columns'] as $colKey => $colDef)
                                    @php
                                        $val  = $row[$colKey] ?? '';
                                        $type = $colDef['type'] ?? 'text';
                                    @endphp

                                    @if($colKey === 'folio' || $colKey === 'sku')
                                        <td>
                                            @if(!empty($row['_url']) && $row['_url'] !== '#')
                                                <a href="{{ $row['_url'] }}" class="fw-semibold text-primary text-decoration-underline">
                                                    {{ $val }}
                                                </a>
                                            @else
                                                <span class="fw-semibold text-body">{{ $val }}</span>
                                            @endif
                                        </td>
                                    @elseif($colKey === 'operation_type' || $colKey === 'origin_type')
                                        <td>
                                            <span class="badge bg-{{ $row['_type_color'] ?? 'primary' }}-subtle text-{{ $row['_type_color'] ?? 'primary' }}">
                                                {{ $val }}
                                            </span>
                                        </td>
                                    @elseif($type === 'status')
                                        @php
                                            $badgeColor = $colKey === 'payment_status'
                                                ? ($row['_pay_color'] ?? 'secondary')
                                                : ($row['_status_color'] ?? 'secondary');
                                        @endphp
                                        <td>
                                            <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }}">
                                                {{ $val }}
                                            </span>
                                        </td>
                                    @elseif($type === 'currency')
                                        <td class="text-end fw-medium {{ (float)$val < 0 ? 'text-danger' : ($colKey === 'balance' && (float)$val > 0 ? 'text-warning fw-semibold' : '') }}">
                                            ${{ number_format((float) $val, 2) }}
                                        </td>
                                    @elseif($type === 'integer')
                                        <td class="text-end fw-medium">{{ number_format((int) $val) }}</td>
                                    @elseif($type === 'percent')
                                        <td class="text-end">
                                            <span class="badge bg-light text-body border">{{ number_format((float) $val, 1) }}%</span>
                                        </td>
                                    @else
                                        <td class="text-truncate" style="max-width: 240px;" title="{{ $val }}">{{ $val }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) + 1 }}" class="text-center py-5 text-muted">
                                    <i class="ri-folder-open-line fs-32 d-block mb-2 text-secondary"></i>
                                    No se encontraron registros para los filtros seleccionados en este período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($report['rows']) > 0)
                    <tfoot class="table-light fw-bold sticky-bottom" style="z-index: 2; border-top: 2px solid #cbd5e1;">
                        <tr>
                            <td class="text-center text-primary">Σ</td>
                            @php $firstFooterCol = true; @endphp
                            @foreach($report['columns'] as $colKey => $colDef)
                                @if($firstFooterCol)
                                    <td class="text-uppercase fs-12">
                                        SUMATORIA TOTAL ({{ $report['totals']['_count'] ?? count($report['rows']) }} registros activos)
                                    </td>
                                    @php $firstFooterCol = false; @endphp
                                @elseif(isset($report['totals'][$colKey]))
                                    @if(($colDef['type'] ?? 'text') === 'integer')
                                        <td class="text-end text-dark fs-13">{{ number_format((int) $report['totals'][$colKey]) }}</td>
                                    @else
                                        <td class="text-end text-primary fs-13">${{ number_format((float) $report['totals'][$colKey], 2) }}</td>
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
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            'use strict';

            var domainsCatalog = @json($domainsCatalog);
            var chartPayload   = @json($report['chart'] ?? null);

            // ── 1. Cambio dinámico de Dominio (actualiza Group By y Columnas disponibles) ──
            var domainSelect  = document.getElementById('select-domain');
            var groupBySelect = document.getElementById('select-group-by');
            var colsContainer = document.getElementById('columns-checkbox-container');

            if (domainSelect) {
                domainSelect.addEventListener('change', function () {
                    var selectedKey = this.value;
                    var meta = domainsCatalog[selectedKey];
                    if (!meta) return;

                    // Actualizar opciones de Group By
                    if (groupBySelect && meta.group_by) {
                        groupBySelect.innerHTML = '';
                        Object.keys(meta.group_by).forEach(function (gKey) {
                            var opt = document.createElement('option');
                            opt.value = gKey;
                            opt.textContent = meta.group_by[gKey];
                            groupBySelect.appendChild(opt);
                        });
                    }

                    // Actualizar checkboxes de columnas
                    if (colsContainer && meta.columns) {
                        colsContainer.innerHTML = '';
                        Object.keys(meta.columns).forEach(function (cKey) {
                            var cDef = meta.columns[cKey];
                            var isDef = !!cDef.default;
                            var colDiv = document.createElement('div');
                            colDiv.className = 'col-xl-2 col-lg-3 col-md-4 col-6';
                            colDiv.innerHTML =
                                '<div class="form-check form-check-primary bg-light rounded p-2 px-3 border">' +
                                    '<input class="form-check-input column-checkbox ms-0 me-2" type="checkbox" name="columns[]" ' +
                                        'value="' + cKey + '" id="col-chk-' + cKey + '" data-default="' + (isDef ? '1' : '0') + '" ' +
                                        (isDef ? 'checked' : '') + ' onchange="updateSelectedColumnsCounter()">' +
                                    '<label class="form-check-label fs-12 fw-medium text-truncate d-block" for="col-chk-' + cKey + '">' +
                                        cDef.label +
                                    '</label>' +
                                '</div>';
                            colsContainer.appendChild(colDiv);
                        });
                        updateSelectedColumnsCounter();
                    }
                });
            }

            // ── 2. Cambio automático a "custom" cuando se modifican fechas manualmente ──
            var datePresetSelect = document.getElementById('select-date-preset');
            var inputDateFrom    = document.getElementById('input-date-from');
            var inputDateTo      = document.getElementById('input-date-to');

            function markCustomDate() {
                if (datePresetSelect) {
                    datePresetSelect.value = 'custom';
                }
            }
            if (inputDateFrom) inputDateFrom.addEventListener('change', markCustomDate);
            if (inputDateTo)   inputDateTo.addEventListener('change', markCustomDate);

            // ── 3. Funciones de selección de columnas ──
            window.updateSelectedColumnsCounter = function () {
                var checked = document.querySelectorAll('.column-checkbox:checked').length;
                var badge   = document.getElementById('selected-cols-counter');
                if (badge) badge.textContent = checked;
            };

            window.toggleAllColumns = function (state) {
                document.querySelectorAll('.column-checkbox').forEach(function (chk) {
                    chk.checked = !!state;
                });
                updateSelectedColumnsCounter();
            };

            window.resetDefaultColumns = function () {
                document.querySelectorAll('.column-checkbox').forEach(function (chk) {
                    chk.checked = chk.getAttribute('data-default') === '1';
                });
                updateSelectedColumnsCounter();
            };

            // ── 4. Filtro instantáneo en tabla del lado del cliente ──
            var tableFilterInput = document.getElementById('client-table-filter');
            if (tableFilterInput) {
                tableFilterInput.addEventListener('input', function () {
                    var q = this.value.toLowerCase().trim();
                    document.querySelectorAll('#bi-report-table tbody tr.report-data-row').forEach(function (tr) {
                        var text = tr.textContent.toLowerCase();
                        tr.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
                    });
                });
            }

            // ── 5. Gestión de Plantillas Personalizadas en localStorage ──
            var STORAGE_KEY = 'urena_bi_saved_templates_v1';

            function getSavedTemplates() {
                try {
                    var raw = localStorage.getItem(STORAGE_KEY);
                    return raw ? JSON.parse(raw) : [];
                } catch (e) {
                    return [];
                }
            }

            function renderSavedTemplates() {
                var listEl = document.getElementById('saved-templates-list');
                if (!listEl) return;

                var templates = getSavedTemplates();
                if (!templates.length) {
                    listEl.innerHTML = '<div class="text-muted small px-2 py-2 text-center">No tienes plantillas guardadas aún.</div>';
                    return;
                }

                listEl.innerHTML = '';
                templates.forEach(function (tpl, idx) {
                    var item = document.createElement('div');
                    item.className = 'd-flex align-items-center justify-content-between px-2 py-1 rounded hover-bg-light';
                    item.innerHTML =
                        '<a href="' + tpl.url + '" class="text-body fs-12 fw-medium text-truncate flex-grow-1 text-decoration-none me-2">' +
                            '<i class="ri-file-chart-line text-primary me-1"></i>' + tpl.name +
                        '</a>' +
                        '<button type="button" class="btn btn-xs btn-ghost-danger py-0 px-1" onclick="deleteSavedTemplate(' + idx + ')" title="Eliminar">' +
                            '<i class="ri-delete-bin-line"></i>' +
                        '</button>';
                    listEl.appendChild(item);
                });
            }

            window.promptSaveCurrentTemplate = function () {
                var name = window.prompt('Nombre para guardar esta configuración de reporte personalizado:', '{{ $report["domain"]["short_name"] }} Personalizado');
                if (!name || !name.trim()) return;

                var form = document.getElementById('custom-report-form');
                var params = new URLSearchParams(new FormData(form)).toString();
                var url = '{{ route("reports.index") }}?' + params;

                var list = getSavedTemplates();
                list.unshift({ name: name.trim(), url: url });
                localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, 15)));
                renderSavedTemplates();
            };

            window.deleteSavedTemplate = function (idx) {
                var list = getSavedTemplates();
                list.splice(idx, 1);
                localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
                renderSavedTemplates();
            };

            renderSavedTemplates();

            // ── 6. Renderizado de Gráfica Analítica Dinámica (ApexCharts) ──
            var chartContainer = document.getElementById('dynamic-report-chart');
            if (chartContainer && chartPayload && chartPayload.categories && chartPayload.categories.length > 0 && typeof ApexCharts !== 'undefined') {
                var chartType = chartPayload.type || 'bar';
                var options = {
                    chart: {
                        type: chartType,
                        height: 310,
                        toolbar: { show: true }
                    },
                    series: chartPayload.series || [],
                    xaxis: {
                        categories: chartPayload.categories || [],
                        labels: { style: { fontSize: '11px' } }
                    },
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                return '$' + Number(val || 0).toLocaleString('es-MX', { maximumFractionDigits: 0 });
                            }
                        }
                    },
                    colors: ['#405189', '#0ab39c', '#299cdb'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: chartType === 'area' ? 2 : 0 },
                    plotOptions: {
                        bar: { borderRadius: 4, columnWidth: '48%' }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return '$' + Number(val || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                };

                var chart = new ApexCharts(chartContainer, options);
                chart.render();
            }
        })();
    </script>
@endsection
