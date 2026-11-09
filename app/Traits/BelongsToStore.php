<?php

namespace App\Traits;

use App\Models\Store;
use App\Scopes\StoreScope;

trait BelongsToStore
{
    /**
     * Boot del trait: aplica scope global por tienda y auto-inyecta store_id al crear.
     */
    protected static function bootBelongsToStore()
    {
        // Scope global: filtra siempre por la tienda activa
        static::addGlobalScope(new StoreScope);

        // Al crear un registro, inyectar automáticamente el store_id
        static::creating(function ($model) {
            if (!$model->store_id && function_exists('current_store_id') && current_store_id()) {
                $model->store_id = current_store_id();
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
