<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialOrderItem extends Model
{
    protected $fillable = [
        'special_order_id',
        'product_id',
        'product_variant_id',
        'purchase_order_id',
        'quantity',
        'quantity_received',
        'unit_price',
        'notes',
        'received_at',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'quantity_received' => 'integer',
        'unit_price'        => 'decimal:2',
        'received_at'       => 'datetime',
    ];

    // ──────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────

    public function specialOrder()
    {
        return $this->belongsTo(SpecialOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function pendingQuantity(): int
    {
        return max(0, $this->quantity - $this->quantity_received);
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity;
    }

    public function lineTotal(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }
}
