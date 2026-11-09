<?php

namespace App\Models;

use App\Traits\Cacheable;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Transfer extends Model
{
    use SoftDeletes, Cacheable, Auditable;

    protected $fillable = [
        'origin_store_id',
        'origin_warehouse_id',
        'destination_store_id',
        'destination_warehouse_id',
        'folio',
        'status',
        'created_by',
        'sent_by',
        'received_by',
        'sent_at',
        'received_at',
        'notes',
        'rejection_reason',
        'receiver_notes',
    ];

    protected $casts = [
        'sent_at'     => 'datetime',
        'received_at' => 'datetime',
    ];

    // ── Relaciones ─────────────────────────────────────────────────

    public function originStore()
    {
        return $this->belongsTo(Store::class, 'origin_store_id');
    }

    public function originWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationStore()
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function proofPhoto()
    {
        return $this->morphOne(File::class, 'fileable')->where('type', 'transfer_proof');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopePendiente(Builder $q): Builder
    {
        return $q->where('status', 'pendiente');
    }

    public function scopeEnTransito(Builder $q): Builder
    {
        return $q->where('status', 'en_transito');
    }

    /** Traspasos donde esta sucursal es origen O destino */
    public function scopeForStore(Builder $q, int $storeId): Builder
    {
        return $q->where(function ($sub) use ($storeId) {
            $sub->where('origin_store_id', $storeId)
                ->orWhere('destination_store_id', $storeId);
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isPendiente(): bool  { return $this->status === 'pendiente'; }
    public function isEnTransito(): bool  { return $this->status === 'en_transito'; }
    public function isRecibido(): bool   { return $this->status === 'recibido'; }
    public function isRechazado(): bool  { return $this->status === 'rechazado'; }
    public function isCancelado(): bool  { return $this->status === 'cancelado'; }
    public function isDevolucion(): bool { return $this->status === 'devolucion'; }
    public function isFinished(): bool   { return in_array($this->status, ['recibido', 'rechazado', 'cancelado', 'devolucion']); }

    /** Etiqueta legible y color Bootstrap del estado */
    public function statusBadge(): array
    {
        return match ($this->status) {
            'pendiente'   => ['label' => 'Pendiente',    'color' => 'warning'],
            'en_transito' => ['label' => 'En Tránsito',  'color' => 'info'],
            'recibido'    => ['label' => 'Recibido',     'color' => 'success'],
            'rechazado'   => ['label' => 'Rechazado',    'color' => 'danger'],
            'cancelado'   => ['label' => 'Cancelado',    'color' => 'secondary'],
            'devolucion'  => ['label' => 'Devolución',   'color' => 'danger'],
            default       => ['label' => $this->status,  'color' => 'light'],
        };
    }

    // ── Folio auto-generado ──────────────────────────────────────────

    /** Genera el siguiente folio: TRP-YYYY-NNNN */
    public static function generateFolio(): string
    {
        $year = now()->year;
        $prefix = "TRP-{$year}-";

        $last = static::withTrashed()
            ->where('folio', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('folio');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // ── Lógica de estados ────────────────────────────────────────────

    /**
     * Marca el traspaso como En Tránsito y descuenta stock en la sucursal origen.
     * Se llama cuando la sucursal origen confirma el envío.
     */
    public function ship(int $userId): void
    {
        if (!$this->isPendiente()) {
            throw new \LogicException("Solo se puede enviar un traspaso en estado Pendiente.");
        }

        DB::transaction(function () use ($userId) {
            foreach ($this->items()->with(['product', 'variant'])->get() as $item) {
                $qtySent = $item->quantity_sent ?? $item->quantity_requested;

                // Descontar stock de la bodega origen
                Inventory::applyMovement(
                    storeId:          $this->origin_store_id,
                    productId:        $item->product_id,
                    type:             'traspaso_salida',
                    quantity:         $qtySent,
                    userId:           $userId,
                    notes:            "Traspaso {$this->folio} → {$this->destinationStore->name}",
                    referenceType:    Transfer::class,
                    referenceId:      $this->id,
                    productVariantId: $item->product_variant_id,
                    warehouseId:      $this->origin_warehouse_id,
                );

                // Guardar qty_sent
                $item->update(['quantity_sent' => $qtySent]);
            }

            $this->update([
                'status'  => 'en_transito',
                'sent_by' => $userId,
                'sent_at' => now(),
            ]);
        });
    }

    /**
     * Marca el traspaso como Recibido y suma stock en la sucursal/bodega destino.
     * $quantities = [transfer_item_id => qty_received, ...]
     */
    public function receive(int $userId, array $quantities = [], ?string $receiverNotes = null): void
    {
        if (!$this->isEnTransito()) {
            throw new \LogicException("Solo se puede recibir un traspaso En Tránsito.");
        }

        DB::transaction(function () use ($userId, $quantities, $receiverNotes) {
            $hasDiscrepancy = false;
            foreach ($this->items()->with(['product', 'variant'])->get() as $item) {
                $qtySent = $item->quantity_sent ?? $item->quantity_requested;
                $qtyReceived = $quantities[$item->id] ?? $qtySent;

                // Sumar stock en la sucursal/bodega destino
                Inventory::applyMovement(
                    storeId:          $this->destination_store_id,
                    productId:        $item->product_id,
                    type:             'traspaso_entrada',
                    quantity:         $qtyReceived,
                    userId:           $userId,
                    notes:            "Traspaso {$this->folio} ← {$this->originStore->name}",
                    referenceType:    Transfer::class,
                    referenceId:      $this->id,
                    productVariantId: $item->product_variant_id,
                    warehouseId:      $this->destination_warehouse_id,
                );

                // Si se recibieron menos productos de los enviados, generar movimiento de regreso en origen
                if ($qtyReceived < $qtySent) {
                    $diff = $qtySent - $qtyReceived;
                    $hasDiscrepancy = true;

                    Inventory::applyMovement(
                        storeId:          $this->origin_store_id,
                        productId:        $item->product_id,
                        type:             'traspaso_entrada',
                        quantity:         $diff,
                        userId:           $userId,
                        notes:            "Devolución por faltante en traspaso {$this->folio} a {$this->destinationStore->name}",
                        referenceType:    Transfer::class,
                        referenceId:      $this->id,
                        productVariantId: $item->product_variant_id,
                    );
                }

                $item->update(['quantity_received' => $qtyReceived]);
            }

            $finalStatus = $hasDiscrepancy ? 'devolucion' : 'recibido';

            $this->update([
                'status'         => $finalStatus,
                'received_by'    => $userId,
                'received_at'    => now(),
                'receiver_notes' => $receiverNotes,
            ]);
        });
    }

    /**
     * Rechaza el traspaso (En Tránsito) y devuelve el stock a la sucursal origen.
     */
    public function reject(int $userId, string $reason): void
    {
        if (!$this->isEnTransito()) {
            throw new \LogicException("Solo se puede rechazar un traspaso En Tránsito.");
        }

        DB::transaction(function () use ($userId, $reason) {
            foreach ($this->items()->with(['product', 'variant'])->get() as $item) {
                $qtySent = $item->quantity_sent ?? $item->quantity_requested;

                // Revertir stock en la sucursal origen
                Inventory::applyMovement(
                    storeId:          $this->origin_store_id,
                    productId:        $item->product_id,
                    type:             'devolucion_compra', // Usamos este tipo para devolver a origen
                    quantity:         $qtySent,
                    userId:           $userId,
                    notes:            "Rechazo traspaso {$this->folio}: {$reason}",
                    referenceType:    Transfer::class,
                    referenceId:      $this->id,
                    productVariantId: $item->product_variant_id,
                );
            }

            $this->update([
                'status'           => 'rechazado',
                'received_by'      => $userId,
                'received_at'      => now(),
                'rejection_reason' => $reason,
            ]);
        });
    }

    /**
     * Cancela el traspaso (solo si está Pendiente).
     */
    public function cancel(int $userId): void
    {
        if (!$this->isPendiente()) {
            throw new \LogicException("Solo se puede cancelar un traspaso Pendiente.");
        }
        $this->update(['status' => 'cancelado']);
    }
}
