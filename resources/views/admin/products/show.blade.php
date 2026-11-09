@extends('layouts.master')
@section('title') {{ $product->name }} — Detalle @endsection

@section('css')
    <!-- SweetAlert2 -->
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/libs/swiper/swiper-bundle.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        /* ── Variant image gallery ─────────────────────────── */
        .variant-thumb {
            width: 56px; height: 56px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: border-color .15s, transform .15s;
        }
        .variant-thumb:hover { border-color: var(--vz-primary); transform: scale(1.05); }
        .variant-thumb.active { border-color: var(--vz-primary); }

        /* ── Upload dropzone ────────────────────────────────── */
        .img-dropzone {
            border: 2px dashed var(--vz-border-color);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .img-dropzone:hover, .img-dropzone.drag-over {
            border-color: var(--vz-primary);
            background: rgba(var(--vz-primary-rgb), .04);
        }
        .img-dropzone input[type="file"] { display:none; }

        /* ── Variant row ────────────────────────────────────── */
        .variant-row { transition: background .15s; }
        .variant-row:hover { background: rgba(var(--vz-primary-rgb), .03); }
        .variant-main-thumb {
            width: 40px; height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
@endsection

@section('content')
<x-breadcrumb />

{{-- ── INFO PRINCIPAL ─────────────────────────────────────────────────── --}}
<div class="row gx-lg-4 mb-4">
    {{-- Galería producto base --}}
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                @php $hasImages = $product->files->count() > 0; @endphp
                @if($hasImages)
                    <div class="swiper product-thumbnail-slider p-2 rounded bg-light mb-2">
                        <div class="swiper-wrapper">
                            @foreach($product->files as $file)
                            <div class="swiper-slide">
                                <img src="{{ asset('storage/' . $file->path) }}" alt="{{ $product->name }}"
                                     class="img-fluid d-block rounded" style="max-height:280px;object-fit:contain;margin:auto">
                            </div>
                            @endforeach
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                    <div class="swiper product-nav-slider">
                        <div class="swiper-wrapper">
                            @foreach($product->files as $file)
                            <div class="swiper-slide">
                                <div class="nav-slide-item">
                                    <img src="{{ asset('storage/' . $file->path) }}" alt=""
                                         class="img-fluid d-block rounded" style="height:60px;object-fit:cover">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded">
                        <i class="ri-image-line display-3 text-muted"></i>
                        <p class="mt-2 text-muted mb-0">Sin imágenes del producto base</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Datos del producto --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <h5 class="card-title mb-0 flex-grow-1">{{ $product->name }}</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modal-print-labels">
                        <i class="ri-printer-line me-1"></i> Imprimir Etiquetas
                    </button>
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary">
                        <i class="ri-pencil-line me-1"></i> Editar
                    </a>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-light">
                        <i class="ri-arrow-left-line me-1"></i> Volver
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-lg-4">
                        <p class="text-muted fs-12 mb-1">SKU</p>
                        <p class="fw-semibold mb-0">{{ $product->sku }}</p>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <p class="text-muted fs-12 mb-1">Tipo</p>
                        <span class="badge {{ $product->type === 'variable' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                            {{ ucfirst($product->type ?? 'simple') }}
                        </span>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <p class="text-muted fs-12 mb-1">Estado</p>
                        <span class="badge {{ $product->status ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                            {{ $product->status ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <p class="text-muted fs-12 mb-1">Marca</p>
                        <p class="fw-semibold mb-0">{{ $product->brand->name ?? '—' }}</p>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <p class="text-muted fs-12 mb-1">Proveedor Principal / Distribuidor</p>
                        <p class="mb-0">
                            @if($product->supplier)
                                <span class="badge bg-success-subtle text-success fw-semibold"><i class="ri-truck-line me-1"></i>{{ $product->supplier->name }}</span>
                            @else
                                <span class="badge bg-light text-muted border"><i class="ri-question-line me-1"></i>Sin prov. asignado</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <p class="text-muted fs-12 mb-1">Categorías</p>
                        <p class="mb-0">
                            @forelse($product->categories as $cat)
                                <span class="badge bg-light text-dark border">{{ $cat->name }}</span>
                            @empty <span class="text-muted">—</span>
                            @endforelse
                        </p>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <p class="text-muted fs-12 mb-1">Costo base</p>
                        <p class="fw-semibold mb-0">${{ number_format($product->base_cost, 2) }}</p>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <p class="text-muted fs-12 mb-1">Impuesto IEPS (SAT)</p>
                        <p class="mb-0">
                            @if(($product->ieps_type ?? 'exempt') === 'percentage' && $product->ieps_value > 0)
                                <span class="badge bg-warning-subtle text-warning">Porcentual ({{ $product->ieps_value * 100 }}%)</span>
                            @elseif(($product->ieps_type ?? 'exempt') === 'fixed_quota' && $product->ieps_value > 0)
                                <span class="badge bg-warning-subtle text-warning">Cuota Fija (${{ number_format($product->ieps_value, 2) }}/u)</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Exento / 0%</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($product->description)
                <hr>
                <div>
                    <p class="text-muted fs-12 mb-1">Descripción</p>
                    <p class="mb-0">{!! nl2br(e($product->description)) !!}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── SECCIÓN DE VARIANTES ──────────────────────────────────────────────── --}}
