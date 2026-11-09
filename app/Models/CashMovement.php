<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class CashMovement extends Model
{
    use Auditable;

    protected $fillable = [
        'cash_shift_id',
        'store_id',
        'user_id',
        'type',
        'category',
        'expense_id',
        'amount',
        'concept',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function isEntry(): bool
    {
        return $this->type === 'entrada';
    }

    public function isWithdrawal(): bool
    {
        return $this->type === 'salida';
    }

    public function isExpense(): bool
    {
        return $this->category === 'gasto_operativo';
    }

    public function isVaultTransfer(): bool
    {
        return $this->category === 'traslado_boveda';
    }
}
