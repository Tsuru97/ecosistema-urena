<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-catalogo')->only(['index', 'show']);
        $this->middleware('permission:editar-catalogo')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $cacheKey = 'brands.index.' . md5(json_encode(array_merge($request->all(), ['store_id' => current_store_id()])));

        $brands = Brand::rememberWithTags($cacheKey, function () use ($request) {
            $query = Brand::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status === 'active');
            }

            $sortField = $request->get('sort_field', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSorts = ['id', 'name', 'status', 'created_at'];

            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('id', 'desc');
            }

            return $query->paginate(15);
        });
        return view('admin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = $request->has('status');

        $brand = Brand::create($validated);

        if ($request->has('seo')) {
            $brand->updateSeo($request->input('seo'));
        }

        return redirect()->route('brands.index')->with('success', 'Marca creada exitosamente.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = $request->has('status');

        $brand->update($validated);

        if ($request->has('seo')) {
            $brand->updateSeo($request->input('seo'));
        }

        return redirect()->route('brands.index')->with('success', 'Marca actualizada exitosamente.');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Marca eliminada exitosamente.');
    }
}
