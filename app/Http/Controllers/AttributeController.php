<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-catalogo')->only(['index']);
        $this->middleware('permission:editar-catalogo')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    // ──────────────────────────────────────────────────────
    // Listado de atributos
    // ──────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('search');

        $cacheKey = 'attributes.index.' . md5($search ?? '');

        $attributes = Attribute::rememberWithTags($cacheKey, function () use ($search) {
            $query = Attribute::withCount('values')->withTrashed(false);

            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }

            return $query->orderBy('order')->orderBy('name')->paginate(20)->withQueryString();
        });

        return view('admin.attributes.index', compact('attributes', 'search'));
    }

    // ──────────────────────────────────────────────────────
    // Crear atributo
    // ──────────────────────────────────────────────────────

    public function create()
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request)
    {
        // Filtrar filas sin valor antes de validar (filas vacías dejadas por el usuario)
        $filteredValues = collect($request->input('values', []))
            ->filter(fn($v) => !blank($v['value'] ?? null))
            ->values()
            ->toArray();
        $request->merge(['values' => $filteredValues]);

        $request->validate([
            'name'         => 'required|string|max:100|unique:attributes,name',
            'type'         => 'required|in:select,color,text',
            'is_variation' => 'boolean',
            'order'        => 'nullable|integer|min:0',
            'status'       => 'boolean',
            'values'       => 'required|array|min:1',
            'values.*.value'     => 'required|string|max:100',
            'values.*.color_hex' => 'nullable|string|max:7',
            'values.*.order'     => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $attribute = Attribute::create([
                'name'         => $request->name,
                'type'         => $request->type,
                'is_variation' => $request->boolean('is_variation', true),
                'order'        => $request->input('order', 0),
                'status'       => $request->boolean('status', true),
            ]);

            foreach ($request->values as $i => $val) {
                if (blank($val['value'] ?? null)) continue;

                $attribute->values()->create([
                    'value'     => $val['value'],
                    'slug'      => static::uniqueValueSlug($attribute->id, $val['value']),
                    'color_hex' => ($request->type === 'color') ? ($val['color_hex'] ?? null) : null,
                    'order'     => $val['order'] ?? $i,
                ]);
            }

            DB::commit();
            Attribute::flushCache();

            return redirect()->route('attributes.index')
                ->with('success', "Atributo \"{$attribute->name}\" creado con {$attribute->values->count()} valores.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el atributo: ' . $e->getMessage())->withInput();
        }
    }

    // ──────────────────────────────────────────────────────
    // Editar atributo
    // ──────────────────────────────────────────────────────

    public function edit(Attribute $attribute)
    {
        $attribute->load(['values' => fn($q) => $q->orderBy('order')]);
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        // Filtrar filas sin valor antes de validar
        $filteredValues = collect($request->input('values', []))
            ->filter(fn($v) => !blank($v['value'] ?? null))
            ->values()
            ->toArray();
        $request->merge(['values' => $filteredValues]);

        $request->validate([
            'name'         => 'required|string|max:100|unique:attributes,name,' . $attribute->id,
            'type'         => 'required|in:select,color,text',
            'is_variation' => 'boolean',
            'order'        => 'nullable|integer|min:0',
            'status'       => 'boolean',
            'values'       => 'required|array|min:1',
            'values.*.id'        => 'nullable|integer|exists:attribute_values,id',
            'values.*.value'     => 'required|string|max:100',
            'values.*.color_hex' => 'nullable|string|max:7',
            'values.*.order'     => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $attribute->update([
                'name'         => $request->name,
                'type'         => $request->type,
                'is_variation' => $request->boolean('is_variation', true),
                'order'        => $request->input('order', 0),
                'status'       => $request->boolean('status', true),
            ]);

            // IDs que vienen del form (valores existentes a mantener)
            $keepIds = collect($request->values)->pluck('id')->filter()->values();

            // Eliminar valores que no están en el form
            $attribute->values()->whereNotIn('id', $keepIds)->delete();

            // Upsert de cada valor
            foreach ($request->values as $i => $val) {
                if (blank($val['value'] ?? null)) continue;

                if (!empty($val['id'])) {
                    AttributeValue::where('id', $val['id'])->update([
                        'value'     => $val['value'],
                        'color_hex' => $val['color_hex'] ?? null,
                        'order'     => $val['order'] ?? $i,
                    ]);
                } else {
                    $attribute->values()->create([
                        'value'     => $val['value'],
                        'slug'      => static::uniqueValueSlug($attribute->id, $val['value']),
                        'color_hex' => ($request->type === 'color') ? ($val['color_hex'] ?? null) : null,
                        'order'     => $val['order'] ?? $i,
                    ]);
                }
            }

            DB::commit();
            Attribute::flushCache();

            return redirect()->route('attributes.index')
                ->with('success', "Atributo \"{$attribute->name}\" actualizado.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage())->withInput();
        }
    }

    // ──────────────────────────────────────────────────────
    // Eliminar atributo
    // ──────────────────────────────────────────────────────

    public function destroy(Attribute $attribute)
    {
        // Verificar si está en uso en algún producto
        if ($attribute->products()->exists()) {
            return back()->with('error',
                "No se puede eliminar \"{$attribute->name}\" porque está asignado a uno o más productos.");
        }

        $attribute->values()->delete();
        $attribute->delete();
        Attribute::flushCache();

        return redirect()->route('attributes.index')
            ->with('success', "Atributo \"{$attribute->name}\" eliminado.");
    }

    // ──────────────────────────────────────────────────────
    // AJAX — Todos los atributos activos (para selector en producto)
    // ──────────────────────────────────────────────────────

    public function allActive()
    {
        return response()->json(
            Attribute::where('status', true)
                ->orderBy('order')->orderBy('name')
                ->get(['id', 'name', 'type'])
        );
    }

    // ──────────────────────────────────────────────────────
    // AJAX — Valores de un atributo (para uso en variantes)
    // ──────────────────────────────────────────────────────

    public function values(Attribute $attribute)
    {
        return response()->json(
            $attribute->values()->orderBy('order')->get(['id', 'value', 'color_hex'])
        );
    }

    // ──────────────────────────────────────────────────────
    // Helper — slug único por atributo (respeta UNIQUE attribute_id+slug)
    // ──────────────────────────────────────────────────────

    protected static function uniqueValueSlug(int $attributeId, string $value): string
    {
        $base  = Str::slug($value);
        $slug  = $base ?: Str::random(8);
        $count = 1;

        while (AttributeValue::where('attribute_id', $attributeId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count++;
        }

        return $slug;
    }
}
