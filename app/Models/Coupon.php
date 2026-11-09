<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Carbon\Carbon;

class Coupon extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_purchase',
        'seller_id',
        'customer_id',
        'store_id',
        'usage_limit',
        'usage_limit_per_customer',
        'times_used',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'times_used' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeForStore($query, ?int $storeId)
    {
        return $query->where(function ($q) use ($storeId) {
            $q->whereNull('store_id');
            if ($storeId) {
                $q->orWhere('store_id', $storeId);
            }
        });
    }

    public function scopeForCustomer($query, ?int $customerId)
    {
        return $query->where(function ($q) use ($customerId) {
            $q->whereNull('customer_id');
            if ($customerId) {
                $q->orWhere('customer_id', $customerId);
            }
        });
    }

    // ── Helper Methods ──────────────────────────────────────────────────────

    /**
     * Valida si el cupón puede ser canjeado bajo las condiciones de la venta.
     */
    public function isValidFor(float $subtotal, ?int $customerId = null, ?int $storeId = null, ?string &$errorMsg = null): bool
    {
        if (!$this->status) {
            $errorMsg = "El cupón {$this->code} está inactivo.";
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            $errorMsg = "El cupón {$this->code} aún no entra en vigencia (inicia el " . $this->starts_at->format('d/m/Y') . ").";
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            $errorMsg = "El cupón {$this->code} ha expirado el " . $this->expires_at->format('d/m/Y') . ".";
            return false;
        }

        if ($this->store_id && $storeId && (int)$this->store_id !== (int)$storeId) {
            $storeName = $this->store ? $this->store->name : 'otra sucursal';
            $errorMsg = "El cupón {$this->code} es exclusivo para la sucursal {$storeName}.";
            return false;
        }

        if ($this->customer_id && (int)$this->customer_id !== (int)$customerId) {
            $clientName = $this->customer ? $this->customer->full_name : 'un cliente específico';
            $errorMsg = "El cupón {$this->code} es personal e intransferible para el cliente {$clientName}.";
            return false;
        }

        if ($this->min_purchase > 0 && $subtotal < (float)$this->min_purchase) {
            $errorMsg = "El cupón requiere una compra mínima de $" . number_format($this->min_purchase, 2) . " (subtotal actual: $" . number_format($subtotal, 2) . ").";
            return false;
        }

        if (!is_null($this->usage_limit) && $this->times_used >= $this->usage_limit) {
            $errorMsg = "El cupón {$this->code} ha alcanzado su límite máximo de usos permitido ({$this->usage_limit}).";
            return false;
        }

        if ($customerId && $this->usage_limit_per_customer > 0) {
            $customerUsages = $this->redemptions()->where('customer_id', $customerId)->count();
            if ($customerUsages >= $this->usage_limit_per_customer) {
                $errorMsg = "El cliente ya ha utilizado este cupón el número máximo de veces permitidas ({$this->usage_limit_per_customer}).";
                return false;
            }
        }

        return true;
    }

    /**
     * Calcula el importe en pesos a descontar sobre una base de importe.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = round($subtotal * (((float)$this->discount_value) / 100), 2);
            if (!is_null($this->max_discount_amount) && (float)$this->max_discount_amount > 0) {
                $discount = min($discount, (float)$this->max_discount_amount);
            }
            return max(0.00, min($subtotal, $discount));
        }

        // Descuento fijo
        $discount = (float)$this->discount_value;
        return max(0.00, min($subtotal, $discount));
    }

    /**
     * Revierte el canje de un cupón cuando una venta es cancelada.
     */
    public static function revertRedemptionForSale(Sale $sale): void
    {
        if (!$sale->coupon_id && !\Illuminate\Support\Facades\Schema::hasTable('coupon_redemptions')) {
            return;
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('coupon_redemptions')) {
            $deleted = CouponRedemption::where('sale_id', $sale->id)->delete();
            if ($sale->coupon_id) {
                $coupon = self::withTrashed()->find($sale->coupon_id);
                if ($coupon) {
                    $actualCount = CouponRedemption::where('coupon_id', $coupon->id)->count();
                    $coupon->times_used = max(0, $actualCount);
                    $coupon->save();
                }
            }
        } elseif ($sale->coupon_id) {
            $coupon = self::withTrashed()->find($sale->coupon_id);
            if ($coupon && $coupon->times_used > 0) {
                $coupon->decrement('times_used');
            }
        }
    }
}