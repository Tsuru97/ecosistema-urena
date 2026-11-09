<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'product_id',
        'product_variant_id',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_sent'      => 'integer',
        'quantity_received'  => 'integer',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Nombre descriptivo de la línea (producto + variante si aplica) */
    public function displayName(): string
    {
        $name = $this->product?->name ?? '—';
        if ($this->variant) {
            $attrs = $this->variant->attributeValues
                ->map(fn($av) => $av->attribute->name . ': ' . $av->value)
                ->join(' / ');
            $name .= " ({$attrs})";
        }
        return $name;
    }
}
