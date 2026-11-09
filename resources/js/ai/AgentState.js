// resources/js/ai/AgentState.js

export class AgentState {
    static KEY_WIDGET_OPEN = "urena_ai_widget_open";
    static KEY_CURRENT_CONVO = "urena_ai_current_convo_id";
    static KEY_MODEL_ID = "urena_ai_selected_model_id";

    /**
     * Guarda el estado visual y del chat actual antes de refrescar la pantalla.
     */
    static saveSessionState(isOpen, conversationId, modelId = null) {
        sessionStorage.setItem(this.KEY_WIDGET_OPEN, isOpen ? "true" : "false");
        if (conversationId) {
            sessionStorage.setItem(this.KEY_CURRENT_CONVO, conversationId.toString());
        }
        if (modelId) {
            localStorage.setItem(this.KEY_MODEL_ID, modelId); // El modelo preferido se guarda permanente
        }
    }

    /**
     * Recupera el estado de sesión tras cargar una nueva pantalla.
     */
    static getSessionState() {
        return {
            isOpen: sessionStorage.getItem(this.KEY_WIDGET_OPEN) === "true",
            conversationId: sessionStorage.getItem(this.KEY_CURRENT_CONVO) 
                ? parseInt(sessionStorage.getItem(this.KEY_CURRENT_CONVO)) 
                : null,
            modelId: localStorage.getItem(this.KEY_MODEL_ID) || "gemma-2-2b-it-q4f16_1-MLC" // Por defecto 4-bit
        };
    }

    /**
     * Limpia la conversación activa al presionar "Nuevo Chat" o cerrar sesión.
     */
    static clearActiveConversation() {
        sessionStorage.removeItem(this.KEY_CURRENT_CONVO);
    }
}
