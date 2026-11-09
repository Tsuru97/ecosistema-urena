<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Cacheable;
use App\Traits\Auditable;

class Warehouse extends Model
{
    use SoftDeletes, Cacheable, Auditable;

    protected $fillable = [
        'store_id',
        'name',
        'type',
        'allow_sale',
        'is_default',
        'status',
    ];

    protected $casts = [
        'allow_sale' => 'boolean',
        'is_default' => 'boolean',
        'status'     => 'boolean',
    ];

    // ──────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeExhibition(Builder $query): Builder
    {
        return $query->where('type', 'exhibition');
    }

    public function scopeWarehousesOnly(Builder $query): Builder
    {
        return $query->where('type', 'warehouse');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function isExhibition(): bool
    {
        return $this->type === 'exhibition';
    }

    /**
     * Asegura las bodegas predeterminadas para una sucursal (Piso de Exhibición + Bodega Principal).
     */
    public static function ensureDefaultWarehousesForStore(int $storeId): void
    {
        // 1. Bodega Principal
        $defaultWarehouse = static::where('store_id', $storeId)->where('is_default', true)->first();
        if (!$defaultWarehouse) {
            static::create([
                'store_id'   => $storeId,
                'name'       => 'Bodega Principal',
                'type'       => 'warehouse',
                'allow_sale' => true,
                'is_default' => true,
                'status'     => true,
            ]);
        }

        // 2. Piso de Exhibición
        $exhibitionWarehouse = static::where('store_id', $storeId)->where('type', 'exhibition')->first();
        if (!$exhibitionWarehouse) {
            static::create([
                'store_id'   => $storeId,
                'name'       => 'Piso de Exhibición',
                'type'       => 'exhibition',
                'allow_sale' => true,
                'is_default' => false,
                'status'     => true,
            ]);
        }
    }
}
