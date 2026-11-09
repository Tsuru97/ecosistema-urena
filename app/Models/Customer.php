<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\Cacheable;

class Customer extends Model
{
    use SoftDeletes, Auditable, Cacheable;

    protected $fillable = [
        'user_id',
        'name',
        'lastname',
        'email',
        'phone',
        'rfc',
        'business_name',
        'legal_address',
        'street',
        'colonia',
        'city',
        'state',
        'zip',
        'delivery_references',
        'delivery_instructions',
        'delivery_lat',
        'delivery_lng',
        'delivery_maps_url',
        'credit_limit',
        'available_credit',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'credit_limit' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'delivery_lat' => 'float',
        'delivery_lng' => 'float',
    ];

    /**
     * Retorna la dirección completa de entrega formateada o la fiscal si está vacía.
     */
    public function getFormattedDeliveryAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->colonia ? 'Col. ' . $this->colonia : null,
            $this->zip ? 'C.P. ' . $this->zip : null,
            $this->city,
            $this->state,
        ]);

        if (!empty($parts)) {
            return implode(', ', $parts);
        }

        return $this->legal_address ?: 'Sin dirección registrada';
    }

    /**
     * Indica si el cliente cuenta con geolocalización GPS fijada.
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->delivery_lat) && !is_null($this->delivery_lng);
    }

    /**
     * El usuario ligado a este cliente (si tiene acceso al sistema)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function specialOrders()
    {
        return $this->hasMany(SpecialOrder::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->name ?? '') . ' ' . ($this->lastname ?? ''));
        if (!empty($name)) {
            return $name;
        }

        if ($this->user) {
            $userName = trim(($this->user->name ?? '') . ' ' . ($this->user->lastname ?? ''));
            if (!empty($userName)) {
                return $userName;
            }
        }

        return $this->business_name ?: 'Cliente #' . $this->id;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->attributes['email'] ?? $this->user?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['phone'] ?? $this->user?->phone;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->attributes['legal_address'] ?? null;
    }

    /**
     * Verifica si el cliente tiene crédito disponible suficiente para un monto dado.
     */
    public function hasAvailableCredit(float $amount): bool
    {
        return (float)$this->available_credit >= $amount;
    }

    /**
     * Consume parte de la línea de crédito disponible (ej. al registrar un apartado con saldo).
     */
    public function consumeCredit(float $amount): void
    {
        $this->available_credit = max(0, (float)$this->available_credit - $amount);
        $this->save();
    }

    /**
     * Restituye crédito disponible (ej. al realizar un abono o cancelar un apartado).
     */
    public function releaseCredit(float $amount): void
    {
        $limit = (float)$this->credit_limit;
        $this->available_credit = min($limit, (float)$this->available_credit + $amount);
        $this->save();
    }

    /**
     * Recompensa de línea de crédito tras liquidar una compra según porcentaje configurado.
     */
    public function rewardCreditFromSale(Sale $sale, float $percentage): void
    {
        if ($percentage <= 0) return;

        $increase = round((float)$sale->total * ($percentage / 100), 2);
        if ($increase > 0) {
            $this->credit_limit = (float)$this->credit_limit + $increase;
            $this->available_credit = (float)$this->available_credit + $increase;
            $this->save();

            activity('pos')
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->log("Línea de crédito incrementada en $" . number_format($increase, 2) . " ({$percentage}%) por compra liquidada ticket {$sale->ticket_folio}. Nuevo límite: $" . number_format($this->credit_limit, 2));
        }
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'customer_id');
    }

    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }
}
