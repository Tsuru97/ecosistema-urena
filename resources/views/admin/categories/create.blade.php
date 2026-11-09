@extends('layouts.master')
@section('title') Crear Categoría @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Crear Nueva Categoría</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('categories.index') }}" class="btn btn-soft-secondary btn-sm">
                            <i class="ri-arrow-left-line align-middle me-1"></i> Volver a la lista
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="name" class="form-label">Nombre de Categoría <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror form-control-sm" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="parent_id" class="form-label">Categoría Padre (Opcional)</label>
                                <select class="form-select @error('parent_id') is-invalid @enderror form-select-sm" id="parent_id" name="parent_id">
                                    <option value="">-- Ninguna (Será Categoría Principal) --</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->full_path }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror form-control-sm" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch form-switch-lg" dir="ltr">
                                    <input type="checkbox" class="form-check-input" id="statusSwitch" name="status" value="1" checked>
                                    <label class="form-check-label" for="statusSwitch">Activo (Visible en el sistema)</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-seo-form />
                            </div>

                            <div class="col-12 mt-4 text-end">
                                <button type="submit" class="btn btn-primary btn-sm">Guardar Categoría</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
