<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class ProductVariant extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'product_id', 'sku', 'price', 'cost', 'barcode',
        'is_default', 'is_active', 'weight',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'cost'       => 'decimal:2',
        'weight'     => 'decimal:2',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_attribute_value',
            'product_variant_id',
            'attribute_value_id'
        )->withTimestamps();
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getEffectivePriceAttribute()
    {
        return $this->price ?? optional($this->product)->base_cost;
    }

    public function getDisplayNameAttribute(): string
    {
        $values = $this->attributeValues->pluck('value')->implode(' / ');
        $productName = optional($this->product)->name ?? '';
        return trim($productName . ($values ? " — {$values}" : ''));
    }
}