@if($product->isVariable())
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="card-title mb-0 flex-grow-1">
                    <i class="ri-stack-line me-1 text-primary"></i>
                    Variantes
                    <span class="badge bg-secondary ms-2">{{ $product->variants->count() }}</span>
                </h5>
            </div>

            @if($product->variants->isEmpty())
            <div class="card-body text-center py-5 text-muted">
                <i class="ri-stack-line fs-32 d-block mb-2"></i>
                <p>No hay variantes. <a href="{{ route('products.edit', $product) }}">Ir a editar</a> para crearlas.</p>
            </div>
            @else
            <div class="card-body p-0">
                {{-- Alerta AJAX --}}
                <div id="variants-alert" class="mx-3 mt-3" style="display:none"></div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px"></th>
                                <th>Combinación / SKU</th>
                                <th style="width:110px">Costo</th>
                                <th style="width:80px" class="text-center">Activa</th>
                                <th style="width:80px" class="text-center">Defecto</th>
                                <th style="width:120px" class="text-center">Imágenes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->variants as $variant)
                            @php
                                $varImg = $variant->files->first();
                                $thumb  = $varImg ? asset('storage/' . $varImg->path) : null;
                                $imgCount = $variant->files->count();
                                $effectivePrice = $variant->price ?? $product->base_cost;
                            @endphp
                            <tr class="variant-row" id="variant-row-{{ $variant->id }}">
                                {{-- Thumbnail --}}
                                <td class="ps-3">
                                    @if($thumb)
                                        <img src="{{ $thumb }}" class="variant-main-thumb" alt="" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="variant-main-thumb bg-light align-items-center justify-content-center text-muted rounded" style="display: none;">
                                            <i class="ri-image-line fs-16"></i>
                                        </div>
                                    @else
                                        <div class="variant-main-thumb bg-light d-flex align-items-center justify-content-center text-muted rounded">
                                            <i class="ri-image-line fs-16"></i>
                                        </div>
                                    @endif
                                </td>

                                {{-- Combinación + SKU --}}
                                <td>
                                    <div class="d-flex flex-wrap gap-1 mb-1">
                                        @foreach($variant->attributeValues->sortBy('attribute.order') as $av)
                                            <span class="badge bg-light text-dark border">
                                                @if($av->color_hex)
                                                    <span class="d-inline-block rounded-circle me-1"
                                                          style="width:9px;height:9px;background:{{ $av->color_hex }};vertical-align:middle"></span>
                                                @endif
                                                <small class="text-muted">{{ $av->attribute->name ?? '' }}:</small>
                                                {{ $av->value }}
                                            </span>
                                        @endforeach
                                        @if($variant->attributeValues->isEmpty())
                                            <span class="text-muted fst-italic fs-12">Sin atributos</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">SKU: <strong>{{ $variant->sku }}</strong></small>
                                </td>

                                {{-- Costo --}}
                                <td>
                                    @if($variant->cost)
                                        ${{ number_format($variant->cost, 2) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Activa --}}
                                <td class="text-center">
                                    @if($variant->is_active)
                                        <span class="badge bg-success-subtle text-success"><i class="ri-check-line"></i></span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary"><i class="ri-close-line"></i></span>
                                    @endif
                                </td>

                                {{-- Defecto --}}
                                <td class="text-center">
                                    @if($variant->is_default)
                                        <span class="badge bg-primary-subtle text-primary"><i class="ri-star-fill"></i></span>
                                    @else
                                        <span class="text-muted fs-12">—</span>
                                    @endif
                                </td>

                                {{-- Imágenes --}}
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm {{ $imgCount > 0 ? 'btn-info' : 'btn-outline-secondary' }} btn-manage-images"
                                            data-variant-id="{{ $variant->id }}"
                                            data-variant-sku="{{ $variant->sku }}"
                                            data-images-url="{{ route('products.variants.images', [$product, $variant]) }}"
                                            data-upload-url="{{ route('products.variants.images.upload', [$product, $variant]) }}"
                                            data-delete-url="{{ url('products/' . $product->id . '/variants/' . $variant->id . '/images') }}"
                                            data-bs-toggle="modal" data-bs-target="#modal-variant-images">
                                        <i class="ri-image-line me-1"></i>
                                        {{ $imgCount > 0 ? $imgCount : '+ Añadir' }}
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── SECCIÓN DE INVENTARIO Y BODEGAS ────────────────────────────────────── --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="card-title mb-0 flex-grow-1">
                    <i class="ri-store-2-line me-1 text-primary"></i>
                    Disponibilidad por Sucursal y Bodega
                </h5>
            </div>
            <div class="card-body">
                @php
                    $invRecords = \App\Models\Inventory::with(['store', 'warehouse', 'variant.attributeValues.attribute'])
                        ->where('product_id', $product->id)
                        ->get();
                @endphp
                @if($invRecords->isEmpty())
                    <p class="text-muted mb-0 fs-13">No hay registros de inventario ingresados para este producto.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 fs-13">
                            <thead class="table-light">
                                <tr>
                                    <th>Sucursal</th>
                                    <th>Bodega / Ubicación</th>
                                    @if($product->isVariable())
                                        <th>Variante</th>
                                    @endif
                                    <th class="text-center">Stock Físico</th>
                                    <th class="text-center">Apartado</th>
                                    <th class="text-center">Disponible</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invRecords as $inv)
                                    <tr>
                                        <td class="fw-semibold">{{ $inv->store->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ ($inv->warehouse->type ?? '') === 'exhibition' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $inv->warehouse->name ?? 'Bodega Principal' }}
                                            </span>
                                        </td>
                                        @if($product->isVariable())
                                            <td>
                                                {{ $inv->variant ? $inv->variant->attributeValues->map(fn($av) => $av->attribute->name . ': ' . $av->value)->implode(' / ') : '—' }}
                                            </td>
                                        @endif
                                        <td class="text-center">{{ $inv->quantity }}</td>
                                        <td class="text-center"><span class="badge bg-warning-subtle text-warning">{{ $inv->reserved_quantity }}</span></td>
                                        <td class="text-center fw-bold {{ $inv->available_quantity <= 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $inv->available_quantity }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL — Imágenes de Variante                                           --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modal-variant-images" tabindex="-1" aria-labelledby="modal-variant-images-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-variant-images-label">
                    <i class="ri-image-line me-1 text-info"></i>
                    Imágenes de variante — <span id="modal-variant-sku" class="text-muted fw-normal"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Aviso de herencia --}}
                <div id="inherited-notice" class="alert alert-info py-2 fs-13" style="display:none">
                    <i class="ri-information-line me-1"></i>
                    Esta variante no tiene imágenes propias — se muestran las del producto base.
                    Sube imágenes para sobreescribir la herencia.
                </div>

                {{-- Galería --}}
                <div id="img-gallery" class="d-flex flex-wrap gap-3 mb-4">
                    <div id="img-gallery-loading" class="text-center w-100 py-3">
                        <div class="spinner-border text-primary spinner-border-sm"></div>
                        <span class="ms-2 text-muted fs-13">Cargando imágenes...</span>
                    </div>
                </div>

                <hr>

                {{-- Upload zone --}}
                <div class="img-dropzone" id="img-dropzone">
                    <input type="file" id="img-file-input" accept="image/*" multiple>
                    <i class="ri-upload-cloud-line fs-32 text-muted d-block mb-1"></i>
                    <p class="mb-1 fw-semibold">Arrastra imágenes aquí o haz clic para seleccionar</p>
                    <p class="fs-12 text-muted mb-0">JPEG, PNG, WebP o GIF · Máx. 4 MB por imagen</p>
                </div>

                {{-- Progress --}}
                <div id="upload-progress" class="mt-3" style="display:none">
                    <div class="progress" style="height:6px">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" id="upload-bar" style="width:0%"></div>
                    </div>
                    <p class="fs-12 text-muted mt-1 mb-0" id="upload-status">Subiendo...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/swiper/swiper-bundle.min.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/ecommerce-product-details.init.js') }}"></script>
