@extends('layouts.master')
@section('title') Productos / Catálogo Base @endsection

@section('css')
    <!-- SweetAlert2 -->
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-checkbox-circle-line me-1 align-middle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-1 align-middle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Catálogo de Productos Base</h4>
                    <div class="flex-shrink-0 d-flex gap-2">
                        <button type="button" class="btn btn-info btn-sm" id="btnBulkPrint" disabled data-bs-toggle="modal" data-bs-target="#modal-bulk-print">
                            <i class="ri-printer-line me-1"></i> Imprimir Selección (<span id="bulkSelectCount">0</span>)
                        </button>
                        <a href="{{ route('products.create') }}" class="btn btn-success btn-label btn-sm">
                            <i class="ri-add-line label-icon align-middle fs-16 me-2"></i> Crear Producto
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Buscador y filtros --}}
                    <form action="{{ route('products.index') }}" method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search form-control-sm" value="{{ request('search') }}" placeholder="Buscar por nombre o SKU...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="category_id" class="form-select form-select-sm">
                                    <option value="">Todas las categorías</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="brand_id" class="form-select form-select-sm">
                                    <option value="">Todas las marcas</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="supplier_id" class="form-select form-select-sm">
                                    <option value="">Todos los proveedores</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Todos los estados</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                                </select>
                            </div>
                            <div class="col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ri-search-line me-1"></i> Buscar
                                </button>
                                @if(request()->hasAny(['search', 'category_id', 'brand_id', 'supplier_id', 'status']))
                                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="ri-close-line me-1"></i> Limpiar
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 40px;">
                                        <input type="checkbox" id="selectAllProducts" class="form-check-input">
                                    </th>
                                    <th scope="col" style="width: 80px;">
                                        <a href="{{ route('products.index', array_merge(request()->except('sort_field', 'sort_direction'), ['sort_field' => 'sku', 'sort_direction' => request('sort_field') === 'sku' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            SKU
                                            @if(request('sort_field') === 'sku')
                                                <i class="ri-arrow-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('products.index', array_merge(request()->except('sort_field', 'sort_direction'), ['sort_field' => 'name', 'sort_direction' => request('sort_field') === 'name' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Producto
                                            @if(request('sort_field') === 'name')
                                                <i class="ri-arrow-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">Categoría</th>
                                    <th scope="col">Marca / Proveedor</th>
                                    <th scope="col">
                                        <a href="{{ route('products.index', array_merge(request()->except('sort_field', 'sort_direction'), ['sort_field' => 'base_cost', 'sort_direction' => request('sort_field') === 'base_cost' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Costo de Compra
                                            @if(request('sort_field') === 'base_cost')
                                                <i class="ri-arrow-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col">
                                        <a href="{{ route('products.index', array_merge(request()->except('sort_field', 'sort_direction'), ['sort_field' => 'status', 'sort_direction' => request('sort_field') === 'status' && request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-dark">
                                            Estado
                                            @if(request('sort_field') === 'status')
                                                <i class="ri-arrow-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @else
                                                <i class="ri-arrow-up-down-line text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input product-select-checkbox" value="{{ $product->id }}">
                                        </td>
                                        <td class="fw-medium">{{ $product->sku }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-2">
                                                    @php
                                                        $mainImage = $product->files->first();
                                                        $imageUrl = $mainImage ? asset('storage/' . $mainImage->path) : null;
                                                    @endphp
                                                    @if($imageUrl)
                                                        <img src="{{ $imageUrl }}" alt="" class="avatar-sm rounded p-1 border" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <div class="avatar-sm rounded p-1 border bg-light text-muted align-items-center justify-content-center" style="display: none;">
                                                            <i class="ri-image-line fs-20"></i>
                                                        </div>
                                                    @else
                                                        <div class="avatar-sm rounded p-1 border bg-light text-muted d-flex align-items-center justify-content-center">
                                                            <i class="ri-image-line fs-20"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 mb-1">
                                                        <a href="{{ route('products.show', $product) }}" class="text-dark">{{ $product->name }}</a>
                                                        @if(($product->ieps_type ?? 'exempt') === 'percentage' && $product->ieps_value > 0)
                                                            <span class="badge bg-warning-subtle text-warning ms-1" title="IEPS Porcentual">IEPS {{ $product->ieps_value * 100 }}%</span>
                                                        @elseif(($product->ieps_type ?? 'exempt') === 'fixed_quota' && $product->ieps_value > 0)
                                                            <span class="badge bg-warning-subtle text-warning ms-1" title="IEPS Cuota Fija">IEPS ${{ number_format($product->ieps_value, 2) }}/u</span>
                                                        @endif
                                                    </h5>
                                                    <p class="text-muted mb-0 fs-12">{{ Str::limit($product->description, 30) }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @forelse($product->categories as $cat)
                                                    <span class="badge bg-info-subtle text-info">{{ $cat->name }}</span>
                                                @empty
                                                    <span class="text-muted fs-12">N/A</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <div><span class="badge bg-secondary-subtle text-secondary" title="Marca"><i class="ri-price-tag-3-line me-1"></i>{{ $product->brand->name ?? 'N/A' }}</span></div>
                                            @if($product->supplier)
                                                <div class="mt-1"><span class="badge bg-success-subtle text-success" title="Proveedor Principal"><i class="ri-truck-line me-1"></i>{{ $product->supplier->name }}</span></div>
                                            @else
                                                <div class="mt-1"><span class="badge bg-light text-muted" title="Sin Proveedor Principal"><i class="ri-question-line me-1"></i>Sin prov. asignado</span></div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>${{ number_format($product->base_cost, 2) }}</strong>
                                        </td>
                                        <td>
                                            @if($product->status)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-primary">
                                                    <i class="ri-eye-fill"></i>
                                                </a>
                                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-info">
                                                    <i class="ri-pencil-fill"></i>
                                                </a>
                                                @if($product->is_wildcard)
                                                    <button type="button" class="btn btn-sm btn-soft-secondary" disabled title="Este producto comodín es una entidad protegida del sistema y no se puede eliminar.">
                                                        <i class="ri-lock-2-line"></i>
                                                    </button>
                                                @else
                                                    <form id="del-prod-{{ $product->id }}" action="{{ route('products.destroy', $product) }}" method="POST" style="display:none;">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        data-confirm
                                                        data-form="del-prod-{{ $product->id }}"
                                                        data-title="Eliminar Producto"
                                                        data-message="¿Estás seguro de eliminar <strong>{{ $product->name }}</strong> del catálogo base? Esta acción no se puede deshacer."
                                                        data-btn-label="Sí, eliminar">
                                                        <i class="ri-delete-bin-fill"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="avatar-md mx-auto mb-4">
                                                <div class="avatar-title bg-light text-primary rounded-circle fs-4">
                                                    <i class="ri-search-line"></i>
                                                </div>
                                            </div>
                                            <h5 class="mb-2">No hay productos encontrados</h5>
                                            <p class="text-muted">Aún no se ha registrado ningún producto en el catálogo base.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $products->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL — Impresión Masiva de Etiquetas (Bulk Print) -->
    <div class="modal fade" id="modal-bulk-print" tabindex="-1" aria-labelledby="modal-bulk-print-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient bg-primary text-white p-3">
                    <h5 class="modal-title text-white" id="modal-bulk-print-label">
                        <i class="ri-printer-line align-middle me-1"></i>
                        Impresión Masiva de Etiquetas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">1. Formato de Etiquetas de Código de Barras</label>
                        
                        @php
                            \App\Models\PdfTemplate::checkAndSeedDefaultTemplates();
                            $allTemplates = \App\Models\PdfTemplate::where('document_type', 'producto')->where('status', true)->get();
                            
                            $tmplA4 = $allTemplates->firstWhere('name', 'Etiqueta A4 Múltiple (4 por hoja) Estándar');
                            $tmpl4x6 = $allTemplates->firstWhere('name', 'Etiqueta Térmica 4x6" Estándar');
                            $tmplBrother = $allTemplates->firstWhere('name', 'Etiqueta Térmica Brother 62x100mm Estándar');
                            
                            $customTemplates = $allTemplates->reject(function ($t) {
                                return in_array($t->name, [
                                    'Etiqueta A4 Múltiple (4 por hoja) Estándar',
                                    'Etiqueta Térmica 4x6" Estándar',
                                    'Etiqueta Térmica Brother 62x100mm Estándar'
                                ]);
                            });
                        @endphp

                        <!-- Opciones Estándar Editables -->
                        <div class="form-check card p-2 mb-2 border-primary-subtle cursor-pointer d-flex justify-content-between align-items-center flex-row">
                            <div class="flex-grow-1">
                                <input class="form-check-input ms-0 me-2" type="radio" name="bulkPrintFormat" id="bulkFormatA4" value="barcode_a4" checked>
                                <label class="form-check-label fw-semibold text-primary mb-0" for="bulkFormatA4">
                                    🔢 Hoja A4 Múltiple (4 etiquetas por hoja)
                                </label>
                                <small class="text-muted d-block ms-4">Distribuye los productos a razón de 4 por hoja (cuadrícula editable).</small>
                            </div>
                            @if($tmplA4)
                                <a href="{{ route('pdf-templates.designer') }}?id={{ $tmplA4->id }}" class="btn btn-sm btn-link text-primary p-0 fs-12 ms-2 fw-semibold" title="Editar diseño de este formato A4" target="_blank">
                                    <i class="ri-edit-line align-middle"></i> Editar
                                </a>
                            @endif
                        </div>

                        <div class="form-check card p-2 mb-2 border-light cursor-pointer d-flex justify-content-between align-items-center flex-row">
                            <div class="flex-grow-1">
                                <input class="form-check-input ms-0 me-2" type="radio" name="bulkPrintFormat" id="bulkFormat4x6" value="barcode_4x6">
                                <label class="form-check-label fw-semibold text-dark mb-0" for="bulkFormat4x6">
                                    🔢 Rollo Térmico 4X6" (1 por hoja, editable)
                                </label>
                                <small class="text-muted d-block ms-4">Etiqueta individual de 10x15 cm para impresoras industriales.</small>
                            </div>
                            @if($tmpl4x6)
                                <a href="{{ route('pdf-templates.designer') }}?id={{ $tmpl4x6->id }}" class="btn btn-sm btn-link text-primary p-0 fs-12 ms-2 fw-semibold" title="Editar diseño del formato 4x6" target="_blank">
                                    <i class="ri-edit-line align-middle"></i> Editar
                                </a>
                            @endif
                        </div>

                        <div class="form-check card p-2 border-light cursor-pointer mb-2 d-flex justify-content-between align-items-center flex-row">
                            <div class="flex-grow-1">
                                <input class="form-check-input ms-0 me-2" type="radio" name="bulkPrintFormat" id="bulkFormatBrother" value="barcode_brother">
                                <label class="form-check-label fw-semibold text-dark mb-0" for="bulkFormatBrother">
                                    🔢 Rollo Térmico 2-3/7 X 4" (1 por hoja, editable)
                                </label>
                                <small class="text-muted d-block ms-4">Etiqueta chica de 62x100mm para impresoras de escritorio.</small>
                            </div>
                            @if($tmplBrother)
                                <a href="{{ route('pdf-templates.designer') }}?id={{ $tmplBrother->id }}" class="btn btn-sm btn-link text-primary p-0 fs-12 ms-2 fw-semibold" title="Editar diseño del formato Brother" target="_blank">
                                    <i class="ri-edit-line align-middle"></i> Editar
                                </a>
                            @endif
                        </div>

                        @foreach($customTemplates as $tmpl)
                            <div class="form-check card p-2 mb-2 border-light cursor-pointer d-flex justify-content-between align-items-center flex-row">
                                <div class="flex-grow-1">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="bulkPrintFormat" id="bulkFormatTmpl{{ $tmpl->id }}" value="custom_{{ $tmpl->id }}">
                                    <label class="form-check-label fw-semibold text-dark mb-0" for="bulkFormatTmpl{{ $tmpl->id }}">
                                        🎨 Plantilla: {{ $tmpl->name }} (Personalizada)
                                    </label>
                                    <small class="text-muted d-block ms-4">Diseño personalizado de plantilla cargado en configuración.</small>
                                </div>
                                <a href="{{ route('pdf-templates.designer') }}?id={{ $tmpl->id }}" class="btn btn-sm btn-link text-primary p-0 fs-12 ms-2 fw-semibold" title="Editar diseño de esta plantilla" target="_blank">
                                    <i class="ri-edit-line align-middle"></i> Editar
                                </a>
                            </div>
                        @endforeach

                        <div class="text-center mt-3 pt-2">
                            <a href="{{ route('pdf-templates.designer') }}" class="btn btn-sm btn-soft-primary w-100" target="_blank">
                                <i class="ri-palette-line align-middle me-1"></i> Diseñar Nueva Plantilla de Etiqueta
                            </a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="bulkPrintStoreId" class="form-label fw-bold">2. Sucursal (Precios y Margen)</label>
                        <select class="form-select" id="bulkPrintStoreId">
                            @php
                                $activeStoreId = current_store_id();
                                $stores = \App\Models\Store::where('status', true)->get();
                            @endphp
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}" {{ $activeStoreId == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }} (Margen: {{ $st->margin_percentage }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btnExecuteBulkPrint">
                        <i class="ri-printer-line me-1"></i> Generar PDF Masivo
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<!-- SweetAlert2 -->
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<!-- CDN para generación de PDFs con PDFme, pdf-lib, jsPDF y JsBarcode -->
<script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<!-- PDFme se carga con import() dinámico en el momento que se necesita (click handler) -->
<!-- No se usa window.pdfmeXxx para evitar race conditions entre módulos y scripts -->


<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllProducts');
    const checkboxes = document.querySelectorAll('.product-select-checkbox');
    const btnBulkPrint = document.getElementById('btnBulkPrint');
    const bulkSelectCount = document.getElementById('bulkSelectCount');

    // ──────────────────────────────────────────────────────────
    // Selección Múltiple Checkboxes
    // ──────────────────────────────────────────────────────────
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkButton();
        });
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateBulkButton));

    function updateBulkButton() {
        const selected = getSelectedIds();
        if (selected.length > 0) {
            btnBulkPrint.removeAttribute('disabled');
            bulkSelectCount.textContent = selected.length;
        } else {
            btnBulkPrint.setAttribute('disabled', 'true');
            bulkSelectCount.textContent = '0';
        }
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.product-select-checkbox:checked')).map(cb => cb.value);
    }

    // ──────────────────────────────────────────────────────────
    // Ejecución de Impresión Masiva (PDFme + pdf-lib)
    // ──────────────────────────────────────────────────────────
    const btnExecute = document.getElementById('btnExecuteBulkPrint');

    function generateBarcodeBase64(value) {
        const canvas = document.createElement('canvas');
        JsBarcode(canvas, value, {
            format: "CODE128",
            width: 2,
            height: 40,
            displayValue: false,
            margin: 0
        });
        return canvas.toDataURL("image/png");
    }

    function mapInputData(template, productDataArray) {
        if (!template || !template.schemas || !productDataArray) return productDataArray;
        return productDataArray.map(data => {
            const mappedData = {};
            template.schemas.forEach(pageSchema => {
                // PDFme v3 usa array de objetos [{name, type, ...}]
                // Formato legacy usa objeto diccionario {key: field}
                // Normalizamos a un array plano de campos con su nombre
                let fields;
                if (Array.isArray(pageSchema)) {
                    // Formato PDFme v3: cada elemento es un objeto campo con propiedad `name`
                    fields = pageSchema.filter(f => f && f.name);
                } else {
                    // Formato legado: objeto {key: fieldObject}
                    fields = Object.entries(pageSchema)
                        .filter(([, f]) => f)
                        .map(([key, f]) => ({ ...f, name: f.name || key }));
                }

                fields.forEach(field => {
                    const key = field.name;
                    // Eliminar sufijos numéricos (_1, _2) para encontrar la clave base en los datos
                    const baseKey = key.replace(/_(?!(3|6|9|12)$)\d+$/, '');

                    if (data.hasOwnProperty(baseKey)) {
                        mappedData[key] = String(data[baseKey] ?? '');
                    } else if (data.hasOwnProperty(key)) {
                        mappedData[key] = String(data[key] ?? '');
                    } else {
                        // Mantener valor por defecto del campo si no hay dato real
                        mappedData[key] = field.content || '';
                    }
                });
            });
            return mappedData;
        });
    }

    function prepareTemplate(template) {
        if (!template || !template.schemas) return template;
        const newTemplate = JSON.parse(JSON.stringify(template));
        newTemplate.schemas = newTemplate.schemas.map(pageSchema => {
            if (Array.isArray(pageSchema)) {
                // Formato PDFme v3: array de objetos campo
                return pageSchema.map(field => {
                    if (!field) return field;
                    const newField = { ...field };
                    if (newField.type === 'text') {
                        newField.fontName = newField.fontName || 'Roboto';
                    }
                    return newField;
                });
            } else {
                // Formato legado: objeto diccionario — convertir a array para PDFme v3
                return Object.entries(pageSchema)
                    .filter(([, f]) => f)
                    .map(([key, field]) => {
                        const newField = { ...field, name: field.name || key };
                        if (newField.type === 'text') {
                            newField.fontName = newField.fontName || 'Roboto';
                        }
                        return newField;
                    });
            }
        });
        return newTemplate;
    }

    function imprimirEtiquetaNativaMasiva(template, productsData) {
        const pageSchema = template.schemas[0] || {};
        const mmW = template.width || 100;
        const mmH = template.height || 50;
        const bgImage = template.basePdf || '';

        // Mapear los campos
        let fields = [];
        if (Array.isArray(pageSchema)) {
            fields = pageSchema.filter(f => f && f.name).map(f => ({ ...f, name: f.name }));
        } else {
            fields = Object.entries(pageSchema)
                .filter(([, f]) => f)
                .map(([key, f]) => ({ ...f, name: f.name || key }));
        }

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            Swal.fire('Error', 'El navegador bloqueó la ventana de impresión emergente. Por favor habilítala.', 'error');
            return;
        }

        // Construir el HTML de todas las etiquetas
        let labelPagesHtml = '';
        
        productsData.forEach((data, index) => {
            const mappedFields = {};
            fields.forEach(field => {
                const key = field.name;
                const baseKey = key.replace(/_(?!(3|6|9|12)$)\d+$/, '');
                let val = '';
                if (data.hasOwnProperty(baseKey)) {
                    val = String(data[baseKey] ?? '');
                } else if (data.hasOwnProperty(key)) {
                    val = String(data[key] ?? '');
                } else {
                    val = field.content || '';
                }
                mappedFields[key] = {
                    ...field,
                    value: val
                };
            });

            let fieldsHtml = '';
            fields.forEach(field => {
                const mapped = mappedFields[field.name];
                const val = mapped.value;
                const left = field.position.x;
                const top = field.position.y;
                const width = field.width;
                const height = field.height;

                let contentHtml = '';
                if (field.type === 'barcodes.code128') {
                    contentHtml = `<svg class="barcode-svg" data-value="${val}" style="width: 100%; height: 100%;"></svg>`;
                } else if (field.type === 'qrcode') {
                    contentHtml = `<canvas class="qrcode-canvas" data-value="${val}" style="width: 100%; height: 100%;"></canvas>`;
                } else if (field.type === 'image') {
                    contentHtml = `<img src="${val}" style="width: 100%; height: 100%; object-fit: contain;">`;
                } else {
                    const fontSize = field.fontSize || 12;
                    const fontColor = field.fontColor || '#000000';
                    const alignment = field.alignment || 'left';
                    const valignment = field.valignment || 'center';
                    contentHtml = `<div style="font-size:${fontSize}pt;color:${fontColor};text-align:${alignment};width:100%;height:100%;white-space:pre-wrap;word-break:break-word;font-family:'Roboto',sans-serif;display:flex;align-items:${valignment === 'top' ? 'flex-start' : (valignment === 'bottom' ? 'flex-end' : 'center')};justify-content:${alignment === 'center' ? 'center' : (alignment === 'right' ? 'flex-end' : 'flex-start')};">${val}</div>`;
                }

                fieldsHtml += `
                    <div class="field-item" style="left: ${left}mm; top: ${top}mm; width: ${width}mm; height: ${height}mm;">
                        ${contentHtml}
                    </div>
                `;
            });

            labelPagesHtml += `
                <div class="label-page">
                    ${fieldsHtml}
                </div>
            `;
        });

        printWindow.document.write(`
<!DOCTYPE html>
<html>
<head>
    <title>Imprimir Etiquetas Masivas</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            body { margin: 0; }
            @page { margin: 0; size: ${mmW}mm ${mmH}mm; }
        }
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .label-page {
            position: relative;
            width: ${mmW}mm;
            height: ${mmH}mm;
            background-image: url('${bgImage}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            box-sizing: border-box;
            background-color: #ffffff;
            overflow: hidden;
            page-break-after: always;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .field-item {
            position: absolute;
            box-sizing: border-box;
            overflow: hidden;
        }
    </style>
</head>
<body>
    ${labelPagesHtml}

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"><\/script>
    <script>
        window.onload = function() {
            // Códigos de barra
            document.querySelectorAll('.barcode-svg').forEach(el => {
                const val = el.getAttribute('data-value');
                if (val) {
                    try {
                        JsBarcode(el, val, {
                            format: "CODE128",
                            displayValue: false,
                            margin: 0,
                            height: el.clientHeight,
                            width: 2
                        });
                    } catch (e) {
                        console.error('Error drawing barcode:', e);
                    }
                }
            });

            // Códigos QR
            document.querySelectorAll('.qrcode-canvas').forEach(el => {
                const val = el.getAttribute('data-value');
                if (val) {
                    try {
                        const size = Math.min(el.clientWidth, el.clientHeight);
                        QRCode.toCanvas(el, val, {
                            margin: 0,
                            width: size
                        });
                    } catch (e) {
                        console.error('Error drawing QR:', e);
                    }
                }
            });

            setTimeout(() => {
                window.print();
                window.close();
            }, 800);
        };
    <\/script>
</body>
</html>
        `);
        printWindow.document.close();
    }

    function imprimirEtiquetaA4Multiple(template, productsData) {
        const pageSchema = template.schemas[0] || {};
        const bgImage = template.basePdf || '';

        let fields = [];
        if (Array.isArray(pageSchema)) {
            fields = pageSchema.filter(f => f && f.name).map(f => ({ ...f, name: f.name }));
        } else {
            fields = Object.entries(pageSchema)
                .filter(([, f]) => f)
                .map(([key, f]) => ({ ...f, name: f.name || key }));
        }

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            Swal.fire('Error', 'El navegador bloqueó la ventana de impresión emergente. Por favor habilítala.', 'error');
            return;
        }

        const labelsConfig = [
            { x: 15, y: 15 },
            { x: 110, y: 15 },
            { x: 15, y: 150 },
            { x: 110, y: 150 }
        ];

        const N = productsData.length;
        const totalPagesNeeded = Math.ceil(N / 4);
        let labelPagesHtml = '';

        for (let p = 0; p < totalPagesNeeded; p++) {
            let gridCellsHtml = '';

            labelsConfig.forEach((pos, idx) => {
                const prodIdx = p * 4 + idx;
                if (prodIdx < N) {
                    const data = productsData[prodIdx];

                    const mappedFields = {};
                    fields.forEach(field => {
                        const key = field.name;
                        const baseKey = key.replace(/_(?!(3|6|9|12)$)\d+$/, '');
                        let val = '';
                        if (data.hasOwnProperty(baseKey)) {
                            val = String(data[baseKey] ?? '');
                        } else if (data.hasOwnProperty(key)) {
                            val = String(data[key] ?? '');
                        } else {
                            val = field.content || '';
                        }
                        mappedFields[key] = {
                            ...field,
                            value: val
                        };
                    });

                    let fieldsHtml = '';
                    fields.forEach(field => {
                        const mapped = mappedFields[field.name];
                        const val = mapped.value;
                        const left = field.position.x;
                        const top = field.position.y;
                        const width = field.width;
                        const height = field.height;

                        let contentHtml = '';
                        if (field.type === 'barcodes.code128') {
                            contentHtml = `<svg class="barcode-svg" data-value="${val}" style="width: 100%; height: 100%;"></svg>`;
                        } else if (field.type === 'qrcode') {
                            contentHtml = `<canvas class="qrcode-canvas" data-value="${val}" style="width: 100%; height: 100%;"></canvas>`;
                        } else if (field.type === 'image') {
                            contentHtml = `<img src="${val}" style="width: 100%; height: 100%; object-fit: contain;">`;
                        } else {
                            const fontSize = field.fontSize || 12;
                            const fontColor = field.fontColor || '#000000';
                            const alignment = field.alignment || 'left';
                            const valignment = field.valignment || 'center';
                            contentHtml = `<div style="font-size:${fontSize}pt;color:${fontColor};text-align:${alignment};width:100%;height:100%;white-space:pre-wrap;word-break:break-word;font-family:'Roboto',sans-serif;display:flex;align-items:${valignment === 'top' ? 'flex-start' : (valignment === 'bottom' ? 'flex-end' : 'center')};justify-content:${alignment === 'center' ? 'center' : (alignment === 'right' ? 'flex-end' : 'flex-start')};">${val}</div>`;
                        }

                        fieldsHtml += `
                            <div class="field-item" style="left: ${left}mm; top: ${top}mm; width: ${width}mm; height: ${height}mm;">
                                ${contentHtml}
                            </div>
                        `;
                    });

                    gridCellsHtml += `
                        <div class="grid-cell" style="left: ${pos.x}mm; top: ${pos.y}mm; width: 85mm; height: 120mm; position: absolute; border: 1px solid #d1d5db; box-sizing: border-box; overflow: hidden; background-color: #ffffff; background-image: url('${bgImage}'); background-size: 100% 100%;">
                            ${fieldsHtml}
                        </div>
                    `;
                }
            });

            labelPagesHtml += `
                <div class="a4-page">
                    ${gridCellsHtml}
                </div>
            `;
        }

        printWindow.document.write(`
<!DOCTYPE html>
<html>
<head>
    <title>Imprimir Etiquetas A4 Múltiples</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            body { margin: 0; }
            @page { margin: 0; size: 210mm 297mm; }
        }
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .a4-page {
            position: relative;
            width: 210mm;
            height: 297mm;
            box-sizing: border-box;
            background-color: #ffffff;
            overflow: hidden;
            page-break-after: always;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .field-item {
            position: absolute;
            box-sizing: border-box;
            overflow: hidden;
        }
    </style>
</head>
<body>
    ${labelPagesHtml}

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"><\/script>
    <script>
        window.onload = function() {
            // Códigos de barra
            document.querySelectorAll('.barcode-svg').forEach(el => {
                const val = el.getAttribute('data-value');
                if (val) {
                    try {
                        JsBarcode(el, val, {
                            format: "CODE128",
                            displayValue: false,
                            margin: 0,
                            height: 50,
                            width: 2
                        });
                    } catch (e) {
                        console.error(e);
                    }
                }
            });

            // Códigos QR
            document.querySelectorAll('.qrcode-canvas').forEach(el => {
                const val = el.getAttribute('data-value');
                if (val) {
                    try {
                        const size = Math.min(el.clientWidth, el.clientHeight) || 128;
                        QRCode.toCanvas(el, val, { margin: 0, width: size });
                    } catch (e) {
                        console.error(e);
                    }
                }
            });

            setTimeout(() => {
                window.print();
                window.close();
            }, 800);
        };
    <\/script>
</body>
</html>
        `);
        printWindow.document.close();
    }

    btnExecute.addEventListener('click', async function () {
        const format = document.querySelector('input[name="bulkPrintFormat"]:checked').value;
        const selectedIds = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        if (selectedIds.length === 0) {
            Swal.fire('Atención', 'No has seleccionado ningún producto.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Preparando Impresión Masiva...',
            text: 'Consultando base de datos y preparando las etiquetas...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Mapear formatos estándar a sus plantillas editables
        const tmplA4Id = "{{ $tmplA4 ? $tmplA4->id : '' }}";
        const tmpl4x6Id = "{{ $tmpl4x6 ? $tmpl4x6->id : '' }}";
        const tmplBrotherId = "{{ $tmplBrother ? $tmplBrother->id : '' }}";

        let selectedFormat = format;
        let isA4Multiple = false;

        if (format === 'barcode_a4') {
            selectedFormat = 'custom_' + tmplA4Id;
            isA4Multiple = true;
        } else if (format === 'barcode_4x6') {
            selectedFormat = 'custom_' + tmpl4x6Id;
        } else if (format === 'barcode_brother') {
            selectedFormat = 'custom_' + tmplBrotherId;
        }

        try {
            if (selectedFormat.startsWith('custom_')) {
                const templateId = selectedFormat.split('_')[1];

                // 1. Obtener la data dinámica masiva de los productos y la plantilla
                const [response, tmplResponse] = await Promise.all([
                    fetch('/pdf-templates/bulk-data', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            product_ids: selectedIds
                        })
                    }),
                    fetch(`/pdf-templates/${templateId}/preview/default`)
                ]);

                const [bulkRes, tmplRes] = await Promise.all([
                    response.json(),
                    tmplResponse.json()
                ]);

                if (!bulkRes.success) {
                    throw new Error(bulkRes.message || 'Error al obtener datos masivos.');
                }
                if (!tmplRes.success) {
                    throw new Error(tmplRes.message || 'Error al obtener la plantilla.');
                }

                const productsData = bulkRes.products_data;
                const template = tmplRes.template;
                const N = productsData.length;

                Swal.close();

                // Cerrar modal
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modal-bulk-print'));
                if (modalInstance) modalInstance.hide();

                // Desmarcar todos
                if (selectAll) selectAll.checked = false;
                checkboxes.forEach(cb => cb.checked = false);
                updateBulkButton();

                if (isA4Multiple) {
                    imprimirEtiquetaA4Multiple(template, productsData);
                } else {
                    imprimirEtiquetaNativaMasiva(template, productsData);
                }

                return; // Detener ejecución para no usar jsPDF
            }

            // 1. Obtener la data dinámica masiva de los productos desde el Backend para formatos estándar
            const response = await fetch('/pdf-templates/bulk-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_ids: selectedIds
                })
            });

            const res = await response.json();
            if (!res.success) {
                throw new Error(res.message || 'Error al obtener datos masivos.');
            }

            const productsData = res.products_data;
            const N = productsData.length;

            const { jsPDF } = window.jspdf;
            let doc;

            if (format === 'barcode_a4') {
                // Formato A4 Múltiple (4 etiquetas por hoja)
                doc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                const labelsConfig = [
                    { x: 15, y: 15 },
                    { x: 110, y: 15 },
                    { x: 15, y: 150 },
                    { x: 110, y: 150 }
                ];

                const totalPagesNeeded = Math.ceil(N / 4);

                for (let p = 0; p < totalPagesNeeded; p++) {
                    if (p > 0) {
                        doc.addPage();
                    }

                    labelsConfig.forEach((pos, idx) => {
                        const prodIdx = p * 4 + idx;
                        if (prodIdx < N) {
                            const pData = productsData[prodIdx];

                            // Dibujar recuadro
                            doc.setDrawColor(209, 213, 219);
                            doc.setLineWidth(0.5);
                            doc.rect(pos.x, pos.y, 85, 120);

                            // Nombre
                            doc.setFont("helvetica", "bold");
                            doc.setFontSize(10);
                            doc.setTextColor(31, 41, 55);
                            const splitTitle = doc.splitTextToSize(pData.nombre_producto, 75);
                            doc.text(splitTitle, pos.x + 5, pos.y + 10);

                            const titleHeight = Math.min(splitTitle.length * 4.5, 15);

                            // SKU
                            doc.setFont("helvetica", "normal");
                            doc.setFontSize(9);
                            doc.text(`SKU: ${pData.codigo_sku}`, pos.x + 5, pos.y + 12 + titleHeight);

                            // Marca y Categoría
                            doc.setFont("helvetica", "normal");
                            doc.setFontSize(8);
                            doc.setTextColor(107, 114, 128);
                            doc.text(`Marca: ${pData.marca_producto}`, pos.x + 5, pos.y + 18 + titleHeight);
                            doc.text(`Categoría: ${pData.categoria_producto}`, pos.x + 5, pos.y + 24 + titleHeight);

                            // Medidas
                            doc.text(`Medidas: ${pData.ancho}x${pData.alto}x${pData.profundidad}`, pos.x + 5, pos.y + 30 + titleHeight);

                            // Precios
                            doc.setFont("helvetica", "bold");
                            doc.setFontSize(10);
                            doc.setTextColor(31, 41, 55);
                            doc.text(`P. Lista: ${pData.precio_lista}`, pos.x + 5, pos.y + 40 + titleHeight);
                            
                            doc.setFontSize(12);
                            doc.setTextColor(225, 29, 72);
                            doc.text(`P. Venta: ${pData.precio_venta}`, pos.x + 5, pos.y + 47 + titleHeight);

                            // Código de barras
                            if (pData.codigo_sku) {
                                try {
                                    const barcodeB64 = generateBarcodeBase64(pData.codigo_sku);
                                    doc.addImage(barcodeB64, 'PNG', pos.x + 5, pos.y + 55 + titleHeight, 75, 16);
                                    doc.setFont("helvetica", "normal");
                                    doc.setFontSize(8);
                                    doc.setTextColor(31, 41, 55);
                                    doc.text(pData.codigo_sku, pos.x + 42.5, pos.y + 76 + titleHeight, { align: "center" });
                                } catch (e) {
                                    console.error(e);
                                }
                            }
                        }
                    });
                }

            } else {
                // Formato térmico individual (4x6 y Brother)
                const is4x6 = format === 'barcode_4x6';
                const pageW = is4x6 ? 101.6 : 62;
                const pageH = is4x6 ? 152.4 : 100;

                doc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: [pageW, pageH]
                });

                productsData.forEach((pData, idx) => {
                    if (idx > 0) {
                        doc.addPage([pageW, pageH]);
                    }

                    if (is4x6) {
                        // 4x6"
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(12);
                        doc.setTextColor(31, 41, 55);
                        const splitName = doc.splitTextToSize(pData.nombre_producto, 90);
                        doc.text(splitName, 5, 10);

                        const titleHeight = Math.min(splitName.length * 5, 20);

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(10);
                        doc.text(`SKU: ${pData.codigo_sku}`, 5, 14 + titleHeight);

                        doc.setFont("helvetica", "normal");
                        doc.setFontSize(9);
                        doc.setTextColor(107, 114, 128);
                        doc.text(`M: ${pData.marca_producto} | C: ${pData.categoria_producto}`, 5, 21 + titleHeight);
                        doc.text(`Medidas: ${pData.ancho} x ${pData.alto} x ${pData.profundidad} (${pData.peso})`, 5, 28 + titleHeight);

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(11);
                        doc.setTextColor(31, 41, 55);
                        doc.text(`P. Lista: ${pData.precio_lista}`, 5, 38 + titleHeight);

                        doc.setFontSize(14);
                        doc.setTextColor(225, 29, 72);
                        doc.text(`P. Venta: ${pData.precio_venta}`, 5, 46 + titleHeight);

                        if (pData.codigo_sku) {
                            try {
                                const barcodeB64 = generateBarcodeBase64(pData.codigo_sku);
                                doc.addImage(barcodeB64, 'PNG', 10, 58 + titleHeight, 81.6, 25);
                                doc.setFont("helvetica", "normal");
                                doc.setFontSize(9);
                                doc.setTextColor(31, 41, 55);
                                doc.text(pData.codigo_sku, 50.8, 88 + titleHeight, { align: "center" });
                            } catch (e) {
                                console.error(e);
                            }
                        }
                    } else {
                        // Brother 62 x 100 mm
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(9);
                        doc.setTextColor(31, 41, 55);
                        const splitName = doc.splitTextToSize(pData.nombre_producto, 52);
                        doc.text(splitName, 5, 8);

                        const titleHeight = Math.min(splitName.length * 4, 16);

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(8);
                        doc.text(`SKU: ${pData.codigo_sku}`, 5, 12 + titleHeight);

                        doc.setFont("helvetica", "normal");
                        doc.setFontSize(7.5);
                        doc.setTextColor(107, 114, 128);
                        doc.text(`M: ${pData.marca_producto} | C: ${pData.categoria_producto}`, 5, 17 + titleHeight);
                        doc.text(`Med: ${pData.ancho}x${pData.alto}x${pData.profundidad}`, 5, 22 + titleHeight);

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(9);
                        doc.setTextColor(31, 41, 55);
                        doc.text(`Lista: ${pData.precio_lista}`, 5, 30 + titleHeight);

                        doc.setFontSize(11);
                        doc.setTextColor(225, 29, 72);
                        doc.text(`Venta: ${pData.precio_venta}`, 5, 36 + titleHeight);

                        if (pData.codigo_sku) {
                            try {
                                const barcodeB64 = generateBarcodeBase64(pData.codigo_sku);
                                doc.addImage(barcodeB64, 'PNG', 5, 45 + titleHeight, 52, 16);
                                doc.setFont("helvetica", "normal");
                                doc.setFontSize(8);
                                doc.setTextColor(31, 41, 55);
                                doc.text(pData.codigo_sku, 31, 65 + titleHeight, { align: "center" });
                            } catch (e) {
                                console.error(e);
                            }
                        }
                    }
                });
            }

            Swal.close();

            // Descarga
            doc.save(`Etiquetas_Masivas_${format}.pdf`);

            // Cerrar modal
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modal-bulk-print'));
            if (modalInstance) modalInstance.hide();

            // Desmarcar todos
            if (selectAll) selectAll.checked = false;
            checkboxes.forEach(cb => cb.checked = false);
            updateBulkButton();

            Swal.fire({
                icon: 'success',
                title: '¡PDF Masivo Generado!',
                text: `Se han impreso exitosamente las etiquetas de ${N} productos.`,
                timer: 3000,
                showConfirmButton: false
            });

        } catch (err) {
            Swal.close();
            console.error(err);
            Swal.fire('Error', 'Ocurrió un error en la impresión masiva: ' + err.message, 'error');
        }
    });
});
</script>
@endsection
