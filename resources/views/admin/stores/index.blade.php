@extends('layouts.master')
@section('title') Sucursales @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Sucursales</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('stores.create') }}" class="btn btn-success btn-label btn-sm">
                            <i class="ri-add-line label-icon align-middle fs-16 me-2"></i> Agregar Nueva
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('stores.index') }}" method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nombre, código o ciudad..." value="{{ $search ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 50px;">#</th>
                                    <th scope="col">
                                        <a href="{{ route('stores.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => ($sortBy ?? '') === 'name' && ($sortDir ?? '') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Nombre
                                            @if(($sortBy ?? '') === 'name')
                                                <i class="ri-arrow-{{ ($sortDir ?? '') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('stores.index', array_merge(request()->all(), ['sort' => 'code', 'direction' => ($sortBy ?? '') === 'code' && ($sortDir ?? '') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Código
                                            @if(($sortBy ?? '') === 'code')
                                                <i class="ri-arrow-{{ ($sortDir ?? '') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('stores.index', array_merge(request()->all(), ['sort' => 'city', 'direction' => ($sortBy ?? '') === 'city' && ($sortDir ?? '') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Ciudad
                                            @if(($sortBy ?? '') === 'city')
                                                <i class="ri-arrow-{{ ($sortDir ?? '') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">Teléfono</th>
                                    <th scope="col">Usuarios</th>
                                    <th scope="col">Estatus</th>
                                    <th scope="col" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stores as $key => $store)
                                    <tr>
                                        <td>{{ $stores->firstItem() + $key }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2 flex-shrink-0">
                                                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                                        <i class="ri-store-2-line"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $store->name }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-info-subtle text-info">{{ $store->code }}</span></td>
                                        <td>{{ $store->city ?? '—' }}</td>
                                        <td>{{ $store->phone ?? '—' }}</td>
                                        <td><span class="badge bg-secondary">{{ $store->users_count }}</span></td>
                                        <td>
                                            @if($store->status)
                                                <span class="badge bg-success">Activa</span>
                                            @else
                                                <span class="badge bg-danger">Inactiva</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="hstack gap-3 flex-wrap">
                                                <a href="{{ route('stores.edit', $store->id) }}" class="link-info fs-15">
                                                    <i class="ri-edit-2-line"></i>
                                                </a>
                                                <form id="del-store-{{ $store->id }}" action="{{ route('stores.destroy', $store->id) }}" method="POST" style="display:none;">
                                                    @csrf @method('DELETE')
                                                </form>
                                                <button type="button" class="btn btn-link link-danger fs-15 p-0 btn-sm"
                                                    data-confirm
                                                    data-form="del-store-{{ $store->id }}"
                                                    data-title="Eliminar Sucursal"
                                                    data-message="¿Estás seguro de eliminar la sucursal <strong>{{ $store->name }}</strong>? Esta acción no se puede deshacer."
                                                    data-btn-label="Sí, eliminar">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="ri-store-2-line fs-24 d-block mb-2"></i>
                                            No se encontraron sucursales.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $stores->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
