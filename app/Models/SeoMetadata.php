<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $fillable = [
        'seoable_id',
        'seoable_type',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image_id'
    ];

    /**
     * Obtener el modelo padre (Producto, Categoría, Marca, etc.)
     */
    public function seoable()
    {
        return $this->morphTo();
    }
}
