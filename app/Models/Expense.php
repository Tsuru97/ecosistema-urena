<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Expense extends Model
{
    use Auditable;

    protected $fillable = [
        'store_id',
        'user_id',
        'concept',
        'amount',
        'date',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashMovement()
    {
        return $this->belongsTo(CashMovement::class, 'reference_id');
    }
}
