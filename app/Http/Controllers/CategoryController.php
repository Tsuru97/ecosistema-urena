<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-catalogo')->only(['index', 'show']);
        $this->middleware('permission:editar-catalogo')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $cacheKey = 'categories.index.' . md5(json_encode(array_merge($request->all(), ['store_id' => current_store_id()])));

        $categories = Category::rememberWithTags($cacheKey, function () use ($request) {
            $query = Category::with('parent');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status === 'active');
            }

            if ($request->filled('parent')) {
                if ($request->parent === 'root') {
                    $query->whereNull('parent_id');
                } elseif ($request->parent === 'sub') {
                    $query->whereNotNull('parent_id');
                }
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
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parentCategories = Category::where('status', true)->get();
        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = $request->has('status');

        $category = Category::create($validated);

        if ($request->has('seo')) {
            $category->updateSeo($request->input('seo'));
        }

        return redirect()->route('categories.index')->with('success', 'Categoría creada exitosamente.');
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::where('id', '!=', $category->id)
            ->where('status', true)->get();
            
        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = $request->has('status');

        $category->update($validated);

        if ($request->has('seo')) {
            $category->updateSeo($request->input('seo'));
        }

        return redirect()->route('categories.index')->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Categoría eliminada exitosamente.');
    }
}
