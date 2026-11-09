<script>
window.addVelzonBlocks = function(bm) {
    // 1. Layout / Contenedores
    bm.add('vz-container', {
        label: 'Contenedor', category: 'Layout/Contenedores',
        content: '<div class="container" style="min-height:50px; padding:10px;"></div>',
        attributes: { class: 'ri-layout-top-line fs-2 pb-2' }
    });
    bm.add('vz-container-fluid', {
        label: 'Contenedor Fluido', category: 'Layout/Contenedores',
        content: '<div class="container-fluid" style="min-height:50px; padding:10px;"></div>',
        attributes: { class: 'ri-layout-top-fill fs-2 pb-2' }
    });

    // 2. Layout / Grid
    bm.add('vz-row', {
        label: 'Fila (Row)', category: 'Layout/Grid',
        content: '<div class="row" style="min-height:50px; padding:10px;"></div>',
        attributes: { class: 'ri-layout-row-line fs-2 pb-2' }
    });

    // 3. Layout / Columnas
    bm.add('vz-col-12', {
        label: 'Col 12 (100%)', category: 'Layout/Columnas',
        content: '<div class="col-12" style="min-height:50px; padding:10px;"></div>',
        attributes: { class: 'ri-layout-column-line fs-2 pb-2' }
    });
    bm.add('vz-col-6', {
        label: 'Col 6 (50%)', category: 'Layout/Columnas',
        content: '<div class="col-md-6" style="min-height:50px; padding:10px;"></div>',
        attributes: { class: 'ri-layout-column-line fs-2 pb-2' }
    });
    bm.add('vz-col-4', {
        label: 'Col 4 (33%)', category: 'Layout/Columnas',
        content: '<div class="col-md-4" style="min-height:50px; padding:10px;"></div>',
        attributes: { class: 'ri-layout-column-line fs-2 pb-2' }
    });
    bm.add('vz-col-3', {
        label: 'Col 3 (25%)', category: 'Layout/Columnas',
        content: '<div class="col-md-3" style="min-height:50px; padding:10px;"></div>',
        attributes: { class: 'ri-layout-column-line fs-2 pb-2' }
    });

    // 4. Tipografia / Headings
    bm.add('vz-h1', { label: 'Heading 1', category: 'Tipografia/Headings', content: '<h1 class="mb-3">Heading 1</h1>', attributes: { class: 'ri-h-1 fs-2 pb-2' } });
    bm.add('vz-h2', { label: 'Heading 2', category: 'Tipografia/Headings', content: '<h2 class="mb-3">Heading 2</h2>', attributes: { class: 'ri-h-2 fs-2 pb-2' } });
    bm.add('vz-h3', { label: 'Heading 3', category: 'Tipografia/Headings', content: '<h3 class="mb-3">Heading 3</h3>', attributes: { class: 'ri-h-3 fs-2 pb-2' } });
    bm.add('vz-h4', { label: 'Heading 4', category: 'Tipografia/Headings', content: '<h4 class="mb-3">Heading 4</h4>', attributes: { class: 'ri-h-4 fs-2 pb-2' } });

    // 5. Tipografia / Texto
    bm.add('vz-p', { label: 'Párrafo', category: 'Tipografia/Texto', content: '<p class="text-muted mb-3">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>', attributes: { class: 'ri-paragraph fs-2 pb-2' } });
    bm.add('vz-lead', { label: 'Texto Destacado', category: 'Tipografia/Texto', content: '<p class="lead mb-3">Este es un párrafo destacado (Lead).</p>', attributes: { class: 'ri-text fs-2 pb-2' } });

    // 6. Tipografia / Listas
    bm.add('vz-ul', { label: 'Lista Desordenada', category: 'Tipografia/Listas', content: '<ul class="mb-3"><li>Elemento 1</li><li>Elemento 2</li><li>Elemento 3</li></ul>', attributes: { class: 'ri-list-unordered fs-2 pb-2' } });
    bm.add('vz-ol', { label: 'Lista Ordenada', category: 'Tipografia/Listas', content: '<ol class="mb-3"><li>Elemento 1</li><li>Elemento 2</li><li>Elemento 3</li></ol>', attributes: { class: 'ri-list-ordered fs-2 pb-2' } });
    bm.add('vz-list-unstyled', { label: 'Lista sin viñetas', category: 'Tipografia/Listas', content: '<ul class="list-unstyled mb-3"><li>Elemento sin estilo 1</li><li>Elemento 2</li></ul>', attributes: { class: 'ri-list-check fs-2 pb-2' } });

    // 7. Tipografia / Utilidades
    bm.add('vz-blockquote', { label: 'Cita', category: 'Tipografia/Utilidades', content: '<blockquote class="blockquote custom-blockquote blockquote-outline blockquote-primary rounded mb-3"><p class="mb-2">"Una cita de ejemplo basada en Velzon."</p><footer class="blockquote-footer mt-0">Autor<cite title="Source Title">Fuente</cite></footer></blockquote>', attributes: { class: 'ri-double-quotes-l fs-2 pb-2' } });
    bm.add('vz-hr', { label: 'Separador (HR)', category: 'Tipografia/Utilidades', content: '<hr class="my-4">', attributes: { class: 'ri-separator fs-2 pb-2' } });

    // 8. Botones / Sólidos
    const solidBtns = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
    solidBtns.forEach(color => {
        bm.add('vz-btn-'+color, {
            label: 'Btn ' + color.charAt(0).toUpperCase() + color.slice(1), category: 'Botones/Sólidos',
            content: `<a href="#" class="btn btn-${color} waves-effect waves-light btn-sm">Botón</a>`,
            attributes: { class: 'ri-checkbox-blank-fill fs-2 pb-2 text-' + color }
        });
    });

    // 9. Botones / Outline
    solidBtns.forEach(color => {
        bm.add('vz-btn-outline-'+color, {
            label: 'Out ' + color.charAt(0).toUpperCase() + color.slice(1), category: 'Botones/Outline',
            content: `<a href="#" class="btn btn-outline-${color} waves-effect waves-light btn-sm">Botón</a>`,
            attributes: { class: 'ri-checkbox-blank-line fs-2 pb-2 text-' + color }
        });
    });

    // 10. Botones / Tamaños
    bm.add('vz-btn-lg', { label: 'Botón Grande', category: 'Botones/Tamaños', content: '<a href="#" class="btn btn-primary waves-effect waves-light btn-sm">Botón Grande</a>', attributes: { class: 'ri-font-size-2 fs-2 pb-2' } });
    bm.add('vz-btn-sm', { label: 'Botón Pequeño', category: 'Botones/Tamaños', content: '<a href="#" class="btn btn-primary btn-sm waves-effect waves-light">Pequeño</a>', attributes: { class: 'ri-font-size fs-3 pb-2' } });

    // 11. Botones / Grupos
    bm.add('vz-btn-group', {
        label: 'Grupo Botones', category: 'Botones/Grupos',
        content: '<div class="btn-group" role="group"><button type="button" class="btn btn-primary btn-sm">Izq</button><button type="button" class="btn btn-primary btn-sm">Centro</button><button type="button" class="btn btn-primary btn-sm">Der</button></div>',
        attributes: { class: 'ri-group-line fs-2 pb-2' }
    });

    // 12. Botones / Especiales
    bm.add('vz-btn-icon', {
        label: 'Boton Ícono', category: 'Botones/Especiales',
        content: '<button type="button" class="btn btn-primary btn-label waves-effect waves-light btn-sm"><i class="ri-user-smile-line label-icon align-middle fs-16 me-2"></i> Botón</button>',
        attributes: { class: 'ri-star-line fs-2 pb-2' }
    });
    bm.add('vz-btn-soft', {
        label: 'Botón Suave', category: 'Botones/Especiales',
        content: '<button type="button" class="btn btn-soft-success waves-effect waves-light btn-sm">Botón Suave</button>',
        attributes: { class: 'ri-contrast-drop-2-line fs-2 pb-2' }
    });

    // 13. Cards / Básicas
    bm.add('vz-card-basic', {
        label: 'Card Básica', category: 'Cards/Básicas',
        content: '<div class="card"><div class="card-body"><h4 class="card-title mb-2">Título de Tarjeta</h4><p class="card-text text-muted">Contenido básico de la tarjeta.</p></div></div>',
        attributes: { class: 'ri-file-list-line fs-2 pb-2' }
    });

    // 14. Cards / Con imagen
    bm.add('vz-card-img-top', {
        label: 'Card Imagen Up', category: 'Cards/Con imagen',
        content: '<div class="card"><img class="card-img-top img-fluid" src="https://via.placeholder.com/300x200" alt="Card image cap"><div class="card-body"><h4 class="card-title mb-2">Título</h4><p class="card-text text-muted">Contenido tarjeta con imagen.</p></div></div>',
        attributes: { class: 'ri-image-line fs-2 pb-2' }
    });
    bm.add('vz-card-img-overlay', {
        label: 'Card Overlay', category: 'Cards/Con imagen',
        content: '<div class="card card-overlay"><img class="card-img img-fluid" src="https://via.placeholder.com/400x200" alt="Card image"><div class="card-img-overlay p-0"><div class="card-header bg-transparent"><h4 class="card-title text-white mb-0">Título Blanco</h4></div><div class="card-body"><p class="card-text text-white">Contenido overlay.</p></div></div></div>',
        attributes: { class: 'ri-image-edit-line fs-2 pb-2' }
    });

    // 15. Cards / Header y Footer
    bm.add('vz-card-full', {
        label: 'Card Completa', category: 'Cards/Header y Footer',
        content: '<div class="card"><div class="card-header"><h6 class="card-title mb-0">Cabecera</h6></div><div class="card-body"><p class="card-text text-muted">Contenido central</p></div><div class="card-footer"><a href="#" class="link-success float-end">Leer más <i class="ri-arrow-right-s-line align-middle ms-1 lh-1"></i></a><p class="text-muted mb-0">Pie de página</p></div></div>',
        attributes: { class: 'ri-layout-top-line fs-2 pb-2' }
    });

    // 16. Cards / Horizontales
    bm.add('vz-card-horizontal', {
        label: 'Card Horizontal', category: 'Cards/Horizontales',
        content: '<div class="card"><div class="row g-0"><div class="col-md-4"><img class="rounded-start img-fluid h-100 object-fit-cover" src="https://via.placeholder.com/200" alt="Card image"></div><div class="col-md-8"><div class="card-header"><h5 class="card-title mb-0">Título</h5></div><div class="card-body"><p class="card-text mb-2 text-muted">Contenido horizontal a la izquierda.</p></div></div></div></div>',
        attributes: { class: 'ri-layout-left-line fs-2 pb-2' }
    });

    // 17. Cards / Grupos
    bm.add('vz-card-group', {
        label: 'Grupo de Cards', category: 'Cards/Grupos',
        content: '<div class="card-group"><div class="card"><div class="card-body"><p class="card-text">Tarjeta 1</p></div></div><div class="card"><div class="card-body"><p class="card-text">Tarjeta 2</p></div></div></div>',
        attributes: { class: 'ri-grid-fill fs-2 pb-2' }
    });

    // 18. Cards / Especiales
    bm.add('vz-card-border-primary', {
        label: 'Card Bordes', category: 'Cards/Especiales',
        content: '<div class="card border border-primary"><div class="card-body"><p class="card-text">Tarjeta con bordes primarios.</p></div></div>',
        attributes: { class: 'ri-checkbox-blank-line fs-2 pb-2 text-primary' }
    });
    bm.add('vz-card-bg-success', {
        label: 'Card Fondo Suave', category: 'Cards/Especiales',
        content: '<div class="card bg-success-subtle text-success"><div class="card-body"><p class="card-text mt-3">Fondo suave verde.</p></div></div>',
        attributes: { class: 'ri-checkbox-blank-fill fs-2 pb-2 text-success' }
    });

    // 19. Laravel Componentes
    bm.add('vz-laravel-placeholder', {
        label: 'Próximamente', category: 'Laravel Componentes',
        content: '<div style="padding: 20px; border: 2px dashed #ccc; text-align: center; color: #999;">Componente Laravel Próximamente</div>',
        attributes: { class: 'ri-braces-line fs-2 pb-2' }
    });

    // Readd standard alerts but grouped under custom category just in case it's needed again since we removed them from previous replace block
    bm.add('vz-alert-success', {
        label: 'Alerta Éxito', category: 'Layout/Utilidades (Alertas)',
        content: '<div class="alert alert-success alert-dismissible fade show material-shadow" role="alert"><strong>¡Éxito!</strong> Acción completada.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
        attributes: { class: 'ri-checkbox-circle-line pb-2 fs-2' }
    });
    bm.add('vz-alert-warning', {
        label: 'Alerta Precaución', category: 'Layout/Utilidades (Alertas)',
        content: '<div class="alert alert-warning alert-dismissible fade show material-shadow" role="alert"><strong>¡Atención!</strong> Revisa esta información.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
        attributes: { class: 'ri-alert-line pb-2 fs-2' }
    });
    bm.add('vz-alert-info', {
        label: 'Alerta Info', category: 'Layout/Utilidades (Alertas)',
        content: '<div class="alert alert-info alert-dismissible fade show material-shadow" role="alert"><strong>Información!</strong> Mensaje informativo aquí.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
        attributes: { class: 'ri-information-line pb-2 fs-2' }
    });

}
</script>
