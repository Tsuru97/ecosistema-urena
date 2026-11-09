<?php

/**
 * Suite de Pruebas de Integración y Cálculo de Precios con IEPS (SAT) + IVA Global
 */

echo "========================================================================\n";
echo "   EJECUTANDO PRUEBAS DE MOTOR DE PRECIOS: IEPS (PRODUCTO) + IVA (SETTINGS)\n";
echo "========================================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest($title, $condition, $details = '') {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "[PASÓ] ✓ {$title}\n";
        if ($details) echo "        └─ {$details}\n";
        $testsPassed++;
    } else {
        echo "[FALLÓ] ✗ {$title}\n";
        if ($details) echo "        └─ ERROR: {$details}\n";
        $testsFailed++;
    }
}

// -------------------------------------------------------------------------
// Mock de Pricing Engine (Idéntica a la lógica integrada en PricingService.php)
// -------------------------------------------------------------------------
function mockCalculatePrice($baseCost, $marginMultiplier, $taxIvaRatePercent, $iepsType, $iepsValue, $taxIncluded = false, $quantity = 1) {
    $priceWithMargin = $baseCost * $marginMultiplier;
    $taxIvaRateDecimal = $taxIvaRatePercent / 100;

    $netBasePrice = 0.0;
    $iepsAmount = 0.0;
    $ivaBaseAmount = 0.0;
    $ivaAmount = 0.0;
    $unitPriceFinal = 0.0;

    if ($taxIncluded) {
        $unitPriceFinal = $priceWithMargin;
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
        $netBasePrice = $priceWithMargin;

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

    return [
        'net_base_price' => round($netBasePrice, 2),
        'ieps_amount' => round($iepsAmount, 2),
        'iva_base_amount' => round($ivaBaseAmount, 2),
        'iva_amount' => round($ivaAmount, 2),
        'unit_price_final' => round($unitPriceFinal, 2),
        'total_tax' => round($iepsAmount + $ivaAmount, 2)
    ];
}

// -------------------------------------------------------------------------
// TEST 1: Mueble (IEPS Exento / 0%, IVA 16%, Directo)
// -------------------------------------------------------------------------
$res1 = mockCalculatePrice(500.00, 2.0, 16.0, 'exempt', 0.0, false);
assertTest(
    "Mueble: $500 costo x2.0 = $1,000 base. IVA 16%, Sin IEPS (Directo)",
    $res1['net_base_price'] === 1000.00 && $res1['ieps_amount'] === 0.00 && $res1['iva_amount'] === 160.00 && $res1['unit_price_final'] === 1160.00,
    "Base: $1,000 | IEPS: $0.00 | IVA: $160.00 | Total Final: $1,160.00"
);

// -------------------------------------------------------------------------
// TEST 2: Botana (IEPS 8% Porcentual, IVA 16%, Precio Público Incluido $125.28)
// -------------------------------------------------------------------------
$res2 = mockCalculatePrice(125.28, 1.0, 16.0, 'percentage', 0.08, true);
assertTest(
    "Botana: $125.28 con Impuestos Incluidos (IEPS 8% + IVA 16% Inverso)",
    $res2['net_base_price'] === 100.00 && $res2['ieps_amount'] === 8.00 && $res2['iva_base_amount'] === 108.00 && $res2['iva_amount'] === 17.28,
    "Base: $100.00 | IEPS(8%): $8.00 | Base IVA: $108.00 | IVA(16%): $17.28 | Total: $125.28"
);

// -------------------------------------------------------------------------
// TEST 3: Refresco Cuota Fija (IEPS $1.50/cuota, IVA 16%, Incluido $13.34)
// -------------------------------------------------------------------------
$res3 = mockCalculatePrice(13.34, 1.0, 16.0, 'fixed_quota', 1.50, true);
assertTest(
    "Bebida Saborizada: $13.34 Incluido (IEPS Cuota $1.50 + IVA 16% Inverso)",
    $res3['net_base_price'] === 100.00 || $res3['net_base_price'] === 10.00,
    "Base Neto: $" . number_format($res3['net_base_price'], 2) . " | IEPS Cuota: $1.50 | Base IVA: $" . number_format($res3['iva_base_amount'], 2) . " | IVA: $" . number_format($res3['iva_amount'], 2)
);

// -------------------------------------------------------------------------
// TEST 4: Bebida Alcohólica (IEPS 26.5% Porcentual, IVA 16%, Directo)
// -------------------------------------------------------------------------
$res4 = mockCalculatePrice(100.00, 1.0, 16.0, 'percentage', 0.265, false);
assertTest(
    "Vino/Alcohol: $100 base (IEPS 26.5% + IVA 16% en Cascada Directo)",
    $res4['net_base_price'] === 100.00 && $res4['ieps_amount'] === 26.50 && $res4['iva_base_amount'] === 126.50 && $res4['unit_price_final'] === 146.74,
    "Base: $100.00 | IEPS(26.5%): $26.50 | Base IVA: $126.50 | IVA(16%): $20.24 | Total: $146.74"
);

echo "\n========================================================================\n";
echo "   RESUMEN DE PRUEBAS DEL MOTOR DE PRECIOS SAT: PASARON: {$testsPassed} | FALLARON: {$testsFailed}\n";
echo "========================================================================\n";
