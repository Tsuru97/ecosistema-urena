<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Quote extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'folio',
        'store_id',
        'user_id',
        'seller_id',
        'customer_id',
        'subtotal',
        'ieps_total',
        'iva_total',
        'tax_total',
        'discount_total',
        'total',
        'status',
        'expires_at',
        // Entrega a domicilio
        'is_delivery',
        'delivery_recipient_name',
        'delivery_phone',
        'delivery_address',
        'delivery_references',
        'delivery_lat',
        'delivery_lng',
        'delivery_maps_url',
        'delivery_date',
        'delivery_time',
        'delivery_time_slot',
        'delivery_instructions',
        'delivery_cost',
        'installation_cost',
        'delivery_status',
        // Cupones
        'coupon_id',
        'coupon_code',
        'coupon_discount',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'ieps_total' => 'decimal:2',
        'iva_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'total' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_delivery' => 'boolean',
        'delivery_lat' => 'float',
        'delivery_lng' => 'float',
        'delivery_date' => 'date',
        'delivery_cost' => 'decimal:2',
        'installation_cost' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id')->withDefault(function () {
            return $this->cashier;
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Genera el siguiente folio secuencial anual de cotización: COT-YYYY-NNNN
     */
    public static function generateFolio(?int $year = null): string
    {
        $year   = $year ?: now()->year;
        $prefix = "COT-{$year}-";

        $folios = static::withTrashed()
            ->where('folio', 'like', $prefix . '%')
            ->pluck('folio');

        $maxSeq = 0;
        foreach ($folios as $f) {
            $suffix = substr((string) $f, strlen($prefix));
            if ($suffix !== '' && ctype_digit($suffix)) {
                $num = (int) $suffix;
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }

        $next = $maxSeq + 1;
        do {
            $folio  = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where('folio', $folio)->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $folio;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function discountAuthorization()
    {
        return $this->hasOne(DiscountAuthorization::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getFormattedDeliveryTimeAttribute(): ?string
    {
        if (!empty($this->delivery_time)) {
            try {
                return \Carbon\Carbon::parse($this->delivery_time)->format('h:i A');
            } catch (\Exception $e) {
                return $this->delivery_time;
            }
        }

        if (!empty($this->delivery_time_slot)) {
            return match ($this->delivery_time_slot) {
                'manana' => 'Mañana (9:00 AM - 1:00 PM)',
                'tarde'  => 'Tarde (1:00 PM - 7:00 PM)',
                'abierto'=> 'Horario Abierto',
                default  => ucfirst($this->delivery_time_slot),
            };
        }

        return null;
    }
}

