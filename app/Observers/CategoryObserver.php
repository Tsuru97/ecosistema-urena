<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Product;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $this->clearRelatedCaches();
    }

    public function updated(Category $category): void
    {
        $this->clearRelatedCaches();
    }

    public function deleted(Category $category): void
    {
        $this->clearRelatedCaches();
    }

    public function restored(Category $category): void
    {
        $this->clearRelatedCaches();
    }

    protected function clearRelatedCaches(): void
    {
        Category::flushCache();
        // Cambiar una categoría también afecta el listado de productos
        Product::flushCache();
    }
}
