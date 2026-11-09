@extends('layouts.master')
@section('title') Traspasos de Inventario @endsection

@section('css')
<style>
    .status-tabs .nav-link        { border-radius: 20px; padding: 4px 14px; font-size: 13px; }
    .status-tabs .nav-link.active { font-weight: 600; }
    .transfer-row-origin   { border-left: 3px solid var(--vz-warning); }
    .transfer-row-dest     { border-left: 3px solid var(--vz-info); }
    .transfer-row-finished { border-left: 3px solid var(--vz-secondary); opacity: .8; }
</style>
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header align-items-center d-flex flex-wrap gap-2">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-swap-box-line me-2"></i> Traspasos
                    </h4>
                    @can('crear-traspasos')
                        @if(current_store_id())
                            <a href="{{ route('transfers.create') }}" class="btn btn-success btn-sm btn-label waves-effect">
                                <i class="ri-add-line label-icon align-middle me-2"></i> Nuevo Traspaso
                            </a>
                        @endif
                    @endcan
                </div>

                <div class="card-body">

                    {{-- Filtros --}}
                    <form action="{{ route('transfers.index') }}" method="GET" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm"
                                           value="{{ $search }}" placeholder="Buscar por folio...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-search-line me-1"></i> Buscar
                                </button>
                                @if($search || $status)
                                    <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="ri-close-line me-1"></i> Limpiar
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    {{-- Tabs de estado --}}
                    <ul class="nav status-tabs gap-1 mb-3">
                        @php
                            $tabs = [
                                ''           => ['label' => 'Todos',       'icon' => 'ri-list-check'],
                                'pendiente'  => ['label' => 'Pendientes',  'icon' => 'ri-time-line'],
                                'en_transito'=> ['label' => 'En Tránsito', 'icon' => 'ri-truck-line'],
                                'recibido'   => ['label' => 'Recibidos',   'icon' => 'ri-checkbox-circle-line'],
                                'devolucion' => ['label' => 'Devoluciones','icon' => 'ri-arrow-go-back-line'],
                                'rechazado'  => ['label' => 'Rechazados',  'icon' => 'ri-close-circle-line'],
                                'cancelado'  => ['label' => 'Cancelados',  'icon' => 'ri-forbid-line'],
                            ];
                        @endphp
                        @foreach($tabs as $val => $tab)
                            <li class="nav-item">
                                <a href="{{ route('transfers.index', array_merge(request()->except('status', 'page'), $val ? ['status' => $val] : [])) }}"
                                   class="nav-link {{ $status === $val ? 'active btn-primary text-white' : 'btn-outline-secondary' }}">
                                    <i class="{{ $tab['icon'] }} me-1"></i>
                                    {{ $tab['label'] }}
                                    @if($val && ($counts[$val] ?? 0) > 0)
                                        <span class="badge bg-white text-dark ms-1">{{ $counts[$val] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Origen → Destino</th>
                                    <th class="text-center">Artículos</th>
                                    <th class="text-center">Estado</th>
                                    <th>Creado por</th>
                                    <th>Fecha</th>
                                    <th style="width:90px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                    @php
                                        $badge   = $transfer->statusBadge();
                                        $sid     = current_store_id();
                                        $rowCls  = $transfer->isFinished()
                                            ? 'transfer-row-finished'
                                            : ($sid && $transfer->origin_store_id === (int)$sid
                                                ? 'transfer-row-origin'
                                                : ($sid && $transfer->destination_store_id === (int)$sid
                                                    ? 'transfer-row-dest' : ''));
                                    @endphp
                                    <tr class="{{ $rowCls }}">
                                        <td>
                                            <a href="{{ route('transfers.show', $transfer) }}"
                                               class="fw-semibold text-dark">{{ $transfer->folio }}</a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2 fs-13">
                                                <span class="badge bg-warning-subtle text-warning">{{ $transfer->originStore->name }}</span>
                                                <i class="ri-arrow-right-line text-muted"></i>
                                                <span class="badge bg-info-subtle text-info">{{ $transfer->destinationStore->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $transfer->items->count() }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $badge['color'] }}">{{ $badge['label'] }}</span>
                                        </td>
                                        <td class="fs-13 text-muted">{{ $transfer->creator?->name }}</td>
                                        <td class="fs-13 text-muted">{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('transfers.show', $transfer) }}"
                                               class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="ri-swap-box-line fs-24 d-block mb-2 text-muted"></i>
                                            <p class="text-muted mb-0">No hay traspasos registrados todavía.
                                                @can('gestionar-inventario')
                                                    @if(current_store_id())
                                                        <a href="{{ route('transfers.create') }}">Crear el primero</a>
                                                    @endif
                                                @endcan
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $transfers->appends(request()->all())->links() }}
                    </div>

                    {{-- Leyenda de colores --}}
                    @if(!$isGlobal && current_store_id())
                        <div class="d-flex gap-3 mt-2 fs-12 text-muted">
                            <span><span style="border-left:3px solid var(--vz-warning);padding-left:6px">Enviaste tú</span></span>
                            <span><span style="border-left:3px solid var(--vz-info);padding-left:6px">Te envían a ti</span></span>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
