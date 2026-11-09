<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\DiscountAuthorization;
use App\Models\Inventory;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class POSService
{
    /**
     * Solicita una autorización OTP para aplicar un descuento manual.
     */
    public function requestDiscountAuthorization(Quote $quote, int $sellerId): DiscountAuthorization
    {
        // Generar OTP de 6 dígitos
        $otp = (string)random_int(100000, 999999);

        $auth = DiscountAuthorization::create([
            'quote_id' => $quote->id,
            'requested_by' => $sellerId,
            'otp_code' => $otp,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addMinutes(15), // expira en 15 minutos
        ]);

        // TODO: Aquí se dispararía el evento para enviar el WhatsApp/Email a los gerentes.
        
        return $auth;
    }

    /**
     * Valida un código OTP y aprueba el descuento.
     */
    public function approveDiscount(Quote $quote, string $otpCode, int $managerId): bool
    {
        $auth = DiscountAuthorization::where('quote_id', $quote->id)
            ->where('otp_code', $otpCode)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$auth) {
            return false;
        }

        $auth->update([
            'status' => 'approved',
            'authorized_by' => $managerId,
        ]);

        return true;
    }

    /**
     * Convierte una cotización en Venta o Apartado.
     */
    public function convertQuoteToSale(Quote $quote, array $paymentData, bool $isLayaway = false): Sale
    {
        DB::beginTransaction();

        try {
            // Buscar turno de caja activo para la sucursal y usuario
            $activeShift = \App\Models\CashShift::activeShift($quote->store_id, auth()->id() ?? $quote->user_id);
            $shiftId = $activeShift?->id;

            // 1. Crear el registro de Venta
            $sale = Sale::create([
                'quote_id' => $quote->id,
                'store_id' => $quote->store_id,
                'cash_shift_id' => $shiftId,
                'user_id' => auth()->id() ?? $quote->user_id, // El cajero actual
                'seller_id' => $quote->seller_id ?? auth()->id(), // El vendedor comisionista
                'customer_id' => $quote->customer_id,
                'ticket_folio' => Sale::generateFolio(),
                'payment_method_id' => $paymentData['method_id'] ?? null,
                'msi_applied' => $paymentData['msi'] ?? 0,
                'subtotal' => $quote->subtotal,
                'ieps_total' => $quote->ieps_total ?? 0,
                'iva_total' => $quote->iva_total ?? 0,
                'tax_total' => $quote->tax_total,
                'discount_total' => $quote->discount_total,
                'total' => $quote->total,
                'amount_paid' => $paymentData['amount_paid'] ?? $quote->total,
                'status' => $isLayaway ? 'apartado' : 'pagada',
                // Copiar datos de entrega si aplican
                'is_delivery' => (bool)$quote->is_delivery,
                'delivery_recipient_name' => $quote->delivery_recipient_name,
                'delivery_phone' => $quote->delivery_phone,
                'delivery_address' => $quote->delivery_address,
                'delivery_references' => $quote->delivery_references,
                'delivery_lat' => $quote->delivery_lat,
                'delivery_lng' => $quote->delivery_lng,
                'delivery_maps_url' => $quote->delivery_maps_url,
                'delivery_date' => $quote->delivery_date,
                'delivery_time' => $quote->delivery_time,
                'delivery_time_slot' => $quote->delivery_time_slot,
                'delivery_instructions' => $quote->delivery_instructions,
                'delivery_cost' => $quote->delivery_cost ?? 0,
                'installation_cost' => $quote->installation_cost ?? 0,
                'delivery_status' => $quote->delivery_status ?? 'pendiente',
                // Cupones
                'coupon_id' => $paymentData['coupon_id'] ?? $quote->coupon_id ?? null,
                'coupon_code' => $paymentData['coupon_code'] ?? $quote->coupon_code ?? null,
                'coupon_discount' => $paymentData['coupon_discount'] ?? $quote->coupon_discount ?? 0,
            ]);

            // Calcular el balance
            $sale->balance = max(0, $sale->total - $sale->amount_paid);
            if ($sale->balance <= 0) {
                $sale->status = 'pagada'; // Si se liquidó todo, no es apartado.
            }
            $sale->save();

            // Gestión de línea de crédito del cliente
            if ($sale->customer) {
                if ($sale->status === 'pagada') {
                    $rewardPct = (float)\App\Models\Setting::get('customer_credit_reward_percentage', 0, $sale->store_id);
                    if ($rewardPct > 0) {
                        $sale->customer->rewardCreditFromSale($sale, $rewardPct);
                    }
                } elseif ($sale->status === 'apartado' && $sale->balance > 0) {
                    $creditCheckEnabled = filter_var(\App\Models\Setting::get('customer_credit_check_enabled', true, $sale->store_id), FILTER_VALIDATE_BOOLEAN);
                    if ($creditCheckEnabled) {
                        $sale->customer->consumeCredit((float)$sale->balance);
                    }
                }
            }

            // 2. Registrar los pagos
            $payments = $paymentData['payments'] ?? [];
            if (empty($payments) && $sale->amount_paid > 0) {
                // Fallback para compatibilidad con llamadas anteriores
                $sale->payments()->create([
                    'cash_shift_id' => $shiftId,
                    'user_id' => $sale->user_id,
                    'amount' => $sale->amount_paid,
                    'payment_method_id' => $sale->payment_method_id,
                    'reference' => $paymentData['reference'] ?? null,
                ]);
            } else {
                foreach ($payments as $p) {
                    $payment = $sale->payments()->create([
                        'cash_shift_id' => $shiftId,
                        'user_id' => $sale->user_id,
                        'amount' => $p['amount'],
                        'payment_method_id' => $p['payment_method_id'],
                        'payment_terminal_id' => $p['payment_terminal_id'] ?? null,
                        'terminal_commission' => $p['terminal_commission'] ?? 0.00,
                        'commission_charged_to' => $p['commission_charged_to'] ?? null,
                        'msi_applied' => $p['msi_applied'] ?? 0,
                        'reference' => $p['reference'] ?? null,
                        'notes' => $p['notes'] ?? null,
                    ]);

                    // 1. Generar Gasto si la comisión de terminal la absorbe la empresa
                    if (($p['commission_charged_to'] ?? null) === 'company' && ($p['terminal_commission'] ?? 0) > 0) {
                        $terminalName = \App\Models\PaymentTerminal::find($p['payment_terminal_id'])->name ?? 'N/A';
                        \App\Models\Expense::create([
                            'store_id' => $sale->store_id,
                            'user_id' => $sale->user_id,
                            'concept' => "Comisión de terminal absorbida (Terminal: {$terminalName})",
                            'amount' => $p['terminal_commission'],
                            'date' => now(),
                            'reference_type' => 'payment',
                            'reference_id' => $payment->id,
                            'notes' => "Generado automáticamente por cobro de terminal en ticket {$sale->ticket_folio}"
                        ]);
                    }

                    // 2. Generar Gasto si la sobretasa por Campaña MSI la absorbe la empresa
                    if (($p['msi_sobretasa_absorbed'] ?? 0) > 0) {
                        $pmName = \App\Models\PaymentMethod::find($p['payment_method_id'])->name ?? 'N/A';
                        \App\Models\Expense::create([
                            'store_id' => $sale->store_id,
                            'user_id' => $sale->user_id,
                            'concept' => "Sobretasa bancaria absorbida por Campaña MSI ({$p['msi_applied']} meses - {$pmName})",
                            'amount' => $p['msi_sobretasa_absorbed'],
                            'date' => now(),
                            'reference_type' => 'payment',
                            'reference_id' => $payment->id,
                            'notes' => "Campaña Meses Sin Intereses activa. El costo financiero de la sobretasa fue absorbido por la empresa en ticket {$sale->ticket_folio}"
                        ]);
                    }
                }
            }

            // 3. Clonar los items de la cotización y descontar inventario
            foreach ($quote->items as $qItem) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $qItem->product_id,
                    'product_variant_id' => $qItem->product_variant_id,
                    'quantity' => $qItem->quantity,
                    'base_cost' => $qItem->base_cost,
                    'unit_price' => $qItem->unit_price_final,
                    'tax_amount' => $qItem->tax_amount,
                    'ieps_amount' => $qItem->ieps_amount ?? 0,
                    'iva_amount' => $qItem->iva_amount ?? 0,
                    'subtotal' => $qItem->subtotal,
                    'total' => $qItem->total,
                    'notes' => $qItem->notes,
                ]);

                // 4. Mover / Reservar Inventario (únicamente para productos físicos y no comodines ni servicios)
                if ($qItem->product && $qItem->product->isPhysical() && !$qItem->product->is_wildcard && !$qItem->product->isService()) {
                    if ($sale->isLayaway()) {
                        // Si es APARTADO: Reservamos el producto en la bodega de la sucursal (bloqueado para otros clientes)
                        Inventory::applyMovement(
                            $sale->store_id,
                            $qItem->product_id,
                            'reserva_apartado',
                            $qItem->quantity,
                            $sale->user_id,
                            'Reserva por Apartado folio: ' . $sale->ticket_folio,
                            'App\Models\Sale',
                            $sale->id,
                            $qItem->product_variant_id
                        );
                    } else {
                        // Si es VENTA PAGADA: Descuenta del inventario disponible
                        Inventory::applyMovement(
                            $sale->store_id,
                            $qItem->product_id,
                            'venta',
                            $qItem->quantity,
                            $sale->user_id,
                            'Venta folio: ' . $sale->ticket_folio,
                            'App\Models\Sale',
                            $sale->id,
                            $qItem->product_variant_id
                        );
                    }
                }
            }

            // Marcar cotización como convertida a venta
            $quote->update(['status' => 'convertida']);

            // 5. Registrar redención de cupón si aplica
            if ($sale->coupon_id && \Illuminate\Support\Facades\Schema::hasTable('coupon_redemptions')) {
                $coupon = \App\Models\Coupon::find($sale->coupon_id);
                if ($coupon) {
                    $coupon->increment('times_used');
                    \App\Models\CouponRedemption::create([
                        'coupon_id'       => $coupon->id,
                        'sale_id'         => $sale->id,
                        'customer_id'     => $sale->customer_id,
                        'user_id'         => $sale->user_id,
                        'seller_id'       => $sale->seller_id,
                        'discount_amount' => (float)$sale->coupon_discount,
                    ]);
                }
            }

            // 6. Distribuir Comisiones según Matriz de Reparto
            if (\Illuminate\Support\Facades\Schema::hasTable('sale_commissions')) {
                app(\App\Services\CommissionService::class)->distributeCommissions($sale);
            }

            DB::commit();
            return $sale;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
