<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-auditoria');
    }

    /**
     * Display the activity log index with filters.
     */
    public function index(Request $request)
    {
        $query = Activity::withoutGlobalScopes()->with('causer')->latest();

        // Filtro por usuario
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
            // quitamos la restricción de causer_type por ahora por si hay logs del sistema
        }

        // Filtro por tipo de modelo
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // Filtro por acción (event)
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filtro por rango de fechas
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(20);

        // Datos para los filtros
        $users = \App\Models\User::select('id', 'name', 'lastname')->orderBy('name')->get();
        $subjectTypes = Activity::withoutGlobalScopes()->select('subject_type')->distinct()->pluck('subject_type')
            ->map(fn($type) => ['value' => $type, 'label' => class_basename($type)])
            ->values();
        $events = Activity::withoutGlobalScopes()->select('event')->distinct()->pluck('event')->filter();

        return view('admin.audit.index', compact('activities', 'users', 'subjectTypes', 'events'));
    }
}
