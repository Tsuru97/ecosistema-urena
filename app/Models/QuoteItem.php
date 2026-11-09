<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'base_cost',
        'unit_price_original',
        'unit_price_final',
        'tax_amount',
        'ieps_amount',
        'iva_amount',
        'subtotal',
        'total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'base_cost' => 'decimal:2',
        'unit_price_original' => 'decimal:2',
        'unit_price_final' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'ieps_amount' => 'decimal:2',
        'iva_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
