<?php

namespace App\Observers;

use App\Models\Inventory;

class InventoryObserver
{
    public function created(Inventory $inventory): void
    {
        Inventory::flushCache();
    }

    public function updated(Inventory $inventory): void
    {
        Inventory::flushCache();
    }

    public function deleted(Inventory $inventory): void
    {
        Inventory::flushCache();
    }
}
