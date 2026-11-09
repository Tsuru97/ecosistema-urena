@extends('layouts.master')
@section('title') Marcas / Proveedores @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Marcas / Proveedores</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('brands.create') }}" class="btn btn-success btn-label btn-sm">
                            <i class="ri-add-line label-icon align-middle fs-16 me-2"></i> Agregar Marca
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Buscador y filtros --}}
                    <form action="{{ route('brands.index') }}" method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm" value="{{ request('search') }}" placeholder="Buscar por nombre...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Todos los estados</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                                </select>
                            </div>
                            <div class="col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-search-line me-1"></i> Buscar
                                </button>
                                @if(request()->hasAny(['search', 'status']))
                                    <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="ri-close-line me-1"></i> Limpiar
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 50px;">
                                        <a href="{{ route('brands.index', array_merge(request()->except('sort_field', 'sort_direction'), ['sort_field' => 'id', 'sort_direction' => request('sort_field') === 'id' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            ID
                                            @if(request('sort_field') === 'id')
                                                <i class="ri-arrow-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('brands.index', array_merge(request()->except('sort_field', 'sort_direction'), ['sort_field' => 'name', 'sort_direction' => request('sort_field') === 'name' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Nombre
                                            @if(request('sort_field') === 'name')
                                                <i class="ri-arrow-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col">
                                        <a href="{{ route('brands.index', array_merge(request()->except('sort_field', 'sort_direction'), ['sort_field' => 'status', 'sort_direction' => request('sort_field') === 'status' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Estado
                                            @if(request('sort_field') === 'status')
                                                <i class="ri-arrow-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('brands.index', array_merge(request()->except('sort_field', 'sort_direction'), ['sort_field' => 'created_at', 'sort_direction' => request('sort_field') === 'created_at' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Creado
                                            @if(request('sort_field') === 'created_at')
                                                <i class="ri-arrow-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($brands as $brand)
                                    <tr>
                                        <td>{{ $brand->id }}</td>
                                        <td>
                                            <h5 class="fs-14 mb-1">{{ $brand->name }}</h5>
                                            <p class="text-muted mb-0 fs-12">{{ $brand->slug }}</p>
                                        </td>
                                        <td>
                                            <span class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                                {{ Str::limit($brand->description, 50, '...') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($brand->status)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>{{ $brand->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-info">
                                                    <i class="ri-pencil-fill"></i>
                                                </a>
                                                <form id="del-brand-{{ $brand->id }}" action="{{ route('brands.destroy', $brand) }}" method="POST" style="display:none;">
                                                    @csrf @method('DELETE')
                                                </form>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    data-confirm
                                                    data-form="del-brand-{{ $brand->id }}"
                                                    data-title="Eliminar Marca"
                                                    data-message="¿Estás seguro de eliminar la marca <strong>{{ $brand->name }}</strong>? Esta acción no se puede deshacer."
                                                    data-btn-label="Sí, eliminar">
                                                    <i class="ri-delete-bin-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No hay marcas registradas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $brands->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
