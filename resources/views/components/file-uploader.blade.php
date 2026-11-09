<div>
    <!-- Very little is needed to make a happy life. - Marcus Aurelius -->
    
    @props([
        'model',
        'type',
        'accept' => '*/*',
        'multiple' => false,
        'maxSize' => 5, // MB
        'layout' => 'dropzone', // 'avatar' | 'dropzone'
        'title' => 'Subir Archivo',
        'description' => 'Arrastra y suelta tus archivos aquí'
    ])

    @if ($layout === 'avatar')
        {{-- Diseño de Avatar Circular --}}
        <div class="profile-user position-relative d-inline-block mx-auto mb-4">
            @php
                $avatarFile = $model->files()->where('type', 'avatar')->first();
                $avatarUrl = $avatarFile ? asset('storage/' . $avatarFile->path) : URL::asset('build/images/users/avatar-1.jpg');
            @endphp
            <img src="{{ $avatarUrl }}" class="rounded-circle avatar-xl img-thumbnail user-profile-image material-shadow" alt="user-profile-image" id="avatar-preview-{{ $type }}">
            
            <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                <input id="avatar-input-{{ $type }}" type="file" class="profile-img-file-input" accept="{{ $accept }}">
                <label for="avatar-input-{{ $type }}" class="profile-photo-edit avatar-xs">
                    <span class="avatar-title rounded-circle bg-light text-body material-shadow">
                        <i class="ri-camera-fill"></i>
                    </span>
                </label>
            </div>
            <!-- Spinner -->
            <div id="avatar-spinner-{{ $type }}" class="position-absolute top-50 start-50 translate-middle d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fileInput = document.getElementById('avatar-input-{{ $type }}');
                const previewImg = document.getElementById('avatar-preview-{{ $type }}');
                const spinner = document.getElementById('avatar-spinner-{{ $type }}');

                if (fileInput) {
                    fileInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (!file) return;

                        // Mostrar preview local
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                        }
                        reader.readAsDataURL(file);

                        // Subir archivo al server por AJAX
                        const formData = new FormData();
                        formData.append('file', file);
                        formData.append('model_type', '{!! addslashes(get_class($model)) !!}');
                        formData.append('model_id', '{{ $model->id }}');
                        formData.append('type', '{{ $type }}');

                        spinner.classList.remove('d-none');
                        previewImg.classList.add('opacity-50');

                        fetch('{{ route("files.upload") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => { throw new Error(err.error || 'Server error') });
                            }
                            return response.json();
                        })
                        .then(data => {
                            spinner.classList.add('d-none');
                            previewImg.classList.remove('opacity-50');
                            if (data.url) {
                                previewImg.src = data.url; // Confirm the uploaded image URL
                                
                                // Opcionalmente actualizar los avatars del header
                                document.querySelectorAll('.header-profile-user').forEach(img => {
                                    img.src = data.url;
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error al subir avatar:', error);
                            spinner.classList.add('d-none');
                            previewImg.classList.remove('opacity-50');
                            alert('Ocurrió un error al subir la imagen.');
                        });
                    });
                }
            });
        </script>
        @endpush

    @else
        {{-- Diseño Compacto de Lista --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0"><i class="ri-attachment-2 me-1"></i>{{ $title }}</h6>
                <label for="file-input-{{ $type }}" class="btn btn-sm btn-primary mb-0" style="cursor:pointer;">
                    <i class="ri-upload-2-line me-1"></i> Subir
                    <input id="file-input-{{ $type }}" type="file" class="d-none" {{ $multiple ? 'multiple' : '' }} accept="{{ $accept }}">
                </label>
            </div>
            <div class="card-body p-0">
                <p class="text-muted px-3 pt-2 mb-1 fs-12">{{ $description }}</p>

                {{-- Lista de archivos existentes + nuevos --}}
                <ul class="list-group list-group-flush" id="file-list-{{ $type }}">
                    @php
                        $existingFiles = $model->files()->where('type', $type)->get();
                    @endphp
                    @foreach($existingFiles as $ef)
                        <li class="list-group-item d-flex align-items-center py-2 px-3 gap-2" id="file-item-{{ $ef->id }}">
                            {{-- Icono según tipo --}}
                            @if(str_contains($ef->mime_type, 'image'))
                                <i class="ri-image-2-line fs-18 text-primary"></i>
                            @elseif(str_contains($ef->mime_type, 'pdf'))
                                <i class="ri-file-pdf-line fs-18 text-danger"></i>
                            @else
                                <i class="ri-file-3-line fs-18 text-secondary"></i>
                            @endif
                            {{-- Nombre y tamaño --}}
                            <div class="flex-grow-1 overflow-hidden">
                                <a href="{{ asset('storage/' . $ef->path) }}" target="_blank" class="text-reset text-truncate d-block fs-13" title="{{ $ef->name }}">
                                    {{ $ef->name }}
                                </a>
                                <span class="text-muted fs-11">{{ number_format($ef->size / 1024, 1) }} KB</span>
                            </div>
                            {{-- Botón eliminar --}}
                            <button type="button" class="btn btn-sm btn-ghost-danger px-1 py-0 btn-delete-file"
                                data-file-id="{{ $ef->id }}"
                                data-csrf="{{ csrf_token() }}"
                                data-url="{{ url('/files/' . $ef->id) }}"
                                title="Eliminar">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </li>
                    @endforeach
                </ul>

                {{-- Spinner de subida --}}
                <div id="upload-spinner-{{ $type }}" class="text-center py-2 d-none">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Subiendo...</span>
                    </div>
                    <span class="text-muted ms-2 fs-12">Subiendo archivos...</span>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var fileInput  = document.getElementById('file-input-{{ $type }}');
            var fileList   = document.getElementById('file-list-{{ $type }}');
            var spinner    = document.getElementById('upload-spinner-{{ $type }}');

            /** Subir un archivo via fetch */
            function uploadFile(file) {
                var formData = new FormData();
                formData.append('file', file);
                formData.append('model_type', '{{ addslashes(get_class($model)) }}');
                formData.append('model_id',   '{{ $model->id }}');
                formData.append('type',       '{{ $type }}');

                spinner.classList.remove('d-none');

                fetch('{{ route("files.upload") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.file) {
                        addFileRow(data.file, data.url);
                    }
                })
                .catch(err => console.error('Error subiendo archivo:', err))
                .finally(() => spinner.classList.add('d-none'));
            }

            /** Crear fila en la lista */
            function getIcon(mime) {
                if (mime.includes('image')) return '<i class="ri-image-2-line fs-18 text-primary"></i>';
                if (mime.includes('pdf'))  return '<i class="ri-file-pdf-line fs-18 text-danger"></i>';
                return '<i class="ri-file-3-line fs-18 text-secondary"></i>';
            }

            function addFileRow(file, url) {
                var kb = (file.size / 1024).toFixed(1);
                var li = document.createElement('li');
                li.className = 'list-group-item d-flex align-items-center py-2 px-3 gap-2';
                li.id = 'file-item-' + file.id;
                li.innerHTML = `
                    ${getIcon(file.mime_type)}
                    <div class="flex-grow-1 overflow-hidden">
                        <a href="${url}" target="_blank" class="text-reset text-truncate d-block fs-13" title="${file.name}">${file.name}</a>
                        <span class="text-muted fs-11">${kb} KB</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-ghost-danger px-1 py-0 btn-delete-file"
                        data-file-id="${file.id}" data-csrf="{{ csrf_token() }}"
                        data-url="{{ url('/files') }}/${file.id}" title="Eliminar">
                        <i class="ri-delete-bin-line"></i>
                    </button>`;
                fileList.appendChild(li);
                bindDeleteButton(li.querySelector('.btn-delete-file'));
            }

            /** Vincular botón eliminar */
            function bindDeleteButton(btn) {
                btn.addEventListener('click', function() {
                    var fileId = this.dataset.fileId;
                    var url    = this.dataset.url;
                    var csrf   = this.dataset.csrf;
                    var item   = document.getElementById('file-item-' + fileId);

                    if (!confirm('¿Eliminar este archivo?')) return;

                    fetch(url, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(() => { if (item) item.remove(); })
                    .catch(err => console.error('Error eliminando:', err));
                });
            }

            // Vincular botones de eliminar de archivos pre-cargados
            document.querySelectorAll('#file-list-{{ $type }} .btn-delete-file').forEach(bindDeleteButton);

            // Escuchar cambio en el input de archivos
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    Array.from(this.files).forEach(uploadFile);
                    this.value = ''; // Resetear para permitir volver a subir el mismo archivo
                });
            }
        });
        </script>
        @endpush
    @endif
</div>

