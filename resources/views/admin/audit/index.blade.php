@extends('layouts.master')
@section('title') Auditoría @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Registro de Actividad</h5>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('audit.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Usuario</label>
                                <select name="user_id" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($users as $user_data)
                                        <option value="{{ $user_data->id }}" {{ request('user_id') == $user_data->id ? 'selected' : '' }}>
                                            {{ $user_data->name }} {{ $user_data->lastname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Modelo</label>
                                <select name="subject_type" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($subjectTypes as $type)
                                        <option value="{{ $type['value'] }}" {{ request('subject_type') == $type['value'] ? 'selected' : '' }}>
                                            {{ $type['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Acción</label>
                                <select name="event" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    @foreach($events as $event)
                                        <option value="{{ $event }}" {{ request('event') == $event ? 'selected' : '' }}>
                                            {{ ucfirst($event) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Desde</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="ri-search-line"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Modelo</th>
                                    <th>Descripción</th>
                                    <th>Cambios</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities as $activity)
                                <tr>
                                    <td>
                                        <span class="text-muted fs-12">{{ $activity->created_at->format('d/m/Y') }}</span>
                                        <br>
                                        <span class="text-muted fs-11">{{ $activity->created_at->format('H:i:s') }}</span>
                                    </td>
                                    <td>
                                        @if($activity->causer)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                                        {{ strtoupper(substr($activity->causer->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    {{ $activity->causer->name }}
                                                    <br><span class="text-muted fs-11">{{ $activity->causer->email }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Sistema</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $eventBadges = [
                                                'created' => 'bg-success-subtle text-success',
                                                'updated' => 'bg-info-subtle text-info',
                                                'deleted' => 'bg-danger-subtle text-danger',
                                            ];
                                            $eventLabels = [
                                                'created' => 'Creado',
                                                'updated' => 'Actualizado',
                                                'deleted' => 'Eliminado',
                                            ];
                                        @endphp
                                        <span class="badge {{ $eventBadges[$activity->event] ?? 'bg-secondary-subtle text-secondary' }}">
                                            {{ $eventLabels[$activity->event] ?? ucfirst($activity->event) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ class_basename($activity->subject_type) }}</span>
                                        @if($activity->subject_id)
                                            <span class="text-muted fs-11">#{{ $activity->subject_id }}</span>
                                        @endif
                                    </td>
                                    <td class="text-wrap" style="max-width: 200px;">
                                        {{ $activity->description }}
                                    </td>
                                    <td>
                                        @if($activity->properties && (isset($activity->properties['old']) || isset($activity->properties['attributes'])))
                                            <button class="btn btn-sm btn-ghost-primary" type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#changes-collapse-{{ $activity->id }}"
                                                    aria-expanded="false"
                                                    aria-controls="changes-collapse-{{ $activity->id }}">
                                                <i class="ri-code-s-slash-line"></i> Ver
                                            </button>
                                        @else
                                            <span class="text-muted fs-11">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($activity->properties && (isset($activity->properties['old']) || isset($activity->properties['attributes'])))
                                <tr>
                                    <td colspan="6" class="p-0 border-0">
                                        <div class="collapse" id="changes-collapse-{{ $activity->id }}">
                                            <div class="p-3 bg-light border-bottom">
                                                <div class="row">
                                                    @if($activity->properties && isset($activity->properties['old']))
                                                    <div class="col-md-6">
                                                        <h6 class="text-danger mb-2"><i class="ri-subtract-line"></i> Antes</h6>
                                                        <div class="bg-white border rounded p-2">
                                                            <pre class="mb-0 fs-12" style="white-space: pre-wrap;">{{ json_encode($activity->properties['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    @if($activity->properties && isset($activity->properties['attributes']))
                                                    <div class="col-md-6">
                                                        <h6 class="text-success mb-2"><i class="ri-add-line"></i> Después</h6>
                                                        <div class="bg-white border rounded p-2">
                                                            <pre class="mb-0 fs-12" style="white-space: pre-wrap;">{{ json_encode($activity->properties['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="ri-file-list-3-line fs-24 d-block mb-2"></i>
                                        No hay registros de actividad.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $activities->appends(request()->all())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
