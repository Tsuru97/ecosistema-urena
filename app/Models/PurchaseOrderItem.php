<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_variant_id',
        'quantity_ordered',
        'quantity_received',
        'quantity_paid',
        'quantity_returned',
        'cost',
        'discount_percent',
        'discount_amount',
        'gross_amount',
        'net_amount',
        'amount_paid',
        'line_status',
        'unit_status',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered'  => 'integer',
        'quantity_received' => 'integer',
        'quantity_paid'     => 'integer',
        'quantity_returned' => 'integer',
        'cost'              => 'decimal:2',
        'discount_percent'  => 'float',
        'discount_amount'   => 'decimal:2',
        'gross_amount'      => 'decimal:2',
        'net_amount'        => 'decimal:2',
        'amount_paid'       => 'decimal:2',
    ];

    // ── Relaciones ─────────────────────────────────────────────────

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // ── Helpers ─────────────────────────────────────────

    /** Unidades pendientes de recibir. */
    public function pendingQuantity(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    /** True si ya se recibieron todas las unidades solicitadas. */
    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    /** Importe bruto de la línea (cantidad ordenada × costo unitario). */
    public function lineGross(): float
    {
        return round((float) $this->cost * $this->quantity_ordered, 2);
    }

    /** Importe neto de la línea (bruto - descuento). */
    public function lineNet(): float
    {
        return round($this->lineGross() - (float) $this->discount_amount, 2);
    }

    /** Saldo pendiente de pago de esta línea. */
    public function pendingBalance(): float
    {
        return max(0, (float) $this->net_amount - (float) $this->amount_paid);
    }
}
