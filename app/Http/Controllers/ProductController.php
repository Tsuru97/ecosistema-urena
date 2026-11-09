<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-catalogo')->only(['index', 'show']);
        $this->middleware('permission:editar-catalogo')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $cacheKey = 'products.index.' . md5(json_encode(array_merge($request->all(), ['store_id' => current_store_id()])));

        $products = Product::rememberWithTags($cacheKey, function () use ($request) {
            $query = Product::with(['categories', 'brand', 'supplier', 'files']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status === 'active');
            }

            if ($request->filled('category_id')) {
                $query->whereHas('categories', function($q) use ($request) {
                    $q->where('categories.id', $request->category_id);
                });
            }

            if ($request->filled('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            $sortField = $request->get('sort_field', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSorts = ['id', 'name', 'sku', 'base_cost', 'status', 'created_at'];

            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('id', 'desc');
            }

            return $query->paginate(15);
        });
        $categories = Category::where('status', true)->orderBy('name')->get();
        $brands     = Brand::where('status', true)->orderBy('name')->get();
        $suppliers  = \App\Models\Supplier::where('status', true)->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories', 'brands', 'suppliers'));
    }

    public function create()
    {
        $categories = Category::where('status', true)->orderBy('name')->get();
        $brands     = Brand::where('status', true)->orderBy('name')->get();
        $suppliers  = \App\Models\Supplier::where('status', true)->orderBy('name')->get();
        return view('admin.products.create', compact('categories', 'brands', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:products|max:100',
            'name' => 'required|string|max:255',
            'base_cost' => 'required|numeric|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string',
            'ieps_type' => 'nullable|in:exempt,percentage,fixed_quota',
            'ieps_value' => 'nullable|numeric|min:0',
        ]);

        $validated['status'] = $request->has('status');
        $validated['ieps_type'] = $request->input('ieps_type', 'exempt');
        $validated['ieps_value'] = (float)$request->input('ieps_value', 0);

        $product = Product::create($validated);
        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        // Flush explícito: sync() modifica la tabla pivote sin disparar el evento
        // 'updated' del modelo, por lo que el observer no se ejecutaría.
        Product::flushCache();

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $uploadedFile) {
                $extension = $uploadedFile->getClientOriginalExtension();
                $filename = Str::uuid() . '.' . $extension;
                $path = $uploadedFile->storeAs('uploads/product/' . $product->id, $filename, 'public');

                \App\Models\File::create([
                    'fileable_type' => get_class($product),
                    'fileable_id' => $product->id,
                    'type' => 'gallery',
                    'name' => $uploadedFile->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $uploadedFile->getMimeType(),
                    'size' => $uploadedFile->getSize(),
                ]);
            }
        }

        if ($request->has('seo')) {
            $product->updateSeo($request->input('seo'));
        }

        return redirect()->route('products.index')->with('success', 'Producto creado exitosamente.');
    }

    public function show(Product $product)
    {
        $product->load([
            'categories', 'brand', 'supplier', 'files',
            'variants' => fn ($q) => $q->orderByDesc('is_default')->orderBy('id'),
            'variants.attributeValues.attribute',
            'variants.files',
        ]);
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('status', true)->orderBy('name')->get();
        $brands     = Brand::where('status', true)->orderBy('name')->get();
        $suppliers  = \App\Models\Supplier::where('status', true)->orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories', 'brands', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku'          => 'required|string|max:100|unique:products,sku,' . $product->id,
            'name'         => 'required|string|max:255',
            'base_cost'    => 'required|numeric|min:0',
            'categories'   => 'required|array',
            'categories.*' => 'exists:categories,id',
            'brand_id'     => 'required|exists:brands,id',
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'description'  => 'nullable|string',
            'type'         => 'nullable|in:simple,variable',
            'ieps_type'    => 'nullable|in:exempt,percentage,fixed_quota',
            'ieps_value'   => 'nullable|numeric|min:0',
        ]);

        $validated['status'] = $request->has('status');
        $validated['type']   = $request->input('type', 'simple');
        $validated['ieps_type']  = $request->input('ieps_type', 'exempt');
        $validated['ieps_value'] = (float)$request->input('ieps_value', 0);

        $product->update($validated);

        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        // ── Sincronizar atributos (tab Atributos) ──────────────────────
        if ($request->filled('attributes') || $validated['type'] === 'variable') {
            $attributesInput = $request->input('attributes', []);
            $syncData = [];

            foreach ($attributesInput as $attrId => $attrData) {
                $syncData[$attrId] = [
                    'is_for_variation' => isset($attrData['is_for_variation']) ? 1 : 0,
                    'order'            => 0,
                ];
            }

            $product->attributes()->sync($syncData);
        }

        // Flush explícito: categorías y atributos no disparan 'updated' en el modelo
        Product::flushCache();

        if ($request->has('seo')) {
            $product->updateSeo($request->input('seo'));
        }

        return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Product $product)
    {
        if ($product->is_wildcard) {
            return back()->with('error', 'El producto comodín "Producto Personalizado" es una entidad protegida del sistema y no se puede eliminar.');
        }

        $product->delete();
        Product::flushCache();
        return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente.');
    }

    // ══════════════════════════════════════════════════════
    // Variantes — endpoints AJAX
    // ══════════════════════════════════════════════════════

    /**
     * GET /products/{product}/variants
     * Devuelve las variantes del producto con sus atributos, para poblar la tabla JS.
     */
    public function variantsList(Product $product)
    {
        $storeId = request()->input('store_id') ?: current_store_id();

        $variants = $product->variants()
            ->with('attributeValues.attribute')
            ->withCount('files')
            ->orderBy('is_default', 'desc')
            ->orderBy('id')
            ->get();

        // Obtener stock por variante en la sucursal activa (si aplica)
        $stockMap = [];
        if ($storeId) {
            $stockMap = \App\Models\Inventory::where('store_id', $storeId)
                ->where('product_id', $product->id)
                ->whereNotNull('product_variant_id')
                ->pluck('quantity', 'product_variant_id')
                ->toArray();
        }

        return response()->json($variants->map(fn ($v) => [
            'id'           => $v->id,
            'sku'          => $v->sku,
            'cost'         => $v->cost,
            'is_default'   => (bool) $v->is_default,
            'is_active'    => (bool) $v->is_active,
            'images_count' => $v->files_count ?? 0,
            'stock'        => $stockMap[$v->id] ?? null,
            'display_name' => $v->attributeValues
                ->sortBy('attribute.order')
                ->map(fn ($av) => $av->value)
                ->join(' / '),
            'attributes'   => $v->attributeValues->map(fn ($av) => [
                'attr'      => $av->attribute->name ?? '',
                'value'     => $av->value,
                'color_hex' => $av->color_hex,
            ]),
        ]));
    }

    /**
     * POST /products/{product}/variants/generate
     * Genera todas las combinaciones posibles de atributos marcados is_for_variation.
     * Salta combinaciones que ya existen.
     */
    public function generateVariants(Product $product)
    {
        // Leer atributos de variación directamente desde la tabla pivot
        // (evita incompatibilidad de wherePivot con ->using(ProductAttribute::class))
        $pivotRows = \DB::table('product_attribute')
            ->where('product_id', $product->id)
            ->where('is_for_variation', true)
            ->pluck('attribute_id');

        if ($pivotRows->isEmpty()) {
            return response()->json([
                'error' => 'No hay atributos marcados como "Para variación" en este producto.',
            ], 422);
        }

        // Cargar atributos con sus valores
        $varAttrs = \App\Models\Attribute::whereIn('id', $pivotRows)
            ->with('values')
            ->get();

        if ($varAttrs->isEmpty()) {
            return response()->json(['error' => 'No se encontraron atributos de variación.'], 422);
        }

        // Construir producto cartesiano
        $sets = $varAttrs->map(fn ($a) => $a->values->toArray())->toArray();

        if (collect($sets)->contains(fn ($s) => empty($s))) {
            return response()->json([
                'error' => 'Uno o más atributos de variación no tienen valores definidos.',
            ], 422);
        }

        $combinations = $this->cartesianProduct($sets);

        // Fingerprints de combinaciones existentes (sorted value IDs como string)
        $existing = $product->variants()
            ->with('attributeValues:id')
            ->get()
            ->map(fn ($v) => $v->attributeValues->pluck('id')->sort()->values()->join(','))
            ->flip(); // key => true para lookup O(1)

        $created = 0;
        foreach ($combinations as $combo) {
            $valueIds  = collect($combo)->pluck('id')->sort()->values();
            $fingerprint = $valueIds->join(',');

            if ($existing->has($fingerprint)) continue;

            $slugParts = collect($combo)->pluck('slug')->join('-');
            $baseSku   = Str::limit($product->sku . '-' . $slugParts, 95, '');
            $sku       = $this->uniqueVariantSku($baseSku);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku'        => $sku,
                'is_default' => false,
                'is_active'  => true,
            ]);

            $variant->attributeValues()->attach($valueIds->toArray());
            $existing->put($fingerprint, true); // evitar duplicados en la misma corrida
            $created++;
        }

        Product::flushCache();

        return response()->json([
            'created' => $created,
            'message' => $created > 0
                ? "{$created} variante(s) generada(s) exitosamente."
                : 'Todas las combinaciones ya existen.',
        ]);
    }

    /**
     * POST /products/{product}/variants/save
     * Actualización masiva de variantes (SKU, precio, costo, activo, defecto).
     */
    public function saveVariants(Request $request, Product $product)
    {
        $request->validate([
            'variants'               => 'required|array|min:1',
            'variants.*.id'          => 'required|integer|exists:product_variants,id',
            'variants.*.sku'         => 'required|string|max:100',
            'variants.*.cost'        => 'nullable|numeric|min:0',
            'variants.*.is_active'   => 'nullable|boolean',
            'variants.*.is_default'  => 'nullable|boolean',
        ]);

        // Solo puede haber un defecto por producto
        $defaultId = collect($request->variants)->firstWhere('is_default', true)['id'] ?? null;

        foreach ($request->variants as $vData) {
            ProductVariant::where('id', $vData['id'])
                ->where('product_id', $product->id)
                ->update([
                    'sku'        => $vData['sku'],
                    'cost'       => filled($vData['cost'] ?? null)  ? $vData['cost']  : null,
                    'is_active'  => (bool) ($vData['is_active']  ?? false),
                    'is_default' => $defaultId && (int)$vData['id'] === (int)$defaultId,
                ]);
        }

        Product::flushCache();

        return response()->json(['success' => true, 'message' => 'Variantes guardadas exitosamente.']);
    }

    /**
     * DELETE /products/{product}/variants/{variant}
     */
    public function deleteVariant(Product $product, ProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 403);
        $variant->delete();
        return response()->json(['success' => true]);
    }

    /**
     * POST /products/{product}/variants/manual
     * Crea una variante única con los atributos de variación elegidos (opcionales).
     */
    public function createVariant(Request $request, Product $product)
    {
        $request->validate([
            'sku'         => ['required', 'string', 'max:100',
                                Rule::unique('product_variants', 'sku')->whereNull('deleted_at')],
            'cost'        => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
            'value_ids'   => 'nullable|array',
            'value_ids.*' => 'integer|exists:attribute_values,id',
        ]);

        // Solo aceptar value_ids que pertenezcan a atributos de variación del producto
        $allowedAttrIds = \DB::table('product_attribute')
            ->where('product_id', $product->id)
            ->where('is_for_variation', true)
            ->pluck('attribute_id');

        $safeValueIds = collect($request->value_ids ?? [])
            ->filter(function ($vid) use ($allowedAttrIds) {
                $attrId = \App\Models\AttributeValue::find($vid)?->attribute_id;
                return $attrId && $allowedAttrIds->contains($attrId);
            })
            ->values()
            ->toArray();

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => $request->sku,
            'cost'       => $request->cost  ?: null,
            'is_default' => false,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        if ($safeValueIds) {
            $variant->attributeValues()->attach($safeValueIds);
        }

        $variant->load('attributeValues.attribute');
        Product::flushCache();

        return response()->json([
            'success' => true,
            'variant' => [
                'id'           => $variant->id,
                'sku'          => $variant->sku,
                'cost'         => $variant->cost,
                'is_default'   => false,
                'is_active'    => (bool) $variant->is_active,
                'images_count' => 0,
                'display_name' => $variant->attributeValues
                    ->sortBy('attribute.order')
                    ->map(fn ($av) => $av->value)
                    ->join(' / '),
                'attributes'   => $variant->attributeValues->map(fn ($av) => [
                    'attr'      => $av->attribute->name ?? '',
                    'value'     => $av->value,
                    'color_hex' => $av->color_hex,
                ]),
            ],
        ]);
    }

    // ── Imágenes por Variante ─────────────────────────────────────────

    /**
     * GET /products/{product}/variants/{variant}/images
     * Lista las imágenes de la variante. Si no tiene, hereda las del producto base.
     */
    public function variantImages(Product $product, ProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 403);

        $files = $variant->files()->orderBy('id')->get();

        $inherited = false;
        if ($files->isEmpty()) {
            $files    = $product->files()->orderBy('id')->get();
            $inherited = true;
        }

        return response()->json([
            'images'    => $files->map(fn ($f) => [
                'id'  => $f->id,
                'url' => asset('storage/' . $f->path),
                'name' => $f->name,
            ]),
            'inherited' => $inherited,
        ]);
    }

    /**
     * POST /products/{product}/variants/{variant}/images
     * Sube una imagen a la variante.
     */
    public function uploadVariantImage(Request $request, Product $product, ProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 403);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,webp,gif|max:4096',
        ]);

        $file = $request->file('image');
        $path = $file->store("variants/{$variant->id}", 'public');

        $record = \App\Models\File::create([
            'fileable_type' => ProductVariant::class,
            'fileable_id'   => $variant->id,
            'type'          => 'image',
            'name'          => $file->getClientOriginalName(),
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'image'   => [
                'id'  => $record->id,
                'url' => asset('storage/' . $path),
                'name' => $record->name,
            ],
        ]);
    }

    /**
     * DELETE /products/{product}/variants/{variant}/images/{file}
     * Elimina una imagen específica de la variante.
     */
    public function deleteVariantImage(Product $product, ProductVariant $variant, \App\Models\File $file)
    {
        abort_if($variant->product_id !== $product->id, 403);
        abort_if($file->fileable_id !== $variant->id || $file->fileable_type !== ProductVariant::class, 403);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($file->path);
        $file->delete();

        return response()->json(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function cartesianProduct(array $sets): array
    {
        if (empty($sets)) return [[]];
        $first = array_shift($sets);
        $rest  = $this->cartesianProduct($sets);
        $result = [];
        foreach ($first as $item) {
            foreach ($rest as $combo) {
                $result[] = array_merge([$item], $combo);
            }
        }
        return $result;
    }

    private function uniqueVariantSku(string $base): string
    {
        $sku   = $base;
        $count = 1;
        // Ignorar soft-deleted: solo verificar contra variantes activas (no eliminadas)
        while (ProductVariant::withoutTrashed()->where('sku', $sku)->exists()) {
            $sku = $base . '-' . $count++;
        }
        return $sku;
    }
}
