<?php

namespace App\Ai\Tools;

use App\Models\Product;
use App\Models\ProductVariant;

class SearchProductsTool implements AiToolInterface
{
    public function getName(): string
    {
        return "search_products";
    }

    public function getDescription(): string
    {
        return "Busca productos o variantes en el catálogo del ERP que coincidan con un término o SKU de búsqueda.";
    }

    public function getKeywords(): array
    {
        return ["buscar", "producto", "modelo", "catalogo", "sku", "precio", "nombre", "variante"];
    }

    public function getParametersSchema(): array
    {
        return [
            "term" => [
                "type" => "string",
                "description" => "Término de búsqueda, puede ser parte del nombre o del SKU (ej. Mesa o MESA-01)"
            ]
        ];
    }

    public function execute(array $arguments)
    {
        $term = $arguments['term'] ?? null;
        if (!$term) {
            return ['error' => 'Debes proporcionar un término de búsqueda válido.'];
        }

        // 1. Buscar en catálogo general (Productos)
        $products = Product::where('name', 'like', "%{$term}%")
            ->orWhere('sku', 'like', "%{$term}%")
            ->select('id', 'name', 'sku', 'base_cost', 'type')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                return [
                    'tipo' => 'producto',
                    'nombre' => $p->name,
                    'sku' => $p->sku,
                    'precio_base_costo' => $p->base_cost,
                    'es_variable' => $p->isVariable()
                ];
            });

        // 2. Buscar en variantes
        $variants = ProductVariant::where('sku', 'like', "%{$term}%")
            ->with('product')
            ->limit(5)
            ->get()
            ->map(function ($v) {
                return [
                    'tipo' => 'variante',
                    'nombre' => $v->getDisplayNameAttribute(),
                    'sku' => $v->sku,
                    'precio_base_costo' => $v->price ?? optional($v->product)->base_cost,
                    'es_variable' => false
                ];
            });

        $merged = $products->merge($variants)->unique('sku')->slice(0, 5)->values();

        return [
            'termino_buscado' => $term,
            'coincidencias_encontradas' => count($merged),
            'resultados' => $merged->toArray()
        ];
    }
}
