@extends('layouts.master')
@section('title') Editar: {{ $attribute->name }} @endsection

@section('content')
    <x-breadcrumb />

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-list-settings-line me-2"></i>Editar Atributo: <strong>{{ $attribute->name }}</strong>
                    </h4>
                    <a href="{{ route('attributes.index') }}" class="btn btn-ghost-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Volver
                    </a>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('attributes.update', $attribute) }}" method="POST" id="attribute-form">
                        @csrf
                        @method('PUT')

                        {{-- ── Datos del atributo ─────────────────────────────────── --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       class="form-control @error('name') is-invalid @enderror form-control-sm"
                                       value="{{ old('name', $attribute->name) }}"
                                       required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="type" class="form-label fw-semibold">Tipo de control <span class="text-danger">*</span></label>
                                <select name="type" id="type"
                                        class="form-select @error('type') is-invalid @enderror form-select-sm" required>
                                    <option value="select" {{ old('type', $attribute->type) === 'select' ? 'selected' : '' }}>
                                        Selección (dropdown / botones)
                                    </option>
                                    <option value="color" {{ old('type', $attribute->type) === 'color' ? 'selected' : '' }}>
                                        Color (muestra de color)
                                    </option>
                                    <option value="text" {{ old('type', $attribute->type) === 'text' ? 'selected' : '' }}>
                                        Texto libre
                                    </option>
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="order" class="form-label fw-semibold">Orden</label>
                                <input type="number" name="order" id="order"
                                       class="form-control @error('order') is-invalid @enderror form-control-sm"
                                       value="{{ old('order', $attribute->order) }}" min="0">
                                @error('order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-2 d-flex flex-column justify-content-end gap-2 pb-1">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="is_variation" id="attr_is_variation" value="1"
                                           {{ old('is_variation', $attribute->is_variation) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="attr_is_variation">Para variación</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="status" id="attr_status" value="1"
                                           {{ old('status', $attribute->status) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="attr_status">Activo</label>
                                </div>
                            </div>
                        </div>

                        {{-- ── Valores del atributo ──────────────────────────────── --}}
                        <div class="border rounded p-3 mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <h6 class="mb-0 flex-grow-1 fw-semibold">
                                    <i class="ri-price-tag-3-line me-2 text-primary"></i>Valores del Atributo
                                    <span class="badge bg-secondary ms-2">{{ $attribute->values->count() }}</span>
                                </h6>
                                <button type="button" id="add-value" class="btn btn-sm btn-outline-success">
                                    <i class="ri-add-line me-1"></i> Agregar valor
                                </button>
                            </div>

                            {{-- Cabeceras --}}
                            <div class="row g-2 mb-2 text-muted fs-12 fw-semibold d-none d-md-flex px-2">
                                <div class="col-md-1 text-center">#</div>
                                <div class="col-md-5">Valor</div>
                                <div class="col-md-3" id="color-hex-header">Color HEX</div>
                                <div class="col-md-2 text-center">Orden</div>
                                <div class="col-md-1"></div>
                            </div>

                            <div id="values-list">
                                @php $values = old('values') ? collect(old('values'))->map(fn($v) => (object)$v) : $attribute->values @endphp
                                @foreach($values as $i => $val)
                                    @include('admin.attributes._value_row', [
                                        'i'   => $i,
                                        'val' => is_array($val) ? $val : $val->toArray(),
                                    ])
                                @endforeach
                            </div>

                            @error('values')
                                <div class="alert alert-danger mt-2 py-2 mb-0">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-line me-1"></i> Guardar Cambios
                            </button>
                            <a href="{{ route('attributes.index') }}" class="btn btn-light btn-sm">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    (function () {
        let rowCount = {{ ($values ?? collect())->count() }};

        const typeSelect  = document.getElementById('type');
        const colorHeader = document.getElementById('color-hex-header');
        const valuesList  = document.getElementById('values-list');

        // Template de fila nueva (sin ID)
        function buildRow(i) {
            return `<div class="row g-2 mb-2 align-items-center value-row">
                <input type="hidden" name="values[${i}][id]" value="">
                <div class="col-md-1 text-center text-muted fw-semibold fs-13">${i + 1}</div>
                <div class="col-md-5">
                    <input type="text" name="values[${i}][value]"
                           class="form-control form-control-sm"
                           placeholder="Ej: Rojo, XL, Algodón...">
                </div>
                <div class="col-md-3 color-col">
                    <input type="color" name="values[${i}][color_hex]"
                           class="form-control form-control-color w-100 p-1 form-control-sm"
                           value="#cccccc">
                </div>
                <div class="col-md-2">
                    <input type="number" name="values[${i}][order]"
                           class="form-control form-control-sm text-center" value="${i}" min="0">
                </div>
                <div class="col-md-1 text-center">
                    <button type="button" class="btn btn-sm btn-ghost-danger remove-row">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>`;
        }

        // Agregar fila
        document.getElementById('add-value').addEventListener('click', function () {
            valuesList.insertAdjacentHTML('beforeend', buildRow(rowCount++));
            applyColorState();
        });

        // Remover fila
        valuesList.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-row');
            if (!btn) return;
            if (valuesList.querySelectorAll('.value-row').length <= 1) {
                alert('Debe haber al menos un valor.');
                return;
            }
            btn.closest('.value-row').remove();
        });

        // Usar disabled + display:none para que los inputs de color no se envíen cuando no aplica
        function applyColorState() {
            const isColor = typeSelect.value === 'color';
            colorHeader.style.display = isColor ? '' : 'none';
            valuesList.querySelectorAll('.color-col').forEach(col => {
                col.style.display = isColor ? '' : 'none';
                col.querySelectorAll('input').forEach(inp => {
                    inp.disabled = !isColor;
                });
            });
        }

        typeSelect.addEventListener('change', applyColorState);
        applyColorState();
    })();
</script>
@endsection
