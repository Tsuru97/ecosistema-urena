<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class AttributeValue extends Model
{
    use Auditable;

    protected $fillable = [
        'attribute_id', 'value', 'slug', 'color_hex', 'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_attribute_value',
            'attribute_value_id',
            'product_variant_id'
        )->withTimestamps();
    }
}
