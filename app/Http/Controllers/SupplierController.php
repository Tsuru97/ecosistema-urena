<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-proveedores')->only(['index', 'show']);
        $this->middleware('permission:crear-proveedores')->only(['create', 'store']);
        $this->middleware('permission:editar-proveedores')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-proveedores')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $cacheKey = 'suppliers.index.' . md5(json_encode($request->all()));

        $suppliers = Supplier::rememberWithTags($cacheKey, function () use ($search) {
            return Supplier::with('user')
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('rfc', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->paginate(20);
        });

        return view('admin.suppliers.index', compact('suppliers', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'rfc'         => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:1000',
            'credit_days' => 'required|integer|min:0',
        ]);

        $email = $request->input('email');
        $existingUser = !empty($email) ? User::where('email', $email)->first() : null;

        if ($existingUser) {
            $existingSupplier = null;
            try {
                $existingSupplier = $existingUser->supplier;
            } catch (\Throwable $th) {}

            if ($existingSupplier) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "El correo {$email} ya pertenece al Proveedor #{$existingSupplier->id} ({$existingUser->name}).");
            }
        }

        DB::beginTransaction();
        try {
            $supplierData = $request->all();
            $isMerged = false;

            $role = Role::firstOrCreate(['name' => 'Proveedor', 'guard_name' => 'web']);

            if ($existingUser) {
                // Vincular expediente de proveedor a usuario existente sin sobreescribir el nombre de persona del usuario
                try {
                    $supplierData['user_id'] = $existingUser->id;
                } catch (\Throwable $th) {}

                if (!$existingUser->hasRole('Proveedor')) {
                    $existingUser->assignRole($role);
                }
                $isMerged = true;
            } else {
                // Crear una cuenta de usuario nueva asignada al proveedor
                $userEmail = $email ?: ('prov_' . Str::random(10) . '@muebleriaurena.com.mx');
                $username = 'supp_' . Str::random(10);
                while (User::where('username', $username)->exists()) {
                    $username = 'supp_' . Str::random(10);
                }

                $user = User::create([
                    'name'     => $request->input('name'),
                    'username' => $username,
                    'email'    => $userEmail,
                    'password' => Hash::make(Str::random(16)),
                    'phone'    => $request->input('phone'),
                    'rfc'      => $request->input('rfc'),
                    'status'   => true,
                ]);

                $user->assignRole($role);
                try {
                    $supplierData['user_id'] = $user->id;
                } catch (\Throwable $th) {}
            }

            $supplier = Supplier::create($supplierData);

            $targetUser = $existingUser ?? ($user ?? null);
            if ($targetUser) {
                (new \App\Services\UserProfileSyncService())->syncUserProfile($targetUser);
            }

            DB::commit();

            User::flushCache();
            Supplier::flushCache();
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $successMsg = $isMerged
                ? "Proveedor registrado e integrado exitosamente al usuario existente '{$existingUser->name}' ({$email}) asignándole el rol de Proveedor."
                : 'Proveedor registrado correctamente y cuenta de usuario asignada.';

            return redirect()->route('suppliers.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar el proveedor: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['user', 'purchaseOrders' => fn($q) => $q->latest()->take(10)]);
        return view('admin.suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'rfc'         => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:1000',
            'credit_days' => 'required|integer|min:0',
            'status'      => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            $supplier->update($request->all());

            try {
                if ($supplier->user) {
                    $supplier->user->update(array_filter([
                        'email'  => $request->input('email') ?: $supplier->user->email,
                        'phone'  => $request->input('phone') ?: $supplier->user->phone,
                        'status' => $request->input('status'),
                    ]));
                }
            } catch (\Throwable $th) {}

            DB::commit();

            User::flushCache();
            Supplier::flushCache();
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return redirect()->route('suppliers.index')
                ->with('success', 'Proveedor actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el proveedor: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        DB::beginTransaction();
        try {
            if ($supplier->purchaseOrders()->count() > 0) {
                // Si tiene compras, lo desactivamos a él y a su usuario
                $supplier->update(['status' => false]);
                try {
                    if ($supplier->user) {
                        $supplier->user->update(['status' => false]);
                    }
                } catch (\Throwable $th) {}

                DB::commit();

                User::flushCache();
                Supplier::flushCache();

                return redirect()->route('suppliers.index')
                    ->with('success', 'El proveedor tiene compras asociadas. Se ha marcado como inactivo junto con su usuario.');
            }

            $user = null;
            try {
                $user = $supplier->user;
            } catch (\Throwable $th) {}

            $supplier->delete();
            if ($user) {
                $user->delete();
            }

            DB::commit();

            User::flushCache();
            Supplier::flushCache();

            return redirect()->route('suppliers.index')
                ->with('success', 'Proveedor y su usuario asociado fueron eliminados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al eliminar el proveedor: ' . $e->getMessage());
        }
    }
}
