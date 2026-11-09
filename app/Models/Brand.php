<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\Sluggable;
use App\Traits\Seoable;
use App\Traits\Cacheable;

class Brand extends Model
{
    use SoftDeletes, Auditable, Sluggable, Seoable, Cacheable;

    protected $fillable = ['name', 'slug', 'description', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
