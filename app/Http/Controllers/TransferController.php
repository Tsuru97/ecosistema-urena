<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-traspasos')->only(['index', 'show', 'productStock']);
        $this->middleware('permission:crear-traspasos')->only(['create', 'store']);
        $this->middleware('permission:autorizar-traspasos')->only(['ship', 'cancel']);
        $this->middleware('permission:recibir-traspasos')->only(['receive', 'reject']);
    }

    // ── Index ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search    = $request->input('search');
        $status    = $request->input('status');
        $isGlobal  = is_global_view() && Auth::user()->can('vista-global');
        $storeId   = $isGlobal ? null : current_store_id();

        $query = Transfer::with([
                'originStore', 'destinationStore', 'creator', 'items',
            ])
            ->when($storeId, fn($q) => $q->forStore($storeId))
            ->when($search, fn($q) => $q->where('folio', 'like', "%{$search}%"))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest();

        $transfers = $query->paginate(20)->withQueryString();

        // Conteo por estado para tabs
        $counts = Transfer::when($storeId, fn($q) => $q->forStore($storeId))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.transfers.index', compact(
            'transfers', 'search', 'status', 'isGlobal', 'storeId', 'counts'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────

    public function create()
    {
        $storeId = current_store_id();
        if (!$storeId) {
            return redirect()->route('transfers.index')
                ->with('error', 'Selecciona una sucursal activa para crear un traspaso.');
        }

        $stores   = Store::where('id', '!=', $storeId)->where('status', true)->orderBy('name')->get();
        $products = Product::where('status', true)
            ->with(['variants' => fn($q) => $q->where('is_active', true)->with('attributeValues.attribute')])
            ->orderBy('name')->get();

        $currentStore = Store::findOrFail($storeId);

        return view('admin.transfers.create', compact('stores', 'products', 'currentStore', 'storeId'));
    }

    public function store(Request $request)
    {
        $storeId = current_store_id();
        if (!$storeId) {
            return back()->with('error', 'No hay sucursal activa.');
        }

        $request->validate([
            'destination_store_id'       => ['required', 'integer', 'exists:stores,id', "different:origin_store_id"],
            'notes'                      => ['nullable', 'string', 'max:1000'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_id'         => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
        ]);

        // Validar que no se manda a la misma sucursal
        if ((int)$request->destination_store_id === (int)$storeId) {
            return back()->with('error', 'La sucursal de destino debe ser diferente a la de origen.')->withInput();
        }

        DB::beginTransaction();
        try {
            $transfer = Transfer::create([
                'origin_store_id'      => $storeId,
                'destination_store_id' => $request->destination_store_id,
                'folio'                => Transfer::generateFolio(),
                'status'               => 'pendiente',
                'created_by'           => Auth::id(),
                'notes'                => $request->notes,
            ]);

            foreach ($request->items as $item) {
                TransferItem::create([
                    'transfer_id'        => $transfer->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?: null,
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            DB::commit();
            Transfer::flushCache();

            return redirect()->route('transfers.show', $transfer)
                ->with('success', "Traspaso {$transfer->folio} creado correctamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el traspaso: ' . $e->getMessage())->withInput();
        }
    }

    // ── Show ──────────────────────────────────────────────────────────

    public function show(Transfer $transfer)
    {
        $transfer->load([
            'originStore', 'destinationStore',
            'creator', 'sender', 'receiver',
            'items.product.brand',
            'items.variant.attributeValues.attribute',
            'proofPhoto',
        ]);

        $storeId  = current_store_id();
        $isOrigin = $storeId && $transfer->origin_store_id === (int)$storeId;
        $isDest   = $storeId && $transfer->destination_store_id === (int)$storeId;
        $isGlobal = is_global_view();

        return view('admin.transfers.show', compact(
            'transfer', 'isOrigin', 'isDest', 'isGlobal'
        ));
    }

    // ── Acciones de estado ────────────────────────────────────────────

    /** Sucursal origen confirma el envío (Pendiente → En Tránsito) */
    public function ship(Request $request, Transfer $transfer)
    {
        $storeId = current_store_id();
        if (!$storeId || $transfer->origin_store_id !== (int)$storeId) {
            return back()->with('error', 'Solo la sucursal de origen puede enviar este traspaso.');
        }

        // Cantidades enviadas opcionales (puede diferir de lo solicitado)
        $quantities = $request->input('quantity_sent', []);

        DB::beginTransaction();
        try {
            // Actualizar qty_sent en cada item si se especificó
            if (!empty($quantities)) {
                foreach ($transfer->items as $item) {
                    if (isset($quantities[$item->id])) {
                        $item->update(['quantity_sent' => max(0, (int)$quantities[$item->id])]);
                    }
                }
                $transfer->refresh();
            }

            // Comprobar si hay artículos con quantity_requested > quantity_sent para generar remanente
            $remainderItems = [];
            foreach ($transfer->items as $item) {
                $qtySent = $item->quantity_sent ?? $item->quantity_requested;
                if ($item->quantity_requested > $qtySent) {
                    $remainderItems[] = [
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity_requested' => $item->quantity_requested - $qtySent,
                    ];
                }
            }

            // Procesar el envío
            $transfer->ship(Auth::id());

            // Crear traspaso de remanente si aplica
            $hasRemainder = false;
            if (!empty($remainderItems)) {
                $remainderTransfer = Transfer::create([
                    'origin_store_id'      => $transfer->origin_store_id,
                    'destination_store_id' => $transfer->destination_store_id,
                    'folio'                => Transfer::generateFolio(),
                    'status'               => 'pendiente',
                    'created_by'           => Auth::id(),
                    'notes'                => "Solicitud automática por diferencia de mercancía en traspaso {$transfer->folio}",
                ]);

                foreach ($remainderItems as $remItem) {
                    TransferItem::create([
                        'transfer_id'        => $remainderTransfer->id,
                        'product_id'         => $remItem['product_id'],
                        'product_variant_id' => $remItem['product_variant_id'],
                        'quantity_requested' => $remItem['quantity_requested'],
                    ]);
                }
                $hasRemainder = true;
            }

            DB::commit();
            Transfer::flushCache();

            $msg = "Traspaso {$transfer->folio} marcado como En Tránsito. Stock descontado de {$transfer->originStore->name}.";
            if ($hasRemainder) {
                $msg .= " Se ha generado automáticamente un nuevo traspaso pendiente ({$remainderTransfer->folio}) por el remanente faltante.";
            }

            return redirect()->route('transfers.show', $transfer)
                ->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /** Sucursal destino confirma la recepción (En Tránsito → Recibido / Devolución) */
    public function receive(Request $request, Transfer $transfer)
    {
        $storeId = current_store_id();
        if (!$storeId || $transfer->destination_store_id !== (int)$storeId) {
            return back()->with('error', 'Solo la sucursal de destino puede confirmar la recepción.');
        }

        $request->validate([
            'quantity_received' => 'nullable|array',
            'quantity_received.*' => 'nullable|integer|min:0',
            'receiver_notes' => 'nullable|string|max:1000',
            'proof_photo' => 'nullable|image|mimes:jpeg,png,webp,gif|max:4096',
        ]);

        $quantities = $request->input('quantity_received', []);
        $receiverNotes = $request->input('receiver_notes');

        DB::beginTransaction();
        try {
            $transfer->receive(Auth::id(), $quantities, $receiverNotes);

            // Guardar foto si se adjunta
            if ($request->hasFile('proof_photo')) {
                $file = $request->file('proof_photo');
                $path = $file->store("transfers/{$transfer->id}", 'public');

                \App\Models\File::create([
                    'fileable_type' => Transfer::class,
                    'fileable_id'   => $transfer->id,
                    'type'          => 'transfer_proof',
                    'name'          => $file->getClientOriginalName(),
                    'path'          => $path,
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }

            DB::commit();
            Transfer::flushCache();

            // Mensaje de éxito dinámico según el estado final del traspaso
            $statusMsg = $transfer->status === 'devolucion'
                ? "recibido con diferencias (estado Devolución). La mercancía faltante fue devuelta automáticamente al stock de {$transfer->originStore->name}."
                : "recibido correctamente. Stock sumado a {$transfer->destinationStore->name}.";

            return redirect()->route('transfers.show', $transfer)
                ->with('success', "Traspaso {$transfer->folio} {$statusMsg}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /** Sucursal destino rechaza el traspaso (En Tránsito → Rechazado) */
    public function reject(Request $request, Transfer $transfer)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $storeId = current_store_id();
        if (!$storeId || $transfer->destination_store_id !== (int)$storeId) {
            return back()->with('error', 'Solo la sucursal de destino puede rechazar este traspaso.');
        }

        try {
            $transfer->reject(Auth::id(), $request->rejection_reason);
            Transfer::flushCache();

            return redirect()->route('transfers.show', $transfer)
                ->with('success', "Traspaso {$transfer->folio} rechazado. Stock devuelto a {$transfer->originStore->name}.");

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** Sucursal origen cancela el traspaso (Pendiente → Cancelado) */
    public function cancel(Transfer $transfer)
    {
        $storeId = current_store_id();
        if (!$storeId || $transfer->origin_store_id !== (int)$storeId) {
            return back()->with('error', 'Solo la sucursal de origen puede cancelar este traspaso.');
        }

        try {
            $transfer->cancel(Auth::id());
            Transfer::flushCache();

            return redirect()->route('transfers.show', $transfer)
                ->with('success', "Traspaso {$transfer->folio} cancelado.");

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** AJAX: stock disponible de un producto en la sucursal activa */
    public function productStock(Request $request)
    {
        $storeId   = current_store_id();
        $productId = $request->integer('product_id');
        $variantId = $request->input('variant_id');

        $query = Inventory::where('store_id', $storeId)
            ->where('product_id', $productId);

        $variantId
            ? $query->where('product_variant_id', $variantId)
            : $query->whereNull('product_variant_id');

        $inventory = $query->first();

        return response()->json([
            'quantity' => $inventory?->quantity ?? 0,
        ]);
    }
}
