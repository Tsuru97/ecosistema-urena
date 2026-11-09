<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-ordenes-compra')->only(['index', 'show', 'downloadInvoice']);
        $this->middleware('permission:crear-ordenes-compra')->only(['create', 'store', 'updateSupplier', 'updateRvFolio']);
        $this->middleware('permission:autorizar-ordenes-compra')->only(['approve']);
        $this->middleware('permission:cancelar-ordenes-compra')->only(['cancel', 'deleteInvoiceFile']);
        $this->middleware('permission:recibir-ordenes-compra')->only(['receive']);
        $this->middleware('permission:pagar-ordenes-compra')->only(['addPayment']);
        $this->middleware('permission:modificar-costos-compra')->only(['updateItemCost']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search        = $request->input('search');
        $status        = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $supplierId    = $request->input('supplier_id');
        $dateFrom      = $request->input('date_from');
        $dateTo        = $request->input('date_to');
        $hasInvoice    = $request->input('has_invoice');
        $storeId       = $request->input('store_id');
        $isGlobal      = is_global_view() && Auth::user()->can('vista-global');

        // Determinar tienda activa si no es vista global
        $activeStoreId = $isGlobal ? $storeId : current_store_id();

        $applyFilters = function ($q) use ($activeStoreId, $search, $status, $paymentStatus, $supplierId, $dateFrom, $dateTo, $hasInvoice) {
            $q->when($activeStoreId, fn($sq) => $sq->where('store_id', $activeStoreId))
              ->when($supplierId, fn($sq) => $sq->where('supplier_id', $supplierId))
              ->when($search, fn($sq) => $sq->where(fn($ssq) => $ssq->where('folio', 'like', "%{$search}%")->orWhere('rv_folio', 'like', "%{$search}%")))
              ->when($status, fn($sq) => $sq->where('status', $status))
              ->when($paymentStatus, fn($sq) => $sq->where('payment_status', $paymentStatus))
              ->when($dateFrom, fn($sq) => $sq->where(fn($dsq) => $dsq->whereDate('issued_at', '>=', $dateFrom)->orWhereDate('created_at', '>=', $dateFrom)))
              ->when($dateTo, fn($sq) => $sq->where(fn($dsq) => $dsq->whereDate('issued_at', '<=', $dateTo)->orWhereDate('created_at', '<=', $dateTo)))
              ->when($hasInvoice === 'with', fn($sq) => $sq->where(fn($isq) => $isq->whereNotNull('rv_folio')->orWhereNotNull('invoice_pdf_path')->orWhereNotNull('invoice_xml_path')))
              ->when($hasInvoice === 'without', fn($sq) => $sq->whereNull('rv_folio')->whereNull('invoice_pdf_path')->whereNull('invoice_xml_path'));
        };

        $query = PurchaseOrder::with(['supplier', 'store', 'creator', 'items', 'payments'])
            ->withCount('items');
        
        $applyFilters($query);

        $purchaseOrders = $query->latest()->paginate(20)->withQueryString();

        // Conteo por estado para las tabs
        $counts = PurchaseOrder::when($activeStoreId, fn($q) => $q->where('store_id', $activeStoreId))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // ── KPIs Contextuales (Filtrados vs Total Sucursal / Consolidado) ─
        $isFiltered = !empty($search) || !empty($paymentStatus) || !empty($status) || !empty($supplierId) || !empty($dateFrom) || !empty($dateTo) || !empty($hasInvoice);

        $storeObj   = $activeStoreId ? Store::find($activeStoreId) : null;
        $scopeLabel = $storeObj ? "Total {$storeObj->name}" : "Total Consolidado";

        $cacheKey = "kpis_baseline_" . ($activeStoreId ?? 'global');
        $baselineKpis = PurchaseOrder::rememberWithTags($cacheKey, function () use ($activeStoreId) {
            $baseKpiQuery = PurchaseOrder::where('status', '!=', 'cancelada')
                ->when($activeStoreId, fn($q) => $q->where('store_id', $activeStoreId));

            $purchased    = (float) (clone $baseKpiQuery)->selectRaw('SUM(CASE WHEN net_amount > 0 THEN net_amount ELSE total END) as total_net')->value('total_net') ?? 0;
            $paid         = (float) (clone $baseKpiQuery)->sum('amount_paid');
            $debt         = max(0, $purchased - $paid);
            $pendingCount = (int) (clone $baseKpiQuery)->whereIn('payment_status', ['pendiente', 'pago_parcial'])->count();
            $efficiency   = $purchased > 0 ? min(100, round(($paid / $purchased) * 100, 1)) : 0;

            return [
                'total_purchased'       => $purchased,
                'total_paid'            => $paid,
                'pending_debt'          => $debt,
                'pending_payment_count' => $pendingCount,
                'payment_efficiency'   => $efficiency,
            ];
        });

        if ($isFiltered) {
            $filteredKpiQuery = PurchaseOrder::where('status', '!=', 'cancelada');
            $applyFilters($filteredKpiQuery);

            $fPurchased    = (float) (clone $filteredKpiQuery)->selectRaw('SUM(CASE WHEN net_amount > 0 THEN net_amount ELSE total END) as total_net')->value('total_net') ?? 0;
            $fPaid         = (float) (clone $filteredKpiQuery)->sum('amount_paid');
            $fDebt         = max(0, $fPurchased - $fPaid);
            $fPendingCount = (int) (clone $filteredKpiQuery)->whereIn('payment_status', ['pendiente', 'pago_parcial'])->count();
            $fEfficiency   = $fPurchased > 0 ? min(100, round(($fPaid / $fPurchased) * 100, 1)) : 0;

            $currentKpis = [
                'total_purchased'       => $fPurchased,
                'total_paid'            => $fPaid,
                'pending_debt'          => $fDebt,
                'pending_payment_count' => $fPendingCount,
                'payment_efficiency'   => $fEfficiency,
            ];
        } else {
            $currentKpis = $baselineKpis;
        }

        $kpis = [
            'is_filtered' => $isFiltered,
            'scope_label' => $scopeLabel,
            'current'     => $currentKpis,
            'baseline'    => $baselineKpis,
        ];

        $suppliers = Supplier::where('status', true)->orderBy('name')->get();

        return view('admin.purchase_orders.index', compact(
            'purchaseOrders', 'search', 'status', 'paymentStatus', 'supplierId', 'dateFrom', 'dateTo', 'hasInvoice',
            'isGlobal', 'activeStoreId', 'counts', 'suppliers', 'kpis'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $isGlobal = is_global_view() && Auth::user()->can('vista-global');
        $activeStoreId = current_store_id();

        if (!$isGlobal && !$activeStoreId) {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Selecciona una sucursal activa para crear una orden de compra.');
        }

        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $stores    = $isGlobal ? Store::where('status', true)->orderBy('name')->get() : collect();
        $products  = Product::where('status', true)
            ->with(['variants' => fn($q) => $q->where('is_active', true)->with('attributeValues.attribute')])
            ->orderBy('name')->get();

        return view('admin.purchase_orders.create', compact('suppliers', 'stores', 'products', 'isGlobal', 'activeStoreId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isGlobal = is_global_view() && Auth::user()->can('vista-global');
        $activeStoreId = current_store_id();

        $request->validate([
            'supplier_id'               => 'required|integer|exists:suppliers,id',
            'store_id'                  => $isGlobal ? 'required|integer|exists:stores,id' : 'nullable',
            'expected_at'               => 'nullable|date',
            'notes'                     => 'nullable|string|max:1000',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'         => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.quantity_ordered'   => 'required|integer|min:1',
            'items.*.cost'               => 'required|numeric|min:0',
        ]);

        $storeId = $isGlobal ? (int) $request->store_id : $activeStoreId;

        if (!$storeId) {
            return back()->with('error', 'Debes seleccionar una sucursal para la compra.')->withInput();
        }

        DB::beginTransaction();
        try {
            // Calcular total general de partidas
            $total = 0.00;
            foreach ($request->items as $item) {
                $total += ((int) $item['quantity_ordered']) * ((float) $item['cost']);
            }

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id'   => $request->supplier_id,
                'store_id'      => $storeId,
                'folio'         => PurchaseOrder::generateFolio(),
                'status'        => 'pendiente',
                'expected_at'   => $request->expected_at,
                'notes'         => $request->notes,
                'total'         => $total,
                'gross_amount'  => $total,
                'net_amount'    => $total,
                'created_by'    => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id'  => $purchaseOrder->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?: null,
                    'quantity_ordered'   => $item['quantity_ordered'],
                    'cost'               => $item['cost'],
                ]);
            }

            DB::commit();
            PurchaseOrder::flushCache();

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', "Orden de compra {$purchaseOrder->folio} creada con éxito.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear la orden de compra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        // Auto-sincronizar campos financieros si la OC no tiene net_amount cargado
        if ((float) $purchaseOrder->net_amount <= 0 && (float) $purchaseOrder->total > 0) {
            $purchaseOrder->recalculatePaymentStatus();
            $purchaseOrder->refresh();
        }

        $purchaseOrder->load([
            'supplier', 'store', 'creator', 'approver',
            'items.product.brand',
            'items.variant.attributeValues.attribute',
        ]);

        $storeId   = current_store_id();
        $isGlobal  = is_global_view() && Auth::user()->can('vista-global');
        $isDest    = $isGlobal || ($storeId && $purchaseOrder->store_id === (int) $storeId);
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();

        return view('admin.purchase_orders.show', compact('purchaseOrder', 'isDest', 'isGlobal', 'suppliers'));
    }

    /**
     * Reasigna el proveedor a una orden de compra en estado borrador/pendiente.
     */
    public function updateSupplier(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pendiente') {
            return back()->with('error', 'Solo se puede modificar el proveedor en órdenes de compra en estado pendiente.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'action'      => 'nullable|in:merge,keep_standalone',
        ]);

        $newSupplier = Supplier::findOrFail($request->supplier_id);
        $action      = $request->input('action', 'keep_standalone');

        // Buscar si ya existe una OC borrador distinta para este proveedor y sucursal
        $existingPo = PurchaseOrder::where('store_id', $purchaseOrder->store_id)
            ->where('supplier_id', $newSupplier->id)
            ->where('status', 'pendiente')
            ->where('id', '!=', $purchaseOrder->id)
            ->latest()
            ->first();

        // Si existe una OC borrador para ese proveedor y el usuario eligió FUSIONAR
        if ($existingPo && $action === 'merge') {
            foreach ($purchaseOrder->items as $item) {
                $isWildcard = ($item->product && (!empty($item->product->is_wildcard) || ($item->product->type ?? '') === 'personalizado'));

                if ($isWildcard) {
                    // En productos personalizados, solo se suman cantidades si comparten las MISMAS notas/especificaciones Y el MISMO costo unitario
                    $matchingItem = $existingPo->items()
                        ->where('product_id', $item->product_id)
                        ->where('cost', $item->cost)
                        ->where('notes', $item->notes)
                        ->first();
                } else {
                    $matchingItem = $existingPo->items()
                        ->where('product_id', $item->product_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->first();
                }

                if ($matchingItem) {
                    $newQty   = $matchingItem->quantity_ordered + $item->quantity_ordered;
                    $newGross = $newQty * $matchingItem->cost;
                    $matchingItem->update([
                        'quantity_ordered' => $newQty,
                        'gross_amount'     => $newGross,
                        'net_amount'       => $newGross,
                    ]);
                } else {
                    // Si las especificaciones o el costo son diferentes, se añade como una partida INDEPENDIENTE
                    $item->update(['purchase_order_id' => $existingPo->id]);
                }
            }

            // Re-vincular cualquier Pedido Especial que apuntara a esta OC
            \App\Models\SpecialOrderItem::where('purchase_order_id', $purchaseOrder->id)
                ->update(['purchase_order_id' => $existingPo->id]);

            // Actualizar montos y notas de $existingPo
            $totalGross    = (float) $existingPo->items()->sum('gross_amount');
            $existingNotes = $existingPo->notes ?? '';
            if ($purchaseOrder->notes) {
                $existingNotes .= " / " . $purchaseOrder->notes;
            }

            $existingPo->update([
                'gross_amount' => $totalGross,
                'net_amount'   => $totalGross,
                'total'        => $totalGross,
                'notes'        => trim(str_replace(' ⚠️ ATENCIÓN: Pendiente de Asignar Proveedor por Compras.', '', $existingNotes)),
            ]);

            $oldFolio = $purchaseOrder->folio;
            $purchaseOrder->delete();

            PurchaseOrder::flushCache();

            return redirect()->route('purchase-orders.show', $existingPo)
                ->with('success', "Partidas de {$oldFolio} fusionadas exitosamente en la Orden de Compra {$existingPo->folio} de {$newSupplier->name}.");
        }

        // Si no hay otra OC borrador o eligió mantenerla independiente
        $cleanNotes = str_replace(' ⚠️ ATENCIÓN: Pendiente de Asignar Proveedor por Compras.', '', $purchaseOrder->notes ?? '');

        $purchaseOrder->update([
            'supplier_id' => $newSupplier->id,
            'notes'       => trim($cleanNotes),
        ]);

        PurchaseOrder::flushCache();

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "Proveedor asignado correctamente a: {$newSupplier->name}.");
    }

    /**
     * Ajusta el costo de compra unitario de una partida en una orden de compra pendiente.
     */
    public function updateItemCost(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderItem $item)
    {
        if ($purchaseOrder->status !== 'pendiente') {
            return back()->with('error', 'Solo se pueden ajustar los costos en órdenes de compra en estado pendiente.');
        }

        if ($item->purchase_order_id !== $purchaseOrder->id) {
            return back()->with('error', 'La partida no pertenece a esta orden de compra.');
        }

        $request->validate([
            'cost' => 'required|numeric|min:0.01',
        ]);

        $newCost = (float) $request->cost;
        $qty     = (int) $item->quantity_ordered;
        $disc    = (float) ($item->discount_amount ?? 0);

        $newGross = round($newCost * $qty, 2);
        $newNet   = round(max(0, $newGross - $disc), 2);

        $item->update([
            'cost'         => $newCost,
            'gross_amount' => $newGross,
            'net_amount'   => $newNet,
        ]);

        // Recalcular montos de la Orden de Compra
        $totalGross = (float) $purchaseOrder->items()->sum('gross_amount');
        $totalNet   = (float) $purchaseOrder->items()->sum('net_amount');

        // Limpiar nota de advertencia de costo estimado si la tenía
        $notes = $purchaseOrder->notes ?? '';
        $notes = preg_replace('/⚠️ ATENCIÓN: Costo estimado \(\$[0-9,.]+\) pendiente de verificación\./u', '', $notes);

        $purchaseOrder->update([
            'gross_amount' => $totalGross,
            'net_amount'   => $totalNet,
            'total'        => $totalNet,
            'notes'        => trim($notes),
        ]);

        PurchaseOrder::flushCache();

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "Costo de compra ajustado correctamente a $" . number_format($newCost, 2) . " para {$item->product->name}.");
    }

    /**
     * Aprueba la orden de compra.
     */
    public function approve(PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder->approve(Auth::id());
            PurchaseOrder::flushCache();

            // ── Sincronización con Pedidos Especiales: Cambiar a estado "ordered" (En Pedido) ──
            $linkedSOItems = \App\Models\SpecialOrderItem::where('purchase_order_id', $purchaseOrder->id)->get();
            foreach ($linkedSOItems as $soItem) {
                if ($soItem->specialOrder && $soItem->specialOrder->isPending()) {
                    $soItem->specialOrder->update(['status' => 'ordered']);
                }
            }

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', "Orden de compra {$purchaseOrder->folio} aprobada correctamente y Pedidos Especiales sincronizados a 'En Pedido'.");

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancela la orden de compra.
     */
    public function cancel(PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder->cancel();
            PurchaseOrder::flushCache();

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', "Orden de compra {$purchaseOrder->folio} cancelada.");

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Confirma la recepción e ingresa stock a inventario.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'quantity_received'   => 'required|array',
            'quantity_received.*' => 'required|integer|min:0',
            'warehouse_id'        => 'nullable|integer|exists:warehouses,id',
        ]);

        $quantities = $request->input('quantity_received', []);
        $warehouseId = $request->input('warehouse_id');

        try {
            $purchaseOrder->receive(Auth::id(), $quantities, $warehouseId);
            PurchaseOrder::flushCache();

            // ── Sincronización con Pedidos Especiales: Asignar stock y actualizar a 'ready' o 'partial' ──
            foreach ($purchaseOrder->items as $item) {
                $qtyReceived = (int)($quantities[$item->id] ?? 0);
                if ($qtyReceived <= 0) continue;

                // Buscar pedidos especiales pendientes para el mismo producto/variante
                $pendingSOItems = \App\Models\SpecialOrderItem::where('product_id', $item->product_id)
                    ->when($item->product_variant_id, fn($q) => $q->where('product_variant_id', $item->product_variant_id))
                    ->whereRaw('quantity_received < quantity')
                    ->whereHas('specialOrder', fn($q) => $q->whereNotIn('status', ['delivered', 'cancelled']))
                    ->orderBy('created_at')
                    ->get();

                $stockToAllocate = $qtyReceived;
                foreach ($pendingSOItems as $soItem) {
                    if ($stockToAllocate <= 0) break;
                    $needed = $soItem->pendingQuantity();
                    $toGive = min($needed, $stockToAllocate);
                    $soItem->update([
                        'quantity_received' => $soItem->quantity_received + $toGive,
                        'received_at'       => now(),
                    ]);
                    $stockToAllocate -= $toGive;

                    // Actualizar estado del pedido especial
                    $soItem->specialOrder->refresh();
                    if ($soItem->specialOrder->allItemsReceived()) {
                        $soItem->specialOrder->update(['status' => 'ready']);
                    } else {
                        $soItem->specialOrder->update(['status' => 'partial']);
                    }
                }
            }
            // ─────────────────────────────────────────────────────────────────

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', "Mercancía de la orden {$purchaseOrder->folio} ingresada al almacén. Stock y costos actualizados con éxito.");

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Registra un pago parcial o total al proveedor por esta OC.
     */
    public function addPayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:efectivo,transferencia,cheque,otro',
            'reference'      => 'nullable|string|max:200',
            'notes'          => 'nullable|string|max:500',
            'paid_at'        => 'nullable|date',
        ]);

        $amount = (float)$request->amount;
        $balance = $purchaseOrder->pendingBalance();

        if ($amount > $balance + 0.01) {
            return back()->with('error', "El monto ($" . number_format($amount, 2) . ") excede el saldo pendiente ($" . number_format($balance, 2) . ").");
        }

        DB::transaction(function () use ($request, $purchaseOrder, $amount) {
            // 1. Registrar como Gasto para control contable
            $expense = \App\Models\Expense::create([
                'store_id'       => $purchaseOrder->store_id,
                'user_id'        => Auth::id(),
                'concept'        => "Pago a proveedor: {$purchaseOrder->supplier->name} — OC {$purchaseOrder->folio}" . ($request->reference ? " (Ref: {$request->reference})" : ''),
                'amount'         => $amount,
                'date'           => $request->paid_at ?? now()->toDateString(),
                'reference_type' => \App\Models\PurchaseOrder::class,
                'reference_id'   => $purchaseOrder->id,
                'notes'          => $request->notes,
            ]);

            // 2. Registrar el pago al proveedor
            \App\Models\SupplierPayment::create([
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id'       => $purchaseOrder->supplier_id,
                'store_id'          => $purchaseOrder->store_id,
                'user_id'           => Auth::id(),
                'amount'            => $amount,
                'payment_method'    => $request->payment_method,
                'reference'         => $request->reference,
                'notes'             => $request->notes,
                'date'              => $request->paid_at ?? now()->toDateString(),
                'expense_id'        => $expense->id,
            ]);

            // 3. Actualizar totales en la OC
            $purchaseOrder->recalculatePaymentStatus();
        });

        return back()->with('success', "Pago de $" . number_format($amount, 2) . " registrado correctamente para la OC {$purchaseOrder->folio}.");
    }

    /**
     * Asigna o actualiza el Folio RV / Factura del proveedor, su fecha de emisión y archivos adjuntos (PDF/XML).
     */
    public function updateRvFolio(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'rv_folio'    => 'required|string|max:50',
            'issued_at'   => 'nullable|date',
            'invoice_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'invoice_xml' => 'nullable|file|mimes:xml,txt|max:5120',
        ]);

        $updateData = [
            'rv_folio'  => $request->rv_folio,
            'issued_at' => $request->issued_at,
        ];

        // Procesar archivo PDF
        if ($request->hasFile('invoice_pdf')) {
            if ($purchaseOrder->invoice_pdf_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($purchaseOrder->invoice_pdf_path);
            }
            $updateData['invoice_pdf_path'] = $request->file('invoice_pdf')->store('purchase_orders/invoices', 'public');
        }

        // Procesar archivo XML
        if ($request->hasFile('invoice_xml')) {
            if ($purchaseOrder->invoice_xml_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($purchaseOrder->invoice_xml_path);
            }
            $updateData['invoice_xml_path'] = $request->file('invoice_xml')->store('purchase_orders/invoices', 'public');
        }

        $purchaseOrder->update($updateData);
        PurchaseOrder::flushCache();

        return back()->with('success', "Información de Factura / RV {$request->rv_folio} y archivos adjuntos guardados correctamente.");
    }

    /**
     * Descarga el archivo de factura adjunto (PDF o XML).
     */
    public function downloadInvoice(PurchaseOrder $purchaseOrder, string $type)
    {
        $path = match($type) {
            'pdf' => $purchaseOrder->invoice_pdf_path,
            'xml' => $purchaseOrder->invoice_xml_path,
            default => null,
        };

        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return back()->with('error', 'El archivo solicitado no existe o no ha sido cargado.');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($path);
    }

    /**
     * Elimina el archivo de factura adjunto (PDF o XML).
     */
    public function deleteInvoiceFile(PurchaseOrder $purchaseOrder, string $type)
    {
        $pathField = $type === 'pdf' ? 'invoice_pdf_path' : ($type === 'xml' ? 'invoice_xml_path' : null);
        if (!$pathField) return back();

        $currentPath = $purchaseOrder->{$pathField};
        if ($currentPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentPath)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($currentPath);
        }

        $purchaseOrder->update([$pathField => null]);
        PurchaseOrder::flushCache();

        return back()->with('success', "Archivo " . strtoupper($type) . " eliminado correctamente.");
    }
}


