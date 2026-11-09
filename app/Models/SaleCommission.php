<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class SaleCommission extends Model
{
    use Auditable;

    protected $fillable = [
        'sale_id',
        'seller_id',
        'coupon_id',
        'role',
        'sale_amount',
        'base_commission_rate',
        'split_percentage',
        'final_rate',
        'commission_amount',
        'status',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'base_commission_rate' => 'decimal:2',
        'split_percentage' => 'decimal:2',
        'final_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeEarned($query)
    {
        return $query->where('status', 'earned');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeBySeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Retorna una etiqueta amigable del rol de comisión.
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'direct_sale' => 'Venta Directa',
            'coupon_owner' => 'Prospección (Dueño del Cupón)',
            'closing_seller' => 'Cierre en Caja / Mostrador',
            default => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }

    /**
     * Retorna clase CSS badge para el estado.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'earned' => 'bg-success-subtle text-success border-success-subtle',
            'paid' => 'bg-primary-subtle text-primary border-primary-subtle',
            'pending' => 'bg-warning-subtle text-warning border-warning-subtle',
            'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
            default => 'bg-light text-muted',
        };
    }
}