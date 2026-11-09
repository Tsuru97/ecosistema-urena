@extends('layouts.master')
@section('title') Métodos de Pago @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header align-items-center d-flex flex-wrap gap-2">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-bank-card-line me-2"></i>Métodos de Pago
                    </h4>
                    @can('gestionar-pagos')
                    <a href="{{ route('payment-methods.create') }}" class="btn btn-success btn-label btn-sm waves-effect waves-light">
                        <i class="ri-add-line label-icon align-middle me-2"></i> Nuevo Método de Pago
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    {{-- Buscador --}}
                    <form action="{{ route('payment-methods.index') }}" method="GET" class="mb-4">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="search-box flex-grow-1" style="max-width: 400px;">
                                <input type="text" name="search" class="form-control search form-control-sm"
                                       value="{{ $search }}" placeholder="Buscar método de pago...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-search-line me-1"></i> Buscar
                            </button>
                            @if($search)
                                <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="ri-close-line me-1"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 60px;">ID</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Comisión Base (%)</th>
                                    <th scope="col">Financiamiento</th>
                                    <th scope="col">Plazos de Mensualidades</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentMethods as $pm)
                                    <tr>
                                        <td>{{ $pm->id }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $pm->name }}</span>
                                            @if($pm->is_system)
                                                <span class="badge bg-info-subtle text-info ms-1">Sistema</span>
                                            @endif
                                        </td>
                                        <td>{{ $pm->commission_percentage }}%</td>
                                        <td>
                                            @if($pm->accepts_financing)
                                                <span class="badge bg-success-subtle text-success">Sí</span>
                                            @else
                                                <span class="badge bg-light text-muted">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pm->accepts_financing && is_array($pm->financing_rates))
                                                @foreach($pm->financing_rates as $rate)
                                                    <span class="badge bg-primary-subtle text-primary me-1">
                                                        {{ $rate['months'] }} meses ({{ $rate['rate'] }}% int.)
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted fs-12">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pm->is_active)
                                                <span class="badge bg-success text-white">Activo</span>
                                            @else
                                                <span class="badge bg-danger text-white">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @can('gestionar-pagos')
                                                <a href="{{ route('payment-methods.edit', $pm->id) }}" class="btn btn-sm btn-info btn-icon" title="Editar">
                                                    <i class="ri-edit-2-line"></i>
                                                </a>
                                                @if(!$pm->is_system)
                                                    <form action="{{ route('payment-methods.destroy', $pm->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este método de pago?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Eliminar">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No se encontraron métodos de pago.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $paymentMethods->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
