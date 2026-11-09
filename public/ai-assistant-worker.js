// public/ai-assistant-worker.js

console.log("[Worker] Archivo de Web Worker cargado e inicializando...");

import { CreateMLCEngine } from "https://esm.run/@mlc-ai/web-llm@0.2.84";

let engine = null;

// Escuchamos los comandos provenientes del hilo principal de JavaScript
self.onmessage = async (event) => {
    const { type, data } = event.data;
    console.log("[Worker] Tarea recibida en el Worker:", type, data);

    try {
        if (type === "init") {
            const modelId = data.modelId || "gemma-2-2b-it-q4f16_1-MLC";
            console.log(`[Worker] Inicializando modelo: ${modelId}...`);
            
            // Inicializar el motor local con el API moderno CreateMLCEngine
            engine = await CreateMLCEngine(modelId, {
                initProgressCallback: (report) => {
                    console.log(`[Worker Progress Callback] ${report.text} | Progreso: ${Math.round(report.progress * 100)}%`);
                    self.postMessage({ type: "progress", data: report });
                }
            });

            console.log("[Worker] Modelo recargado e inicializado de manera exitosa en WebLLM.");
            self.postMessage({ type: "ready" });
        }

        if (type === "generate") {
            const { history, systemPrompt } = data;
            console.log("[Worker] Generando respuesta local con Gemma...");
            console.log("[Worker] Mensajes históricos:", history.length);

            // Formateamos el historial de conversación al formato de WebLLM
            // Convertimos cualquier rol 'system' subsecuente (como resultados de tools) a 'user' para evitar el SystemMessageOrderError
            const messages = [
                { role: "system", content: systemPrompt },
                ...history.map(msg => ({
                    role: msg.role === "system" ? "user" : msg.role,
                    content: msg.content
                }))
            ];

            // Generamos la respuesta usando la API compatible con OpenAI
            const reply = await engine.chat.completions.create({
                messages: messages,
                temperature: 0.1, // Muy bajo para evitar alucinaciones en los toolCalls JSON
                max_tokens: 1024
            });

            const response = reply.choices[0].message.content;
            console.log("[Worker] Respuesta generada con éxito.");
            self.postMessage({ type: "result", data: response });
        }
    } catch (error) {
        console.error("[Worker] Error interno en el Web Worker:", error);
        self.postMessage({ type: "error", data: error.message });
    }
};
