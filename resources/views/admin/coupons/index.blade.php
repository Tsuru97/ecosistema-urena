@extends('layouts.master')
@section('title') Cupones de Descuento @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Cupones de Descuento</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('root') }}">Ventas & Clientes</a></li>
                    <li class="breadcrumb-item active">Cupones de Descuento</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Mensajes Flash --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-double-line me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Tarjetas KPI --}}
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-1 fs-12">Cupones Activos</p>
                        <h4 class="fs-22 fw-bold text-success mb-0">{{ number_format($kpis['total_active']) }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                            <i class="ri-ticket-line"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-1 fs-12">Total Redenciones</p>
                        <h4 class="fs-22 fw-bold text-primary mb-0">{{ number_format($kpis['total_redemptions']) }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                            <i class="ri-shopping-cart-2-line"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-1 fs-12">Descuento Otorgado</p>
                        <h4 class="fs-22 fw-bold text-warning mb-0">${{ number_format($kpis['total_discount_given'], 2) }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                            <i class="ri-hand-coin-line"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-1 fs-12">Total de Cupones</p>
                        <h4 class="fs-22 fw-bold text-info mb-0">{{ number_format($kpis['total_coupons']) }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                            <i class="ri-folder-zip-line"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabla Principal y Filtros --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="ri-coupon-3-line text-primary me-1"></i>Listado de Cupones</h5>
                <p class="text-muted small mb-0">Gestiona cupones de descuento asignados a clientes, asesores o sucursales con reparto de comisiones.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-coupon">
                <i class="ri-add-line align-middle me-1"></i> Nuevo Cupón
            </button>
        </div>
    </div>
    <div class="card-body border-bottom bg-light py-2">
        <form method="GET" action="{{ route('coupons.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Buscar por código o nombre..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="seller_id" class="form-select form-select-sm">
                    <option value="">— Filtrar por Vendedor —</option>
                    @foreach($sellers as $s)
                        <option value="{{ $s->id }}" {{ request('seller_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} {{ $s->lastname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="store_id" class="form-select form-select-sm">
                    <option value="">— Sucursal —</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">— Estado —</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="ri-filter-line me-1"></i> Filtrar</button>
                <a href="{{ route('coupons.index') }}" class="btn btn-sm btn-light" title="Limpiar filtros"><i class="ri-refresh-line"></i></a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-12 text-muted">
                    <tr>
                        <th class="ps-3">Código</th>
                        <th>Nombre / Descripción</th>
                        <th>Descuento</th>
                        <th>Beneficiario / Pertenencia</th>
                        <th class="text-center">Usos / Límite</th>
                        <th>Vigencia</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12 font-monospace px-2 py-1">
                                <i class="ri-ticket-2-line me-1"></i>{{ $coupon->code }}
                            </span>
                        </td>
                        <td>
                            <strong class="text-dark d-block">{{ $coupon->name }}</strong>
                            <small class="text-muted">{{ Str::limit($coupon->description, 45) ?: 'Sin descripción adicional' }}</small>
                        </td>
                        <td>
                            @if($coupon->discount_type === 'percentage')
                                <span class="badge bg-success text-white fs-12">{{ number_format($coupon->discount_value, 0) }}% OFF</span>
                                @if($coupon->max_discount_amount)
                                    <small class="d-block text-muted">Tope: ${{ number_format($coupon->max_discount_amount, 2) }}</small>
                                @endif
                            @else
                                <span class="badge bg-info text-white fs-12">${{ number_format($coupon->discount_value, 2) }} OFF</span>
                            @endif
                            @if($coupon->min_purchase > 0)
                                <small class="d-block text-muted">Mín: ${{ number_format($coupon->min_purchase, 2) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($coupon->seller)
                                <div class="fs-12 text-dark fw-semibold">
                                    <i class="ri-user-star-line text-info me-1"></i>{{ $coupon->seller->name }} {{ $coupon->seller->lastname }}
                                </div>
                            @else
                                <span class="badge bg-light text-muted border">General de Tienda</span>
                            @endif
                            @if($coupon->customer)
                                <small class="d-block text-muted"><i class="ri-user-heart-line text-danger me-1"></i>{{ $coupon->customer->full_name }}</small>
                            @endif
                            @if($coupon->store)
                                <small class="d-block text-muted"><i class="ri-store-2-line text-secondary me-1"></i>{{ $coupon->store->name }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-soft-info py-0 px-2" onclick='showRedemptions({{ json_encode($coupon->code) }}, {{ json_encode($coupon->redemptions ?? []) }})' title="Ver historial de canjes">
                                <span class="fw-bold fs-13">{{ $coupon->times_used }}</span>
                                <span class="text-muted fs-11">/ {{ $coupon->usage_limit ? $coupon->usage_limit : '∞' }}</span>
                                <i class="ri-history-line ms-1"></i>
                            </button>
                        </td>
                        <td>
                            @if($coupon->expires_at)
                                <span class="fs-12 {{ $coupon->expires_at->isPast() ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    <i class="ri-calendar-event-line me-1"></i>{{ $coupon->expires_at->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success fs-11">Sin vencimiento</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('coupons.toggle-status', $coupon->id) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent shadow-none" title="Cambiar estado">
                                    @if($coupon->status)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="ri-checkbox-circle-line me-1"></i>Activo</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="ri-close-circle-line me-1"></i>Inactivo</span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-primary shadow-none" onclick="editCoupon({{ json_encode($coupon) }})" title="Editar cupón">
                                <i class="ri-edit-line"></i>
                            </button>
                            <form method="POST" action="{{ route('coupons.destroy', $coupon->id) }}" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar el cupón {{ $coupon->code }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger shadow-none" title="Eliminar cupón">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-ticket-line fs-36 d-block mb-2 text-primary opacity-50"></i>
                                <h5>No se encontraron cupones registrados</h5>
                                <p class="small mb-3">Crea cupones promocionales para que tus vendedores ganen comisiones compartidas por prospección.</p>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-create-coupon">
                                    <i class="ri-add-line me-1"></i> Crear Primer Cupón
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($coupons->hasPages())
    <div class="card-footer bg-white border-top py-3">
        <div class="d-flex justify-content-end">
            {{ $coupons->links() }}
        </div>
    </div>
    @endif
</div>

{{-- MODAL: CREAR CUPÓN --}}
<div class="modal fade" id="modal-create-coupon" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="ri-ticket-line me-2"></i>Crear Nuevo Cupón de Descuento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('coupons.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Código del Cupón <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="code" id="create-code" class="form-control text-uppercase fw-bold" placeholder="EJ: JUAN2026" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="generateRandomCode('create-code')" title="Generar código aleatorio">
                                    <i class="ri-magic-line"></i> Generar
                                </button>
                            </div>
                            <small class="text-muted">Código único en mayúsculas para canjear en el POS.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre de Campaña / Promoción <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Ej: Bono de Prospección Carlos" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción o Notas Internas</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Condiciones o notas operativas del cupón..."></textarea>
                        </div>

                        <hr class="my-2">

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipo de Descuento <span class="text-danger">*</span></label>
                            <select name="discount_type" id="create-discount-type" class="form-select" onchange="toggleDiscountFields('create')" required>
                                <option value="percentage">Porcentaje (%)</option>
                                <option value="fixed">Monto Fijo ($ MXN)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Valor del Descuento <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="100" name="discount_value" id="create-discount-value" class="form-control fw-bold" placeholder="Ej: 10 o 500" required>
                        </div>
                        <div class="col-md-4" id="create-max-discount-col">
                            <label class="form-label">Tope Máximo de Descuento ($)</label>
                            <input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control" placeholder="Opcional (ej: 500.00)">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Compra Mínima Requerida ($)</label>
                            <input type="number" step="0.01" min="0" name="min_purchase" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Límite Global de Canjes</label>
                            <input type="number" min="1" name="usage_limit" class="form-control" placeholder="Vacío = Ilimitado">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Límite por Cliente</label>
                            <input type="number" min="1" name="usage_limit_per_customer" class="form-control" value="1">
                        </div>

                        <hr class="my-2">
                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-1"><i class="ri-shield-user-line me-1"></i>Matriz de Comisiones & Segmentación</h6>
                            <p class="text-muted small mb-2">Si asignas un vendedor, cada vez que este cupón se redima en el POS se generará comisión compartida con el vendedor cajero según la matriz.</p>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-info"><i class="ri-user-star-line me-1"></i>Vendedor Comisionista / Dueño</label>
                            <select name="seller_id" class="form-select">
                                <option value="">— Ninguno (Comisión 100% en caja) —</option>
                                @foreach($sellers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} {{ $s->lastname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger"><i class="ri-user-heart-line me-1"></i>Cliente Exclusivo (Opcional)</label>
                            <select name="customer_id" class="form-select">
                                <option value="">— Cualquier Cliente —</option>
                                @foreach($customers ?? [] as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sucursal Específica</label>
                            <select name="store_id" class="form-select">
                                <option value="">— Todas las Sucursales —</option>
                                @foreach($stores as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha de Inicio (Opcional)</label>
                            <input type="datetime-local" name="starts_at" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Vencimiento (Opcional)</label>
                            <input type="datetime-local" name="expires_at" class="form-control">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" class="form-check-input" name="status" id="create-status" value="1" checked>
                                <label class="form-check-label fw-semibold" for="create-status">Cupón Activo e Habilitado para Canje</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="ri-save-line me-1"></i> Crear Cupón
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: EDITAR CUPÓN --}}
<div class="modal fade" id="modal-edit-coupon" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="ri-edit-line me-2"></i>Editar Cupón de Descuento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-edit-coupon" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Código del Cupón <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="edit-code" class="form-control text-uppercase fw-bold" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre de Campaña <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit-name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" id="edit-description" class="form-control" rows="2"></textarea>
                        </div>

                        <hr class="my-2">

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipo de Descuento <span class="text-danger">*</span></label>
                            <select name="discount_type" id="edit-discount-type" class="form-select" onchange="toggleDiscountFields('edit')" required>
                                <option value="percentage">Porcentaje (%)</option>
                                <option value="fixed">Monto Fijo ($ MXN)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Valor del Descuento <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="discount_value" id="edit-discount-value" class="form-control fw-bold" required>
                        </div>
                        <div class="col-md-4" id="edit-max-discount-col">
                            <label class="form-label">Tope Máximo de Descuento ($)</label>
                            <input type="number" step="0.01" min="0" name="max_discount_amount" id="edit-max-discount-amount" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Compra Mínima ($)</label>
                            <input type="number" step="0.01" min="0" name="min_purchase" id="edit-min-purchase" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Límite Global de Canjes</label>
                            <input type="number" min="1" name="usage_limit" id="edit-usage-limit" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Límite por Cliente</label>
                            <input type="number" min="1" name="usage_limit_per_customer" id="edit-usage-limit-per-customer" class="form-control">
                        </div>

                        <hr class="my-2">

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-info"><i class="ri-user-star-line me-1"></i>Vendedor Comisionista / Dueño</label>
                            <select name="seller_id" id="edit-seller-id" class="form-select">
                                <option value="">— Ninguno (Comisión 100% en caja) —</option>
                                @foreach($sellers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} {{ $s->lastname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger"><i class="ri-user-heart-line me-1"></i>Cliente Exclusivo (Opcional)</label>
                            <select name="customer_id" id="edit-customer-id" class="form-select">
                                <option value="">— Cualquier Cliente —</option>
                                @foreach($customers ?? [] as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sucursal Específica</label>
                            <select name="store_id" id="edit-store-id" class="form-select">
                                <option value="">— Todas las Sucursales —</option>
                                @foreach($stores as $st)
                                    <option value="{{ $st->id }}">{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha de Inicio</label>
                            <input type="datetime-local" name="starts_at" id="edit-starts-at" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Vencimiento</label>
                            <input type="datetime-local" name="expires_at" id="edit-expires-at" class="form-control">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" class="form-check-input" name="status" id="edit-status" value="1">
                                <label class="form-check-label fw-semibold" for="edit-status">Cupón Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4">
                        <i class="ri-save-line me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: HISTORIAL DE CANJES / REDENCIONES --}}
<div class="modal fade" id="modal-coupon-redemptions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold text-white">
                    <i class="ri-history-line me-2"></i>Historial de Canjes del Cupón: <span id="redemptions-coupon-code" class="font-monospace"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-12 text-muted">
                            <tr>
                                <th class="ps-3">Fecha / Hora</th>
                                <th>Folio Venta</th>
                                <th>Cliente</th>
                                <th>Vendedor Cierre</th>
                                <th class="text-end pe-3">Descuento Aplicado</th>
                            </tr>
                        </thead>
                        <tbody id="redemptions-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function generateRandomCode(targetInputId) {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById(targetInputId).value = code;
    }

    function toggleDiscountFields(prefix) {
        const type = document.getElementById(prefix + '-discount-type').value;
        const maxCol = document.getElementById(prefix + '-max-discount-col');
        const valInput = document.getElementById(prefix + '-discount-value');
        if (type === 'percentage') {
            maxCol.style.display = 'block';
            if (valInput) valInput.setAttribute('max', '100');
        } else {
            maxCol.style.display = 'none';
            if (valInput) valInput.removeAttribute('max');
        }
    }

    function formatLocalDatetime(isoStr) {
        if (!isoStr) return '';
        const d = new Date(isoStr);
        if (isNaN(d.getTime())) return String(isoStr).substring(0, 16);
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function editCoupon(coupon) {
        const form = document.getElementById('form-edit-coupon');
        form.action = `/coupons/${coupon.id}`;

        document.getElementById('edit-code').value = coupon.code;
        document.getElementById('edit-name').value = coupon.name;
        document.getElementById('edit-description').value = coupon.description || '';
        document.getElementById('edit-discount-type').value = coupon.discount_type;
        document.getElementById('edit-discount-value').value = coupon.discount_value;
        document.getElementById('edit-max-discount-amount').value = coupon.max_discount_amount || '';
        document.getElementById('edit-min-purchase').value = coupon.min_purchase || 0;
        document.getElementById('edit-usage-limit').value = coupon.usage_limit || '';
        document.getElementById('edit-usage-limit-per-customer').value = coupon.usage_limit_per_customer || 1;
        document.getElementById('edit-seller-id').value = coupon.seller_id || '';
        document.getElementById('edit-customer-id').value = coupon.customer_id || '';
        document.getElementById('edit-store-id').value = coupon.store_id || '';

        document.getElementById('edit-starts-at').value = formatLocalDatetime(coupon.starts_at);
        document.getElementById('edit-expires-at').value = formatLocalDatetime(coupon.expires_at);

        document.getElementById('edit-status').checked = !!coupon.status;

        toggleDiscountFields('edit');

        const modal = new bootstrap.Modal(document.getElementById('modal-edit-coupon'));
        modal.show();
    }

    function showRedemptions(code, redemptions) {
        document.getElementById('redemptions-coupon-code').innerText = code;
        const tbody = document.getElementById('redemptions-tbody');
        tbody.innerHTML = '';

        if (!redemptions || redemptions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="ri-inbox-line fs-24 d-block mb-1"></i>
                        Este cupón aún no registra redenciones en ventas activas.
                    </td>
                </tr>`;
        } else {
            redemptions.forEach(r => {
                const dateStr = r.created_at ? new Date(r.created_at).toLocaleString('es-MX') : '-';
                const folio = r.sale ? r.sale.ticket_folio : `Venta #${r.sale_id}`;
                const custName = r.customer
                    ? ((r.customer.name || '') + ' ' + (r.customer.lastname || r.customer.last_name || '')).trim() || r.customer.business_name || 'Cliente'
                    : 'Público en General';
                const sellerName = r.seller
                    ? ((r.seller.name || '') + ' ' + (r.seller.lastname || '')).trim()
                    : (r.cashier ? r.cashier.name : 'N/A');
                const disc = parseFloat(r.discount_amount || 0).toFixed(2);

                tbody.innerHTML += `
                    <tr>
                        <td class="ps-3 fs-12">${dateStr}</td>
                        <td><a href="/pos/sales?search=${encodeURIComponent(folio)}" class="fw-bold text-primary font-monospace">${folio}</a></td>
                        <td>${custName}</td>
                        <td><span class="badge bg-info-subtle text-info">${sellerName}</span></td>
                        <td class="text-end pe-3 fw-bold text-success font-monospace">-$${disc}</td>
                    </tr>`;
            });
        }

        const modal = new bootstrap.Modal(document.getElementById('modal-coupon-redemptions'));
        modal.show();
    }
</script>
@endsection