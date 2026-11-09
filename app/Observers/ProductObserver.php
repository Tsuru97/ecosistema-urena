<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;

class ProductObserver
{
    public function created(Product $product): void
    {
        $this->clearRelatedCaches($product);
    }

    public function updated(Product $product): void
    {
        $this->clearRelatedCaches($product);
    }

    public function deleting(Product $product): bool
    {
        if ($product->is_wildcard) {
            throw new \Exception('No se puede eliminar el producto comodín protegido del sistema.');
        }
        return true;
    }

    public function deleted(Product $product): void
    {
        $this->clearRelatedCaches($product);
    }

    public function restored(Product $product): void
    {
        $this->clearRelatedCaches($product);
    }

    protected function clearRelatedCaches(Product $product): void
    {
        Product::flushCache();
        // Al cambiar un producto, también se invalida caché de categorías y marcas
        // por si el index de estas muestra conteos o relaciones.
        Category::flushCache();
        Brand::flushCache();
    }
}
