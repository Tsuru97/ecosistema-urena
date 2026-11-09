@extends('layouts.master')
@section('title') Historial y Auditoría de Cortes de Caja @endsection

@section('content')
<x-breadcrumb />

{{-- KPIs de Caja para Gerencia y Dirección --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm rounded bg-primary-subtle flex-shrink-0">
                        <i class="ri-history-line text-primary fs-4 d-flex align-items-center justify-content-center h-100"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-primary">{{ $kpis['total_shifts'] }}</div>
                        <div class="text-muted small">
                            Turnos Totales 
                            <span class="text-success">({{ $kpis['closed_shifts'] }} cerrados)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm rounded {{ $kpis['open_shifts'] > 0 ? 'bg-warning-subtle' : 'bg-secondary-subtle' }} flex-shrink-0">
                        <i class="ri-key-2-line {{ $kpis['open_shifts'] > 0 ? 'text-warning' : 'text-secondary' }} fs-4 d-flex align-items-center justify-content-center h-100"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold {{ $kpis['open_shifts'] > 0 ? 'text-warning' : 'text-secondary' }}">{{ $kpis['open_shifts'] }}</div>
                        <div class="text-muted small">Cajas en Operación (Abiertas)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm rounded bg-success-subtle flex-shrink-0">
                        <i class="ri-money-dollar-circle-line text-success fs-4 d-flex align-items-center justify-content-center h-100"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-success">${{ number_format($kpis['total_sales'], 2) }}</div>
                        <div class="text-muted small">
                            Efectivo: ${{ number_format($kpis['total_sales_cash'], 2) }} | Tarj: ${{ number_format($kpis['total_sales_card'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm rounded {{ $kpis['net_diff'] < -0.01 ? 'bg-danger-subtle' : ($kpis['net_diff'] > 0.01 ? 'bg-info-subtle' : 'bg-success-subtle') }} flex-shrink-0">
                        <i class="ri-scales-3-line {{ $kpis['net_diff'] < -0.01 ? 'text-danger' : ($kpis['net_diff'] > 0.01 ? 'text-info' : 'text-success') }} fs-4 d-flex align-items-center justify-content-center h-100"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold {{ $kpis['net_diff'] < -0.01 ? 'text-danger' : ($kpis['net_diff'] > 0.01 ? 'text-info' : 'text-success') }}">
                            {{ $kpis['net_diff'] >= 0 ? '+' : '' }}${{ number_format($kpis['net_diff'], 2) }}
                        </div>
                        <div class="text-muted small">
                            Diferencia Neta ({{ $kpis['shifts_with_diff'] }} con descuadre)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alertas --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header align-items-center d-flex flex-wrap gap-2">
        <h5 class="card-title mb-0 flex-grow-1">
            <i class="ri-safe-2-line me-2 text-primary"></i>Historial de Cortes de Caja y Arqueos
        </h5>
        <div class="flex-shrink-0 d-flex flex-wrap gap-2">
            @if($canReport)
            <a href="{{ route('cash-shifts.report', request()->query()) }}" target="_blank" class="btn btn-soft-info btn-sm fw-semibold">
                <i class="ri-printer-line me-1"></i> Reporte Imprimible / PDF
            </a>
            @endif
            @if($canExport)
            <a href="{{ route('cash-shifts.export', request()->query()) }}" class="btn btn-soft-success btn-sm fw-semibold">
                <i class="ri-file-excel-2-line me-1"></i> Exportar a Excel (CSV)
            </a>
            @endif
            <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm fw-semibold">
                <i class="ri-store-2-line me-1"></i> Ir al Punto de Venta
            </a>
        </div>
    </div>

    <div class="card-body">
        {{-- Formulario de Filtros de Auditoría --}}
        <form action="{{ route('cash-shifts.index') }}" method="GET" class="mb-4 bg-light p-3 rounded border">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Búsqueda Rápida</label>
                    <div class="search-box">
                        <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Folio, cajero, notas...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>

                {{-- Selector de Sucursales si tiene permiso auditar-cortes-caja --}}
                @if($canAuditAll)
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Sucursal</label>
                    <select name="store_id" class="form-select form-select-sm">
                        <option value="all">🏢 Todas las sucursales</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Selector de Cajeros / Vendedores --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Cajero / Vendedor</label>
                    <select name="cashier_id" class="form-select form-select-sm">
                        <option value="all">👤 Todos los cajeros</option>
                        @foreach($cashiers as $c)
                            <option value="{{ $c->id }}" {{ request('cashier_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} {{ $c->lastname ?? '' }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Selector de Periodo Rápido --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Periodo</label>
                    <select name="period" id="filter-period" class="form-select form-select-sm" onchange="toggleCustomDates(this.value)">
                        <option value="" {{ !request('period') && !request('date_from') ? 'selected' : '' }}>Histórico Completo</option>
                        <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Hoy</option>
                        <option value="yesterday" {{ request('period') === 'yesterday' ? 'selected' : '' }}>Ayer</option>
                        <option value="this_week" {{ request('period') === 'this_week' ? 'selected' : '' }}>Esta Semana</option>
                        <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }}>Este Mes</option>
                        <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }}>Mes Anterior</option>
                        <option value="custom" {{ request('period') === 'custom' || request('date_from') ? 'selected' : '' }}>Personalizado...</option>
                    </select>
                </div>

                {{-- Estado del Turno --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Estado</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">Todos los estados</option>
                        <option value="abierto" {{ request('status') === 'abierto' ? 'selected' : '' }}>Abierto (En curso)</option>
                        <option value="cerrado" {{ request('status') === 'cerrado' ? 'selected' : '' }}>Cerrado (Corte Z)</option>
                    </select>
                </div>

                {{-- Filtro de Auditoría / Descuadres --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Auditoría / Arqueo</label>
                    <select name="discrepancy" class="form-select form-select-sm">
                        <option value="all">Todos los arqueos</option>
                        <option value="with_diff" {{ request('discrepancy') === 'with_diff' ? 'selected' : '' }}>⚠️ Con Descuadre (Cualquiera)</option>
                        <option value="shortage" {{ request('discrepancy') === 'shortage' ? 'selected' : '' }}>🔴 Solo Faltantes</option>
                        <option value="surplus" {{ request('discrepancy') === 'surplus' ? 'selected' : '' }}>🔵 Solo Sobrantes</option>
                        <option value="exact" {{ request('discrepancy') === 'exact' ? 'selected' : '' }}>🟢 Cuadrados Exactos</option>
                    </select>
                </div>

                {{-- Rango de Fechas Personalizado --}}
                <div class="col-md-3" id="custom-date-container" style="{{ request('period') === 'custom' || request('date_from') ? '' : 'display: none;' }}">
                    <label class="form-label small fw-semibold">Rango de Fechas</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        <span class="input-group-text">a</span>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                        <i class="ri-filter-2-line me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('cash-shifts.index') }}" class="btn btn-soft-secondary btn-sm" title="Limpiar Filtros">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </div>
        </form>

        {{-- Tabla de Turnos --}}
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Sucursal</th>
                        <th>Cajero</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th class="text-end">Fondo Inicial</th>
                        <th class="text-end">Ventas Cobradas</th>
                        <th class="text-end">Esperado</th>
                        <th class="text-end">Físico Contado</th>
                        <th class="text-center">Diferencia</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                    @php
                        $diff = (float) ($shift->difference_cash ?? 0);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('cash-shifts.show', $shift->id) }}" class="fw-bold text-primary">
                                <i class="ri-ticket-2-line me-1"></i>{{ $shift->folio }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="ri-store-2-line me-1 text-muted"></i>{{ $shift->store?->name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-xs me-2">
                                    <span class="avatar-title rounded-circle bg-light text-primary fs-12 fw-bold">
                                        {{ substr($shift->cashier?->name ?? 'C', 0, 1) }}
                                    </span>
                                </div>
                                <span>{{ $shift->cashier?->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="text-dark fw-medium">{{ $shift->opened_at->format('d/m/Y') }}</span>
                            <small class="text-muted d-block">{{ $shift->opened_at->format('H:i') }} hrs</small>
                        </td>
                        <td>
                            @if($shift->closed_at)
                                <span class="text-dark fw-medium">{{ $shift->closed_at->format('d/m/Y') }}</span>
                                <small class="text-muted d-block">{{ $shift->closed_at->format('H:i') }} hrs</small>
                            @else
                                <span class="badge bg-warning-subtle text-warning"><i class="ri-time-line me-1"></i>En curso</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">${{ number_format($shift->opening_amount, 2) }}</td>
                        <td class="text-end text-success fw-bold">${{ number_format($shift->total_sales_amount, 2) }}</td>
                        <td class="text-end fw-semibold">
                            ${{ number_format($shift->isClosed() ? $shift->expected_cash : $shift->calculateLiveTotals()['expected_cash'], 2) }}
                        </td>
                        <td class="text-end fw-bold">
                            @if($shift->isClosed())
                                ${{ number_format($shift->actual_cash, 2) }}
                            @else
                                <span class="text-muted small">Pendiente</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($shift->isClosed())
                                @if(abs($diff) < 0.01)
                                    <span class="badge bg-success-subtle text-success fw-semibold">
                                        <i class="ri-checkbox-circle-line me-1"></i> Exacto
                                    </span>
                                @elseif($diff > 0)
                                    <span class="badge bg-info-subtle text-info fw-semibold" title="Sobrante de efectivo">
                                        +${{ number_format($diff, 2) }}
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger fw-semibold" title="Faltante de efectivo">
                                        -${{ number_format(abs($diff), 2) }}
                                    </span>
                                @endif
                            @else
                                <span class="badge bg-light text-muted">Sin arqueo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($shift->isOpen())
                                <span class="badge bg-success"><i class="ri-checkbox-circle-line me-1"></i>Abierto</span>
                            @else
                                <span class="badge bg-secondary"><i class="ri-lock-line me-1"></i>Cerrado</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('cash-shifts.show', $shift->id) }}" class="btn btn-sm btn-soft-primary" title="Ver Detalle y Auditoría">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-soft-success" onclick="window.open('{{ route('cash-shifts.print', $shift->id) }}?type={{ $shift->isClosed() ? 'z' : 'x' }}&format=a4', '_blank')" title="Imprimir Comprobante en Hoja A4">
                                    <i class="ri-file-text-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-soft-info" onclick="window.open('{{ route('cash-shifts.print', $shift->id) }}?type={{ $shift->isClosed() ? 'z' : 'x' }}&format=ticket', '_blank', 'width=450,height=650')" title="Imprimir Ticket Térmico (80mm)">
                                    <i class="ri-printer-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">
                            <i class="ri-safe-2-line fs-1 d-block mb-2 text-muted opacity-50"></i>
                            <h6 class="fw-semibold">No se encontraron turnos de caja para los criterios seleccionados</h6>
                            <p class="small text-muted mb-0">Prueba ajustando los filtros de fecha, sucursal o estado.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($shifts->hasPages())
        <div class="mt-4 d-flex justify-content-end">
            {{ $shifts->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    function toggleCustomDates(val) {
        const container = document.getElementById('custom-date-container');
        if (container) {
            container.style.display = (val === 'custom') ? '' : 'none';
        }
    }
</script>
@endsection
