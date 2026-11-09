<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\Sluggable;
use App\Traits\Cacheable;

class Attribute extends Model
{
    use SoftDeletes, Auditable, Sluggable, Cacheable;

    protected $fillable = [
        'name', 'slug', 'type', 'is_variation', 'order', 'status',
    ];

    protected $casts = [
        'is_variation' => 'boolean',
        'status'       => 'boolean',
        'order'        => 'integer',
    ];

    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute')
                    ->using(ProductAttribute::class)
                    ->withPivot(['id', 'is_for_variation', 'order'])
                    ->withTimestamps();
    }
}
