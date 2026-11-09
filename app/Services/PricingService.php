<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Product;

class PricingService
{
    /**
     * Calcula el precio final de un producto considerando:
     * - El costo base
     * - El margen de ganancia de la sucursal
     * - El interés por MSI (Meses sin intereses)
     * - Impuestos en cascada SAT: IEPS por producto (porcentual/cuota/exento) -> IVA Global (Settings) sobre (Base + IEPS).
     */
    public function calculatePrice(Product $product, int $storeId, string $marginType = 'venta', int $msiMonths = 0, float $customBaseCost = null, int $quantity = 1): array
    {
        $baseCost = $customBaseCost !== null ? $customBaseCost : (float)($product->base_cost ?? 0);

        // 1. Obtener Margen de ganancia (Multiplicador)
        $marginKey = 'margin_' . strtolower($marginType);
        $defaultMultiplier = match (strtolower($marginType)) {
            'lista' => '2.50',
            'venta' => '2.00',
            'rebajado' => '1.80',
            'outlet' => '1.50',
            default => '1.00',
        };
        $marginMultiplier = (float)Setting::get($marginKey, $defaultMultiplier, $storeId);
        if ($marginMultiplier <= 0) {
            $marginMultiplier = 1.0;
        }

        // Precio antes de MSI (Costo Base * Multiplicador)
        $priceWithMargin = $baseCost * $marginMultiplier;

        // 2. Aplicar interés por MSI (si aplica)
        $msiPercentage = 0;
        if ($msiMonths > 0) {
            $msiKey = "interest_{$msiMonths}msi";
            $msiPercentage = (float)Setting::get($msiKey, 0, $storeId);
        }

        $calculatedPrice = $priceWithMargin * (1 + ($msiPercentage / 100));

        // 3. Aplicar Impuestos SAT (IEPS por producto + IVA global de Settings)
        $taxIvaRatePercent = (float)Setting::get('tax_iva_rate', 16, $storeId);
        $taxIvaRateDecimal = $taxIvaRatePercent / 100;

        $iepsType = $product->ieps_type ?? 'exempt';
        $iepsValue = (float)($product->ieps_value ?? 0.0);

        $taxIncluded = filter_var(Setting::get('tax_included', false, $storeId), FILTER_VALIDATE_BOOLEAN);

        $netBasePrice = 0.0;
        $iepsAmount = 0.0;
        $ivaBaseAmount = 0.0;
        $ivaAmount = 0.0;
        $unitPriceFinal = 0.0;

        if ($taxIncluded) {
            // CÁLCULO INVERSO: $calculatedPrice es el Precio Público Total al Cliente
            $unitPriceFinal = $calculatedPrice;
            $ivaBaseAmount = $unitPriceFinal / (1 + $taxIvaRateDecimal);
            $ivaAmount = $unitPriceFinal - $ivaBaseAmount;

            if ($iepsType === 'percentage' && $iepsValue > 0) {
                $netBasePrice = $ivaBaseAmount / (1 + $iepsValue);
                $iepsAmount = $ivaBaseAmount - $netBasePrice;
            } elseif ($iepsType === 'fixed_quota' && $iepsValue > 0) {
                $iepsAmount = $iepsValue * $quantity;
                $netBasePrice = max(0, $ivaBaseAmount - $iepsAmount);
            } else {
                $netBasePrice = $ivaBaseAmount;
                $iepsAmount = 0.0;
            }
        } else {
            // CÁLCULO DIRECTO: $calculatedPrice es el Precio Base Neto
            $netBasePrice = $calculatedPrice;

            if ($iepsType === 'percentage' && $iepsValue > 0) {
                $iepsAmount = $netBasePrice * $iepsValue;
            } elseif ($iepsType === 'fixed_quota' && $iepsValue > 0) {
                $iepsAmount = $iepsValue * $quantity;
            } else {
                $iepsAmount = 0.0;
            }

            $ivaBaseAmount = $netBasePrice + $iepsAmount;
            $ivaAmount = $ivaBaseAmount * $taxIvaRateDecimal;
            $unitPriceFinal = $ivaBaseAmount + $ivaAmount;
        }

        $totalTaxAmount = $iepsAmount + $ivaAmount;

        return [
            'base_cost' => round($baseCost, 2),
            'margin_percentage' => $marginMultiplier,
            'margin_multiplier' => $marginMultiplier,
            'msi_percentage' => $msiPercentage,
            'net_base_price' => round($netBasePrice, 2),
            'ieps_type' => $iepsType,
            'ieps_value' => $iepsValue,
            'ieps_amount' => round($iepsAmount, 2),
            'iva_base_amount' => round($ivaBaseAmount, 2),
            'tax_iva_rate' => $taxIvaRatePercent,
            'iva_amount' => round($ivaAmount, 2),
            'tax_included' => $taxIncluded,
            'total_tax_amount' => round($totalTaxAmount, 2),
            'unit_price_final' => round($unitPriceFinal, 2),

            // Alias para compatibilidad con código existente
            'unit_price_before_tax' => round($netBasePrice, 2),
            'tax_rate' => round($taxIvaRatePercent + ($iepsType === 'percentage' ? $iepsValue * 100 : 0), 2),
            'tax_ieps_rate' => $iepsType === 'percentage' ? $iepsValue * 100 : 0,
            'tax_amount' => round($totalTaxAmount, 2),
        ];
    }
}
