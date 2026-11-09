<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreSchedule;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-sucursales')->only(['index']);
        $this->middleware('permission:crear-sucursales')->only(['create', 'store']);
        $this->middleware('permission:editar-sucursales')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-sucursales')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->get('search');
        $sortBy = $request->get('sort', 'name');
        $sortDir = $request->get('direction', 'asc');
        $allowedSorts = ['name', 'code', 'city', 'status', 'created_at'];

        // Clave estándar igual al resto de controllers (prefijo + md5 de parámetros)
        $cacheKey = 'stores.index.' . md5(json_encode($request->only(['search', 'sort', 'direction', 'page'])));

        $stores = Store::rememberWithTags($cacheKey, function () use ($search, $sortBy, $sortDir, $allowedSorts) {
            $query = Store::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            }

            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir);
            }

            return $query->withCount('users')->paginate(15)->withQueryString();
        });

        return view('admin.stores.index', compact('stores', 'search', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        return view('admin.stores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'code'    => 'required|string|max:10|unique:stores,code',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city'    => 'nullable|string|max:255',
            'state'   => 'nullable|string|max:255',
            'zip'     => 'nullable|string|max:10',
            'lat'     => 'nullable|numeric|between:-90,90',
            'lng'     => 'nullable|numeric|between:-180,180',
            'status'  => 'boolean',
            'schedules'                => 'nullable|array',
            'schedules.*.is_open'      => 'nullable',
            'schedules.*.open_time'    => 'nullable|date_format:H:i',
            'schedules.*.close_time'   => 'nullable|date_format:H:i',
            'schedules.*.has_break'    => 'nullable',
            'schedules.*.break_start'  => 'nullable|date_format:H:i',
            'schedules.*.break_end'    => 'nullable|date_format:H:i',
        ]);

        $validated['status'] = $request->has('status');

        $store = Store::create($validated);

        $this->saveSchedules($store, $request->input('schedules', []));

        // Flush manual: saveSchedules() usa updateOrCreate sobre StoreSchedule
        // y no dispara el observer de Store.
        Store::flushCache();

        return redirect()->route('stores.index')
            ->with('success', 'Sucursal creada exitosamente.');
    }

    public function edit(Store $store)
    {
        $store->load('schedules');
        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'code'    => 'required|string|max:10|unique:stores,code,' . $store->id,
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city'    => 'nullable|string|max:255',
            'state'   => 'nullable|string|max:255',
            'zip'     => 'nullable|string|max:10',
            'lat'     => 'nullable|numeric|between:-90,90',
            'lng'     => 'nullable|numeric|between:-180,180',
            'status'  => 'boolean',
            'schedules'                => 'nullable|array',
            'schedules.*.is_open'      => 'nullable',
            'schedules.*.open_time'    => 'nullable|date_format:H:i',
            'schedules.*.close_time'   => 'nullable|date_format:H:i',
            'schedules.*.has_break'    => 'nullable',
            'schedules.*.break_start'  => 'nullable|date_format:H:i',
            'schedules.*.break_end'    => 'nullable|date_format:H:i',
        ]);

        $validated['status'] = $request->has('status');

        $store->update($validated);

        $this->saveSchedules($store, $request->input('schedules', []));

        // Flush manual: saveSchedules() usa updateOrCreate sobre StoreSchedule
        // y no dispara el observer de Store.
        Store::flushCache();

        return redirect()->route('stores.index')
            ->with('success', 'Sucursal actualizada exitosamente.');
    }

    /**
     * Guardar/actualizar los horarios de la sucursal (7 días).
     */
    private function saveSchedules(Store $store, array $schedules): void
    {
        foreach (StoreSchedule::DAYS as $day => $name) {
            $data = $schedules[$day] ?? [];
            $isOpen = isset($data['is_open']);
            $hasBreak = $isOpen && isset($data['has_break']);

            $store->schedules()->updateOrCreate(
                ['day_of_week' => $day],
                [
                    'is_open'     => $isOpen,
                    'open_time'   => $isOpen ? ($data['open_time'] ?? null) : null,
                    'close_time'  => $isOpen ? ($data['close_time'] ?? null) : null,
                    'break_start' => $hasBreak ? ($data['break_start'] ?? null) : null,
                    'break_end'   => $hasBreak ? ($data['break_end'] ?? null) : null,
                ]
            );
        }
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()->route('stores.index')
            ->with('success', 'Sucursal eliminada exitosamente.');
    }

    /**
     * Cambiar a una tienda específica.
     */
    public function switchStore(Store $store)
    {
        // Super Admin puede acceder a cualquier tienda
        $user = auth()->user();
        if (!$user->hasRole('Super Admin') && !$user->stores->contains($store->id)) {
            abort(403, 'No tienes acceso a esta sucursal.');
        }

        session(['current_store_id' => $store->id]);

        return redirect()->back()->with('success', "Sucursal cambiada a: {$store->name}");
    }

    /**
     * Cambiar a vista Global — disponible para todos los usuarios autenticados.
     * Cada módulo aplica sus propios filtros según el rol del usuario.
     */
    public function switchGlobal()
    {
        session(['current_store_id' => 'global']);

        return redirect()->back()->with('success', 'Vista global activada.');
    }
}
