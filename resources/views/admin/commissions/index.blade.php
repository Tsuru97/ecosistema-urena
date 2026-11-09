@extends('layouts.master')
@section('title') Comisiones de Venta @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Comisiones de Venta</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('root') }}">Ventas & Clientes</a></li>
                    <li class="breadcrumb-item active">Comisiones de Venta</li>
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

{{-- Tarjetas KPI --}}
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-1 fs-12">Ganadas (Por Liquidar)</p>
                        <h4 class="fs-22 fw-bold text-success mb-0">${{ number_format($kpis['total_earned'], 2) }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                            <i class="ri-money-dollar-circle-line"></i>
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
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-1 fs-12">Liquidadas / Pagadas</p>
                        <h4 class="fs-22 fw-bold text-primary mb-0">${{ number_format($kpis['total_paid'], 2) }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                            <i class="ri-checkbox-circle-line"></i>
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
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-1 fs-12">Pendientes en Apartados</p>
                        <h4 class="fs-22 fw-bold text-warning mb-0">${{ number_format($kpis['total_pending'], 2) }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                            <i class="ri-time-line"></i>
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
                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-1 fs-12">Prospección por Cupones</p>
                        <h4 class="fs-22 fw-bold text-info mb-0">${{ number_format($kpis['total_prospecting'], 2) }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                            <i class="ri-ticket-line"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabla Principal --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="ri-hand-coin-line text-primary me-1"></i>Libro Mayor de Comisiones</h5>
                <p class="text-muted small mb-0">Auditoría, desglose por matriz de reparto y liquidación de nómina de comisiones comerciales.</p>
            </div>
            <button type="button" class="btn btn-success btn-sm shadow-none" id="btn-batch-pay" onclick="submitBatchPay()" disabled>
                <i class="ri-checkbox-circle-line me-1"></i> Liquidar Seleccionadas
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card-body border-bottom bg-light py-2">
        <form method="GET" action="{{ route('commissions.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="seller_id" class="form-select form-select-sm">
                    <option value="">— Filtrar por Vendedor —</option>
                    @foreach($sellers as $s)
                        <option value="{{ $s->id }}" {{ request('seller_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} {{ $s->lastname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">— Estado —</option>
                    <option value="earned" {{ request('status') === 'earned' ? 'selected' : '' }}>Ganada (Por Liquidar)</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Pagada</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente (Apartado)</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select form-select-sm">
                    <option value="">— Rol / Origen —</option>
                    <option value="direct_sale" {{ request('role') === 'direct_sale' ? 'selected' : '' }}>Venta Directa (100%)</option>
                    <option value="coupon_owner" {{ request('role') === 'coupon_owner' ? 'selected' : '' }}>Prospección (Cupón)</option>
                    <option value="closing_seller" {{ request('role') === 'closing_seller' ? 'selected' : '' }}>Cierre en Mostrador</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control form-control-sm" title="Fecha Desde" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control form-control-sm" title="Fecha Hasta" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Filtrar"><i class="ri-filter-line"></i></button>
                <a href="{{ route('commissions.index') }}" class="btn btn-sm btn-light" title="Limpiar"><i class="ri-refresh-line"></i></a>
            </div>
        </form>
    </div>

    <form id="form-batch-pay" method="POST" action="{{ route('commissions.pay-multiple') }}">
        @csrf
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-12 text-muted">
                        <tr>
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="check-all" onclick="toggleAllChecks(this)">
                            </th>
                            <th>Ticket / Fecha</th>
                            <th>Vendedor Beneficiario</th>
                            <th>Rol / Modalidad</th>
                            <th>Venta Base</th>
                            <th>Tasa / Reparto</th>
                            <th>Monto Comisión</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $comm)
                        <tr>
                            <td class="ps-3">
                                @if($comm->status === 'earned')
                                    <input type="checkbox" name="commission_ids[]" value="{{ $comm->id }}" class="form-check-input comm-check" onchange="updateBatchButton()">
                                @else
                                    <input type="checkbox" class="form-check-input" disabled>
                                @endif
                            </td>
                            <td>
                                @if($comm->sale)
                                    <span class="fw-bold font-monospace text-primary">
                                        <i class="ri-receipt-line me-1"></i>{{ $comm->sale->ticket_folio }}
                                    </span>
                                    <small class="d-block text-muted">{{ $comm->created_at->format('d/m/Y H:i') }}</small>
                                    @if($comm->sale->customer)
                                        <small class="text-dark d-block text-truncate" style="max-width: 160px;" title="{{ $comm->sale->customer->full_name }}">
                                            {{ $comm->sale->customer->full_name }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">Venta #{{ $comm->sale_id }}</span>
                                @endif
                            </td>
                            <td>
                                @if($comm->seller)
                                    <strong class="text-dark">{{ $comm->seller->name }} {{ $comm->seller->lastname }}</strong>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($comm->role === 'direct_sale')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="ri-user-line me-1"></i>Venta Directa
                                    </span>
                                @elseif($comm->role === 'coupon_owner')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="ri-ticket-line me-1"></i>Prospección Cupón
                                    </span>
                                    @if($comm->coupon)
                                        <small class="d-block font-monospace text-muted">{{ $comm->coupon->code }}</small>
                                    @endif
                                @elseif($comm->role === 'closing_seller')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="ri-store-2-line me-1"></i>Cierre en Caja
                                    </span>
                                    @if($comm->coupon)
                                        <small class="d-block font-monospace text-muted">{{ $comm->coupon->code }}</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <span class="fw-medium">${{ number_format($comm->sale_amount, 2) }}</span>
                            </td>
                            <td>
                                <span class="fs-12">{{ number_format($comm->base_commission_rate, 2) }}% base</span>
                                @if($comm->split_percentage < 100)
                                    <small class="d-block text-muted">Reparto: {{ number_format($comm->split_percentage, 0) }}% (efectiva: {{ number_format($comm->final_rate, 2) }}%)</small>
                                @endif
                            </td>
                            <td>
                                <strong class="fs-14 text-success">${{ number_format($comm->commission_amount, 2) }}</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $comm->status_badge_class }} border px-2 py-1">
                                    @if($comm->status === 'earned')
                                        <i class="ri-checkbox-circle-line me-1"></i>Ganada
                                    @elseif($comm->status === 'paid')
                                        <i class="ri-check-double-line me-1"></i>Pagada
                                    @elseif($comm->status === 'pending')
                                        <i class="ri-time-line me-1"></i>Apartado Pendiente
                                    @elseif($comm->status === 'cancelled')
                                        <i class="ri-close-circle-line me-1"></i>Cancelada
                                    @endif
                                </span>
                                @if($comm->status === 'paid' && $comm->paid_at)
                                    <small class="d-block text-muted fs-11">{{ $comm->paid_at->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @if($comm->status === 'earned')
                                    <button type="button" class="btn btn-sm btn-outline-success shadow-none" onclick="paySingle({{ $comm->id }}, '{{ number_format($comm->commission_amount, 2) }}', '{{ $comm->seller ? $comm->seller->name : '' }}')" title="Marcar como pagada">
                                        <i class="ri-hand-coin-line me-1"></i> Liquidar
                                    </button>
                                @elseif($comm->status === 'pending')
                                    <span class="text-muted small" title="Se liquidará automáticamente cuando el cliente pague el apartado"><i class="ri-lock-line"></i> En espera</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ri-hand-coin-line fs-36 d-block mb-2 text-primary opacity-50"></i>
                                    <h5>No hay registros de comisiones</h5>
                                    <p class="small mb-0">Las comisiones se generan automáticamente al realizar ventas o apartados en el Punto de Venta.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($commissions->hasPages())
        <div class="card-footer bg-white border-top py-3">
            <div class="d-flex justify-content-end">
                {{ $commissions->links() }}
            </div>
        </div>
        @endif
    </form>
</div>
@endsection

@section('script')
<script>
    function toggleAllChecks(master) {
        const checks = document.querySelectorAll('.comm-check');
        checks.forEach(c => c.checked = master.checked);
        updateBatchButton();
    }

    function updateBatchButton() {
        const checked = document.querySelectorAll('.comm-check:checked').length;
        const btn = document.getElementById('btn-batch-pay');
        if (btn) {
            btn.disabled = checked === 0;
            btn.innerHTML = `<i class="ri-checkbox-circle-line me-1"></i> Liquidar Seleccionadas (${checked})`;
        }
    }

    function submitBatchPay() {
        const checked = document.querySelectorAll('.comm-check:checked').length;
        if (checked === 0) return;

        Swal.fire({
            title: '¿Confirmar liquidación masiva?',
            text: `Se marcarán como PAGADAS ${checked} comisiones comerciales.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, liquidar en nómina',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-batch-pay').submit();
            }
        });
    }

    function paySingle(commissionId, amount, sellerName) {
        Swal.fire({
            title: 'Liquidar Comisión',
            html: `¿Confirmas el pago de <strong>$${amount}</strong> a <strong>${sellerName}</strong>?`,
            icon: 'info',
            input: 'text',
            inputPlaceholder: 'Notas de liquidación (ej: Nómina quincena 1)...',
            showCancelButton: true,
            confirmButtonText: 'Sí, liquidar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/commissions/${commissionId}/pay`;

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);

                if (result.value) {
                    const notesInput = document.createElement('input');
                    notesInput.type = 'hidden';
                    notesInput.name = 'notes';
                    notesInput.value = result.value;
                    form.appendChild(notesInput);
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endsection