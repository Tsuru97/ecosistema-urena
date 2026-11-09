<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-clientes')->only(['index', 'show']);
        $this->middleware('permission:crear-clientes')->only(['create', 'store']);
        $this->middleware('permission:editar-clientes')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-clientes')->only(['destroy']);
    }

    /**
     * Muestra el listado de clientes con caching, busqueda y ordenamiento.
     */
    public function index(Request $request)
    {
        $cacheKey = 'customers.index.' . md5(json_encode($request->all()));

        $customers = Customer::rememberWithTags($cacheKey, function () use ($request) {
            $query = Customer::with('user');

            // Búsqueda por texto libre
            if ($request->has('search') && !empty($request->search)) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('business_name', 'like', "%{$search}%")
                      ->orWhere('rfc', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($u) use ($search) {
                          $u->where('name', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                      });
                });
            }

            // Ordenamiento por columna
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

            $allowedSorts = ['id', 'business_name', 'rfc', 'credit_limit', 'available_credit', 'status', 'created_at'];
            
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            return $query->paginate(15);
        });

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Muestra el formulario para crear un nuevo cliente.
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Guarda un cliente recien creado y su usuario asociado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'lastname'         => 'required|string|max:255',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:20',
            'rfc'              => 'nullable|string|max:20',
            'business_name'    => 'nullable|string|max:255',
            'legal_address'         => 'nullable|string|max:1000',
            'street'                => 'nullable|string|max:255',
            'colonia'               => 'nullable|string|max:255',
            'city'                  => 'nullable|string|max:100',
            'state'                 => 'nullable|string|max:100',
            'zip'                   => 'nullable|string|max:20',
            'delivery_references'   => 'nullable|string|max:1000',
            'delivery_instructions' => 'nullable|string|max:1000',
            'delivery_lat'          => 'nullable|numeric',
            'delivery_lng'          => 'nullable|numeric',
            'delivery_maps_url'     => 'nullable|string|max:500',
            'credit_limit'          => 'nullable|numeric|min:0',
            'available_credit'      => 'nullable|numeric|min:0',
            'status'                => 'required|boolean',
        ]);

        $email = $request->input('email');

        // Detectar si el usuario ya existe en el sistema
        $existingUser = !empty($email) ? User::where('email', $email)->first() : null;

        if ($existingUser) {
            if ($existingUser->customer) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "El correo {$email} ya pertenece a un Cliente registrado (#{$existingUser->customer->id} — {$existingUser->name} {$existingUser->lastname}).");
            }
        }

        DB::beginTransaction();
        try {
            $isMerged = false;

            if ($existingUser) {
                // Fusión con cuenta existente: se asigna rol Cliente y se actualizan datos de contacto
                $user = $existingUser;
                $user->update(array_filter([
                    'phone' => $request->input('phone') ?: $user->phone,
                    'rfc'   => $request->input('rfc') ?: $user->rfc,
                ]));

                $role = Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
                if (!$user->hasRole('Cliente')) {
                    $user->assignRole($role);
                }
                User::flushCache();
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

                $isMerged = true;
            } else {
                // Crear usuario nuevo
                if (empty($email)) {
                    $email = 'cliente_' . Str::random(10) . '@muebleriaurena.com.mx';
                }

                $username = 'cust_' . Str::random(10);
                while (User::where('username', $username)->exists()) {
                    $username = 'cust_' . Str::random(10);
                }

                $user = User::create([
                    'name'     => $request->input('name'),
                    'lastname' => $request->input('lastname'),
                    'username' => $username,
                    'email'    => $email,
                    'password' => Hash::make(Str::random(16)),
                    'phone'    => $request->input('phone'),
                    'rfc'      => $request->input('rfc'),
                    'status'   => $request->input('status', true),
                ]);

                $role = Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
                $user->assignRole($role);
                User::flushCache();
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }

            // 2. Crear Customer con sus propios datos de contacto
            $creditLimit = auth()->user()->can('gestionar-credito') ? (float)($request->input('credit_limit') ?? 0) : 0;
            $availableCredit = auth()->user()->can('gestionar-credito') && $request->has('available_credit') && !is_null($request->input('available_credit'))
                ? (float)$request->input('available_credit')
                : $creditLimit;

            $latVal = $request->filled('delivery_lat') ? (float)$request->input('delivery_lat') : null;
            $lngVal = $request->filled('delivery_lng') ? (float)$request->input('delivery_lng') : null;
            $mapsUrl = $request->input('delivery_maps_url');
            if (empty($mapsUrl) && $latVal && $lngVal) {
                $mapsUrl = "https://www.google.com/maps?q={$latVal},{$lngVal}";
            }

            $customer = Customer::create([
                'user_id'               => $user->id,
                'name'                  => $request->input('name'),
                'lastname'              => $request->input('lastname'),
                'email'                 => $request->input('email'),
                'phone'                 => $request->input('phone'),
                'rfc'                   => $request->input('rfc'),
                'business_name'         => $request->input('business_name'),
                'legal_address'         => $request->input('legal_address'),
                'street'                => $request->input('street'),
                'colonia'               => $request->input('colonia'),
                'city'                  => $request->input('city'),
                'state'                 => $request->input('state'),
                'zip'                   => $request->input('zip'),
                'delivery_references'   => $request->input('delivery_references'),
                'delivery_instructions' => $request->input('delivery_instructions'),
                'delivery_lat'          => $latVal,
                'delivery_lng'          => $lngVal,
                'delivery_maps_url'     => $mapsUrl,
                'credit_limit'          => $creditLimit,
                'available_credit'      => $availableCredit,
                'status'                => $request->input('status', true),
            ]);

            // Garantizar sucursal vinculada en store_user
            (new \App\Services\UserProfileSyncService())->syncUserProfile($user);

            DB::commit();

            Customer::flushCache();

            $logMessage = $isMerged
                ? "Cliente #{$customer->id} ({$customer->full_name}) integrado a la cuenta de usuario existente '{$user->name} {$user->lastname}' ({$user->email})."
                : "Cliente {$customer->full_name} creado exitosamente.";

            activity('customers')
                ->performedOn($customer)
                ->causedBy(auth()->user())
                ->log($logMessage);

            $successMsg = $isMerged
                ? "Se detectó la cuenta de usuario '{$user->name} {$user->lastname}'. El expediente de Cliente fue integrado a su cuenta con el rol de Cliente."
                : "Cliente registrado exitosamente.";

            return redirect()->route('customers.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Muestra la ficha técnica completa del cliente.
     */
    public function show(Customer $customer)
    {
        $customer->load([
            'user',
            'sales' => fn($q) => $q->latest()->take(10),
            'specialOrders' => fn($q) => $q->latest()->take(10),
            'quotes' => fn($q) => $q->latest()->take(10),
        ]);

        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Formulario de edicion de cliente.
     */
    public function edit(Customer $customer)
    {
        $customer->load('user');
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Actualiza el cliente y su usuario.
     */
    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'lastname'         => 'required|string|max:255',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:20',
            'rfc'              => 'nullable|string|max:20',
            'business_name'    => 'nullable|string|max:255',
            'legal_address'         => 'nullable|string|max:1000',
            'street'                => 'nullable|string|max:255',
            'colonia'               => 'nullable|string|max:255',
            'city'                  => 'nullable|string|max:100',
            'state'                 => 'nullable|string|max:100',
            'zip'                   => 'nullable|string|max:20',
            'delivery_references'   => 'nullable|string|max:1000',
            'delivery_instructions' => 'nullable|string|max:1000',
            'delivery_lat'          => 'nullable|numeric',
            'delivery_lng'          => 'nullable|numeric',
            'delivery_maps_url'     => 'nullable|string|max:500',
            'credit_limit'          => 'nullable|numeric|min:0',
            'available_credit'      => 'nullable|numeric|min:0',
            'status'                => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            if (auth()->user()->can('gestionar-credito')) {
                $creditLimit = (float)($request->input('credit_limit') ?? 0);
                $availableCredit = $request->has('available_credit') && !is_null($request->input('available_credit'))
                    ? (float)$request->input('available_credit')
                    : $creditLimit;
            } else {
                $creditLimit = (float)($customer->credit_limit ?? 0);
                $availableCredit = (float)($customer->available_credit ?? 0);
            }

            $latVal = $request->filled('delivery_lat') ? (float)$request->input('delivery_lat') : null;
            $lngVal = $request->filled('delivery_lng') ? (float)$request->input('delivery_lng') : null;
            $mapsUrl = $request->input('delivery_maps_url');
            if (empty($mapsUrl) && $latVal && $lngVal) {
                $mapsUrl = "https://www.google.com/maps?q={$latVal},{$lngVal}";
            }

            // Actualizar datos propios del cliente
            $customer->update([
                'name'                  => $request->input('name'),
                'lastname'              => $request->input('lastname'),
                'email'                 => $request->input('email'),
                'phone'                 => $request->input('phone'),
                'rfc'                   => $request->input('rfc'),
                'business_name'         => $request->input('business_name'),
                'legal_address'         => $request->input('legal_address'),
                'street'                => $request->input('street'),
                'colonia'               => $request->input('colonia'),
                'city'                  => $request->input('city'),
                'state'                 => $request->input('state'),
                'zip'                   => $request->input('zip'),
                'delivery_references'   => $request->input('delivery_references'),
                'delivery_instructions' => $request->input('delivery_instructions'),
                'delivery_lat'          => $latVal,
                'delivery_lng'          => $lngVal,
                'delivery_maps_url'     => $mapsUrl,
                'credit_limit'          => $creditLimit,
                'available_credit'      => $availableCredit,
                'status'                => $request->input('status'),
            ]);

            DB::commit();

            Customer::flushCache();

            activity('customers')
                ->performedOn($customer)
                ->causedBy(auth()->user())
                ->log("Cliente ID #{$customer->id} actualizado exitosamente.");

            return redirect()->route('customers.index')
                ->with('success', 'Cliente actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Elimina o desactiva el cliente.
     */
    public function destroy(Customer $customer)
    {
        $hasSales = $customer->sales()->count() > 0;
        $hasOrders = $customer->specialOrders()->count() > 0;

        if ($hasSales || $hasOrders) {
            $customer->update(['status' => false]);
            if ($customer->user) {
                $customer->user->update(['status' => false]);
            }
            Customer::flushCache();
            return redirect()->route('customers.index')
                ->with('success', 'El cliente tiene historial comercial activo. Se ha marcado como inactivo junto con su usuario.');
        }

        $user = $customer->user;
        $customer->delete();
        if ($user) {
            $user->delete();
        }
        Customer::flushCache();

        return redirect()->route('customers.index')
            ->with('success', 'Cliente y su usuario asociado fueron eliminados correctamente.');
    }
}
