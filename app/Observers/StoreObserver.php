<?php

namespace App\Observers;

use App\Models\Store;
use App\Models\User;

class StoreObserver
{
    public function created(Store $store): void
    {
        $this->clearRelatedCaches();
    }

    public function updated(Store $store): void
    {
        $this->clearRelatedCaches();
    }

    public function deleted(Store $store): void
    {
        $this->clearRelatedCaches();
    }

    public function restored(Store $store): void
    {
        $this->clearRelatedCaches();
    }

    protected function clearRelatedCaches(): void
    {
        Store::flushCache();
        // Al cambiar una sucursal, el listado de usuarios también puede verse afectado
        // ya que el index de usuarios filtra por store_id.
        User::flushCache();
    }
}
