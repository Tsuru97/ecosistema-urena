@extends('layouts.master')
@section('title') Pedidos Especiales @endsection

@section('content')
<x-breadcrumb />

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    @php
    $kpiConfig = [
        'pending' => ['label'=>'Pendientes',    'icon'=>'ri-time-line',          'color'=>'warning'],
        'ordered' => ['label'=>'En Pedido OC',  'icon'=>'ri-truck-line',          'color'=>'info'],
        'partial' => ['label'=>'Recib. Parcial','icon'=>'ri-inbox-archive-line',  'color'=>'primary'],
        'ready'   => ['label'=>'Listos',        'icon'=>'ri-checkbox-circle-line','color'=>'success'],
    ];
    @endphp
    @foreach($kpiConfig as $k => $cfg)
    <div class="col-6 col-md-3">
        <a href="{{ request()->fullUrlWithQuery(['status' => $k]) }}" class="text-decoration-none">
            <div class="card border-{{ $cfg['color'] }} border-opacity-50 h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm rounded bg-{{ $cfg['color'] }}-subtle flex-shrink-0">
                            <i class="{{ $cfg['icon'] }} text-{{ $cfg['color'] }} fs-4 d-flex align-items-center justify-content-center h-100"></i>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-{{ $cfg['color'] }}">{{ $kpis[$k] }}</div>
                            <div class="text-muted small">{{ $cfg['label'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Filtro activo --}}
@if($status || !empty($search))
<div class="alert alert-info d-flex align-items-center py-2 mb-3">
    <i class="ri-filter-line me-2"></i>
    <span>
        Filtrando por:
        @if(!empty($search))
            <strong class="ms-1">Búsqueda: "{{ $search }}"</strong>
        @endif
        @if($status)
            <strong class="ms-1">{{ ['pending'=>'Pendientes','ordered'=>'En Pedido','partial'=>'Recibido Parcial','ready'=>'Listos para Entrega','delivered'=>'Entregados','cancelled'=>'Cancelados'][$status] ?? $status }}</strong>
        @endif
    </span>
    <a href="{{ route('special-orders.index') }}" class="ms-auto btn btn-sm btn-outline-secondary">Ver todos</a>
</div>
@endif

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <h5 class="card-title mb-0 flex-grow-1">
            <i class="ri-file-list-3-line me-2 text-primary"></i>Pedidos Especiales
        </h5>
        <form action="{{ route('special-orders.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-0">
            <div class="search-box" style="min-width: 260px;">
                <input type="text" name="search" class="form-control form-control-sm search" value="{{ request('search') }}" placeholder="Ej. PED-2026-0001, #8, cliente o tel...">
                <i class="ri-search-line search-icon"></i>
            </div>
            <select name="status" class="form-select form-select-sm" style="width:165px;" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach(['pending'=>'Pendientes','ordered'=>'En Pedido OC','partial'=>'Recib. Parcial','ready'=>'Listos','delivered'=>'Entregados','cancelled'=>'Cancelados'] as $v => $l)
                    <option value="{{ $v }}" {{ $status === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm" title="Buscar"><i class="ri-filter-2-line"></i></button>
            @if(request('search') || request('status'))
                <a href="{{ route('special-orders.index') }}" class="btn btn-soft-secondary btn-sm" title="Limpiar"><i class="ri-refresh-line"></i></a>
            @endif
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Anticipo</th>
                        <th>Balance</th>
                        <th>Estado</th>
                        <th>Entrega Esperada</th>
                        <th>Creado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr class="{{ $order->isReady() ? 'table-success table-success bg-opacity-25' : '' }}">
                        <td>
                            <a href="{{ route('special-orders.show', $order) }}" class="fw-semibold text-primary">
                                {{ $order->folio }}
                            </a>
                            @if($order->hasDelivery())
                                <span class="badge bg-primary-subtle text-primary d-block mt-1 fs-10" style="width: fit-content;">
                                    <i class="ri-truck-line me-1"></i>A Domicilio
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium">{{ $order->customer?->full_name ?: trim(($order->customer?->name ?? '') . ' ' . ($order->customer?->lastname ?? '')) }}</div>
                            <small class="text-muted">{{ $order->customer?->phone }}</small>
                        </td>
                        <td>
                            @foreach($order->items as $item)
                                <div class="small">
                                    <span class="fw-medium">{{ $item->product?->name }}</span>
                                    <span class="text-muted ms-1">×{{ $item->quantity }}</span>
                                    @if($item->quantity_received > 0)
                                        <span class="badge bg-success-subtle text-success ms-1">Rec: {{ $item->quantity_received }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td class="fw-semibold">${{ number_format($order->total, 2) }}</td>
                        <td class="text-success">${{ number_format($order->totalPaid(), 2) }}</td>
                        <td>
                            @if($order->balanceDue() > 0)
                                <span class="text-danger fw-semibold">${{ number_format($order->balanceDue(), 2) }}</span>
                            @else
                                <span class="badge bg-success-subtle text-success">Saldado</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                        </td>
                        <td>
                            @if($order->expected_delivery_date)
                                @php $overdue = $order->expected_delivery_date->isPast() && !$order->isDelivered() && !$order->isCancelled(); @endphp
                                <span class="{{ $overdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    {{ $order->expected_delivery_date->format('d/m/Y') }}
                                    @if($overdue) <i class="ri-alarm-warning-line"></i> @endif
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('special-orders.show', $order) }}" class="btn btn-sm btn-soft-primary btn-icon">
                                <i class="ri-eye-line"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="ri-file-list-3-line fs-1 mb-2 d-block"></i>
                            No hay pedidos especiales {{ $status ? 'con este estado' : '' }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($orders->hasPages())
    <div class="card-footer">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
