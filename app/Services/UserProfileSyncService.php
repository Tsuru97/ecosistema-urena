<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Store;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class UserProfileSyncService
{
    /**
     * Sincroniza el perfil de cliente/proveedor y sucursal para un usuario específico.
     */
    public function syncUserProfile(User $user, ?array $storeIds = null, ?int $defaultStoreId = null): void
    {
        // 1. Sincronización con expediente de Cliente
        if ($user->hasRole('Cliente')) {
            if (!$user->customer()->exists()) {
                $creditLimit = (float)($user->credit_limit ?? 0);
                $availableCredit = (float)($user->available_credit ?? $creditLimit);

                Customer::create([
                    'user_id'          => $user->id,
                    'name'             => $user->name,
                    'lastname'         => $user->lastname,
                    'email'            => $user->email,
                    'phone'            => $user->phone,
                    'rfc'              => $user->rfc,
                    'business_name'    => trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')),
                    'credit_limit'     => $creditLimit,
                    'available_credit' => $availableCredit,
                    'status'           => $user->status ?? true,
                ]);

                Customer::flushCache();
            }
        }

        // 2. Sincronización con expediente de Proveedor
        if ($user->hasRole('Proveedor')) {
            if (!$user->supplier()->exists()) {
                Supplier::create([
                    'user_id'     => $user->id,
                    'name'        => trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')),
                    'rfc'         => $user->rfc,
                    'email'       => $user->email,
                    'phone'       => $user->phone,
                    'credit_days' => 0,
                    'status'      => $user->status ?? true,
                ]);

                Supplier::flushCache();
            }
        }

        // 3. Garantizar que el usuario tenga sucursales asociadas en store_user
        if ($user->stores()->count() === 0) {
            if (!empty($storeIds)) {
                $syncData = [];
                foreach ($storeIds as $sId) {
                    $syncData[$sId] = ['is_default' => ($sId == $defaultStoreId)];
                }
                $user->stores()->sync($syncData);
            } else {
                // Si no se especificaron sucursales (ej. al crear desde POS o formulario cliente),
                // asociar la sucursal activa actual o la primera sucursal del sistema.
                $currentStoreId = current_store_id();
                if ($currentStoreId && $currentStoreId !== 'global') {
                    $user->stores()->sync([$currentStoreId => ['is_default' => true]]);
                } else {
                    $defaultStore = Store::where('status', true)->first();
                    if ($defaultStore) {
                        $user->stores()->sync([$defaultStore->id => ['is_default' => true]]);
                    }
                }
            }
        }

        User::flushCache();
    }

    /**
     * Escanea y repara todos los usuarios huérfanos de la base de datos.
     */
    public function syncAllOrphanProfiles(): array
    {
        $stats = [
            'customers_created' => 0,
            'suppliers_created' => 0,
            'roles_assigned'    => 0,
            'stores_assigned'   => 0,
        ];

        $roleCliente = Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
        $roleProveedor = Role::firstOrCreate(['name' => 'Proveedor', 'guard_name' => 'web']);

        $defaultStore = Store::where('status', true)->first();
        $defaultStoreId = $defaultStore?->id;

        // A) Crear expediente Customer para usuarios con rol 'Cliente' que no lo tengan
        $clientUsers = User::role('Cliente')->whereDoesntHave('customer')->get();
        foreach ($clientUsers as $user) {
            Customer::create([
                'user_id'          => $user->id,
                'name'             => $user->name,
                'lastname'         => $user->lastname,
                'email'            => $user->email,
                'phone'            => $user->phone,
                'rfc'              => $user->rfc,
                'business_name'    => trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')),
                'credit_limit'     => (float)($user->credit_limit ?? 0),
                'available_credit' => (float)($user->available_credit ?? 0),
                'status'           => $user->status ?? true,
            ]);
            $stats['customers_created']++;
        }

        // B) Crear expediente Supplier para usuarios con rol 'Proveedor' que no lo tengan
        $supplierUsers = User::role('Proveedor')->whereDoesntHave('supplier')->get();
        foreach ($supplierUsers as $user) {
            Supplier::create([
                'user_id'     => $user->id,
                'name'        => trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')),
                'rfc'         => $user->rfc,
                'email'       => $user->email,
                'phone'       => $user->phone,
                'credit_days' => 0,
                'status'      => $user->status ?? true,
            ]);
            $stats['suppliers_created']++;
        }

        // C) Asignar rol 'Cliente' a usuarios vinculados a Customer que no tengan el rol
        $customersNoRole = Customer::with('user')->whereHas('user', function ($q) {
            $q->whereDoesntHave('roles', function ($r) {
                $r->where('name', 'Cliente');
            });
        })->get();

        foreach ($customersNoRole as $cust) {
            if ($cust->user) {
                $cust->user->assignRole($roleCliente);
                $stats['roles_assigned']++;
            }
        }

        // D) Asignar rol 'Proveedor' a usuarios vinculados a Supplier que no tengan el rol
        $suppliersNoRole = Supplier::with('user')->whereHas('user', function ($q) {
            $q->whereDoesntHave('roles', function ($r) {
                $r->where('name', 'Proveedor');
            });
        })->get();

        foreach ($suppliersNoRole as $supp) {
            if ($supp->user) {
                $supp->user->assignRole($roleProveedor);
                $stats['roles_assigned']++;
            }
        }

        // E) Asignar sucursal predeterminada a usuarios que no tengan ninguna sucursal asignada en store_user
        if ($defaultStoreId) {
            $usersNoStore = User::whereDoesntHave('stores')->get();
            foreach ($usersNoStore as $user) {
                $user->stores()->sync([$defaultStoreId => ['is_default' => true]]);
                $stats['stores_assigned']++;
            }
        }

        User::flushCache();
        Customer::flushCache();
        Supplier::flushCache();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Log::info('Ejecutada sincronización y reparación masiva de usuarios huérfanos.', $stats);

        return $stats;
    }
}
