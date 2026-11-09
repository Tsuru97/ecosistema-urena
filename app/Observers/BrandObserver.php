<?php

namespace App\Observers;

use App\Models\Brand;
use App\Models\Product;

class BrandObserver
{
    public function created(Brand $brand): void
    {
        $this->clearRelatedCaches();
    }

    public function updated(Brand $brand): void
    {
        $this->clearRelatedCaches();
    }

    public function deleted(Brand $brand): void
    {
        $this->clearRelatedCaches();
    }

    public function restored(Brand $brand): void
    {
        $this->clearRelatedCaches();
    }

    protected function clearRelatedCaches(): void
    {
        Brand::flushCache();
        // Cambiar una marca también afecta el listado de productos
        Product::flushCache();
    }
}
