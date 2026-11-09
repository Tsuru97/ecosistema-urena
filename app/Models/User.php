<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\Cacheable;
use App\Traits\Auditable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Cacheable, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'lastname',
        'username',
        'email',
        'password',
        'phone',
        'address',
        'birthday',
        'rfc',
        'status',
        'phone_verified_at',
        'credit_limit',
        'available_credit',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'birthday' => 'date',
        'credit_limit' => 'decimal:2',
        'available_credit' => 'decimal:2',
    ];

    /**
     * Get the avatar associated with the user.
     */
    public function avatar()
    {
        return $this->morphOne(File::class, 'fileable')->where('type', 'avatar');
    }

    /**
     * Get all of the user's files.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class)
                    ->withPivot('is_default')
                    ->withTimestamps();
    }

    public function defaultStore()
    {
        return $this->belongsToMany(Store::class)
                    ->withPivot('is_default')
                    ->wherePivot('is_default', true)
                    ->withTimestamps();
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class);
    }

    /**
     * Retorna la sucursal por defecto del usuario, o la primera disponible.
     */
    public function getDefaultStoreAttribute()
    {
        return $this->stores()->wherePivot('is_default', true)->first()
            ?? $this->stores()->first();
    }

    /**
     * Turnos de caja asociados al usuario como cajero.
     */
    public function cashShifts()
    {
        return $this->hasMany(CashShift::class, 'user_id');
    }

    /**
     * Turnos de caja cerrados por este usuario (supervisor/gerente).
     */
    public function closedCashShifts()
    {
        return $this->hasMany(CashShift::class, 'closed_by');
    }

    /**
     * Cupones asignados a este usuario como vendedor / promotor.
     */
    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'seller_id');
    }

    /**
     * Comisiones de venta generadas por este vendedor.
     */
    public function saleCommissions()
    {
        return $this->hasMany(SaleCommission::class, 'seller_id');
    }
}
