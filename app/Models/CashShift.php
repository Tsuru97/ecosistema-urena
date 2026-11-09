<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class CashShift extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'store_id',
        'user_id',
        'closed_by',
        'folio',
        'opening_amount',
        'opened_at',
        'closed_at',
        'status',
        'expected_cash',
        'actual_cash',
        'difference_cash',
        'total_sales_cash',
        'total_sales_card',
        'total_sales_transfer',
        'total_sales_other',
        'total_layaways_cash',
        'total_special_orders_cash',
        'total_cash_in',
        'total_cash_out',
        'total_expenses_cash',
        'total_vault_cash',
        'total_sales_amount',
        'sales_count',
        'opening_notes',
        'closing_notes',
    ];

    protected $casts = [
        'opened_at'                  => 'datetime',
        'closed_at'                  => 'datetime',
        'opening_amount'             => 'decimal:2',
        'expected_cash'              => 'decimal:2',
        'actual_cash'                => 'decimal:2',
        'difference_cash'            => 'decimal:2',
        'total_sales_cash'           => 'decimal:2',
        'total_sales_card'           => 'decimal:2',
        'total_sales_transfer'       => 'decimal:2',
        'total_sales_other'          => 'decimal:2',
        'total_layaways_cash'        => 'decimal:2',
        'total_special_orders_cash'  => 'decimal:2',
        'total_cash_in'              => 'decimal:2',
        'total_cash_out'             => 'decimal:2',
        'total_expenses_cash'        => 'decimal:2',
        'total_vault_cash'           => 'decimal:2',
        'total_sales_amount'         => 'decimal:2',
        'sales_count'                => 'integer',
    ];

    // ──────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function movements()
    {
        return $this->hasMany(CashMovement::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function salePayments()
    {
        return $this->hasMany(Payment::class);
    }

    public function specialOrderPayments()
    {
        return $this->hasMany(SpecialOrderPayment::class);
    }

    // ──────────────────────────────────────────
    // Helpers y Estados
    // ──────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === 'abierto';
    }

    public function isClosed(): bool
    {
        return $this->status === 'cerrado';
    }

    public static function generateFolio(): string
    {
        $year  = now()->year;
        $count = static::withTrashed()->whereYear('created_at', $year)->count();
        return sprintf('TURNO-%d-%04d', $year, $count + 1);
    }

    /**
     * Busca el turno activo para una tienda y usuario dados.
     */
    public static function activeShift(int $storeId, ?int $userId = null): ?self
    {
        return static::where('store_id', $storeId)
            ->where('status', 'abierto')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('opened_at')
            ->first();
    }

    /**
     * Calcula los totales en tiempo real para Arqueo (Corte X / Corte Z).
     */
    public function calculateLiveTotals(): array
    {
        // 1. Pagos de Ventas (normales y apartados)
        $payments = $this->salePayments()->with('paymentMethod')->get();
        
        $salesCash     = 0.0;
        $salesCard     = 0.0;
        $salesTransfer = 0.0;
        $salesOther    = 0.0;
        $terminalComms = 0.0;

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;
            $type   = strtolower($payment->paymentMethod->type ?? '');
            $name   = strtolower($payment->paymentMethod->name ?? '');

            if ($type === 'efectivo' || str_contains($name, 'efectivo')) {
                $salesCash += $amount;
            } elseif ($type === 'tarjeta' || str_contains($name, 'tarjeta') || str_contains($name, 'debito') || str_contains($name, 'credito')) {
                $salesCard += $amount;
            } elseif ($type === 'transferencia' || str_contains($name, 'transferencia')) {
                $salesTransfer += $amount;
            } else {
                $salesOther += $amount;
            }

            if (($payment->terminal_commission ?? 0) > 0) {
                $terminalComms += (float) $payment->terminal_commission;
            }
        }

        // 2. Anticipos de Pedidos Especiales
        $soPayments = $this->specialOrderPayments()->with('paymentMethod')->get();
        $soCash     = 0.0;
        $soCard     = 0.0;
        $soTransfer = 0.0;

        foreach ($soPayments as $soPayment) {
            $amount = (float) $soPayment->amount;
            $type   = strtolower($soPayment->paymentMethod->type ?? '');
            $name   = strtolower($soPayment->paymentMethod->name ?? '');

            if ($type === 'efectivo' || str_contains($name, 'efectivo')) {
                $soCash += $amount;
            } elseif ($type === 'tarjeta' || str_contains($name, 'tarjeta')) {
                $soCard += $amount;
            } else {
                $soTransfer += $amount;
            }
        }

        // 3. Movimientos manuales de efectivo (Entradas y Salidas/Sangrías/Gastos)
        $cashIn       = (float) $this->movements()->where('type', 'entrada')->sum('amount');
        $cashOut      = (float) $this->movements()->where('type', 'salida')->sum('amount');
        $expensesCash = (float) $this->movements()->where('type', 'salida')->where('category', 'gasto_operativo')->sum('amount');
        $vaultCash    = (float) $this->movements()->where('type', 'salida')->where(function ($q) {
            $q->where('category', 'traslado_boveda')->orWhereNull('category');
        })->sum('amount');

        // 4. Totales generales
        $totalSalesCashCombined = $salesCash + $soCash;
        $totalCardCombined      = $salesCard + $soCard;
        $totalTransferCombined  = $salesTransfer + $soTransfer;
        $totalCollected         = $totalSalesCashCombined + $totalCardCombined + $totalTransferCombined + $salesOther;

        $opening = (float) $this->opening_amount;
        $expectedCashInDrawer = $opening + $totalSalesCashCombined + $cashIn - $cashOut;

        $salesCount = $this->sales()->count();

        return [
            'opening_amount'            => $opening,
            'total_sales_cash'          => $salesCash,
            'total_special_orders_cash' => $soCash,
            'total_cash_sales_all'      => $totalSalesCashCombined,
            'total_sales_card'          => $totalCardCombined,
            'total_sales_transfer'      => $totalTransferCombined,
            'total_sales_other'         => $salesOther,
            'total_cash_in'             => $cashIn,
            'total_cash_out'            => $cashOut,
            'total_expenses_cash'       => $expensesCash,
            'total_vault_cash'          => $vaultCash,
            'terminal_commissions'      => $terminalComms,
            'total_sales_amount'        => $totalCollected,
            'expected_cash'             => $expectedCashInDrawer,
            'sales_count'               => $salesCount,
        ];
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'abierto');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'cerrado');
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }
}
