<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, Cacheable, Auditable;

    protected $fillable = [
        'supplier_id',
        'store_id',
        'folio',
        'rv_folio',
        'issued_at',
        'invoice_pdf_path',
        'invoice_xml_path',
        'status',
        'expected_at',
        'notes',
        'total',
        'gross_amount',
        'discount_amount',
        'net_amount',
        'amount_paid',
        'payment_status',
        'unit_status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    public function hasInvoicePdf(): bool { return !empty($this->invoice_pdf_path); }
    public function hasInvoiceXml(): bool { return !empty($this->invoice_xml_path); }


    protected $casts = [
        'expected_at'     => 'date',
        'issued_at'       => 'date',
        'approved_at'     => 'datetime',
        'total'           => 'decimal:2',
        'gross_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount'      => 'decimal:2',
        'amount_paid'     => 'decimal:2',
    ];

    // ── Relaciones ─────────────────────────────────────────────────

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    // ── Helpers financieros (Gestión RVs) ─────────────────────────

    /**
     * Calcula la suma del costo de las piezas efectivamente recibidas en almacén.
     */
    public function receivedTotal(): float
    {
        return (float) $this->items()->selectRaw('SUM(quantity_received * cost) as total')->value('total') ?? 0;
    }

    /**
     * Calcula el monto monetario devuelto o no recibido (merma / piezas dañadas).
     */
    public function unreceivedAmount(): float
    {
        if (!$this->isRecibida()) {
            return 0;
        }

        $gross = $this->effectiveGrossAmount();
        $rec   = $this->receivedTotal();
        return max(0, $gross - $rec);
    }

    public function effectiveNetAmount(): float
    {
        $net = (float) $this->net_amount;
        if ($net > 0) {
            return $net;
        }

        // Si ya fue recibida, el neto efectivo por defecto es el costo de lo efectivamente recibido
        if ($this->isRecibida()) {
            $recTotal = $this->receivedTotal();
            if ($recTotal > 0) {
                return $recTotal;
            }
        }

        return (float) $this->total;
    }

    public function effectiveGrossAmount(): float
    {
        $gross = (float) $this->gross_amount;
        if ($gross > 0) {
            return $gross;
        }
        return (float) $this->total;
    }

    public function effectiveDiscountAmount(): float
    {
        return (float) $this->discount_amount;
    }


    public function pendingBalance(): float
    {
        return max(0, $this->effectiveNetAmount() - (float) $this->amount_paid);
    }

    public function paymentEfficiency(): float
    {
        $net = $this->effectiveNetAmount();
        if ($net <= 0) return 0;
        return round(((float) $this->amount_paid / $net) * 100, 1);
    }

    /**
     * Recalcula amount_paid y payment_status en base a los SupplierPayments registrados.
     * Se llama automáticamente al añadir/eliminar un pago.
     */
    public function recalculatePaymentStatus(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $net  = $this->effectiveNetAmount();

        $status = match(true) {
            $paid <= 0              => 'pendiente',
            $paid >= ($net - 0.01)  => 'pagada',
            default                 => 'pago_parcial',
        };

        $this->update([
            'gross_amount'   => (float) $this->gross_amount > 0 ? $this->gross_amount : $net,
            'net_amount'     => $net,
            'amount_paid'    => $paid,
            'payment_status' => $status,
        ]);

        self::flushCache();
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isPendiente(): bool { return $this->status === 'pendiente'; }
    public function isAprobada(): bool  { return $this->status === 'aprobada'; }
    public function isRecibida(): bool  { return $this->status === 'recibida'; }
    public function isCancelada(): bool { return $this->status === 'cancelada'; }
    public function isFinished(): bool  { return in_array($this->status, ['recibida', 'cancelada']); }

    public function statusBadge(): array
    {
        return match ($this->status) {
            'pendiente' => ['label' => 'Pendiente',  'color' => 'warning'],
            'aprobada'  => ['label' => 'Aprobada',   'color' => 'info'],
            'recibida'  => ['label' => 'Recibida',   'color' => 'success'],
            'cancelada' => ['label' => 'Cancelada',  'color' => 'secondary'],
            default     => ['label' => $this->status, 'color' => 'light'],
        };
    }

    // ── Folio auto-generado ──────────────────────────────────────────

    public static function generateFolio(): string
    {
        $year = now()->year;
        $prefix = "OC-{$year}-";

        $last = static::withoutGlobalScopes()
            ->where('folio', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('folio');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // ── Acciones de Estado ────────────────────────────────────────────

    /**
     * Aprueba la orden de compra.
     */
    public function approve(int $userId): void
    {
        if (!$this->isPendiente()) {
            throw new \LogicException("Solo se pueden aprobar órdenes de compra en estado Pendiente.");
        }

        $this->update([
            'status'      => 'aprobada',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Cancela la orden de compra.
     */
    public function cancel(): void
    {
        if ($this->isFinished()) {
            throw new \LogicException("No se puede cancelar una orden de compra ya finalizada.");
        }

        $this->update([
            'status' => 'cancelada',
        ]);
    }

    /**
     * Devuelve 'completa' o 'incompleta' calculando dinámicamente si las cantidades recibidas cubren las ordenadas.
     */
    public function calculatedUnitStatus(): string
    {
        if ($this->unit_status === 'completa') {
            return 'completa';
        }

        if ($this->isRecibida()) {
            $totalOrdered  = (int) $this->items()->sum('quantity_ordered');
            $totalReceived = (int) $this->items()->sum('quantity_received');

            if ($totalOrdered > 0 && $totalReceived >= $totalOrdered) {
                return 'completa';
            }
        }

        return 'incompleta';
    }

    /**
     * Confirma la recepción de mercancía, suma el stock físico y actualiza costos en catálogo.
     */
    public function receive(int $userId, array $receivedQuantities = [], ?int $warehouseId = null): void
    {
        if (!$this->isAprobada()) {
            throw new \LogicException("Solo se pueden recibir mercancías de órdenes en estado Aprobada.");
        }

        DB::transaction(function () use ($userId, $receivedQuantities, $warehouseId) {
            $totalOrdered  = 0;
            $totalReceived = 0;

            foreach ($this->items()->with(['product', 'variant'])->get() as $item) {
                // Cantidad confirmada o la solicitada por defecto
                $qtyReceived   = max(0, (int) ($receivedQuantities[$item->id] ?? $item->quantity_ordered));
                $totalOrdered  += (int) $item->quantity_ordered;
                $totalReceived += $qtyReceived;

                // 1. Sumar stock en la sucursal/bodega de destino
                Inventory::applyMovement(
                    storeId:          $this->store_id,
                    productId:        $item->product_id,
                    type:             'compra',
                    quantity:         $qtyReceived,
                    userId:           $userId,
                    notes:            "Compra recibida folio {$this->folio}",
                    referenceType:    PurchaseOrder::class,
                    referenceId:      $this->id,
                    productVariantId: $item->product_variant_id,
                    warehouseId:      $warehouseId,
                );

                // 2. Actualizar costo unitario en catálogo
                if ($item->product_variant_id && $item->variant) {
                    $item->variant->update(['cost' => $item->cost]);
                } elseif ($item->product) {
                    $item->product->update(['cost' => $item->cost]);
                }

                // Guardar cantidad recibida y estatus por línea
                $itemUnitStatus = ($qtyReceived >= $item->quantity_ordered) ? 'completa' : 'incompleta';
                $itemLineStatus = ($qtyReceived >= $item->quantity_ordered) ? 'cerrada' : 'abierta';

                $item->update([
                    'quantity_received' => $qtyReceived,
                    'unit_status'       => $itemUnitStatus,
                    'line_status'       => $itemLineStatus,
                ]);
            }

            $unitStatus       = ($totalOrdered > 0 && $totalReceived >= $totalOrdered) ? 'completa' : 'incompleta';
            $receivedNetTotal = (float) $this->items()->selectRaw('SUM(quantity_received * cost) as total')->value('total') ?? 0;

            $gross  = (float) $this->gross_amount > 0 ? (float) $this->gross_amount : (float) $this->total;
            $newNet = $receivedNetTotal > 0 ? $receivedNetTotal : $gross;

            $paid    = (float) $this->payments()->sum('amount');
            $pStatus = match(true) {
                $paid <= 0                => 'pendiente',
                $paid >= ($newNet - 0.01) => 'pagada',
                default                   => 'pago_parcial',
            };

            $this->update([
                'status'         => 'recibida',
                'unit_status'    => $unitStatus,
                'gross_amount'   => $gross,
                'net_amount'     => $newNet,
                'payment_status' => $pStatus,
            ]);

            self::flushCache();
        });
    }
}
