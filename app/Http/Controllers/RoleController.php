<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-roles')->only(['index']);
        $this->middleware('permission:crear-roles')->only(['create', 'store']);
        $this->middleware('permission:editar-roles')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-roles')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search    = $request->input('search');
        $sortField = $request->get('sort_field', 'id');
        $sortDir   = $request->get('sort_direction', 'desc');

        $allowedSorts = ['id', 'name'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'id';
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }

        $cacheKey = 'roles.index.' . md5(json_encode($request->all()));

        $roles = Role::rememberWithTags($cacheKey, function () use ($search, $sortField, $sortDir) {
            $query = Role::query();

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            $query->orderBy($sortField, $sortDir);

            return $query->with('permissions')->paginate(10)->withQueryString();
        });

        return view('admin.roles.index', compact('roles', 'search', 'sortField', 'sortDir'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::orderBy('type')->orderBy('name')->get()->groupBy('type');
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

            $permissions = array_map('intval', $request->input('permissions', []));
            $role->syncPermissions($permissions);

            DB::commit();

            // RoleObserver flushea caché automáticamente
            return redirect()->route('roles.index')
                             ->with('success', 'Rol creado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el rol: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions    = Permission::orderBy('type')->orderBy('name')->get()->groupBy('type');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions'   => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $role->update(['name' => $request->name]);

            $permissions = array_map('intval', $request->input('permissions', []));
            $role->syncPermissions($permissions);

            DB::commit();

            // RoleObserver flushea caché automáticamente
            return redirect()->route('roles.index')
                             ->with('success', 'Rol actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el rol: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Proteger roles del sistema que no se pueden eliminar
        if (in_array($role->name, ['Super Admin', 'Dirección'])) {
            return redirect()->route('roles.index')
                             ->with('error', 'El rol "' . $role->name . '" no puede ser eliminado.');
        }

        $role->delete(); // RoleObserver flushea caché automáticamente

        return redirect()->route('roles.index')
                         ->with('success', 'Rol eliminado exitosamente.');
    }
}
