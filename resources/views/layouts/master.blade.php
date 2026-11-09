<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="vertical" data-topbar="light" data-sidebar="light"
    data-sidebar-size="lg" data-preloader="disable" data-theme="default"
    data-theme-colors="blue" data-bs-theme="light" data-layout-width="fluid" data-layout-position="fixed" data-layout-style="default" data-sidebar-visibility="show">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | Ureña Muebles - Sistema de Gestión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta content="Sistema de Gestión Ureña Muebles" name="description" />
    <meta content="Ureña Muebles" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/images/urena-logo-sm.jpg') }}">
    @include('layouts.head-css')
</head>

<body>
    <!-- Begin page -->
    <div id="layout-wrapper">
        @include('layouts.topbar')
        @include('layouts.sidebar')
        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ri-error-warning-line me-2"></i><strong>Ups! Hemos detectado los siguientes errores:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            @include('layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    {{-- Modal de confirmación estándar (disponible en todas las páginas) --}}
    <x-confirm-modal />



{{--
    @include('layouts.customizer')
--}}
    <!-- JAVASCRIPT -->
    @include('layouts.vendor-scripts')
    
    <script>
        // Global Form Loader
        document.addEventListener('submit', function(e) {
            const form = e.target;
            // Omitir si el form tiene data-no-loader="true"
            if (form.getAttribute('data-no-loader') === 'true') return;
            
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            
            submitButtons.forEach(btn => {
                // Prevenir doble click
                if (btn.hasAttribute('data-submitting')) {
                    e.preventDefault();
                    return;
                }
                
                btn.setAttribute('data-submitting', 'true');
                btn.disabled = true;
                
                // Guardar contenido original
                const oriText = btn.innerHTML;
                btn.setAttribute('data-original-text', oriText);
                
                // Cambiar a loader
                if (btn.tagName === 'BUTTON') {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Procesando...';
                }
            });
        });
    </script>
    
    <!-- Botón Flotante para abrir la IA (Glassmorphism con micro-animaciones) -->
    <button class="btn btn-primary btn-icon ai-float-btn" onclick="toggleAiChat()">
        <i class="ri-robot-line fs-22"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="margin-left: -5px; margin-top: 5px;">AI</span>
    </button>

    <!-- Panel Deslizable (Offcanvas) para el Historial de Chats -->
    <div class="offcanvas offcanvas-start bg-light border-end" tabindex="-1" id="aiHistorySidebar" style="width: 280px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fs-14"><i class="ri-history-line me-1"></i> Historial de Chats</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="p-3">
                <button class="btn btn-outline-primary w-100 btn-sm" id="ai-new-chat-btn">
                    <i class="ri-add-line me-1"></i> Nuevo Chat
                </button>
            </div>
            <!-- Lista de chats anteriores -->
            <div class="list-group list-group-flush" id="ai-past-conversations-list" style="overflow-y: auto; max-height: calc(100vh - 140px);">
                <!-- Cargado dinámicamente vía JS -->
            </div>
        </div>
    </div>

    <!-- Contenedor del Chat Widget -->
    <div class="card ai-chat-widget d-none" id="ai-chat-widget">
        <!-- Header con engranaje y menú deslizable -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center bg-gradient-glow py-2">
            <div class="d-flex align-items-center">
                <button class="btn btn-link text-white p-0 me-2" type="button" id="ai-history-toggle">
                    <i class="ri-menu-2-line fs-16"></i>
                </button>
                <div class="avatar-xxs bg-light-subtle rounded-circle p-1 me-2 d-flex align-items-center justify-content-center">
                    <i class="ri-robot-fill text-primary"></i>
                </div>
                <div>
                    <h6 class="text-white mb-0 fs-13">Ureña Local AI</h6>
                    <span class="text-white-50 fs-10">WebGPU Local Engine</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <!-- Engrane de Ajustes -->
                <button class="btn btn-link text-white p-0 me-3" id="ai-settings-toggle">
                    <i class="ri-settings-4-line fs-16"></i>
                </button>
                <button type="button" class="btn-close btn-close-white" onclick="toggleAiChat()"></button>
            </div>
        </div>

        <!-- Panel de Ajustes / Diagnóstico (Oculto por defecto) -->
        <div id="ai-settings-panel" class="p-3 border-bottom d-none ai-settings-card m-2">
            <h6 class="diag-title mb-2"><i class="ri-computer-line me-1"></i> Diagnóstico de WebGPU</h6>
            <div class="mb-1"><strong>GPU:</strong> <span id="diag-gpu-info" class="text-muted">Cargando...</span></div>
            <div class="mb-2"><strong>RAM:</strong> <span id="diag-ram-info" class="text-muted">Cargando...</span></div>
            <div class="mb-2"><strong>Recomendado:</strong> <span id="diag-rec-model" class="badge bg-success-subtle text-success fs-10">...</span></div>
            
            <div class="form-group mt-2">
                <label for="ai-model-select" class="form-label mb-1"><strong>Cambiar Modelo Local:</strong></label>
                <select id="ai-model-select" class="form-select form-select-sm">
                    <option value="gemma-2-2b-it-q4f16_1-MLC">⚡ Gemma 2 2B (PC Equilibrado - 1.4 GB)</option>
                    <option value="Qwen2.5-1.5B-Instruct-q4f32_1-MLC">📱 Qwen 2.5 1.5B (Celular Equilibrado - 1.2 GB)</option>
                    <option value="Qwen2.5-0.5B-Instruct-q4f32_1-MLC">⚡ Qwen 2.5 0.5B (Celular Ultra-Ligero - 500 MB)</option>
                </select>
            </div>

            <div class="form-group mt-3 pt-2 border-top">
                <div class="form-check form-switch form-switch-sm d-flex align-items-center justify-content-between px-0">
                    <label class="form-check-label fs-11 text-dark mb-0" for="ai-show-thoughts-btn">
                        <i class="ri-mind-map text-primary me-1"></i> Mostrar Pensamiento de la IA
                    </label>
                    <input class="form-check-input ms-2" type="checkbox" role="switch" id="ai-show-thoughts-btn">
                </div>
            </div>
        </div>

        <!-- Descarga del Modelo / Progreso -->
        <div class="p-3 border-bottom bg-light d-none">
            <div id="ai-loading-status" class="fs-11 text-muted mb-2">Inicializando motor...</div>
            <div class="progress progress-sm">
                <div id="ai-progress-bar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
        </div>

        <!-- Caja de Conversación -->
        <div class="card-body chat-conversation-box" id="ai-chat-messages" style="height: 330px; overflow-y: auto; background: rgba(243, 246, 249, 0.5);">
            <div class="chat-message-system mb-3">
                <div class="alert alert-info py-2 px-3 fs-12 mb-0">
                    <i class="ri-info-card-line me-1"></i> Esta IA se ejecuta en tu tarjeta gráfica. Tus datos de inventario y costos permanecen protegidos localmente.
                </div>
            </div>
        </div>

        <!-- Input Box -->
        <div class="card-footer p-2 bg-transparent border-top">
            <div class="input-group">
                <input type="text" id="ai-user-input" class="form-control border-0 bg-light fs-13" placeholder="Pregunta sobre stock, comisiones..." aria-label="Escribe un mensaje...">
                <button class="btn btn-primary" type="button" id="ai-send-btn">
                    <i class="ri-send-plane-2-fill"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts del Agente -->
    <script>
        // Función global para alternar la visibilidad del chat widget
        function toggleAiChat() {
            const widget = document.getElementById("ai-chat-widget");
            if (widget) {
                widget.classList.toggle("d-none");
                const isOpen = !widget.classList.contains("d-none");
                
                // Guardar estado en sessionStorage
                sessionStorage.setItem("urena_ai_widget_open", isOpen ? "true" : "false");
            }
        }

        // Hook para activar la barra lateral de historial mediante Bootstrap Offcanvas
        document.addEventListener("DOMContentLoaded", function() {
            const historyToggle = document.getElementById("ai-history-toggle");
            const historySidebar = document.getElementById("aiHistorySidebar");
            
            if (historyToggle && historySidebar) {
                const offcanvas = new bootstrap.Offcanvas(historySidebar);
                historyToggle.addEventListener("click", function() {
                    // Cargar conversaciones pasadas
                    loadPastConversationsList();
                    offcanvas.toggle();
                });
            }
            
            // Carga dinámica de la lista de conversaciones pasadas
            async function loadPastConversationsList() {
                const listContainer = document.getElementById("ai-past-conversations-list");
                if (!listContainer) return;
                
                try {
                    const response = await fetch('/api/ai/conversations');
                    const conversations = await response.json();
                    
                    listContainer.innerHTML = '';
                    if (conversations.length === 0) {
                        listContainer.innerHTML = '<div class="text-muted text-center py-4 fs-12">No hay chats anteriores.</div>';
                        return;
                    }
                    
                    conversations.forEach(convo => {
                        const btn = document.createElement("button");
                        btn.className = "list-group-item list-group-item-action border-0 d-flex justify-content-between align-items-center py-2 px-3 fs-12";
                        btn.innerHTML = `
                            <span class="text-truncate" style="max-width: 180px;"><i class="ri-chat-3-line me-2 text-primary"></i>${convo.title}</span>
                            <span class="text-muted fs-10">${new Date(convo.updated_at).toLocaleDateString()}</span>
                        `;
                        btn.addEventListener("click", async () => {
                            // Cargar esta conversación
                            sessionStorage.setItem("urena_ai_current_convo_id", convo.id.toString());
                            window.location.reload(); // Recargar para restaurar estado limpio
                        });
                        listContainer.appendChild(btn);
                    });
                } catch (e) {
                    console.error("Error cargando conversaciones:", e);
                }
            }
        });
    </script>
    <script type="module" src="{{ URL::asset('build/js/ai-assistant.init.js') }}"></script>
    
    @yield('scripts')
    @stack('scripts')
</body>

</html>
