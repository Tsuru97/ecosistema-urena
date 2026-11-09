@extends('layouts.master')
@section('title') Dashboard @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/swiper/swiper-bundle.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@php
    $ctx    = $dashboard['context'];
    $kpis   = $dashboard['kpis'];
    $charts = $dashboard['charts'];
    $tables = $dashboard['tables'];
@endphp

    @component('components.breadcrumb')
        @slot('li_1') Ecosistema Ureña @endslot
        @slot('title') Dashboard Ejecutivo ({{ $ctx['period_label'] }}) @endslot
    @endcomponent

    {{-- ══════════════════════════════════════════════════════════════════════════
         CABECERA EJECUTIVA Y SELECTOR DE PERÍODO (Basado en Velzon Native Cards)
         ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="row">
        <div class="col-xl-12">
            <div class="card bg-primary-subtle shadow-none border border-primary-subtle mb-4">
                <div class="card-body py-3">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-5">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-title bg-primary text-white rounded-2 fs-3 material-shadow">
                                        <i class="bx bx-bar-chart-alt-2"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="fs-16 mb-1 text-primary fw-semibold">{{ $greeting }}, {{ $user->name }}!</h5>
                                    <p class="text-muted mb-0 text-truncate">
                                        Gestión de <b>{{ $currentStore->name ?? 'Todas las Tiendas (Global)' }}</b> &bull; Comparativa vs. {{ $ctx['prev_period_label'] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 d-none d-lg-flex justify-content-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="#sec-comunicados" class="btn btn-soft-primary material-shadow-none">
                                    Avisos
                                    @if(($postsMeta['unread_count'] ?? 0) > 0)
                                        <span class="badge bg-danger ms-1">{{ $postsMeta['unread_count'] }}</span>
                                    @endif
                                </a>
                                <a href="#sec-kpis" class="btn btn-soft-primary material-shadow-none">1. KPIs</a>
                                <a href="#sec-charts" class="btn btn-soft-primary material-shadow-none">2. Ventas</a>
                                <a href="#sec-tablero" class="btn btn-soft-primary material-shadow-none">3. Tablero</a>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <form method="GET" action="{{ route('root') }}" data-no-loader="true" class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                                <div class="input-group input-group-sm" style="width: auto;">
                                    <span class="input-group-text bg-white border-primary-subtle text-primary">
                                        <i class="ri-calendar-event-line"></i>
                                    </span>
                                    <select name="month" class="form-select form-select-sm border-primary-subtle fw-medium" onchange="this.form.submit()">
                                        @foreach($ctx['months_list'] as $mNum => $mName)
                                            <option value="{{ $mNum }}" @selected($ctx['month'] == $mNum)>{{ $mName }}</option>
                                        @endforeach
                                    </select>
                                    <select name="year" class="form-select form-select-sm border-primary-subtle fw-medium" onchange="this.form.submit()">
                                        @foreach($ctx['available_years'] as $yOption)
                                            <option value="{{ $yOption }}" @selected($ctx['year'] == $yOption)>{{ $yOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if(!$ctx['is_current_month'])
                                    <a href="{{ route('root') }}" class="btn btn-sm btn-warning material-shadow-none">
                                        <i class="ri-history-line align-middle me-1"></i>Hoy
                                    </a>
                                @endif

                                @can('ver-reportes')
                                    <a href="{{ route('reports.index', ['month' => $ctx['month'], 'year' => $ctx['year']]) }}" class="btn btn-sm btn-info text-white material-shadow-none" title="Abrir Constructor de Reportes Personalizados">
                                        <i class="ri-bar-chart-box-line align-middle me-1"></i>Reportes BI
                                    </a>
                                @endcan

                                @can('exportar-reportes')
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-success dropdown-toggle material-shadow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-download-2-line align-middle me-1"></i>Exportar Mes
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li class="dropdown-header text-uppercase fs-11">Ventas Consolidado ({{ $ctx['period_label'] }})</li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('reports.export', ['domain' => 'sales', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'excel']) }}">
                                                    <i class="ri-file-excel-2-line text-success align-middle me-1"></i> Descargar Excel (.xls)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('reports.export', ['domain' => 'sales', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'csv']) }}">
                                                    <i class="ri-file-list-3-line text-info align-middle me-1"></i> Descargar CSV (.csv)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" target="_blank" href="{{ route('reports.export', ['domain' => 'sales', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'pdf']) }}">
                                                    <i class="ri-file-pdf-2-line text-danger align-middle me-1"></i> Reporte PDF Ejecutivo
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                @endcan

                                @can('crear-ventas')
                                    <a href="{{ route('pos.index') }}" class="btn btn-sm btn-primary material-shadow">
                                        <i class="ri-shopping-cart-2-line align-middle me-1"></i>POS
                                    </a>
                                @endcan
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         ÁREA DE COMUNICADOS MEJORADA (Basada en Velzon Explore-Box + Filtros Rápidos)
         ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="row" id="sec-comunicados">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header align-items-center d-flex flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                        <h4 class="card-title mb-0">Centro de Comunicados y Avisos</h4>
                        @if(($postsMeta['unread_count'] ?? 0) > 0)
                            <span class="badge bg-primary-subtle text-primary">{{ $postsMeta['unread_count'] }} sin leer</span>
                        @else
                            <span class="badge bg-success-subtle text-success"><i class="ri-check-double-line align-middle me-1"></i>Al día</span>
                        @endif
                        @if(($postsMeta['urgent_unread_count'] ?? 0) > 0)
                            <span class="badge bg-danger"><i class="ri-alarm-warning-line align-middle me-1"></i>{{ $postsMeta['urgent_unread_count'] }} Urgente</span>
                        @endif
                    </div>

                    <div class="flex-shrink-0 d-flex align-items-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-soft-secondary active material-shadow-none comm-filter-btn" data-filter="all">
                                Todos ({{ $postsMeta['total_count'] ?? 0 }})
                            </button>
                            <button type="button" class="btn btn-soft-secondary material-shadow-none comm-filter-btn" data-filter="unread">
                                No leídos ({{ $postsMeta['unread_count'] ?? 0 }})
                            </button>
                            <button type="button" class="btn btn-soft-secondary material-shadow-none comm-filter-btn" data-filter="urgent">
                                Urgentes
                            </button>
                            <button type="button" class="btn btn-soft-secondary material-shadow-none comm-filter-btn" data-filter="tarea">
                                Tareas ({{ $postsMeta['tasks_count'] ?? 0 }})
                            </button>
                        </div>

                        @can('crear-comunicados')
                            <a href="{{ route('feed.create') }}" class="btn btn-soft-success btn-sm material-shadow-none">
                                <i class="ri-add-line align-middle"></i> Nuevo
                            </a>
                        @endcan
                        <a href="{{ route('feed.index') }}" class="btn btn-soft-primary btn-sm material-shadow-none">
                            Ver Todos <i class="ri-arrow-right-line align-middle"></i>
                        </a>
                        <button type="button" class="btn btn-soft-secondary btn-sm material-shadow-none" id="toggle-comm-hub" title="Contraer / Expandir">
                            <i class="ri-arrow-up-s-line align-middle" id="toggle-comm-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body" id="comm-hub-body">
                    @if($posts->isEmpty())
                        <div class="alert alert-info mb-0">
                            <i class="ri-information-line me-1 align-middle"></i> No hay comunicados recientes para mostrar.
                        </div>
                    @else
                        <div class="row">
                            @foreach($posts as $post)
                                @php
                                    $isRead = (bool) $post->is_read_by_me;
                                    $iconClass = match($post->type) {
                                        'comunicado' => 'ri-megaphone-line text-primary',
                                        'anuncio'    => 'ri-alarm-warning-line text-warning',
                                        'tarea'      => 'ri-task-line text-success',
                                        default      => 'ri-article-line text-secondary',
                                    };
                                    $bgClass = match($post->type) {
                                        'comunicado' => 'bg-primary-subtle',
                                        'anuncio'    => 'bg-warning-subtle',
                                        'tarea'      => 'bg-success-subtle',
                                        default      => 'bg-light',
                                    };
                                    $excerpt = $post->summary ?: \Illuminate\Support\Str::limit(trim(strip_tags($post->body)), 80);
                                @endphp
                                <div class="col-xl-4 col-md-6 comm-item"
                                     data-read="{{ $isRead ? 'read' : 'unread' }}"
                                     data-priority="{{ $post->priority }}"
                                     data-type="{{ $post->type }}">
                                    <div class="card card-animate border {{ !$isRead ? 'border-primary' : '' }} shadow-none mb-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar-sm {{ $bgClass }} rounded p-1">
                                                        <i class="{{ $iconClass }} fs-22 align-middle"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <div class="d-flex align-items-center gap-1 mb-1">
                                                        @if($post->pinned)
                                                            <i class="ri-pushpin-2-fill text-warning" title="Fijado"></i>
                                                        @endif
                                                        <h5 class="mb-0 fs-14 text-truncate">
                                                            <a href="{{ route('feed.show', $post) }}" class="text-body">{{ $post->title }}</a>
                                                        </h5>
                                                    </div>
                                                    <p class="text-muted text-truncate mb-0 fs-12">
                                                        {{ $post->author->name ?? 'Sistema' }} &bull; {{ $post->published_at ? $post->published_at->diffForHumans() : 'Reciente' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <p class="text-muted fs-12 mb-3 text-truncate">
                                                {{ $excerpt ?: 'Haz clic en leer para revisar el detalle completo.' }}
                                            </p>

                                            <div class="d-flex align-items-center justify-content-between pt-2 border-top border-top-dashed">
                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                    @if($post->priority === 'urgente')
                                                        <span class="badge bg-danger-subtle text-danger">URGENTE</span>
                                                    @else
                                                        <span class="badge bg-info-subtle text-info text-capitalize">{{ $post->type }}</span>
                                                    @endif

                                                    <span class="badge bg-light text-muted">
                                                        {{ $post->store ? $post->store->name : 'Global' }}
                                                    </span>

                                                    @if(!$isRead)
                                                        <span class="badge bg-primary-subtle text-primary">Nuevo</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success"><i class="ri-check-double-line"></i> Leído</span>
                                                    @endif
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <a href="{{ route('feed.show', $post) }}" class="btn btn-sm {{ !$isRead ? 'btn-primary' : 'btn-soft-success' }} material-shadow-none">
                                                        Leer <i class="ri-arrow-right-line align-middle"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         SECCIÓN 1: KPIs (Basado en Velzon Tile Boxes, CRM Widget y Project Cards)
         ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="row" id="sec-kpis">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-1">
                <h5 class="text-decoration-underline mb-0">1. Indicadores Clave de Desempeño (KPIs — {{ $ctx['period_label'] }})</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary fs-12">POS = Venta Directa</span>
                    <span class="badge bg-info-subtle text-info fs-12">Ped. Esp = Pedido Especial</span>
                </div>
            </div>
        </div>
    </div>

    {{-- FILA 1 DE KPIs: Tile Boxes Nativos de Velzon (Total Vendido, Venta Neta, Ticket Promedio, Cancelaciones) --}}
    <div class="row">
        {{-- 1. Total vendido en el mes --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Vendido ({{ $ctx['month_name'] }})</p>
                        </div>
                        <div class="flex-shrink-0">
                            @php $gSold = $kpis['total_sold']['growth_pct']; @endphp
                            <h5 class="{{ $gSold >= 0 ? 'text-success' : 'text-danger' }} fs-14 mb-0">
                                <i class="{{ $gSold >= 0 ? 'ri-arrow-right-up-line' : 'ri-arrow-right-down-line' }} fs-13 align-middle"></i>
                                {{ $gSold >= 0 ? '+' : '' }}{{ number_format($gSold, 1) }} %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-3">
                                ${{ number_format($kpis['total_sold']['total'], 2) }}
                            </h4>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-primary-subtle text-primary">POS: ${{ number_format($kpis['total_sold']['pos_total'], 2) }} ({{ $kpis['total_sold']['pos_count'] }})</span>
                                <span class="badge bg-info-subtle text-info">Ped. Esp: ${{ number_format($kpis['total_sold']['special_total'], 2) }} ({{ $kpis['total_sold']['special_count'] }})</span>
                            </div>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3 material-shadow">
                                <i class="bx bx-dollar-circle text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Venta Neta (Subtotal - Descuentos) --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Venta Neta (Subtotal - Desc)</p>
                        </div>
                        <div class="flex-shrink-0">
                            @php $gNet = $kpis['net_sales']['growth_pct']; @endphp
                            <h5 class="{{ $gNet >= 0 ? 'text-success' : 'text-danger' }} fs-14 mb-0">
                                <i class="{{ $gNet >= 0 ? 'ri-arrow-right-up-line' : 'ri-arrow-right-down-line' }} fs-13 align-middle"></i>
                                {{ $gNet >= 0 ? '+' : '' }}{{ number_format($gNet, 1) }} %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-3">
                                ${{ number_format($kpis['net_sales']['total'], 2) }}
                            </h4>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-primary-subtle text-primary">POS: ${{ number_format($kpis['net_sales']['pos_net'], 2) }}</span>
                                <span class="badge bg-info-subtle text-info">Ped. Esp: ${{ number_format($kpis['net_sales']['special_net'], 2) }}</span>
                            </div>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3 material-shadow">
                                <i class="bx bx-trending-up text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Ticket Promedio (Total) --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Ticket Promedio (Total)</p>
                        </div>
                        <div class="flex-shrink-0">
                            @php $gAvg = $kpis['avg_ticket']['growth_pct']; @endphp
                            <h5 class="{{ $gAvg >= 0 ? 'text-success' : 'text-danger' }} fs-14 mb-0">
                                <i class="{{ $gAvg >= 0 ? 'ri-arrow-right-up-line' : 'ri-arrow-right-down-line' }} fs-13 align-middle"></i>
                                {{ $gAvg >= 0 ? '+' : '' }}{{ number_format($gAvg, 1) }} %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-3">
                                ${{ number_format($kpis['avg_ticket']['total'], 2) }}
                            </h4>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-primary-subtle text-primary">POS: ${{ number_format($kpis['avg_ticket']['pos_avg'], 2) }}</span>
                                <span class="badge bg-info-subtle text-info">Ped. Esp: ${{ number_format($kpis['avg_ticket']['special_avg'], 2) }}</span>
                            </div>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3 material-shadow">
                                <i class="bx bx-receipt text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Cancelaciones (Métrica Operativa Independiente) --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Cancelaciones ({{ $kpis['cancellations']['total_count'] }})</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="{{ $kpis['cancellations']['total_count'] > 0 ? 'text-danger' : 'text-muted' }} fs-14 mb-0">
                                {{ $kpis['cancellations']['rate_pct'] }} % tasa
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-3 {{ $kpis['cancellations']['total_amount'] > 0 ? 'text-danger' : '' }}">
                                ${{ number_format($kpis['cancellations']['total_amount'], 2) }}
                            </h4>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-light text-body">POS: ${{ number_format($kpis['cancellations']['pos_amount'], 2) }} ({{ $kpis['cancellations']['pos_count'] }})</span>
                                <span class="badge bg-light text-body">Ped: ${{ number_format($kpis['cancellations']['special_amount'], 2) }} ({{ $kpis['cancellations']['special_count'] }})</span>
                            </div>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-3 material-shadow">
                                <i class="bx bx-x-circle text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILA 2 DE KPIs: CRM Widget Strip Nativo de Velzon (Cobros, Gastos, Cartera Clientes, Deuda Proveedores, Utilidad/Flujo) --}}
    <div class="row">
        <div class="col-xl-12">
            <div class="card crm-widget">
                <div class="card-body p-0">
                    <div class="row row-cols-xxl-5 row-cols-md-3 row-cols-1 g-0">
                        {{-- 5. Cobros del mes --}}
                        <div class="col border-end">
                            <div class="py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">
                                    Cobros del Mes
                                    <span class="badge {{ $kpis['collections']['growth_pct'] >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} float-end">
                                        {{ $kpis['collections']['growth_pct'] >= 0 ? '+' : '' }}{{ $kpis['collections']['growth_pct'] }}%
                                    </span>
                                </h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-hand-coin-line display-6 text-primary cfs-22"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3 overflow-hidden">
                                        <h2 class="mb-1 cfs-22">${{ number_format($kpis['collections']['gross_collected'], 2) }}</h2>
                                        <p class="text-muted mb-0 fs-12 text-truncate">
                                            Com. Terminal: <span class="text-danger fw-medium">-${{ number_format($kpis['collections']['terminal_commissions'], 2) }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 6. Gastos del mes --}}
                        <div class="col border-end">
                            <div class="mt-3 mt-md-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">
                                    Gastos del Mes
                                    <span class="badge bg-warning-subtle text-warning float-end">{{ $kpis['expenses']['count'] }} reg.</span>
                                </h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-wallet-3-line display-6 text-warning cfs-22"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3 overflow-hidden">
                                        <h2 class="mb-1 cfs-22">${{ number_format($kpis['expenses']['total_amount'], 2) }}</h2>
                                        <p class="text-muted mb-0 fs-12 text-truncate">
                                            Oper: ${{ number_format($kpis['expenses']['operational_amount'], 0) }} | Prov: ${{ number_format($kpis['expenses']['supplier_amount'], 0) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 7. Cartera de clientes --}}
                        <div class="col border-end">
                            <div class="mt-3 mt-md-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">
                                    Cartera de Clientes
                                    <span class="badge bg-info-subtle text-info float-end">{{ $kpis['receivables']['customers_with_debt'] }} c/saldo</span>
                                </h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-account-pin-box-line display-6 text-info cfs-22"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3 overflow-hidden">
                                        <h2 class="mb-1 cfs-22">${{ number_format($kpis['receivables']['total_balance'], 2) }}</h2>
                                        <p class="text-muted mb-0 fs-12 text-truncate">
                                            POS: ${{ number_format($kpis['receivables']['pos_balance'], 0) }} | Ped: ${{ number_format($kpis['receivables']['special_balance'], 0) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 8. Deuda con proveedores --}}
                        <div class="col border-end">
                            <div class="mt-3 mt-lg-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">
                                    Deuda Proveedores
                                    <span class="badge bg-danger-subtle text-danger float-end">{{ $kpis['payables']['pending_orders_count'] }} OC</span>
                                </h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-building-4-line display-6 text-danger cfs-22"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3 overflow-hidden">
                                        <h2 class="mb-1 cfs-22">${{ number_format($kpis['payables']['total_debt'], 2) }}</h2>
                                        <p class="text-muted mb-0 fs-12 text-truncate">
                                            Pagado mes: <span class="text-success fw-medium">${{ number_format($kpis['payables']['paid_this_month'], 2) }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Extra 2: Utilidad Bruta y Flujo de Caja Neto --}}
                        <div class="col">
                            <div class="mt-3 mt-lg-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">
                                    Utilidad Bruta Est.
                                    <span class="badge bg-success-subtle text-success float-end">{{ $kpis['profitability']['gross_margin_pct'] }}% Mg</span>
                                </h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-funds-box-line display-6 text-success cfs-22"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3 overflow-hidden">
                                        <h2 class="mb-1 cfs-22">${{ number_format($kpis['profitability']['gross_profit'], 2) }}</h2>
                                        <p class="text-muted mb-0 fs-12 text-truncate">
                                            Flujo Neto: <span class="fw-semibold {{ $kpis['profitability']['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($kpis['profitability']['net_cash_flow'], 2) }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILA 3 DE KPIs: Récords y Liderazgo del Mes (Basado en Analytics Cards de widgets.blade.php) --}}
    <div class="row">
        {{-- 9. Venta más alta --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="overflow-hidden">
                            <p class="fw-medium text-muted mb-0">Venta Más Alta del Mes</p>
                            <h2 class="mt-3 ff-secondary cfs-22 fw-semibold">
                                ${{ number_format($kpis['highest_sale']['amount'], 2) }}
                            </h2>
                            <p class="mb-0 text-muted text-truncate">
                                @if($kpis['highest_sale']['type'] === 'pos')
                                    <span class="badge bg-primary-subtle text-primary mb-0">Venta POS</span>
                                @elseif($kpis['highest_sale']['type'] === 'special')
                                    <span class="badge bg-info-subtle text-info mb-0">Pedido Especial</span>
                                @else
                                    <span class="badge bg-light text-muted mb-0">Sin ventas</span>
                                @endif
                                @if($kpis['highest_sale']['url'] !== '#')
                                    <a href="{{ $kpis['highest_sale']['url'] }}" class="text-decoration-underline ms-1 fw-medium">{{ $kpis['highest_sale']['folio'] }}</a>
                                @endif
                            </p>
                        </div>
                        <div class="flex-shrink-0 ms-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle rounded-circle fs-2 material-shadow">
                                    <i class="bx bx-crown text-warning"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 10. Mejor vendedor del mes --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="overflow-hidden">
                            <p class="fw-medium text-muted mb-0">Mejor Vendedor del Mes</p>
                            <h4 class="mt-3 fw-semibold text-truncate">{{ $kpis['best_seller']['name'] }}</h4>
                            <p class="mb-0 text-muted text-truncate">
                                <span class="badge bg-light text-success mb-0">
                                    ${{ number_format($kpis['best_seller']['total_amount'], 2) }}
                                </span>
                                <span class="fs-12 ms-1">({{ $kpis['best_seller']['ops_count'] }} oper &bull; {{ $kpis['best_seller']['share_pct'] }}%)</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0 ms-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-success-subtle rounded-circle fs-2 material-shadow">
                                    <i class="bx bx-medal text-success"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 11. Mejor Sucursal del mes --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="overflow-hidden">
                            <p class="fw-medium text-muted mb-0">Mejor Sucursal del Mes</p>
                            <h4 class="mt-3 fw-semibold text-truncate">{{ $kpis['best_store']['name'] }}</h4>
                            <p class="mb-0 text-muted text-truncate">
                                <span class="badge bg-light text-primary mb-0">
                                    ${{ number_format($kpis['best_store']['total_amount'], 2) }}
                                </span>
                                <span class="fs-12 ms-1">({{ $kpis['best_store']['share_pct'] }}% del global)</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0 ms-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle rounded-circle fs-2 material-shadow">
                                    <i class="bx bx-store-alt text-primary"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Extra 5: Embudo de Conversión de Cotizaciones --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="overflow-hidden">
                            <p class="fw-medium text-muted mb-0">Conversión Cotizaciones</p>
                            <h2 class="mt-3 ff-secondary cfs-22 fw-semibold">
                                {{ $kpis['quotes_funnel']['conversion_rate_pct'] }}%
                            </h2>
                            <p class="mb-0 text-muted text-truncate">
                                <span class="badge bg-light text-info mb-0">
                                    {{ $kpis['quotes_funnel']['converted_quotes'] }} de {{ $kpis['quotes_funnel']['total_quotes'] }}
                                </span>
                                <span class="fs-12 ms-1">Abiertas: ${{ number_format($kpis['quotes_funnel']['open_quotes_amount'], 0) }}</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0 ms-2">
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle rounded-circle fs-2 material-shadow">
                                    <i class="bx bx-filter-alt text-info"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILA 4 DE KPIs: Logística e Inventario (Basado en dashboard-projects.blade.php lines 17-89) --}}
    <div class="row">
        {{-- 12. Valor del inventario --}}
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle text-info rounded-2 fs-2 material-shadow">
                                <i class="bx bx-package text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 overflow-hidden ms-3">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-2">Valor del Inventario (Costo)</p>
                            <div class="d-flex align-items-center mb-2">
                                <h4 class="fs-4 flex-grow-1 mb-0">${{ number_format($kpis['inventory_value']['cost_value'], 2) }}</h4>
                                <span class="badge bg-info-subtle text-info fs-12">{{ number_format($kpis['inventory_value']['total_units']) }} uds</span>
                            </div>
                            <p class="text-muted text-truncate mb-0 fs-12">
                                Comercial: ~${{ number_format($kpis['inventory_value']['retail_value'], 2) }} &bull; Disp: {{ number_format($kpis['inventory_value']['available_units']) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 13. Pedidos pendientes de entrega --}}
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-2 fs-2 material-shadow">
                                <i class="bx bxs-truck text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 overflow-hidden ms-3">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-2">Pedidos Pendientes de Entrega</p>
                            <div class="d-flex align-items-center mb-2">
                                <h4 class="fs-4 flex-grow-1 mb-0">{{ $kpis['pending_deliveries']['total_count'] }} entregas</h4>
                                <span class="badge bg-primary-subtle text-primary fs-12">{{ $kpis['pending_deliveries']['in_route'] }} en ruta</span>
                            </div>
                            <p class="text-muted text-truncate mb-0 fs-12">
                                Envíos POS: <b>{{ $kpis['pending_deliveries']['pos_count'] }}</b> &bull; Pedidos Especiales: <b>{{ $kpis['pending_deliveries']['special_count'] }}</b>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 14. Entregas con retraso --}}
        <div class="col-xl-4 col-md-12">
            @php $hasOverdue = $kpis['overdue_deliveries']['total_count'] > 0; @endphp
            <div class="card card-animate {{ $hasOverdue ? 'border border-danger' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title {{ $hasOverdue ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} rounded-2 fs-2 material-shadow">
                                <i class="bx {{ $hasOverdue ? 'bx-error-circle text-danger' : 'bx-check-shield text-success' }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 overflow-hidden ms-3">
                            <p class="text-uppercase fw-medium {{ $hasOverdue ? 'text-danger' : 'text-muted' }} text-truncate mb-2">Entregas con Retraso</p>
                            <div class="d-flex align-items-center mb-2">
                                <h4 class="fs-4 flex-grow-1 mb-0 {{ $hasOverdue ? 'text-danger' : 'text-success' }}">
                                    {{ $kpis['overdue_deliveries']['total_count'] }} {{ $hasOverdue ? 'vencidas' : 'sin retrasos' }}
                                </h4>
                                <span class="badge {{ $hasOverdue ? 'bg-danger text-white' : 'bg-success-subtle text-success' }} fs-12">
                                    {{ $hasOverdue ? 'Urgente' : 'En tiempo' }}
                                </span>
                            </div>
                            <p class="text-muted text-truncate mb-0 fs-12">
                                POS atrasados: <b>{{ $kpis['overdue_deliveries']['pos_count'] }}</b> &bull; Ped. Especiales atrasados: <b>{{ $kpis['overdue_deliveries']['special_count'] }}</b>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         SECCIÓN 2: REPORTE DE VENTAS (5 Gráficas Requeridas + Extra 4)
         ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="row" id="sec-charts">
        <div class="col-12">
            <h5 class="text-decoration-underline mb-3 mt-2 pb-1">2. Reporte Analítico de Ventas</h5>
        </div>
    </div>

    {{-- FILA 1: Ventas Mensuales del Año en Curso (8 col) + Ventas Anuales (4 col) --}}
    <div class="row">
        {{-- Gráfica 2: Ventas mensuales del año en curso (Estilo Projects Overview de dashboard-projects.blade.php) --}}
        <div class="col-xxl-8 col-xl-7">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Ventas Mensuales del Año en Curso ({{ $ctx['year'] }})</h4>
                    <div class="flex-shrink-0">
                        <span class="badge bg-primary-subtle text-primary">Ventas POS</span>
                        <span class="badge bg-info-subtle text-info ms-1">Pedidos Especiales</span>
                        <span class="badge bg-success-subtle text-success ms-1">Cobros</span>
                    </div>
                </div>

                <div class="card-header p-0 border-0 bg-light-subtle">
                    <div class="row g-0 text-center">
                        <div class="col-6 col-sm-3">
                            <div class="p-3 border border-dashed border-start-0">
                                <h5 class="mb-1 text-primary">${{ number_format(array_sum($charts['monthly_sales']['pos_series']), 2) }}</h5>
                                <p class="text-muted mb-0 fs-12">Acumulado POS ({{ $ctx['year'] }})</p>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 border border-dashed border-start-0">
                                <h5 class="mb-1 text-info">${{ number_format(array_sum($charts['monthly_sales']['special_series']), 2) }}</h5>
                                <p class="text-muted mb-0 fs-12">Acumulado Ped. Especiales</p>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 border border-dashed border-start-0">
                                <h5 class="mb-1 text-body">${{ number_format(array_sum($charts['monthly_sales']['net_series']), 2) }}</h5>
                                <p class="text-muted mb-0 fs-12">Venta Neta Anual</p>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 border border-dashed border-start-0 border-end-0">
                                <h5 class="mb-1 text-success">${{ number_format(array_sum($charts['monthly_sales']['collections_series']), 2) }}</h5>
                                <p class="text-muted mb-0 fs-12">Cobros Anuales</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 pb-2">
                    <div id="chart-monthly-sales"
                         data-colors='["--vz-primary", "--vz-info", "--vz-success"]'
                         class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>

        {{-- Gráfica 1: Ventas Anuales --}}
        <div class="col-xxl-4 col-xl-5">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Ventas Anuales (Histórico)</h4>
                </div>
                <div class="card-body">
                    <div id="chart-annual-sales"
                         data-colors='["--vz-primary", "--vz-info", "--vz-success"]'
                         class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILA 2: Dona Comparativa por Producto (4 col) + Ingreso por Sucursal (4 col) + Ventas por Vendedor (4 col) --}}
    <div class="row">
        {{-- Gráfica 3: Ventas del mes actual por producto (Dona) --}}
        <div class="col-xl-4 col-lg-6">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Ventas del Mes por Producto</h4>
                    <div class="flex-shrink-0">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-soft-primary active material-shadow-none" id="btn-prod-amount" onclick="switchProductDonutMode('amount')">Monto $</button>
                            <button type="button" class="btn btn-soft-primary material-shadow-none" id="btn-prod-qty" onclick="switchProductDonutMode('qty')">Uds #</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="chart-products-donut"
                         data-colors='["--vz-primary", "--vz-success", "--vz-info", "--vz-warning", "--vz-danger", "--vz-secondary"]'
                         class="apex-charts" dir="ltr"></div>

                    <div class="table-responsive table-card mt-3">
                        <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted border-dashed border border-start-0 border-end-0 bg-light-subtle">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Uds</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody class="border-0">
                                @forelse(array_slice($charts['products_donut']['breakdown'], 0, 4) as $prodItem)
                                    <tr>
                                        <td class="text-truncate" style="max-width: 170px;">{{ $prodItem['name'] }}</td>
                                        <td class="text-center text-muted">{{ $prodItem['total_qty'] }}</td>
                                        <td class="text-end fw-medium">${{ number_format($prodItem['total_amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-2">Sin ventas de productos en este mes</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráfica 4: Ingreso del mes por sucursal --}}
        <div class="col-xl-4 col-lg-6">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Ingreso del Mes por Sucursal</h4>
                </div>
                <div class="card-body">
                    <div id="chart-store-revenue"
                         data-colors='["--vz-primary", "--vz-info"]'
                         class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>

        {{-- Gráfica 5: Ventas del mes por vendedor --}}
        <div class="col-xl-4 col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Ventas del Mes por Vendedor</h4>
                </div>
                <div class="card-body">
                    <div id="chart-seller-sales"
                         data-colors='["--vz-success", "--vz-info"]'
                         class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILA 3 (EXTRA 4): Ingresos por Método de Pago y Comisiones de Terminal --}}
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header align-items-center d-flex flex-wrap gap-2">
                    <h4 class="card-title mb-0 flex-grow-1">Ingresos por Método de Pago y Comisiones Bancarias ({{ $ctx['period_label'] }})</h4>
                    <div class="flex-shrink-0">
                        <span class="badge bg-danger-subtle text-danger fs-12">
                            Comisiones de Terminal del Mes: -${{ number_format($kpis['collections']['terminal_commissions'], 2) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-5">
                            <div id="chart-payment-methods"
                                 data-colors='["--vz-primary", "--vz-danger"]'
                                 class="apex-charts" dir="ltr"></div>
                        </div>
                        <div class="col-lg-7">
                            <div class="table-responsive table-card">
                                <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-muted">
                                            <th>Método de Pago</th>
                                            <th class="text-center">Operaciones</th>
                                            <th class="text-end">Monto Cobrado</th>
                                            <th class="text-end">Comisión Terminal</th>
                                            <th class="text-end">Total Neto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($charts['payment_methods']['breakdown'] as $pmRow)
                                            <tr>
                                                <td class="fw-medium">{{ $pmRow['name'] }}</td>
                                                <td class="text-center">{{ $pmRow['count'] }}</td>
                                                <td class="text-end fw-semibold">${{ number_format($pmRow['amount'], 2) }}</td>
                                                <td class="text-end text-danger">-${{ number_format($pmRow['commission'], 2) }}</td>
                                                <td class="text-end text-success fw-semibold">${{ number_format(max(0, $pmRow['amount'] - $pmRow['commission']), 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Sin cobros registrados en este período.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         SECCIÓN 3: TABLERO OPERATIVO (4 Tablas Requeridas + Extra 3 en Velzon Table-Card)
         ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="row" id="sec-tablero">
        <div class="col-12">
            <h5 class="text-decoration-underline mb-3 mt-2 pb-1">3. Tablero Operativo y Financiero ({{ $ctx['period_label'] }})</h5>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header align-items-center d-flex flex-wrap gap-2 border-bottom-dashed">
                    <ul class="nav nav-pills card-header-pills flex-grow-1 gap-1" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-btn-sales" data-bs-toggle="tab" data-bs-target="#tab-pane-sales" type="button" role="tab">
                                <i class="ri-shopping-bag-3-line align-middle me-1"></i>Ventas del Mes
                                <span class="badge bg-white text-primary ms-1">{{ $tables['sales']['totals']['count'] }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-btn-deliveries" data-bs-toggle="tab" data-bs-target="#tab-pane-deliveries" type="button" role="tab">
                                <i class="ri-truck-line align-middle me-1"></i>Pedidos por Entregar
                                <span class="badge {{ $kpis['overdue_deliveries']['total_count'] > 0 ? 'bg-danger text-white' : 'bg-info-subtle text-info' }} ms-1">
                                    {{ $tables['deliveries']['totals']['count'] }}
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-btn-payments" data-bs-toggle="tab" data-bs-target="#tab-pane-payments" type="button" role="tab">
                                <i class="ri-hand-coin-line align-middle me-1"></i>Pagos de Clientes
                                <span class="badge bg-success-subtle text-success ms-1">{{ $tables['customer_payments']['totals']['count'] }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-btn-expenses" data-bs-toggle="tab" data-bs-target="#tab-pane-expenses" type="button" role="tab">
                                <i class="ri-wallet-3-line align-middle me-1"></i>Gastos Empresariales
                                <span class="badge bg-warning-subtle text-warning ms-1">{{ $tables['expenses']['totals']['count'] }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-btn-alerts" data-bs-toggle="tab" data-bs-target="#tab-pane-alerts" type="button" role="tab">
                                <i class="ri-alert-line align-middle me-1"></i>Stock Crítico y Traspasos
                                @if(($tables['inventory_alerts']['alerts_count'] ?? 0) > 0)
                                    <span class="badge bg-danger-subtle text-danger ms-1">{{ $tables['inventory_alerts']['alerts_count'] }}</span>
                                @endif
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        {{-- ── TABLA 1: VENTAS DEL MES CON PIE DE SUMATORIA DE SALDOS DEUDORES Y TOTALES ── --}}
                        <div class="tab-pane fade show active" id="tab-pane-sales" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-soft-primary active material-shadow-none sales-type-filter" data-type="all">
                                        Todos ({{ $tables['sales']['totals']['count'] }})
                                    </button>
                                    <button type="button" class="btn btn-soft-primary material-shadow-none sales-type-filter" data-type="pos">
                                        Solo Ventas POS (${{ number_format($tables['sales']['totals']['pos_sum_total'], 2) }})
                                    </button>
                                    <button type="button" class="btn btn-soft-info material-shadow-none sales-type-filter" data-type="special">
                                        Solo Pedidos Especiales (${{ number_format($tables['sales']['totals']['special_sum_total'], 2) }})
                                    </button>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @can('exportar-reportes')
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('reports.export', ['domain' => 'sales', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'excel']) }}"
                                               class="btn btn-soft-success material-shadow-none" title="Descargar Ventas en Excel">
                                                <i class="ri-file-excel-2-line align-middle me-1"></i>Excel
                                            </a>
                                            <a href="{{ route('reports.export', ['domain' => 'sales', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'csv']) }}"
                                               class="btn btn-soft-info material-shadow-none" title="Descargar Ventas en CSV">
                                                <i class="ri-file-list-3-line align-middle me-1"></i>CSV
                                            </a>
                                            <a href="{{ route('reports.export', ['domain' => 'sales', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'pdf']) }}"
                                               target="_blank"
                                               class="btn btn-soft-danger material-shadow-none" title="Abrir PDF Ejecutivo de Ventas">
                                                <i class="ri-file-pdf-2-line align-middle me-1"></i>PDF
                                            </a>
                                        </div>
                                    @endcan
                                    @can('ver-reportes')
                                        <a href="{{ route('reports.index', ['domain' => 'sales', 'month' => $ctx['month'], 'year' => $ctx['year']]) }}"
                                           class="btn btn-sm btn-soft-secondary material-shadow-none" title="Personalizar columnas y agrupación en BI">
                                            <i class="ri-equalizer-line align-middle"></i>
                                        </a>
                                    @endcan
                                    <div class="search-box" style="min-width: 240px;">
                                        <input type="text" class="form-control form-control-sm" id="search-sales-table" placeholder="Buscar folio, cliente, vendedor...">
                                        <i class="ri-search-line search-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive table-card">
                                <table class="table table-hover table-nowrap align-middle mb-0" id="table-monthly-sales">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th scope="col">Folio</th>
                                            <th scope="col">Tipo</th>
                                            <th scope="col">Fecha</th>
                                            <th scope="col">Cliente</th>
                                            <th scope="col">Vendedor</th>
                                            <th scope="col">Sucursal</th>
                                            <th scope="col" class="text-center">Estado</th>
                                            <th scope="col" class="text-end">Venta Neta</th>
                                            <th scope="col" class="text-end">Total Venta</th>
                                            <th scope="col" class="text-end">Saldo Deudor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tables['sales']['rows'] as $sRow)
                                            <tr class="sales-table-row" data-sale-type="{{ $sRow['type'] }}">
                                                <td class="fw-medium">
                                                    <a href="{{ $sRow['url'] }}" class="fw-semibold link-primary">{{ $sRow['folio'] }}</a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $sRow['type_badge'] }}-subtle text-{{ $sRow['type_badge'] }}">
                                                        {{ $sRow['type_label'] }}
                                                    </span>
                                                </td>
                                                <td class="text-muted">{{ $sRow['date_formatted'] }}</td>
                                                <td>{{ $sRow['customer_name'] }}</td>
                                                <td>{{ $sRow['seller_name'] }}</td>
                                                <td>{{ $sRow['store_name'] }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $sRow['status_color'] }}-subtle text-{{ $sRow['status_color'] }}">
                                                        {{ $sRow['status_label'] }}
                                                    </span>
                                                </td>
                                                <td class="text-end text-muted">${{ number_format($sRow['net_amount'], 2) }}</td>
                                                <td class="text-end fw-semibold">
                                                    @if($sRow['is_cancelled'])
                                                        <del class="text-danger">${{ number_format($sRow['total'], 2) }}</del>
                                                    @else
                                                        ${{ number_format($sRow['total'], 2) }}
                                                    @endif
                                                </td>
                                                <td class="text-end {{ $sRow['balance'] > 0 ? 'text-warning fw-bold' : 'text-muted' }}">
                                                    ${{ number_format($sRow['balance'], 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">No hay ventas registradas en {{ $ctx['period_label'] }}.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td colspan="7" class="text-end text-uppercase">
                                                Sumatoria de Ventas Activas (POS: ${{ number_format($tables['sales']['totals']['pos_sum_total'], 2) }} | Ped. Especial: ${{ number_format($tables['sales']['totals']['special_sum_total'], 2) }}):
                                            </td>
                                            <td class="text-end text-success">${{ number_format($tables['sales']['totals']['sum_net'], 2) }}</td>
                                            <td class="text-end text-primary fs-15">${{ number_format($tables['sales']['totals']['sum_total'], 2) }}</td>
                                            <td class="text-end text-warning fs-15">${{ number_format($tables['sales']['totals']['sum_balance'], 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- ── TABLA 2: PEDIDOS POR ENTREGAR CON "DÍAS RESTANTES" ── --}}
                        <div class="tab-pane fade" id="tab-pane-deliveries" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-danger-subtle text-danger">Retrasado</span>
                                    <span class="badge bg-warning-subtle text-warning">Entrega Hoy</span>
                                    <span class="badge bg-info-subtle text-info">Próximos 1–3 días</span>
                                    <span class="badge bg-success-subtle text-success">En tiempo (+3 días)</span>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @can('exportar-reportes')
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('reports.export', ['domain' => 'sellers', 'status' => 'pending_delivery', 'date_preset' => 'this_year', 'format' => 'excel']) }}"
                                               class="btn btn-soft-success material-shadow-none">
                                                <i class="ri-file-excel-2-line align-middle me-1"></i>Excel
                                            </a>
                                            <a href="{{ route('reports.export', ['domain' => 'sellers', 'status' => 'pending_delivery', 'date_preset' => 'this_year', 'format' => 'csv']) }}"
                                               class="btn btn-soft-info material-shadow-none">
                                                <i class="ri-file-list-3-line align-middle me-1"></i>CSV
                                            </a>
                                            <a href="{{ route('reports.export', ['domain' => 'sellers', 'status' => 'pending_delivery', 'date_preset' => 'this_year', 'format' => 'pdf']) }}"
                                               target="_blank"
                                               class="btn btn-soft-danger material-shadow-none">
                                                <i class="ri-file-pdf-2-line align-middle me-1"></i>PDF
                                            </a>
                                        </div>
                                    @endcan
                                    <div class="search-box" style="min-width: 240px;">
                                        <input type="text" class="form-control form-control-sm" id="search-deliveries-table" placeholder="Buscar pedido, cliente, dirección...">
                                        <i class="ri-search-line search-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive table-card">
                                <table class="table table-hover table-nowrap align-middle mb-0" id="table-deliveries">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th scope="col">Folio</th>
                                            <th scope="col">Tipo</th>
                                            <th scope="col">Cliente / Destinatario</th>
                                            <th scope="col">Dirección / Modalidad</th>
                                            <th scope="col">Sucursal</th>
                                            <th scope="col">Fecha Programada</th>
                                            <th scope="col" class="text-center">Estado</th>
                                            <th scope="col" class="text-center">Días Restantes</th>
                                            <th scope="col" class="text-end">Total Pedido</th>
                                            <th scope="col" class="text-end">Saldo Deudor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tables['deliveries']['rows'] as $dRow)
                                            <tr>
                                                <td>
                                                    <a href="{{ $dRow['url'] }}" class="fw-semibold link-primary">{{ $dRow['folio'] }}</a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $dRow['type_badge'] }}-subtle text-{{ $dRow['type_badge'] }}">
                                                        {{ $dRow['type_label'] }}
                                                    </span>
                                                </td>
                                                <td class="fw-medium">{{ $dRow['customer_name'] }}</td>
                                                <td class="text-muted text-truncate" style="max-width: 220px;" title="{{ $dRow['address'] }}">
                                                    {{ $dRow['address'] }}
                                                </td>
                                                <td>{{ $dRow['store_name'] }}</td>
                                                <td>
                                                    {{ $dRow['scheduled_date'] }}
                                                    @if($dRow['scheduled_time'])
                                                        <small class="text-muted ms-1">({{ $dRow['scheduled_time'] }})</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $dRow['logistics_color'] }}-subtle text-{{ $dRow['logistics_color'] }}">
                                                        {{ $dRow['logistics_status'] }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge {{ $dRow['days_color'] === 'danger' ? 'bg-danger text-white' : 'bg-' . $dRow['days_color'] . '-subtle text-' . $dRow['days_color'] }}">
                                                        {{ $dRow['days_label'] }}
                                                    </span>
                                                </td>
                                                <td class="text-end fw-semibold">${{ number_format($dRow['total'], 2) }}</td>
                                                <td class="text-end {{ $dRow['balance'] > 0 ? 'text-warning fw-bold' : 'text-muted' }}">
                                                    ${{ number_format($dRow['balance'], 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">No hay pedidos pendientes de entrega.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td colspan="8" class="text-end text-uppercase">Sumatoria de Pedidos por Entregar ({{ $tables['deliveries']['totals']['count'] }} registros):</td>
                                            <td class="text-end text-primary fs-15">${{ number_format($tables['deliveries']['totals']['sum_total'], 2) }}</td>
                                            <td class="text-end text-warning fs-15">${{ number_format($tables['deliveries']['totals']['sum_balance'], 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- ── TABLA 3: PAGOS DEL MES EJECUTADOS POR CLIENTES (MONTOS, COMISIONES Y TOTALES) ── --}}
                        <div class="tab-pane fade" id="tab-pane-payments" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <span class="text-muted fs-13">Pagos de contado, abonos a apartados POS y anticipos de Pedidos Especiales del mes.</span>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @can('exportar-reportes')
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('reports.export', ['domain' => 'collections', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'excel']) }}"
                                               class="btn btn-soft-success material-shadow-none">
                                                <i class="ri-file-excel-2-line align-middle me-1"></i>Excel
                                            </a>
                                            <a href="{{ route('reports.export', ['domain' => 'collections', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'csv']) }}"
                                               class="btn btn-soft-info material-shadow-none">
                                                <i class="ri-file-list-3-line align-middle me-1"></i>CSV
                                            </a>
                                            <a href="{{ route('reports.export', ['domain' => 'collections', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'pdf']) }}"
                                               target="_blank"
                                               class="btn btn-soft-danger material-shadow-none">
                                                <i class="ri-file-pdf-2-line align-middle me-1"></i>PDF
                                            </a>
                                        </div>
                                    @endcan
                                    <div class="search-box" style="min-width: 240px;">
                                        <input type="text" class="form-control form-control-sm" id="search-payments-table" placeholder="Buscar folio, cliente, método...">
                                        <i class="ri-search-line search-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive table-card">
                                <table class="table table-hover table-nowrap align-middle mb-0" id="table-customer-payments">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th scope="col">Fecha / Hora</th>
                                            <th scope="col">Folio Origen</th>
                                            <th scope="col">Tipo</th>
                                            <th scope="col">Cliente</th>
                                            <th scope="col">Cajero</th>
                                            <th scope="col">Sucursal</th>
                                            <th scope="col">Método / Terminal</th>
                                            <th scope="col" class="text-end">Monto</th>
                                            <th scope="col" class="text-end">Comisión</th>
                                            <th scope="col" class="text-end">Total Neto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tables['customer_payments']['rows'] as $pRow)
                                            <tr>
                                                <td class="text-muted">{{ $pRow['date_formatted'] }}</td>
                                                <td>
                                                    <a href="{{ $pRow['url'] }}" class="fw-semibold link-primary">{{ $pRow['folio'] }}</a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $pRow['origin_badge'] }}-subtle text-{{ $pRow['origin_badge'] }}">
                                                        {{ $pRow['origin_label'] }}
                                                    </span>
                                                </td>
                                                <td class="fw-medium">{{ $pRow['customer_name'] }}</td>
                                                <td>{{ $pRow['cashier_name'] }}</td>
                                                <td>{{ $pRow['store_name'] }}</td>
                                                <td>
                                                    {{ $pRow['method_name'] }}
                                                    @if($pRow['terminal_name'])
                                                        <small class="text-muted">({{ $pRow['terminal_name'] }})</small>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-semibold">${{ number_format($pRow['amount'], 2) }}</td>
                                                <td class="text-end {{ $pRow['commission'] > 0 ? 'text-danger' : 'text-muted' }}">
                                                    -${{ number_format($pRow['commission'], 2) }}
                                                </td>
                                                <td class="text-end fw-bold text-success">${{ number_format($pRow['net_total'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">No hay pagos registrados en {{ $ctx['period_label'] }}.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td colspan="7" class="text-end text-uppercase">Sumatoria de Pagos del Mes ({{ $tables['customer_payments']['totals']['count'] }} transacciones):</td>
                                            <td class="text-end text-primary fs-15">${{ number_format($tables['customer_payments']['totals']['sum_amount'], 2) }}</td>
                                            <td class="text-end text-danger fs-15">-${{ number_format($tables['customer_payments']['totals']['sum_commission'], 2) }}</td>
                                            <td class="text-end text-success fs-15">${{ number_format($tables['customer_payments']['totals']['sum_total'], 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- ── TABLA 4: GASTOS EMPRESARIALES DEL MES CON SUMATORIA DE TOTALES ── --}}
                        <div class="tab-pane fade" id="tab-pane-expenses" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div>
                                    @can('crear-gastos')
                                        <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-soft-primary material-shadow-none">
                                            <i class="ri-add-line align-middle me-1"></i>Administrar Gastos
                                        </a>
                                    @endcan
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @can('exportar-reportes')
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('reports.export', ['domain' => 'expenses', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'excel']) }}"
                                               class="btn btn-soft-success material-shadow-none">
                                                <i class="ri-file-excel-2-line align-middle me-1"></i>Excel
                                            </a>
                                            <a href="{{ route('reports.export', ['domain' => 'expenses', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'csv']) }}"
                                               class="btn btn-soft-info material-shadow-none">
                                                <i class="ri-file-list-3-line align-middle me-1"></i>CSV
                                            </a>
                                            <a href="{{ route('reports.export', ['domain' => 'expenses', 'month' => $ctx['month'], 'year' => $ctx['year'], 'format' => 'pdf']) }}"
                                               target="_blank"
                                               class="btn btn-soft-danger material-shadow-none">
                                                <i class="ri-file-pdf-2-line align-middle me-1"></i>PDF
                                            </a>
                                        </div>
                                    @endcan
                                    <div class="search-box" style="min-width: 240px;">
                                        <input type="text" class="form-control form-control-sm" id="search-expenses-table" placeholder="Buscar concepto, sucursal, usuario...">
                                        <i class="ri-search-line search-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive table-card">
                                <table class="table table-hover table-nowrap align-middle mb-0" id="table-expenses">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th scope="col">Fecha</th>
                                            <th scope="col">Concepto</th>
                                            <th scope="col">Categoría</th>
                                            <th scope="col">Sucursal</th>
                                            <th scope="col">Registrado Por</th>
                                            <th scope="col">Notas</th>
                                            <th scope="col" class="text-end">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tables['expenses']['rows'] as $eRow)
                                            <tr>
                                                <td class="text-muted">{{ $eRow['date_formatted'] }}</td>
                                                <td class="fw-medium">{{ $eRow['concept'] }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $eRow['category_color'] }}-subtle text-{{ $eRow['category_color'] }}">
                                                        {{ $eRow['category_label'] }}
                                                    </span>
                                                </td>
                                                <td>{{ $eRow['store_name'] }}</td>
                                                <td>{{ $eRow['user_name'] }}</td>
                                                <td class="text-muted text-truncate" style="max-width: 240px;">{{ $eRow['notes'] }}</td>
                                                <td class="text-end fw-semibold text-danger">${{ number_format($eRow['amount'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">No se registraron gastos empresariales en {{ $ctx['period_label'] }}.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td colspan="6" class="text-end text-uppercase">Sumatoria Total de Gastos Empresariales ({{ $tables['expenses']['totals']['count'] }} registros):</td>
                                            <td class="text-end text-danger fs-15">${{ number_format($tables['expenses']['totals']['sum_total'], 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- ── TABLA 5 (EXTRA 3): ALERTAS DE STOCK CRÍTICO Y TRASPASOS ── --}}
                        <div class="tab-pane fade" id="tab-pane-alerts" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-xl-7">
                                    <h6 class="fw-semibold mb-3 text-danger">
                                        <i class="ri-error-warning-line align-middle me-1"></i>Productos en o por Debajo del Stock Mínimo
                                    </h6>
                                    <div class="table-responsive table-card">
                                        <table class="table table-hover table-nowrap align-middle mb-0">
                                            <thead class="table-light text-muted">
                                                <tr>
                                                    <th>SKU</th>
                                                    <th>Producto</th>
                                                    <th>Sucursal / Bodega</th>
                                                    <th class="text-center">Existencia</th>
                                                    <th class="text-center">Mínimo</th>
                                                    <th class="text-center">Déficit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($tables['inventory_alerts']['stock_alerts'] as $alRow)
                                                    <tr>
                                                        <td class="text-muted">{{ $alRow['sku'] }}</td>
                                                        <td class="fw-medium">{{ $alRow['product_name'] }}</td>
                                                        <td>{{ $alRow['store_name'] }} <small class="text-muted">({{ $alRow['warehouse_name'] }})</small></td>
                                                        <td class="text-center fw-bold {{ $alRow['quantity'] <= 0 ? 'text-danger' : 'text-warning' }}">{{ $alRow['quantity'] }}</td>
                                                        <td class="text-center text-muted">{{ $alRow['min_stock'] }}</td>
                                                        <td class="text-center">
                                                            <span class="badge bg-danger-subtle text-danger">-{{ $alRow['deficit'] }} uds</span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-4">Sin alertas de stock mínimo activas.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-xl-5">
                                    <h6 class="fw-semibold mb-3 text-info">
                                        <i class="ri-arrow-left-right-line align-middle me-1"></i>Traspasos Activos entre Sucursales
                                    </h6>
                                    <div class="table-responsive table-card">
                                        <table class="table table-hover table-nowrap align-middle mb-0">
                                            <thead class="table-light text-muted">
                                                <tr>
                                                    <th>Folio</th>
                                                    <th>Ruta</th>
                                                    <th class="text-center">Ítems</th>
                                                    <th class="text-center">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($tables['inventory_alerts']['active_transfers'] as $tRow)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ $tRow['url'] }}" class="fw-semibold link-primary">{{ $tRow['folio'] }}</a>
                                                        </td>
                                                        <td>{{ $tRow['origin_store'] }} &rarr; <b>{{ $tRow['destination_store'] }}</b></td>
                                                        <td class="text-center">{{ $tRow['items_count'] }}</td>
                                                        <td class="text-center">
                                                            <span class="badge bg-{{ $tRow['status_color'] }}-subtle text-{{ $tRow['status_color'] }}">
                                                                {{ $tRow['status_label'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted py-4">No hay traspasos en tránsito.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/swiper/swiper-bundle.min.js') }}"></script>

    <script>
        // Helper nativo de Velzon para obtener los colores desde variables CSS (data-colors)
        function getChartColorsArray(chartId) {
            if (document.getElementById(chartId) !== null) {
                var colors = document.getElementById(chartId).getAttribute("data-colors");
                if (colors) {
                    colors = JSON.parse(colors);
                    return colors.map(function (value) {
                        var newValue = value.replace(" ", "");
                        if (newValue.indexOf(",") === -1) {
                            var color = getComputedStyle(document.documentElement).getPropertyValue(newValue);
                            if (color) return color.trim();
                            else return newValue;
                        } else {
                            var val = value.split(',');
                            if (val.length == 2) {
                                var rgbaColor = getComputedStyle(document.documentElement).getPropertyValue(val[0]);
                                rgbaColor = "rgba(" + rgbaColor.trim() + "," + val[1] + ")";
                                return rgbaColor;
                            } else {
                                return newValue;
                            }
                        }
                    });
                }
            }
            return ['#405189', '#0ab39c', '#299cdb'];
        }

        document.addEventListener('DOMContentLoaded', function () {
            const chartsPayload = @json($charts);
            const hasUrgentUnread = {{ ($postsMeta['urgent_unread_count'] ?? 0) > 0 ? 'true' : 'false' }};

            const mxnFormatter = new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const compactMxnFormatter = function (val) {
                if (val === null || val === undefined || isNaN(val)) return '$0';
                const abs = Math.abs(val);
                if (abs >= 1000000) return '$' + (val / 1000000).toFixed(2) + 'M';
                if (abs >= 1000) return '$' + (val / 1000).toFixed(1) + 'k';
                return '$' + Number(val).toFixed(0);
            };

            // ── 1. Hub de Comunicados (Filtros + Colapsado con Persistencia) ──
            const commBody = document.getElementById('comm-hub-body');
            const toggleCommBtn = document.getElementById('toggle-comm-hub');
            const toggleCommIcon = document.getElementById('toggle-comm-icon');
            const storageKey = 'urena_dashboard_comm_collapsed';

            if (!hasUrgentUnread && localStorage.getItem(storageKey) === '1' && commBody) {
                commBody.classList.add('d-none');
                if (toggleCommIcon) toggleCommIcon.className = 'ri-arrow-down-s-line align-middle';
            }

            if (toggleCommBtn && commBody) {
                toggleCommBtn.addEventListener('click', function () {
                    const isHidden = commBody.classList.toggle('d-none');
                    localStorage.setItem(storageKey, isHidden ? '1' : '0');
                    if (toggleCommIcon) {
                        toggleCommIcon.className = isHidden ? 'ri-arrow-down-s-line align-middle' : 'ri-arrow-up-s-line align-middle';
                    }
                });
            }

            document.querySelectorAll('.comm-filter-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.comm-filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.getAttribute('data-filter');

                    if (commBody && commBody.classList.contains('d-none')) {
                        commBody.classList.remove('d-none');
                    }

                    document.querySelectorAll('.comm-item').forEach(card => {
                        const isRead = card.getAttribute('data-read');
                        const priority = card.getAttribute('data-priority');
                        const type = card.getAttribute('data-type');

                        let show = true;
                        if (filter === 'unread') show = (isRead === 'unread');
                        else if (filter === 'urgent') show = (priority === 'urgente');
                        else if (filter === 'tarea') show = (type === 'tarea');

                        card.classList.toggle('d-none', !show);
                    });
                });
            });

            // ── 2. Gráfica Mensual del Año en Curso ──
            const monthlyColors = getChartColorsArray("chart-monthly-sales");
            const monthlyEl = document.querySelector('#chart-monthly-sales');
            if (monthlyEl && chartsPayload.monthly_sales) {
                const mData = chartsPayload.monthly_sales;
                new ApexCharts(monthlyEl, {
                    series: [
                        { name: 'Ventas POS', type: 'column', data: mData.pos_series || [] },
                        { name: 'Pedidos Especiales', type: 'column', data: mData.special_series || [] },
                        { name: 'Cobros Ejecutados', type: 'line', data: mData.collections_series || [] }
                    ],
                    chart: {
                        height: 350,
                        type: 'line',
                        toolbar: { show: false }
                    },
                    stroke: {
                        curve: 'smooth',
                        width: [0, 0, 3]
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '45%',
                            borderRadius: 3
                        }
                    },
                    colors: monthlyColors,
                    xaxis: {
                        categories: mData.categories || [],
                        axisTicks: { show: false },
                        axisBorder: { show: false }
                    },
                    yaxis: {
                        labels: { formatter: compactMxnFormatter }
                    },
                    grid: {
                        show: true,
                        xaxis: { lines: { show: true } },
                        yaxis: { lines: { show: false } },
                        padding: { top: 0, right: 10, bottom: 15, left: 10 }
                    },
                    legend: {
                        show: true,
                        horizontalAlign: 'center',
                        offsetX: 0,
                        offsetY: -5,
                        markers: { width: 9, height: 9, radius: 6 },
                        itemMargin: { horizontal: 10, vertical: 0 }
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: { formatter: val => mxnFormatter.format(val || 0) }
                    }
                }).render();
            }

            // ── 3. Gráfica de Ventas Anuales ──
            const annualColors = getChartColorsArray("chart-annual-sales");
            const annualEl = document.querySelector('#chart-annual-sales');
            if (annualEl && chartsPayload.annual_sales) {
                const aData = chartsPayload.annual_sales;
                new ApexCharts(annualEl, {
                    series: [
                        { name: 'Ventas POS', data: aData.pos_series || [] },
                        { name: 'Pedidos Especiales', data: aData.special_series || [] },
                        { name: 'Venta Neta', data: aData.net_series || [] }
                    ],
                    chart: {
                        height: 370,
                        type: 'bar',
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            borderRadius: 4
                        }
                    },
                    dataLabels: { enabled: false },
                    colors: annualColors,
                    xaxis: {
                        categories: aData.categories || []
                    },
                    yaxis: {
                        labels: { formatter: compactMxnFormatter }
                    },
                    tooltip: {
                        y: { formatter: val => mxnFormatter.format(val || 0) }
                    },
                    legend: { position: 'top' }
                }).render();
            }

            // ── 4. Gráfica de Dona: Ventas del Mes por Producto ──
            let productDonutChart = null;
            const donutColors = getChartColorsArray("chart-products-donut");
            const donutEl = document.querySelector('#chart-products-donut');
            if (donutEl && chartsPayload.products_donut) {
                const pData = chartsPayload.products_donut;
                const hasProdData = (pData.amount_series || []).some(v => v > 0);

                window.__donutMode = 'amount';
                productDonutChart = new ApexCharts(donutEl, {
                    series: hasProdData ? pData.amount_series : [1],
                    labels: hasProdData ? pData.labels : ['Sin ventas registradas'],
                    chart: {
                        height: 250,
                        type: 'donut'
                    },
                    colors: hasProdData ? donutColors : ['#e2e5ed'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total Prod.',
                                        formatter: function (w) {
                                            if (!hasProdData) return '$0.00';
                                            const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                            return compactMxnFormatter(sum);
                                        }
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    legend: { position: 'bottom' },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                if (!hasProdData) return '$0.00';
                                return window.__donutMode === 'qty'
                                    ? (val + ' unidades')
                                    : mxnFormatter.format(val || 0);
                            }
                        }
                    }
                });
                productDonutChart.render();

                window.switchProductDonutMode = function (mode) {
                    if (!productDonutChart || !hasProdData) return;
                    window.__donutMode = mode;
                    document.getElementById('btn-prod-amount')?.classList.toggle('active', mode === 'amount');
                    document.getElementById('btn-prod-qty')?.classList.toggle('active', mode === 'qty');

                    const newSeries = mode === 'qty' ? pData.qty_series : pData.amount_series;
                    productDonutChart.updateOptions({
                        series: newSeries,
                        plotOptions: {
                            pie: {
                                donut: {
                                    labels: {
                                        total: {
                                            label: mode === 'qty' ? 'Total Uds.' : 'Total Prod.',
                                            formatter: function (w) {
                                                const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                                return mode === 'qty' ? (sum + ' uds') : compactMxnFormatter(sum);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    });
                };
            }

            // ── 5. Gráfica de Ingreso del Mes por Sucursal ──
            const storeColors = getChartColorsArray("chart-store-revenue");
            const storeEl = document.querySelector('#chart-store-revenue');
            if (storeEl && chartsPayload.store_revenue) {
                const stData = chartsPayload.store_revenue;
                new ApexCharts(storeEl, {
                    series: [
                        { name: 'Ventas POS', data: stData.pos_series || [] },
                        { name: 'Pedidos Especiales', data: stData.special_series || [] }
                    ],
                    chart: {
                        height: 350,
                        type: 'bar',
                        stacked: true,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '55%',
                            borderRadius: 4
                        }
                    },
                    colors: storeColors,
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: stData.labels || [],
                        labels: { formatter: compactMxnFormatter }
                    },
                    tooltip: {
                        y: { formatter: val => mxnFormatter.format(val || 0) }
                    },
                    legend: { position: 'top' }
                }).render();
            }

            // ── 6. Gráfica de Ventas del Mes por Vendedor ──
            const sellerColors = getChartColorsArray("chart-seller-sales");
            const sellerEl = document.querySelector('#chart-seller-sales');
            if (sellerEl && chartsPayload.seller_sales) {
                const slData = chartsPayload.seller_sales;
                new ApexCharts(sellerEl, {
                    series: [
                        { name: 'Ventas POS', data: slData.pos_series || [] },
                        { name: 'Pedidos Especiales', data: slData.special_series || [] }
                    ],
                    chart: {
                        height: 350,
                        type: 'bar',
                        stacked: true,
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '55%',
                            borderRadius: 4
                        }
                    },
                    colors: sellerColors,
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: slData.labels && slData.labels.length ? slData.labels : ['Sin ventas'],
                        labels: { formatter: compactMxnFormatter }
                    },
                    tooltip: {
                        y: { formatter: val => mxnFormatter.format(val || 0) }
                    },
                    legend: { position: 'top' }
                }).render();
            }

            // ── 7. Gráfica de Métodos de Pago y Comisiones ──
            const pmColors = getChartColorsArray("chart-payment-methods");
            const pmEl = document.querySelector('#chart-payment-methods');
            if (pmEl && chartsPayload.payment_methods) {
                const pmData = chartsPayload.payment_methods;
                new ApexCharts(pmEl, {
                    series: [
                        { name: 'Monto Cobrado', data: pmData.amount_series || [] },
                        { name: 'Comisión Terminal', data: pmData.commissions_series || [] }
                    ],
                    chart: {
                        height: 230,
                        type: 'bar',
                        toolbar: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '55%',
                            borderRadius: 3
                        }
                    },
                    colors: pmColors,
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: pmData.labels && pmData.labels.length ? pmData.labels : ['Sin cobros'],
                        labels: { formatter: compactMxnFormatter }
                    },
                    tooltip: {
                        y: { formatter: val => mxnFormatter.format(val || 0) }
                    },
                    legend: { position: 'top' }
                }).render();
            }

            // ── 8. Filtros y Buscadores del Tablero ──
            function bindTableSearch(inputId, tableId) {
                const input = document.getElementById(inputId);
                const table = document.getElementById(tableId);
                if (!input || !table) return;

                input.addEventListener('input', function () {
                    const q = this.value.toLowerCase().trim();
                    table.querySelectorAll('tbody tr').forEach(tr => {
                        if (tr.cells.length <= 1) return;
                        const text = tr.innerText.toLowerCase();
                        tr.style.display = text.includes(q) ? '' : 'none';
                    });
                });
            }

            bindTableSearch('search-sales-table', 'table-monthly-sales');
            bindTableSearch('search-deliveries-table', 'table-deliveries');
            bindTableSearch('search-payments-table', 'table-customer-payments');
            bindTableSearch('search-expenses-table', 'table-expenses');

            document.querySelectorAll('.sales-type-filter').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.sales-type-filter').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const targetType = this.getAttribute('data-type');

                    document.querySelectorAll('#table-monthly-sales tbody tr.sales-table-row').forEach(tr => {
                        const rowType = tr.getAttribute('data-sale-type');
                        const show = (targetType === 'all' || rowType === targetType);
                        tr.classList.toggle('d-none', !show);
                    });
                });
            });
        });
    </script>
@endsection
