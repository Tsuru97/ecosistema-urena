<script>
window.initGeminiChat = function(editor) {
    var pn = editor.Panels;
    
    // Esperar a que el editor se cargue para interactuar con sus paneles DOM
    editor.on('load', function() {
        const viewsContainer = document.querySelector('.gjs-pn-views-container');
        if (!viewsContainer) return;

        // Crear el contenedor de IA que se superpondrá a los bloques
        const aiContainer = document.createElement('div');
        aiContainer.id = 'gjs-ai-container';
        aiContainer.style.display = 'none';
        aiContainer.style.padding = '15px';
        aiContainer.style.color = '#fff';
        aiContainer.style.height = '100%';
        aiContainer.style.flexDirection = 'column';
        aiContainer.style.backgroundColor = '#444'; 
        
        aiContainer.innerHTML = `
            <h5 style="color: #fff; margin-bottom: 15px; font-size: 14px;">
                <i class="ri-sparkling-fill text-warning"></i> Asistente Gemini
            </h5>
            <div id="ai-chat-history" style="flex-grow: 1; overflow-y: auto; margin-bottom: 10px; font-size: 13px; padding-right: 5px;">
                <div style="margin-bottom: 15px; color: #ccc;">Dime qué componente de Bootstrap necesitas y lo construiré para ti.</div>
            </div>
            <div style="display: flex; flex-direction: column;">
                <textarea id="ai-prompt" class="form-control bg-dark text-white border-secondary mb-2 form-control-sm" rows="3" placeholder="Ej: Crea una tabla simple de 3 columnas para precios" style="font-size: 12px; resize: none;"></textarea>
                <button id="btn-ai-generate" class="btn btn-primary btn-sm w-100">
                    <i class="ri-magic-line me-1"></i> Generar e Inyectar
                </button>
            </div>
        `;
        
        viewsContainer.appendChild(aiContainer);

        // Añadir el botón para abrir el asistente de IA en el panel de Opciones de arriba a la derecha
        if (!pn.getButton('options', 'open-ai')) {
            pn.addButton('options', [{
                id: 'open-ai',
                className: 'fa fa-magic', // Usar icono compatible (font-awesome o grapesjs base)
                command: 'open-ai',
                attributes: { title: 'Asistente IA (Gemini)', 'data-tooltip-pos': 'bottom' }
            }]);
        }

        editor.Commands.add('open-ai', {
            run(editor, sender) {
                // Ocultar paneles de vistas normales (bloques, estilos)
                Array.from(viewsContainer.children).forEach(child => {
                    if (child.id !== 'gjs-ai-container') child.style.display = 'none';
                });
                aiContainer.style.display = 'flex';
                // Apagar botones de Vistas activas en GrapesJs
                const viewsPanel = pn.getPanel('views');
                if (viewsPanel) {
                    viewsPanel.get('buttons').each(btn => {
                        if (btn.get('active')) btn.set('active', false, { silent: true });
                    });
                }
            },
            stop(editor, sender) {
                aiContainer.style.display = 'none';
                
                // Solo reactivamos el panel de bloques si no hay ninguna otra vista activa
                // (por ejemplo, si el usuario apagó la IA manualmente haciendo clic en su botón)
                const viewsPanel = pn.getPanel('views');
                let anyActive = false;
                if (viewsPanel) {
                    viewsPanel.get('buttons').each(btn => {
                        if (btn.get('active')) anyActive = true;
                    });
                }
                
                if (!anyActive) {
                    const blocksBtn = pn.getButton('views', 'open-blocks');
                    if (blocksBtn) blocksBtn.set('active', 1);
                }
            }
        });

        // Cuando el usuario hace clic en los botones de "Bloques" o "Estilos" nativos, apagamos nuestro comando AI
        const viewsPanel = pn.getPanel('views');
        if (viewsPanel) {
            viewsPanel.get('buttons').each(btn => {
                btn.on('change:active', (model, val) => {
                    if (val && model.id !== 'open-ai') {
                        editor.stopCommand('open-ai');
                    }
                });
            });
        }

        // Lógica de Generación
        document.getElementById('btn-ai-generate').addEventListener('click', function() {
            var prompt = document.getElementById('ai-prompt').value;
            if(!prompt.trim()) return;
            
            var history = document.getElementById('ai-chat-history');
            history.innerHTML += `<div style="margin-bottom: 10px; color: #fff; background: rgba(255,255,255,0.05); padding: 8px; border-radius: 5px;"><strong>Tú:</strong> ${prompt}</div>`;
            
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generando...';
            this.disabled = true;
            
            fetch('{{ route("feed.ai.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ prompt: prompt })
            })
            .then(res => res.json())
            .then(data => {
                if(data.html) {
                    history.innerHTML += `<div style="margin-bottom: 10px; color: #0ab39c; background: rgba(10, 179, 156, 0.1); padding: 8px; border-radius: 5px;"><strong>Gemini:</strong> ¡Bloque generado e inyectado!</div>`;
                    editor.addComponents(data.html);
                    history.scrollTop = history.scrollHeight;
                } else {
                    history.innerHTML += `<div style="margin-bottom: 10px; color: #f06548; padding: 8px;"><strong>Error:</strong> ${data.error || 'No se pudo generar'}</div>`;
                }
            })
            .catch(err => {
                history.innerHTML += `<div style="margin-bottom: 10px; color: #f06548; padding: 8px;"><strong>Error:</strong> Fallo en comunicación.</div>`;
            })
            .finally(() => {
                this.innerHTML = '<i class="ri-magic-line me-1"></i> Generar e Inyectar';
                this.disabled = false;
                document.getElementById('ai-prompt').value = '';
                history.scrollTop = history.scrollHeight;
            });
        });
    });
};
</script>
