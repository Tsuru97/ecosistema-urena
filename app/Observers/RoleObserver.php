<?php

namespace App\Observers;

use App\Models\Role;
use Illuminate\Support\Facades\Cache;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role)
    {
        Role::flushCache();
        // Invalida caché de usuarios ya que cambiar roles afecta los listados
        \App\Models\User::flushCache();
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role)
    {
        Role::flushCache();
        \App\Models\User::flushCache();
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role)
    {
        Role::flushCache();
        \App\Models\User::flushCache();
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role)
    {
        Role::flushCache();
        \App\Models\User::flushCache();
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role)
    {
        Role::flushCache();
        \App\Models\User::flushCache();
    }
}
