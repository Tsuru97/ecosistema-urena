<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Cacheable;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, Auditable, Cacheable;

    protected $fillable = [
        'user_id',
        'name',
        'rfc',
        'email',
        'phone',
        'address',
        'credit_days',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'credit_days' => 'integer',
    ];

    // ── Relaciones ─────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
