@extends('layouts.master')
@section('title') Editar Producto @endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" id="product-edit-form">
            @csrf
            @method('PUT')

            @if(session('success'))
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif
            @if($errors->any())
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="ri-error-warning-line me-2"></i>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif

            {{-- ── Tabs ─────────────────────────────────────────────────────── --}}
            <div class="col-12 mb-3">
                <ul class="nav nav-tabs nav-tabs-custom nav-primary" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-general" role="tab">
                            <i class="ri-information-line me-1"></i> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-attributes" role="tab" id="tab-attr-link">
                            <i class="ri-list-settings-line me-1"></i> Atributos y Variantes
                        </a>
                    </li>
                    <li class="nav-item" id="tab-variants-li" style="{{ $product->type !== 'variable' ? 'display:none' : '' }}">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-variants" role="tab" id="tab-variants-link"
                         data-variants-url="{{ route('products.variants.list', $product) }}"
                           data-generate-url="{{ route('products.variants.generate', $product) }}"
                           data-create-url="{{ route('products.variants.create', $product) }}"
                           data-save-url="{{ route('products.variants.save', $product) }}"
                           data-delete-url="{{ url('products/' . $product->id . '/variants') }}">
                            <i class="ri-stack-line me-1"></i> Variantes
                            @if($product->variants()->count() > 0)
                                <span class="badge bg-primary ms-1" id="variants-count-badge">{{ $product->variants()->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>

            {{-- ══════════════════════════════════════════════════════ --}}
            {{-- TAB 1 — GENERAL                                        --}}
            {{-- ══════════════════════════════════════════════════════ --}}
            <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <div class="row">
                <!-- Columna Principal -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="name">Nombre del Producto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="description">Descripción Detallada</label>
                                <textarea class="form-control form-control-sm @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Galería del Producto</h5>
                        </div>
                        <div class="card-body">
                            <x-file-uploader :model="$product" type="gallery" :multiple="true" accept="image/*" />
                        </div>
                    </div>

                    <x-seo-form :model="$product" />
                </div>
                <!-- /Columna Principal -->

                <!-- Columna Lateral -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Estado de Publicación</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch form-switch-lg">
                                <input type="checkbox" class="form-check-input" id="statusSwitch" name="status" value="1" {{ $product->status ? 'checked' : '' }}>
                                <label class="form-check-label" for="statusSwitch">Activo (Visible en catálogos)</label>
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
                                <input type="text" class="form-control form-control-sm @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required>
                                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="base_cost" class="form-label">Costo de compra ($) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('base_cost') is-invalid @enderror" id="base_cost" name="base_cost" value="{{ old('base_cost', $product->base_cost) }}" required>
                                </div>
                                @error('base_cost') <div class="text-danger mt-1 fs-12">{{ $message }}</div> @enderror
                            </div>

                            <!-- Configuración de IEPS (SAT) -->
                            <div class="p-2 mb-3 bg-light rounded border border-dashed">
                                <label for="ieps_type" class="form-label mb-1 fw-semibold text-primary">Configuración de IEPS (SAT)</label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <select class="form-select form-select-sm" id="ieps_type" name="ieps_type" onchange="toggleIepsInput()">
                                            <option value="exempt" {{ old('ieps_type', $product->ieps_type ?? 'exempt') == 'exempt' ? 'selected' : '' }}>Exento / Sin IEPS (0%)</option>
                                            <option value="percentage" {{ old('ieps_type', $product->ieps_type ?? 'exempt') == 'percentage' ? 'selected' : '' }}>Porcentual (%) (Ej: 0.08 = 8%)</option>
                                            <option value="fixed_quota" {{ old('ieps_type', $product->ieps_type ?? 'exempt') == 'fixed_quota' ? 'selected' : '' }}>Cuota Fija ($ por litro/unidad)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 {{ old('ieps_type', $product->ieps_type ?? 'exempt') == 'exempt' ? 'd-none' : '' }}" id="ieps_value_wrapper">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text" id="ieps_prefix">%</span>
                                            <input type="number" step="0.0001" min="0" class="form-control form-control-sm" id="ieps_value" name="ieps_value" value="{{ old('ieps_value', $product->ieps_value ?? '0.0000') }}" placeholder="Valor IEPS">
                                        </div>
                                        <small class="text-muted fs-11 d-block mt-1" id="ieps_help">Ingresa en decimal ej. 0.08 para 8%.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="categories" class="form-label">Categorías <span class="text-danger">*</span></label>
                                @php $selectedCategories = old('categories', $product->categories->pluck('id')->toArray()); @endphp
                                <select class="form-select form-select-sm @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple required style="height:120px;">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ in_array($cat->id, (array)$selectedCategories) ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Ctrl/Cmd para seleccionar varias.</small>
                                @error('categories') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Marca <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('brand_id') is-invalid @enderror" id="brand_id" name="brand_id" required>
                                    <option value="">Seleccione una marca</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Proveedor Principal / Distribuidor</label>
                                <select class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id">
                                    <option value="">— Ninguno (Asignación libre) —</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted fs-11">Utilizado para generar Órdenes de Compra automáticas.</small>
                                @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="card-footer border-top text-end">
                            <a href="{{ route('products.index') }}" class="btn btn-light btn-sm me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-sm">Actualizar Producto</button>
                        </div>
                    </div>
                </div>
                <!-- /Columna Lateral -->
            </div>
            </div>{{-- /tab-general --}}

            {{-- ══════════════════════════════════════════════════════ --}}
            {{-- TAB 2 — ATRIBUTOS Y VARIANTES                          --}}
            {{-- ══════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-attributes" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">

                    {{-- Tipo de producto --}}
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">Tipo de Producto</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted fs-13 mb-3">
                                Un producto <strong>Simple</strong> tiene un único SKU y precio.
                                Un producto <strong>Variable</strong> tiene múltiples combinaciones (ej. Color × Talla).
                            </p>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="type_simple"
                                           value="simple" {{ old('type', $product->type ?? 'simple') === 'simple' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="type_simple">
                                        <i class="ri-box-1-line me-1 text-secondary"></i> Producto Simple
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="type_variable"
                                           value="variable" {{ old('type', $product->type) === 'variable' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="type_variable">
                                        <i class="ri-list-check-2 me-1 text-primary"></i> Producto Variable
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Panel de atributos (visible solo si variable) --}}
                    <div class="card" id="attributes-panel" style="{{ old('type', $product->type) !== 'variable' ? 'display:none' : '' }}">
                        <div class="card-header d-flex align-items-center gap-2">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <i class="ri-list-settings-line me-1 text-primary"></i>Atributos Asignados
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-attribute">
                                <i class="ri-add-line me-1"></i> Agregar atributo
                            </button>
                        </div>
                        <div class="card-body p-0">

                            {{-- Buscador de atributos (hidden) --}}
                            <div id="attribute-search-box" class="p-3 border-bottom" style="display:none">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                                    <input type="text" class="form-control" id="attr-search-input" placeholder="Buscar atributo...">
                                </div>
                                <div id="attr-search-results" class="list-group mt-2 shadow-sm" style="max-height:200px;overflow-y:auto"></div>
                            </div>

                            {{-- Lista de atributos asignados --}}
                            <div id="assigned-attributes-list">
                                @php
                                    $assignedAttrs = $product->attributes()->orderBy('product_attribute.order')->get();
                                @endphp

                                @forelse($assignedAttrs as $attr)
                                    <div class="assigned-attr-row border-bottom p-3 d-flex align-items-center gap-3"
                                         data-attr-id="{{ $attr->id }}">
                                        <input type="hidden" name="attributes[{{ $attr->id }}][id]" value="{{ $attr->id }}">
                                        <div class="flex-grow-1">
                                            <span class="fw-semibold">{{ $attr->name }}</span>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @foreach($attr->values as $val)
                                                    <span class="badge bg-light text-dark border fs-11">
                                                        @if($attr->type === 'color' && $val->color_hex)
                                                            <span class="d-inline-block rounded-circle me-1"
                                                                  style="width:10px;height:10px;background:{{ $val->color_hex }}"></span>
                                                        @endif
                                                        {{ $val->value }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 text-nowrap">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       name="attributes[{{ $attr->id }}][is_for_variation]"
                                                       id="attr_var_{{ $attr->id }}" value="1"
                                                       {{ $attr->pivot->is_for_variation ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="attr_var_{{ $attr->id }}">Para variación</label>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-ghost-danger btn-remove-attr"
                                                    data-attr-id="{{ $attr->id }}" title="Quitar">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div id="no-attrs-msg" class="text-center py-4 text-muted">
                                        <i class="ri-list-settings-line fs-24 d-block mb-2"></i>
                                        Ningún atributo asignado. Haz clic en <strong>Agregar atributo</strong>.
                                    </div>
                                @endforelse
                            </div>

                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ri-save-line me-1"></i> Guardar Atributos
                            </button>
                        </div>
                    </div>

                    <div id="simple-hint" class="text-center text-muted py-5"
                         style="{{ old('type', $product->type) === 'variable' ? 'display:none' : '' }}">
                        <i class="ri-information-line fs-24 d-block mb-2"></i>
                        Selecciona <strong>Producto Variable</strong> para asignar atributos y generar combinaciones.
                    </div>

                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-2"><i class="ri-question-line me-1 text-primary"></i>¿Qué son los atributos?</h6>
                            <p class="text-muted fs-13 mb-2">Los <strong>atributos</strong> definen las características variables de un producto (Color, Talla, Material…).</p>
                            <p class="text-muted fs-13 mb-2">Marca <em>Para variación</em> en aquellos que generarán combinaciones de stock independiente.</p>
                            <p class="text-muted fs-13 mb-0">En el siguiente tab (<strong>Variantes</strong>) podrás generar automáticamente todas las combinaciones.</p>
                        </div>
                    </div>
                </div>
            </div>
            </div>{{-- /tab-attributes --}}

            {{-- ══════════════════════════════════════════════════════ --}}
            {{-- TAB 3 — VARIANTES                                      --}}
            {{-- ══════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-variants" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center flex-wrap gap-2">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <i class="ri-stack-line me-1 text-primary"></i>
                                Variantes del Producto
                                <span id="variants-table-count" class="badge bg-secondary ms-2"></span>
                            </h5>
                            {{-- Botón secundario: auto-generar (opcional) --}}
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-generate-variants"
                                    title="Genera automáticamente TODAS las combinaciones posibles de los atributos marcados como 'Para variación'">
                                <i class="ri-magic-line me-1"></i> Auto-generar combinaciones
                            </button>
                            {{-- Botón principal: nueva variante manual --}}
                            <button type="button" class="btn btn-sm btn-success" id="btn-new-variant"
                                    data-bs-toggle="modal" data-bs-target="#modal-new-variant">
                                <i class="ri-add-circle-line me-1"></i> Nueva variante
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn-save-variants">
                                <i class="ri-save-line me-1"></i> Guardar cambios
                            </button>
                        </div>

                        {{-- Alerta inline para AJAX --}}
                        <div id="variants-alert" class="mx-3 mt-3" style="display:none"></div>

                        <div class="card-body p-0">
                            {{-- Loading --}}
                            <div id="variants-loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="text-muted mt-2 mb-0">Cargando variantes...</p>
                            </div>

                            {{-- Tabla de variantes --}}
                            <div class="table-responsive" id="variants-table-wrapper" style="display:none">
                                <table class="table table-sm align-middle mb-0" id="variants-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:30px"></th>
                                            <th>Combinación</th>
                                            <th style="width:150px">SKU</th>
                                            <th style="width:100px">Costo ($)</th>
                                            <th style="width:70px" class="text-center">Activa</th>
                                            <th style="width:70px" class="text-center">Defecto</th>
                                            <th style="width:60px" class="text-center">
                                                <i class="ri-image-line" title="Imágenes"></i>
                                            </th>
                                            <th style="width:40px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="variants-tbody">
                                        {{-- Filas inyectadas por JS --}}
                                    </tbody>
                                </table>
                            </div>

                            {{-- Estado vacío --}}
                            <div id="variants-empty" class="text-center py-5 text-muted" style="display:none">
                                <i class="ri-stack-line fs-32 d-block mb-2"></i>
                                <p class="mb-1">No hay variantes generadas.</p>
                                <p class="fs-13">
                                    Usa <strong>Nueva variante</strong> para crear manualmente, o
                                    <strong>Auto-generar combinaciones</strong> para crear todas las posibles.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>{{-- /tab-variants --}}

            </div>{{-- /tab-content --}}

        </form>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL — Nueva Variante Manual                                      --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="modal-new-variant" tabindex="-1" aria-labelledby="modal-new-variant-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-new-variant-label">
                        <i class="ri-add-circle-line me-1 text-success"></i> Nueva variante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Alerta del modal --}}
                    <div id="modal-variant-alert" style="display:none"></div>

                    {{-- Selects dinámicos por atributo de variación --}}
                    <div id="modal-attr-selects">
                        <div class="text-center text-muted py-3" id="modal-attr-loading">
                            <div class="spinner-border spinner-border-sm me-1"></div> Cargando atributos...
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="nv-sku">
                                SKU <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="nv-sku" placeholder="Ej. PROD-ROJO-M" required>
                            <div class="form-text">Se generará automáticamente al elegir valores, o escribe uno propio.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="nv-cost">Costo ($)</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="nv-cost" placeholder="— heredar del producto —">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="nv-active" checked>
                                <label class="form-check-label" for="nv-active">Variante activa</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-success" id="btn-save-new-variant">
                        <i class="ri-save-line me-1"></i> Crear variante
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection


@section('script')
<script>
(function () {
    // Obtener CSRF token: primero desde cookie (Laravel siempre la establece),
    // fallback al input hidden @csrf del formulario de la página.
    function getCsrfToken() {
        const cookie = document.cookie.split(';').map(c => c.trim())
            .find(c => c.startsWith('XSRF-TOKEN='));
        if (cookie) return decodeURIComponent(cookie.split('=')[1]);
        const input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }
    const csrfToken = getCsrfToken();

    // ── Tipo simple/variable ──────────────────────────────────────────────────
    const radios         = document.querySelectorAll('input[name="type"]');
    const panel          = document.getElementById('attributes-panel');
    const hint           = document.getElementById('simple-hint');
    const variantsTabLi  = document.getElementById('tab-variants-li');

    radios.forEach(r => r.addEventListener('change', function () {
        const isVar = this.value === 'variable';
        panel.style.display        = isVar ? '' : 'none';
        hint.style.display         = isVar ? 'none' : '';
        variantsTabLi.style.display = isVar ? '' : 'none';
    }));

    // ── Atributos — buscador ──────────────────────────────────────────────────
    const btnAdd    = document.getElementById('btn-add-attribute');
    const searchBox = document.getElementById('attribute-search-box');
    const searchInp = document.getElementById('attr-search-input');
    const results   = document.getElementById('attr-search-results');
    const attrList  = document.getElementById('assigned-attributes-list');
    const noMsg     = document.getElementById('no-attrs-msg');

    btnAdd?.addEventListener('click', () => {
        searchBox.style.display = searchBox.style.display === 'none' ? '' : 'none';
        if (searchBox.style.display !== 'none') searchInp.focus();
    });

    let searchTimer, _allAttrs = null;

    searchInp?.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (!q) { results.innerHTML = ''; return; }
        searchTimer = setTimeout(async () => {
            try {
                const all      = await fetchAllAttributes();
                const assigned = getAssignedIds();
                const filtered = all.filter(a => a.name.toLowerCase().includes(q.toLowerCase()) && !assigned.includes(a.id));
                results.innerHTML = filtered.length
                    ? filtered.map(a => `<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2 fs-13 btn-pick-attr"
                            data-attr-id="${a.id}" data-attr-name="${a.name}" data-attr-type="${a.type}">
                            <i class="ri-list-settings-line text-primary"></i>${a.name}
                            <span class="badge bg-secondary-subtle text-secondary ms-auto">${a.type}</span>
                        </button>`).join('')
                    : '<div class="list-group-item text-muted fs-13">Sin resultados</div>';
            } catch { results.innerHTML = '<div class="list-group-item text-danger fs-13">Error al buscar</div>'; }
        }, 300);
    });

    async function fetchAllAttributes() {
        if (_allAttrs) return _allAttrs;
        const r = await fetch('/attributes/all-active');
        _allAttrs = await r.json();
        return _allAttrs;
    }
    function getAssignedIds() {
        return [...attrList.querySelectorAll('.assigned-attr-row')].map(el => parseInt(el.dataset.attrId));
    }

    results?.addEventListener('click', async e => {
        const btn = e.target.closest('.btn-pick-attr');
        if (!btn) return;
        const id   = parseInt(btn.dataset.attrId);
        const name = btn.dataset.attrName;
        const type = btn.dataset.attrType;
        const valsR = await fetch(`/attributes/${id}/values`);
        const vals  = await valsR.json();
        addAttributeRow(id, name, type, vals, true);
        results.innerHTML = '';
        searchInp.value   = '';
        searchBox.style.display = 'none';
        noMsg && (noMsg.style.display = 'none');
    });

    attrList?.addEventListener('click', e => {
        const btn = e.target.closest('.btn-remove-attr');
        if (!btn) return;
        btn.closest('.assigned-attr-row').remove();
        if (!attrList.querySelector('.assigned-attr-row')) noMsg && (noMsg.style.display = '');
    });

    function addAttributeRow(id, name, type, vals, isForVariation) {
        const badges = vals.map(v => {
            const dot = (type === 'color' && v.color_hex)
                ? `<span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:${v.color_hex}"></span>` : '';
            return `<span class="badge bg-light text-dark border fs-11">${dot}${v.value}</span>`;
        }).join('');
        attrList.insertAdjacentHTML('beforeend', `<div class="assigned-attr-row border-bottom p-3 d-flex align-items-center gap-3" data-attr-id="${id}">
            <input type="hidden" name="attributes[${id}][id]" value="${id}">
            <div class="flex-grow-1"><span class="fw-semibold">${name}</span>
                <div class="d-flex flex-wrap gap-1 mt-1">${badges}</div></div>
            <div class="d-flex align-items-center gap-3 text-nowrap">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="attributes[${id}][is_for_variation]" id="attr_var_${id}" value="1" ${isForVariation ? 'checked' : ''}>
                    <label class="form-check-label small" for="attr_var_${id}">Para variación</label>
                </div>
                <button type="button" class="btn btn-sm btn-ghost-danger btn-remove-attr" data-attr-id="${id}">
                    <i class="ri-close-line"></i></button>
            </div></div>`);
    }

    // ── Variantes ─────────────────────────────────────────────────────────────
    const tabLink      = document.getElementById('tab-variants-link');
    const tbody        = document.getElementById('variants-tbody');
    const loadingEl    = document.getElementById('variants-loading');
    const tableWrapper = document.getElementById('variants-table-wrapper');
    const emptyEl      = document.getElementById('variants-empty');
    const alertEl      = document.getElementById('variants-alert');
    const countBadge   = document.getElementById('variants-table-count');

    let variantsLoaded = false;

    // Cargar variantes al entrar en el tab (lazy)
    tabLink?.addEventListener('shown.bs.tab', async () => {
        if (variantsLoaded) return;
        await loadVariants();
    });

    async function loadVariants() {
        loadingEl.style.display    = '';
        tableWrapper.style.display = 'none';
        emptyEl.style.display      = 'none';

        try {
            const r    = await fetch(tabLink.dataset.variantsUrl);
            const data = await r.json();
            renderVariants(data);
            variantsLoaded = true;
        } catch {
            showAlert('danger', 'Error al cargar las variantes.');
            loadingEl.style.display = 'none';
        }
    }

    function renderVariants(variants) {
        loadingEl.style.display = 'none';
        tbody.innerHTML = '';

        if (!variants.length) {
            emptyEl.style.display = '';
            tableWrapper.style.display = 'none';
            countBadge.textContent = '0';
            return;
        }

        countBadge.textContent = variants.length;
        tableWrapper.style.display = '';

        variants.forEach((v, i) => {
            const attrs = v.attributes.map(a => {
                const dot = a.color_hex
                    ? `<span class="d-inline-block rounded-circle border me-1" style="width:12px;height:12px;background:${a.color_hex}"></span>` : '';
                return `<span class="badge bg-light text-dark border me-1">${dot}<small class="text-muted">${a.attr}:</small> ${a.value}</span>`;
            }).join('');

            const imgsBadge = v.images_count > 0
                ? `<span class="badge bg-info-subtle text-info border">${v.images_count} <i class="ri-image-line"></i></span>`
                : `<span class="text-muted fs-12">—</span>`;

            tbody.insertAdjacentHTML('beforeend', `<tr data-variant-id="${v.id}">
                <td class="ps-3 text-muted fs-12">${i + 1}</td>
                <td>${attrs || v.display_name || '<span class="text-muted fst-italic fs-12">Sin atributos</span>'}</td>
                <td><input type="text" class="form-control form-control-sm var-sku" value="${v.sku}" required></td>
                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm var-cost" value="${v.cost ?? ''}" placeholder="—"></td>
                <td class="text-center">
                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                        <input class="form-check-input var-active" type="checkbox" role="switch" ${v.is_active ? 'checked' : ''}>
                    </div>
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                        <input class="form-check-input var-default" type="checkbox" role="switch" ${v.is_default ? 'checked' : ''}>
                    </div>
                </td>
                <td class="text-center">${imgsBadge}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-ghost-danger btn-delete-variant" data-variant-id="${v.id}" title="Eliminar">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            </tr>`);
        });

        // Radio behavior para "defecto"
        tbody.querySelectorAll('.var-default').forEach(chk => {
            chk.addEventListener('change', function () {
                if (this.checked) {
                    tbody.querySelectorAll('.var-default').forEach(c => { if (c !== this) c.checked = false; });
                }
            });
        });
    }

    // ── Auto-generar combinaciones ────────────────────────────────────────────
    document.getElementById('btn-generate-variants')?.addEventListener('click', async () => {
        const btn = document.getElementById('btn-generate-variants');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando...';
        alertEl.style.display = 'none';

        try {
            const r    = await fetch(tabLink.dataset.generateUrl, {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            });
            const data = await r.json();

            if (!r.ok) {
                showAlert('warning', data.error ?? 'Error al generar variantes.');
            } else {
                showAlert('success', data.message);
                variantsLoaded = false;
                await loadVariants();
            }
        } catch {
            showAlert('danger', 'Error de conexión al generar variantes.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-magic-line me-1"></i> Auto-generar combinaciones';
        }
    });

    // ── Guardar variantes ─────────────────────────────────────────────────────
    document.getElementById('btn-save-variants')?.addEventListener('click', async () => {
        const btn = document.getElementById('btn-save-variants');
        alertEl.style.display = 'none';

        const variants = [...tbody.querySelectorAll('tr[data-variant-id]')].map(row => ({
            id:         parseInt(row.dataset.variantId),
            sku:        row.querySelector('.var-sku').value.trim(),
            cost:       row.querySelector('.var-cost').value  || null,
            is_active:  row.querySelector('.var-active').checked  ? 1 : 0,
            is_default: row.querySelector('.var-default').checked ? 1 : 0,
        }));

        if (!variants.length) { showAlert('warning', 'No hay variantes para guardar.'); return; }
        if (variants.some(v => !v.sku)) { showAlert('warning', 'Todos los SKU son obligatorios.'); return; }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

        try {
            const r    = await fetch(tabLink.dataset.saveUrl, {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ variants }),
            });
            const data = await r.json();
            showAlert(r.ok ? 'success' : 'danger', data.message ?? 'Error al guardar.');
        } catch {
            showAlert('danger', 'Error de conexión al guardar.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-save-line me-1"></i> Guardar cambios';
        }
    });

    // ── Eliminar variante ─────────────────────────────────────────────────────
    tbody?.addEventListener('click', async e => {
        const btn = e.target.closest('.btn-delete-variant');
        if (!btn) return;
        if (!confirm('¿Eliminar esta variante? Esta acción no se puede deshacer.')) return;

        const varId  = btn.dataset.variantId;
        const deleteUrl = `${tabLink.dataset.deleteUrl}/${varId}`;

        try {
            const r = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: { 'X-XSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            if (r.ok) {
                btn.closest('tr').remove();
                const remaining = tbody.querySelectorAll('tr').length;
                countBadge.textContent = remaining;
                if (!remaining) { tableWrapper.style.display = 'none'; emptyEl.style.display = ''; }
                showAlert('success', 'Variante eliminada.');
            } else {
                showAlert('danger', 'No se pudo eliminar la variante.');
            }
        } catch {
            showAlert('danger', 'Error de conexión.');
        }
    });

    function showAlert(type, msg) {
        alertEl.className = `alert alert-${type} alert-dismissible fade show mx-3 mt-3`;
        alertEl.innerHTML = `${msg} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        alertEl.style.display = '';
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ── Modal: Nueva Variante Manual ──────────────────────────────────────────
    const modalEl       = document.getElementById('modal-new-variant');
    const modalAttrBox  = document.getElementById('modal-attr-selects');
    const modalLoading  = document.getElementById('modal-attr-loading');
    const modalAlertEl  = document.getElementById('modal-variant-alert');
    const nvSku         = document.getElementById('nv-sku');
    const baseSku       = {!! json_encode($product->sku) !!};

    let varAttrOptions  = []; // [{id, name, values:[{id,value,slug}]}]
    let modalAttrLoaded = false;

    modalEl?.addEventListener('show.bs.modal', async () => {
        // Reset form
        nvSku.value = '';
        document.getElementById('nv-cost').value  = '';
        document.getElementById('nv-active').checked = true;
        modalAlertEl.style.display = 'none';

        if (modalAttrLoaded) { updateSkuSuggestion(); return; }

        // Cargar atributos de variación del producto
        try {
            const pivotUrl = tabLink.dataset.variantsUrl.replace('/variants', '') + '/variation-attrs';
            // Usamos el endpoint que ya tenemos: leer pivot via DB en el backend
            // En su lugar, leemos los atributos asignados del DOM (ya están en el Tab 2)
            const rows = document.querySelectorAll('#assigned-attributes-list .assigned-attr-row');
            const varRows = [...rows].filter(r => r.querySelector('input[type="checkbox"][name*="is_for_variation"]')?.checked);

            if (!varRows.length) {
                modalAttrBox.innerHTML = `<div class="alert alert-warning fs-13">
                    <i class="ri-alert-line me-1"></i>
                    No hay atributos marcados como <em>Para variación</em> en el Tab de Atributos.
                    Guarda el producto con atributos de variación primero.
                </div>`;
                modalAttrLoaded = true;
                return;
            }

            // Construir selects desde los atributos asignados en el DOM
            let html = '';
            varAttrOptions = [];

            for (const row of varRows) {
                const attrId   = row.dataset.attrId;
                const attrName = row.querySelector('.fw-semibold')?.textContent?.trim() ?? 'Atributo';
                const badges   = [...row.querySelectorAll('.badge')];
                const values   = badges.map(b => ({
                    id:    null,  // no tenemos el id desde DOM, lo cargamos por API
                    value: b.textContent.trim(),
                    slug:  b.textContent.trim().toLowerCase().replace(/\s+/g, '-'),
                }));

                // Cargar values con IDs reales
                const valsR = await fetch(`/attributes/${attrId}/values`);
                const valsData = await valsR.json();

                varAttrOptions.push({ id: attrId, name: attrName, values: valsData });

                const opts = valsData.map(v => {
                    const slug = v.slug || slugify(v.value ?? '');
                    const dot = v.color_hex
                        ? `<span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:${v.color_hex};vertical-align:middle"></span>` : '';
                    return `<option value="${v.id}" data-slug="${slug}">${dot}${v.value}</option>`;
                }).join('');

                html += `<div class="mb-3">
                    <label class="form-label fw-semibold fs-13">${attrName}</label>
                    <select class="form-select form-select-sm nv-attr-select" data-attr-id="${attrId}" data-attr-name="${attrName}">
                        <option value="">— Sin especificar —</option>
                        ${opts}
                    </select>
                </div>`;
            }

            if (modalLoading) modalLoading.remove();
            modalAttrBox.innerHTML = html;

            // Auto-sugerir SKU al cambiar selects
            modalAttrBox.querySelectorAll('.nv-attr-select').forEach(sel => {
                sel.addEventListener('change', updateSkuSuggestion);
            });

            modalAttrLoaded = true;
            updateSkuSuggestion();

        } catch (err) {
            console.error('modal attr load error:', err);
            modalAttrBox.innerHTML = `<div class="text-danger fs-13"><i class="ri-error-warning-line me-1"></i>Error al cargar atributos: ${err.message}</div>`;
        }
    });

    // Convierte un texto a slug compatible con SKU
    function slugify(str) {
        return (str ?? '').toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')  // quitar acentos
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function updateSkuSuggestion() {
        const selects = [...(modalAttrBox?.querySelectorAll('.nv-attr-select') ?? [])];
        const slugParts = selects
            .map(s => {
                const opt = s.selectedOptions[0];
                if (!opt || !opt.value) return null;  // "— Sin especificar —" (value="")
                return opt.dataset.slug || slugify(opt.textContent.trim());
            })
            .filter(Boolean);
        nvSku.value = slugParts.length
            ? `${baseSku}-${slugParts.join('-')}`
            : baseSku;
    }

    function showModalAlert(type, msg) {
        modalAlertEl.className = `alert alert-${type} alert-dismissible fade show mb-3`;
        modalAlertEl.innerHTML = `${msg} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        modalAlertEl.style.display = '';
    }

    document.getElementById('btn-save-new-variant')?.addEventListener('click', async () => {
        const btn = document.getElementById('btn-save-new-variant');
        modalAlertEl.style.display = 'none';

        const sku = nvSku.value.trim();
        if (!sku) { showModalAlert('warning', 'El SKU es obligatorio.'); nvSku.focus(); return; }

        // Recoger value_ids seleccionados (omitir los vacíos)
        const valueIds = [...(modalAttrBox?.querySelectorAll('.nv-attr-select') ?? [])]
            .map(s => s.value)
            .filter(v => v !== '');

        const payload = {
            sku,
            cost:      document.getElementById('nv-cost').value  || null,
            is_active: document.getElementById('nv-active').checked ? 1 : 0,
            value_ids: valueIds,
        };

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creando...';

        try {
            const createUrl = tabLink.dataset.createUrl;
            const r    = await fetch(createUrl, {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });

            // Parsear respuesta — si el servidor devuelve HTML (500/419) no explotar
            let data = {};
            try { data = await r.json(); } catch {
                showModalAlert('danger', `Error del servidor (HTTP ${r.status}). Revisa la consola.`);
                console.error('createVariant non-JSON response:', r.status, r.statusText);
                return;
            }

            if (!r.ok) {
                const errMsg = data.errors
                    ? Object.values(data.errors).flat().join('<br>')
                    : (data.message ?? `Error ${r.status} al crear la variante.`);
                showModalAlert('danger', errMsg);
            } else {
                // Cerrar modal y agregar fila a la tabla
                bootstrap.Modal.getInstance(modalEl)?.hide();
                appendVariantRow(data.variant);
                showAlert('success', `Variante <strong>${data.variant.sku}</strong> creada exitosamente.`);
                modalAttrLoaded = false; // reset para la próxima apertura
            }
        } catch (err) {
            console.error('createVariant error:', err);
            showModalAlert('danger', 'Error de conexión al crear la variante. Revisa la consola.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-save-line me-1"></i> Crear variante';
        }
    });

    // Agrega una fila nueva a la tabla sin recargar
    function appendVariantRow(v) {
        // Mostrar tabla si estaba oculta
        if (tableWrapper.style.display === 'none') {
            tableWrapper.style.display = '';
            emptyEl.style.display = 'none';
        }
        const rowIndex = tbody.querySelectorAll('tr').length + 1;
        const attrs = (v.attributes ?? []).map(a => {
            const dot = a.color_hex
                ? `<span class="d-inline-block rounded-circle border me-1" style="width:12px;height:12px;background:${a.color_hex}"></span>` : '';
            return `<span class="badge bg-light text-dark border me-1">${dot}<small class="text-muted">${a.attr}:</small> ${a.value}</span>`;
        }).join('');

        tbody.insertAdjacentHTML('beforeend', `<tr data-variant-id="${v.id}">
            <td class="ps-3 text-muted fs-12">${rowIndex}</td>
            <td>${attrs || v.display_name || '<span class="text-muted fst-italic fs-12">Sin atributos</span>'}</td>
            <td><input type="text" class="form-control form-control-sm var-sku" value="${v.sku}" required></td>
            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm var-cost" value="${v.cost ?? ''}" placeholder="—"></td>
            <td class="text-center">
                <div class="form-check form-switch d-flex justify-content-center mb-0">
                    <input class="form-check-input var-active" type="checkbox" role="switch" ${v.is_active ? 'checked' : ''}>
                </div>
            </td>
            <td class="text-center">
                <div class="form-check form-switch d-flex justify-content-center mb-0">
                    <input class="form-check-input var-default" type="checkbox" role="switch">
                </div>
            </td>
            <td class="text-center"><span class="text-muted fs-12">—</span></td>
            <td>
                <button type="button" class="btn btn-sm btn-ghost-danger btn-delete-variant" data-variant-id="${v.id}" title="Eliminar">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </td>
        </tr>`);

        const newChk = tbody.lastElementChild?.querySelector('.var-default');
        newChk?.addEventListener('change', function () {
            if (this.checked) tbody.querySelectorAll('.var-default').forEach(c => { if (c !== this) c.checked = false; });
        });

        countBadge.textContent = tbody.querySelectorAll('tr').length;
    }

    function toggleIepsInput() {
        const iepsTypeEl = document.getElementById('ieps_type');
        if (!iepsTypeEl) return;
        const iepsType = iepsTypeEl.value;
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

    document.addEventListener('DOMContentLoaded', () => {
        toggleIepsInput();
    });

})();
</script>
@endsection

