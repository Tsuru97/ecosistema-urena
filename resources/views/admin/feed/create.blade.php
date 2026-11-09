@extends('layouts.master')
@section('title') Nuevo Comunicado @endsection

@section('css')
    <link href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" rel="stylesheet">
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Nuevo Comunicado</h5>
                </div>
                <div class="card-body">
                    @if (count($errors) > 0)
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Se le pone un ID para que el text-editor pueda enviar el body al hidden input -->
                    <form action="{{ route('feed.store') }}" method="POST" id="postForm">
                        @csrf
                        <input type="hidden" name="post_id" value="{{ $post->id }}">

                        {{-- Indicador de sucursal --}}
                        @if(is_global_view())
                            <div class="alert alert-info d-flex align-items-center py-2 mb-3">
                                <i class="ri-global-line fs-18 me-2"></i>
                                <span>Este comunicado será <strong>global</strong> — visible para todas las sucursales.</span>
                            </div>
                        @elseif($currentStore ?? null)
                            <div class="alert alert-success d-flex align-items-center py-2 mb-3">
                                <i class="ri-store-2-line fs-18 me-2"></i>
                                <span>Este comunicado será para la sucursal: <strong>{{ $currentStore->name }}</strong></span>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control form-control-sm" value="{{ old('title') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="summary" class="form-label">Resumen / Descripción Corta</label>
                            <textarea name="summary" id="summary" class="form-control form-control-sm" rows="2" placeholder="Opcional. Se mostrará en la vista previa.">{{ old('summary') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Contenido <span class="text-danger">*</span></label>
                            <input type="hidden" name="body" id="body" value="{{ old('body') }}">
                            <input type="hidden" name="gjs_data" id="gjs_data" value="{{ old('gjs_data') }}">
                            <div style="border: 1px solid #ccc; height: 500px; width: 100%;">
                                <div id="gjs">{!! old('body') !!}</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Tipo</label>
                                    <select name="type" id="type" class="form-select form-select-sm">
                                        <option value="comunicado" {{ old('type') == 'comunicado' ? 'selected' : '' }}>📢 Comunicado</option>
                                        <option value="anuncio" {{ old('type') == 'anuncio' ? 'selected' : '' }}>📌 Anuncio</option>
                                        <option value="tarea" {{ old('type') == 'tarea' ? 'selected' : '' }}>✅ Tarea</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Prioridad</label>
                                    <select name="priority" id="priority" class="form-select form-select-sm">
                                        <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                                        <option value="importante" {{ old('priority') == 'importante' ? 'selected' : '' }}>🟡 Importante</option>
                                        <option value="urgente" {{ old('priority') == 'urgente' ? 'selected' : '' }}>🔴 Urgente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="published_at" class="form-label">Fecha de Publicación</label>
                                    <input type="datetime-local" name="published_at" id="published_at" class="form-control form-control-sm" value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 d-flex align-items-end h-100 pb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="pinned" id="pinned" class="form-check-input" {{ old('pinned') ? 'checked' : '' }}>
                                        <label for="pinned" class="form-check-label">Fijar arriba</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <!-- TODO: Añadir un form para eliminar el borrador si el usuario cancela -->
                            <a href="{{ route('feed.index') }}" class="btn btn-ghost-secondary me-2 btn-sm">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn-publish">
                                <i class="ri-send-plane-line me-1"></i> Publicar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Columna lateral para Archivos -->
        <div class="col-lg-4">
            <x-file-uploader 
                :model="$post" 
                type="attachment" 
                :multiple="true" 
                accept="image/*,application/pdf" 
                title="Archivos Adjuntos" 
                description="Sube imágenes o documentos relacionados. Se publicarán con tu comunicado." 
                layout="dropzone"
            />
        </div>
    </div>
@endsection

@section('script')
    <script src="https://unpkg.com/grapesjs"></script>
    <script src="https://unpkg.com/grapesjs-preset-webpage@1.0.2"></script>
    <script src="https://unpkg.com/grapesjs-blocks-basic@1.0.1"></script>
    <script>
        // Prevenir que Dropzone se auto-inicialice antes de que nuestro código lo haga
        if (typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Dropzone !== 'undefined') {
                Dropzone.autoDiscover = false;
            }

            var editor = grapesjs.init({
                container: '#gjs',
                height: '100%',
                width: '100%',
                protectedCss: '',
                storageManager: false,
                plugins: [
                    typeof grapesjsPresetWebpage !== 'undefined' ? grapesjsPresetWebpage : null,
                    typeof grapesjsBlocksBasic !== 'undefined' ? grapesjsBlocksBasic : null,
                ].filter(Boolean),
                pluginsOpts: {},
                assetManager: {
                    assets: [],
                    upload: false,
                },
                canvas: {
                    styles: [
                        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
                    ],
                    scripts: [
                        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
                    ]
                }
            });

            if (typeof window.addVelzonBlocks === 'function') {
                window.addVelzonBlocks(editor.BlockManager);
            }

            if (typeof window.initGeminiChat === 'function') {
                window.initGeminiChat(editor);
            }

            editor.Commands.add('open-code', {
                run: function(editor, sender) {
                    sender && sender.set('active', 0);
                    var html = editor.getHtml();
                    var css = editor.getCss();

                    var content = `
                        <div style="display:flex; flex-direction:column; height: 100%;">
                            <div class="mb-3">
                                <label class="form-label text-white">Modificar HTML</label>
                                <textarea id="gjs-html-edit" class="form-control bg-dark text-white border-secondary form-control-sm" style="height: 300px; font-family: monospace;">${html}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-white">Modificar CSS</label>
                                <textarea id="gjs-css-edit" class="form-control bg-dark text-white border-secondary form-control-sm" style="height: 150px; font-family: monospace;">${css}</textarea>
                            </div>
                            <div class="text-end mt-2">
                                <button type="button" id="gjs-save-code" class="btn btn-primary btn-sm">Aplicar Cambios</button>
                            </div>
                        </div>
                    `;

                    editor.Modal.setTitle('Editar Código Fuente')
                      .setContent(content)
                      .open();

                    document.getElementById('gjs-save-code').onclick = function() {
                        var h = document.getElementById('gjs-html-edit').value;
                        var c = document.getElementById('gjs-css-edit').value;
                        editor.CssComposer.clear();
                        editor.setComponents(h);
                        editor.setStyle(c);
                        editor.Modal.close();
                    };
                }
            });

            editor.on('load', function() {
                var pn = editor.Panels;
                if (!pn.getButton('options', 'open-code')) {
                    pn.addButton('options', [{
                        id: 'open-code',
                        className: 'fa fa-code',
                        command: 'open-code',
                        attributes: { title: 'Editar Código HTML/CSS', 'data-tooltip-pos': 'bottom' }
                    }]);
                }
            });

            var gjsData = @json(old('gjs_data', $post->gjs_data ?? null));
            var rawContent = @json(old('body', $post->body ?? ''));

            editor.on('load', function() {
                if (gjsData) {
                    try {
                        var parsed = typeof gjsData === 'string' ? JSON.parse(gjsData) : gjsData;
                        editor.loadProjectData(parsed);
                    } catch(e) {
                        console.error('Error parseando gjs_data', e);
                    }
                } else if (rawContent && rawContent.trim()) {
                    var styleMatch = rawContent.match(/<style[^>]*>([\s\S]*?)<\/style>/i);
                    var htmlOnly = rawContent.replace(/<style[^>]*>[\s\S]*?<\/style>/i, '');
                    if (styleMatch && styleMatch[1]) {
                        editor.setStyle(styleMatch[1]);
                    }
                    if (htmlOnly && htmlOnly.trim()) {
                        editor.setComponents(htmlOnly);
                    }
                }
            });

            // Sincronizar el contenido antes de enviar
            var form = document.getElementById('postForm');
            form.onsubmit = function() {
                var html = editor.getHtml();
                var css = editor.getCss();
                document.getElementById('body').value = `<style>${css}</style>\n${html}`;
                document.getElementById('gjs_data').value = JSON.stringify(editor.getProjectData());
                return true;
            };
        });
    </script>
    @include('admin.feed.grapesjs-blocks')
    @include('admin.feed.grapesjs-ai')
@endsection

