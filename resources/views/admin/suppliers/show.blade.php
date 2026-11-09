@extends('layouts.master')
@section('title') Proveedor: {{ $supplier->name }} @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        {{-- Ficha del proveedor --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h5 class="card-title mb-0 flex-grow-1"><i class="ri-truck-line me-2"></i>Ficha de Proveedor</h5>
                    @can('editar-proveedores')
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary"><i class="ri-edit-line"></i></a>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-24">
                                <i class="ri-truck-line"></i>
                            </span>
                        </div>
                        <h5 class="mb-1">{{ $supplier->name }}</h5>
                        <span class="badge {{ $supplier->status ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} fs-11">
                            {{ $supplier->status ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row" class="ps-0" style="width: 110px;">RFC:</th>
                                    <td class="text-muted"><code>{{ $supplier->rfc ?: '—' }}</code></td>
                                </tr>
                                <tr>
                                    <th scope="row" class="ps-0">Email:</th>
                                    <td class="text-muted">{{ $supplier->email ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="ps-0">Teléfono:</th>
                                    <td class="text-muted">{{ $supplier->phone ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="ps-0">Condiciones:</th>
                                    <td class="text-muted">
                                        {{ $supplier->credit_days > 0 ? $supplier->credit_days . ' días de crédito' : 'Pago al contado' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row" class="ps-0">Dirección:</th>
                                    <td class="text-muted"><small>{{ $supplier->address ?: '—' }}</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Compras realizadas --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h5 class="card-title mb-0 flex-grow-1"><i class="ri-history-line me-2"></i>Últimas Órdenes de Compra</h5>
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-ghost-secondary btn-sm"><i class="ri-list-check me-1"></i>Ver todas</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Sucursal Destino</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Total</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supplier->purchaseOrders as $order)
                                    @php $badge = $order->statusBadge(); @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('purchase-orders.show', $order) }}" class="fw-semibold text-dark">{{ $order->folio }}</a>
                                        </td>
                                        <td>{{ $order->store->name }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $badge['color'] }}">{{ $badge['label'] }}</span>
                                        </td>
                                        <td class="text-end fw-bold">${{ number_format($order->total, 2) }}</td>
                                        <td class="text-muted fs-12">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No hay órdenes de compra registradas para este proveedor todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
