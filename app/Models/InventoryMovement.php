<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovement extends Model
{
    protected $fillable = [
        'store_id',
        'warehouse_id',
        'target_warehouse_id',
        'product_id',
        'product_variant_id',
        'type',
        'direction',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity'        => 'integer',
        'quantity_before' => 'integer',
        'quantity_after'  => 'integer',
    ];

    // ──────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Referencia polimórfica al documento de origen */
    public function reference()
    {
        return $this->morphTo();
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeOfType(Builder $query, string|array $type): Builder
    {
        return is_array($type)
            ? $query->whereIn('type', $type)
            : $query->where('type', $type);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /** Etiqueta legible del tipo de movimiento */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'entrada'         => 'Entrada manual',
            'salida'          => 'Salida manual',
            'ajuste'          => 'Ajuste de inventario',
            'traspaso_salida' => 'Traspaso enviado',
            'traspaso_entrada'=> 'Traspaso recibido',
            'compra'          => 'Recepción de compra',
            'venta'           => 'Venta',
            'devolucion_venta'=> 'Devolución de venta',
            'devolucion_compra'=> 'Devolución a proveedor',
            default           => $this->type,
        };
    }

    /** Color de badge según dirección */
    public function getDirectionColorAttribute(): string
    {
        return $this->direction === 'in' ? 'success' : 'danger';
    }
}
