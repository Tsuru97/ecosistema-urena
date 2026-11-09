<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTerminal extends Model
{
    protected $fillable = [
        'name',
        'commission_fixed',
        'commission_percentage',
        'charge_client',
        'is_active',
    ];

    protected $casts = [
        'commission_fixed' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'charge_client' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'payment_terminal_payment_method');
    }
}
