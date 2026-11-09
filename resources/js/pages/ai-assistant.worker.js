// resources/js/pages/ai-assistant.worker.js

console.log("[Worker] Archivo de Web Worker cargado e inicializando...");

import { ChatModule } from "https://esm.sh/@mlc-ai/web-llm@0.2.46";

// Instanciamos el módulo central de WebLLM
const chat = new ChatModule();
console.log("[Worker] Instancia de ChatModule creada con éxito.");

// Escuchamos los comandos provenientes del hilo principal de JavaScript
self.onmessage = async (event) => {
    const { type, data } = event.data;
    console.log("[Worker] Tarea recibida en el Worker:", type, data);

    try {
        if (type === "init") {
            const modelId = data.modelId || "gemma-2-2b-it-q4f16_1-MLC";
            console.log(`[Worker] Inicializando modelo: ${modelId}...`);
            
            // Registramos el callback para enviar el progreso de descarga de los pesos al hilo principal
            chat.setInitProgressCallback((report) => {
                console.log(`[Worker Progress Callback] ${report.text} | Progreso: ${Math.round(report.progress * 100)}%`);
                self.postMessage({ type: "progress", data: report });
            });

            // Recargar e inicializar el motor con el modelo seleccionado
            await chat.reload(modelId);
            console.log("[Worker] Modelo recargado e inicializado de manera exitosa en WebLLM.");
            self.postMessage({ type: "ready" });
        }

        if (type === "generate") {
            const { history, systemPrompt } = data;
            console.log("[Worker] Generando respuesta local con Gemma...");
            console.log("[Worker] Mensajes históricos:", history.length);

            // Formateamos el historial de conversación al formato de WebLLM
            // WebLLM chat.generate acepta un historial completo
            const messages = [
                { role: "system", content: systemPrompt },
                ...history
            ];

            // Generamos la respuesta con baja temperatura para maximizar el determinismo
            const response = await chat.generate(messages, {
                temperature: 0.1, // Muy bajo para evitar alucinaciones en los toolCalls JSON
                max_gen_len: 1024
            });

            console.log("[Worker] Respuesta generada con éxito.");
            self.postMessage({ type: "result", data: response });
        }
    } catch (error) {
        console.error("[Worker] Error interno en el Web Worker:", error);
        self.postMessage({ type: "error", data: error.message });
    }
};
