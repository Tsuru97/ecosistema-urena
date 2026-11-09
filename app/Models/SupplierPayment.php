<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class SupplierPayment extends Model
{
    use Auditable;

    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'store_id',
        'user_id',
        'amount',
        'date',
        'payment_method',
        'reference',
        'notes',
        'expense_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date'   => 'date',
    ];

    // ──────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
