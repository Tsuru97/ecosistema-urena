@extends('layouts.master')
@section('title') Atributos de Producto @endsection

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
                        <i class="ri-list-settings-line me-2"></i>Atributos de Variantes
                    </h4>
                    @can('editar-catalogo')
                    <a href="{{ route('attributes.create') }}" class="btn btn-success btn-label btn-sm waves-effect waves-light">
                        <i class="ri-add-line label-icon align-middle me-2"></i> Nuevo Atributo
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    {{-- Buscador --}}
                    <form action="{{ route('attributes.index') }}" method="GET" class="mb-4">
                        <div class="d-flex gap-2 align-items-center">
                            <div class="search-box flex-grow-1" style="max-width: 400px;">
                                <input type="text" name="search" class="form-control search form-control-sm"
                                       value="{{ $search }}" placeholder="Buscar atributo...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-search-line me-1"></i> Buscar
                            </button>
                            @if($search)
                                <a href="{{ route('attributes.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="ri-close-line me-1"></i> Limpiar
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 60px;">#</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col" class="text-center">Valores</th>
                                    <th scope="col" class="text-center">Para Variación</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" style="width: 120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attributes as $attribute)
                                    <tr>
                                        <td class="text-muted fs-12">{{ $attribute->order }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ $attribute->name }}</span>
                                            <small class="text-muted d-block">{{ $attribute->slug }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $typeColor = match($attribute->type) {
                                                    'color'  => 'info',
                                                    'text'   => 'secondary',
                                                    default  => 'primary',
                                                };
                                                $typeLabel = match($attribute->type) {
                                                    'color'  => 'Color',
                                                    'text'   => 'Texto libre',
                                                    default  => 'Selección',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}">
                                                {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-dark-subtle text-dark fw-semibold">
                                                {{ $attribute->values_count }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($attribute->is_variation)
                                                <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Sí</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($attribute->status)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="hstack gap-2">
                                                @can('editar-catalogo')
                                                <a href="{{ route('attributes.edit', $attribute) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="ri-pencil-line"></i>
                                                </a>
                                                <form action="{{ route('attributes.destroy', $attribute) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            title="Eliminar"
                                                            data-confirm="true"
                                                            data-confirm-title="Eliminar atributo"
                                                            data-confirm-message="¿Eliminar el atributo <strong>{{ $attribute->name }}</strong> y sus {{ $attribute->values_count }} valores? Esta acción no puede deshacerse.">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="ri-list-settings-line fs-24 d-block mb-2 text-muted"></i>
                                            <p class="text-muted mb-0">
                                                No hay atributos definidos.
                                                @can('editar-catalogo')
                                                    <a href="{{ route('attributes.create') }}">Crear el primero</a>
                                                @endcan
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $attributes->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