<script>
(function () {
    /* ── CSRF ──────────────────────────────────────────────────────── */
    function getCsrfToken() {
        const c = document.cookie.split(';').map(x => x.trim()).find(x => x.startsWith('XSRF-TOKEN='));
        if (c) return decodeURIComponent(c.split('=')[1]);
        const inp = document.querySelector('input[name="_token"]');
        return inp ? inp.value : '';
    }
    const csrf = getCsrfToken();
    const headers = { 'X-XSRF-TOKEN': csrf, 'Accept': 'application/json' };

    /* ── Referencias modal ─────────────────────────────────────────── */
    const modalEl        = document.getElementById('modal-variant-images');
    const skuLabel       = document.getElementById('modal-variant-sku');
    const gallery        = document.getElementById('img-gallery');
    const galleryLoading = document.getElementById('img-gallery-loading');
    const inheritNotice  = document.getElementById('inherited-notice');
    const dropzone       = document.getElementById('img-dropzone');
    const fileInput      = document.getElementById('img-file-input');
    const uploadProgress = document.getElementById('upload-progress');
    const uploadBar      = document.getElementById('upload-bar');
    const uploadStatus   = document.getElementById('upload-status');
    const alertEl        = document.getElementById('variants-alert');

    let currentBtn = null;

    /* ── Abrir modal ───────────────────────────────────────────────── */
    modalEl?.addEventListener('show.bs.modal', async (event) => {
        const button = event.relatedTarget;
        if (!button) return;
        currentBtn = button;

        skuLabel.textContent = button.dataset.variantSku;
        gallery.innerHTML = '';
        gallery.appendChild(galleryLoading);
        galleryLoading.style.display = '';
        inheritNotice.style.display  = 'none';
        uploadProgress.style.display = 'none';

        await loadImages(button.dataset.imagesUrl);
    });

    /* ── Cargar imágenes ───────────────────────────────────────────── */
    async function loadImages(url) {
        try {
            const r    = await fetch(url, { headers });
            const data = await r.json();

            galleryLoading.style.display = 'none';
            inheritNotice.style.display  = data.inherited ? '' : 'none';

            if (data.images.length === 0) {
                gallery.innerHTML = `<p class="text-muted fs-13 w-100 text-center py-2">Sin imágenes aún.</p>`;
                return;
            }

            gallery.innerHTML = '';
            data.images.forEach(img => addImageThumb(img, data.inherited));
        } catch {
            galleryLoading.style.display = 'none';
            gallery.innerHTML = `<p class="text-danger fs-13">Error al cargar imágenes.</p>`;
        }
    }

    /* ── Añadir thumbnail a galería ────────────────────────────────── */
    function addImageThumb(img, inherited) {
        const wrap = document.createElement('div');
        wrap.className = 'position-relative';
        wrap.innerHTML = `
            <img src="${img.url}" class="variant-thumb" alt="${img.name}" title="${img.name}">
            ${!inherited ? `<button type="button" class="btn btn-danger btn-xs position-absolute top-0 end-0 p-0 rounded-circle btn-del-img"
                    style="width:18px;height:18px;line-height:1;font-size:10px"
                    data-file-id="${img.id}"><i class="ri-close-line"></i></button>` : ''}
        `;

        // Eliminar imagen
        wrap.querySelector('.btn-del-img')?.addEventListener('click', async () => {
            if (!confirm('¿Eliminar esta imagen?')) return;
            const delUrl = `${currentBtn.dataset.deleteUrl}/${img.id}`;
            const r = await fetch(delUrl, { method: 'DELETE', headers });
            if (r.ok) {
                wrap.remove();
                updateRowThumb();
                if (!gallery.querySelector('img')) {
                    gallery.innerHTML = `<p class="text-muted fs-13 w-100 text-center py-2">Sin imágenes aún.</p>`;
                }
            }
        });

        gallery.appendChild(wrap);
    }

    /* ── Dropzone ──────────────────────────────────────────────────── */
    dropzone.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        uploadFiles([...e.dataTransfer.files]);
    });
    fileInput.addEventListener('change', () => uploadFiles([...fileInput.files]));

    async function uploadFiles(files) {
        if (!currentBtn || !files.length) return;
        const imgFiles = files.filter(f => f.type.startsWith('image/'));
        if (!imgFiles.length) return;

        uploadProgress.style.display = '';
        uploadBar.style.width = '0%';

        for (let i = 0; i < imgFiles.length; i++) {
            uploadStatus.textContent = `Subiendo ${i + 1} de ${imgFiles.length}...`;
            uploadBar.style.width = `${Math.round(((i) / imgFiles.length) * 100)}%`;

            const fd = new FormData();
            fd.append('image', imgFiles[i]);

            const r = await fetch(currentBtn.dataset.uploadUrl, {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: fd,
            });

            if (r.ok) {
                const data = await r.json();
                // Quitar mensaje "sin imágenes"
                const empty = gallery.querySelector('p.text-muted');
                if (empty) empty.remove();
                // Quitar heredado si aplica
                inheritNotice.style.display = 'none';
                addImageThumb(data.image, false);
                updateRowThumb(data.image.url);
            }
        }

        uploadBar.style.width = '100%';
        uploadStatus.textContent = 'Completado';
        setTimeout(() => { uploadProgress.style.display = 'none'; }, 1500);
        fileInput.value = '';
    }

    /* ── Actualizar thumbnail en la fila de la tabla ───────────────── */
    function updateRowThumb(url) {
        if (!currentBtn) return;
        const varId = currentBtn.dataset.variantId;
        const row   = document.getElementById(`variant-row-${varId}`);
        if (!row) return;

        const thumbCell = row.querySelector('td:first-child');
        if (!thumbCell) return;

        const imgEls = gallery.querySelectorAll('img.variant-thumb');
        const imgCount = imgEls.length;
        const isInherited = inheritNotice.style.display !== 'none';

        if (imgCount > 0 && !isInherited) {
            const firstImgUrl = url || imgEls[0].src;
            thumbCell.innerHTML = `<img src="${firstImgUrl}" class="variant-main-thumb" alt="">`;
            currentBtn.className = 'btn btn-sm btn-info btn-manage-images';
            currentBtn.innerHTML = `<i class="ri-image-line me-1"></i>${imgCount}`;
        } else {
            thumbCell.innerHTML = `<div class="variant-main-thumb bg-light d-flex align-items-center justify-content-center text-muted rounded"><i class="ri-image-line fs-16"></i></div>`;
            currentBtn.className = 'btn btn-sm btn-outline-secondary btn-manage-images';
            currentBtn.innerHTML = `<i class="ri-image-line me-1"></i>+ Añadir`;
        }
    }

})();
</script>

