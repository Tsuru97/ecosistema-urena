<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\PaymentTerminal;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Store;
use App\Models\User;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:acceder-pos')->only(['index']);
        $this->middleware('permission:ver-ventas')->only(['sales', 'show']);
        $this->middleware('permission:registrar-abonos-ventas')->only(['addPayment']);
        $this->middleware('permission:anular-ventas')->only(['cancel']);
        $this->middleware('permission:ver-cotizaciones')->only(['quotes']);
        $this->middleware('permission:imprimir-ventas')->only([
            'printSale', 'printSaleDocument', 'printPaymentHistory',
            'printAnticipoDocument', 'printPaymentReceipt', 'printDeliveryOrder'
        ]);
        $this->middleware('permission:imprimir-cotizaciones')->only(['printQuoteDocument']);
    }

    /**
     * Muestra la interfaz principal de la caja (Punto de Venta).
     */
    public function index()
    {
        Sale::ensureFoliosStandardized();

        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $paymentTerminals = PaymentTerminal::with('paymentMethods')->where('is_active', true)->get();
        $defaultCustomers = Customer::rememberWithTags('pos.default_customers', function () {
            return Customer::with('user')
                ->where('status', true)
                ->get()
                ->sortBy('full_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        });

        // Obtener el store_id activo del contexto de la sesión/topbar
        $activeStoreId = current_store_id();
        $isGlobalAdmin = is_null($activeStoreId) || $activeStoreId === 'global';
        $currentStore = !$isGlobalAdmin ? Store::find($activeStoreId) : null;
        $taxIvaRate = (float)\App\Models\Setting::get('tax_iva_rate', 16, $activeStoreId);

        $fleteProduct = \App\Models\Product::firstOrCreate(
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
        $armadoProduct = \App\Models\Product::firstOrCreate(
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

        $sellerQuery = User::where('status', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Vendedor', 'Gerente', 'Dirección', 'Super Admin']);
            });

        if ($activeStoreId && $activeStoreId !== 'global') {
            $sellerQuery->whereHas('stores', function ($sq) use ($activeStoreId) {
                $sq->where('stores.id', $activeStoreId);
            });
        }

        $sellers = $sellerQuery->orderBy('name')->get(['id', 'name', 'lastname', 'email']);

        return view('admin.pos.index', compact(
            'paymentMethods',
            'paymentTerminals',
            'defaultCustomers',
            'isGlobalAdmin',
            'activeStoreId',
            'currentStore',
            'taxIvaRate',
            'fleteProduct',
            'armadoProduct',
            'sellers'
        ));
    }

    /**
     * Muestra el historial de Ventas y Apartados con KPIs y filtros
     */
    public function sales(Request $request)
    {
        Sale::ensureFoliosStandardized();

        $query = Sale::with(['customer', 'store', 'seller', 'cashier', 'payments.paymentMethod', 'quote.discountAuthorization.authorizer'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                Sale::applySmartFolioSearch($q, $search, 'ticket_folio');
                $q->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                  ->orWhere('coupon_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->paginate(15)->withQueryString();
        $sellers = User::where('status', true)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['Vendedor', 'Gerente', 'Dirección', 'Super Admin']))
            ->orderBy('name')
            ->get(['id', 'name', 'lastname']);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $paymentTerminals = PaymentTerminal::where('is_active', true)->get();

        // KPIs calculados
        $kpis = [
            'total_today' => (float)Sale::whereDate('created_at', today())->where('status', '!=', 'cancelada')->sum('total'),
            'collected_today' => (float)Payment::whereDate('created_at', today())->sum('amount'),
            'layaways_count' => Sale::where('status', 'apartado')->where('balance', '>', 0)->count(),
            'layaways_balance' => (float)Sale::where('status', 'apartado')->where('balance', '>', 0)->sum('balance'),
            'cancelled_count' => Sale::where('status', 'cancelada')->count(),
        ];

        return view('admin.pos.sales', compact('sales', 'sellers', 'paymentMethods', 'paymentTerminals', 'kpis'));
    }

    /**
     * Retorna los detalles completos de una venta para el modal interactivo
     */
    public function show(Sale $sale)
    {
        $sale->load([
            'customer',
            'store',
            'seller',
            'cashier',
            'deliveredByUser',
            'items.product',
            'items.variant',
            'payments.paymentMethod',
            'payments.terminal',
            'payments.cashier',
            'quote.discountAuthorization.authorizer'
        ]);

        return response()->json([
            'success' => true,
            'sale' => $sale,
            'delivery_meta' => $sale->hasDelivery() ? [
                'delivery_status'         => $sale->delivery_status ?: 'pendiente',
                'delivery_date_input'     => $sale->delivery_date ? $sale->delivery_date->format('Y-m-d') : '',
                'delivery_date_formatted' => $sale->delivery_date ? $sale->delivery_date->format('d/m/Y') : 'Por programar',
                'delivery_time_input'     => $sale->delivery_time ? substr($sale->delivery_time, 0, 5) : '11:00',
                'delivery_time_formatted' => $sale->formatted_delivery_time ?: 'Por definir',
                'has_evidence'            => !empty($sale->delivery_evidence_path),
                'evidence_url'            => $sale->delivery_evidence_url,
                'is_evidence_pdf'         => $sale->isEvidencePdf(),
                'delivered_at_formatted'  => $sale->delivered_at ? $sale->delivered_at->format('d/m/Y h:i A') : null,
                'delivered_by_name'       => $sale->deliveredByUser ? trim($sale->deliveredByUser->name . ' ' . $sale->deliveredByUser->lastname) : null,
            ] : null,
            'print_url' => ($sale->status === 'apartado' || (float)$sale->balance > 0.01) 
                ? route('pos.sales.print-anticipo', $sale->id) 
                : route('pos.sales.print-sale', $sale->id),
            'print_payments_url' => route('pos.sales.payments.print-history', $sale->id),
            'delivery_order_url' => $sale->hasDelivery() ? route('admin.pos.delivery-order', $sale->id) : null
        ]);
    }

    /**
     * Registra un nuevo abono a un apartado pendiente
     */
    public function addPayment(Request $request, Sale $sale)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_terminal_id' => 'nullable|exists:payment_terminals,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($sale->status === 'cancelada') {
            return response()->json(['success' => false, 'message' => 'No se pueden registrar abonos a una venta cancelada.'], 422);
        }

        if ($sale->balance <= 0.01) {
            return response()->json(['success' => false, 'message' => 'Esta venta ya se encuentra totalmente liquidada.'], 422);
        }

        $amount = (float)$request->amount;
        if ($amount > ($sale->balance + 0.05)) {
            return response()->json([
                'success' => false,
                'message' => 'El monto del abono ($' . number_format($amount, 2) . ') no puede superar el saldo pendiente ($' . number_format($sale->balance, 2) . ').'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Turno de caja activo para que alimente el Corte X / Z en tiempo real
            $activeShift = \App\Models\CashShift::activeShift($sale->store_id, auth()->id());
            $shiftId = $activeShift?->id;

            $payment = $sale->payments()->create([
                'cash_shift_id' => $shiftId,
                'user_id' => auth()->id(),
                'amount' => $amount,
                'payment_method_id' => $request->payment_method_id,
                'payment_terminal_id' => $request->payment_terminal_id,
                'reference' => $request->reference,
                'notes' => $request->notes,
            ]);

            // Recalcular saldo acumulado
            $totalPaid = (float)$sale->payments()->sum('amount');
            $newBalance = max(0, round((float)$sale->total - $totalPaid, 2));
            $sale->amount_paid = $totalPaid;
            $sale->balance = $newBalance;

            $liquidated = false;
            if ($newBalance <= 0.01) {
                $sale->status = 'pagada';
                $sale->balance = 0;
                $liquidated = true;

                // Liberación de reserva de inventario en bodega (salida física definitiva)
                foreach ($sale->items as $item) {
                    if ($item->product && $item->product->isPhysical() && !$item->product->is_wildcard && !$item->product->isService()) {
                        Inventory::applyMovement(
                            $sale->store_id,
                            $item->product_id,
                            'liberacion_apartado',
                            $item->quantity,
                            auth()->id(),
                            'Liquidación de apartado ticket: ' . $sale->ticket_folio,
                            'App\Models\Sale',
                            $sale->id,
                            $item->product_variant_id
                        );
                    }
                }

                // Recompensa de crédito al cliente si está configurado el porcentaje
                if ($sale->customer) {
                    $rewardPct = (float)\App\Models\Setting::get('customer_credit_reward_percentage', 0, $sale->store_id);
                    if ($rewardPct > 0) {
                        $sale->customer->rewardCreditFromSale($sale, $rewardPct);
                    }
                }
            }

            $sale->save();

            // Liberar la línea de crédito ocupada por este apartado
            if ($sale->customer) {
                $sale->customer->releaseCredit($amount);
            }

            // Liberar comisiones de la venta si el apartado quedó liquidado
            if ($liquidated) {
                app(\App\Services\CommissionService::class)->onSaleLiquidated($sale);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $liquidated ? '¡Apartado completamente liquidado! El inventario ha sido liberado para entrega.' : 'Abono de $' . number_format($amount, 2) . ' registrado con éxito.',
                'new_balance' => $sale->balance,
                'new_status' => $sale->status,
                'payment_id' => $payment->id,
                'print_url' => route('pos.sales.payment.print', [$sale->id, $payment->id]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al procesar el abono: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancela una venta o apartado liberando existencias e historial crediticio
     */
    public function cancel(Request $request, Sale $sale)
    {
        if ($sale->status === 'cancelada') {
            return response()->json(['success' => false, 'message' => 'Esta venta ya se encuentra cancelada.'], 422);
        }

        DB::beginTransaction();
        try {
            if ($sale->isLayaway()) {
                // Apartado: Liberar cantidades reservadas sin afectar la cantidad física
                foreach ($sale->items as $item) {
                    if ($item->product && $item->product->isPhysical() && !$item->product->is_wildcard && !$item->product->isService()) {
                        $inv = Inventory::where('store_id', $sale->store_id)
                            ->where('product_id', $item->product_id)
                            ->when($item->product_variant_id, fn($q) => $q->where('product_variant_id', $item->product_variant_id), fn($q) => $q->whereNull('product_variant_id'))
                            ->first();
                        if ($inv) {
                            $inv->reserved_quantity = max(0, $inv->reserved_quantity - $item->quantity);
                            $inv->save();

                            try {
                                InventoryMovement::create([
                                    'store_id' => $sale->store_id,
                                    'warehouse_id' => $inv->warehouse_id,
                                    'product_id' => $item->product_id,
                                    'product_variant_id' => $item->product_variant_id,
                                    'type' => 'ajuste',
                                    'direction' => 'in',
                                    'quantity' => $item->quantity,
                                    'quantity_before' => $inv->quantity,
                                    'quantity_after' => $inv->quantity,
                                    'reference_type' => 'App\Models\Sale',
                                    'reference_id' => $sale->id,
                                    'notes' => 'Cancelación de apartado (liberación de reserva) ticket: ' . $sale->ticket_folio,
                                    'user_id' => auth()->id(),
                                ]);
                            } catch (\Exception $me) {
                                // Fallback silencioso si type enum es restrictivo
                            }
                        }
                    }
                }

                // Liberar el saldo de crédito que retenía el cliente
                if ($sale->customer && $sale->balance > 0) {
                    $sale->customer->releaseCredit((float)$sale->balance);
                }
            } else {
                // Venta pagada: Reingresar mercancía física al inventario
                foreach ($sale->items as $item) {
                    if ($item->product && $item->product->isPhysical() && !$item->product->is_wildcard && !$item->product->isService()) {
                        Inventory::applyMovement(
                            $sale->store_id,
                            $item->product_id,
                            'ajuste',
                            $item->quantity,
                            auth()->id(),
                            'Reingreso por cancelación de venta ticket: ' . $sale->ticket_folio,
                            'App\Models\Sale',
                            $sale->id,
                            $item->product_variant_id
                        );
                    }
                }
            }

            $reason = $request->input('reason', 'Sin motivo especificado');
            $userName = auth()->user()?->name ?? 'Cajero';
            $sale->status = 'cancelada';
            $sale->notes = ($sale->notes ? $sale->notes . " | " : "") . "Cancelada por {$userName} el " . now()->format('d/m/Y H:i') . ": {$reason}";
            $sale->save();

            // Cancelar comisiones de la venta y revertir canje de cupón si aplica
            app(\App\Services\CommissionService::class)->onSaleCancelled($sale);
            \App\Models\Coupon::revertRedemptionForSale($sale);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta cancelada exitosamente y mercancía restablecida en inventario.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al cancelar la venta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Muestra el historial de Cotizaciones activas/pendientes
     */
    public function quotes(Request $request)
    {
        Sale::ensureFoliosStandardized();

        $query = Quote::with(['customer', 'store', 'seller', 'user', 'items.product', 'items.variant'])
            ->whereNotNull('folio')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                Sale::applySmartFolioSearch($q, $search, 'folio');
                $q->orWhere('coupon_code', 'like', "%{$search}%")
                  ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'vencida') {
                $query->where('status', 'pendiente')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
            } elseif ($request->status === 'pendiente') {
                $query->where('status', 'pendiente')
                    ->where(function($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    });
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        $quotes = $query->paginate(15)->withQueryString();
        $sellers = User::where('status', true)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['Vendedor', 'Gerente', 'Dirección', 'Super Admin']))
            ->orderBy('name')
            ->get(['id', 'name', 'lastname']);

        $kpis = [
            'total_quotes' => Quote::whereNotNull('folio')->count(),
            'active_quotes' => Quote::whereNotNull('folio')->where('status', 'pendiente')->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })->count(),
            'converted_quotes' => Quote::whereNotNull('folio')->where('status', 'convertida')->count(),
            'total_quoted_amount' => (float)Quote::whereNotNull('folio')->where('status', 'pendiente')->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })->sum('total'),
        ];

        return view('admin.pos.quotes', compact('quotes', 'sellers', 'kpis'));
    }

    /**
     * Obtiene el detalle completo de una cotización en formato JSON para el modal de vista rápida
     */
    public function showQuote(Quote $quote)
    {
        $quote->load(['customer', 'store', 'seller', 'user', 'items.product', 'items.variant']);

        $isExpired = $quote->expires_at && $quote->expires_at->isPast() && $quote->status === 'pendiente';

        $items = $quote->items->map(function ($item) {
            $product = $item->product;
            $isWildcard = $product && $product->is_wildcard;
            $displayName = $product ? $product->name : 'Producto Eliminado';
            $notes = $item->notes;

            if ($isWildcard && !empty($item->notes)) {
                if (str_contains($item->notes, ' - ')) {
                    [$customName, $restNotes] = explode(' - ', $item->notes, 2);
                    $displayName = trim($customName);
                    $notes = trim($restNotes);
                } else {
                    $displayName = trim($item->notes);
                    $notes = null;
                }
            } elseif ($item->variant && $item->variant->name) {
                $displayName .= ' - ' . $item->variant->name;
            }

            return [
                'id' => $item->id,
                'sku' => $item->variant ? $item->variant->sku : ($product->sku ?? 'S/N'),
                'name' => $displayName,
                'notes' => $notes,
                'quantity' => (int)$item->quantity,
                'unit_price_original' => (float)$item->unit_price_original,
                'unit_price_final' => (float)$item->unit_price_final,
                'has_discount' => ((float)$item->unit_price_original - (float)$item->unit_price_final) > 0.05,
                'total' => (float)$item->total,
            ];
        });

        return response()->json([
            'success' => true,
            'quote' => [
                'id' => $quote->id,
                'folio' => $quote->folio ?? ('COT-' . $quote->id),
                'status' => $isExpired ? 'vencida' : $quote->status,
                'raw_status' => $quote->status,
                'is_active' => $quote->status === 'pendiente' && !$isExpired,
                'created_at' => $quote->created_at?->format('d/m/Y h:i A'),
                'expires_at' => $quote->expires_at?->format('d/m/Y'),
                'store_name' => $quote->store->name ?? 'Matriz',
                'customer_name' => $quote->customer ? trim($quote->customer->name . ' ' . $quote->customer->lastname) : 'Público en General',
                'customer_business' => $quote->customer->business_name ?? null,
                'customer_phone' => $quote->customer->phone ?? null,
                'seller_name' => $quote->seller ? trim($quote->seller->name . ' ' . $quote->seller->lastname) : ($quote->user->name ?? 'N/A'),
                'subtotal' => (float)$quote->subtotal,
                'tax_total' => (float)$quote->tax_total,
                'discount_total' => (float)$quote->discount_total,
                'coupon_code' => $quote->coupon_code,
                'coupon_discount' => (float)$quote->coupon_discount,
                'total' => (float)$quote->total,
                'is_delivery' => (bool)$quote->is_delivery,
                'delivery_address' => $quote->delivery_address,
                'delivery_recipient' => $quote->delivery_recipient_name,
                'delivery_phone' => $quote->delivery_phone,
                'delivery_date' => $quote->delivery_date?->format('d/m/Y'),
                'delivery_time_slot' => $quote->delivery_time_slot,
                'print_url' => route('pos.quotes.print', $quote->id),
                'load_pos_url' => route('pos.index', ['quote_id' => $quote->id]),
                'items' => $items,
            ]
        ]);
    }

    /**
     * Cancela una cotización activa/pendiente
     */
    public function cancelQuote(Request $request, Quote $quote)
    {
        if ($quote->status === 'convertida') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar una cotización que ya fue convertida en venta.'
            ], 422);
        }

        if ($quote->status === 'cancelada') {
            return response()->json([
                'success' => false,
                'message' => 'Esta cotización ya se encuentra cancelada.'
            ], 422);
        }

        $reason = trim($request->input('reason', 'Cancelada por el usuario'));
        $quote->update(['status' => 'cancelada']);

        activity('pos')
            ->performedOn($quote)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log("Cotización {$quote->folio} cancelada. Motivo: {$reason}");

        return response()->json([
            'success' => true,
            'message' => "La cotización {$quote->folio} ha sido cancelada correctamente."
        ]);
    }

    /**
     * Genera la vista de impresión provisional (o redirige según status)
     */
    public function printSale($saleId)
    {
        $sale = Sale::findOrFail($saleId);
        if ($sale->status === 'apartado' || $sale->balance > 0.01) {
            return $this->printAnticipoDocument($sale);
        }
        return $this->printSaleDocument($sale);
    }

    /**
     * Impresión de Orden de Venta Liquidada (Dual A4 / Ticket 80mm)
     * Solo accesible si la venta está 100% saldada.
     */
    public function printSaleDocument($saleId)
    {
        $sale = $saleId instanceof Sale ? $saleId : Sale::with(['items.product', 'items.variant', 'customer', 'store', 'seller', 'cashier', 'payments.paymentMethod'])->findOrFail($saleId);

        // Regla: No imprimir ticket de venta liquidada si existe saldo pendiente
        if ($sale->status === 'apartado' || $sale->balance > 0.01) {
            return redirect()->route('pos.sales.print-anticipo', $sale->id);
        }

        return view('admin.pos.print.sale_order', compact('sale'));
    }

    /**
     * Impresión del Historial Completo de Pagos / Abonos de la Venta (Dual A4 / Ticket 80mm)
     */
    public function printPaymentHistory($saleId)
    {
        $sale = $saleId instanceof Sale ? $saleId : Sale::findOrFail($saleId);
        $sale->load([
            'customer',
            'store',
            'seller',
            'cashier',
            'payments.paymentMethod',
            'payments.terminal',
            'payments.cashier',
            'payments.cashShift'
        ]);

        return view('admin.pos.print.payment_history', compact('sale'));
    }

    /**
     * Impresión de Comprobante de Anticipo / Apartado (Dual A4 / Ticket 80mm)
     */
    public function printAnticipoDocument($saleId)
    {
        $sale = $saleId instanceof Sale ? $saleId : Sale::with(['items.product', 'items.variant', 'customer', 'store', 'seller', 'cashier', 'payments.paymentMethod'])->findOrFail($saleId);
        return view('admin.pos.print.layaway_receipt', compact('sale'));
    }

    /**
     * Impresión de Recibo de Abono individual (Dual A4 / Ticket 80mm)
     */
    public function printPaymentReceipt($saleId, $paymentId)
    {
        $sale = Sale::with(['customer', 'store', 'seller', 'cashier'])->findOrFail($saleId);
        $payment = Payment::with(['paymentMethod', 'terminal', 'cashier', 'cashShift'])->where('sale_id', $sale->id)->findOrFail($paymentId);
        return view('admin.pos.print.payment_receipt', compact('sale', 'payment'));
    }

    /**
     * Impresión de Comprobante de Cotización (Dual A4 / Ticket 80mm)
     */
    public function printQuoteDocument($quoteId)
    {
        $quote = Quote::with(['items.product', 'items.variant', 'customer', 'store', 'seller', 'user'])->findOrFail($quoteId);
        return view('admin.pos.print.quote', compact('quote'));
    }

    /**
     * Muestra e imprime la Orden de Entrega de una Venta a Domicilio
     */
    public function printDeliveryOrder($saleId)
    {
        $sale = Sale::with(['items.product', 'items.variant', 'customer', 'store', 'seller'])->findOrFail($saleId);
        return view('admin.pos.delivery_order', compact('sale'));
    }
}
