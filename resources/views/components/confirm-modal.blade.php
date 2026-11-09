{{--
    Componente: x-confirm-modal
    ──────────────────────────────────────────────────────────────────────
    Modal Bootstrap reutilizable para confirmar acciones destructivas.

    Uso básico (eliminar registro):
        <button type="button"
                data-confirm
                data-form="form-id"
                data-title="Eliminar Usuario"
                data-message="¿Estás seguro de eliminar a <strong>Juan</strong>? Esta acción no se puede deshacer."
                data-btn-label="Sí, eliminar"
                data-btn-class="btn-danger">
            <i class="ri-delete-bin-line"></i>
        </button>
        <form id="form-id" action="{{ route(...) }}" method="POST" style="display:none;">
            @csrf @method('DELETE')
        </form>

    Atributos del botón disparador:
      - data-confirm       → (requerido) marca el botón como disparador del modal
      - data-form          → ID del <form> que se enviará al confirmar
      - data-title         → Título del modal (default: "Confirmar Acción")
      - data-message       → Cuerpo del modal. Acepta HTML básico.
      - data-btn-label     → Texto del botón de confirmación (default: "Confirmar")
      - data-btn-class     → Clase CSS del botón de confirmación (default: "btn-danger")
--}}

<div class="modal fade" id="confirmActionModal" tabindex="-1"
     aria-labelledby="confirmActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmActionModalLabel">
                    <i class="ri-alert-line me-2 text-warning"></i>
                    <span id="confirmModalTitle">Confirmar Acción</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="confirmModalMessage" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmModalBtn">
                    <i class="ri-check-line me-1"></i>
                    <span id="confirmModalBtnLabel">Confirmar</span>
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    'use strict';

    var confirmModal   = document.getElementById('confirmActionModal');
    var modalTitle     = document.getElementById('confirmModalTitle');
    var modalMessage   = document.getElementById('confirmModalMessage');
    var modalBtn       = document.getElementById('confirmModalBtn');
    var modalBtnLabel  = document.getElementById('confirmModalBtnLabel');
    var targetFormId   = null;

    // Escuchar todos los clics del documento (event delegation)
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-confirm]');
        if (!trigger) return;

        e.preventDefault();
        e.stopPropagation();

        // Leer atributos del disparador
        targetFormId = trigger.getAttribute('data-form') || null;

        var title   = trigger.getAttribute('data-title')   || 'Confirmar Acción';
        var message = trigger.getAttribute('data-message') || '¿Estás seguro de continuar?';
        var label   = trigger.getAttribute('data-btn-label') || 'Confirmar';
        var cls     = trigger.getAttribute('data-btn-class') || 'btn-danger';

        // Actualizar el modal
        modalTitle.textContent   = title;
        modalMessage.innerHTML   = message;
        modalBtnLabel.textContent = label;
        modalBtn.className = 'btn ' + cls;

        // Mostrar el modal
        var bsModal = bootstrap.Modal.getOrCreateInstance(confirmModal);
        bsModal.show();
    });

    // Al hacer clic en "Confirmar" dentro del modal
    modalBtn.addEventListener('click', function () {
        if (targetFormId) {
            var form = document.getElementById(targetFormId);
            if (form) {
                form.submit();
                return;
            }
        }
        // Fallback: si no hay form definido, simplemente cierra
        bootstrap.Modal.getInstance(confirmModal).hide();
    });
})();
</script>
@endpush
@endonce
