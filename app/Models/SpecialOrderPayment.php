<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialOrderPayment extends Model
{
    protected $fillable = [
        'special_order_id',
        'cash_shift_id',
        'payment_method_id',
        'payment_terminal_id',
        'user_id',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ──────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────

    public function specialOrder()
    {
        return $this->belongsTo(SpecialOrder::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentTerminal()
    {
        return $this->belongsTo(PaymentTerminal::class, 'payment_terminal_id');
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }
}
