<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\Sluggable;
use App\Traits\Seoable;
use App\Traits\Cacheable;

class Product extends Model
{
    use SoftDeletes, Auditable, Sluggable, Seoable, Cacheable;

    protected $fillable = [
        'sku', 'name', 'slug', 'description',
        'base_cost', 'category_id', 'brand_id', 'supplier_id', 'status', 'type',
        'ieps_type', 'ieps_value', 'is_wildcard',
    ];

    protected $casts = [
        'status'      => 'boolean',
        'is_wildcard' => 'boolean',
        'base_cost'   => 'decimal:2',
        'ieps_value'  => 'float',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute')
                    ->using(ProductAttribute::class)
                    ->withPivot(['id', 'is_for_variation', 'order'])
                    ->withTimestamps()
                    ->orderBy('product_attribute.order');
    }

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    // ──────────────────────────────────────────
    // Helpers de tipo de producto
    // ──────────────────────────────────────────

    public function isVariable(): bool
    {
        return $this->type === 'variable';
    }

    public function isSimple(): bool
    {
        return $this->type === 'simple' || $this->type === null;
    }

    /** Producto físico (simple o variable) — valida y consume stock. */
    public function isPhysical(): bool
    {
        return in_array($this->type, ['simple', 'variable']);
    }

    /** Producto digital o de concepto — no requiere inventario. */
    public function isVirtual(): bool
    {
        return $this->type === 'virtual';
    }

    /** Servicio (instalación, flete, mano de obra) — no requiere inventario. */
    public function isService(): bool
    {
        return $this->type === 'service';
    }

    /** Producto comodín configurable desde el POS (precio y nombre libres). */
    public function isWildcard(): bool
    {
        return (bool) $this->is_wildcard;
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopePhysical($query)
    {
        return $query->whereIn('type', ['simple', 'variable']);
    }

    public function scopeVirtual($query)
    {
        return $query->where('type', 'virtual');
    }

    public function scopeService($query)
    {
        return $query->where('type', 'service');
    }

    public function defaultVariant()
    {
        return $this->variants()
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->first();
    }

    public function priceRange(): array
    {
        if ($this->isSimple()) {
            return ['min' => $this->base_cost, 'max' => $this->base_cost];
        }

        $prices = $this->variants()
                       ->where('is_active', true)
                       ->pluck('price')
                       ->map(fn($p) => $p ?? $this->base_cost)
                       ->filter(fn($p) => $p !== null);

        if ($prices->isEmpty()) {
            return ['min' => $this->base_cost, 'max' => $this->base_cost];
        }

        return ['min' => $prices->min(), 'max' => $prices->max()];
    }

    public function getPriceForStore($store, string $type)
    {
        $baseCost = (float)($this->base_cost ?? 0);

        // Obtener el ID de la sucursal
        $storeId = null;
        if ($store instanceof Store) {
            $storeId = $store->id;
        } elseif (is_numeric($store)) {
            $storeId = (int)$store;
        }

        // Obtener el margen configurado específicamente para esta sucursal (o global si es nulo) (Multiplicador)
        $marginKey = 'margin_' . $type;
        $marginMultiplier = (float)Setting::get($marginKey, $this->getDefaultMargin($type), $storeId);
        if ($marginMultiplier <= 0) {
            $marginMultiplier = 1.0;
        }

        // Fórmula exacta: Costo base * Multiplicador
        return round($baseCost * $marginMultiplier, 2);
    }

    private function getDefaultMargin(string $type): string
    {
        return match ($type) {
            'lista' => '2.50',
            'venta' => '2.00',
            'rebajado' => '1.80',
            'outlet' => '1.50',
            default => '1.00',
        };
    }

    public function getSlugNameAttribute()
    {
        return $this->name . ' ' . $this->sku;
    }

    public function getSlugSourceAttribute(): string
    {
        return 'slug_name';
    }
}
