<?php

namespace App\Ai\Tools;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;

class GetStockTool implements AiToolInterface
{
    public function getName(): string
    {
        return "get_stock_status";
    }

    public function getDescription(): string
    {
        return "Obtiene el stock actual en tiempo real de un SKU específico en todas las sucursales del sistema.";
    }

    public function getKeywords(): array
    {
        return ["stock", "existencias", "sucursales", "almacen", "cantidad", "inventario", "unidad"];
    }

    public function getParametersSchema(): array
    {
        return [
            "sku" => [
                "type" => "string",
                "description" => "El SKU exacto del producto o variante (ej. SILLA-RUSTICA-01)"
            ]
        ];
    }

    public function execute(array $arguments)
    {
        $sku = $arguments['sku'] ?? null;
        if (!$sku) {
            return ['error' => 'Debes proporcionar un SKU válido.'];
        }

        // 1. Buscar en catálogo general (Producto simple)
        $product = Product::where('sku', $sku)->first();
        
        if ($product) {
            $inventories = Inventory::where('product_id', $product->id)
                ->with('store:id,name')
                ->get();
        } else {
            // 2. Si no es simple, buscar en variantes
            $variant = ProductVariant::where('sku', $sku)
                ->with('product')
                ->first();

            if ($variant) {
                $inventories = Inventory::where('product_variant_id', $variant->id)
                    ->with('store:id,name')
                    ->get();
            } else {
                return ['error' => "No se encontró ningún producto o variante con el SKU: {$sku}"];
            }
        }

        if ($inventories->isEmpty()) {
            return [
                'sku' => $sku,
                'mensaje' => 'El producto existe pero no tiene ningún registro de inventario inicializado.',
                'existencias' => []
            ];
        }

        $stockDetails = $inventories->map(function ($inv) {
            return [
                'sucursal' => $inv->store->name,
                'cantidad_actual' => $inv->quantity,
                'stock_seguridad_minimo' => $inv->min_stock,
                'en_alerta_minimo' => $inv->isAlert()
            ];
        });

        return [
            'sku' => $sku,
            'nombre_producto' => $product ? $product->name : $variant->getDisplayNameAttribute(),
            'existencias' => $stockDetails->toArray()
        ];
    }
}
