<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-inventario')->only(['index', 'movements']);
        $this->middleware('permission:gestionar-inventario')->only(['adjust', 'storeAdjust', 'setMinStock']);
    }

    /**
     * Listado de inventario por sucursal con alertas y búsqueda.
     */
    public function index(Request $request)
    {
        $search      = $request->input('search');
        $alertOnly   = $request->boolean('alerts');
        $warehouseId = $request->input('warehouse_id');
        $isGlobal    = is_global_view() && Auth::user()->can('vista-global');
        $storeId     = $isGlobal ? null : current_store_id();

        if ($storeId) {
            \App\Models\Warehouse::ensureDefaultWarehousesForStore($storeId);
        }

        $cacheKey = 'inventory.index.' . md5(json_encode([$storeId, $warehouseId, $search, $alertOnly]));

        // Traer todos los inventarios agrupables, con bodega, variante y product
        $inventories = Inventory::rememberWithTags($cacheKey, function () use (
            $storeId, $warehouseId, $search, $alertOnly
        ) {
            $query = Inventory::with([
                    'warehouse',
                    'product.brand',
                    'store',
                    'variant.attributeValues.attribute',
                ])
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->select('inventories.*');

            if ($storeId) {
                $query->where('inventories.store_id', $storeId);
            }

            if ($warehouseId) {
                $query->where('inventories.warehouse_id', $warehouseId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('products.name', 'like', '%' . $search . '%')
                      ->orWhere('products.sku', 'like', '%' . $search . '%');
                });
            }

            if ($alertOnly) {
                $query->alerts();
            }

            return $query->orderBy('products.name', 'asc')
                         ->orderByRaw('inventories.product_variant_id IS NULL DESC') // simple primero
                         ->orderBy('inventories.product_variant_id', 'asc')
                         ->paginate(30)->withQueryString();
        });

        // Agrupar por store_id, warehouse_id y product_id
        $grouped = $inventories->getCollection()->groupBy(function($item) use ($isGlobal) {
            return $isGlobal ? ($item->store_id . '_' . ($item->warehouse_id ?? 0) . '_' . $item->product_id) : (($item->warehouse_id ?? 0) . '_' . $item->product_id);
        });

        // Conteo de alertas
        $alertCount = Inventory::when($storeId, fn($q) => $q->forStore($storeId))->alerts()->count();

        $stores = $isGlobal ? Store::orderBy('name')->get() : collect();
        $warehouses = $storeId ? \App\Models\Warehouse::where('store_id', $storeId)->get() : \App\Models\Warehouse::orderBy('name')->get();

        return view('admin.inventory.index', compact(
            'inventories', 'grouped', 'search',
            'alertOnly', 'alertCount', 'isGlobal', 'stores', 'warehouses', 'warehouseId'
        ));
    }

    /**
     * Formulario de ajuste manual de stock.
     */
    public function adjust(Request $request, ?int $productId = null)
    {
        $isGlobal = is_global_view() && Auth::user()->can('vista-global');
        $storeId  = $isGlobal ? $request->input('store_id') : current_store_id();

        if (!$isGlobal && !$storeId) {
            return redirect()->route('inventory.index')
                ->with('error', 'Debes seleccionar una sucursal activa para realizar ajustes.');
        }

        $product = $productId ? Product::with('variants.attributeValues.attribute')->findOrFail($productId) : null;

        // Si el producto y la sucursal están seleccionados, cargar los inventarios por variante
        $variantInventories = collect();
        $inventory = null;

        if ($storeId && $product) {
            if ($product->isVariable()) {
                $variants = $product->variants()->where('is_active', true)->with('attributeValues.attribute')->get();
                foreach ($variants as $v) {
                    $inv = Inventory::firstOrCreate(
                        ['store_id' => $storeId, 'product_id' => $product->id, 'product_variant_id' => $v->id],
                        ['quantity' => 0, 'min_stock' => 0]
                    );
                    $inv->setRelation('variant', $v);
                    $variantInventories->push($inv);
                }
            } else {
                // Inventario del producto simple (sin variante)
                $inventory = Inventory::firstOrCreate(
                    ['store_id' => $storeId, 'product_id' => $product->id, 'product_variant_id' => null],
                    ['quantity' => 0, 'min_stock' => 0]
                );
            }
        }

        $products = Product::where('status', true)->orderBy('name')->get();
        $stores = $isGlobal ? Store::where('status', true)->orderBy('name')->get() : collect();

        return view('admin.inventory.adjust', compact(
            'product', 'inventory', 'products', 'storeId', 'variantInventories', 'isGlobal', 'stores'
        ));
    }

    /**
     * Procesar ajuste manual de stock.
     */
    public function storeAdjust(Request $request)
    {
        $request->validate([
            'product_id'         => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'warehouse_id'       => 'nullable|integer|exists:warehouses,id',
            'type'               => 'required|in:entrada,salida,ajuste',
            'quantity'           => 'required|integer|min:0',
            'notes'              => 'nullable|string|max:500',
            'min_stock'          => 'nullable|integer|min:0',
            'store_id'           => 'nullable|integer|exists:stores,id',
        ]);

        $isGlobal = is_global_view() && Auth::user()->can('vista-global');
        $storeId = $isGlobal ? $request->input('store_id') : current_store_id();

        if (!$storeId) {
            return back()->with('error', 'Debes seleccionar una sucursal para realizar el ajuste.')->withInput();
        }

        // Si el producto es variable, la variante es obligatoria
        $product = Product::findOrFail($request->product_id);
        if ($product->isVariable() && !$request->filled('product_variant_id')) {
            return back()->with('error', 'Debes seleccionar una variante para productos variables.')->withInput();
        }

        DB::beginTransaction();
        try {
            $inventory = Inventory::applyMovement(
                storeId:          $storeId,
                productId:        $request->product_id,
                type:             $request->type,
                quantity:         $request->quantity,
                userId:           Auth::id(),
                notes:            $request->notes ?? 'Ajuste manual',
                productVariantId: $request->product_variant_id ?: null,
                warehouseId:      $request->warehouse_id ?: null,
            );

            if ($request->filled('min_stock')) {
                $inventory->update(['min_stock' => $request->min_stock]);
            }

            DB::commit();

            return redirect()->route('inventory.index')
                ->with('success', 'Ajuste de inventario registrado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar el ajuste: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Actualizar solo el stock mínimo de un producto (inline vía AJAX o form).
     */
    public function setMinStock(Request $request, Inventory $inventory)
    {
        $request->validate(['min_stock' => 'required|integer|min:0']);

        $inventory->update(['min_stock' => $request->min_stock]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'min_stock' => $inventory->min_stock]);
        }

        return back()->with('success', 'Stock mínimo actualizado.');
    }

    /**
     * Historial de movimientos de un producto en la sucursal activa.
     */
    public function movements(Request $request, Product $product)
    {
        $storeId = is_global_view() ? null : current_store_id();

        $movements = InventoryMovement::with(['user', 'reference', 'variant.attributeValues.attribute', 'store'])
            ->when($storeId, fn($q) => $q->forStore($storeId))
            ->forProduct($product->id)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $inventory = $storeId
            ? Inventory::where('store_id', $storeId)->where('product_id', $product->id)->first()
            : null;

        return view('admin.inventory.movements', compact('product', 'movements', 'inventory', 'storeId'));
    }
}
