<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'commission_percentage',
        'is_system',
        'is_active',
        'accepts_financing',
        'financing_rates',
        'transfer_msi_cost_to_client',
        'apply_iva_to_rates',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'accepts_financing' => 'boolean',
        'financing_rates' => 'array',
        'transfer_msi_cost_to_client' => 'boolean',
        'apply_iva_to_rates' => 'boolean',
    ];

    public function terminals()
    {
        return $this->belongsToMany(PaymentTerminal::class, 'payment_terminal_payment_method');
    }
}
