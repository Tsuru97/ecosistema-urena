// resources/js/pages/ai-assistant.init.js

import { AgentState } from '../ai/AgentState.js';
import { CreateMLCEngine } from "https://esm.run/@mlc-ai/web-llm@0.2.84";

document.addEventListener("DOMContentLoaded", async function () {
    // Referencias de elementos DOM del widget flotante
    const chatWidget = document.getElementById("ai-chat-widget");
    const chatMessages = document.getElementById("ai-chat-messages");
    const userInput = document.getElementById("ai-user-input");
    const sendButton = document.getElementById("ai-send-btn");
    const loadingStatus = document.getElementById("ai-loading-status");
    const progressBar = document.getElementById("ai-progress-bar");
    const settingsButton = document.getElementById("ai-settings-toggle");
    const settingsPanel = document.getElementById("ai-settings-panel");
    const newChatButton = document.getElementById("ai-new-chat-btn");
    const openHistoryButton = document.getElementById("ai-history-toggle");

    // Elementos de Diagnóstico en Panel de Ajustes
    const gpuInfoEl = document.getElementById("diag-gpu-info");
    const ramInfoEl = document.getElementById("diag-ram-info");
    const recModelEl = document.getElementById("diag-rec-model");
    const modelSelector = document.getElementById("ai-model-select");

    // Variables de Estado del Agente
    let aiWorker = null;
    let mainThreadChat = null;
    let mainThreadFallbackActive = false;
    let currentConversationId = null;
    let isModelLoaded = false;
    let isWorkerInitializing = false;
    let conversationHistory = []; // Hilo local de inferencia

    // Selector de Pensamientos de IA
    const thoughtsToggle = document.getElementById("ai-show-thoughts-btn");
    let showAiThoughts = localStorage.getItem("urena_ai_show_thoughts") === "true";

    if (thoughtsToggle) {
        thoughtsToggle.checked = showAiThoughts;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "";

    // ────────────────────────────────────────────────────────
    // CONFIGURACIÓN DE MOTOR (WebLLM vs LiteRT vs Cloud API)
    // ────────────────────────────────────────────────────────
    const USE_CLOUD_API = true; // Seteado en true para usar Google Gemini API en el servidor (Recomendado para Celulares y Computadoras)
    const USE_LITERT = false; // Cambia a true si deseas probar el motor Google LiteRT (MediaPipe Tasks GenAI) en lugar de WebLLM
    const LITERT_MODEL_URL = "https://storage.googleapis.com/mediapipe-models/llm_inference/gemma-2b-it-gpu.bin";
    let liteRtEngine = null;

    // ────────────────────────────────────────────────────────
    // 1. Diagnóstico de Hardware y WebGPU
    // ────────────────────────────────────────────────────────
    async function diagnoseHardware() {
        if (USE_CLOUD_API) {
            if (recModelEl) recModelEl.innerText = "Google Gemini (Nube)";
            return {
                hasWebGpu: true,
                recommendedModel: "gemini"
            };
        }
        let hasWebGpu = false;
        let gpuDescription = "No disponible o bloqueado";
        let ramGb = navigator.deviceMemory || "Desconocida";

        if (navigator.gpu) {
            hasWebGpu = true;
            try {
                const adapter = await navigator.gpu.requestAdapter();
                if (adapter) {
                    // Obtener nombre técnico de la tarjeta gráfica si está disponible
                    const adapterInfo = await adapter.requestAdapterInfo?.() || {};
                    gpuDescription = adapterInfo.description || adapterInfo.vendor || "GPU Genérica WebGPU";
                } else {
                    gpuDescription = "WebGPU compatible (Sin adaptador)";
                }
            } catch (e) {
                gpuDescription = "WebGPU compatible (Error de consulta)";
            }
        }

        // Renderizar diagnóstico en panel
        if (gpuInfoEl) gpuInfoEl.innerText = gpuDescription;
        if (ramInfoEl) ramInfoEl.innerText = hasWebGpu ? `${ramGb} GB RAM` : `${ramGb} GB RAM (Sin WebGPU)`;

        // Recomendación Inteligente
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        let recommendedModel = "gemma-2-2b-it-q4f16_1-MLC"; // 4-bit (Equilibrado) por defecto para computadoras
        let recommendedLabel = "Gemma 2B (PC Equilibrado)";

        if (!hasWebGpu) {
            recommendedModel = "none";
            recommendedLabel = "WebGPU no soportado en este navegador.";
        } else if (isMobile) {
            // En celulares/tablets, forzar q4f32_1 de 32-bit para evitar fallos de shader-f16
            recommendedModel = "Qwen2.5-0.5B-Instruct-q4f32_1-MLC";
            recommendedLabel = "Qwen 2.5 0.5B (Celulares - Ultra-Ligero)";
        } else if (ramGb !== "Desconocida" && ramGb < 8) {
            recommendedModel = "Qwen2.5-1.5B-Instruct-q4f32_1-MLC";
            recommendedLabel = "Qwen 2.5 1.5B (PC / Celulares - Equilibrado)";
        }

        if (recModelEl) recModelEl.innerText = recommendedLabel;

        return {
            hasWebGpu,
            recommendedModel
        };
    }

    // ────────────────────────────────────────────────────────
    // 2.2 Inicialización de Google LiteRT (MediaPipe GenAI) Fallback
    // ────────────────────────────────────────────────────────
    async function initLiteRT() {
        if (isWorkerInitializing || isModelLoaded) return;
        isWorkerInitializing = true;

        console.log("[Ureña-AI LiteRT] Inicializando Google LiteRT (MediaPipe Tasks GenAI)...");
        if (loadingStatus) {
            loadingStatus.innerText = "Preparando motor LiteRT local...";
            loadingStatus.parentElement.classList.remove("d-none");
        }
        if (progressBar) progressBar.style.width = "20%";

        try {
            const moduleUrl = "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-genai@0.10.14";
            console.log("[Ureña-AI LiteRT] Cargando librerías desde CDN:", moduleUrl);

            const genai = await import(moduleUrl);
            if (progressBar) progressBar.style.width = "50%";
            if (loadingStatus) loadingStatus.innerText = "Descargando modelo LiteRT (.bin)...";

            const wasmFileset = await genai.FilesetResolver.forGenAiTasks(
                "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-genai@0.10.14/wasm"
            );
            if (progressBar) progressBar.style.width = "75%";

            liteRtEngine = await genai.LlmInference.createFromOptions(wasmFileset, {
                baseOptions: {
                    modelAssetPath: LITERT_MODEL_URL
                },
                maxTokens: 1024,
                temperature: 0.1,
            });

            console.log("[Ureña-AI LiteRT] ¡Motor LiteRT inicializado con éxito!");
            isModelLoaded = true;
            isWorkerInitializing = false;
            if (progressBar) progressBar.style.width = "100%";
            if (loadingStatus) {
                loadingStatus.parentElement.classList.add("d-none");
            }
            appendSystemBubble("¡Google LiteRT (Gemma 2B) está cargado localmente en tu GPU y listo!");
        } catch (error) {
            console.error("[Ureña-AI LiteRT Error]:", error);
            isWorkerInitializing = false;
            hideLoadingDots();
            appendSystemBubble(`Error crítico en LiteRT: ${error.message}. Verifica que tu navegador soporte WebGPU.`);
        }
    }

    // ────────────────────────────────────────────────────────
    // 2. Inicialización del Web Worker de WebLLM
    // ────────────────────────────────────────────────────────
    function initWebWorker(modelId) {
        if (USE_LITERT) {
            initLiteRT();
            return;
        }
        if (isWorkerInitializing || isModelLoaded) return;
        isWorkerInitializing = true;

        console.log("[Ureña-AI] Inicializando...");
        console.log("[Ureña-AI] Modelo seleccionado:", modelId);

        if (loadingStatus) {
            loadingStatus.innerText = "Preparando motor de IA local...";
            loadingStatus.parentElement.classList.remove("d-none");
        }
        if (progressBar) {
            progressBar.style.width = "0%";
        }

        try {
            // Usamos la ruta simplificada estática de un solo punto
            const workerUrl = "/ai-assistant-worker.js";
            console.log("[Ureña-AI] Cargando Web Worker estático desde:", workerUrl);

            aiWorker = new Worker(workerUrl, { type: 'module' });

            // Capturar errores de carga del Worker (CSP, MIME, 404, etc.)
            aiWorker.onerror = function (error) {
                console.warn("[Ureña-AI] El Web Worker falló al cargar/ejecutar (onerror):", error);

                // Si el worker falló, activar fallback de Hilo Principal
                if (!isModelLoaded && !mainThreadFallbackActive) {
                    activateMainThreadFallback(modelId);
                }
            };

            // Enviar señal de arranque con el modelo preferido
            aiWorker.postMessage({
                type: "init",
                data: { modelId: modelId }
            });
            console.log("[Ureña-AI] Mensaje 'init' enviado al Web Worker.");

            // Escuchar estados del Worker
            aiWorker.onmessage = function (event) {
                const { type, data } = event.data;
                console.log("[Ureña-AI] Evento recibido del Worker:", type, data);

                if (type === "progress") {
                    const percentage = Math.round(data.progress * 100);
                    console.log(`[Ureña-AI Progress] Descarga: ${percentage}% | Detalle: ${data.text}`);
                    if (progressBar) progressBar.style.width = `${percentage}%`;
                    if (loadingStatus) {
                        loadingStatus.innerText = `Descargando modelo local: ${percentage}% (${data.text})`;
                    }
                }

                if (type === "ready") {
                    console.log("[Ureña-AI] ¡El modelo se ha cargado en el Worker con éxito!");
                    isModelLoaded = true;
                    isWorkerInitializing = false;
                    if (loadingStatus) {
                        loadingStatus.parentElement.classList.add("d-none");
                    }
                    appendSystemBubble("¡Gemma 2B está cargada 100% local en tu GPU y lista para procesar comandos!");
                }

                if (type === "error") {
                    console.warn("[Ureña-AI] Error reportado por el Worker:", data);
                    // Si el worker arroja error al inicializar el modelo, también intentamos fallback
                    if (!isModelLoaded && !mainThreadFallbackActive) {
                        activateMainThreadFallback(modelId);
                    } else {
                        isWorkerInitializing = false;
                        hideLoadingDots();
                        appendSystemBubble(`Error del motor local: ${data}`);
                    }
                }
            };
        } catch (e) {
            console.warn("[Ureña-AI] Error al instanciar Worker. Activando fallback...", e);
            activateMainThreadFallback(modelId);
        }
    }

    async function activateMainThreadFallback(modelId) {
        if (mainThreadFallbackActive) return;
        mainThreadFallbackActive = true;
        console.log("[Ureña-AI Fallback] ¡ACTIVANDO FALLBACK DIRECTO EN EL HILO PRINCIPAL!");

        if (aiWorker) {
            try {
                aiWorker.terminate();
            } catch (e) {}
            aiWorker = null;
        }

        if (loadingStatus) {
            loadingStatus.innerText = "Cargando motor local directamente en pestaña (hilo principal)...";
        }

        try {
            console.log("[Ureña-AI Fallback] Cargando e inicializando motor en el hilo principal...");

            // Instanciar y recargar el motor con CreateMLCEngine en el hilo principal
            mainThreadChat = await CreateMLCEngine(modelId, {
                initProgressCallback: (report) => {
                    const percentage = Math.round(report.progress * 100);
                    console.log(`[Ureña-AI Hilo Principal Progress] ${percentage}% | ${report.text}`);
                    if (progressBar) progressBar.style.width = `${percentage}%`;
                    if (loadingStatus) {
                        loadingStatus.innerText = `Descargando modelo local: ${percentage}% (${report.text})`;
                    }
                }
            });
            console.log("[Ureña-AI Fallback] ¡Modelo cargado en el hilo principal con éxito!");

            isModelLoaded = true;
            isWorkerInitializing = false;
            if (loadingStatus) {
                loadingStatus.parentElement.classList.add("d-none");
            }
            appendSystemBubble("¡Gemma 2B está cargada 100% local en tu GPU (Hilo Principal) y lista para responder!");
        } catch (error) {
            console.error("[Ureña-AI Fallback Error]:", error);
            isWorkerInitializing = false;
            hideLoadingDots();
            appendSystemBubble(`Error crítico en el motor local: ${error.message}. Por favor, verifica que tu navegador soporte WebGPU en este dispositivo.`);
        }
    }

    // ────────────────────────────────────────────────────────
    // 3. Persistencia Híbrida y Carga de Hilos desde Laravel
    // ────────────────────────────────────────────────────────
    async function loadActiveChatSession(recommendedModel) {
        const state = AgentState.getSessionState();

        if (USE_CLOUD_API) {
            // Si el widget estaba abierto, mantenerlo abierto
            if (state.isOpen) {
                chatWidget.classList.remove("d-none");
            }
            if (modelSelector) {
                modelSelector.innerHTML = '<option value="gemini-cloud">☁️ Google Gemini API (Servidor)</option>';
                modelSelector.disabled = true;
            }
            if (state.conversationId) {
                currentConversationId = state.conversationId;
                await loadMessagesFromServer(currentConversationId);
            } else {
                await startNewChat();
            }
            return;
        }

        if (USE_LITERT) {
            // Si el widget estaba abierto, mantenerlo abierto
            if (state.isOpen) {
                chatWidget.classList.remove("d-none");
                initLiteRT();
            }
            if (modelSelector) {
                modelSelector.innerHTML = '<option value="litert-gemma-2b">⚡ Google LiteRT (Gemma 2B - 1.3 GB)</option>';
                modelSelector.disabled = true;
            }
            if (state.conversationId) {
                currentConversationId = state.conversationId;
                await loadMessagesFromServer(currentConversationId);
            } else {
                await startNewChat();
            }
            return;
        }

        // Si no hay un modelo guardado explícitamente en localStorage, usar el recomendado por hardware
        let activeModel = localStorage.getItem("urena_ai_selected_model_id");

        // VALIDACIÓN DE CACHÉ: Comprobar si el modelo guardado existe en las opciones actuales del selector
        // Esto evita que navegadores que guardaron modelos obsoletos o incompatibles con móviles (f16) sigan crasheando
        let modelExists = false;
        if (modelSelector) {
            modelExists = Array.from(modelSelector.options).some(opt => opt.value === activeModel);
        }

        if (!activeModel || !modelExists) {
            activeModel = recommendedModel;
            if (activeModel && activeModel !== "none") {
                localStorage.setItem("urena_ai_selected_model_id", activeModel);
            }
        }

        // Si el widget estaba abierto, mantenerlo abierto
        if (state.isOpen) {
            chatWidget.classList.remove("d-none");
            // Arrancar el worker con el modelo correcto
            initWebWorker(activeModel);
        }

        if (modelSelector) {
            modelSelector.value = activeModel;
        }

        if (state.conversationId) {
            currentConversationId = state.conversationId;
            await loadMessagesFromServer(currentConversationId);
        } else {
            // Si no hay chat activo, crear uno nuevo en base de datos
            await startNewChat();
        }
    }

    async function loadMessagesFromServer(convoId) {
        showLoadingDots();
        try {
            const response = await fetch(`/api/ai/conversations/${convoId}/messages`);
            const messages = await response.json();
            hideLoadingDots();

            // Renderizar las burbujas guardadas
            chatMessages.innerHTML = ''; // Limpiar

            // Reinsertar alerta informativa inicial
            const infoBox = document.createElement("div");
            infoBox.className = "chat-message-system mb-3";
            if (USE_CLOUD_API) {
                infoBox.innerHTML = `
                    <div class="alert alert-info py-2 px-3 fs-12 mb-0">
                        <i class="ri-shield-check-line me-1"></i> Conexión con IA en la nube activa. Tus datos permanecen cifrados y protegidos de forma segura.
                    </div>
                `;
            } else {
                infoBox.innerHTML = `
                    <div class="alert alert-info py-2 px-3 fs-12 mb-0">
                        <i class="ri-info-card-line me-1"></i> Esta IA se ejecuta localmente. Tus datos de inventario y costos permanecen protegidos en tu dispositivo.
                    </div>
                `;
            }
            chatMessages.appendChild(infoBox);

            messages.forEach(msg => {
                if (msg.role === 'user') {
                    renderUserBubble(msg.content);
                } else if (msg.role === 'assistant') {
                    // Si el contenido es un JSON intermedio de herramienta o pensamiento
                    try {
                        const parsed = JSON.parse(msg.content);
                        if (parsed.thought && showAiThoughts) {
                            renderThoughtBubble(parsed.thought, parsed.toolCall);
                        }
                        if (parsed.toolCall || parsed.thought) {
                            return; // Ocultar globos de diálogo JSON crudos
                        }
                    } catch (e) {
                        // Es texto plano normal de la IA
                    }
                    renderSystemBubble(msg.content);
                }
            });

            // Convertir mensajes al formato de Gemma para mantener continuidad de diálogo
            conversationHistory = messages.map(msg => ({
                role: msg.role === 'system' ? 'system' : msg.role,
                content: msg.content
            }));

            scrollToBottom();
        } catch (e) {
            console.error("Error cargando mensajes:", e);
            hideLoadingDots();
        }
    }

    async function startNewChat() {
        try {
            const response = await fetch('/api/ai/conversations', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ title: 'Nueva consulta ERP' })
            });
            const convo = await response.json();
            currentConversationId = convo.id;

            // Guardar ID en la sesión
            AgentState.saveSessionState(true, currentConversationId, modelSelector?.value);

            chatMessages.innerHTML = '';
            // Insertar alerta informativa inicial
            const infoBox = document.createElement("div");
            infoBox.className = "chat-message-system mb-3";
            if (USE_CLOUD_API) {
                infoBox.innerHTML = `
                    <div class="alert alert-info py-2 px-3 fs-12 mb-0">
                        <i class="ri-shield-check-line me-1"></i> Conexión con IA en la nube activa. Tus datos permanecen cifrados y protegidos de forma segura.
                    </div>
                `;
            } else {
                infoBox.innerHTML = `
                    <div class="alert alert-info py-2 px-3 fs-12 mb-0">
                        <i class="ri-info-card-line me-1"></i> Esta IA se ejecuta localmente. Tus datos de inventario y costos permanecen protegidos en tu dispositivo.
                    </div>
                `;
            }
            chatMessages.appendChild(infoBox);

            const greeting = USE_CLOUD_API 
                ? "¡Hola! Soy tu asistente Ureña-AI basado en la nube. ¿En qué puedo ayudarte hoy con el catálogo, existencias o traspasos del ERP?"
                : "¡Hola! Soy tu asistente de IA Local. ¿En qué puedo ayudarte hoy con el catálogo, traspasos o existencias?";
            appendSystemBubble(greeting);
            conversationHistory = [];
        } catch (e) {
            console.error("Error creando chat nuevo:", e);
        }
    }

    function cleanAndParseJSON(rawText) {
        let cleanText = rawText.trim();

        // Si el LLM envolvió su JSON en bloques de código markdown, extraerlo
        if (cleanText.includes("```")) {
            const match = cleanText.match(/```(?:json)?\s*([\s\S]*?)\s*```/);
            if (match && match[1]) {
                cleanText = match[1].trim();
            }
        }

        return JSON.parse(cleanText);
    }

    // ────────────────────────────────────────────────────────
    // 4. Bucle Agéntico (Agent Loop) y Llamada a Herramientas
    // ────────────────────────────────────────────────────────
    async function processMessageFlow() {
        const query = userInput.value.trim();
        if (!query) return;

        if (USE_CLOUD_API) {
            renderUserBubble(query);
            userInput.value = "";
            showLoadingDots();
            scrollToBottom();

            try {
                const response = await fetch(`/api/ai/conversations/${currentConversationId}/chat`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ message: query })
                });

                const result = await response.json();
                hideLoadingDots();

                if (result.error) {
                    appendSystemBubble(`Error: ${result.message || result.error}`);
                    return;
                }

                // Si hay pensamiento guardado y está activa la opción, renderizarlo antes del mensaje
                if (result.thought && showAiThoughts) {
                    renderThoughtBubble(result.thought, result.redirect ? { name: 'navigate_to', arguments: { route_name: result.route_name } } : null);
                }

                if (result.redirect) {
                    appendSystemBubble(result.message || "Redirigiendo de pantalla...");
                    // Redirigir dinámicamente según la lógica de frontend
                    setTimeout(() => {
                        const routes = {
                            'transfers.index': '/transfers',
                            'transfers.create': '/transfers/create',
                            'inventory': '/inventory',
                            'audit': '/audit',
                            'settings': '/settings'
                        };
                        window.location.href = routes[result.route_name] || '/';
                    }, 1000);
                    return;
                }

                if (result.message) {
                    appendSystemBubble(result.message);
                }
            } catch (e) {
                hideLoadingDots();
                appendSystemBubble("Error al conectarse con el servidor de inteligencia artificial. Revisa tu conexión a internet.");
                console.error("Error en flujo de API en la nube:", e);
            }
            return;
        }

        // Comprobar soporte de WebGPU
        const diag = await diagnoseHardware();
        if (!diag.hasWebGpu) {
            Swal.fire({
                title: 'WebGPU no Soportado',
                text: 'Esta función requiere un navegador moderno con aceleración WebGPU activa (Chrome, Edge o Safari 18+).',
                icon: 'warning',
                confirmButtonColor: '#405189'
            });
            return;
        }

        // Si no se ha inicializado el Worker, forzar carga
        if (!isModelLoaded) {
            initWebWorker(modelSelector?.value || diag.recommendedModel);
            return;
        }

        // Renderizar mensaje de usuario y guardar en DB
        renderUserBubble(query);
        userInput.value = "";
        showLoadingDots();
        scrollToBottom();

        await saveMessageToServer('user', query);
        conversationHistory.push({ role: "user", content: query });

        let agentResolved = false;
        let loopLimit = 3; // Evita bucle agéntico infinito en caso de errores repetidos

        while (!agentResolved && loopLimit > 0) {
            loopLimit--;

            // A. Obtener de Laravel solo las herramientas relevantes (Dynamic Tool Routing)
            const routeResponse = await fetch('/api/ai/tools/route', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ query: query })
            });
            const { tools } = await routeResponse.json();

            // B. Construir el prompt de sistema inyectando estas herramientas dinámicas
            const systemPrompt = `
Eres Ureña-AI, un asistente inteligente local integrado en el ERP "Ecosistema Ureña".
Tu tarea es resolver dudas operativas y automatizar tareas. No inventes datos numéricos de stock ni precios. Si necesitas información para responder la pregunta, invoca obligatoriamente una herramienta.

HERRAMIENTAS DISPONIBLES EN ESTA PETICIÓN (Filtradas dinámicamente):
${JSON.stringify(tools)}

FORMATO DE RESPUESTA OBLIGATORIO:
Debes responder SIEMPRE en formato JSON estricto sin bloques de código markdown, con esta estructura exacta:
{
  "thought": "Explicación interna de qué herramienta necesitas y por qué.",
  "toolCall": {
    "name": "nombre_de_la_herramienta",
    "arguments": { "arg1": "valor" }
  }
}

O si ya tienes la información del sistema para responderle directamente al usuario:
{
  "thought": "Tengo los datos necesarios de la base de datos.",
  "message": "Mensaje en español amigable, detallando la respuesta de forma clara al usuario."
}

REGLAS DE CONTEXTO Y CONFIANZA DE DATOS (CRÍTICO):
1. Si en el historial de conversación (mensajes con rol "user") recibes un mensaje que comienza con "[RESULTADO DE LA HERRAMIENTA ...]", considéralo como datos verídicos y reales inyectados de forma segura por la base de datos de nuestro ERP para responder a tu llamada de herramienta.
2. Utiliza estos datos JSON del historial para formular tu respuesta final de forma amigable y detallada en el campo "message".
3. NUNCA respondas al usuario afirmando que no puedes acceder a la información, que no tienes precios en tiempo real o que no puedes consultar la base de datos directamente si ya tienes los resultados de la herramienta en tu contexto. Tu único deber es tomar esos datos y formatearlos de forma legible en el campo "message".
`;

            // C. Consultar a Gemma localmente
            const rawResponse = await askGemmaLocalWorker(conversationHistory, systemPrompt);

            try {
                const response = cleanAndParseJSON(rawResponse);
                console.log("[IA Thought]:", response.thought);

                // CASO A: IA solicita invocar una herramienta
                if (response.toolCall) {
                    const { name, arguments: args } = response.toolCall;
                    updateLoadingText(`Ejecutando herramienta: ${name}...`);

                    // IMPORTANTE: Guardamos el paso de razonamiento del asistente en el historial para
                    // mantener un flujo de turnos estrictamente alternado (user -> assistant -> user)
                    conversationHistory.push({ role: "assistant", content: rawResponse });

                    // Llamamos al backend para ejecutar la herramienta dinámica contra SQL
                    const executionResponse = await fetch('/api/ai/tools/execute', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ name, arguments: args })
                    });
                    const executionResult = await executionResponse.json();

                    // Comprobar si la IA hizo una búsqueda recursiva de herramientas en caliente
                    if (executionResult.es_busqueda_de_herramientas) {
                        conversationHistory.push({
                            role: "system",
                            content: `[RESULTADO DE LA BÚSQUEDA DE HERRAMIENTAS]: Nuevas herramientas encontradas: ${JSON.stringify(executionResult.herramientas_encontradas)}. Re-evalúa tu respuesta y utiliza una si es necesario.`
                        });
                        continue;
                    }

                    // Inyectar el resultado SQL real en el contexto de diálogo
                    conversationHistory.push({
                        role: "system",
                        content: `[RESULTADO DE LA HERRAMIENTA ${name}]: ${JSON.stringify(executionResult)}`
                    });

                    // Comprobar si la herramienta era de navegación
                    if (name === "navigate_to") {
                        agentResolved = true;
                        hideLoadingDots();
                        appendSystemBubble("Redirigiendo de pantalla...");
                        AgentState.saveSessionState(true, currentConversationId, modelSelector?.value);

                        // Redirigir dinámicamente según la lógica de frontend
                        setTimeout(() => {
                            const routes = {
                                'transfers.index': '/transfers',
                                'transfers.create': '/transfers/create',
                                'inventory': '/inventory',
                                'audit': '/audit',
                                'settings': '/settings'
                            };
                            window.location.href = routes[args.route_name] || '/';
                        }, 1000);
                        return;
                    }

                    continue; // Volver a consultar a Gemma con los datos del ERP inyectados
                }

                // CASO B: IA responde finalmente al usuario
                if (response.message) {
                    hideLoadingDots();
                    appendSystemBubble(response.message);
                    await saveMessageToServer('assistant', response.message);
                    conversationHistory.push({ role: "assistant", content: response.message });
                    agentResolved = true;
                }

            } catch (e) {
                // Fallback si Gemma devuelve texto plano o tiene error de parsing
                hideLoadingDots();
                appendSystemBubble(rawResponse);
                await saveMessageToServer('assistant', rawResponse);
                conversationHistory.push({ role: "assistant", content: rawResponse });
                agentResolved = true;
            }
        }

        if (loopLimit === 0 && !agentResolved) {
            hideLoadingDots();
            appendSystemBubble("Disculpa, se agotó el límite de intentos de consulta con el sistema. Intenta reformular tu pregunta.");
        }
    }

    function askGemmaLocalWorker(history, systemPrompt) {
        if (USE_LITERT) {
            return new Promise(async (resolve) => {
                if (!liteRtEngine) {
                    resolve("Error: Motor LiteRT no inicializado.");
                    return;
                }
                console.log("[Ureña-AI LiteRT] Generando respuesta local con Gemma...");
                try {
                    // Formatear el historial de conversación en el formato estricto de turnos de Gemma
                    let formattedPrompt = `<start_of_turn>user\n${systemPrompt}\n\n`;
                    history.forEach(msg => {
                        const role = msg.role === "system" ? "user" : msg.role;
                        formattedPrompt += `<start_of_turn>${role}\n${msg.content}<end_of_turn>\n`;
                    });
                    formattedPrompt += `<start_of_turn>model\n`;

                    const reply = await liteRtEngine.generateResponse(formattedPrompt);
                    console.log("[Ureña-AI LiteRT] Respuesta generada con éxito.");
                    resolve(reply);
                } catch (error) {
                    console.error("[Ureña-AI LiteRT] Error al generar respuesta:", error);
                    resolve(`Error en LiteRT: ${error.message}`);
                }
            });
        }

        if (mainThreadFallbackActive && mainThreadChat) {
            console.log("[Ureña-AI Hilo Principal] Generando respuesta local...");
            return new Promise(async (resolve) => {
                const messages = [
                    { role: "system", content: systemPrompt },
                    ...history.map(msg => ({
                        role: msg.role === "system" ? "user" : msg.role,
                        content: msg.content
                    }))
                ];
                try {
                    const reply = await mainThreadChat.chat.completions.create({
                        messages: messages,
                        temperature: 0.1,
                        max_tokens: 1024
                    });
                    const response = reply.choices[0].message.content;
                    console.log("[Ureña-AI Hilo Principal] Respuesta generada con éxito.");
                    resolve(response);
                } catch (error) {
                    console.error("[Ureña-AI Hilo Principal] Error generando respuesta:", error);
                    resolve(`Error: ${error.message}`);
                }
            });
        }

        // Si el worker está activo y funcionando
        return new Promise((resolve) => {
            aiWorker.postMessage({
                type: "generate",
                data: {
                    history: history,
                    systemPrompt: systemPrompt
                }
            });

            aiWorker.onmessage = function (event) {
                if (event.data.type === "result") {
                    resolve(event.data.data);
                }
                if (event.data.type === "error") {
                    resolve(`Error: ${event.data.data}`);
                }
            };
        });
    }

    async function saveMessageToServer(role, content) {
        try {
            await fetch(`/api/ai/conversations/${currentConversationId}/messages`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ role, content })
            });
        } catch (e) {
            console.error("Error al guardar mensaje en servidor:", e);
        }
    }

    // ────────────────────────────────────────────────────────
    // 5. Helpers Visuales de la Interfaz (Burbujas & Animaciones)
    // ────────────────────────────────────────────────────────
    function renderUserBubble(text) {
        const wrap = document.createElement("div");
        wrap.className = "d-flex justify-content-end mb-3";
        wrap.innerHTML = `
            <div class="chat-bubble-user">${escapeHtml(text)}</div>
        `;
        chatMessages.appendChild(wrap);
    }

    function renderSystemBubble(text) {
        const wrap = document.createElement("div");
        wrap.className = "d-flex justify-content-start mb-3";
        wrap.innerHTML = `
            <div class="chat-bubble-system">${text.replace(/\n/g, '<br>')}</div>
        `;
        chatMessages.appendChild(wrap);
    }

    function renderThoughtBubble(thought, toolCall) {
        const wrap = document.createElement("div");
        wrap.className = "d-flex justify-content-start mb-2";
        
        let toolCallHtml = "";
        if (toolCall) {
            const toolName = toolCall.name;
            const args = JSON.stringify(toolCall.arguments || {});
            toolCallHtml = `
                <div class="mt-1 pt-1 border-top border-light fs-10 text-primary">
                    <i class="ri-terminal-box-line me-1"></i>Herramienta: <strong>${toolName}</strong>(${args})
                </div>
            `;
        }

        wrap.innerHTML = `
            <div class="chat-bubble-thought">
                <div><i class="ri-lightbulb-line me-1 text-warning"></i><strong>Pensamiento IA:</strong> ${escapeHtml(thought)}</div>
                ${toolCallHtml}
            </div>
        `;
        chatMessages.appendChild(wrap);
    }

    function appendSystemBubble(text) {
        renderSystemBubble(text);
        scrollToBottom();
    }

    function showLoadingDots() {
        const wrap = document.createElement("div");
        wrap.id = "ai-loading-dots";
        wrap.className = "d-flex justify-content-start mb-3";
        wrap.innerHTML = `
            <div class="chat-bubble-system" style="padding: 12px 20px;">
                <span id="ai-loading-text" class="fs-12 text-muted me-2">La IA está procesando...</span>
                <span class="spinner-grow spinner-grow-sm text-primary" role="status" style="width: 6px; height: 6px;"></span>
                <span class="spinner-grow spinner-grow-sm text-primary ms-1" role="status" style="width: 6px; height: 6px;"></span>
                <span class="spinner-grow spinner-grow-sm text-primary ms-1" role="status" style="width: 6px; height: 6px;"></span>
            </div>
        `;
        chatMessages.appendChild(wrap);
        scrollToBottom();
    }

    function hideLoadingDots() {
        const dots = document.getElementById("ai-loading-dots");
        if (dots) dots.remove();
    }

    function updateLoadingText(text) {
        const textEl = document.getElementById("ai-loading-text");
        if (textEl) textEl.innerText = text;
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function escapeHtml(string) {
        return String(string).replace(/[&<>"']/g, function (s) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            }[s];
        });
    }

    // ────────────────────────────────────────────────────────
    // 6. Configuración de Eventos de la Interfaz
    // ────────────────────────────────────────────────────────
    sendButton.addEventListener("click", processMessageFlow);
    userInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter") processMessageFlow();
    });

    if (settingsButton) {
        settingsButton.addEventListener("click", () => {
            settingsPanel.classList.toggle("d-none");
        });
    }

    if (modelSelector) {
        modelSelector.addEventListener("change", () => {
            const selectedModel = modelSelector.value;
            // Guardar modelo seleccionado en storage
            AgentState.saveSessionState(true, currentConversationId, selectedModel);
            // Destruir worker actual si existía y re-inicializar
            if (aiWorker) {
                try {
                    aiWorker.terminate();
                } catch (e) {}
                aiWorker = null;
                isModelLoaded = false;
            }
            // Si el fallback estaba activo, resetearlo
            if (mainThreadFallbackActive) {
                mainThreadChat = null;
                mainThreadFallbackActive = false;
                isModelLoaded = false;
            }
            initWebWorker(selectedModel);
            settingsPanel.classList.add("d-none");
        });
    }

    if (newChatButton) {
        newChatButton.addEventListener("click", async () => {
            if (confirm("¿Deseas iniciar una nueva sesión de chat? Se mantendrá guardado el historial en el menú lateral.")) {
                AgentState.clearActiveConversation();
                await startNewChat();
            }
        });
    }

    if (thoughtsToggle) {
        thoughtsToggle.addEventListener("change", () => {
            showAiThoughts = thoughtsToggle.checked;
            localStorage.setItem("urena_ai_show_thoughts", showAiThoughts ? "true" : "false");
            
            // Recargar conversación actual si existe para re-renderizar pensamientos en caliente
            if (currentConversationId) {
                loadMessagesFromServer(currentConversationId);
            }
        });
    }

    // Diagnosticar y Cargar estado inicial
    const diag = await diagnoseHardware();
    await loadActiveChatSession(diag.recommendedModel);
});
