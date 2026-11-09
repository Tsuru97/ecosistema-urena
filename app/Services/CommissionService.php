<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Coupon;
use App\Models\SaleCommission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    /**
     * Calcula la tasa base de comisión para una venta según la configuración del sistema.
     */
    public function calculateBaseCommissionRate(Sale $sale): float
    {
        $storeId = $sale->store_id;

        // 1. Venta a Meses Sin Intereses (MSI)
        $hasMsi = (int)$sale->msi_applied > 0;
        if (!$hasMsi && $sale->payments) {
            foreach ($sale->payments as $p) {
                if ((int)$p->msi_applied > 0) {
                    $hasMsi = true;
                    break;
                }
            }
        }

        if ($hasMsi) {
            return (float)Setting::get('comm_msi', 0.50, $storeId);
        }

        // 2. Venta con Descuento (Regateo o Cupón)
        $couponDiscount = (float)($sale->coupon_discount ?? 0);
        $manualDiscount = (float)($sale->discount_total ?? 0);
        $totalDiscount  = $couponDiscount + $manualDiscount;

        if ($totalDiscount > 0) {
            // Subtotal + Impuestos representa el importe de artículos antes del cupón; sumamos discount_total para obtener el precio de lista original
            $itemsTotalWithTax = (float)$sale->subtotal + (float)($sale->tax_total ?? 0);
            $baseTotal = ($itemsTotalWithTax > 0 ? $itemsTotalWithTax : (float)$sale->total + $couponDiscount) + $manualDiscount;
            $discountPct = $baseTotal > 0 ? round(($totalDiscount / $baseTotal) * 100, 2) : 0;

            if ($discountPct > 3.0) {
                return (float)Setting::get('comm_discount_high', 0.50, $storeId);
            } else {
                return (float)Setting::get('comm_discount_low', 0.70, $storeId);
            }
        }

        // 3. Venta estándar Contado / Transferencia sin descuento
        return (float)Setting::get('comm_cash', 1.00, $storeId);
    }

    /**
     * Distribuye las comisiones de una venta según la Matriz de Reparto de Cupones.
     */
    public function distributeCommissions(Sale $sale): array
    {
        if (!Schema::hasTable('sale_commissions')) {
            return [];
        }

        try {
            // Eliminar comisiones previas de la venta si se estuviera recalculando
            SaleCommission::where('sale_id', $sale->id)->delete();

            $saleAmount = (float)$sale->total;
            if ($saleAmount <= 0) {
                return [];
            }

            $baseRate  = $this->calculateBaseCommissionRate($sale);
            $totalPool = round($saleAmount * ($baseRate / 100), 2);

            // Determinar estatus inicial de la comisión
            $status = 'earned';
            if ($sale->status === 'apartado') {
                $trigger = Setting::get('comm_layaway_payout_trigger', 'on_liquidation', $sale->store_id);
                $status = ($trigger === 'on_creation') ? 'earned' : 'pending';
            } elseif ($sale->status === 'cancelada') {
                $status = 'cancelled';
            }

            // Identificar vendedor en caja y dueño del cupón
            $closingSellerId = $sale->seller_id ?: $sale->user_id;
            $coupon = null;
            if ($sale->coupon_id && Schema::hasTable('coupons')) {
                $coupon = Coupon::find($sale->coupon_id);
            }

            $couponOwnerId = $coupon?->seller_id;
            $createdCommissions = [];

            // REGLA MATRIZ: Cupón ligado a Vendedor A pero cobrado/cerrado por Vendedor B
            $isSplit = ($couponOwnerId && $closingSellerId && (int)$couponOwnerId !== (int)$closingSellerId);

            if ($isSplit) {
                $ownerPct   = (float)Setting::get('comm_coupon_owner_split_pct', 50.00, $sale->store_id);
                $closingPct = (float)Setting::get('comm_closing_seller_split_pct', 50.00, $sale->store_id);

                // Normalizar porcentajes a base 100
                $sum = $ownerPct + $closingPct;
                if ($sum > 0) {
                    $ownerPct   = round(($ownerPct / $sum) * 100, 2);
                    $closingPct = round(100.00 - $ownerPct, 2);
                } else {
                    $ownerPct = 50.00;
                    $closingPct = 50.00;
                }

                $ownerAmount   = round($totalPool * ($ownerPct / 100), 2);
                $closingAmount = round($totalPool - $ownerAmount, 2); // Exactitud al centavo

                $closingSeller = User::find($closingSellerId);
                $ownerSeller   = User::find($couponOwnerId);

                // 1. Comisión Vendedor Prospector (Dueño del Cupón)
                $createdCommissions[] = SaleCommission::create([
                    'sale_id'              => $sale->id,
                    'seller_id'            => $couponOwnerId,
                    'coupon_id'            => $coupon->id,
                    'role'                 => 'coupon_owner',
                    'sale_amount'          => $saleAmount,
                    'base_commission_rate' => $baseRate,
                    'split_percentage'     => $ownerPct,
                    'final_rate'           => round($baseRate * ($ownerPct / 100), 3),
                    'commission_amount'    => $ownerAmount,
                    'status'               => $status,
                    'notes'                => "Comisión del {$ownerPct}% por prospección vía cupón {$coupon->code} (Venta cerrada por " . ($closingSeller?->name ?? 'otro asesor') . ")",
                ]);

                // 2. Comisión Vendedor en Mostrador / Cajero (Cierre)
                $createdCommissions[] = SaleCommission::create([
                    'sale_id'              => $sale->id,
                    'seller_id'            => $closingSellerId,
                    'coupon_id'            => $coupon->id,
                    'role'                 => 'closing_seller',
                    'sale_amount'          => $saleAmount,
                    'base_commission_rate' => $baseRate,
                    'split_percentage'     => $closingPct,
                    'final_rate'           => round($baseRate * ($closingPct / 100), 3),
                    'commission_amount'    => $closingAmount,
                    'status'               => $status,
                    'notes'                => "Comisión del {$closingPct}% por atención y cierre en caja de cupón {$coupon->code} (Prospectado por " . ($ownerSeller?->name ?? 'otro asesor') . ")",
                ]);

            } else {
                // Venta directa o cupón propio: 100% de la comisión al vendedor asignado
                $targetSellerId = $closingSellerId ?: $couponOwnerId;
                if (!$targetSellerId) {
                    return [];
                }

                $role = ($coupon && $couponOwnerId && (int)$couponOwnerId === (int)$closingSellerId)
                    ? 'coupon_owner'
                    : 'direct_sale';

                $notes = ($role === 'coupon_owner')
                    ? "Comisión 100% (Venta y cupón propio {$coupon->code})"
                    : "Comisión 100% por venta directa";

                $createdCommissions[] = SaleCommission::create([
                    'sale_id'              => $sale->id,
                    'seller_id'            => $targetSellerId,
                    'coupon_id'            => $coupon?->id,
                    'role'                 => $role,
                    'sale_amount'          => $saleAmount,
                    'base_commission_rate' => $baseRate,
                    'split_percentage'     => 100.00,
                    'final_rate'           => $baseRate,
                    'commission_amount'    => $totalPool,
                    'status'               => $status,
                    'notes'                => $notes,
                ]);
            }

            return $createdCommissions;

        } catch (\Exception $e) {
            Log::error("Error al calcular o distribuir comisiones en venta {$sale->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);
            return [];
        }
    }

    /**
     * Se invoca cuando un apartado queda liquidado al 100% de su saldo.
     */
    public function onSaleLiquidated(Sale $sale): void
    {
        if (!Schema::hasTable('sale_commissions')) {
            return;
        }

        $pending = SaleCommission::where('sale_id', $sale->id)
            ->where('status', 'pending')
            ->get();

        foreach ($pending as $comm) {
            $comm->update(['status' => 'earned']);
        }

        if ($pending->count() > 0) {
            activity('commissions')
                ->performedOn($sale)
                ->causedBy(auth()->user())
                ->log("Comisiones de venta liberadas a GANADA tras liquidar apartado ticket {$sale->ticket_folio}.");
        }
    }

    /**
     * Se invoca cuando una venta o apartado es cancelado.
     */
    public function onSaleCancelled(Sale $sale): void
    {
        if (!Schema::hasTable('sale_commissions')) {
            return;
        }

        SaleCommission::where('sale_id', $sale->id)
            ->where('status', '!=', 'paid')
            ->update(['status' => 'cancelled']);

        activity('commissions')
            ->performedOn($sale)
            ->causedBy(auth()->user())
            ->log("Comisiones canceladas por anulación de ticket {$sale->ticket_folio}.");
    }

    /**
     * Marca una comisión como liquidada / pagada al vendedor.
     */
    public function markAsPaid(SaleCommission $commission, int $paidById, ?string $notes = null): bool
    {
        if ($commission->status !== 'earned') {
            return false;
        }

        $commission->update([
            'status'  => 'paid',
            'paid_at' => now(),
            'paid_by' => $paidById,
            'notes'   => $notes ? ($commission->notes . ' | ' . $notes) : $commission->notes,
        ]);

        activity('commissions')
            ->performedOn($commission)
            ->causedBy(auth()->user())
            ->log("Comisión #{$commission->id} por $" . number_format($commission->commission_amount, 2) . " liquidada a {$commission->seller?->name}.");

        return true;
    }
}