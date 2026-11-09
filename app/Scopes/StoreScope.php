<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreScope implements Scope
{
    /**
     * Aplica filtro por store_id.
     * Si current_store_id() es null (vista Global) → no filtra → muestra todo.
     * Si tiene valor → filtra por esa tienda específica.
     */
    public function apply(Builder $builder, Model $model)
    {
        if (function_exists('current_store_id') && current_store_id()) {
            $builder->where($model->getTable() . '.store_id', current_store_id());
        }
    }
}
