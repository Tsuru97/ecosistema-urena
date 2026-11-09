@props(['model' => null])

@php
    $seo = $model ? $model->seoMetadata : null;
@endphp

<div class="card mt-4 border-primary border-opacity-25">
    <div class="card-header bg-primary-subtle align-items-center d-flex">
        <h5 class="card-title mb-0 flex-grow-1"><i class="ri-search-eye-line me-1"></i> Optimización para Buscadores (SEO)</h5>
        <div class="flex-shrink-0">
            <span class="badge bg-primary-subtle text-primary">Google, Indexación & Redes Sociales</span>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">La información proporcionada aquí determinará cómo aparece esta página en los resultados de búsqueda (como Google) y cuando se comparte en redes sociales (como WhatsApp o Facebook).</p>
        
        <div class="row g-3">
            <div class="col-md-12">
                <div>
                    <label for="custom_slug" class="form-label">Identificador URL (Slug)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted">{{ url('/') }}/.../</span>
                        <input type="text" class="form-control text-lowercase form-control-sm" id="custom_slug" name="custom_slug" value="{{ old('custom_slug', $model ? $model->slug : '') }}" placeholder="se-generara-automaticamente">
                    </div>
                    <small class="form-text text-muted">Aparecerá en el navegador. Si lo dejas vacío, el sistema lo autogenerará basado en el nombre (y el SKU si es un producto). Automáticamente evitará duplicados.</small>
                </div>
            </div>

            <div class="col-12">
                <hr class="mb-1 mt-1">
            </div>

            <div class="col-md-6">
                <div>
                    <label for="seo_meta_title" class="form-label">Meta Título (Meta Title)</label>
                    <input type="text" class="form-control form-control-sm" id="seo_meta_title" name="seo[meta_title]" value="{{ old('seo.meta_title', $seo->meta_title ?? '') }}" maxlength="60" placeholder="Ej: Comprar Sala Esquinera al mejor precio">
                    <small class="form-text text-muted">Idealmente entre 50 y 60 caracteres. Si se deja vacío, el sistema podría usar el nombre principal.</small>
                </div>
            </div>

            <div class="col-md-6">
                <div>
                    <label for="seo_robots" class="form-label">Reglas de Rastreo (Robots)</label>
                    <select class="form-select form-select-sm" id="seo_robots" name="seo[robots]">
                        <option value="index, follow" {{ old('seo.robots', $seo->robots ?? '') == 'index, follow' ? 'selected' : '' }}>Indexar y Seguir (Recomendado)</option>
                        <option value="noindex, follow" {{ old('seo.robots', $seo->robots ?? '') == 'noindex, follow' ? 'selected' : '' }}>NO Indexar pero Seguir</option>
                        <option value="noindex, nofollow" {{ old('seo.robots', $seo->robots ?? '') == 'noindex, nofollow' ? 'selected' : '' }}>Ocultar completamente de buscadores</option>
                    </select>
                </div>
            </div>

            <div class="col-md-12">
                <div>
                    <label for="seo_meta_description" class="form-label">Meta Descripción (Meta Description)</label>
                    <textarea class="form-control form-control-sm" id="seo_meta_description" name="seo[meta_description]" rows="2" maxlength="160" placeholder="Ej: Descubre nuestra colección de salas esquineras de tela con envío a domicilio...">{{ old('seo.meta_description', $seo->meta_description ?? '') }}</textarea>
                    <small class="form-text text-muted">Resumen persuasivo de 150-160 caracteres sobre la página.</small>
                </div>
            </div>

            <div class="col-md-6">
                <div>
                    <label for="seo_meta_keywords" class="form-label">Palabras Clave (Meta Keywords)</label>
                    <input type="text" class="form-control form-control-sm" id="seo_meta_keywords" name="seo[meta_keywords]" value="{{ old('seo.meta_keywords', $seo->meta_keywords ?? '') }}" placeholder="Ej: salas, esquinero, mueble, hogar">
                    <small class="form-text text-muted">Separadas por comas. *Nota: Hoy en día Google les resta importancia, pero motores internos sí las usan.</small>
                </div>
            </div>

            <div class="col-md-6">
                <div>
                    <label for="seo_canonical_url" class="form-label">URL Canónica (Opcional)</label>
                    <input type="url" class="form-control form-control-sm" id="seo_canonical_url" name="seo[canonical_url]" value="{{ old('seo.canonical_url', $seo->canonical_url ?? '') }}" placeholder="https://...">
                    <small class="form-text text-muted">Evita penalizaciones por contenido duplicado.</small>
                </div>
            </div>

            <!-- Cabecera Redes Sociales -->
            <div class="col-12 mt-4">
                <hr class="mb-3">
                <h6 class="text-primary mb-3"><i class="ri-share-forward-line me-1"></i> Optimización para Redes Sociales (OpenGraph)</h6>
            </div>

            <div class="col-md-6">
                <div>
                    <label for="seo_og_title" class="form-label">Título Social (OG Title)</label>
                    <input type="text" class="form-control form-control-sm" id="seo_og_title" name="seo[og_title]" value="{{ old('seo.og_title', $seo->og_title ?? '') }}" placeholder="Aparecerá en Facebook/WhatsApp">
                </div>
            </div>
            
            <div class="col-md-6">
                <div>
                    <label for="seo_og_image" class="form-label">Imagen Destacada (OG Image)</label>
                    <input type="file" class="form-control form-control-sm" id="seo_og_image" name="seo_og_image" accept="image/*">
                    <small class="form-text text-muted">Aparecerá como miniatura. Por defecto se usará la imagen principal del modelo o el logo del sistema.</small>
                    @if($seo && $seo->og_image_id)
                        @php 
                            $ogImage = \App\Models\File::find($seo->og_image_id); 
                        @endphp
                        @if($ogImage)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $ogImage->path) }}" alt="OG Preview" class="img-thumbnail" style="max-height: 80px;">
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <div class="col-md-12">
                <div>
                    <label for="seo_og_description" class="form-label">Descripción Social (OG Description)</label>
                    <textarea class="form-control form-control-sm" id="seo_og_description" name="seo[og_description]" rows="2">{{ old('seo.og_description', $seo->og_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
        
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sourceName = document.getElementById('name');
        const sourceDesc = document.getElementById('description'); // if available

        const metaTitle = document.getElementById('seo_meta_title');
        const metaDesc = document.getElementById('seo_meta_description');
        const ogTitle = document.getElementById('seo_og_title');
        const ogDesc = document.getElementById('seo_og_description');
        const customSlug = document.getElementById('custom_slug');

        // Function allowing sync only if user hasn't typed in the SEO fields manually
        function getAutoSyncValues(elem) {
            return !elem.dataset.manuallyEdited;
        }
        
        function makeSlug(str) {
            return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        }

        [metaTitle, metaDesc, ogTitle, ogDesc, customSlug].forEach(el => {
            if (el) {
                // If it already has value from DB, mark as manually edited so we don't overwrite
                if (el.value.trim() !== '') el.dataset.manuallyEdited = true;
                
                el.addEventListener('input', function() {
                    this.dataset.manuallyEdited = true;
                });
            }
        });

        if (sourceName) {
            sourceName.addEventListener('input', function() {
                const val = this.value;
                if (metaTitle && getAutoSyncValues(metaTitle)) metaTitle.value = val;
                if (ogTitle && getAutoSyncValues(ogTitle)) ogTitle.value = val;
                if (customSlug && getAutoSyncValues(customSlug)) customSlug.value = makeSlug(val);
            });
        }

        if (sourceDesc) {
            sourceDesc.addEventListener('input', function() {
                const val = this.value;
                if (metaDesc && getAutoSyncValues(metaDesc)) metaDesc.value = val.substring(0, 160);
                if (ogDesc && getAutoSyncValues(ogDesc)) ogDesc.value = val.substring(0, 200);
            });
        }
    });
</script>
@endpush
