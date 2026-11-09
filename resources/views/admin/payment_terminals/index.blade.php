@extends('layouts.master')
@section('title') Terminales Bancarias @endsection

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
                        <i class="ri-sound-module-line me-2"></i>Terminales de Pago Bancarias
                    </h4>
                    @can('gestionar-terminales-pago')
                    <a href="{{ route('payment-terminals.create') }}" class="btn btn-success btn-label btn-sm waves-effect waves-light">
                        <i class="ri-add-line label-icon align-middle me-2"></i> Nueva Terminal
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    {{-- Buscador --}}
                    <form action="{{ route('payment-terminals.index') }}" method="GET" class="mb-4">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="search-box flex-grow-1" style="max-width: 400px;">
                                <input type="text" name="search" class="form-control search form-control-sm"
                                       value="{{ $search }}" placeholder="Buscar terminal...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-search-line me-1"></i> Buscar
                            </button>
                            @if($search)
                                <a href="{{ route('payment-terminals.index') }}" class="btn btn-outline-secondary btn-sm">
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
                                    <th scope="col">Comisión Fija</th>
                                    <th scope="col">Comisión Porcentaje</th>
                                    <th scope="col">Cobrar Comisión A</th>
                                    <th scope="col">Métodos Asignados por Default</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($terminals as $t)
                                    <tr>
                                        <td>{{ $t->id }}</td>
                                        <td class="fw-semibold">{{ $t->name }}</td>
                                        <td>${{ number_format($t->commission_fixed, 2) }}</td>
                                        <td>{{ $t->commission_percentage }}%</td>
                                        <td>
                                            @if($t->charge_client)
                                                <span class="badge bg-success-subtle text-success">Cliente</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Empresa (Gasto)</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse($t->paymentMethods as $pm)
                                                <span class="badge bg-primary-subtle text-primary me-1">{{ $pm->name }}</span>
                                            @empty
                                                <span class="text-muted fs-12">Ninguno (Soporta todos bajo advertencia)</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if($t->is_active)
                                                <span class="badge bg-success text-white">Activa</span>
                                            @else
                                                <span class="badge bg-danger text-white">Inactiva</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @can('gestionar-terminales-pago')
                                                <a href="{{ route('payment-terminals.edit', $t->id) }}" class="btn btn-sm btn-info btn-icon" title="Editar">
                                                    <i class="ri-edit-2-line"></i>
                                                </a>
                                                <form action="{{ route('payment-terminals.destroy', $t->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta terminal bancaria?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Eliminar">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No se encontraron terminales bancarias.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $terminals->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
