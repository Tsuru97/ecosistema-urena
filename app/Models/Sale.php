<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Sale extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'quote_id',
        'store_id',
        'cash_shift_id',
        'user_id',
        'seller_id',
        'customer_id',
        'ticket_folio',
        'payment_method_id',
        'msi_applied',
        'subtotal',
        'ieps_total',
        'iva_total',
        'tax_total',
        'discount_total',
        'total',
        'amount_paid',
        'balance',
        'status',
        // Entrega a domicilio
        'is_delivery',
        'delivery_recipient_name',
        'delivery_phone',
        'delivery_address',
        'delivery_references',
        'delivery_lat',
        'delivery_lng',
        'delivery_maps_url',
        'delivery_date',
        'delivery_time',
        'delivery_time_slot',
        'delivery_instructions',
        'delivery_cost',
        'installation_cost',
        'delivery_status',
        'delivery_evidence_path',
        'delivered_at',
        'delivered_by',
        // Cupones
        'coupon_id',
        'coupon_code',
        'coupon_discount',
    ];

    protected $casts = [
        'msi_applied' => 'integer',
        'subtotal' => 'decimal:2',
        'ieps_total' => 'decimal:2',
        'iva_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_delivery' => 'boolean',
        'delivery_lat' => 'float',
        'delivery_lng' => 'float',
        'delivery_date' => 'date',
        'delivery_cost' => 'decimal:2',
        'installation_cost' => 'decimal:2',
        'delivered_at' => 'datetime',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id')->withDefault(function () {
            return $this->cashier;
        });
    }

    public function deliveredByUser()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }

    /**
     * Verifica si la venta es un apartado
     */
     public function isLayaway(): bool
     {
         return $this->status === 'apartado';
     }

    /**
     * Verifica si la venta cuenta con servicio de entrega a domicilio
     */
    public function hasDelivery(): bool
    {
        return (bool) $this->is_delivery;
    }

    /**
     * Retorna la hora programada formateada o el time_slot histórico
     */
    public function getFormattedDeliveryTimeAttribute(): ?string
    {
        if (!empty($this->delivery_time)) {
            try {
                return \Carbon\Carbon::createFromFormat('H:i:s', strlen($this->delivery_time) === 5 ? $this->delivery_time . ':00' : substr($this->delivery_time, 0, 8))->format('h:i A');
            } catch (\Exception $e) {
                return $this->delivery_time;
            }
        }
        return $this->delivery_time_slot ?: null;
    }

    /**
     * Retorna el objeto Carbon combinando fecha y hora de entrega para FullCalendar
     */
    public function getScheduledDeliveryDatetimeAttribute(): ?\Carbon\Carbon
    {
        if (!$this->delivery_date) {
            return null;
        }
        $dateStr = $this->delivery_date->format('Y-m-d');
        if (!empty($this->delivery_time)) {
            $timeStr = strlen($this->delivery_time) === 5 ? $this->delivery_time . ':00' : substr($this->delivery_time, 0, 8);
            return \Carbon\Carbon::parse("{$dateStr} {$timeStr}");
        }
        return \Carbon\Carbon::parse("{$dateStr} 09:00:00");
    }

    /**
     * URL pública del archivo de evidencia de entrega (Foto o PDF firmado)
     */
    public function getDeliveryEvidenceUrlAttribute(): ?string
    {
        if (empty($this->delivery_evidence_path)) {
            return null;
        }
        return asset('storage/' . ltrim($this->delivery_evidence_path, '/'));
    }

    /**
     * Determina si el archivo de evidencia es un PDF
     */
    public function isEvidencePdf(): bool
    {
        if (empty($this->delivery_evidence_path)) {
            return false;
        }
        return strtolower(pathinfo($this->delivery_evidence_path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function commissions()
    {
        return $this->hasMany(SaleCommission::class);
    }

    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    // ──────────────────────────────────────────
    // Generación y Búsqueda Inteligente de Folio
    // ──────────────────────────────────────────

    /**
     * Genera el siguiente folio secuencial anual de venta: VTA-YYYY-NNNN
     */
    public static function generateFolio(?int $year = null): string
    {
        $year   = $year ?: now()->year;
        $prefix = "VTA-{$year}-";

        $folios = static::withTrashed()
            ->where('ticket_folio', 'like', $prefix . '%')
            ->pluck('ticket_folio');

        $maxSeq = 0;
        foreach ($folios as $f) {
            $suffix = substr((string) $f, strlen($prefix));
            if ($suffix !== '' && ctype_digit($suffix)) {
                $num = (int) $suffix;
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }

        $next = $maxSeq + 1;
        do {
            $folio  = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where('ticket_folio', $folio)->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $folio;
    }

    /**
     * Aplica condiciones de búsqueda inteligente de folio sobre una subconsulta (orWhere / where).
     * Permite encontrar p. ej. VTA-2026-0042 escribiendo "42", "#42", "0042", "VTA 42", "VTA-42" o el folio completo.
     */
    public static function applySmartFolioSearch($q, string $search, string $column = 'ticket_folio'): void
    {
        $clean = trim($search);
        if ($clean === '') {
            return;
        }

        $q->where($column, 'like', "%{$clean}%");

        // Normalizar espacios por guiones (ej. "VTA 2026 0042" -> "VTA-2026-0042")
        $normalized = preg_replace('/[\s_]+/', '-', strtoupper($clean));
        if ($normalized !== strtoupper($clean)) {
            $q->orWhere($column, 'like', "%{$normalized}%");
        }

        // Si escribe solo el consecutivo o prefijo + consecutivo (ej. "42", "#42", "VTA 42", "COT-15", "PED 8")
        if (preg_match('/^(?:#|VTA|COT|PED|TKT|TICK)?[\s\-]*(?:(\d{4})[\s\-]+)?(\d{1,6})$/i', $clean, $matches)) {
            $yearPart = !empty($matches[1]) ? $matches[1] : null;
            $seqNum   = (int) $matches[2];
            $padded   = str_pad((string) $seqNum, 4, '0', STR_PAD_LEFT);

            if ($yearPart) {
                $q->orWhere($column, 'like', "%-{$yearPart}-{$padded}");
            } else {
                $q->orWhere($column, 'like', "%-{$padded}");
            }
        }
    }

    /**
     * Verifica y ejecuta automáticamente (si existen registros con formato antiguo)
     * la estandarización de folios de Ventas (VTA-YYYY-NNNN), Cotizaciones (COT-YYYY-NNNN)
     * y Pedidos Especiales (PED-YYYY-NNNN), actualizando también las notas de movimientos demo.
     */
    public static function ensureFoliosStandardized(): void
    {
        try {
            $hasLegacySale = static::withTrashed()
                ->where(function ($q) {
                    $q->where('ticket_folio', 'not like', 'VTA-%')
                      ->orWhereNull('ticket_folio');
                })
                ->exists();

            $hasLegacyQuote = false;
            if (\Illuminate\Support\Facades\Schema::hasTable('quotes')) {
                $quotes = \Illuminate\Support\Facades\DB::table('quotes')
                    ->whereNotNull('folio')
                    ->where('folio', '!=', '')
                    ->pluck('folio');
                foreach ($quotes as $qf) {
                    if (!preg_match('/^COT-\d{4}-\d{4,}$/', (string) $qf)) {
                        $hasLegacyQuote = true;
                        break;
                    }
                }
            }

            if ($hasLegacySale || $hasLegacyQuote) {
                static::standardizeAllEcosystemFolios();
            }
        } catch (\Throwable $e) {
            // Silencioso en caso de que alguna tabla aún no exista durante migraciones iniciales
        }
    }

    /**
     * Re-etiqueta cronológicamente todas las Ventas (VTA-YYYY-NNNN), Cotizaciones formales (COT-YYYY-NNNN)
     * y Pedidos Especiales (PED-YYYY-NNNN) existentes, sincronizando las referencias en movimientos de inventario,
     * gastos, órdenes de compra y bitácora de auditoría.
     */
    public static function standardizeAllEcosystemFolios(): array
    {
        $db = \Illuminate\Support\Facades\DB::connection();
        $schema = \Illuminate\Support\Facades\Schema::getFacadeRoot();

        $stats = [
            'sales_updated'          => 0,
            'quotes_updated'         => 0,
            'special_orders_updated' => 0,
            'movements_updated'      => 0,
        ];

        $db->transaction(function () use ($db, $schema, &$stats) {
            // 1. Estandarizar VENTAS (VTA-YYYY-NNNN)
            if ($schema->hasTable('sales')) {
                $sales = $db->table('sales')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get(['id', 'ticket_folio', 'created_at']);

                $seqByYear = [];
                $salePlan  = [];

                foreach ($sales as $sale) {
                    $year = $sale->created_at
                        ? \Carbon\Carbon::parse($sale->created_at)->year
                        : now()->year;

                    $seqByYear[$year] = ($seqByYear[$year] ?? 0) + 1;
                    $newFolio = sprintf('VTA-%d-%04d', $year, $seqByYear[$year]);

                    if ($sale->ticket_folio !== $newFolio) {
                        $salePlan[] = [
                            'id'        => $sale->id,
                            'old_folio' => (string) $sale->ticket_folio,
                            'new_folio' => $newFolio,
                        ];
                    }
                }

                // Fase 1A: Folio temporal para evitar colisión de índice único
                foreach ($salePlan as $row) {
                    $db->table('sales')
                        ->where('id', $row['id'])
                        ->update(['ticket_folio' => 'TMP-VTA-' . $row['id']]);
                }

                // Fase 1B: Folio definitivo + actualización de referencias en movimientos demo
                foreach ($salePlan as $row) {
                    $db->table('sales')
                        ->where('id', $row['id'])
                        ->update(['ticket_folio' => $row['new_folio']]);
                    $stats['sales_updated']++;

                    $old = $row['old_folio'];
                    $new = $row['new_folio'];

                    if ($old !== '') {
                        if ($schema->hasTable('inventory_movements')) {
                            $movs = $db->table('inventory_movements')
                                ->where(function ($q) use ($row, $old) {
                                    $q->where(function ($sq) use ($row) {
                                        $sq->where('reference_type', 'App\\Models\\Sale')
                                           ->where('reference_id', $row['id']);
                                    })->orWhere('notes', 'like', "%{$old}%");
                                })
                                ->get(['id', 'notes']);

                            foreach ($movs as $m) {
                                $updatedNote = str_replace($old, $new, (string) $m->notes);
                                if ($updatedNote !== $m->notes) {
                                    $db->table('inventory_movements')
                                        ->where('id', $m->id)
                                        ->update(['notes' => $updatedNote]);
                                    $stats['movements_updated']++;
                                }
                            }
                        }

                        if ($schema->hasTable('expenses')) {
                            $exps = $db->table('expenses')
                                ->where('notes', 'like', "%{$old}%")
                                ->get(['id', 'notes']);

                            foreach ($exps as $exp) {
                                $db->table('expenses')
                                    ->where('id', $exp->id)
                                    ->update(['notes' => str_replace($old, $new, (string) $exp->notes)]);
                            }
                        }

                        if ($schema->hasTable('activity_log')) {
                            $logs = $db->table('activity_log')
                                ->where('description', 'like', "%{$old}%")
                                ->orWhere('properties', 'like', "%{$old}%")
                                ->get(['id', 'description', 'properties']);

                            foreach ($logs as $log) {
                                $db->table('activity_log')
                                    ->where('id', $log->id)
                                    ->update([
                                        'description' => str_replace($old, $new, (string) $log->description),
                                        'properties'  => str_replace($old, $new, (string) $log->properties),
                                    ]);
                            }
                        }
                    }
                }
            }

            // 2. Estandarizar COTIZACIONES FORMALES (COT-YYYY-NNNN)
            if ($schema->hasTable('quotes')) {
                $quotes = $db->table('quotes')
                    ->whereNotNull('folio')
                    ->where('folio', '!=', '')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get(['id', 'folio', 'created_at']);

                $seqByYear = [];
                $quotePlan = [];

                foreach ($quotes as $quote) {
                    $year = $quote->created_at
                        ? \Carbon\Carbon::parse($quote->created_at)->year
                        : now()->year;

                    $seqByYear[$year] = ($seqByYear[$year] ?? 0) + 1;
                    $newFolio = sprintf('COT-%d-%04d', $year, $seqByYear[$year]);

                    if ($quote->folio !== $newFolio) {
                        $quotePlan[] = [
                            'id'        => $quote->id,
                            'old_folio' => (string) $quote->folio,
                            'new_folio' => $newFolio,
                        ];
                    }
                }

                foreach ($quotePlan as $row) {
                    $db->table('quotes')
                        ->where('id', $row['id'])
                        ->update(['folio' => 'TMP-COT-' . $row['id']]);
                }

                foreach ($quotePlan as $row) {
                    $db->table('quotes')
                        ->where('id', $row['id'])
                        ->update(['folio' => $row['new_folio']]);
                    $stats['quotes_updated']++;

                    $old = $row['old_folio'];
                    $new = $row['new_folio'];

                    if ($old !== '' && $schema->hasTable('activity_log')) {
                        $logs = $db->table('activity_log')
                            ->where('description', 'like', "%{$old}%")
                            ->orWhere('properties', 'like', "%{$old}%")
                            ->get(['id', 'description', 'properties']);

                        foreach ($logs as $log) {
                            $db->table('activity_log')
                                ->where('id', $log->id)
                                ->update([
                                    'description' => str_replace($old, $new, (string) $log->description),
                                    'properties'  => str_replace($old, $new, (string) $log->properties),
                                ]);
                        }
                    }
                }
            }

            // 3. Estandarizar PEDIDOS ESPECIALES (PED-YYYY-NNNN)
            if ($schema->hasTable('special_orders')) {
                $orders = $db->table('special_orders')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get(['id', 'folio', 'created_at']);

                $seqByYear = [];
                $orderPlan = [];

                foreach ($orders as $order) {
                    $year = $order->created_at
                        ? \Carbon\Carbon::parse($order->created_at)->year
                        : now()->year;

                    $seqByYear[$year] = ($seqByYear[$year] ?? 0) + 1;
                    $newFolio = sprintf('PED-%d-%04d', $year, $seqByYear[$year]);

                    if ($order->folio !== $newFolio) {
                        $orderPlan[] = [
                            'id'        => $order->id,
                            'old_folio' => (string) $order->folio,
                            'new_folio' => $newFolio,
                        ];
                    }
                }

                foreach ($orderPlan as $row) {
                    $db->table('special_orders')
                        ->where('id', $row['id'])
                        ->update(['folio' => 'TMP-PED-' . $row['id']]);
                }

                foreach ($orderPlan as $row) {
                    $db->table('special_orders')
                        ->where('id', $row['id'])
                        ->update(['folio' => $row['new_folio']]);
                    $stats['special_orders_updated']++;

                    $old = $row['old_folio'];
                    $new = $row['new_folio'];

                    if ($old !== '') {
                        if ($schema->hasTable('purchase_orders')) {
                            $pos = $db->table('purchase_orders')
                                ->where('notes', 'like', "%{$old}%")
                                ->get(['id', 'notes']);
                            foreach ($pos as $po) {
                                $db->table('purchase_orders')
                                    ->where('id', $po->id)
                                    ->update(['notes' => str_replace($old, $new, (string) $po->notes)]);
                            }
                        }

                        if ($schema->hasTable('purchase_order_items')) {
                            $poItems = $db->table('purchase_order_items')
                                ->where('notes', 'like', "%{$old}%")
                                ->get(['id', 'notes']);
                            foreach ($poItems as $poi) {
                                $db->table('purchase_order_items')
                                    ->where('id', $poi->id)
                                    ->update(['notes' => str_replace($old, $new, (string) $poi->notes)]);
                            }
                        }

                        if ($schema->hasTable('inventory_movements')) {
                            $movs = $db->table('inventory_movements')
                                ->where('notes', 'like', "%{$old}%")
                                ->get(['id', 'notes']);
                            foreach ($movs as $m) {
                                $db->table('inventory_movements')
                                    ->where('id', $m->id)
                                    ->update(['notes' => str_replace($old, $new, (string) $m->notes)]);
                                $stats['movements_updated']++;
                            }
                        }

                        if ($schema->hasTable('activity_log')) {
                            $logs = $db->table('activity_log')
                                ->where('description', 'like', "%{$old}%")
                                ->orWhere('properties', 'like', "%{$old}%")
                                ->get(['id', 'description', 'properties']);

                            foreach ($logs as $log) {
                                $db->table('activity_log')
                                    ->where('id', $log->id)
                                    ->update([
                                        'description' => str_replace($old, $new, (string) $log->description),
                                        'properties'  => str_replace($old, $new, (string) $log->properties),
                                    ]);
                            }
                        }
                    }
                }
            }
        });

        return $stats;
    }
}
