<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Inventory extends Model
{
    use Cacheable, Auditable;

    protected $fillable = [
        'store_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'reserved_quantity',
        'min_stock',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'reserved_quantity' => 'integer',
        'min_stock'         => 'integer',
    ];

    // ──────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function movements()
    {
        $relation = $this->hasMany(InventoryMovement::class, 'product_id', 'product_id')
                         ->where('store_id', $this->store_id);

        if ($this->product_variant_id === null) {
            $relation->whereNull('product_variant_id');
        } else {
            $relation->where('product_variant_id', $this->product_variant_id);
        }

        if ($this->warehouse_id) {
            $relation->where('warehouse_id', $this->warehouse_id);
        }

        return $relation;
    }

    // ──────────────────────────────────────────
    // Scopes & Accessors
    // ──────────────────────────────────────────

    /** Stock disponible para venta inmediata (Físico - Reservados por Apartados) */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /** Productos con stock en o por debajo del mínimo de seguridad */
    public function scopeAlerts(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'min_stock')
                     ->where('min_stock', '>', 0);
    }

    /** Filtrar por sucursal */
    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    /** Filtrar por bodega */
    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /** Productos sin stock (agotados) */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /** ¿Está en alerta de stock mínimo? */
    public function isAlert(): bool
    {
        return $this->min_stock > 0 && $this->quantity <= $this->min_stock;
    }

    /** ¿Está agotado? */
    public function isOutOfStock(): bool
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Registra un movimiento y actualiza el stock/reservas.
     * Método central para gestionar el inventario multisucursal y multibodega.
     */
    public static function applyMovement(
        int     $storeId,
        int     $productId,
        string  $type,
        int     $quantity,
        int     $userId,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int    $referenceId = null,
        ?int    $productVariantId = null,
        ?int    $warehouseId = null
    ): self {
        // Asegurar bodegas predeterminadas si no se especificó bodega
        if (!$warehouseId) {
            Warehouse::ensureDefaultWarehousesForStore($storeId);
            $defaultWh = Warehouse::where('store_id', $storeId)->where('is_default', true)->first();
            $warehouseId = $defaultWh ? $defaultWh->id : null;
        }

        // Obtener o crear registro de inventario (robusto ante registros heredados o índices antiguos)
        $inventory = static::where('store_id', $storeId)
            ->where('product_id', $productId)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId), fn($q) => $q->whereNull('warehouse_id'))
            ->when($productVariantId === null, fn($q) => $q->whereNull('product_variant_id'), fn($q) => $q->where('product_variant_id', $productVariantId))
            ->first();

        if (!$inventory && $warehouseId) {
            // Si no existe para la bodega específica, asociar cualquier registro antiguo con warehouse_id NULL
            $legacyInv = static::where('store_id', $storeId)
                ->where('product_id', $productId)
                ->whereNull('warehouse_id')
                ->when($productVariantId === null, fn($q) => $q->whereNull('product_variant_id'), fn($q) => $q->where('product_variant_id', $productVariantId))
                ->first();

            if ($legacyInv) {
                $legacyInv->update(['warehouse_id' => $warehouseId]);
                $inventory = $legacyInv;
            }
        }

        if (!$inventory) {
            try {
                $inventory = static::create([
                    'store_id'           => $storeId,
                    'warehouse_id'       => $warehouseId,
                    'product_id'         => $productId,
                    'product_variant_id' => $productVariantId,
                    'quantity'           => 0,
                    'reserved_quantity'  => 0,
                    'min_stock'          => 0,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Si la base de datos conserva el índice único inv_store_prod_var_unique sin la columna warehouse_id:
                $inventory = static::where('store_id', $storeId)
                    ->where('product_id', $productId)
                    ->when($productVariantId === null, fn($q) => $q->whereNull('product_variant_id'), fn($q) => $q->where('product_variant_id', $productVariantId))
                    ->first();

                if ($inventory) {
                    $inventory->update(['warehouse_id' => $warehouseId]);
                } else {
                    throw $e;
                }
            }
        }

        $quantityBefore = $inventory->quantity;
        $reservedBefore = $inventory->reserved_quantity;
        $quantityAfter  = $quantityBefore;
        $reservedAfter  = $reservedBefore;
        $direction      = 'in';

        if ($type === 'reserva_apartado') {
            // Reserva stock para apartado (incrementa el apartado en bodega sin sacarlo físicamente aún)
            $reservedAfter = $reservedBefore + $quantity;
            $direction = 'out';
        } elseif ($type === 'liberacion_apartado') {
            // Entrega final de un apartado liquidado (libera la reserva y realiza la salida física)
            $reservedAfter = max(0, $reservedBefore - $quantity);
            $quantityAfter = max(0, $quantityBefore - $quantity);
            $direction = 'out';
        } elseif ($type === 'ajuste') {
            // Ajuste manual directo: establece el valor absoluto de stock ingresado
            $quantityAfter = max(0, $quantity);
            $direction = $quantityAfter >= $quantityBefore ? 'in' : 'out';
        } else {
            // Movimientos estándar de entrada / salida
            $outTypes  = ['salida', 'traspaso_salida', 'venta', 'devolucion_compra'];
            $direction = in_array($type, $outTypes) ? 'out' : 'in';

            $quantityAfter = $direction === 'in'
                ? $quantityBefore + $quantity
                : max(0, $quantityBefore - $quantity);
        }

        // Actualizar stock e inventario reservado
        $inventory->update([
            'quantity'          => $quantityAfter,
            'reserved_quantity' => $reservedAfter,
        ]);

        // Registrar trazabilidad de movimiento
        try {
            InventoryMovement::create([
                'store_id'           => $storeId,
                'warehouse_id'       => $warehouseId,
                'product_id'         => $productId,
                'product_variant_id' => $productVariantId,
                'type'               => $type,
                'direction'          => $direction,
                'quantity'           => $quantity,
                'quantity_before'    => $quantityBefore,
                'quantity_after'     => $quantityAfter,
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'notes'              => $notes,
                'user_id'            => $userId,
            ]);
        } catch (\Illuminate\Database\QueryException $qe) {
            // Si la columna 'type' en la base de datos es un ENUM antiguo que rechaza 'reserva_apartado' o 'liberacion_apartado' (error 1265 o 01000)
            if (str_contains($qe->getMessage(), 'Data truncated') || str_contains($qe->getMessage(), '1265')) {
                $fallbackType = ($direction === 'out') ? 'salida' : 'ajuste';
                InventoryMovement::create([
                    'store_id'           => $storeId,
                    'warehouse_id'       => $warehouseId,
                    'product_id'         => $productId,
                    'product_variant_id' => $productVariantId,
                    'type'               => $fallbackType,
                    'direction'          => $direction,
                    'quantity'           => $quantity,
                    'quantity_before'    => $quantityBefore,
                    'quantity_after'     => $quantityAfter,
                    'reference_type'     => $referenceType,
                    'reference_id'       => $referenceId,
                    'notes'              => "[Tipo: {$type}] " . ($notes ?? ''),
                    'user_id'            => $userId,
                ]);
            } else {
                throw $qe;
            }
        }

        return $inventory->fresh();
    }
}
