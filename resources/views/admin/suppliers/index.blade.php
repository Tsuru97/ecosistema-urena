@extends('layouts.master')
@section('title') Proveedores @endsection

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
                        <i class="ri-truck-line me-2"></i>Catálogo de Proveedores
                    </h4>
                    @can('crear-proveedores')
                    <a href="{{ route('suppliers.create') }}" class="btn btn-success btn-label btn-sm waves-effect">
                        <i class="ri-add-line label-icon align-middle me-2"></i> Nuevo Proveedor
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    {{-- Buscador --}}
                    <form action="{{ route('suppliers.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm"
                                           value="{{ $search }}" placeholder="Buscar por nombre, RFC, email...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-search-line me-1"></i> Buscar
                                </button>
                                @if($search)
                                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm ms-1">
                                        <i class="ri-close-line me-1"></i> Limpiar
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre / Razón Social</th>
                                    <th>RFC</th>
                                    <th>Contacto</th>
                                    <th>Condiciones</th>
                                    <th class="text-center">Estado</th>
                                    <th style="width: 150px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suppliers as $supplier)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold fs-14">
                                                <a href="{{ route('suppliers.show', $supplier) }}" class="text-dark">{{ $supplier->name }}</a>
                                            </div>
                                            <small class="text-muted">{{ $supplier->address }}</small>
                                        </td>
                                        <td><code>{{ $supplier->rfc ?: '—' }}</code></td>
                                        <td>
                                            @if($supplier->email)
                                                <div class="fs-13"><i class="ri-mail-line me-1 align-middle text-muted"></i>{{ $supplier->email }}</div>
                                            @endif
                                            @if($supplier->phone)
                                                <div class="fs-13 text-muted"><i class="ri-phone-line me-1 align-middle text-muted"></i>{{ $supplier->phone }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $supplier->credit_days > 0 ? $supplier->credit_days . ' días crédito' : 'Contado' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $supplier->status ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ $supplier->status ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="hstack gap-1">
                                                <a href="{{ route('suppliers.show', $supplier) }}"
                                                   class="btn btn-sm btn-outline-info" title="Ver ficha técnica">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @can('editar-proveedores')
                                                <a href="{{ route('suppliers.edit', $supplier) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                @endcan
                                                @can('eliminar-proveedores')
                                                <form id="del-supp-{{ $supplier->id }}" action="{{ route('suppliers.destroy', $supplier) }}" method="POST" style="display:none;">
                                                    @csrf @method('DELETE')
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar / Desactivar"
                                                        data-confirm
                                                        data-form="del-supp-{{ $supplier->id }}"
                                                        data-title="Eliminar Proveedor"
                                                        data-message="¿Estás seguro de eliminar o desactivar a <strong>{{ $supplier->name }}</strong>?"
                                                        data-btn-label="Sí, eliminar">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="ri-truck-line fs-24 d-block mb-2 text-muted"></i>
                                            <p class="text-muted mb-0">No hay proveedores registrados todavía.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $suppliers->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
