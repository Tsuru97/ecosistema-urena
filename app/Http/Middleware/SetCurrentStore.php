<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store;

class SetCurrentStore
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            // session('current_store_id') puede ser:
            //   - un ID numérico → tienda específica
            //   - 'global'       → vista consolidada (solo Dirección/Gerente)
            //   - null           → primera visita, usar default
            $sessionValue = session('current_store_id');
            $isGlobal = ($sessionValue === 'global');
            $storeId = $isGlobal ? null : $sessionValue;

            // Si no hay valor en sesión, decidir según rol
            if ($sessionValue === null) {
                if ($user->hasAnyRole(['Dirección', 'Gerente', 'Super Admin'])) {
                    $isGlobal = true;
                    $storeId = null;
                    session(['current_store_id' => 'global']);
                } else {
                    $defaultStore = $user->default_store;
                    $storeId = $defaultStore?->id;
                    session(['current_store_id' => $storeId]);
                }
            }

            // Todos los usuarios pueden usar vista global (para ver comunicados globales, etc.)
            // Los módulos sensibles (usuarios, productos) aplican sus propios filtros internamente.
            // Solo se bloquea si no tiene ninguna tienda asignada y tampoco permisos de admin.
            if ($isGlobal && !$user->hasAnyRole(['Dirección', 'Gerente', 'Super Admin'])) {
                // Verificar que tiene al menos una tienda asignada antes de permitir global
                $hasStores = $user->stores()->where('status', true)->exists();
                if (!$hasStores) {
                    // Sin tiendas asignadas y sin rol admin: forzar a global de todas formas
                    // (mejor que no ver nada)
                    $isGlobal = true;
                    $storeId = null;
                }
                // Con tiendas asignadas: permitir global sin restricción
            }

            // Si seleccionó una tienda específica, verificar acceso
            // Super Admin puede acceder a cualquier tienda sin estar asignado
            if (!$isGlobal && $storeId && !$user->hasRole('Super Admin')) {
                if (!$user->stores()->where('stores.id', $storeId)->exists()) {
                    $defaultStore = $user->default_store;
                    $storeId = $defaultStore?->id;
                    session(['current_store_id' => $storeId]);
                }
            }

            // Compartir con la app y las vistas
            app()->instance('current_store_id', $storeId);
            app()->instance('is_global_view', $isGlobal);
            $currentStore = $storeId ? Store::find($storeId) : null;
            app()->instance('current_store', $currentStore);

            view()->share('currentStore', $currentStore);
            view()->share('isGlobalView', $isGlobal);
            // Todos los usuarios pueden cambiar a vista global para ver comunicados globales, etc.
            // Los admins además ven datos consolidados de todos los módulos.
            view()->share('canViewGlobal', true);
            // Super Admin ve todas las tiendas activas; otros solo las asignadas
            if ($user->hasRole('Super Admin')) {
                view()->share('userStores', Store::where('status', true)->get());
            } else {
                view()->share('userStores', $user->stores()->where('status', true)->get());
            }
        }

        return $next($request);
    }
}
