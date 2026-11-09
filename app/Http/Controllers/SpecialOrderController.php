<?php

namespace App\Http\Controllers;

use App\Models\SpecialOrder;
use App\Models\SpecialOrderItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecialOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-pedidos-especiales')->only(['index', 'show']);
        $this->middleware('permission:gestionar-pedidos-especiales')->only(['updateStatus']);
        $this->middleware('permission:abonar-pedidos-especiales')->only(['addPayment']);
        $this->middleware('permission:recibir-pedidos-especiales')->only(['receiveItem']);
        $this->middleware('permission:imprimir-pedidos-especiales')->only(['printDeliveryOrder']);
    }

    /**
     * Listado de Pedidos Especiales con filtros de búsqueda, estado y sucursal.
     */
    public function index(Request $request)
    {
        \App\Models\Sale::ensureFoliosStandardized();

        $isGlobal = is_global_view() && auth()->user()->can('vista-global');
        $storeId  = $isGlobal ? $request->input('store_id') : current_store_id();
        $status   = $request->input('status');
        $search   = trim((string) $request->input('search', ''));

        $query = SpecialOrder::with(['customer', 'seller', 'items.product'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($status,  fn($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    \App\Models\Sale::applySmartFolioSearch($sub, $search, 'folio');
                    $sub->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                        ->orWhere('delivery_phone', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%")
                              ->orWhere('lastname', 'like', "%{$search}%")
                              ->orWhere('business_name', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at');

        $orders = $query->paginate(20)->withQueryString();

        // KPIs del panel
        $kpis = [
            'pending'   => SpecialOrder::when($storeId, fn($q) => $q->where('store_id', $storeId))->where('status', 'pending')->count(),
            'ordered'   => SpecialOrder::when($storeId, fn($q) => $q->where('store_id', $storeId))->where('status', 'ordered')->count(),
            'partial'   => SpecialOrder::when($storeId, fn($q) => $q->where('store_id', $storeId))->where('status', 'partial')->count(),
            'ready'     => SpecialOrder::when($storeId, fn($q) => $q->where('store_id', $storeId))->where('status', 'ready')->count(),
        ];

        return view('admin.special-orders.index', compact('orders', 'kpis', 'status', 'search'));
    }

    /**
     * Detalle completo del Pedido Especial.
     */
    public function show(SpecialOrder $specialOrder)
    {
        $specialOrder->load(['customer', 'seller', 'items.product', 'items.variant', 'payments.paymentMethod']);

        return view('admin.special-orders.show', compact('specialOrder'));
    }

    /**
     * Actualiza el estado del Pedido Especial.
     * Flujo: pending → ordered → partial/ready → delivered | cancelled
     */
    public function updateStatus(Request $request, SpecialOrder $specialOrder)
    {
        $request->validate([
            'status' => 'required|in:pending,ordered,partial,ready,delivered,cancelled',
        ]);

        $newStatus = $request->input('status');

        // Validaciones de flujo
        if ($specialOrder->isDelivered() || $specialOrder->isCancelled()) {
            return back()->with('error', 'Este pedido ya está finalizado y no puede modificarse.');
        }

        if ($newStatus === 'delivered' && $specialOrder->balanceDue() > 0) {
            return back()->with('error', "No se puede marcar como Entregado. Saldo pendiente: $" . number_format($specialOrder->balanceDue(), 2));
        }

        $specialOrder->update(['status' => $newStatus]);

        return back()->with('success', "Estado actualizado a: {$specialOrder->statusLabel()}");
    }

    /**
     * Registra un abono del cliente al Pedido Especial.
     */
    public function addPayment(Request $request, SpecialOrder $specialOrder)
    {
        $request->validate([
            'amount'              => 'required|numeric|min:0.01',
            'payment_method_id'   => 'required|exists:payment_methods,id',
            'payment_terminal_id' => 'nullable|exists:payment_terminals,id',
            'notes'               => 'nullable|string|max:500',
        ]);

        if ($specialOrder->isDelivered() || $specialOrder->isCancelled()) {
            return back()->with('error', 'No se pueden registrar pagos en pedidos finalizados.');
        }

        $amount = (float)$request->amount;
        if ($amount > $specialOrder->balanceDue()) {
            return back()->with('error', "El monto ($" . number_format($amount, 2) . ") excede el saldo pendiente ($" . number_format($specialOrder->balanceDue(), 2) . ").");
        }

        // Vincular a Turno de Caja activo para Cortes X y Z
        $activeShift = \App\Models\CashShift::activeShift($specialOrder->store_id, auth()->id())
            ?? \App\Models\CashShift::activeShift($specialOrder->store_id);

        $specialOrder->payments()->create([
            'cash_shift_id'       => $activeShift?->id,
            'payment_method_id'   => $request->payment_method_id,
            'payment_terminal_id' => $request->payment_terminal_id,
            'user_id'             => auth()->id(),
            'amount'              => $amount,
            'notes'               => $request->notes,
        ]);

        // Si ya está saldado, actualizar estado automáticamente a "ready" si ya tenía mercancía
        if ($specialOrder->balanceDue() <= 0 && $specialOrder->allItemsReceived()) {
            $specialOrder->update(['status' => 'ready']);
        }

        $shiftNotice = $activeShift ? " (Asignado al turno {$activeShift->folio})" : " (Sin turno de caja activo)";
        return back()->with('success', "Abono de $" . number_format($amount, 2) . " registrado correctamente{$shiftNotice}.");
    }

    /**
     * Imprime la Orden de Entrega a Domicilio para Chofer / Logística de Pedido Especial.
     */
    public function printDeliveryOrder(SpecialOrder $specialOrder)
    {
        $specialOrder->load(['customer', 'store', 'seller', 'items.product', 'items.variant']);

        if (!$specialOrder->hasDelivery()) {
            return back()->with('error', 'Este pedido especial no está programado para entrega a domicilio.');
        }

        return view('admin.special-orders.delivery_order', compact('specialOrder'));
    }

    /**
     * Registra la recepción de unidades de un ítem del pedido.
     */
    public function receiveItem(Request $request, SpecialOrder $specialOrder, SpecialOrderItem $item)
    {
        $request->validate([
            'quantity_received' => 'required|integer|min:1',
        ]);

        if ($item->special_order_id !== $specialOrder->id) {
            abort(403);
        }

        $pending = $item->pendingQuantity();
        $toReceive = min((int)$request->quantity_received, $pending);

        DB::transaction(function () use ($item, $toReceive, $specialOrder) {
            $item->update([
                'quantity_received' => $item->quantity_received + $toReceive,
                'received_at'       => now(),
            ]);

            // Actualizar estado del pedido
            $specialOrder->refresh();
            if ($specialOrder->allItemsReceived()) {
                $newStatus = $specialOrder->balanceDue() <= 0 ? 'ready' : 'partial';
                $specialOrder->update(['status' => $newStatus]);
            } else {
                $specialOrder->update(['status' => 'partial']);
            }
        });

        return back()->with('success', "{$toReceive} unidad(es) recibida(s) correctamente.");
    }
}
