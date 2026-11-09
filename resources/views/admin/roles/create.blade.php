@extends('layouts.master')
@section('title') Crear Rol @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-shield-user-line me-2"></i>Crear Nuevo Rol
                    </h4>
                    <a href="{{ route('roles.index') }}" class="btn btn-ghost-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Volver al listado
                    </a>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-4">
                        Complete el nombre del rol y seleccione los permisos que desea asignarle.
                    </p>

                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf

                        {{-- Nombre del Rol --}}
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-semibold">
                                        Nombre del Rol <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror form-control-sm"
                                           id="name" name="name"
                                           value="{{ old('name') }}"
                                           placeholder="Ej: Supervisor de Ventas"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">El nombre debe ser único en el sistema.</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Sección de Permisos --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0"><i class="ri-key-2-line me-2 text-primary"></i>Asignar Permisos</h5>
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                                <label class="form-check-label fw-semibold" for="checkAll">
                                    Seleccionar Todos
                                </label>
                            </div>
                        </div>

                        @foreach($permissions as $type => $groupPermissions)
                            <div class="card border mb-3">
                                <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 text-capitalize fw-semibold">
                                        <i class="ri-folder-line me-2 text-primary"></i>{{ $type }}
                                        <span class="badge bg-secondary ms-1">{{ $groupPermissions->count() }}</span>
                                    </h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input group-check" type="checkbox"
                                               id="check-group-{{ Str::slug($type) }}">
                                        <label class="form-check-label small" for="check-group-{{ Str::slug($type) }}">
                                            Todo el grupo
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body py-3">
                                    <div class="row g-2">
                                        @foreach($groupPermissions as $permission)
                                            <div class="col-md-4 col-lg-3">
                                                <div class="form-check">
                                                    <input class="form-check-input permission-check"
                                                           type="checkbox"
                                                           name="permissions[]"
                                                           id="perm-{{ $permission->id }}"
                                                           value="{{ $permission->id }}"
                                                           data-group="{{ Str::slug($type) }}"
                                                           @if(is_array(old('permissions')) && in_array($permission->id, old('permissions'))) checked @endif>
                                                    <label class="form-check-label small" for="perm-{{ $permission->id }}">
                                                        {{ $permission->name_public ?: $permission->name }}
                                                        @if($permission->description)
                                                            <span class="ms-1 text-muted"
                                                                  data-bs-toggle="tooltip"
                                                                  data-bs-placement="top"
                                                                  title="{{ $permission->description }}">
                                                                <i class="ri-question-line"></i>
                                                            </span>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-line me-1"></i> Guardar Rol
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Activar tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });

    const checkAll = document.getElementById('checkAll');
    const permissionChecks = document.querySelectorAll('.permission-check');
    const groupChecks = document.querySelectorAll('.group-check');

    // Seleccionar / deseleccionar todos
    checkAll.addEventListener('change', function () {
        permissionChecks.forEach(cb => cb.checked = this.checked);
        groupChecks.forEach(cb => cb.checked = this.checked);
    });

    // Seleccionar todo el grupo
    groupChecks.forEach(groupCheckbox => {
        groupCheckbox.addEventListener('change', function () {
            const groupId = this.id.replace('check-group-', '');
            document.querySelectorAll(`.permission-check[data-group="${groupId}"]`)
                    .forEach(cb => cb.checked = this.checked);
            updateCheckAllState();
        });
    });

    // Actualizar estado de grupo y checkAll al marcar individual
    permissionChecks.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateGroupCheckState(this.dataset.group);
            updateCheckAllState();
        });
    });

    function updateGroupCheckState(groupId) {
        const groupCheckbox = document.getElementById(`check-group-${groupId}`);
        if (!groupCheckbox) return;
        const inGroup = document.querySelectorAll(`.permission-check[data-group="${groupId}"]`);
        groupCheckbox.checked = Array.from(inGroup).every(p => p.checked);
        groupCheckbox.indeterminate = !groupCheckbox.checked && Array.from(inGroup).some(p => p.checked);
    }

    function updateCheckAllState() {
        checkAll.checked = Array.from(permissionChecks).every(p => p.checked);
        checkAll.indeterminate = !checkAll.checked && Array.from(permissionChecks).some(p => p.checked);
    }

    // Inicializar estados
    groupChecks.forEach(gc => updateGroupCheckState(gc.id.replace('check-group-', '')));
    updateCheckAllState();
});
</script>
@endsection
