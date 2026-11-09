<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class SpecialOrder extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'folio',
        'store_id',
        'customer_id',
        'user_id',
        'quote_id',
        'status',
        'notes',
        'expected_delivery_date',
        'total',
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
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'total'                  => 'decimal:2',
        'is_delivery'            => 'boolean',
        'delivery_lat'           => 'float',
        'delivery_lng'           => 'float',
        'delivery_date'          => 'date',
        'delivery_cost'          => 'decimal:2',
        'installation_cost'      => 'decimal:2',
        'delivered_at'           => 'datetime',
    ];

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
        $date = $this->delivery_date ?: $this->expected_delivery_date;
        if (!$date) {
            return null;
        }
        $dateStr = $date->format('Y-m-d');
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

    // ──────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deliveredByUser()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function items()
    {
        return $this->hasMany(SpecialOrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SpecialOrderPayment::class);
    }

    // ──────────────────────────────────────────
    // Helpers financieros
    // ──────────────────────────────────────────

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->total - $this->totalPaid());
    }

    public function depositPercent(): float
    {
        if ($this->total <= 0) return 0;
        return round(($this->totalPaid() / $this->total) * 100, 1);
    }

    // ──────────────────────────────────────────
    // Helpers de estado
    // ──────────────────────────────────────────

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isOrdered(): bool   { return $this->status === 'ordered'; }
    public function isPartial(): bool   { return $this->status === 'partial'; }
    public function isReady(): bool     { return $this->status === 'ready'; }
    public function isDelivered(): bool { return $this->status === 'delivered'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    public function statusLabel(): string
    {
        return match($this->status) {
            'pending'   => 'Pendiente',
            'ordered'   => 'En Pedido',
            'partial'   => 'Recibido Parcial',
            'ready'     => 'Listo para Entrega',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            default     => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'   => 'bg-secondary',
            'ordered'   => 'bg-info',
            'partial'   => 'bg-warning',
            'ready'     => 'bg-success',
            'delivered' => 'bg-primary',
            'cancelled' => 'bg-danger',
            default     => 'bg-secondary',
        };
    }

    // ──────────────────────────────────────────
    // Helpers logísticos
    // ──────────────────────────────────────────

    public function allItemsReceived(): bool
    {
        return $this->items->every(fn($i) => $i->quantity_received >= $i->quantity);
    }

    // ──────────────────────────────────────────
    // Generación de folio: PED-YYYY-NNNN
    // ──────────────────────────────────────────

    /**
     * Genera el siguiente folio secuencial anual de pedido especial: PED-YYYY-NNNN
     */
    public static function generateFolio(?int $year = null): string
    {
        $year   = $year ?: now()->year;
        $prefix = "PED-{$year}-";

        $folios = static::withTrashed()
            ->where('folio', 'like', $prefix . '%')
            ->pluck('folio');

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
            $exists = static::withTrashed()->where('folio', $folio)->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $folio;
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['delivered', 'cancelled']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