<!-- SweetAlert2 -->
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<!-- CDN para generación de PDFs con PDFme, pdf-lib, jsPDF y JsBarcode -->
<script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<!-- PDFme se carga con import() dinámico en el momento que se necesita (click handler) -->
<!-- No se usa window.pdfmeXxx para evitar race conditions entre módulos y scripts -->


<!-- MODAL — Imprimir Etiquetas del Producto -->
<div class="modal fade" id="modal-print-labels" tabindex="-1" aria-labelledby="modal-print-labels-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient bg-primary text-white p-3">
                <h5 class="modal-title text-white" id="modal-print-labels-label">
                    <i class="ri-printer-line align-middle me-1"></i>
                    Imprimir Etiquetas / Precios
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">1. Seleccionar Formato de Impresión</label>
                    <div class="form-check card p-2 mb-2 border-primary-subtle cursor-pointer hover-shadow" style="transition: all 0.2s;">
                        <input class="form-check-input ms-0 me-2" type="radio" name="printFormat" id="formatPriceTag" value="price_tag" checked>
                        <label class="form-check-label fw-semibold text-primary" for="formatPriceTag">
                            🏷️ PDF de Precios (Formato Tienda Física - Carta)
                        </label>
                        <small class="text-muted ms-4">Ideal para folletos informativos de clientes con precios de lista, venta y rebajados.</small>
                    </div>
                    
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

                    <div class="form-check card p-2 mb-2 border-light cursor-pointer d-flex justify-content-between align-items-center flex-row">
                        <div class="flex-grow-1">
                            <input class="form-check-input ms-0 me-2" type="radio" name="printFormat" id="formatA4" value="barcode_a4">
                            <label class="form-check-label fw-semibold text-dark mb-0" for="formatA4">
                                🔢 Etiqueta Código de Barras A4 (4 por hoja, editable)
                            </label>
                            <small class="text-muted d-block ms-4">Impresión masiva/múltiple de 4 etiquetas idénticas en hoja A4 estándar.</small>
                        </div>
                        @if($tmplA4)
                            <a href="{{ route('pdf-templates.designer') }}?id={{ $tmplA4->id }}" class="btn btn-sm btn-link text-primary p-0 fs-12 ms-2 fw-semibold" title="Editar diseño de este formato A4" target="_blank">
                                <i class="ri-edit-line align-middle"></i> Editar
                            </a>
                        @endif
                    </div>

                    <div class="form-check card p-2 mb-2 border-light cursor-pointer d-flex justify-content-between align-items-center flex-row">
                        <div class="flex-grow-1">
                            <input class="form-check-input ms-0 me-2" type="radio" name="printFormat" id="format4x6" value="barcode_4x6">
                            <label class="form-check-label fw-semibold text-dark mb-0" for="format4x6">
                                🔢 Etiqueta Código de Barras 4X6" (Individual, editable)
                            </label>
                            <small class="text-muted d-block ms-4">Formato estándar de 10x15 cm para impresoras térmicas de etiquetas (Zebra, Dymo).</small>
                        </div>
                        @if($tmpl4x6)
                            <a href="{{ route('pdf-templates.designer') }}?id={{ $tmpl4x6->id }}" class="btn btn-sm btn-link text-primary p-0 fs-12 ms-2 fw-semibold" title="Editar diseño del formato 4x6" target="_blank">
                                <i class="ri-edit-line align-middle"></i> Editar
                            </a>
                        @endif
                    </div>

                    <div class="form-check card p-2 border-light cursor-pointer mb-2 d-flex justify-content-between align-items-center flex-row">
                        <div class="flex-grow-1">
                            <input class="form-check-input ms-0 me-2" type="radio" name="printFormat" id="formatBrother" value="barcode_brother">
                            <label class="form-check-label fw-semibold text-dark mb-0" for="formatBrother">
                                🔢 Etiqueta Código de Barras 2-3/7 X 4" (Individual, editable)
                            </label>
                            <small class="text-muted d-block ms-4">Formato pequeño de 62x100mm para etiquetas rápidas en sucursal.</small>
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
                                <input class="form-check-input ms-0 me-2" type="radio" name="printFormat" id="formatTmpl{{ $tmpl->id }}" value="custom_{{ $tmpl->id }}">
                                <label class="form-check-label fw-semibold text-dark mb-0" for="formatTmpl{{ $tmpl->id }}">
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
                    <label for="printStoreId" class="form-label fw-bold">2. Sucursal (Determina precios y márgenes)</label>
                    <select class="form-select" id="printStoreId">
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
                    <small class="text-muted">Los precios se calcularán dinámicamente con los márgenes de esta sucursal.</small>
                </div>
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="btnExecutePrint">
                    <i class="ri-printer-line me-1"></i> Generar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnPrint = document.getElementById('btnExecutePrint');

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

        function imprimirEtiquetaNativa(template, data) {
            const pageSchema = template.schemas[0] || {};
            const mmW = template.width || 100;
            const mmH = template.height || 50;
            const bgImage = template.basePdf || '';

            // Mapear los datos
            let fields = [];
            if (Array.isArray(pageSchema)) {
                fields = pageSchema.filter(f => f && f.name).map(f => ({ ...f, name: f.name }));
            } else {
                fields = Object.entries(pageSchema)
                    .filter(([, f]) => f)
                    .map(([key, f]) => ({ ...f, name: f.name || key }));
            }

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

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                Swal.fire('Error', 'El navegador bloqueó la ventana de impresión. Por favor habilita las ventanas emergentes.', 'error');
                return;
            }

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

            printWindow.document.write(`
<!DOCTYPE html>
<html>
<head>
    <title>Imprimir Etiqueta</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            body { margin: 0; }
            @page { margin: 0; size: ${mmW}mm ${mmH}mm; }
        }
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
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
    <div class="label-page">
        ${fieldsHtml}
    </div>

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
            }, 600);
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
        
        btnPrint.addEventListener('click', async function () {
            const format = document.querySelector('input[name="printFormat"]:checked').value;
            const storeId = document.getElementById('printStoreId').value;
            const productId = "{{ $product->id }}";

            Swal.fire({
                title: 'Generando Etiqueta...',
                text: 'Consultando base de datos y compilando PDF...',
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
                    // Impresión usando la Plantilla Personalizada diseñada en Configuración
                    const templateId = selectedFormat.split('_')[1];

                    // 1. Obtener datos y plantilla del ERP
                    const response = await fetch(`/pdf-templates/${templateId}/preview/${productId}?store_id=${storeId}`);
                    const res = await response.json();
                    if (!res.success) {
                        throw new Error(res.message || 'Error al consultar datos.');
                    }

                    const template = res.template;
                    
                    // Si es A4 Múltiple para un solo producto, duplicamos sus datos 4 veces
                    let productsData = res.data;
                    if (isA4Multiple) {
                        const singleData = res.data[0];
                        productsData = [singleData, singleData, singleData, singleData];
                    }

                    Swal.close();

                    // Cerrar modal
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modal-print-labels'));
                    if (modalInstance) modalInstance.hide();

                    // Ejecutar la impresión nativa
                    if (isA4Multiple) {
                        imprimirEtiquetaA4Multiple(template, productsData);
                    } else {
                        imprimirEtiquetaNativa(template, productsData[0]);
                    }
                    
                    return; // Detener ejecución para no usar jsPDF
                }

                // Impresión usando plantillas preconfiguradas con jsPDF (Rápida y robusta)
                const response = await fetch(`/pdf-templates/1/preview/${productId}?store_id=${storeId}`);
                const res = await response.json();
                
                if (!res.success) {
                    throw new Error(res.message || 'Error al consultar datos.');
                }

                const productData = res.data[0];
                const { jsPDF } = window.jspdf;
                let doc;

                if (format === 'price_tag') {
                    // Formato Folleto de Precios (Carta)
                    doc = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: 'letter'
                    });

                    const primaryColor = [79, 70, 229]; // Indigo
                    const secondaryColor = [225, 29, 72]; // Rose
                    const darkColor = [31, 41, 55];
                    const grayColor = [107, 114, 128];

                    // Dibujar marco elegante
                    doc.setDrawColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                    doc.setLineWidth(1);
                    doc.rect(10, 10, 195.9, 259.4);

                    // Cabecera
                    doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                    doc.rect(10, 10, 195.9, 25, 'F');

                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(22);
                    doc.setTextColor(255, 255, 255);
                    doc.text("MUEBLES UREÑA", 107.9, 26, { align: "center" });

                    // Nombre del Producto
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(26);
                    doc.setTextColor(darkColor[0], darkColor[1], darkColor[2]);
                    const splitName = doc.splitTextToSize(productData.nombre_producto, 175);
                    doc.text(splitName, 20, 60);

                    const nameHeight = splitName.length * 10;
                    let currentY = 60 + nameHeight + 5;

                    // Marca y Categoría
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(13);
                    doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                    doc.text(`Categoría: ${productData.categoria_producto}   |   Marca: ${productData.marca_producto}`, 20, currentY);

                    currentY += 12;

                    // Línea separadora
                    doc.setDrawColor(229, 231, 235);
                    doc.setLineWidth(0.5);
                    doc.line(20, currentY, 195.9, currentY);

                    currentY += 12;

                    // Precios
                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(15);
                    doc.setTextColor(grayColor[0], grayColor[1], grayColor[2]);
                    doc.text("Precio de Lista:", 20, currentY);
                    doc.setFont("helvetica", "bold");
                    doc.text(productData.precio_lista, 70, currentY);

                    currentY += 14;

                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(17);
                    doc.setTextColor(darkColor[0], darkColor[1], darkColor[2]);
                    doc.text("Precio de Venta:", 20, currentY);
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(22);
                    doc.setTextColor(secondaryColor[0], secondaryColor[1], secondaryColor[2]);
                    doc.text(productData.precio_venta, 70, currentY);

                    if (productData.precio_rebajado && productData.precio_rebajado !== '$0.00' && productData.precio_rebajado !== productData.precio_venta) {
                        currentY += 16;
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(16);
                        doc.setTextColor(5, 150, 105);
                        doc.text("¡Precio Especial de Rebaja!", 20, currentY);
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(22);
                        doc.text(productData.precio_rebajado, 100, currentY);
                    }

                    currentY += 22;

                    // Especificaciones
                    doc.setFillColor(249, 250, 251);
                    doc.rect(20, currentY, 175.9, 32, 'F');
                    doc.setDrawColor(229, 231, 235);
                    doc.rect(20, currentY, 175.9, 32, 'D');

                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(11);
                    doc.setTextColor(darkColor[0], darkColor[1], darkColor[2]);
                    doc.text("ESPECIFICACIONES DEL PRODUCTO", 25, currentY + 7);

                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(10);
                    doc.text(`Ancho: ${productData.ancho}    |    Alto: ${productData.alto}    |    Profundidad: ${productData.profundidad}`, 25, currentY + 16);
                    doc.text(`Peso Aproximado: ${productData.peso}`, 25, currentY + 24);

                    currentY += 46;

                    // Código de barras
                    if (productData.codigo_sku) {
                        try {
                            const barcodeB64 = generateBarcodeBase64(productData.codigo_sku);
                            doc.addImage(barcodeB64, 'PNG', 60, currentY, 95.9, 20);
                            doc.setFont("helvetica", "bold");
                            doc.setFontSize(11);
                            doc.setTextColor(darkColor[0], darkColor[1], darkColor[2]);
                            doc.text(`SKU: ${productData.codigo_sku}`, 107.9, currentY + 26, { align: "center" });
                        } catch (e) {
                            console.error(e);
                        }
                    }

                    // Pie de página
                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(9);
                    doc.setTextColor(grayColor[0], grayColor[1], grayColor[2]);
                    doc.text(`Fecha de Impresión: ${productData.fecha_impresion}`, 20, 260);

                } else if (format === 'barcode_a4') {
                    // Formato A4 Múltiple (4 etiquetas distribuidas)
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

                    labelsConfig.forEach(pos => {
                        // Dibujar recuadro de la etiqueta
                        doc.setDrawColor(209, 213, 219);
                        doc.setLineWidth(0.5);
                        doc.rect(pos.x, pos.y, 85, 120);

                        // Contenido de la etiqueta
                        // Nombre
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(10);
                        doc.setTextColor(31, 41, 55);
                        const splitTitle = doc.splitTextToSize(productData.nombre_producto, 75);
                        doc.text(splitTitle, pos.x + 5, pos.y + 10);

                        const titleHeight = Math.min(splitTitle.length * 4.5, 15);

                        // SKU
                        doc.setFont("helvetica", "normal");
                        doc.setFontSize(9);
                        doc.text(`SKU: ${productData.codigo_sku}`, pos.x + 5, pos.y + 12 + titleHeight);

                        // Marca y Categoría
                        doc.setFont("helvetica", "normal");
                        doc.setFontSize(8);
                        doc.setTextColor(107, 114, 128);
                        doc.text(`Marca: ${productData.marca_producto}`, pos.x + 5, pos.y + 18 + titleHeight);
                        doc.text(`Categoría: ${productData.categoria_producto}`, pos.x + 5, pos.y + 24 + titleHeight);

                        // Medidas
                        doc.text(`Medidas: ${productData.ancho}x${productData.alto}x${productData.profundidad}`, pos.x + 5, pos.y + 30 + titleHeight);

                        // Precios
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(10);
                        doc.setTextColor(31, 41, 55);
                        doc.text(`P. Lista: ${productData.precio_lista}`, pos.x + 5, pos.y + 40 + titleHeight);
                        
                        doc.setFontSize(12);
                        doc.setTextColor(225, 29, 72);
                        doc.text(`P. Venta: ${productData.precio_venta}`, pos.x + 5, pos.y + 47 + titleHeight);

                        // Código de barras
                        if (productData.codigo_sku) {
                            try {
                                const barcodeB64 = generateBarcodeBase64(productData.codigo_sku);
                                doc.addImage(barcodeB64, 'PNG', pos.x + 5, pos.y + 55 + titleHeight, 75, 16);
                                doc.setFont("helvetica", "normal");
                                doc.setFontSize(8);
                                doc.setTextColor(31, 41, 55);
                                doc.text(productData.codigo_sku, pos.x + 42.5, pos.y + 76 + titleHeight, { align: "center" });
                            } catch (e) {
                                console.error(e);
                            }
                        }
                    });

                } else if (format === 'barcode_4x6' || format === 'barcode_brother') {
                    const is4x6 = format === 'barcode_4x6';
                    const pageW = is4x6 ? 101.6 : 62;
                    const pageH = is4x6 ? 152.4 : 100;

                    doc = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: [pageW, pageH]
                    });

                    if (is4x6) {
                        // 4x6"
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(12);
                        doc.setTextColor(31, 41, 55);
                        const splitName = doc.splitTextToSize(productData.nombre_producto, 90);
                        doc.text(splitName, 5, 10);

                        const titleHeight = Math.min(splitName.length * 5, 20);

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(10);
                        doc.text(`SKU: ${productData.codigo_sku}`, 5, 14 + titleHeight);

                        doc.setFont("helvetica", "normal");
                        doc.setFontSize(9);
                        doc.setTextColor(107, 114, 128);
                        doc.text(`M: ${productData.marca_producto} | C: ${productData.categoria_producto}`, 5, 21 + titleHeight);
                        doc.text(`Medidas: ${productData.ancho} x ${productData.alto} x ${productData.profundidad} (${productData.peso})`, 5, 28 + titleHeight);

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(11);
                        doc.setTextColor(31, 41, 55);
                        doc.text(`P. Lista: ${productData.precio_lista}`, 5, 38 + titleHeight);

                        doc.setFontSize(14);
                        doc.setTextColor(225, 29, 72);
                        doc.text(`P. Venta: ${productData.precio_venta}`, 5, 46 + titleHeight);

                        if (productData.codigo_sku) {
                            try {
                                const barcodeB64 = generateBarcodeBase64(productData.codigo_sku);
                                doc.addImage(barcodeB64, 'PNG', 10, 58 + titleHeight, 81.6, 25);
                                doc.setFont("helvetica", "normal");
                                doc.setFontSize(9);
                                doc.setTextColor(31, 41, 55);
                                doc.text(productData.codigo_sku, 50.8, 88 + titleHeight, { align: "center" });
                            } catch (e) {
                                console.error(e);
                            }
                        }
                    } else {
                        // Brother 62 x 100 mm
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(9);
                        doc.setTextColor(31, 41, 55);
                        const splitName = doc.splitTextToSize(productData.nombre_producto, 52);
                        doc.text(splitName, 5, 8);

                        const titleHeight = Math.min(splitName.length * 4, 16);

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(8);
                        doc.text(`SKU: ${productData.codigo_sku}`, 5, 12 + titleHeight);

                        doc.setFont("helvetica", "normal");
                        doc.setFontSize(7.5);
                        doc.setTextColor(107, 114, 128);
                        doc.text(`M: ${productData.marca_producto} | C: ${productData.categoria_producto}`, 5, 17 + titleHeight);
                        doc.text(`Med: ${productData.ancho}x${productData.alto}x${productData.profundidad}`, 5, 22 + titleHeight);

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(9);
                        doc.setTextColor(31, 41, 55);
                        doc.text(`Lista: ${productData.precio_lista}`, 5, 30 + titleHeight);

                        doc.setFontSize(11);
                        doc.setTextColor(225, 29, 72);
                        doc.text(`Venta: ${productData.precio_venta}`, 5, 36 + titleHeight);

                        if (productData.codigo_sku) {
                            try {
                                const barcodeB64 = generateBarcodeBase64(productData.codigo_sku);
                                doc.addImage(barcodeB64, 'PNG', 5, 45 + titleHeight, 52, 16);
                                doc.setFont("helvetica", "normal");
                                doc.setFontSize(8);
                                doc.setTextColor(31, 41, 55);
                                doc.text(productData.codigo_sku, 31, 65 + titleHeight, { align: "center" });
                            } catch (e) {
                                console.error(e);
                            }
                        }
                    }
                }

                Swal.close();

                // Iniciar la descarga del PDF generado
                doc.save(`Etiqueta_${productData.codigo_sku}_${format}.pdf`);

                // Cerrar modal
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modal-print-labels'));
                if (modalInstance) modalInstance.hide();

                Swal.fire({
                    icon: 'success',
                    title: '¡PDF Generado con Éxito!',
                    text: 'La descarga ha comenzado de forma segura.',
                    timer: 2000,
                    showConfirmButton: false
                });

            } catch (err) {
                Swal.close();
                console.error(err);
                Swal.fire('Error', 'Ocurrió un error en el procesado del PDF: ' + err.message, 'error');
            }
        });
    });
</script>
@endsection

