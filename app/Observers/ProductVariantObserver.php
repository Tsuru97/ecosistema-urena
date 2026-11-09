<?php

namespace App\Observers;

use App\Models\ProductVariant;

class ProductVariantObserver
{
    public function created(ProductVariant $variant): void
    {
        $this->flushRelated($variant);
    }

    public function updated(ProductVariant $variant): void
    {
        $this->flushRelated($variant);
    }

    public function deleted(ProductVariant $variant): void
    {
        $this->flushRelated($variant);
    }

    public function restored(ProductVariant $variant): void
    {
        $this->flushRelated($variant);
    }

    private function flushRelated(ProductVariant $variant): void
    {
        // Invalida el cache del producto padre (que incluye sus variantes)
        \App\Models\Product::flushCache();
        // Invalida el inventario ya que el stock por variante puede cambiar
        \App\Models\Inventory::flushCache();
    }
}
