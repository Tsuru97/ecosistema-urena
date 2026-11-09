<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductAttribute extends Pivot
{
    protected $table = 'product_attribute';

    public $incrementing = true;

    protected $fillable = [
        'product_id', 'attribute_id', 'is_for_variation', 'order',
    ];

    protected $casts = [
        'is_for_variation' => 'boolean',
        'order'            => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function values()
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_attribute_value',
            'product_attribute_id',
            'attribute_value_id'
        )->withTimestamps();
    }
}
