@extends('layouts.master')
@section('title') Crear Producto @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row">
                <!-- Columna Principal -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="name">Nombre del Producto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror form-control-sm" id="name" name="name" value="{{ old('name') }}" placeholder="Ej. Sala Esquinera modelo Venecia" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="description">Descripción Detallada</label>
                                <textarea class="form-control @error('description') is-invalid @enderror form-control-sm" id="description" name="description" rows="4" placeholder="Especificaciones, dimensiones, etc...">{{ old('description') }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Galería del Producto</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Sube imágenes en alta calidad del producto. La primera imagen será usada como portada principal.</p>
                            <!-- Input Nativo para Creación -->
                            <input type="file" name="gallery[]" id="gallery" class="form-control form-control-sm" multiple accept="image/*">
                            <small class="form-text text-muted mt-2 d-block">Selecciona una o más imágenes. Podrás administrar esta galería más tarde en el apartado de edición.</small>
                        </div>
                    </div>
                    
                    <x-seo-form />
                    
                </div>
                <!-- /Columna Principal -->

                <!-- Columna Lateral -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Estado de Publicación</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <label for="status" class="form-label mb-3">Visibilidad</label>
                                <div class="form-check form-switch form-switch-lg" dir="ltr">
                                    <input type="checkbox" class="form-check-input" id="statusSwitch" name="status" value="1" checked>
                                    <label class="form-check-label" for="statusSwitch">Activo (Visible en catálogos y PDV)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="sku" class="form-label">Código SKU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('sku') is-invalid @enderror form-control-sm" id="sku" name="sku" value="{{ old('sku') }}" placeholder="Ej. SL-VEN-001" required>
                                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">Tipo de Producto</label>
                                <select class="form-select form-select-sm" id="type" name="type" onchange="toggleProductType(this.value)">
                                    <option value="simple"   {{ old('type','simple') == 'simple'   ? 'selected' : '' }}>Simple (sin variantes)</option>
                                    <option value="variable" {{ old('type') == 'variable' ? 'selected' : '' }}>Variable (con variantes)</option>
                                    <option value="virtual"  {{ old('type') == 'virtual'  ? 'selected' : '' }}>Virtual / Servicio Digital</option>
                                    <option value="service"  {{ old('type') == 'service'  ? 'selected' : '' }}>Servicio (mano de obra)</option>
                                </select>
                                <small class="text-muted">Los productos Virtual y Servicio no descuentan inventario.</small>
                            </div>

                            {{-- Opción comodín: solo visible para tipo virtual --}}
                            <div class="mb-3" id="wildcard-option" style="{{ old('type') === 'virtual' ? '' : 'display:none' }}">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_wildcard" name="is_wildcard" value="1" {{ old('is_wildcard') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold text-warning" for="is_wildcard">
                                        <i class="ri-edit-2-line me-1"></i> Producto Comodín (Personalizado en POS)
                                    </label>
                                </div>
                                <small class="text-muted">Permite al cajero definir nombre, precio y descripción libremente.</small>
                            </div>

                            <div class="mb-3">
                                <label for="base_cost" class="form-label">Costo de compra ($) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control @error('base_cost') is-invalid @enderror form-control-sm" id="base_cost" name="base_cost" value="{{ old('base_cost', '0.00') }}" required>
                                </div>
                                <small class="text-muted">Sin impuestos. Este costo definirá el precio dinámico de cada sucursal.</small>
                                @error('base_cost') <div class="text-danger mt-1 fs-12">{{ $message }}</div> @enderror
                            </div>

                            <!-- Configuración de IEPS (SAT) -->
                            <div class="p-2 mb-3 bg-light rounded border border-dashed">
                                <label for="ieps_type" class="form-label mb-1 fw-semibold text-primary">Configuración de IEPS (SAT)</label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <select class="form-select form-select-sm" id="ieps_type" name="ieps_type" onchange="toggleIepsInput()">
                                            <option value="exempt" {{ old('ieps_type', 'exempt') == 'exempt' ? 'selected' : '' }}>Exento / Sin IEPS (0%)</option>
                                            <option value="percentage" {{ old('ieps_type') == 'percentage' ? 'selected' : '' }}>Porcentual (%) (Ej: 0.08 = 8%)</option>
                                            <option value="fixed_quota" {{ old('ieps_type') == 'fixed_quota' ? 'selected' : '' }}>Cuota Fija ($ por litro/unidad)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 {{ old('ieps_type', 'exempt') == 'exempt' ? 'd-none' : '' }}" id="ieps_value_wrapper">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text" id="ieps_prefix">%</span>
                                            <input type="number" step="0.0001" min="0" class="form-control form-control-sm" id="ieps_value" name="ieps_value" value="{{ old('ieps_value', '0.0000') }}" placeholder="Valor IEPS">
                                        </div>
                                        <small class="text-muted fs-11 d-block mt-1" id="ieps_help">Ingresa en decimal ej. 0.08 para 8%.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="categories" class="form-label">Categorías <span class="text-danger">*</span></label>
                                <select class="form-select @error('categories') is-invalid @enderror form-select-sm" id="categories" name="categories[]" multiple required style="height: 120px;">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ is_array(old('categories')) && in_array($category->id, old('categories')) ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Mantén presionado Ctrl (o Cmd en Mac) para seleccionar varias.</small>
                                @error('categories') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Marca <span class="text-danger">*</span></label>
                                <select class="form-select @error('brand_id') is-invalid @enderror form-select-sm" id="brand_id" name="brand_id" required>
                                    <option value="">Seleccione una marca</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Proveedor Principal / Distribuidor</label>
                                <select class="form-select @error('supplier_id') is-invalid @enderror form-select-sm" id="supplier_id" name="supplier_id">
                                    <option value="">— Ninguno (Asignación libre) —</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted fs-11">Utilizado para generar Órdenes de Compra automáticas.</small>
                                @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                        </div>
                        <div class="card-footer border-top text-end">
                            <a href="{{ route('products.index') }}" class="btn btn-light me-2 btn-sm">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-sm">Registrar Producto</button>
                        </div>
                    </div>
                </div>
                <!-- /Columna Lateral -->
            </div>
            <!-- end row -->

        </form>
    </div>
@endsection

@section('script')
<script>
    function toggleIepsInput() {
        const iepsType = document.getElementById('ieps_type').value;
        const wrapper = document.getElementById('ieps_value_wrapper');
        const prefix = document.getElementById('ieps_prefix');
        const help = document.getElementById('ieps_help');

        if (iepsType === 'exempt') {
            wrapper.classList.add('d-none');
        } else {
            wrapper.classList.remove('d-none');
            if (iepsType === 'percentage') {
                prefix.innerText = '%';
                help.innerText = 'Ingresa la tasa en decimal (ej. 0.08 para 8%, 0.265 para 26.5%).';
            } else if (iepsType === 'fixed_quota') {
                prefix.innerText = '$';
                help.innerText = 'Ingresa el monto de cuota fija por unidad/litro (ej. 1.50).';
            }
        }
    }

    function toggleProductType(value) {
        // Mostrar opción comodín solo para virtual
        const wildcardOption = document.getElementById('wildcard-option');
        if (wildcardOption) {
            wildcardOption.style.display = (value === 'virtual') ? '' : 'none';
            if (value !== 'virtual') {
                document.getElementById('is_wildcard').checked = false;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        toggleIepsInput();
        const typeSelect = document.getElementById('type');
        if (typeSelect) toggleProductType(typeSelect.value);
    });

</script>
@endsection
