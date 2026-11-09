@extends('layouts.master')
@section('title') Movimientos: {{ $product->name }} @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">

            {{-- Encabezado del producto --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-md">
                                <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-20">
                                    <i class="ri-box-3-line"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1">{{ $product->name }}</h5>
                                <div class="d-flex gap-3 text-muted fs-13">
                                    <span>SKU: <strong>{{ $product->sku }}</strong></span>
                                    @if($product->brand)
                                        <span>Marca: <strong>{{ $product->brand->name }}</strong></span>
                                    @endif
                                    @if($inventory)
                                        <span>Stock actual: <strong class="{{ $inventory->isAlert() ? 'text-warning' : 'text-success' }}">{{ $inventory->quantity }}</strong></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            @can('gestionar-inventario')
                            <a href="{{ route('inventory.adjust', $product->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ri-equalizer-line me-1"></i> Ajustar
                            </a>
                            @endcan
                            <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-ghost-secondary">
                                <i class="ri-arrow-left-line me-1"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabla de movimientos --}}
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <i class="ri-history-line me-2"></i>Historial de Movimientos
                    </h5>
                    <span class="badge bg-secondary">{{ $movements->total() }} registros</span>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    @if(is_global_view())
                                        <th scope="col">Sucursal</th>
                                    @endif
                                    <th scope="col">Tipo</th>
                                    <th scope="col">Variante</th>
                                    <th scope="col" class="text-center">Movimiento</th>
                                    <th scope="col" class="text-center">Antes</th>
                                    <th scope="col" class="text-center">Después</th>
                                    <th scope="col">Referencia</th>
                                    <th scope="col">Registrado por</th>
                                    <th scope="col">Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $mov)
                                    <tr>
                                        <td class="fs-12 text-muted">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                        @if(is_global_view())
                                            <td>
                                                <span class="badge bg-info-subtle text-info fw-medium">
                                                    {{ $mov->store?->name ?? '—' }}
                                                </span>
                                            </td>
                                        @endif
                                        <td>
                                            <span class="badge bg-{{ $mov->direction_color }}-subtle text-{{ $mov->direction_color }}">
                                                {{ $mov->type_label }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($mov->variant)
                                                @php
                                                    $avLabels = $mov->variant->attributeValues->map(fn($av) =>
                                                        '<span class="badge bg-light text-dark border me-1">'
                                                        . ($av->color_hex ? '<span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:' . $av->color_hex . ';vertical-align:middle"></span>' : '')
                                                        . e($av->attribute->name ?? '') . ': ' . e($av->value)
                                                        . '</span>'
                                                    )->implode('');
                                                @endphp
                                                {!! $avLabels !!}
                                                <br><small class="text-muted">SKU: {{ $mov->variant->sku }}</small>
                                            @else
                                                <span class="text-muted">— (Producto Simple)</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold {{ $mov->direction === 'in' ? 'text-success' : 'text-danger' }}">
                                                {{ $mov->direction === 'in' ? '+' : '-' }}{{ $mov->quantity }}
                                            </span>
                                        </td>
                                        <td class="text-center text-muted">{{ $mov->quantity_before }}</td>
                                        <td class="text-center fw-semibold">{{ $mov->quantity_after }}</td>
                                        <td>
                                            @if($mov->reference)
                                                <span class="badge bg-info-subtle text-info">
                                                    {{ class_basename($mov->reference_type) }} #{{ $mov->reference_id }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fs-12">{{ $mov->user?->name ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-12 text-muted" title="{{ $mov->notes }}">
                                                {{ Str::limit($mov->notes, 40) ?: '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ is_global_view() ? 10 : 9 }}" class="text-center py-4 text-muted">
                                            <i class="ri-history-line fs-24 d-block mb-2"></i>
                                            Sin movimientos registrados para este producto.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $movements->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
