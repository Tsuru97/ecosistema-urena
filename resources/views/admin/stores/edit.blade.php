@extends('layouts.master')
@section('title') Editar Sucursal @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('stores.update', $store->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">Editar: {{ $store->name }}</h4>
                        <div class="flex-shrink-0">
                            <div class="form-check form-switch form-switch-md" dir="ltr">
                                <input type="checkbox" class="form-check-input" id="store-status" name="status" value="1" role="switch"
                                       {{ old('status', $store->status) ? 'checked' : '' }}>
                                <label class="form-check-label" for="store-status">Sucursal Activa</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('admin.stores._form', ['store' => $store])

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('stores.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-line me-1"></i> Actualizar Sucursal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
