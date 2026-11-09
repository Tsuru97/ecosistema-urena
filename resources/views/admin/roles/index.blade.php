@extends('layouts.master')
@section('title') Gestión de Roles @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-shield-user-line me-2"></i>Roles del Sistema
                    </h4>
                    @can('crear-roles')
                    <div class="flex-shrink-0">
                        <a href="{{ route('roles.create') }}" class="btn btn-success btn-label waves-effect waves-light btn-sm">
                            <i class="ri-add-line label-icon align-middle fs-16 me-2"></i> Nuevo Rol
                        </a>
                    </div>
                    @endcan
                </div>

                <div class="card-body">
                    {{-- Alertas de sesión --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Buscador --}}
                    <form action="{{ route('roles.index') }}" method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm"
                                           value="{{ $search }}" placeholder="Buscar rol por nombre...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-search-line me-1"></i> Buscar
                                </button>
                                @if($search)
                                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
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
                                    <th scope="col" style="width: 60px;">
                                        <a href="{{ route('roles.index', array_merge(request()->all(), ['sort_field' => 'id', 'sort_direction' => $sortField === 'id' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            #
                                            @if($sortField === 'id')
                                                <i class="ri-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('roles.index', array_merge(request()->all(), ['sort_field' => 'name', 'sort_direction' => $sortField === 'name' && $sortDir === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Nombre del Rol
                                            @if($sortField === 'name')
                                                <i class="ri-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">Permisos Asignados</th>
                                    <th scope="col" style="width: 130px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $key => $role)
                                    <tr>
                                        <td>{{ $roles->firstItem() + $key }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2 flex-shrink-0">
                                                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                                        <i class="ri-shield-user-line"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $role->name }}</h6>
                                                    @if(in_array($role->name, ['Super Admin', 'Dirección']))
                                                        <span class="badge bg-danger-subtle text-danger fs-10">Protegido</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @forelse($role->permissions->take(6) as $permission)
                                                <span class="badge bg-primary-subtle text-primary me-1 mb-1">
                                                    {{ $permission->name_public ?: $permission->name }}
                                                </span>
                                            @empty
                                                <span class="badge bg-secondary-subtle text-secondary">Sin permisos</span>
                                            @endforelse
                                            @if($role->permissions->count() > 6)
                                                <span class="badge bg-info-subtle text-info">
                                                    +{{ $role->permissions->count() - 6 }} más
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="hstack gap-3 flex-wrap">
                                                @can('editar-roles')
                                                <a href="{{ route('roles.edit', $role->id) }}" class="link-info fs-15" title="Editar rol">
                                                    <i class="ri-edit-2-line"></i>
                                                </a>
                                                @endcan

                                                @can('eliminar-roles')
                                                @if(!in_array($role->name, ['Super Admin', 'Dirección']))
                                                <form id="del-role-{{ $role->id }}" action="{{ route('roles.destroy', $role->id) }}" method="POST" style="display:none;">
                                                    @csrf @method('DELETE')
                                                </form>
                                                <button type="button" class="link-danger fs-15 border-0 bg-transparent p-0"
                                                    data-confirm
                                                    data-form="del-role-{{ $role->id }}"
                                                    data-title="Eliminar Rol"
                                                    data-message="¿Estás seguro de eliminar el rol <strong>{{ $role->name }}</strong>? Los usuarios que lo tengan asignado perderán estos permisos."
                                                    data-btn-label="Sí, eliminar"
                                                    title="Eliminar rol">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                                @else
                                                <span class="text-muted fs-15" title="Este rol está protegido">
                                                    <i class="ri-lock-line"></i>
                                                </span>
                                                @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="ri-shield-user-line fs-24 d-block mb-2 text-muted"></i>
                                            No se encontraron roles.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $roles->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
