<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\Quote;
use App\Services\PricingService;
use App\Services\POSService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class POSApiController extends Controller
{
    protected PricingService $pricingService;
    protected POSService $posService;

    public function __construct(PricingService $pricingService, POSService $posService)
    {
        $this->pricingService = $pricingService;
        $this->posService = $posService;
    }

    /**
     * Búsqueda rápida de productos/variantes para agregar al carrito.
     * Prioriza búsqueda por código exacto de variante (SKU/Barcode).
     */
    public function search(Request $request)
    {
        $storeId = current_store_id();
        if (is_null($storeId) || $storeId === 'global') {
            return response()->json(['data' => []]);
        }

        $query = $request->input('q');
        if (!$query) {
            return response()->json(['data' => []]);
        }

        // Buscar primero variantes exactas por SKU o Barcode
        $variants = ProductVariant::where('sku', $query)
            ->orWhere('barcode', $query)
            ->with(['product.files'])
            ->get();

        if ($variants->count() > 0) {
            return response()->json(['data' => $this->formatVariantsForPos($variants, $storeId)]);
        }

        // Búsqueda LIKE por nombre de producto, SKU o descripción
        $products = Product::where('name', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with(['variants', 'files'])
            ->limit(10)
            ->get();

        $result = [];

        foreach ($products as $p) {
            $imagePath = $p->files->first() ? asset('storage/' . $p->files->first()->path) : asset('build/images/products/img-1.png');
            $pricing = $this->pricingService->calculatePrice($p, $storeId);
            $finalPrice = $pricing['unit_price_final'];
            $isVirtual = (bool) ($p->isVirtual() || $p->isService());
            $isWildcard = (bool) $p->isWildcard();

            if ($p->variants->count() > 0) {
                foreach ($p->variants as $v) {
                    $vCost = !is_null($v->cost) ? (float)$v->cost : null;
                    $vPricing = $this->pricingService->calculatePrice($p, $storeId, 'venta', 0, $vCost);
                    $variantPrice = $vPricing['unit_price_final'];
                    if ($v->price) {
                        $taxRate = $vPricing['tax_rate'] ?? 16;
                        $taxIncluded = $vPricing['tax_included'] ?? false;
                        if ($taxIncluded) {
                            $variantPrice = (float)$v->price;
                        } else {
                            $variantPrice = (float)$v->price * (1 + ($taxRate / 100));
                        }
                    }
                    
                    $stockData = $this->getStockByWarehouseType($storeId, $p->id, $v->id);

                    $result[] = [
                        'id'               => $p->id,
                        'variant_id'       => $v->id,
                        'name'             => $p->name . ($v->name ? ' - ' . $v->name : ''),
                        'sku'              => $v->sku,
                        'price'            => round($variantPrice, 2),
                        'stock'            => $isVirtual ? 9999 : $stockData['total'],
                        'stock_warehouse'  => $stockData['warehouse'],
                        'stock_exhibition' => $stockData['exhibition'],
                        'only_exhibition'  => $stockData['only_exhibition'],
                        'image'            => $imagePath,
                        'is_virtual'       => $isVirtual,
                        'is_wildcard'      => $isWildcard,
                        'type'             => $p->type,
                    ];
                }
            } else {
                $stockData = $this->getStockByWarehouseType($storeId, $p->id, null);

                $result[] = [
                    'id'               => $p->id,
                    'variant_id'       => null,
                    'name'             => $p->name,
                    'sku'              => $p->sku,
                    'price'            => $finalPrice,
                    'stock'            => $isVirtual ? 9999 : $stockData['total'],
                    'stock_warehouse'  => $stockData['warehouse'],
                    'stock_exhibition' => $stockData['exhibition'],
                    'only_exhibition'  => $stockData['only_exhibition'],
                    'image'            => $imagePath,
                    'is_virtual'       => $isVirtual,
                    'is_wildcard'      => $isWildcard,
                    'type'             => $p->type,
                ];
            }
        }

        return response()->json(['data' => $result]);
    }

    /**
     * Recibe los items del carrito y devuelve los totales recalculados.
     */
    public function calculate(Request $request)
    {
        $items = $request->input('items', []);
        $storeId = current_store_id();
        if (is_null($storeId) || $storeId === 'global') {
            return response()->json(['error' => 'Debes seleccionar una sucursal en el selector superior.'], 400);
        }
        $msiMonths = (int)$request->input('msi', 0);
        
        $msiCampaignActive = (bool)\App\Models\Setting::get('msi_campaign_active', false, $storeId);
        $msiMonthsCalc = $msiCampaignActive ? 0 : $msiMonths;

        $subtotal = 0;
        $ivaTotal = 0;
        $iepsTotal = 0;
        $taxTotal = 0;
        $total = 0;
        $discountTotal = 0;
        $requiresOtp = false;

        foreach ($items as &$item) {
            $productId = $item['id'] ?? null;
            $product = null;

            if (!empty($item['is_delivery_charge']) || ($item['sku'] ?? '') === 'SERV-FLETE') {
                $product = Product::firstOrCreate(
                    ['sku' => 'SERV-FLETE'],
                    [
                        'name' => 'Flete / Envío a Domicilio',
                        'slug' => 'servicio-flete-envio-domicilio',
                        'description' => 'Servicio logístico de flete y transporte de mercancía para entrega a domicilio',
                        'type' => 'service',
                        'status' => true,
                        'is_wildcard' => false,
                        'base_cost' => 0
                    ]
                );
                $item['id'] = $product->id;
                $item['is_service'] = true;
                $item['is_delivery_charge'] = true;
            } elseif (!empty($item['is_installation_charge']) || ($item['sku'] ?? '') === 'SERV-ARMADO') {
                $product = Product::firstOrCreate(
                    ['sku' => 'SERV-ARMADO'],
                    [
                        'name' => 'Servicio de Armado e Instalación',
                        'slug' => 'servicio-armado-instalacion',
                        'description' => 'Servicio especializado de armado, ensamble e instalación de muebles en sitio',
                        'type' => 'service',
                        'status' => true,
                        'is_wildcard' => false,
                        'base_cost' => 0
                    ]
                );
                $item['id'] = $product->id;
                $item['is_service'] = true;
                $item['is_installation_charge'] = true;
            } elseif ($productId) {
                $product = Product::find($productId);
            }

            if (!$product) {
                $product = Product::where('is_wildcard', true)->first() 
                        ?? Product::where('sku', 'CUSTOM-001')->first();
            }

            if (!$product) continue;

            $variant = null;
            $variantCost = null;
            if (!empty($item['variant_id'])) {
                $variant = \App\Models\ProductVariant::find($item['variant_id']);
                if ($variant && !is_null($variant->cost)) {
                    $variantCost = (float)$variant->cost;
                }
            }

            // Tipo de precio por producto
            $itemPriceType = $item['price_type'] ?? 'venta';
            $calcType = ($itemPriceType === 'personalizado') ? 'venta' : $itemPriceType;

            $pricing = $this->pricingService->calculatePrice($product, $storeId, $calcType, $msiMonthsCalc, $variantCost);
            $qty = (int)$item['quantity'];
            
            // Si la variante tiene un precio específico, lo usamos como base, aplicando MSI e impuestos si corresponde
            $baseFinalPrice = $pricing['unit_price_final'];
            if ($variant && $variant->price) {
                $msiPercentage = 0;
                if ($msiMonthsCalc > 0) {
                    $msiKey = "interest_{$msiMonthsCalc}msi";
                    $msiPercentage = (float)\App\Models\Setting::get($msiKey, 0, $storeId);
                }
                $priceWithMsi = (float)$variant->price * (1 + ($msiPercentage / 100));

                $taxRate = $pricing['tax_rate'] ?? 16;
                $taxIncluded = $pricing['tax_included'] ?? false;
                if ($taxIncluded) {
                    $baseFinalPrice = $priceWithMsi;
                } else {
                    $baseFinalPrice = $priceWithMsi * (1 + ($taxRate / 100));
                }
            }

            // Calcular matriz de precios y plazos de MSI para info en el frontend (Refleja la campaña si está activa)
            $options = [];
            $typesToCalc = ['venta', 'lista', 'rebajado', 'outlet'];
            if ($itemPriceType === 'personalizado') {
                $typesToCalc[] = 'personalizado';
            }

            $uiPrice = isset($item['adjusted_price']) && is_numeric($item['adjusted_price']) ? (float)$item['adjusted_price'] : $baseFinalPrice;

            foreach ($typesToCalc as $type) {
                $options[$type] = [];
                foreach ([0, 3, 6, 9, 12] as $m) {
                    $mCalc = $msiCampaignActive ? 0 : $m;
                    
                    if ($type === 'personalizado') {
                        $msiPercent = 0;
                        if ($mCalc > 0) {
                            $msiPercent = (float)\App\Models\Setting::get("interest_{$mCalc}msi", 0, $storeId);
                        }
                        $vPrice = $uiPrice * (1 + ($msiPercent / 100));
                    } else {
                        $calc = $this->pricingService->calculatePrice($product, $storeId, $type, $mCalc, $variantCost);
                        $vPrice = $calc['unit_price_final'];
                        if ($variant && $variant->price) {
                            $msiPercent = 0;
                            if ($mCalc > 0) {
                                $msiPercent = (float)\App\Models\Setting::get("interest_{$mCalc}msi", 0, $storeId);
                            }
                            $pWithMsi = (float)$variant->price * (1 + ($msiPercent / 100));
                            $tIncluded = $calc['tax_included'] ?? false;
                            $tRate = $calc['tax_rate'] ?? 16;
                            $vPrice = $tIncluded ? $pWithMsi : $pWithMsi * (1 + ($tRate / 100));
                        }
                    }
                    
                    $options[$type][$m] = [
                        'price' => round($vPrice, 2)
                    ];
                }
            }

            // Si es personalizado, le aplicamos el recargo financiero sobre el precio negociado (ya que se negoció en base a contado)
            if ($itemPriceType === 'personalizado') {
                $msiPercentage = 0;
                if ($msiMonthsCalc > 0) {
                    $msiKey = "interest_{$msiMonthsCalc}msi";
                    $msiPercentage = (float)\App\Models\Setting::get($msiKey, 0, $storeId);
                }
                $uiPrice = $uiPrice * (1 + ($msiPercentage / 100));
            }

            // Si el precio de UI es menor al precio calculado base, requiere autorización (Regateo)
            if ($uiPrice < $baseFinalPrice) {
                $requiresOtp = true;
            }

            $lineDiscount = 0;
            if ($itemPriceType === 'personalizado') {
                $lineDiscount = max(0, $baseFinalPrice - $uiPrice) * $qty;
            }
            $discountTotal += $lineDiscount;

            // Calcular desglose real del ítem según impuestos SAT (IVA + IEPS)
            $taxRate = $pricing['tax_rate'] ?? 16;
            $taxMultiplier = 1 + ($taxRate / 100);

            // En la UI, uiPrice es el precio total al cliente con impuestos
            $unitPriceBeforeTax = $uiPrice / $taxMultiplier;
            $ivaBaseLine = $unitPriceBeforeTax * $qty;
            $ivaLine = $ivaBaseLine * ($taxRate / 100);

            $iepsType = $product->ieps_type ?? 'exempt';
            $iepsValue = (float)($product->ieps_value ?? 0.0);
            $iepsLine = 0.0;
            if ($iepsType === 'percentage' && $iepsValue > 0) {
                $subtotalLine = $ivaBaseLine / (1 + $iepsValue);
                $iepsLine = $ivaBaseLine - $subtotalLine;
            } elseif ($iepsType === 'fixed_quota' && $iepsValue > 0) {
                $iepsLine = $iepsValue * $qty;
                $subtotalLine = max(0, $ivaBaseLine - $iepsLine);
            } else {
                $subtotalLine = $ivaBaseLine;
                $iepsLine = 0.0;
            }

            $itemSubtotalLine = $subtotalLine;
            $itemIvaLine = $ivaLine;
            $itemIepsLine = $iepsLine;
            $itemTaxLine = $ivaLine + $iepsLine;
            $itemTotalLine = $uiPrice * $qty;

            $item['price'] = $uiPrice;
            $item['original_price'] = $baseFinalPrice;
            $item['price_options'] = $options;
            $item['subtotal'] = $itemSubtotalLine;
            $item['iva_amount'] = $itemIvaLine;
            $item['ieps_amount'] = $itemIepsLine;
            $item['tax_amount'] = $itemTaxLine;

            $subtotal += $itemSubtotalLine;
            $ivaTotal += $itemIvaLine;
            $iepsTotal += $itemIepsLine;
            $taxTotal += $itemTaxLine;
            $total += $itemTotalLine;
        }

        return response()->json([
            'subtotal' => round($subtotal, 2),
            'iva_total' => round($ivaTotal, 2),
            'ieps_total' => round($iepsTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total' => round($total, 2),
            'requires_otp' => $requiresOtp,
            'items' => $items
        ]);
    }

    public function checkout(Request $request)
    {
        $items = $request->input('items', []);
        $customerId = $request->input('customer_id');
        $sellerId = $request->input('seller_id') ?: auth()->id();
        $methodId = $request->input('method_id');
        $isLayaway = $request->input('is_layaway', false);
        $amountPaid = $request->input('amount_paid');
        $storeId = current_store_id();
        if (is_null($storeId) || $storeId === 'global') {
            return response()->json(['success' => false, 'message' => 'Debes seleccionar una sucursal en el selector superior.']);
        }
        $priceType = $request->input('price_type', 'venta');
        $msiMonths = (int)$request->input('msi', 0);

        $msiCampaignActive = (bool)\App\Models\Setting::get('msi_campaign_active', false, $storeId);
        $msiMonthsCalc = $msiCampaignActive ? 0 : $msiMonths;

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío.']);
        }

        try {
            // 1. Crear Cotización
            $quote = \App\Models\Quote::create([
                'store_id' => $storeId,
                'user_id' => auth()->id(),
                'seller_id' => $sellerId,
                'customer_id' => $customerId ?: null,
                'subtotal' => 0,
                'ieps_total' => 0,
                'iva_total' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'total' => 0,
                'status' => 'borrador'
            ]);

            $total = 0;
            $subtotalVal = 0;
            $iepsTotalVal = 0;
            $ivaTotalVal = 0;
            $taxTotalVal = 0;
            $discountTotalVal = 0;

            foreach ($items as $item) {
                $productId = $item['id'] ?? null;
                $product = null;

                if (!empty($item['is_delivery_charge']) || ($item['sku'] ?? '') === 'SERV-FLETE') {
                    $product = Product::firstOrCreate(
                        ['sku' => 'SERV-FLETE'],
                        [
                            'name' => 'Flete / Envío a Domicilio',
                            'slug' => 'servicio-flete-envio-domicilio',
                            'description' => 'Servicio logístico de flete y transporte de mercancía para entrega a domicilio',
                            'type' => 'service',
                            'status' => true,
                            'is_wildcard' => false,
                            'base_cost' => 0
                        ]
                    );
                } elseif (!empty($item['is_installation_charge']) || ($item['sku'] ?? '') === 'SERV-ARMADO') {
                    $product = Product::firstOrCreate(
                        ['sku' => 'SERV-ARMADO'],
                        [
                            'name' => 'Servicio de Armado e Instalación',
                            'slug' => 'servicio-armado-instalacion',
                            'description' => 'Servicio especializado de armado, ensamble e instalación de muebles en sitio',
                            'type' => 'service',
                            'status' => true,
                            'is_wildcard' => false,
                            'base_cost' => 0
                        ]
                    );
                } elseif ($productId) {
                    $product = Product::find($productId);
                }

                if (!$product) {
                    $product = Product::where('is_wildcard', true)->first() 
                            ?? Product::where('sku', 'CUSTOM-001')->first();
                }
                if (!$product) continue;

                $qty = (int)$item['quantity'];

                // Para productos virtuales/servicio/comodín:
                // usar el precio que viene del frontend directamente, sin recalcular márgenes
                $isNonPhysical = !$product->isPhysical() || !empty($item['is_delivery_charge']) || !empty($item['is_installation_charge']);

                $variant = null;
                $variantCost = null;
                if (!empty($item['variant_id'])) {
                    $variant = \App\Models\ProductVariant::find($item['variant_id']);
                    if ($variant && !is_null($variant->cost)) {
                        $variantCost = (float)$variant->cost;
                    }
                }

                $itemPriceType = $item['price_type'] ?? 'venta';
                $calcType = ($itemPriceType === 'personalizado') ? 'venta' : $itemPriceType;
                $pricing = $this->pricingService->calculatePrice($product, $storeId, $calcType, $msiMonthsCalc, $variantCost);

                // Precio base: para comodín se usa el precio que definió el cajero
                $baseFinalPrice = $isNonPhysical && isset($item['adjusted_price']) && is_numeric($item['adjusted_price'])
                    ? (float)$item['adjusted_price']
                    : $pricing['unit_price_final'];

                if ($variant && $variant->price && !$isNonPhysical) {
                    $msiPercentage = 0;
                    if ($msiMonthsCalc > 0) {
                        $msiKey = "interest_{$msiMonthsCalc}msi";
                        $msiPercentage = (float)\App\Models\Setting::get($msiKey, 0, $storeId);
                    }
                    $priceWithMsi = (float)$variant->price * (1 + ($msiPercentage / 100));

                    $taxRate = $pricing['tax_rate'] ?? 16;
                    $taxIncluded = $pricing['tax_included'] ?? false;
                    if ($taxIncluded) {
                        $baseFinalPrice = $priceWithMsi;
                    } else {
                        $baseFinalPrice = $priceWithMsi * (1 + ($taxRate / 100));
                    }
                }

                $uiPrice = isset($item['adjusted_price']) && is_numeric($item['adjusted_price']) ? (float)$item['adjusted_price'] : $baseFinalPrice;

                if ($itemPriceType === 'personalizado' && !$isNonPhysical) {
                    $msiPercentage = 0;
                    if ($msiMonthsCalc > 0) {
                        $msiKey = "interest_{$msiMonthsCalc}msi";
                        $msiPercentage = (float)\App\Models\Setting::get($msiKey, 0, $storeId);
                    }
                    $uiPrice = $uiPrice * (1 + ($msiPercentage / 100));
                }

                $lineDiscount = 0;
                if ($itemPriceType === 'personalizado') {
                    $lineDiscount = max(0, $baseFinalPrice - $uiPrice) * $qty;
                }
                $discountTotalVal += $lineDiscount;

                // Desglose fiscal
                $taxIvaRatePercent = (float)\App\Models\Setting::get('tax_iva_rate', 16, $storeId);
                $taxIvaRateDecimal = $taxIvaRatePercent / 100;

                $itemTotal = $uiPrice * $qty;
                $ivaBaseLine = $itemTotal / (1 + $taxIvaRateDecimal);
                $ivaLine = $itemTotal - $ivaBaseLine;

                $iepsType  = $product->ieps_type  ?? 'exempt';
                $iepsValue = (float)($product->ieps_value ?? 0.0);

                $subtotalLine = 0.0;
                $iepsLine     = 0.0;

                if ($iepsType === 'percentage' && $iepsValue > 0) {
                    $subtotalLine = $ivaBaseLine / (1 + $iepsValue);
                    $iepsLine     = $ivaBaseLine - $subtotalLine;
                } elseif ($iepsType === 'fixed_quota' && $iepsValue > 0) {
                    $iepsLine     = $iepsValue * $qty;
                    $subtotalLine = max(0, $ivaBaseLine - $iepsLine);
                } else {
                    $subtotalLine = $ivaBaseLine;
                    $iepsLine     = 0.0;
                }

                $total        += $itemTotal;
                $subtotalVal  += $subtotalLine;
                $iepsTotalVal += $iepsLine;
                $ivaTotalVal  += $ivaLine;
                $taxTotalVal  += ($iepsLine + $ivaLine);

                // Nombre: para comodín usar el custom_name que definió el cajero
                \App\Models\QuoteItem::create([
                    'quote_id'            => $quote->id,
                    'product_id'          => $product->id,
                    'product_variant_id'  => $item['variant_id'] ?? null,
                    'quantity'            => $qty,
                    'base_cost'           => $pricing['base_cost'],
                    'unit_price_original' => $baseFinalPrice,
                    'unit_price_final'    => $uiPrice,
                    'tax_amount'          => round($iepsLine + $ivaLine, 2),
                    'ieps_amount'         => round($iepsLine, 2),
                    'iva_amount'          => round($ivaLine, 2),
                    'subtotal'            => round($subtotalLine, 2),
                    'total'               => round($itemTotal, 2),
                    // Notas por ítem + descripción del comodín / servicio
                    'notes'               => ($product->is_wildcard && !empty($item['name']) && $item['name'] !== $product->name)
                        ? ($item['name'] . (!empty($item['notes']) ? " - " . $item['notes'] : ''))
                        : (!empty($item['custom_description'])
                            ? ($item['custom_description'] . (!empty($item['notes']) ? "\n" . $item['notes'] : ''))
                            : ($item['notes'] ?? null)),
                ]);
            }

            $cartTotal = $total;

            // Procesar Cupón de Descuento
            $couponCode = $request->input('coupon_code');
            $couponId   = $request->input('coupon_id');
            $couponDiscount = 0;
            $appliedCoupon = null;

            if (($couponCode || $couponId) && \Illuminate\Support\Facades\Schema::hasTable('coupons')) {
                $appliedCoupon = \App\Models\Coupon::when($couponId, fn($q) => $q->where('id', $couponId))
                    ->when(!$couponId && $couponCode, fn($q) => $q->where('code', strtoupper(trim($couponCode))))
                    ->first();

                $couponErrorMsg = null;
                if (!$appliedCoupon || !$appliedCoupon->isValidFor($cartTotal, $customerId, $storeId, $couponErrorMsg)) {
                    throw new \Exception($couponErrorMsg ?: 'El cupón aplicado ya no es válido para las condiciones actuales de la venta.');
                }

                $couponDiscount = $appliedCoupon->calculateDiscount($cartTotal);
                $cartTotal = max(0, $cartTotal - $couponDiscount);
            }

            // Procesar pagos múltiples
            $paymentsInput = $request->input('payments', []);
            if (empty($paymentsInput)) {
                // Fallback si viene de versión anterior (pago único)
                $paymentsInput = [
                    [
                        'payment_method_id' => $methodId,
                        'amount' => $amountPaid ?? $cartTotal,
                        'msi_applied' => $msiMonths,
                        'reference' => $request->input('reference'),
                        'notes' => $request->input('notes'),
                    ]
                ];
            }

            $processedPayments = [];
            $totalFinancingInterest = 0;
            $totalTerminalCommissionChargedToClient = 0;
            $totalAmountPaid = 0;

            foreach ($paymentsInput as $pData) {
                $pmId = (int)($pData['payment_method_id'] ?? 0);
                if (!$pmId) continue;
                $pm = \App\Models\PaymentMethod::find($pmId);
                if (!$pm) continue;
                
                $amountBase = (float)($pData['amount'] ?? 0);
                if ($amountBase <= 0) continue;
                
                // 1. Calcular intereses de financiamiento y sobretasa con IVA dinámico de Settings
                $msiApplied = (int)($pData['msi_applied'] ?? 0);
                $interestAmount = 0;
                $msiSobretasaAbsorbed = 0;
                $applyIva = (bool)($pm->apply_iva_to_rates ?? true);
                
                $taxIvaRate = (float)\App\Models\Setting::get('tax_iva_rate', 16, $storeId);
                $ivaMultiplier = $applyIva ? (1 + ($taxIvaRate / 100)) : 1.0;
                
                if ($msiApplied > 0 && $pm->accepts_financing && is_array($pm->financing_rates)) {
                    $rateObj = collect($pm->financing_rates)->firstWhere('months', $msiApplied);
                    $ratePercent = $rateObj ? (float)$rateObj['rate'] : 0;
                    
                    if ($ratePercent > 0) {
                        $rawSobretasaWithIva = ($amountBase * ($ratePercent / 100)) * $ivaMultiplier;
                        
                        if ($pm->transfer_msi_cost_to_client) {
                            // Opción activada en método de pago: Trasladar costo de sobretasa (con IVA) al cliente
                            $interestAmount = $rawSobretasaWithIva;
                        } elseif ($msiCampaignActive) {
                            // Campaña MSI ACTIVA: La empresa absorbe la sobretasa (con IVA) como gasto operativo
                            $msiSobretasaAbsorbed = $rawSobretasaWithIva;
                        }
                        // Si msiCampaignActive es false y no se traslada al cliente: Informativo para el cliente ante su banco.
                    }
                }
                
                $amountWithInterest = $amountBase + $interestAmount;
                $totalFinancingInterest += $interestAmount;
                
                // 2. Calcular comisión de terminal (con IVA del 16% si aplica)
                $terminalId = !empty($pData['payment_terminal_id']) ? (int)$pData['payment_terminal_id'] : null;
                $terminalCommission = 0;
                $commissionChargedTo = null;
                
                if ($terminalId) {
                    $terminal = \App\Models\PaymentTerminal::find($terminalId);
                    if ($terminal) {
                        $fixedComm = (float)$terminal->commission_fixed;
                        $percentComm = (float)$terminal->commission_percentage;
                        
                        if ($fixedComm > 0 || $percentComm > 0) {
                            $rawComm = $fixedComm + ($amountWithInterest * ($percentComm / 100));
                            $terminalCommission = $rawComm * $ivaMultiplier;
                        }
                        
                        $commissionChargedTo = $terminal->charge_client ? 'client' : 'company';
                        
                        if ($terminal->charge_client) {
                            $totalTerminalCommissionChargedToClient += $terminalCommission;
                            $amountFinal = $amountWithInterest + $terminalCommission;
                        } else {
                            $amountFinal = $amountWithInterest;
                        }
                    } else {
                        $amountFinal = $amountWithInterest;
                    }
                } else {
                    $amountFinal = $amountWithInterest;
                }
                
                $totalAmountPaid += $amountFinal;
                
                $processedPayments[] = [
                    'payment_method_id' => $pm->id,
                    'payment_terminal_id' => $terminalId,
                    'amount' => $amountFinal,
                    'terminal_commission' => $terminalCommission,
                    'commission_charged_to' => $commissionChargedTo,
                    'msi_applied' => $msiApplied,
                    'msi_sobretasa_absorbed' => $msiSobretasaAbsorbed,
                    'reference' => $pData['reference'] ?? null,
                    'notes' => $pData['notes'] ?? null,
                ];
            }

            // El total final de la venta incluye la suma de recargos de financiamiento y comisiones cargadas al cliente
            $saleTotal = $cartTotal + $totalFinancingInterest + $totalTerminalCommissionChargedToClient;

            // Si el monto total pagado es menor al total requerido de la venta, forzar apartado
            $finalIsLayaway = $isLayaway;
            if ($totalAmountPaid < $saleTotal) {
                $finalIsLayaway = true;
            }

            // VALIDACIÓN A: Si es Apartado, validar el Porcentaje Mínimo de Anticipo desde Settings
            if ($finalIsLayaway) {
                $minPct = (float)\App\Models\Setting::get('layaway_min_downpayment_percentage', 20, $storeId);
                $minRequiredAmount = $saleTotal * ($minPct / 100);

                if ($totalAmountPaid < $minRequiredAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => "El anticipo ingresado ($" . number_format($totalAmountPaid, 2) . ") es menor al monto mínimo requerido ({$minPct}% = $" . number_format($minRequiredAmount, 2) . ") para registrar un apartado en esta sucursal."
                    ], 422);
                }

                // VALIDACIÓN A.2: Validar Línea de Crédito del Cliente
                $creditCheckEnabled = filter_var(\App\Models\Setting::get('customer_credit_check_enabled', true, $storeId), FILTER_VALIDATE_BOOLEAN);
                if ($creditCheckEnabled && $customerId) {
                    $customer = \App\Models\Customer::find($customerId);
                    $unpaidBalance = max(0, $saleTotal - $totalAmountPaid);
                    if ($customer && !$customer->hasAvailableCredit($unpaidBalance)) {
                        $authorizedBy = session('otp_authorized_by');
                        if (!$authorizedBy) {
                            return response()->json([
                                'success' => false,
                                'requires_credit_otp' => true,
                                'available_credit' => (float)$customer->available_credit,
                                'required_credit' => $unpaidBalance,
                                'message' => "La línea de crédito disponible del cliente ($" . number_format($customer->available_credit, 2) . ") es insuficiente para cubrir el saldo restante del apartado ($" . number_format($unpaidBalance, 2) . "). Se requiere autorización OTP de un superior para proceder."
                            ], 422);
                        }
                    }
                }
            } else {
                // VALIDACIÓN B: Si es Venta Inmediata y la tienda no permite venta sin stock, validar existencias
                $allowOutOfStock = filter_var(\App\Models\Setting::get('allow_out_of_stock_sales', false, $storeId), FILTER_VALIDATE_BOOLEAN);
                if (!$allowOutOfStock) {
                    foreach ($items as $chkItem) {
                        $pId     = $chkItem['id'] ?? null;
                        $vId     = $chkItem['variant_id'] ?? null;
                        $qNeeded = (int)($chkItem['quantity'] ?? 1);

                        // Saltar conceptos de flete, armado, servicios adicionales o virtuales
                        if (!empty($chkItem['is_delivery_charge']) || !empty($chkItem['is_installation_charge']) || !empty($chkItem['is_virtual'])) {
                            continue;
                        }

                        // Saltar si no hay ID de producto
                        if (!$pId) {
                            continue;
                        }

                        // Saltar validación para productos virtuales, servicio y comodín
                        $chkProduct = Product::find($pId);
                        if (!$chkProduct || !$chkProduct->isPhysical() || $chkProduct->is_wildcard) {
                            continue;
                        }

                        $invRecords = \App\Models\Inventory::where('store_id', $storeId)
                            ->where('product_id', $pId)
                            ->when($vId, fn($q) => $q->where('product_variant_id', $vId), fn($q) => $q->whereNull('product_variant_id'))
                            ->get();

                        $avail = $invRecords->sum(fn($i) => max(0, $i->quantity - $i->reserved_quantity));

                        if ($avail < $qNeeded) {
                            $prodName = $chkProduct->name ?? 'Producto';
                            return response()->json([
                                'success' => false,
                                'message' => "Stock insuficiente en esta sucursal para '{$prodName}'. Disponible para entrega inmediata: {$avail} unidad(es), solicitado: {$qNeeded}."
                            ], 422);
                        }
                    }
                }
            }

            $quoteData = [
                'subtotal' => round($subtotalVal, 2),
                'ieps_total' => round($iepsTotalVal, 2),
                'iva_total' => round($ivaTotalVal, 2),
                'tax_total' => round($taxTotalVal, 2),
                'discount_total' => round($discountTotalVal, 2),
                'coupon_id' => $appliedCoupon?->id,
                'coupon_code' => $appliedCoupon?->code,
                'coupon_discount' => round($couponDiscount, 2),
                'total' => round($saleTotal, 2)
            ];

            // Datos de Entrega a Domicilio
            $deliveryInput = $request->input('delivery', []);
            if (!empty($deliveryInput['is_delivery'])) {
                $lat = !empty($deliveryInput['lat']) ? (float)$deliveryInput['lat'] : null;
                $lng = !empty($deliveryInput['lng']) ? (float)$deliveryInput['lng'] : null;
                $mapsUrl = $deliveryInput['maps_url'] ?? (($lat && $lng) ? "https://www.google.com/maps?q={$lat},{$lng}" : null);

                $quoteData['is_delivery']             = true;
                $quoteData['delivery_recipient_name'] = $deliveryInput['recipient_name'] ?? null;
                $quoteData['delivery_phone']          = $deliveryInput['phone'] ?? null;
                $quoteData['delivery_address']        = $deliveryInput['address'] ?? null;
                $quoteData['delivery_references']     = $deliveryInput['references'] ?? null;
                $quoteData['delivery_lat']            = $lat;
                $quoteData['delivery_lng']            = $lng;
                $quoteData['delivery_maps_url']       = $mapsUrl;
                $quoteData['delivery_date']           = !empty($deliveryInput['date']) ? $deliveryInput['date'] : null;
                $quoteData['delivery_time']           = !empty($deliveryInput['time']) ? $deliveryInput['time'] : null;
                $quoteData['delivery_time_slot']      = $deliveryInput['time_slot'] ?? null;
                $quoteData['delivery_instructions']   = $deliveryInput['instructions'] ?? null;
                $quoteData['delivery_cost']           = !empty($deliveryInput['delivery_cost']) ? (float)$deliveryInput['delivery_cost'] : 0;
                $quoteData['installation_cost']       = !empty($deliveryInput['installation_cost']) ? (float)$deliveryInput['installation_cost'] : 0;
                $quoteData['delivery_status']         = 'pendiente';
            }

            $quote->update($quoteData);

            // Guardar autorización si hay descuento y se validó un OTP en esta sesión
            $authorizerId = session('otp_authorized_by');
            if ($authorizerId && $discountTotalVal > 0) {
                \App\Models\DiscountAuthorization::create([
                    'quote_id' => $quote->id,
                    'requested_by' => auth()->id() ?? $quote->user_id,
                    'authorized_by' => $authorizerId,
                    'otp_code' => 'OTP',
                    'status' => 'approved',
                    'expires_at' => now()->addMinutes(10),
                ]);
            }
            
            // Registrar actividad de auditoría (Spatie Activity Log)
            if ($discountTotalVal > 0) {
                $authorizer = $authorizerId ? \App\Models\User::find($authorizerId) : null;
                $authName = $authorizer ? $authorizer->name : 'N/A';
                
                activity('pos')
                    ->performedOn($quote)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'authorized_by_id' => $authorizerId,
                        'authorized_by_name' => $authName,
                        'discount_total' => $discountTotalVal
                    ])
                    ->log("Venta cobrada con descuento de regateo total de $" . number_format($discountTotalVal, 2) . ". Autorizado por: {$authName}");
            }
            
            // Limpiar la variable de sesión para que no se arrastre
            session()->forget('otp_authorized_by');

            // 2. Convertir a Venta
            $paymentData = [
                'method_id' => $processedPayments[0]['payment_method_id'] ?? null,
                'amount_paid' => $totalAmountPaid,
                'msi' => $processedPayments[0]['msi_applied'] ?? 0,
                'payments' => $processedPayments,
                'coupon_id' => $appliedCoupon?->id,
                'coupon_code' => $appliedCoupon?->code,
                'coupon_discount' => round($couponDiscount, 2),
            ];

            $sale = $this->posService->convertQuoteToSale($quote, $paymentData, $finalIsLayaway);

            // Si la venta proviene de una cotización formal cargada en el POS, marcarla como convertida
            $loadedQuoteId = $request->input('quote_id');
            if ($loadedQuoteId) {
                $formalQuote = Quote::where('id', $loadedQuoteId)->whereNotNull('folio')->first();
                if ($formalQuote && $formalQuote->status !== 'convertida') {
                    $formalQuote->update(['status' => 'convertida']);
                    activity('pos')
                        ->performedOn($formalQuote)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'sale_id' => $sale->id,
                            'ticket_folio' => $sale->ticket_folio,
                        ])
                        ->log("Cotización formal {$formalQuote->folio} convertida en la venta {$sale->ticket_folio}");
                }
            }

            $deliveryUrl = $sale->hasDelivery() ? route('admin.pos.delivery-order', $sale->id) : null;

            return response()->json([
                'success' => true,
                'message' => 'Transacción completada exitosamente',
                'sale_id' => $sale->id,
                'ticket_folio' => $sale->ticket_folio,
                'is_delivery' => $sale->hasDelivery(),
                'delivery_order_url' => $deliveryUrl
            ]);

        } catch (\Exception $e) {
            Log::error("Error en checkout POS: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function createCustomer(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'last_name'             => 'nullable|string|max:255',
            'phone'                 => 'nullable|string|max:20',
            'email'                 => 'nullable|email|max:255',
            'rfc'                   => 'nullable|string|max:20',
            'business_name'         => 'nullable|string|max:255',
            'legal_address'         => 'nullable|string',
            'street'                => 'nullable|string|max:255',
            'colonia'               => 'nullable|string|max:255',
            'city'                  => 'nullable|string|max:100',
            'state'                 => 'nullable|string|max:100',
            'zip'                   => 'nullable|string|max:20',
            'delivery_references'   => 'nullable|string|max:1000',
            'delivery_instructions' => 'nullable|string|max:1000',
            'delivery_lat'          => 'nullable|numeric',
            'delivery_lng'          => 'nullable|numeric',
            'delivery_maps_url'     => 'nullable|string|max:500',
        ]);

        \DB::beginTransaction();
        try {
            // 1. Crear el usuario
            $email = $request->input('email');
            $existingUser = !empty($email) ? \App\Models\User::where('email', $email)->first() : null;

            if ($existingUser) {
                if ($existingUser->customer) {
                    return response()->json([
                        'success' => false,
                        'message' => "El correo {$email} pertenece al cliente ya registrado #{$existingUser->customer->id} ({$existingUser->name} {$existingUser->lastname}).",
                    ], 422);
                }

                $user = $existingUser;
                $user->update(array_filter([
                    'phone' => $request->input('phone') ?: $user->phone,
                    'rfc'   => $request->input('rfc') ?: $user->rfc,
                ]));

                $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
                if (!$user->hasRole('Cliente')) {
                    $user->assignRole($role);
                }
                \App\Models\User::flushCache();
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            } else {
                if (empty($email)) {
                    $email = 'cliente_' . \Illuminate\Support\Str::random(10) . '@muebleriaurena.com.mx';
                }

                $username = 'cust_' . \Illuminate\Support\Str::random(10);
                while (\App\Models\User::where('username', $username)->exists()) {
                    $username = 'cust_' . \Illuminate\Support\Str::random(10);
                }

                $user = \App\Models\User::create([
                    'name'     => $request->input('name'),
                    'lastname' => $request->input('last_name'),
                    'username' => $username,
                    'email'    => $email,
                    'password' => \Hash::make(\Illuminate\Support\Str::random(16)),
                    'phone'    => $request->input('phone'),
                    'rfc'      => $request->input('rfc'),
                    'status'   => true,
                ]);

                $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
                $user->assignRole($role);
                \App\Models\User::flushCache();
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }

            $latVal = $request->filled('delivery_lat') ? (float)$request->input('delivery_lat') : null;
            $lngVal = $request->filled('delivery_lng') ? (float)$request->input('delivery_lng') : null;
            $mapsUrl = $request->input('delivery_maps_url');
            if (empty($mapsUrl) && $latVal && $lngVal) {
                $mapsUrl = "https://www.google.com/maps?q={$latVal},{$lngVal}";
            }

            // 2. Crear el Customer enlazado
            $customer = \App\Models\Customer::create([
                'user_id'               => $user->id,
                'name'                  => $request->input('name'),
                'lastname'              => $request->input('last_name'),
                'email'                 => $request->input('email'),
                'phone'                 => $request->input('phone'),
                'rfc'                   => $request->input('rfc'),
                'business_name'         => $request->input('business_name'),
                'legal_address'         => $request->input('legal_address'),
                'street'                => $request->input('street'),
                'colonia'               => $request->input('colonia'),
                'city'                  => $request->input('city'),
                'state'                 => $request->input('state'),
                'zip'                   => $request->input('zip'),
                'delivery_references'   => $request->input('delivery_references'),
                'delivery_instructions' => $request->input('delivery_instructions'),
                'delivery_lat'          => $latVal,
                'delivery_lng'          => $lngVal,
                'delivery_maps_url'     => $mapsUrl,
                'status'                => true,
            ]);

            // Sincronizar sucursal en store_user
            (new \App\Services\UserProfileSyncService())->syncUserProfile($user);

            \DB::commit();

            \App\Models\Customer::flushCache();

            // Auditoría
            activity('customers')
                ->performedOn($customer)
                ->causedBy(auth()->user())
                ->log("Cliente {$user->name} {$user->lastname} creado con usuario ID: {$user->id} desde el POS.");

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente.',
                'customer' => [
                    'id'                    => $customer->id,
                    'name'                  => $user->name,
                    'last_name'             => $user->lastname,
                    'full_name'             => trim("{$user->name} {$user->lastname}"),
                    'phone'                 => $customer->phone ?: $user->phone,
                    'rfc'                   => $customer->rfc ?: $user->rfc,
                    'email'                 => $customer->email ?: $user->email,
                    'street'                => $customer->street,
                    'colonia'               => $customer->colonia,
                    'city'                  => $customer->city,
                    'state'                 => $customer->state,
                    'zip'                   => $customer->zip,
                    'delivery_references'   => $customer->delivery_references,
                    'delivery_instructions' => $customer->delivery_instructions,
                    'delivery_lat'          => $customer->delivery_lat,
                    'delivery_lng'          => $customer->delivery_lng,
                    'delivery_maps_url'     => $customer->delivery_maps_url,
                    'legal_address'         => $customer->legal_address,
                ]
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error("Error al crear cliente desde POS: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los datos detallados de un cliente incluyendo dirección y coordenadas de entrega para el POS.
     */
    public function getCustomer($customerId)
    {
        $customer = \App\Models\Customer::with('user')->find($customerId);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'id'                    => $customer->id,
                'name'                  => $customer->name ?? $customer->user?->name ?? '',
                'lastname'              => $customer->lastname ?? $customer->user?->lastname ?? '',
                'full_name'             => $customer->full_name,
                'phone'                 => $customer->phone ?? $customer->user?->phone ?? '',
                'email'                 => $customer->email ?? $customer->user?->email ?? '',
                'rfc'                   => $customer->rfc ?? '',
                'business_name'         => $customer->business_name ?? '',
                'street'                => $customer->street ?? '',
                'colonia'               => $customer->colonia ?? '',
                'city'                  => $customer->city ?? '',
                'state'                 => $customer->state ?? '',
                'zip'                   => $customer->zip ?? '',
                'delivery_references'   => $customer->delivery_references ?? '',
                'delivery_instructions' => $customer->delivery_instructions ?? '',
                'delivery_lat'          => $customer->delivery_lat ? (float)$customer->delivery_lat : null,
                'delivery_lng'          => $customer->delivery_lng ? (float)$customer->delivery_lng : null,
                'delivery_maps_url'     => $customer->delivery_maps_url ?? '',
                'legal_address'         => $customer->legal_address ?? '',
            ]
        ]);
    }

    /**
     * Actualiza el perfil de entrega y coordenadas de un cliente desde el POS.
     */
    public function updateCustomerDelivery(Request $request, $customerId)
    {
        $customer = \App\Models\Customer::find($customerId);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        $validated = $request->validate([
            'street'                => 'nullable|string|max:255',
            'colonia'               => 'nullable|string|max:255',
            'city'                  => 'nullable|string|max:100',
            'state'                 => 'nullable|string|max:100',
            'zip'                   => 'nullable|string|max:20',
            'delivery_references'   => 'nullable|string|max:1000',
            'delivery_instructions' => 'nullable|string|max:1000',
            'delivery_lat'          => 'nullable|numeric',
            'delivery_lng'          => 'nullable|numeric',
            'delivery_maps_url'     => 'nullable|string|max:500',
        ]);

        $latVal = $request->filled('delivery_lat') ? (float)$request->input('delivery_lat') : null;
        $lngVal = $request->filled('delivery_lng') ? (float)$request->input('delivery_lng') : null;
        $mapsUrl = $request->input('delivery_maps_url');
        if (empty($mapsUrl) && $latVal && $lngVal) {
            $mapsUrl = "https://www.google.com/maps?q={$latVal},{$lngVal}";
        }

        $validated['delivery_lat'] = $latVal;
        $validated['delivery_lng'] = $lngVal;
        $validated['delivery_maps_url'] = $mapsUrl;

        $customer->update(array_filter($validated, fn($v) => !is_null($v)));
        \App\Models\Customer::flushCache();

        activity('customers')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->log("Domicilio y coordenadas de entrega actualizados desde el módulo de POS para el cliente #{$customer->id}.");

        return response()->json([
            'success' => true,
            'message' => 'Domicilio y geolocalización del cliente actualizados.',
            'customer' => $customer
        ]);
    }

    private function formatVariantsForPos($variants, $storeId)
    {
        return $variants->map(function ($v) use ($storeId) {
            $imagePath = $v->product->files->first() ? asset('storage/' . $v->product->files->first()->path) : asset('build/images/products/img-1.png');
            $vCost = !is_null($v->cost) ? (float)$v->cost : null;
            $pricing = $this->pricingService->calculatePrice($v->product, $storeId, 'venta', 0, $vCost);
            $finalPrice = $pricing['unit_price_final'];

            if ($v->price) {
                $taxRate = $pricing['tax_rate'] ?? 16;
                $taxIncluded = $pricing['tax_included'] ?? false;
                if ($taxIncluded) {
                    $finalPrice = (float)$v->price;
                } else {
                    $finalPrice = (float)$v->price * (1 + ($taxRate / 100));
                }
            }

            $stockData = $this->getStockByWarehouseType($storeId, $v->product_id, $v->id);
            $isVirtual = (bool) ($v->product->isVirtual() || $v->product->isService());
            $isWildcard = (bool) $v->product->isWildcard();

            return [
                'id'               => $v->product_id,
                'variant_id'       => $v->id,
                'name'             => $v->product->name . ($v->name ? ' - ' . $v->name : ''),
                'sku'              => $v->sku,
                'price'            => round($finalPrice, 2),
                'stock'            => $isVirtual ? 9999 : $stockData['total'],
                'stock_warehouse'  => $stockData['warehouse'],
                'stock_exhibition' => $stockData['exhibition'],
                'only_exhibition'  => $stockData['only_exhibition'],
                'image'            => $imagePath,
                'is_virtual'       => $isVirtual,
                'is_wildcard'      => $isWildcard,
                'type'             => $v->product->type,
            ];
        });
    }

    /**
     * Obtiene el stock disponible desglosado por tipo de bodega para un producto/variante.
     * Retorna: total, warehouse (bodega principal), exhibition (piso de exhibición), only_exhibition.
     */
    private function getStockByWarehouseType(int $storeId, int $productId, ?int $variantId): array
    {
        $query = \App\Models\Inventory::with('warehouse')
            ->where('store_id', $storeId)
            ->where('product_id', $productId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        $records = $query->get();

        $stockWarehouse  = 0;
        $stockExhibition = 0;

        foreach ($records as $inv) {
            $available = max(0, $inv->quantity - $inv->reserved_quantity);
            $warehouseType = optional($inv->warehouse)->type;

            if ($warehouseType === 'exhibition') {
                $stockExhibition += $available;
            } else {
                // Sin bodega asignada o type === 'warehouse'
                $stockWarehouse += $available;
            }
        }

        $total = $stockWarehouse + $stockExhibition;

        return [
            'total'           => $total,
            'warehouse'       => $stockWarehouse,
            'exhibition'      => $stockExhibition,
            'only_exhibition' => ($total > 0 && $stockWarehouse === 0),
        ];
    }

    /**
     * Crea un Pedido Especial (backorder) desde el POS cuando no hay stock.
     */
    public function createSpecialOrder(Request $request)
    {
        $validated = $request->validate([
            'product_id'              => 'required|exists:products,id',
            'product_variant_id'      => 'nullable|exists:product_variants,id',
            'customer_id'             => 'required|exists:customers,id',
            'quantity'                => 'required|integer|min:1',
            'unit_price'              => 'required|numeric|min:0',
            'unit_cost'               => 'nullable|numeric|min:0',
            'custom_name'             => 'nullable|string|max:255',
            'amount_paid'             => 'required|numeric|min:0',
            'payment_method_id'       => 'required_if:amount_paid,>0|nullable|exists:payment_methods,id',
            'payment_terminal_id'     => 'nullable|exists:payment_terminals,id',
            'notes'                   => 'nullable|string|max:1000',
            'expected_delivery_date'  => 'nullable|date',
            'store_id'                => 'required|exists:stores,id',
            // Campos de entrega a domicilio opcionales
            'is_delivery'             => 'nullable|boolean',
            'delivery_recipient_name' => 'nullable|string|max:255',
            'delivery_phone'          => 'nullable|string|max:30',
            'delivery_address'        => 'nullable|string',
            'delivery_references'     => 'nullable|string',
            'delivery_lat'            => 'nullable|numeric',
            'delivery_lng'            => 'nullable|numeric',
            'delivery_maps_url'       => 'nullable|string|max:500',
            'delivery_date'           => 'nullable|date',
            'delivery_time'           => 'nullable|string|max:20',
            'delivery_time_slot'      => 'nullable|string|max:50',
            'delivery_instructions'   => 'nullable|string|max:500',
            'delivery_cost'           => 'nullable|numeric|min:0',
            'installation_cost'       => 'nullable|numeric|min:0',
        ]);

        try {
            $isDelivery = !empty($validated['is_delivery']);
            $deliveryCost = $isDelivery && !empty($validated['delivery_cost']) ? (float)$validated['delivery_cost'] : 0.0;
            $installationCost = $isDelivery && !empty($validated['installation_cost']) ? (float)$validated['installation_cost'] : 0.0;
            $itemsTotal = (float)$validated['unit_price'] * (int)$validated['quantity'];
            $total = $itemsTotal + $deliveryCost + $installationCost;

            $minDeposit = round($total * 0.20, 2);
            $amountPaid = (float)($validated['amount_paid'] ?? 0);

            if ($amountPaid < ($minDeposit - 0.01)) {
                return response()->json([
                    'success' => false,
                    'message' => "El anticipo ($" . number_format($amountPaid, 2) . ") es inferior al mínimo requerido del 20% ($" . number_format($minDeposit, 2) . ").",
                ], 422);
            }

            $specialOrder = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $total, $amountPaid, $isDelivery, $deliveryCost, $installationCost) {

                $orderData = [
                    'folio'                  => \App\Models\SpecialOrder::generateFolio(),
                    'store_id'               => $validated['store_id'],
                    'customer_id'            => $validated['customer_id'],
                    'user_id'                => auth()->id(),
                    'status'                 => 'pending',
                    'notes'                  => $validated['notes'] ?? null,
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? ($isDelivery ? ($validated['delivery_date'] ?? null) : null),
                    'total'                  => $total,
                ];

                if ($isDelivery) {
                    $orderData['is_delivery'] = true;
                    $orderData['delivery_recipient_name'] = $validated['delivery_recipient_name'] ?? null;
                    $orderData['delivery_phone'] = $validated['delivery_phone'] ?? null;
                    $orderData['delivery_address'] = $validated['delivery_address'] ?? null;
                    $orderData['delivery_references'] = $validated['delivery_references'] ?? null;
                    $orderData['delivery_lat'] = !empty($validated['delivery_lat']) ? (float)$validated['delivery_lat'] : null;
                    $orderData['delivery_lng'] = !empty($validated['delivery_lng']) ? (float)$validated['delivery_lng'] : null;
                    $orderData['delivery_maps_url'] = $validated['delivery_maps_url'] ?? null;
                    $orderData['delivery_date'] = $validated['delivery_date'] ?? null;
                    $orderData['delivery_time'] = $validated['delivery_time'] ?? null;
                    $orderData['delivery_time_slot'] = $validated['delivery_time_slot'] ?? null;
                    $orderData['delivery_instructions'] = $validated['delivery_instructions'] ?? null;
                    $orderData['delivery_cost'] = $deliveryCost;
                    $orderData['installation_cost'] = $installationCost;
                    $orderData['delivery_status'] = 'pendiente';
                }

                $order = \App\Models\SpecialOrder::create($orderData);

                $itemNotes = !empty($validated['custom_name'])
                    ? ($validated['custom_name'] . (!empty($validated['notes']) ? " — " . $validated['notes'] : ''))
                    : ($validated['notes'] ?? null);

                $soItem = $order->items()->create([
                    'product_id'         => $validated['product_id'],
                    'product_variant_id' => $validated['product_variant_id'] ?? null,
                    'quantity'           => $validated['quantity'],
                    'quantity_received'  => 0,
                    'unit_price'         => $validated['unit_price'],
                    'notes'              => $itemNotes,
                ]);

                // Registrar anticipo si hay un monto
                if ($amountPaid > 0 && !empty($validated['payment_method_id'])) {
                    $activeShift = \App\Models\CashShift::activeShift($validated['store_id'], auth()->id())
                        ?? \App\Models\CashShift::activeShift($validated['store_id']);
                    $order->payments()->create([
                        'cash_shift_id'       => $activeShift?->id,
                        'payment_method_id'   => $validated['payment_method_id'],
                        'payment_terminal_id' => $validated['payment_terminal_id'] ?? null,
                        'user_id'             => auth()->id(),
                        'amount'              => $amountPaid,
                        'notes'               => 'Anticipo inicial',
                    ]);
                }

                // ── Disparo Automático: Jerarquía Inteligente de Proveedor y Costo ──
                $product = \App\Models\Product::with(['brand'])->find($validated['product_id']);

                // 1. Jerarquía de Proveedor: Se respeta ÚNICAMENTE si el producto tiene un Proveedor Principal asignado explícitamente en catálogo.
                // Si es un producto personalizado, comodín o sin proveedor principal, NO se asigna proveedor automático para evitar fusiones indeseadas.
                $supplierId = null;

                if ($product && !($product->is_wildcard ?? false) && ($product->type ?? '') !== 'personalizado') {
                    $supplierId = $product->supplier_id;
                }

                // 2. Jerarquía de Costo: unit_cost enviado -> base_cost -> Último costo de compra -> Estimado
                $isCostUnverified = false;

                if (!empty($validated['unit_cost']) && (float)$validated['unit_cost'] > 0) {
                    $itemCost = (float) $validated['unit_cost'];
                } else {
                    $itemCost = (float)($product?->base_cost ?? 0);
                    if ($itemCost <= 0) {
                        $lastCost = \App\Models\PurchaseOrderItem::where('product_id', $product->id)
                            ->where('cost', '>', 0)
                            ->latest()
                            ->value('cost');
                        if ($lastCost && $lastCost > 0) {
                            $itemCost = (float) $lastCost;
                        } else {
                            $itemCost = round($validated['unit_price'] * 0.7, 2);
                            $isCostUnverified = true;
                        }
                    }
                }

                $poTotal = $itemCost * $validated['quantity'];
                $notes   = "Generada automáticamente por Pedido Especial {$order->folio}.";
                if ($itemNotes) {
                    $notes .= " Especificación: {$itemNotes}.";
                }
                if (!$supplierId) {
                    $notes .= " ⚠️ ATENCIÓN: Pendiente de Asignar Proveedor por Compras.";
                }
                if ($isCostUnverified) {
                    $notes .= " ⚠️ ATENCIÓN: Costo estimado ($" . number_format($itemCost, 2) . ") pendiente de verificación.";
                }

                // Si el producto TIENE proveedor determinado, buscar o unificar con OC borrador de ese proveedor.
                // Si el producto NO TIENE proveedor (null), crear una OC independiente sin proveedor (supplier_id = null) sin fusionarla.
                $existingPo = null;

                if ($supplierId) {
                    $existingPo = \App\Models\PurchaseOrder::where('store_id', $validated['store_id'])
                        ->where('supplier_id', $supplierId)
                        ->where('status', 'pendiente')
                        ->latest()
                        ->first();
                }

                $poItemNotes = $itemNotes ? ("Mueble a Medida: {$itemNotes} — Encargo {$order->folio}") : "Encargo {$order->folio}";

                if ($existingPo) {
                    $po = $existingPo;

                    // Para productos personalizados, solo coinciden si comparten exactamente las MISMAS especificaciones Y costo unitario
                    $isWildcard = ($product && (!empty($product->is_wildcard) || ($product->type ?? '') === 'personalizado'));

                    if ($isWildcard) {
                        $existingItem = $po->items()
                            ->where('product_id', $validated['product_id'])
                            ->where('cost', $itemCost)
                            ->where('notes', $poItemNotes)
                            ->first();
                    } else {
                        $existingItem = $po->items()
                            ->where('product_id', $validated['product_id'])
                            ->where('product_variant_id', $validated['product_variant_id'] ?? null)
                            ->first();
                    }

                    if ($existingItem) {
                        $newQty   = $existingItem->quantity_ordered + $validated['quantity'];
                        $newGross = $newQty * $itemCost;
                        $existingItem->update([
                            'quantity_ordered' => $newQty,
                            'cost'             => $itemCost,
                            'gross_amount'     => $newGross,
                            'net_amount'       => $newGross,
                        ]);
                    } else {
                        $po->items()->create([
                            'product_id'         => $validated['product_id'],
                            'product_variant_id' => $validated['product_variant_id'] ?? null,
                            'quantity_ordered'   => $validated['quantity'],
                            'quantity_received'  => 0,
                            'cost'               => $itemCost,
                            'gross_amount'       => $poTotal,
                            'net_amount'         => $poTotal,
                            'notes'              => $poItemNotes,
                        ]);
                    }

                    // Recalcular montos totales de la OC borrador y actualizar notas
                    $totalGross = (float) $po->items()->sum('gross_amount');
                    $poNotes    = $po->notes ?? '';
                    if (!str_contains($poNotes, $order->folio)) {
                        $poNotes .= " / Incluye encargo {$order->folio}";
                    }

                    $po->update([
                        'gross_amount' => $totalGross,
                        'net_amount'   => $totalGross,
                        'total'        => $totalGross,
                        'notes'        => trim($poNotes),
                    ]);

                } else {
                    // Crear una nueva OC borrador independiente (supplier_id = null si no tiene proveedor)
                    $po = \App\Models\PurchaseOrder::create([
                        'folio'          => \App\Models\PurchaseOrder::generateFolio(),
                        'store_id'       => $validated['store_id'],
                        'supplier_id'    => $supplierId ?: null,
                        'created_by'     => auth()->id() ?? 1,
                        'status'         => 'pendiente',
                        'payment_status' => 'pendiente',
                        'unit_status'    => 'incompleta',
                        'gross_amount'   => $poTotal,
                        'net_amount'     => $poTotal,
                        'total'          => $poTotal,
                        'notes'          => $notes,
                    ]);

                    $po->items()->create([
                        'product_id'         => $validated['product_id'],
                        'product_variant_id' => $validated['product_variant_id'] ?? null,
                        'quantity_ordered'   => $validated['quantity'],
                        'quantity_received'  => 0,
                        'cost'               => $itemCost,
                        'gross_amount'       => $poTotal,
                        'net_amount'         => $poTotal,
                        'notes'              => $poItemNotes,
                    ]);
                }

                $soItem->update(['purchase_order_id' => $po->id]);
                \App\Models\PurchaseOrder::flushCache();

                return $order;
            });

            return response()->json([
                'success'            => true,
                'folio'              => $specialOrder->folio,
                'special_order_id'   => $specialOrder->id,
                'balance_due'        => $specialOrder->balanceDue(),
                'is_delivery'        => $specialOrder->hasDelivery(),
                'delivery_order_url' => $specialOrder->hasDelivery() ? route('special-orders.delivery-order', $specialOrder->id) : null,
                'message'            => "Pedido Especial {$specialOrder->folio} creado correctamente.",
            ]);

        } catch (\Throwable $e) {
            Log::error('Error al crear Pedido Especial: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el Pedido Especial: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guarda el estado actual del carrito del POS como una Cotización formal.
     */
    public function saveQuote(Request $request)
    {
        $items = $request->input('items', []);
        $customerId = $request->input('customer_id');
        $sellerId = $request->input('seller_id') ?: auth()->id();
        $storeId = $request->input('store_id') ?: current_store_id();
        if ((is_null($storeId) || $storeId === 'global' || !$storeId) && auth()->check()) {
            $storeId = auth()->user()->store_id;
        }
        if (is_null($storeId) || $storeId === 'global' || !$storeId) {
            $storeId = \App\Models\Store::where('status', true)->value('id');
        }
        if (is_null($storeId) || !$storeId) {
            return response()->json(['success' => false, 'message' => 'Debes seleccionar una sucursal activa en el selector superior.'], 422);
        }

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío.'], 422);
        }

        try {
            DB::beginTransaction();

            $validityDays = (int)\App\Models\Setting::get('quote_validity_days', 30, $storeId);
            $expiresAt = now()->addDays($validityDays);

            $quote = \App\Models\Quote::create([
                'folio' => \App\Models\Quote::generateFolio(),
                'store_id' => $storeId,
                'user_id' => auth()->id(),
                'seller_id' => $sellerId,
                'customer_id' => $customerId ?: null,
                'subtotal' => 0,
                'ieps_total' => 0,
                'iva_total' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'total' => 0,
                'status' => 'pendiente',
                'expires_at' => $expiresAt,
            ]);

            $total = 0;
            $subtotalVal = 0;
            $iepsTotalVal = 0;
            $ivaTotalVal = 0;
            $taxTotalVal = 0;
            $discountTotalVal = 0;

            foreach ($items as $item) {
                $productId = $item['id'] ?? null;
                $product = null;

                if (!empty($item['is_delivery_charge']) || ($item['sku'] ?? '') === 'SERV-FLETE') {
                    $product = Product::firstOrCreate(
                        ['sku' => 'SERV-FLETE'],
                        ['name' => 'Flete / Envío a Domicilio', 'slug' => 'servicio-flete-envio-domicilio', 'type' => 'service', 'status' => true, 'is_wildcard' => false, 'base_cost' => 0]
                    );
                } elseif (!empty($item['is_installation_charge']) || ($item['sku'] ?? '') === 'SERV-ARMADO') {
                    $product = Product::firstOrCreate(
                        ['sku' => 'SERV-ARMADO'],
                        ['name' => 'Servicio de Armado e Instalación', 'slug' => 'servicio-armado-instalacion', 'type' => 'service', 'status' => true, 'is_wildcard' => false, 'base_cost' => 0]
                    );
                } elseif ($productId) {
                    $product = Product::find($productId);
                }

                if (!$product) {
                    $product = Product::where('is_wildcard', true)->first() ?? Product::where('sku', 'CUSTOM-001')->first();
                }
                if (!$product) continue;

                $qty = (int)($item['quantity'] ?? 1);
                $uiPrice = isset($item['adjusted_price']) && is_numeric($item['adjusted_price']) 
                    ? (float)$item['adjusted_price'] 
                    : (float)($item['price'] ?? 0);

                $isNonPhysical = !$product->isPhysical() || !empty($item['is_delivery_charge']) || !empty($item['is_installation_charge']);
                $variant = !empty($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;
                $variantCost = ($variant && !is_null($variant->cost)) ? (float)$variant->cost : null;

                $baseOriginalPrice = $uiPrice;
                if (!$isNonPhysical && !$product->is_wildcard) {
                    $pricing = $this->pricingService->calculatePrice($product, $storeId, 'venta', 0, $variantCost);
                    $baseOriginalPrice = $pricing['unit_price_final'];
                    if ($variant && $variant->price) {
                        $tRate = $pricing['tax_rate'] ?? 16;
                        $tInc = $pricing['tax_included'] ?? false;
                        $vPrice = (float)$variant->price;
                        $baseOriginalPrice = $tInc ? $vPrice : $vPrice * (1 + ($tRate / 100));
                    }
                }
                if (isset($item['original_price']) && is_numeric($item['original_price']) && (float)$item['original_price'] > 0) {
                    $baseOriginalPrice = (float)$item['original_price'];
                }

                $lineDiscount = max(0, $baseOriginalPrice - $uiPrice) * $qty;
                $discountTotalVal += $lineDiscount;
                
                $taxIvaRate = (float)\App\Models\Setting::get('tax_iva_rate', 16, $storeId);
                $taxIvaRateDecimal = $taxIvaRate / 100;
                $itemTotal = $uiPrice * $qty;
                $ivaBaseLine = $itemTotal / (1 + $taxIvaRateDecimal);
                $ivaLine = $itemTotal - $ivaBaseLine;
                $subtotalLine = $ivaBaseLine;

                $total += $itemTotal;
                $subtotalVal += $subtotalLine;
                $ivaTotalVal += $ivaLine;
                $taxTotalVal += $ivaLine;

                $itemNotes = ($product->is_wildcard && !empty($item['name']) && $item['name'] !== $product->name)
                    ? ($item['name'] . (!empty($item['notes']) ? " - " . $item['notes'] : ''))
                    : (!empty($item['custom_description'])
                        ? ($item['custom_description'] . (!empty($item['notes']) ? "\n" . $item['notes'] : ''))
                        : ($item['notes'] ?? null));

                \App\Models\QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $qty,
                    'base_cost' => $variantCost ?? ($product->base_cost ?? 0),
                    'unit_price_original' => round($baseOriginalPrice, 2),
                    'unit_price_final' => $uiPrice,
                    'tax_amount' => round($ivaLine, 2),
                    'ieps_amount' => 0,
                    'iva_amount' => round($ivaLine, 2),
                    'subtotal' => round($subtotalLine, 2),
                    'total' => round($itemTotal, 2),
                    'notes' => $itemNotes,
                ]);
            }

            // Procesar Cupón de Descuento si aplica
            $couponCode = $request->input('coupon_code');
            $couponId   = $request->input('coupon_id');
            $couponDiscount = 0;
            $appliedCoupon = null;

            if (($couponCode || $couponId) && \Illuminate\Support\Facades\Schema::hasTable('coupons')) {
                $appliedCoupon = \App\Models\Coupon::when($couponId, fn($q) => $q->where('id', $couponId))
                    ->when(!$couponId && $couponCode, fn($q) => $q->where('code', strtoupper(trim($couponCode))))
                    ->first();

                if ($appliedCoupon && $appliedCoupon->isValidFor($total, $quote->customer_id, $storeId)) {
                    $couponDiscount = $appliedCoupon->calculateDiscount($total);
                    $total = max(0, $total - $couponDiscount);
                }
            }

            // Datos de entrega si aplican
            $deliveryInput = $request->input('delivery', []);
            $deliveryUpdate = [
                'subtotal' => round($subtotalVal, 2),
                'ieps_total' => round($iepsTotalVal, 2),
                'iva_total' => round($ivaTotalVal, 2),
                'tax_total' => round($taxTotalVal, 2),
                'discount_total' => round($discountTotalVal, 2),
                'coupon_id' => $appliedCoupon?->id,
                'coupon_code' => $appliedCoupon?->code,
                'coupon_discount' => round($couponDiscount, 2),
                'total' => round($total, 2),
            ];

            if (!empty($deliveryInput['is_delivery'])) {
                $deliveryUpdate['is_delivery'] = true;
                $deliveryUpdate['delivery_recipient_name'] = $deliveryInput['recipient_name'] ?? null;
                $deliveryUpdate['delivery_phone'] = $deliveryInput['phone'] ?? null;
                $deliveryUpdate['delivery_address'] = $deliveryInput['address'] ?? null;
                $deliveryUpdate['delivery_references'] = $deliveryInput['references'] ?? null;
                $deliveryUpdate['delivery_lat'] = !empty($deliveryInput['lat']) ? (float)$deliveryInput['lat'] : null;
                $deliveryUpdate['delivery_lng'] = !empty($deliveryInput['lng']) ? (float)$deliveryInput['lng'] : null;
                $deliveryUpdate['delivery_maps_url'] = $deliveryInput['maps_url'] ?? null;
                $deliveryUpdate['delivery_date'] = !empty($deliveryInput['date']) ? $deliveryInput['date'] : null;
                $deliveryUpdate['delivery_time'] = !empty($deliveryInput['time']) ? $deliveryInput['time'] : null;
                $deliveryUpdate['delivery_time_slot'] = $deliveryInput['time_slot'] ?? null;
                $deliveryUpdate['delivery_instructions'] = $deliveryInput['instructions'] ?? null;
                $deliveryUpdate['delivery_cost'] = !empty($deliveryInput['delivery_cost']) ? (float)$deliveryInput['delivery_cost'] : 0;
                $deliveryUpdate['installation_cost'] = !empty($deliveryInput['installation_cost']) ? (float)$deliveryInput['installation_cost'] : 0;
            }

            $quote->update($deliveryUpdate);
            DB::commit();

            activity('pos')
                ->performedOn($quote)
                ->causedBy(auth()->user())
                ->log("Cotización guardada con folio {$quote->folio} por un total de $" . number_format($quote->total, 2));

            return response()->json([
                'success' => true,
                'message' => "Cotización {$quote->folio} guardada exitosamente.",
                'quote_id' => $quote->id,
                'folio' => $quote->folio,
                'print_url' => route('pos.quotes.print', $quote->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar cotización: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar cotización: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Compara los precios pactados en una cotización con los precios vigentes de catálogo.
     */
    public function compareQuotePricing(Request $request, Quote $quote)
    {
        if ($quote->status === 'cancelada') {
            return response()->json([
                'success' => false,
                'message' => "La cotización {$quote->folio} se encuentra cancelada y no puede cargarse al POS."
            ], 422);
        }

        if ($quote->status === 'convertida') {
            return response()->json([
                'success' => false,
                'message' => "La cotización {$quote->folio} ya fue convertida en venta previamente."
            ], 422);
        }

        $storeId = current_store_id();
        if (is_null($storeId) || $storeId === 'global' || !$storeId) {
            $storeId = $quote->store_id ?: (auth()->user()->store_id ?? \App\Models\Store::where('status', true)->value('id'));
        }

        $quote->load(['items.product', 'items.variant', 'customer', 'seller']);

        $discrepancies = [];
        $itemsPayload = [];

        foreach ($quote->items as $qItem) {
            $product = $qItem->product;
            if (!$product) continue;

            $isVirtual = (bool)($product->isVirtual() || $product->isService());
            $isWildcard = (bool)$product->is_wildcard;

            $variantCost = ($qItem->variant && !is_null($qItem->variant->cost)) ? (float)$qItem->variant->cost : null;
            $pricing = $this->pricingService->calculatePrice($product, $storeId, 'venta', 0, $variantCost);
            $currentCatalogPrice = $pricing['unit_price_final'];

            if ($qItem->variant && $qItem->variant->price) {
                $tRate = $pricing['tax_rate'] ?? 16;
                $tInc = $pricing['tax_included'] ?? false;
                $vPrice = (float)$qItem->variant->price;
                $currentCatalogPrice = $tInc ? $vPrice : $vPrice * (1 + ($tRate / 100));
            }

            if ($isVirtual || $isWildcard) {
                $currentCatalogPrice = (float)$qItem->unit_price_final;
            }

            $quotedPrice = (float)$qItem->unit_price_final;
            $hasDifference = abs($currentCatalogPrice - $quotedPrice) > 0.05 && !$isWildcard && !$isVirtual;

            $displayName = $product->name;
            $itemNotes = $qItem->notes;

            if ($isWildcard && !empty($qItem->notes)) {
                if (str_contains($qItem->notes, ' - ')) {
                    [$customName, $restNotes] = explode(' - ', $qItem->notes, 2);
                    $displayName = trim($customName);
                    $itemNotes = trim($restNotes);
                } else {
                    $displayName = trim($qItem->notes);
                    $itemNotes = null;
                }
            } elseif ($qItem->variant && $qItem->variant->name) {
                $displayName = $product->name . ' - ' . $qItem->variant->name;
            }

            if ($hasDifference) {
                $discrepancies[] = [
                    'item_id' => $qItem->id,
                    'sku' => $qItem->variant ? $qItem->variant->sku : $product->sku,
                    'name' => $displayName,
                    'quoted_price' => $quotedPrice,
                    'current_price' => round($currentCatalogPrice, 2),
                    'difference' => round($currentCatalogPrice - $quotedPrice, 2),
                ];
            }

            $stockData = ($isVirtual || $isWildcard)
                ? ['total' => 9999, 'warehouse' => 9999, 'exhibition' => 0, 'only_exhibition' => false]
                : $this->getStockByWarehouseType($storeId, $product->id, $qItem->product_variant_id);

            $itemsPayload[] = [
                'id' => $product->id,
                'variant_id' => $qItem->product_variant_id,
                'sku' => $qItem->variant ? $qItem->variant->sku : $product->sku,
                'name' => $displayName,
                'price' => $quotedPrice,
                'original_price' => (float)($qItem->unit_price_original ?: $currentCatalogPrice),
                'adjusted_price' => $quotedPrice,
                'current_catalog_price' => round($currentCatalogPrice, 2),
                'price_type' => $hasDifference ? 'personalizado' : 'venta',
                'quantity' => (int)$qItem->quantity,
                'is_virtual' => $isVirtual,
                'is_wildcard' => $isWildcard,
                'is_delivery_charge' => ($product->sku === 'SERV-FLETE'),
                'is_installation_charge' => ($product->sku === 'SERV-ARMADO'),
                'notes' => $itemNotes,
                'stock' => ($isVirtual || $isWildcard) ? 9999 : $stockData['total'],
                'stock_warehouse' => $stockData['warehouse'],
                'stock_exhibition' => $stockData['exhibition'],
                'only_exhibition' => $stockData['only_exhibition'],
                'type' => $product->type,
            ];
        }

        $couponPayload = null;
        if ($quote->coupon_id || $quote->coupon_code) {
            $couponModel = ($quote->coupon_id && \Illuminate\Support\Facades\Schema::hasTable('coupons'))
                ? \App\Models\Coupon::withTrashed()->with('seller')->find($quote->coupon_id)
                : null;

            $ownerName = ($couponModel && $couponModel->seller)
                ? trim($couponModel->seller->name . ' ' . $couponModel->seller->lastname)
                : null;

            $ownerPct = (float)\App\Models\Setting::get('comm_coupon_owner_split_pct', 50.00, $storeId);
            $closingPct = (float)\App\Models\Setting::get('comm_closing_seller_split_pct', 50.00, $storeId);
            $sumPct = $ownerPct + $closingPct;
            if ($sumPct > 0) {
                $ownerPct = round(($ownerPct / $sumPct) * 100, 2);
                $closingPct = round(100.00 - $ownerPct, 2);
            }

            $isSplit = ($couponModel && $couponModel->seller_id && $quote->seller_id && (int)$couponModel->seller_id !== (int)$quote->seller_id);

            $couponPayload = [
                'id' => $couponModel ? $couponModel->id : $quote->coupon_id,
                'code' => $couponModel ? $couponModel->code : $quote->coupon_code,
                'name' => $couponModel ? $couponModel->name : $quote->coupon_code,
                'description' => $couponModel ? ($couponModel->description ?: $couponModel->name) : $quote->coupon_code,
                'discount_type' => $couponModel ? $couponModel->discount_type : 'fixed',
                'discount_value' => $couponModel ? (float)$couponModel->discount_value : (float)$quote->coupon_discount,
                'max_discount_amount' => ($couponModel && $couponModel->max_discount_amount) ? (float)$couponModel->max_discount_amount : null,
                'min_purchase' => $couponModel ? (float)$couponModel->min_purchase : 0.00,
                'discount' => (float)$quote->coupon_discount,
                'discount_amount' => (float)$quote->coupon_discount,
                'seller_id' => $couponModel ? $couponModel->seller_id : null,
                'seller_name' => $ownerName,
                'customer_id' => $couponModel ? $couponModel->customer_id : null,
                'store_id' => $couponModel ? $couponModel->store_id : null,
                'commission_info' => ($couponModel && $couponModel->seller_id) ? [
                    'has_commission' => true,
                    'is_split' => $isSplit,
                    'owner_seller_id' => $couponModel->seller_id,
                    'owner_seller_name' => $ownerName,
                    'owner_split_pct' => $isSplit ? $ownerPct : 100.00,
                    'closing_split_pct' => $isSplit ? $closingPct : 0.00,
                    'default_owner_split_pct' => $ownerPct,
                    'default_closing_split_pct' => $closingPct,
                    'split_summary' => $isSplit
                        ? "{$ownerPct}% Prospección / {$closingPct}% Cierre"
                        : "100% Venta Directa",
                ] : null,
            ];
        }

        $customerName = $quote->customer
            ? trim($quote->customer->name . ' ' . $quote->customer->lastname) . ($quote->customer->business_name ? " ({$quote->customer->business_name})" : '')
            : 'Público en General';

        $sellerName = $quote->seller
            ? trim($quote->seller->name . ' ' . $quote->seller->lastname)
            : null;

        return response()->json([
            'success' => true,
            'quote_id' => $quote->id,
            'folio' => $quote->folio ?: ('COT-' . $quote->id),
            'customer_id' => $quote->customer_id,
            'customer_name' => $customerName,
            'seller_id' => $quote->seller_id,
            'seller_name' => $sellerName,
            'quote_total' => (float)$quote->total,
            'is_expired' => $quote->expires_at && $quote->expires_at->isPast(),
            'expires_at' => $quote->expires_at?->format('d/m/Y'),
            'has_discrepancies' => count($discrepancies) > 0,
            'discrepancies' => $discrepancies,
            'items' => $itemsPayload,
            'coupon' => $couponPayload,
            'delivery' => $quote->is_delivery ? [
                'is_delivery' => true,
                'recipient_name' => $quote->delivery_recipient_name,
                'phone' => $quote->delivery_phone,
                'address' => $quote->delivery_address,
                'references' => $quote->delivery_references,
                'lat' => $quote->delivery_lat,
                'lng' => $quote->delivery_lng,
                'maps_url' => $quote->delivery_maps_url,
                'date' => $quote->delivery_date?->format('Y-m-d'),
                'time' => $quote->delivery_time ? substr($quote->delivery_time, 0, 5) : null,
                'time_slot' => $quote->delivery_time_slot,
                'instructions' => $quote->delivery_instructions,
                'delivery_cost' => (float)$quote->delivery_cost,
                'installation_cost' => (float)$quote->installation_cost,
            ] : null,
        ]);
    }

    /**
     * Valida un cupón de descuento en tiempo real para el carrito del POS.
     */
    public function validateCoupon(Request $request)
    {
        $code = strtoupper(trim($request->input('code', '')));
        if (!$code) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Por favor ingresa un código de cupón.'
            ], 422);
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('coupons')) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'El módulo de cupones no está disponible en este momento.'
            ], 503);
        }

        $coupon = \App\Models\Coupon::with('seller', 'customer', 'store')
            ->where('code', $code)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => "El cupón '{$code}' no existe o no es válido."
            ], 404);
        }

        $subtotal = (float)($request->input('subtotal') ?? $request->input('cart_total', 0));
        $customerId = $request->input('customer_id') ? (int)$request->input('customer_id') : null;
        $storeId = $request->input('store_id') ? (int)$request->input('store_id') : current_store_id();

        $errorMsg = null;
        if (!$coupon->isValidFor($subtotal, $customerId, $storeId, $errorMsg)) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => $errorMsg ?: "El cupón no cumple con las condiciones para ser aplicado."
            ], 422);
        }

        $discountAmount = $coupon->calculateDiscount($subtotal);
        $closingSellerId = $request->input('seller_id') ? (int)$request->input('seller_id') : auth()->id();

        // Calcular preview de comisiones según la Matriz de Reparto
        $ownerPct = (float)\App\Models\Setting::get('comm_coupon_owner_split_pct', 50.00, $storeId);
        $closingPct = (float)\App\Models\Setting::get('comm_closing_seller_split_pct', 50.00, $storeId);
        $sum = $ownerPct + $closingPct;
        if ($sum > 0) {
            $ownerPct = round(($ownerPct / $sum) * 100, 2);
            $closingPct = round(100.00 - $ownerPct, 2);
        }

        $isSplit = ($coupon->seller_id && $closingSellerId && (int)$coupon->seller_id !== (int)$closingSellerId);
        $ownerName = $coupon->seller ? trim($coupon->seller->name . ' ' . $coupon->seller->lastname) : null;

        $splitPreview = null;
        $commissionSplit = null;

        if ($coupon->seller_id) {
            $commissionSplit = [
                'has_commission' => true,
                'is_split' => (bool)$isSplit,
                'owner_seller_id' => $coupon->seller_id,
                'owner_seller_name' => $ownerName ?: 'Vendedor Asociado',
                'owner_split_pct' => $isSplit ? $ownerPct : 100.00,
                'closing_split_pct' => $isSplit ? $closingPct : 0.00,
                'default_owner_split_pct' => $ownerPct,
                'default_closing_split_pct' => $closingPct,
                'split_summary' => $isSplit
                    ? "{$ownerPct}% Prospección / {$closingPct}% Cierre"
                    : "100% Venta Directa",
                'message' => $isSplit
                    ? "Cupón promovido por {$ownerName}. La comisión se repartirá {$ownerPct}% (prospección) y {$closingPct}% (cierre en caja)."
                    : "Cupón promovido por {$ownerName} (100% comisión directa).",
            ];

            if ($isSplit) {
                $splitPreview = [
                    'is_split' => true,
                    'coupon_owner_name' => $ownerName ?: 'Vendedor Asociado',
                    'owner_split_pct' => $ownerPct,
                    'closing_split_pct' => $closingPct,
                    'message' => $commissionSplit['message'],
                ];
            }
        }

        $label = $coupon->discount_type === 'percentage'
            ? "{$coupon->discount_value}% de descuento"
            : "$" . number_format($coupon->discount_value, 2) . " de descuento fijo";

        return response()->json([
            'success' => true,
            'valid' => true,
            'discount_amount' => round($discountAmount, 2),
            'message' => "¡Cupón '{$coupon->code}' aplicado con éxito! ({$label})",
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->description ?: $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float)$coupon->discount_value,
                'max_discount_amount' => $coupon->max_discount_amount ? (float)$coupon->max_discount_amount : null,
                'min_purchase' => (float)$coupon->min_purchase,
                'discount_amount' => round($discountAmount, 2),
                'seller_id' => $coupon->seller_id,
                'seller_name' => $ownerName,
                'customer_id' => $coupon->customer_id,
                'store_id' => $coupon->store_id,
            ],
            'split_preview' => $splitPreview,
            'commission_split' => $commissionSplit,
        ]);
    }
}
