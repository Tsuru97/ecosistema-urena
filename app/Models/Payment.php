<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'sale_id',
        'cash_shift_id',
        'user_id',
        'amount',
        'payment_method_id',
        'payment_terminal_id',
        'terminal_commission',
        'commission_charged_to',
        'msi_applied',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'terminal_commission' => 'decimal:2',
        'msi_applied' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function terminal()
    {
        return $this->belongsTo(PaymentTerminal::class, 'payment_terminal_id');
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }
}
