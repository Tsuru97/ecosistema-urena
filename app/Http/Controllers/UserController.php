<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use App\Notifications\WelcomeNotification;
use App\Notifications\VerifyEmailAndPhone;
use DB;
use Hash;
use Illuminate\Support\Arr;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-usuarios')->only(['index', 'show']);
        $this->middleware('permission:crear-usuarios')->only(['create', 'store']);
        $this->middleware('permission:editar-usuarios')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-usuarios')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $queryParams = $request->all();
        $queryParams['page'] = $page;
        $queryParams['store_id'] = current_store_id();
        ksort($queryParams);

        $cacheKey = 'users.index.' . md5(json_encode($queryParams));

        $users = User::rememberWithTags($cacheKey, function () use ($request) {
            $query = User::query();

            // Filtrar por sucursal activa (incluye personal de la tienda, clientes, proveedores y usuarios globales)
            if ($storeId = current_store_id()) {
                $query->where(function ($q) use ($storeId) {
                    $q->whereHas('stores', function ($sq) use ($storeId) {
                        $sq->where('store_id', $storeId);
                    })
                    ->orWhereHas('roles', function ($rq) {
                        $rq->whereIn('name', ['Cliente', 'Proveedor']);
                    })
                    ->orWhereDoesntHave('stores');
                });
            }

            // Search
            if ($request->has('search') && trim($request->search) != '') {
                $search = trim($request->search);
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('lastname', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(name, ' ', lastname) LIKE ?", ["%{$search}%"])
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->get('sort_field', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            // Allow sorting by specific columns only
            $allowedSorts = ['id', 'name', 'username', 'email', 'status', 'created_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('id', 'desc');
            }

            return $query->with('stores')->paginate(10);
        });

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name','name')->all();
        $stores = Store::where('status', true)->get();
        return view('admin.users.create', compact('roles', 'stores'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'lastname' => 'required',
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'phone_local' => 'nullable|regex:/^[0-9]{7,15}$/',
            'address' => 'nullable',
            'birthday' => 'nullable|date',
            'rfc' => 'nullable',
            'status' => 'required|boolean',
            'password' => 'required|same:confirm-password',
            'roles' => 'required',
            'stores' => 'required|array|min:1',
            'stores.*' => 'exists:stores,id',
            'default_store' => 'required|integer|in_array:stores.*',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $input = $request->all();
        if (!auth()->user()->hasAnyRole(['Dirección', 'Gerente'])) {
            unset($input['credit_limit']);
        }

        $input['password'] = Hash::make($input['password']);

        // Combinar código de país + número local
        if (!empty($input['phone_local'])) {
            $input['phone'] = preg_replace('/[^0-9]/', '', $input['phone_country'] . $input['phone_local']);
        } else {
            $input['phone'] = null;
        }
        unset($input['phone_country'], $input['phone_local']);

        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        // Sincronizar sucursales con pivot is_default
        $storeSync = [];
        foreach ($request->input('stores') as $storeId) {
            $storeSync[$storeId] = ['is_default' => $storeId == $request->input('default_store')];
        }
        $user->stores()->sync($storeSync);

        // Flush manual: assignRole() y sync() no disparan el Observer Eloquent
        User::flushCache();

        // Sincronización automática de expedientes (Cliente/Proveedor)
        (new \App\Services\UserProfileSyncService())->syncUserProfile($user);

        // Enviar notificaciones por email
        $user->notify(new WelcomeNotification());
        $user->notify(new VerifyEmailAndPhone());

        // Enviar verificación por WhatsApp si tiene teléfono
        if (!empty($user->phone)) {
            try {
                $url = VerifyEmailAndPhone::verificationPhoneUrl($user);
                $whatsapp = new \App\Services\WhatsAppService();
                $whatsapp->sendTemplate(
                    $user->phone,
                    config('services.whatsapp.verify_template', 'verificacion_telefono'),
                    'es_MX',
                    [$user->name, $url]
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error enviando WhatsApp', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('users.index')
                        ->with('success','Usuario creado exitosamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.show',compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name','name')->all();
        $stores = Store::where('status', true)->get();
        $userStoreIds = $user->stores->pluck('id')->toArray();
        $defaultStoreId = $user->stores()->wherePivot('is_default', true)->first()?->id;

        return view('admin.users.edit', compact('user', 'roles', 'userRole', 'stores', 'userStoreIds', 'defaultStoreId'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'lastname' => 'required',
            'username' => 'required|unique:users,username,'.$id,
            'email' => 'required|email|unique:users,email,'.$id,
            'phone_local' => 'nullable|regex:/^[0-9]{7,15}$/',
            'address' => 'nullable',
            'birthday' => 'nullable|date',
            'rfc' => 'nullable',
            'status' => 'required|boolean',
            'password' => 'same:confirm-password',
            'roles' => 'required',
            'stores' => 'required|array|min:1',
            'stores.*' => 'exists:stores,id',
            'default_store' => 'required|integer|in_array:stores.*',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $input = $request->all();
        if (!auth()->user()->hasAnyRole(['Dirección', 'Gerente'])) {
            unset($input['credit_limit']);
        }

        // Combinar código de país + número local
        if (!empty($input['phone_local'])) {
            $input['phone'] = preg_replace('/[^0-9]/', '', $input['phone_country'] . $input['phone_local']);
        } else {
            $input['phone'] = null;
        }
        unset($input['phone_country'], $input['phone_local']);

        if(!empty($input['password'])){
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input,array('password'));
        }

        $user = User::findOrFail($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();

        $user->assignRole($request->input('roles'));

        // Sincronizar sucursales con pivot is_default
        $storeSync = [];
        foreach ($request->input('stores') as $storeId) {
            $storeSync[$storeId] = ['is_default' => $storeId == $request->input('default_store')];
        }
        $user->stores()->sync($storeSync);

        // Flush manual: DB::table(), assignRole() y sync() no disparan el Observer Eloquent
        User::flushCache();

        // Sincronización automática de expedientes (Cliente/Proveedor)
        (new \App\Services\UserProfileSyncService())->syncUserProfile($user);

        return redirect()->route('users.index')
                        ->with('success','Usuario actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return redirect()->route('users.index')
                        ->with('success','Usuario eliminado exitosamente');
    }

    /**
     * Endpoint API para consultar si existe un usuario por correo electrónico.
     */
    public function checkEmail(Request $request)
    {
        try {
            $email = trim($request->input('email'));
            if (empty($email)) {
                return response()->json(['exists' => false]);
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(['exists' => false]);
            }

            $hasCustomer = false;
            $hasSupplier = false;

            try {
                $hasCustomer = $user->customer()->exists();
            } catch (\Throwable $th) {}

            try {
                $hasSupplier = $user->supplier()->exists();
            } catch (\Throwable $th) {}

            return response()->json([
                'exists' => true,
                'user'   => [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'lastname'     => $user->lastname,
                    'full_name'    => trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')),
                    'email'        => $user->email,
                    'phone'        => $user->phone,
                    'rfc'          => $user->rfc,
                    'roles'        => $user->getRoleNames(),
                    'has_customer' => $hasCustomer,
                    'has_supplier' => $hasSupplier,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['exists' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Mostrar página de confirmación de verificación de email.
     */
    public function verifyEmail($id, Request $request)
    {
        $user = User::findOrFail($id);

        if (sha1($user->getEmailForVerification()) !== $request->route('hash')) {
            abort(403, 'El enlace de verificación no es válido.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('root')
                ->with('info', 'El correo ya está verificado.');
        }

        return view('admin.users.verify-confirm', [
            'user' => $user,
            'type' => 'email',
            'confirmUrl' => request()->fullUrl(),
        ]);
    }

    /**
     * Confirmar verificación de email (POST).
     */
    public function confirmVerifyEmail($id, Request $request)
    {
        $user = User::findOrFail($id);

        if (sha1($user->getEmailForVerification()) !== $request->route('hash')) {
            abort(403, 'El enlace de verificación no es válido.');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->route('root')
                        ->with('success', '¡Correo electrónico verificado exitosamente!');
    }

    /**
     * Mostrar página de confirmación de verificación de teléfono.
     */
    public function verifyPhone($id, Request $request)
    {
        $user = User::findOrFail($id);

        if (sha1($user->phone) !== $request->route('hash')) {
            abort(403, 'El enlace de verificación no es válido.');
        }

        if ($user->phone_verified_at) {
            return redirect()->route('root')
                ->with('info', 'El teléfono ya está verificado.');
        }

        return view('admin.users.verify-confirm', [
            'user' => $user,
            'type' => 'phone',
            'confirmUrl' => request()->fullUrl(),
        ]);
    }

    /**
     * Confirmar verificación de teléfono (POST).
     */
    public function confirmVerifyPhone($id, Request $request)
    {
        $user = User::findOrFail($id);

        if (sha1($user->phone) !== $request->route('hash')) {
            abort(403, 'El enlace de verificación no es válido.');
        }

        if (is_null($user->phone_verified_at)) {
            $user->update(['phone_verified_at' => now()]);
        }

        return redirect()->route('root')
                        ->with('success', '¡Número de teléfono verificado exitosamente!');
    }

    /**
     * Reenviar verificación de email.
     */
    public function resendVerifyEmail($id)
    {
        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('users.index')
                ->with('info', 'El correo ya está verificado.');
        }

        $user->notify(new VerifyEmailAndPhone());

        return redirect()->route('users.index')
            ->with('success', 'Correo de verificación reenviado exitosamente.');
    }

    /**
     * Reenviar verificación de WhatsApp.
     */
    public function resendVerifyWhatsApp($id)
    {
        $user = User::findOrFail($id);

        if (empty($user->phone)) {
            return redirect()->route('users.index')
                ->with('error', 'El usuario no tiene número de teléfono.');
        }

        if ($user->phone_verified_at) {
            return redirect()->route('users.index')
                ->with('info', 'El teléfono ya está verificado.');
        }

        try {
            $url = VerifyEmailAndPhone::verificationPhoneUrl($user);
            $whatsapp = new \App\Services\WhatsAppService();
            $whatsapp->sendTemplate(
                $user->phone,
                config('services.whatsapp.verify_template', 'verificacion_telefono'),
                'es_MX',
                [$user->name, $url]
            );

            return redirect()->route('users.index')
                ->with('success', 'Mensaje de verificación de WhatsApp reenviado exitosamente.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error reenviando WhatsApp', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('users.index')
                ->with('error', 'Error al enviar el mensaje de WhatsApp.');
        }
    }
}
