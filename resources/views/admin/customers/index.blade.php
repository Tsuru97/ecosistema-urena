@extends('layouts.master')
@section('title') Clientes @endsection

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
                        <i class="ri-user-star-line me-2"></i>Catálogo de Clientes
                    </h4>
                    @can('crear-clientes')
                    <a href="{{ route('customers.create') }}" class="btn btn-success btn-label btn-sm waves-effect">
                        <i class="ri-add-line label-icon align-middle me-2"></i> Nuevo Cliente
                    </a>
                    @endcan
                </div>

                <div class="card-body">
                    {{-- Buscador --}}
                    <form action="{{ route('customers.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm"
                                           value="{{ request('search') }}" placeholder="Buscar por cliente, Razón Social, RFC, email, teléfono...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-search-line me-1"></i> Buscar
                                </button>
                                @if(request('search'))
                                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm ms-1">
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
                                    <th scope="col" style="width: 70px;">
                                        <a href="{{ route('customers.index', array_merge(request()->all(), ['sort_field' => 'id', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                            #
                                            @if(request('sort_field') == 'id')
                                                <i class="ri-arrow-{{ request('sort_direction') == 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('customers.index', array_merge(request()->all(), ['sort_field' => 'business_name', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                            Cliente / Razón Social
                                            @if(request('sort_field') == 'business_name')
                                                <i class="ri-arrow-{{ request('sort_direction') == 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('customers.index', array_merge(request()->all(), ['sort_field' => 'rfc', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                            RFC
                                            @if(request('sort_field') == 'rfc')
                                                <i class="ri-arrow-{{ request('sort_direction') == 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">Contacto</th>
                                    <th scope="col">
                                        <a href="{{ route('customers.index', array_merge(request()->all(), ['sort_field' => 'credit_limit', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                            Límite de Crédito
                                            @if(request('sort_field') == 'credit_limit')
                                                <i class="ri-arrow-{{ request('sort_direction') == 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="text-center">
                                        <a href="{{ route('customers.index', array_merge(request()->all(), ['sort_field' => 'status', 'sort_direction' => request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                            Estado
                                            @if(request('sort_field') == 'status')
                                                <i class="ri-arrow-{{ request('sort_direction') == 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                    <tr>
                                        <td><code>#{{ $customer->id }}</code></td>
                                        <td>
                                            <div class="fw-semibold fs-14">
                                                <a href="{{ route('customers.show', $customer) }}" class="text-dark">
                                                    {{ $customer->full_name }}
                                                </a>
                                            </div>
                                            @if($customer->business_name && $customer->business_name !== $customer->full_name)
                                                <small class="text-muted d-block"><i class="ri-building-line me-1"></i>{{ $customer->business_name }}</small>
                                            @endif
                                        </td>
                                        <td><code>{{ $customer->rfc ?: '—' }}</code></td>
                                        <td>
                                            @if($customer->email)
                                                <div class="fs-13"><i class="ri-mail-line me-1 align-middle text-muted"></i>{{ $customer->email }}</div>
                                            @endif
                                            @if($customer->phone)
                                                <div class="fs-13 text-muted"><i class="ri-phone-line me-1 align-middle text-muted"></i>{{ $customer->phone }}</div>
                                            @endif
                                            @if(!$customer->email && !$customer->phone)
                                                <span class="text-muted small">Sin contacto</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($customer->credit_limit > 0)
                                                <span class="badge bg-success-subtle text-success fs-12 fw-medium">
                                                    ${{ number_format($customer->credit_limit, 2) }}
                                                </span>
                                                <small class="text-muted d-block fs-11">
                                                    Disp: ${{ number_format($customer->available_credit, 2) }}
                                                </small>
                                            @else
                                                <span class="badge bg-light text-secondary border">Sin Crédito</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $customer->status ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ $customer->status ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="hstack gap-1">
                                                <a href="{{ route('customers.show', $customer) }}"
                                                   class="btn btn-sm btn-outline-info" title="Ver Expediente">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @can('editar-clientes')
                                                <a href="{{ route('customers.edit', $customer) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                @endcan
                                                @can('eliminar-clientes')
                                                <form id="del-cust-{{ $customer->id }}" action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar / Desactivar"
                                                        data-confirm="true"
                                                        data-form="del-cust-{{ $customer->id }}"
                                                        data-title="Eliminar Cliente"
                                                        data-message="¿Estás seguro de eliminar o desactivar al cliente <strong>{{ $customer->full_name }}</strong>?"
                                                        data-btn-label="Sí, eliminar">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="ri-user-star-line fs-24 d-block mb-2 text-muted"></i>
                                            <p class="text-muted mb-0">No se encontraron clientes registrados.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $customers->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
