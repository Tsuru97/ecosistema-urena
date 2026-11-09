@extends('layouts.master')
@section('title') Gastos Empresariales @endsection

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

            <div class="card">
                <div class="card-header align-items-center d-flex flex-wrap gap-2">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-money-dollar-circle-line me-2"></i>Gastos Empresariales
                    </h4>
                    @can('crear-gastos')
                    <a href="{{ route('expenses.create') }}" class="btn btn-danger btn-label btn-sm waves-effect waves-light">
                        <i class="ri-add-line label-icon align-middle me-2"></i> Registrar Gasto Manual
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    {{-- Buscador --}}
                    <form action="{{ route('expenses.index') }}" method="GET" class="mb-4">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="search-box flex-grow-1" style="max-width: 400px;">
                                <input type="text" name="search" class="form-control search form-control-sm"
                                       value="{{ $search }}" placeholder="Buscar por concepto...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-search-line me-1"></i> Buscar
                            </button>
                            @if($search)
                                <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">
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
                                    <th scope="col">Concepto</th>
                                    <th scope="col">Monto</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Sucursal</th>
                                    <th scope="col">Registrado Por</th>
                                    <th scope="col">Origen</th>
                                    <th scope="col">Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $e)
                                    <tr>
                                        <td>{{ $e->id }}</td>
                                        <td class="fw-semibold text-danger">{{ $e->concept }}</td>
                                        <td class="fw-bold text-danger">${{ number_format($e->amount, 2) }}</td>
                                        <td>{{ $e->date->format('d/m/Y') }}</td>
                                        <td>{{ $e->store ? $e->store->name : 'Global' }}</td>
                                        <td>{{ $e->user ? $e->user->name : 'N/A' }}</td>
                                        <td>
                                            @if($e->reference_type === 'payment')
                                                <span class="badge bg-warning-subtle text-warning">Comisión Bancaria</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Manual</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted text-wrap d-inline-block" style="max-width: 250px;">
                                                {{ $e->notes ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No se encontraron registros de gastos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $expenses->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
