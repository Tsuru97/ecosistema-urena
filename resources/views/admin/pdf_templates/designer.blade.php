@extends('layouts.master')

@section('title') Diseñador de Plantillas de Etiquetas @endsection

@section('css')
    <!-- SweetAlert2 -->
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        /* Contenedor del Editor a Pantalla Completa */
        .designer-container {
            height: calc(100vh - 220px);
            min-height: 650px;
            background: #1a1d24;
            border-radius: 0 0 8px 8px;
            position: relative;
            overflow: hidden;
            display: flex;
        }

        .dark-card-header {
            background: linear-gradient(135deg, #1f2937, #111827);
            color: #fff;
            border-bottom: 1px solid #2d323f;
        }

        /* Estilos del Workspace Canvas */
        #designer-canvas-wrapper {
            position: relative;
            margin: auto;
            flex-shrink: 0;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            background-color: #ffffff;
            background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px);
            background-size: 16px 16px;
            transition: width 0.3s, height 0.3s;
            border-radius: 4px;
        }

        .draggable-field {
            position: absolute;
            background: rgba(255, 255, 255, 0.9);
            border: 1.5px dashed #4f46e5;
            border-radius: 4px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            touch-action: none;
            user-select: none;
            padding: 2px 4px;
            cursor: move;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .draggable-field.active-field {
            border: 2px solid #6366f1;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
            z-index: 10;
        }

        .field-title {
            font-size: 8px;
            color: #4f46e5;
            text-transform: uppercase;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 2px;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
        }

        .field-preview {
            font-size: 11px;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }

        .unselectable {
            -webkit-user-drag: none;
            user-select: none;
        }

        /* Barra Lateral Derecha - Estilo GrapeJS */
        .designer-sidebar-right {
            width: 380px;
            flex-shrink: 0;
            background-color: #1e222b !important;
            border-left: 1px solid #2d323f !important;
            display: flex;
            flex-direction: column;
            color: #cbd5e1;
            overflow: hidden;
        }

        .designer-sidebar-header {
            padding: 15px;
            border-bottom: 1px solid #2d323f;
            background-color: #1a1d24;
        }

        .designer-sidebar-body {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
        }

        /* Tabs de Navegación */
        .designer-sidebar-right .nav-pills .nav-link {
            background-color: #2a2f3b;
            color: #a0aec0;
            border: 1px solid #3f4756;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .designer-sidebar-right .nav-pills .nav-link:hover {
            color: #fff;
            background-color: #353b4a;
        }

        .designer-sidebar-right .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #4f46e5, #6366f1) !important;
            color: #fff !important;
            border-color: #6366f1;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }

        /* Rejilla de Elementos en Acordeón */
        .elements-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 5px 0;
        }

        .element-btn {
            background: #2a2f3b;
            border: 1px solid #3f4756;
            color: #e2e8f0;
            border-radius: 6px;
            padding: 12px 8px;
            cursor: pointer;
            text-align: center;
            font-size: 11px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
            user-select: none;
        }

        .element-btn i {
            font-size: 18px;
            color: #818cf8;
            transition: transform 0.2s ease;
        }

        .element-btn:hover {
            background: #353b4a;
            border-color: #6366f1;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .element-btn:hover i {
            transform: scale(1.1);
            color: #a5b4fc;
        }

        /* Estilización de Inputs en Modo Oscuro */
        .designer-dark-input, .designer-dark-select {
            background-color: #2a2f3b !important;
            border: 1px solid #3f4756 !important;
            color: #fff !important;
            font-size: 13px !important;
        }

        .designer-dark-input::placeholder {
            color: #718096 !important;
        }

        .designer-dark-input:focus, .designer-dark-select:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 0.15rem rgba(99, 102, 241, 0.25) !important;
            outline: none;
        }

        .designer-dark-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
        }

        .designer-sidebar-right .form-label {
            color: #a0aec0;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 5px;
            display: block;
        }

        /* Acordeón Personalizado Oscuro */
        .designer-accordion .accordion-item {
            background-color: #1e222b !important;
            border: 1px solid #2d323f !important;
            border-radius: 6px !important;
            margin-bottom: 8px;
            overflow: hidden;
        }

        .designer-accordion .accordion-button {
            background-color: #20242e !important;
            color: #e2e8f0 !important;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 15px;
            border: none;
            box-shadow: none !important;
        }

        .designer-accordion .accordion-button:not(.collapsed) {
            background-color: #262b37 !important;
            color: #818cf8 !important;
        }

        .designer-accordion .accordion-button::after {
            filter: invert(1) brightness(0.8);
        }

        .designer-accordion .accordion-body {
            background-color: #161920 !important;
            padding: 10px;
            border-top: 1px solid #2d323f;
        }

        /* Personalización del selector de color */
        .designer-color-input {
            height: 38px;
            padding: 4px;
            cursor: pointer;
        }

        /* Inspector Empty State */
        .inspector-empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #718096;
        }

        .inspector-empty-state i {
            font-size: 28px;
            color: #4f46e5;
            opacity: 0.7;
        }
    </style>
@endsection

@section('content')
    <x-breadcrumb title="Diseñador Visual de Plantillas (Nativo)" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-lg mb-4 bg-dark text-white border-0">
                <div class="card-header dark-card-header d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                    <!-- Sección de Selección y Título -->
                    <div class="d-flex align-items-center gap-2" style="min-width: 320px;">
                        <span class="fw-bold fs-14 text-white"><i class="ri-edit-box-line me-1"></i> Plantilla:</span>
                        <select class="form-select form-select-sm designer-dark-select border-0 py-1" id="templateSelect" style="width: auto; max-width: 250px;">
                            <option value="new" data-name="" data-type="producto" selected>--- ➕ Crear Nueva Plantilla ---</option>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}" data-name="{{ $t->name }}" data-type="{{ $t->document_type }}">
                                    {{ $t->name }} ({{ strtoupper($t->document_type) }})
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-sm btn-danger py-1" id="btnDeleteTemplate" title="Eliminar Plantilla Seleccionada" style="display: none;">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>

                    <!-- Controles de Zoom -->
                    <div class="d-flex align-items-center bg-black bg-opacity-25 rounded px-2 py-1 gap-2">
                        <button type="button" class="btn btn-sm btn-link text-white py-0 px-2 fs-14" id="btnZoomOut" title="Disminuir Zoom"><i class="ri-zoom-out-line"></i></button>
                        <span class="text-white fw-bold fs-12 px-1" id="zoomPercent" style="min-width: 45px; text-align: center;">100%</span>
                        <button type="button" class="btn btn-sm btn-link text-white py-0 px-2 fs-14" id="btnZoomIn" title="Aumentar Zoom"><i class="ri-zoom-in-line"></i></button>
                        <button type="button" class="btn btn-sm btn-link text-white py-0 px-2 fs-14" id="btnZoomReset" title="Restaurar Zoom"><i class="ri-refresh-line"></i></button>
                    </div>

                    <!-- Botones de Acción Global -->
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-primary btn-sm px-3 py-1.5 fs-13" id="btnSaveTemplate">
                            <i class="ri-save-3-line align-middle me-1"></i> Guardar Plantilla
                        </button>
                        <button class="btn btn-success btn-sm px-3 py-1.5 fs-13" id="btnGeneratePreview">
                            <i class="ri-printer-line align-middle me-1"></i> Probar e Imprimir
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="designer-container">
                        <!-- Canvas Workspace -->
                        <div class="flex-grow-1 position-relative d-flex bg-dark overflow-auto p-4" style="background-color: #13151a !important;">
                            <div id="designer-canvas-wrapper">
                                <!-- Background Image (rendered from PDF) -->
                                 <img id="canvas-bg-img" class="position-absolute top-0 start-0 unselectable" style="pointer-events: none; display: none; width: 100% !important; height: 100% !important; max-width: none !important; max-height: none !important; object-fit: fill;">
                                
                                <!-- Container of interactive fields -->
                                <div id="canvas-fields-container" class="position-absolute top-0 start-0 w-100 h-100"></div>
                            </div>
                        </div>

                        <!-- Barra Lateral Derecha - GrapeJS Style -->
                        <div id="property-inspector" class="designer-sidebar-right">
                            <!-- Cabecera de Pestañas -->
                            <div class="designer-sidebar-header">
                                <ul class="nav nav-pills nav-justified" id="designer-sidebar-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active py-2 px-1 fs-11" id="elements-tab" data-bs-toggle="pill" data-bs-target="#elements-pane" type="button" role="tab" aria-selected="true">
                                            <i class="ri-add-box-line d-block fs-14 mb-0.5"></i> Elementos
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link py-2 px-1 fs-11" id="properties-tab" data-bs-toggle="pill" data-bs-target="#properties-pane" type="button" role="tab" aria-selected="false">
                                            <i class="ri-settings-4-line d-block fs-14 mb-0.5"></i> Propiedades
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link py-2 px-1 fs-11" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings-pane" type="button" role="tab" aria-selected="false">
                                            <i class="ri-equalizer-line d-block fs-14 mb-0.5"></i> Ajustes
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Cuerpo de Pestañas -->
                            <div class="designer-sidebar-body tab-content">
                                <!-- PESTAÑA 1: ELEMENTOS -->
                                <div class="tab-pane fade show active" id="elements-pane" role="tabpanel">
                                    <p class="text-muted fs-11 mb-3">Haz clic en un componente para agregarlo al lienzo:</p>
                                    
                                    <div class="accordion designer-accordion" id="accordionElements">
                                        <!-- Datos de Producto -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProduct" aria-expanded="true">
                                                    📦 Datos del Producto
                                                </button>
                                            </h2>
                                            <div id="collapseProduct" class="accordion-collapse collapse show" data-bs-parent="#accordionElements">
                                                <div class="accordion-body">
                                                    <div class="elements-grid">
                                                        <div class="element-btn" data-variable="nombre_producto">
                                                            <i class="ri-box-3-line"></i>
                                                            <span>Producto</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="codigo_sku">
                                                            <i class="ri-hashtag"></i>
                                                            <span>SKU</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="categoria_producto">
                                                            <i class="ri-folder-open-line"></i>
                                                            <span>Categoría</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="marca_producto">
                                                            <i class="ri-bookmark-3-line"></i>
                                                            <span>Marca</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="stock_disponible">
                                                            <i class="ri-line-chart-line"></i>
                                                            <span>Stock</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="ancho">
                                                            <i class="ri-ruler-line"></i>
                                                            <span>Ancho</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="alto">
                                                            <i class="ri-ruler-2-line"></i>
                                                            <span>Alto</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="profundidad">
                                                            <i class="ri-shape-line"></i>
                                                            <span>Fondo</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="peso">
                                                            <i class="ri-scales-line"></i>
                                                            <span>Peso</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Precios -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrices">
                                                    💰 Precios y Finanzas
                                                </button>
                                            </h2>
                                            <div id="collapsePrices" class="accordion-collapse collapse" data-bs-parent="#accordionElements">
                                                <div class="accordion-body">
                                                    <div class="elements-grid">
                                                        <div class="element-btn" data-variable="precio_venta">
                                                            <i class="ri-coins-line"></i>
                                                            <span>Precio Venta</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="precio_lista">
                                                            <i class="ri-price-tag-3-line"></i>
                                                            <span>Precio Lista</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="precio_rebajado">
                                                            <i class="ri-price-tag-2-line"></i>
                                                            <span>P. Rebajado</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="precio_outlet">
                                                            <i class="ri-percent-line"></i>
                                                            <span>P. Outlet</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="mensualidad_3">
                                                            <i class="ri-bank-card-line"></i>
                                                            <span>3 Meses</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="mensualidad_6">
                                                            <i class="ri-bank-card-line"></i>
                                                            <span>6 Meses</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="mensualidad_9">
                                                            <i class="ri-bank-card-line"></i>
                                                            <span>9 Meses</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="mensualidad_12">
                                                            <i class="ri-bank-card-line"></i>
                                                            <span>12 Meses</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Códigos -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCodes">
                                                    📊 Códigos de Barras / QR
                                                </button>
                                            </h2>
                                            <div id="collapseCodes" class="accordion-collapse collapse" data-bs-parent="#accordionElements">
                                                <div class="accordion-body">
                                                    <div class="elements-grid">
                                                        <div class="element-btn" data-variable="codigo_barras" data-type="barcodes.code128">
                                                            <i class="ri-barcode-line"></i>
                                                            <span>Cód. Barras</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="codigo_barras" data-type="qrcode">
                                                            <i class="ri-qr-code-line"></i>
                                                            <span>Cód. QR</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fijos -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatic">
                                                    🎨 Elementos Estáticos
                                                </button>
                                            </h2>
                                            <div id="collapseStatic" class="accordion-collapse collapse" data-bs-parent="#accordionElements">
                                                <div class="accordion-body">
                                                    <div class="elements-grid">
                                                        <div class="element-btn" data-variable="texto_estatico" data-type="text">
                                                            <i class="ri-edit-line"></i>
                                                            <span>Texto Fijo</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="imagen_estatica" data-type="image">
                                                            <i class="ri-image-2-line"></i>
                                                            <span>Imagen / Logo</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="fecha_impresion" data-type="text">
                                                            <i class="ri-calendar-line"></i>
                                                            <span>Fecha Gener.</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Datos de Venta/Cliente -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSales">
                                                    🧾 Datos de Venta / Cliente
                                                </button>
                                            </h2>
                                            <div id="collapseSales" class="accordion-collapse collapse" data-bs-parent="#accordionElements">
                                                <div class="accordion-body">
                                                    <div class="elements-grid">
                                                        <div class="element-btn" data-variable="folio" data-type="text">
                                                            <i class="ri-file-text-line"></i>
                                                            <span>Folio</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="id_transaccion" data-type="text">
                                                            <i class="ri-hashtag"></i>
                                                            <span>ID Transac.</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="fecha_hora" data-type="text">
                                                            <i class="ri-time-line"></i>
                                                            <span>Fecha/Hora</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="nombre_cliente" data-type="text">
                                                            <i class="ri-user-line"></i>
                                                            <span>Cliente</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="telefono_cliente" data-type="text">
                                                            <i class="ri-phone-line"></i>
                                                            <span>Teléfono</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="direccion_cliente" data-type="text">
                                                            <i class="ri-map-pin-line"></i>
                                                            <span>Dirección</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="email_cliente" data-type="text">
                                                            <i class="ri-mail-line"></i>
                                                            <span>Email</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="subtotal" data-type="text">
                                                            <i class="ri-money-dollar-circle-line"></i>
                                                            <span>Subtotal</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="impuestos" data-type="text">
                                                            <i class="ri-percent-line"></i>
                                                            <span>Impuestos</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="descuento_total" data-type="text">
                                                            <i class="ri-discount-percent-line"></i>
                                                            <span>Descuento</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="total" data-type="text">
                                                            <i class="ri-money-dollar-box-fill"></i>
                                                            <span>Total Pagar</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="metodos_pago" data-type="text">
                                                            <i class="ri-bank-card-line"></i>
                                                            <span>Método Pago</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="observaciones" data-type="text">
                                                            <i class="ri-chat-1-line"></i>
                                                            <span>Notas / Obs.</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Datos de Entrega / Despacho -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDelivery">
                                                    🚚 Datos de Entrega / Despacho
                                                </button>
                                            </h2>
                                            <div id="collapseDelivery" class="accordion-collapse collapse" data-bs-parent="#accordionElements">
                                                <div class="accordion-body">
                                                    <div class="elements-grid">
                                                        <div class="element-btn" data-variable="nombre_receptor" data-type="text">
                                                            <i class="ri-user-received-line"></i>
                                                            <span>Receptor</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="telefono_receptor" data-type="text">
                                                            <i class="ri-phone-line"></i>
                                                            <span>Tel. Receptor</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="direccion_entrega" data-type="text">
                                                            <i class="ri-map-pin-line"></i>
                                                            <span>Dir. Entrega</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="referencias_entrega" data-type="text">
                                                            <i class="ri-compass-3-line"></i>
                                                            <span>Referencias</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="fecha_entrega" data-type="text">
                                                            <i class="ri-calendar-event-line"></i>
                                                            <span>Fecha Entrega</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="turno_entrega" data-type="text">
                                                            <i class="ri-time-line"></i>
                                                            <span>Turno / Horario</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="instrucciones_entrega" data-type="text">
                                                            <i class="ri-information-line"></i>
                                                            <span>Instrucciones</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="costo_flete" data-type="text">
                                                            <i class="ri-truck-line"></i>
                                                            <span>Costo Flete</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="costo_armado" data-type="text">
                                                            <i class="ri-tools-line"></i>
                                                            <span>Costo Armado</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="saldo_contra_entrega" data-type="text">
                                                            <i class="ri-money-dollar-box-line"></i>
                                                            <span>Saldo Entrega</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="estatus_pago" data-type="text">
                                                            <i class="ri-shield-check-line"></i>
                                                            <span>Estatus Pago</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="codigo_qr_mapa" data-type="qrcode">
                                                            <i class="ri-qr-code-line"></i>
                                                            <span>QR Ubicación</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="firma_almacen" data-type="text">
                                                            <i class="ri-quill-pen-line"></i>
                                                            <span>Firma Almacén</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="firma_chofer" data-type="text">
                                                            <i class="ri-quill-pen-line"></i>
                                                            <span>Firma Chofer</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="firma_cliente" data-type="text">
                                                            <i class="ri-quill-pen-line"></i>
                                                            <span>Firma Cliente</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recibos, Cobranza y Sucursal -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReceipts">
                                                    💰 Recibos, Abonos y Sucursal
                                                </button>
                                            </h2>
                                            <div id="collapseReceipts" class="accordion-collapse collapse" data-bs-parent="#accordionElements">
                                                <div class="accordion-body">
                                                    <div class="elements-grid">
                                                        <div class="element-btn" data-variable="monto_abono" data-type="text">
                                                            <i class="ri-hand-coin-line"></i>
                                                            <span>Monto Abono</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="saldo_anterior" data-type="text">
                                                            <i class="ri-history-line"></i>
                                                            <span>Saldo Anterior</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="saldo_pendiente" data-type="text">
                                                            <i class="ri-refund-2-line"></i>
                                                            <span>Saldo Restante</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="folio_venta_origen" data-type="text">
                                                            <i class="ri-file-search-line"></i>
                                                            <span>Folio Ref.</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="cajero_nombre" data-type="text">
                                                            <i class="ri-user-star-line"></i>
                                                            <span>Cajero / Asesor</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="sucursal_nombre" data-type="text">
                                                            <i class="ri-store-2-line"></i>
                                                            <span>Sucursal</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="sucursal_telefono" data-type="text">
                                                            <i class="ri-phone-line"></i>
                                                            <span>Tel. Sucursal</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="sucursal_direccion" data-type="text">
                                                            <i class="ri-map-pin-2-line"></i>
                                                            <span>Dir. Sucursal</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="total_iva" data-type="text">
                                                            <i class="ri-percent-line"></i>
                                                            <span>IVA</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="total_ieps" data-type="text">
                                                            <i class="ri-percent-line"></i>
                                                            <span>IEPS</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Componentes Dinámicos -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDynamic">
                                                    📄 Componentes Multi-hoja
                                                </button>
                                            </h2>
                                            <div id="collapseDynamic" class="accordion-collapse collapse" data-bs-parent="#accordionElements">
                                                <div class="accordion-body">
                                                    <div class="elements-grid">
                                                        <div class="element-btn" data-variable="tabla_articulos" data-type="table">
                                                            <i class="ri-table-2"></i>
                                                            <span>Tabla Artículos</span>
                                                        </div>
                                                        <div class="element-btn" data-variable="numero_hoja" data-type="text">
                                                            <i class="ri-pages-line"></i>
                                                            <span>Paginación</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PESTAÑA 2: PROPIEDADES (INSPECTOR) -->
                                <div class="tab-pane fade" id="properties-pane" role="tabpanel">
                                    <!-- Empty State -->
                                    <div id="inspector-empty" class="inspector-empty-state">
                                        <i class="ri-focus-3-line d-block mb-3"></i>
                                        <span>Selecciona un elemento en el lienzo para ajustar sus propiedades.</span>
                                    </div>

                                    <!-- Editor Form -->
                                    <div id="inspector-form" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label text-primary">Variable de Datos</label>
                                            <select id="field-variable-select" class="form-select designer-dark-select border-0"></select>
                                        </div>

                                        <div class="mb-3" id="content-group" style="display: none;">
                                            <label class="form-label text-success">Texto Estático / Fijo</label>
                                            <input type="text" id="field-content" class="form-control designer-dark-input border-0" placeholder="Ej. Garantía, Oferta, etc.">
                                        </div>

                                        <div class="mb-3" id="image-upload-group" style="display: none;">
                                            <label class="form-label text-info">Subir Imagen Fija / Logo</label>
                                            <input type="file" id="field-image-file" class="form-control designer-dark-input border-0" accept="image/*">
                                            <small class="text-muted fs-11">Se guardará de forma fija dentro de la plantilla.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tipo de Visualización</label>
                                            <select id="field-type-select" class="form-select designer-dark-select border-0">
                                                <option value="text">🔤 Texto Plano</option>
                                                <option value="barcodes.code128">📊 Código de Barras (Code 128)</option>
                                                <option value="qrcode">📱 Código QR</option>
                                                <option value="image">🖼️ Imagen Estática</option>
                                                <option value="table">📋 Tabla Dinámica</option>
                                            </select>
                                        </div>

                                        <div class="mb-3" id="columns-widths-group" style="display: none;">
                                            <label class="form-label text-warning">Ancho de Columnas (%)</label>
                                            <input type="text" id="field-columns-widths" class="form-control designer-dark-input border-0" placeholder="Ej: 15,40,15,10,20">
                                            <small class="text-muted fs-11">Separa los anchos por comas. Deben sumar 100.</small>
                                        </div>

                                        <div class="mb-3" id="font-size-group">
                                            <label class="form-label">Tamaño de Letra (pt)</label>
                                            <input type="number" id="field-font-size" class="form-control designer-dark-input border-0" min="6" max="72" value="12">
                                        </div>

                                        <div class="mb-3" id="font-color-group">
                                            <label class="form-label">Color de Letra</label>
                                            <input type="color" id="field-font-color" class="form-control form-control-color designer-dark-input border-0 w-100 designer-color-input" value="#000000">
                                        </div>

                                        <div class="mb-3" id="alignment-group">
                                            <label class="form-label">Alineación Horizontal</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="alignment" id="align-left" value="left">
                                                <label class="btn btn-outline-secondary btn-sm border-secondary-subtle text-white" for="align-left"><i class="ri-align-left"></i> Izq</label>

                                                <input type="radio" class="btn-check" name="alignment" id="align-center" value="center">
                                                <label class="btn btn-outline-secondary btn-sm border-secondary-subtle text-white" for="align-center"><i class="ri-align-center"></i> Cen</label>

                                                <input type="radio" class="btn-check" name="alignment" id="align-right" value="right">
                                                <label class="btn btn-outline-secondary btn-sm border-secondary-subtle text-white" for="align-right"><i class="ri-align-right"></i> Der</label>
                                            </div>
                                        </div>

                                        <div class="mb-3" id="vertical-alignment-group">
                                            <label class="form-label">Alineación Vertical</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="valignment" id="valign-top" value="top">
                                                <label class="btn btn-outline-secondary btn-sm border-secondary-subtle text-white" for="valign-top"><i class="ri-align-top"></i> Sup</label>

                                                <input type="radio" class="btn-check" name="valignment" id="valign-center" value="center" checked>
                                                <label class="btn btn-outline-secondary btn-sm border-secondary-subtle text-white" for="valign-center"><i class="ri-align-vertically"></i> Cen</label>

                                                <input type="radio" class="btn-check" name="valignment" id="valign-bottom" value="bottom">
                                                <label class="btn btn-outline-secondary btn-sm border-secondary-subtle text-white" for="valign-bottom"><i class="ri-align-bottom"></i> Inf</label>
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-4">
                                            <div class="col-6">
                                                <label class="form-label text-muted">Ancho (mm)</label>
                                                <input type="number" step="0.1" id="field-info-w" class="form-control form-control-sm designer-dark-input border-0">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-muted">Alto (mm)</label>
                                                <input type="number" step="0.1" id="field-info-h" class="form-control form-control-sm designer-dark-input border-0">
                                            </div>
                                        </div>

                                        <button id="btn-delete-field" class="btn btn-danger w-100 py-2 fs-13">
                                            <i class="ri-delete-bin-line align-middle me-1"></i> Eliminar Campo
                                        </button>
                                    </div>
                                </div>

                                <!-- PESTAÑA 3: CONFIGURACIÓN GENERAL (AJUSTES) -->
                                <div class="tab-pane fade" id="settings-pane" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="templateName" class="form-label text-white">Nombre de la Plantilla</label>
                                        <input type="text" class="form-control designer-dark-input border-0" id="templateName" placeholder="Ej. Etiqueta Producto Sucursal" value="">
                                    </div>

                                    <div class="mb-3">
                                        <label for="documentType" class="form-label text-white">Tipo de Documento</label>
                                        <select class="form-select designer-dark-select border-0" id="documentType">
                                            <option value="producto" selected>📦 PDF Producto (Etiquetas/Precios)</option>
                                            <option value="orden_entrega">🚚 PDF Orden de Entrega</option>
                                            <option value="orden_venta">💳 PDF Orden de Venta</option>
                                            <option value="abono">💰 PDF Abono</option>
                                            <option value="anticipo">📈 PDF Anticipo</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="basePdfFile" class="form-label text-white">Subir PDF Base (Plantilla)</label>
                                        <input class="form-control designer-dark-input border-0" type="file" id="basePdfFile" accept="application/pdf">
                                        <small class="text-muted fs-11">Carga un PDF base. El sistema lo convertirá automáticamente a imagen de fondo.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="paperPreset" class="form-label text-white">Formato de Papel Rápido</label>
                                        <select class="form-select designer-dark-select border-0" id="paperPreset">
                                            <option value="custom">⚙️ Personalizado (manual)</option>
                                            <option value="carta">📄 Carta Vertical (215.9 x 279.4 mm)</option>
                                            <option value="carta_landscape">📄 Carta Horizontal (279.4 x 215.9 mm)</option>
                                            <option value="a4" selected>📄 A4 Vertical (210 x 297 mm)</option>
                                            <option value="a4_landscape">📄 A4 Horizontal (297 x 210 mm)</option>
                                            <option value="media_carta">📄 Media Carta Vertical (139.7 x 215.9 mm)</option>
                                            <option value="media_carta_landscape">📄 Media Carta Horizontal (215.9 x 139.7 mm)</option>
                                            <option value="ticket_80">🧾 Ticket Térmico 80mm (80 x 200 mm)</option>
                                            <option value="ticket_58">🧾 Ticket Térmico 58mm (58 x 150 mm)</option>
                                            <option value="termica_4x6">🏷️ Etiqueta 4x6" (101.6 x 152.4 mm)</option>
                                            <option value="brother_62x100">🏷️ Brother 62x100mm (62 x 100 mm)</option>
                                        </select>
                                    </div>

                                    <div class="row g-2 mb-4">
                                        <div class="col-6">
                                            <label for="templateWidth" class="form-label fs-10 text-white">Ancho (mm)</label>
                                            <input type="number" step="0.1" class="form-control designer-dark-input border-0" id="templateWidth" placeholder="Ej. 100">
                                        </div>
                                        <div class="col-6">
                                            <label for="templateHeight" class="form-label fs-10 text-white">Alto (mm)</label>
                                            <input type="number" step="0.1" class="form-control designer-dark-input border-0" id="templateHeight" placeholder="Ej. 50">
                                        </div>
                                    </div>

                                    <div class="p-3 mb-3 bg-black bg-opacity-25 rounded text-center">
                                        <p class="mb-1 text-muted fs-11">Sucursal Activa para Pruebas</p>
                                        <span class="badge bg-success fs-12 py-1.5 px-3">
                                            <i class="ri-store-2-line me-1"></i> {{ current_store() ? current_store()->name : 'Vista Global (Primera Sucursal)' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- SweetAlert2 -->
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    
    <!-- Librerías de Apoyo: pdf.js e interact.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

    <script>
        // Configurar pdf.js worker
        window['pdfjs-dist/build/pdf'].GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

        document.addEventListener('DOMContentLoaded', function () {
            const allowedVariables = [
                // Catálogo y Producto
                { value: 'nombre_producto', label: '📦 Nombre del Producto' },
                { value: 'precio_venta', label: '💰 Precio de Venta' },
                { value: 'precio_lista', label: '🏷️ Precio de Lista' },
                { value: 'precio_rebajado', label: '🏷️ Precio Rebajado' },
                { value: 'precio_outlet', label: '🏷️ Precio Outlet' },
                { value: 'mensualidad_3', label: '💳 Mensualidad 3 meses' },
                { value: 'mensualidad_6', label: '💳 Mensualidad 6 meses' },
                { value: 'mensualidad_9', label: '💳 Mensualidad 9 meses' },
                { value: 'mensualidad_12', label: '💳 Mensualidad 12 meses' },
                { value: 'codigo_sku', label: '🔢 Código SKU' },
                { value: 'codigo_barras', label: '📊 Código de Barras' },
                { value: 'stock_disponible', label: '📉 Stock Actual' },
                { value: 'fecha_impresion', label: '📅 Fecha de Generación' },
                { value: 'ancho', label: '📏 Ancho (cm)' },
                { value: 'alto', label: '📏 Alto (cm)' },
                { value: 'profundidad', label: '📏 Profundidad (cm)' },
                { value: 'peso', label: '⚖️ Peso (kg)' },
                { value: 'categoria_producto', label: '📂 Categoría' },
                { value: 'marca_producto', label: '🏷️ Marca' },
                { value: 'texto_estatico', label: '✏️ Texto Estático / Fijo' },
                { value: 'imagen_estatica', label: '🖼️ Imagen Fija / Logo' },

                // Transaccionales y Venta (POS)
                { value: 'folio', label: '📄 Folio (Ticket/Venta)' },
                { value: 'id_transaccion', label: '🆔 ID Transacción' },
                { value: 'fecha_hora', label: '⌚ Fecha con hora' },
                { value: 'nombre_cliente', label: '👤 Nombre Cliente' },
                { value: 'direccion_cliente', label: '📍 Dirección Cliente' },
                { value: 'telefono_cliente', label: '📞 Teléfono Cliente' },
                { value: 'email_cliente', label: '✉️ Email Cliente' },
                { value: 'subtotal', label: '💵 Subtotal' },
                { value: 'impuestos', label: '📊 Impuestos' },
                { value: 'total_iva', label: '📊 IVA (16%)' },
                { value: 'total_ieps', label: '📊 IEPS' },
                { value: 'descuento_total', label: '📉 Descuento Total' },
                { value: 'total', label: '💰 Total a Pagar' },
                { value: 'metodos_pago', label: '💳 Métodos de Pago' },
                { value: 'observaciones', label: '📝 Observaciones' },
                { value: 'cajero_nombre', label: '🧑‍💼 Cajero / Vendedor' },
                { value: 'sucursal_nombre', label: '🏢 Sucursal' },
                { value: 'sucursal_telefono', label: '📞 Teléfono Sucursal' },
                { value: 'sucursal_direccion', label: '📍 Dirección Sucursal' },

                // Logística y Entrega
                { value: 'nombre_receptor', label: '👤 Nombre del Receptor' },
                { value: 'telefono_receptor', label: '📞 Teléfono Receptor' },
                { value: 'direccion_entrega', label: '📍 Dirección Entrega' },
                { value: 'referencias_entrega', label: '🧭 Referencias Entrega' },
                { value: 'fecha_entrega', label: '📅 Fecha Entrega' },
                { value: 'turno_entrega', label: '⏰ Horario / Turno Entrega' },
                { value: 'instrucciones_entrega', label: 'ℹ️ Instrucciones Entrega' },
                { value: 'costo_flete', label: '🚛 Costo Flete' },
                { value: 'costo_armado', label: '🔧 Costo Armado' },
                { value: 'saldo_contra_entrega', label: '💵 Saldo Contra Entrega' },
                { value: 'estatus_pago', label: '💳 Estatus de Pago' },
                { value: 'codigo_qr_mapa', label: '🗺️ QR Ubicación Google Maps' },
                { value: 'firma_almacen', label: '✍️ Firma Almacén' },
                { value: 'firma_chofer', label: '✍️ Firma Chofer' },
                { value: 'firma_cliente', label: '✍️ Firma Cliente' },

                // Cobranza y Abonos
                { value: 'monto_abono', label: '💵 Monto Abono' },
                { value: 'saldo_anterior', label: '📉 Saldo Anterior' },
                { value: 'saldo_pendiente', label: '💰 Saldo Pendiente' },
                { value: 'folio_venta_origen', label: '📄 Folio Pedido / Venta' },

                // Multi-hoja y Tablas
                { value: 'tabla_articulos', label: '📋 Tabla de Artículos' },
                { value: 'numero_hoja', label: '📃 Número de Hoja (Actual)' },
                { value: 'total_hojas', label: '📑 Total Hojas' }
            ];

            // Registrar los clicks de los botones de elementos clasificados
            document.querySelectorAll('.element-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const variableKey = this.getAttribute('data-variable');
                    const forceType = this.getAttribute('data-type');
                    addField(variableKey, forceType);
                    
                    // Activar el tab de Propiedades automáticamente
                    const propertiesTabTrigger = document.getElementById('properties-tab');
                    if (propertiesTabTrigger) {
                        const tabInstance = bootstrap.Tab.getOrCreateInstance(propertiesTabTrigger);
                        tabInstance.show();
                    }
                });
            });

            // Llenar el selector del Inspector de Propiedades
            const varSelect = document.getElementById('field-variable-select');
            allowedVariables.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.value;
                opt.textContent = v.label;
                varSelect.appendChild(opt);
            });

            // Variables de Estado del Canvas
            let pageWidthMm = 210; // A4 por defecto
            let pageHeightMm = 297;
            let workspaceScaleX = 1; // mm por píxel
            let workspaceScaleY = 1;
            let selectedFieldEl = null;
            let currentPdfWidth = 595; // Dimensiones base detectadas del fondo original
            let currentPdfHeight = 841;
            let zoomLevel = 1.0; // Nivel de zoom de trabajo

            let currentTemplate = {
                basePdf: '', // Contendrá la imagen base64 PNG del fondo
                schemas: [{}]
            };

            // Generar una imagen en blanco por defecto (A4)
            function generateBlankBackground() {
                const canvas = document.createElement('canvas');
                canvas.width = 595;
                canvas.height = 841;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                return canvas.toDataURL('image/png');
            }

            // Establecer dimensiones del lienzo adaptativas conservando la relación de aspecto de la plantilla física (evita deformación de campos)
            function setWorkspaceSize(pdfWidth, pdfHeight) {
                if (pdfWidth) currentPdfWidth = pdfWidth;
                if (pdfHeight) currentPdfHeight = pdfHeight;

                const container = document.querySelector('.designer-container');
                const maxW = container.clientWidth - 410; // Dejar espacio al inspector (380px + margen)
                const maxH = container.clientHeight - 60;  // Margen interno
                
                // El ratio del lienzo en pantalla debe ser exactamente el ratio físico (pageWidthMm / pageHeightMm)
                // para evitar deformaciones en los campos interactivos.
                const ratio = pageWidthMm / pageHeightMm;
                let fitWidth, fitHeight;

                if (ratio > maxW / maxH) {
                    fitWidth = maxW;
                    fitHeight = fitWidth / ratio;
                } else {
                    fitHeight = maxH;
                    fitWidth = fitHeight * ratio;
                }

                // Aplicar nivel de zoom
                const finalWidth = fitWidth * zoomLevel;
                const finalHeight = fitHeight * zoomLevel;

                const wrapper = document.getElementById('designer-canvas-wrapper');
                wrapper.style.width = `${finalWidth}px`;
                wrapper.style.height = `${finalHeight}px`;

                workspaceScaleX = pageWidthMm / finalWidth;
                workspaceScaleY = pageHeightMm / finalHeight;

                // Sincronizar inputs de dimensiones en el panel lateral
                const inputW = document.getElementById('templateWidth');
                const inputH = document.getElementById('templateHeight');
                if (inputW && document.activeElement !== inputW) inputW.value = pageWidthMm;
                if (inputH && document.activeElement !== inputH) inputH.value = pageHeightMm;
                if (typeof syncPaperPresetDropdown === 'function') syncPaperPresetDropdown(pageWidthMm, pageHeightMm);

                // Redibujar campos existentes con la nueva escala
                updateFieldsLayout();
            }

            // Convertir PDF a Imagen PNG en el navegador usando pdf.js
            async function loadTemplateBackground(basePdf, savedWidth, savedHeight) {
                if (!basePdf) {
                    const bgImg = document.getElementById('canvas-bg-img');
                    if (bgImg) {
                        bgImg.src = '';
                        bgImg.style.display = 'none';
                    }
                    if (savedWidth && savedHeight) {
                        pageWidthMm = parseFloat(savedWidth);
                        pageHeightMm = parseFloat(savedHeight);
                    } else {
                        pageWidthMm = 210;
                        pageHeightMm = 297;
                    }
                    setWorkspaceSize(595, 841);
                    return;
                }

                // Si es un objeto File, lo convertimos a DataURL/base64 primero
                if (basePdf instanceof File) {
                    return new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = async function (e) {
                            try {
                                await loadTemplateBackground(e.target.result, savedWidth, savedHeight);
                                resolve();
                            } catch (err) {
                                reject(err);
                            }
                        };
                        reader.onerror = reject;
                        reader.readAsDataURL(basePdf);
                    });
                }

                if (basePdf.startsWith('data:image/') || basePdf.startsWith('data:application/png') || basePdf.startsWith('data:application/jpg')) {
                    // Es una imagen directa
                    return new Promise((resolve) => {
                        const img = new Image();
                        img.onload = function () {
                            const ratio = img.width / img.height;
                            if (savedWidth && savedHeight) {
                                pageWidthMm = parseFloat(savedWidth);
                                pageHeightMm = parseFloat(savedHeight);
                            } else if (ratio > 1.2) {
                                pageWidthMm = 100; // Etiqueta genérica
                                pageHeightMm = parseFloat((100 / ratio).toFixed(2));
                            } else {
                                pageWidthMm = 210; // A4
                                pageHeightMm = 297;
                            }
                            setWorkspaceSize(img.width, img.height);
                            const bgImg = document.getElementById('canvas-bg-img');
                            bgImg.src = basePdf;
                            bgImg.style.display = 'block';
                            resolve();
                        };
                        img.src = basePdf;
                    });
                } else {
                    // Es un PDF (en base64 o URL)
                    try {
                        let pdfData;
                        if (basePdf.startsWith('data:application/pdf;base64,')) {
                            pdfData = atob(basePdf.split(',')[1]);
                        } else {
                            // Fetch del PDF
                            const res = await fetch(basePdf);
                            const ab = await res.arrayBuffer();
                            pdfData = new Uint8Array(ab).reduce((data, byte) => data + String.fromCharCode(byte), '');
                        }

                        const pdfDataArray = new Uint8Array(pdfData.length);
                        for (let i = 0; i < pdfData.length; i++) {
                            pdfDataArray[i] = pdfData.charCodeAt(i);
                        }

                        const loadingTask = window['pdfjs-dist/build/pdf'].getDocument({ data: pdfDataArray });
                        const pdf = await loadingTask.promise;
                        const page = await pdf.getPage(1);
                        
                        const viewport = page.getViewport({ scale: 2.0 }); // Alta resolución
                        const view = page.view;
                        
                        if (savedWidth && savedHeight) {
                            pageWidthMm = parseFloat(savedWidth);
                            pageHeightMm = parseFloat(savedHeight);
                        } else {
                            pageWidthMm = parseFloat(((view[2] - view[0]) * 25.4 / 72).toFixed(2));
                            pageHeightMm = parseFloat(((view[3] - view[1]) * 25.4 / 72).toFixed(2));
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        const ctx = canvas.getContext('2d');
                        
                        await page.render({ canvasContext: ctx, viewport: viewport }).promise;
                        const base64Png = canvas.toDataURL('image/png');

                        setWorkspaceSize(viewport.width, viewport.height);
                        
                        const bgImg = document.getElementById('canvas-bg-img');
                        bgImg.src = base64Png;
                        bgImg.style.display = 'block';

                        // Guardar la imagen convertida como fondo de la plantilla actual
                        currentTemplate.basePdf = base64Png;

                    } catch (err) {
                        console.error('Error rendering PDF background:', err);
                        Swal.fire('Error', 'No se pudo procesar el PDF base. Asegúrate de que sea un archivo válido.', 'error');
                    }
                }
            }

            // Inicializar el Diseñador con una Plantilla
            async function initDesigner(template) {
                currentTemplate = template;
                deselectAllFields();
                await loadTemplateBackground(template.basePdf, template.width, template.height);
                loadFieldsFromSchema(template.schemas[0]);
            }

            // Cargar y posicionar los campos del esquema
            function loadFieldsFromSchema(schema) {
                const container = document.getElementById('canvas-fields-container');
                container.innerHTML = ''; // Limpiar campos viejos

                if (!schema) return;

                Object.entries(schema).forEach(([key, field]) => {
                    if (!field) return;

                    const baseVariable = key.replace(/_(?!(3|6|9|12)$)\d+$/, '');

                    const fieldEl = document.createElement('div');
                    fieldEl.className = 'draggable-field';
                    fieldEl.setAttribute('data-id', key);
                    fieldEl.setAttribute('data-variable', baseVariable);
                    fieldEl.setAttribute('data-type', field.type || 'text');
                    fieldEl.setAttribute('data-font-size', field.fontSize || 12);
                    fieldEl.setAttribute('data-font-color', field.fontColor || '#000000');
                    fieldEl.setAttribute('data-alignment', field.alignment || 'left');
                    fieldEl.setAttribute('data-valignment', field.valignment || 'center');
                    fieldEl.setAttribute('data-content', field.content || '');
                    if (field.columnsWidths) {
                        fieldEl.setAttribute('data-columns-widths', field.columnsWidths);
                    }

                    fieldEl.innerHTML = `
                        <div class="field-title"></div>
                        <div class="field-preview"></div>
                    `;

                    // Almacenar valores de milímetros directamente en atributos
                    fieldEl.setAttribute('data-mm-x', field.position.x);
                    fieldEl.setAttribute('data-mm-y', field.position.y);
                    fieldEl.setAttribute('data-mm-w', field.width);
                    fieldEl.setAttribute('data-mm-h', field.height);

                    container.appendChild(fieldEl);

                    // Posicionar según la escala de píxeles actual
                    positionFieldElement(fieldEl);
                    updateFieldPreview(fieldEl, field.type || 'text', baseVariable);
                    applyFieldStyles(fieldEl);

                    fieldEl.addEventListener('click', function (e) {
                        e.stopPropagation();
                        selectField(fieldEl);
                    });
                });
            }

            // Convierte coordenadas mm a píxeles y posiciona el elemento
            function positionFieldElement(fieldEl) {
                const mmX = parseFloat(fieldEl.getAttribute('data-mm-x')) || 0;
                const mmY = parseFloat(fieldEl.getAttribute('data-mm-y')) || 0;
                const mmW = parseFloat(fieldEl.getAttribute('data-mm-w')) || 20;
                const mmH = parseFloat(fieldEl.getAttribute('data-mm-h')) || 8;

                const pxX = mmX / workspaceScaleX;
                const pxY = mmY / workspaceScaleY;
                const pxW = mmW / workspaceScaleX;
                const pxH = mmH / workspaceScaleY;

                fieldEl.style.width = `${pxW}px`;
                fieldEl.style.height = `${pxH}px`;
                fieldEl.style.transform = `translate(${pxX}px, ${pxY}px)`;
                fieldEl.setAttribute('data-x', pxX);
                fieldEl.setAttribute('data-y', pxY);
            }

            // Recalcular píxeles para todos los elementos tras un redimensionado de pantalla/zoom
            function updateFieldsLayout() {
                const fields = document.querySelectorAll('.draggable-field');
                fields.forEach(f => {
                    positionFieldElement(f);
                    applyFieldStyles(f);
                });
            }

            // Actualizar vista previa visual de un campo en el lienzo
            function updateFieldPreview(fieldEl, type, variableKey) {
                const label = allowedVariables.find(v => v.value === variableKey)?.label || variableKey;
                const titleEl = fieldEl.querySelector('.field-title');
                const previewEl = fieldEl.querySelector('.field-preview');

                titleEl.textContent = label;

                if (type === 'barcodes.code128') {
                    previewEl.innerHTML = `<span class="text-muted fs-10"><i class="ri-barcode-line"></i> Código Barras</span>`;
                } else if (type === 'qrcode') {
                    previewEl.innerHTML = `<span class="text-muted fs-10"><i class="ri-qr-code-line"></i> Código QR</span>`;
                } else if (type === 'table') {
                    previewEl.innerHTML = `
                        <div style="font-size: 7.5px; color: #475569; width: 100%; border: 1px dashed #cbd5e1; border-radius: 2px; padding: 2px; background: rgba(241, 245, 249, 0.7); overflow: hidden;">
                            <div style="font-weight: bold; background: #e2e8f0; display: flex; justify-content: space-between; padding: 1px 3px; font-size: 7.5px;">
                                <span>CANT</span><span>DESCRIPCIÓN</span><span>NOTAS / IMPORTE</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 1px 3px; border-top: 1px solid #e2e8f0; font-size: 7px;">
                                <span>1</span><span>[Partida de artículos...]</span><span>OK / $0.00</span>
                            </div>
                        </div>
                    `;
                } else if (type === 'image') {
                    const content = fieldEl.getAttribute('data-content');
                    if (content && (content.startsWith('data:image/') || content.startsWith('http://') || content.startsWith('https://'))) {
                        previewEl.innerHTML = `<img src="${content}" style="width: 100%; height: 100%; object-fit: contain;">`;
                    } else {
                        previewEl.innerHTML = `<span class="text-muted fs-10"><i class="ri-image-line"></i> Imagen Fija</span>`;
                    }
                } else {
                    if (variableKey === 'texto_estatico') {
                        previewEl.textContent = fieldEl.getAttribute('data-content') || '[Texto Estático]';
                    } else if (variableKey.startsWith('firma_')) {
                        const content = fieldEl.getAttribute('data-content');
                        previewEl.innerHTML = `<div style="text-align: center; border-top: 1px solid #475569; margin-top: 8px; padding-top: 2px; font-size: 8px; line-height: 1.1;">${content || label}</div>`;
                    } else {
                        previewEl.textContent = `[${variableKey}]`;
                    }
                }
            }

            // Aplicar estilos tipográficos al elemento del canvas
            function applyFieldStyles(fieldEl) {
                const fontSizePt = parseFloat(fieldEl.getAttribute('data-font-size')) || 12;
                const fontColor = fieldEl.getAttribute('data-font-color') || '#000000';
                const alignment = fieldEl.getAttribute('data-alignment') || 'left';
                const valignment = fieldEl.getAttribute('data-valignment') || 'center';
                const type = fieldEl.getAttribute('data-type') || 'text';
                
                const titleEl = fieldEl.querySelector('.field-title');
                const previewEl = fieldEl.querySelector('.field-preview');
                
                if (type === 'image') {
                    if (titleEl) titleEl.style.display = 'none';
                    if (previewEl) {
                        previewEl.style.width = '100%';
                        previewEl.style.height = '100%';
                        previewEl.style.textAlign = 'center';
                    }
                    fieldEl.style.padding = '0';
                } else {
                    if (titleEl) {
                        titleEl.style.display = 'block';
                        // Escalar el título proporcionalmente al zoom
                        const titleSizeMm = 6.5 * (25.4 / 72);
                        const titleSizePx = titleSizeMm / workspaceScaleY;
                        titleEl.style.fontSize = `${Math.max(5, titleSizePx)}px`;
                    }
                    if (previewEl) {
                        // Calcular el tamaño de fuente en píxeles de pantalla según la escala del lienzo
                        const fontSizeMm = fontSizePt * (25.4 / 72);
                        const fontSizePx = fontSizeMm / workspaceScaleY;
                        
                        previewEl.style.fontSize = `${fontSizePx}px`;
                        previewEl.style.color = fontColor;
                        previewEl.style.textAlign = alignment;
                        previewEl.style.width = '';
                        previewEl.style.height = '';
                    }
                    fieldEl.style.padding = '2px 4px';
                }

                // Ajustar alineación vertical en el canvas usando justify-content
                fieldEl.style.justifyContent = valignment === 'top' ? 'flex-start' : (valignment === 'bottom' ? 'flex-end' : 'center');
            }

            // Añadir un nuevo campo interactivo al lienzo con tamaños inteligentes según tipo
            function addField(variableKey, forceType = null) {
                const container = document.getElementById('canvas-fields-container');
                const fieldId = `field_${Date.now()}`;
                const defaultType = forceType || ((variableKey === 'imagen_estatica') ? 'image' : (variableKey === 'tabla_articulos' ? 'table' : (variableKey === 'codigo_barras' ? 'barcodes.code128' : (variableKey === 'codigo_qr_mapa' ? 'qrcode' : 'text'))));

                let defaultW = 50;
                let defaultH = 10;
                let defaultFontSize = 11;
                let defaultContent = variableKey;

                if (defaultType === 'image') {
                    defaultW = 35;
                    defaultH = 35;
                    defaultContent = '';
                } else if (defaultType === 'table') {
                    defaultW = Math.min(185, Math.max(100, pageWidthMm - 30));
                    defaultH = 60;
                    defaultFontSize = 9;
                } else if (defaultType === 'qrcode') {
                    defaultW = 28;
                    defaultH = 28;
                    defaultContent = variableKey === 'codigo_qr_mapa' ? 'https://maps.google.com' : 'QR_CODE_DATA';
                } else if (defaultType === 'barcodes.code128') {
                    defaultW = 65;
                    defaultH = 18;
                    defaultContent = '123456789012';
                } else if (variableKey.startsWith('firma_')) {
                    defaultW = 55;
                    defaultH = 20;
                    defaultFontSize = 9;
                    if (variableKey === 'firma_almacen') {
                        defaultContent = 'Responsable de Almacén';
                    } else if (variableKey === 'firma_chofer') {
                        defaultContent = 'Chofer / Transportista';
                    } else if (variableKey === 'firma_cliente') {
                        defaultContent = 'Firma de Conformidad Cliente';
                    }
                } else if (variableKey === 'texto_estatico') {
                    defaultContent = 'Texto Fijo';
                }

                const fieldEl = document.createElement('div');
                fieldEl.className = 'draggable-field';
                fieldEl.setAttribute('data-id', fieldId);
                fieldEl.setAttribute('data-variable', variableKey);
                fieldEl.setAttribute('data-type', defaultType);
                fieldEl.setAttribute('data-font-size', defaultFontSize);
                fieldEl.setAttribute('data-font-color', '#000000');
                fieldEl.setAttribute('data-alignment', variableKey.startsWith('firma_') ? 'center' : 'left');
                fieldEl.setAttribute('data-valignment', 'center');
                fieldEl.setAttribute('data-content', defaultContent);
                if (defaultType === 'table') {
                    fieldEl.setAttribute('data-columns-widths', '15,45,20,20');
                }

                fieldEl.innerHTML = `
                    <div class="field-title"></div>
                    <div class="field-preview"></div>
                `;

                // Posicionar por defecto en el centro en milímetros
                const defaultX = Math.max(5, (pageWidthMm - defaultW) / 2);
                const defaultY = Math.max(5, (pageHeightMm - defaultH) / 2);

                fieldEl.setAttribute('data-mm-x', parseFloat(defaultX.toFixed(1)));
                fieldEl.setAttribute('data-mm-y', parseFloat(defaultY.toFixed(1)));
                fieldEl.setAttribute('data-mm-w', parseFloat(defaultW.toFixed(1)));
                fieldEl.setAttribute('data-mm-h', parseFloat(defaultH.toFixed(1)));

                container.appendChild(fieldEl);

                positionFieldElement(fieldEl);
                updateFieldPreview(fieldEl, defaultType, variableKey);
                applyFieldStyles(fieldEl);

                fieldEl.addEventListener('click', function (e) {
                    e.stopPropagation();
                    selectField(fieldEl);
                });

                selectField(fieldEl);
            }

            // Seleccionar y activar un campo
            function selectField(fieldEl) {
                deselectAllFields();
                selectedFieldEl = fieldEl;
                fieldEl.classList.add('active-field');

                // Llenar datos en el Property Inspector
                document.getElementById('inspector-empty').style.display = 'none';
                document.getElementById('inspector-form').style.display = 'block';

                const variable = fieldEl.getAttribute('data-variable');
                document.getElementById('field-variable-select').value = variable;
                document.getElementById('field-type-select').value = fieldEl.getAttribute('data-type');
                document.getElementById('field-font-size').value = fieldEl.getAttribute('data-font-size') || 12;
                document.getElementById('field-font-color').value = fieldEl.getAttribute('data-font-color') || '#000000';
                
                const alignment = fieldEl.getAttribute('data-alignment') || 'left';
                const radioBtn = document.getElementById(`align-${alignment}`);
                if (radioBtn) radioBtn.checked = true;

                const valignment = fieldEl.getAttribute('data-valignment') || 'center';
                const vRadioBtn = document.getElementById(`valign-${valignment}`);
                if (vRadioBtn) vRadioBtn.checked = true;

                // Llenar el contenido estático
                const contentInput = document.getElementById('field-content');
                contentInput.value = (variable === 'texto_estatico' && fieldEl.getAttribute('data-type') !== 'image') ? fieldEl.getAttribute('data-content') : '';

                // Llenar el ancho de columnas
                const columnsInput = document.getElementById('field-columns-widths');
                columnsInput.value = fieldEl.getAttribute('data-columns-widths') || '15,40,15,10,20';

                // Limpiar el selector de archivos
                document.getElementById('field-image-file').value = '';

                // Activar/Ocultar campos dinámicamente según tipo y variable
                toggleInspectorFields();

                updateInspectorDimensions(fieldEl);

                // Activar el tab de Propiedades automáticamente para edición fluida
                const propertiesTabTrigger = document.getElementById('properties-tab');
                if (propertiesTabTrigger) {
                    const tabInstance = bootstrap.Tab.getOrCreateInstance(propertiesTabTrigger);
                    tabInstance.show();
                }
            }

            function toggleInspectorFields() {
                if (!selectedFieldEl) return;
                
                const type = selectedFieldEl.getAttribute('data-type');
                const variable = selectedFieldEl.getAttribute('data-variable');
                
                // Mostrar/Ocultar grupo de texto estático
                const contentGroup = document.getElementById('content-group');
                if (variable === 'texto_estatico' && type !== 'image') {
                    contentGroup.style.display = 'block';
                } else {
                    contentGroup.style.display = 'none';
                }
                
                // Mostrar/Ocultar grupo de subir imagen
                const imageGroup = document.getElementById('image-upload-group');
                if (type === 'image') {
                    imageGroup.style.display = 'block';
                } else {
                    imageGroup.style.display = 'none';
                }

                // Mostrar/Ocultar anchos de columnas
                const columnsGroup = document.getElementById('columns-widths-group');
                if (type === 'table') {
                    columnsGroup.style.display = 'block';
                } else {
                    columnsGroup.style.display = 'none';
                }
                
                // Mostrar/Ocultar estilos de texto
                const fontGroup = document.getElementById('font-size-group');
                const colorGroup = document.getElementById('font-color-group');
                const alignGroup = document.getElementById('alignment-group');
                const valignGroup = document.getElementById('vertical-alignment-group');
                
                if (type === 'text') {
                    fontGroup.style.display = 'block';
                    colorGroup.style.display = 'block';
                    alignGroup.style.display = 'block';
                    valignGroup.style.display = 'block';
                } else {
                    fontGroup.style.display = 'none';
                    colorGroup.style.display = 'none';
                    alignGroup.style.display = 'none';
                    valignGroup.style.display = 'none';
                }
            }

            // Deseleccionar campos
            function deselectAllFields() {
                selectedFieldEl = null;
                document.querySelectorAll('.draggable-field').forEach(f => f.classList.remove('active-field'));
                document.getElementById('inspector-empty').style.display = 'block';
                document.getElementById('inspector-form').style.display = 'none';
            }

            // Deseleccionar al hacer click fuera del lienzo
            document.getElementById('designer-canvas-wrapper').addEventListener('click', function (e) {
                if (e.target === this || e.target === document.getElementById('canvas-bg-img') || e.target === document.getElementById('canvas-fields-container')) {
                    deselectAllFields();
                }
            });

            // Actualizar dimensiones informativas en el Inspector
            function updateInspectorDimensions(fieldEl) {
                const mmW = parseFloat(fieldEl.getAttribute('data-mm-w')) || 0;
                const mmH = parseFloat(fieldEl.getAttribute('data-mm-h')) || 0;
                document.getElementById('field-info-w').value = mmW.toFixed(1);
                document.getElementById('field-info-h').value = mmH.toFixed(1);
            }

            // Event Listeners del Property Inspector
            document.getElementById('field-variable-select').addEventListener('change', function () {
                if (!selectedFieldEl) return;
                const val = this.value;
                selectedFieldEl.setAttribute('data-variable', val);
                
                // Si cambia a imagen_estatica, cambiar también el tipo a image
                if (val === 'imagen_estatica') {
                    selectedFieldEl.setAttribute('data-type', 'image');
                    document.getElementById('field-type-select').value = 'image';
                    selectedFieldEl.setAttribute('data-content', '');
                } else {
                    selectedFieldEl.setAttribute('data-content', val);
                }
                
                updateFieldPreview(selectedFieldEl, selectedFieldEl.getAttribute('data-type'), val);
                applyFieldStyles(selectedFieldEl);
                toggleInspectorFields();
            });

            document.getElementById('field-content').addEventListener('input', function () {
                if (!selectedFieldEl) return;
                const val = this.value;
                selectedFieldEl.setAttribute('data-content', val);
                updateFieldPreview(selectedFieldEl, selectedFieldEl.getAttribute('data-type'), selectedFieldEl.getAttribute('data-variable'));
            });

            document.getElementById('field-columns-widths').addEventListener('input', function () {
                if (!selectedFieldEl) return;
                const val = this.value;
                selectedFieldEl.setAttribute('data-columns-widths', val);
            });

            // Cargar archivo de imagen local y guardar base64
            document.getElementById('field-image-file').addEventListener('change', function (e) {
                if (!selectedFieldEl) return;
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (evt) {
                        const base64 = evt.target.result;
                        selectedFieldEl.setAttribute('data-content', base64);
                        updateFieldPreview(selectedFieldEl, 'image', selectedFieldEl.getAttribute('data-variable'));
                        applyFieldStyles(selectedFieldEl);
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Ancho editable
            document.getElementById('field-info-w').addEventListener('input', function () {
                if (!selectedFieldEl) return;
                const mmW = parseFloat(this.value);
                if (isNaN(mmW) || mmW <= 0) return;

                selectedFieldEl.setAttribute('data-mm-w', mmW);
                const pxW = mmW / workspaceScaleX;
                selectedFieldEl.style.width = `${pxW}px`;
            });

            // Alto editable
            document.getElementById('field-info-h').addEventListener('input', function () {
                if (!selectedFieldEl) return;
                const mmH = parseFloat(this.value);
                if (isNaN(mmH) || mmH <= 0) return;

                selectedFieldEl.setAttribute('data-mm-h', mmH);
                const pxH = mmH / workspaceScaleY;
                selectedFieldEl.style.height = `${pxH}px`;
            });

            document.getElementById('field-type-select').addEventListener('change', function () {
                if (!selectedFieldEl) return;
                const val = this.value;
                selectedFieldEl.setAttribute('data-type', val);
                if (val !== 'image' && selectedFieldEl.getAttribute('data-variable') === 'imagen_estatica') {
                    // Si cambia de imagen a texto, restablecer la variable
                    selectedFieldEl.setAttribute('data-variable', 'texto_estatico');
                    document.getElementById('field-variable-select').value = 'texto_estatico';
                }
                updateFieldPreview(selectedFieldEl, val, selectedFieldEl.getAttribute('data-variable'));
                applyFieldStyles(selectedFieldEl);
                toggleInspectorFields();
            });

            document.getElementById('field-font-size').addEventListener('input', function () {
                if (!selectedFieldEl) return;
                const val = this.value;
                selectedFieldEl.setAttribute('data-font-size', val);
                applyFieldStyles(selectedFieldEl);
            });

            document.getElementById('field-font-color').addEventListener('input', function () {
                if (!selectedFieldEl) return;
                const val = this.value;
                selectedFieldEl.setAttribute('data-font-color', val);
                applyFieldStyles(selectedFieldEl);
            });

            document.querySelectorAll('input[name="alignment"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    if (!selectedFieldEl || !this.checked) return;
                    selectedFieldEl.setAttribute('data-alignment', this.value);
                    applyFieldStyles(selectedFieldEl);
                });
            });

            document.querySelectorAll('input[name="valignment"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    if (!selectedFieldEl || !this.checked) return;
                    selectedFieldEl.setAttribute('data-valignment', this.value);
                    applyFieldStyles(selectedFieldEl);
                });
            });

            document.getElementById('btn-delete-field').addEventListener('click', function () {
                if (!selectedFieldEl) return;
                selectedFieldEl.remove();
                deselectAllFields();
            });

            // Configurar interact.js para arrastrar y redimensionar
            interact('.draggable-field')
                .draggable({
                    inertia: false,
                    modifiers: [
                        interact.modifiers.restrictRect({
                            restriction: 'parent',
                            endOnly: true
                        })
                    ],
                    autoScroll: false,
                    listeners: {
                        move(event) {
                            const target = event.target;
                            let x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                            let y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                            target.style.transform = `translate(${x}px, ${y}px)`;
                            target.setAttribute('data-x', x);
                            target.setAttribute('data-y', y);

                            // Guardar coordenadas convertidas a mm
                            const mmX = parseFloat((x * workspaceScaleX).toFixed(2));
                            const mmY = parseFloat((y * workspaceScaleY).toFixed(2));
                            target.setAttribute('data-mm-x', mmX);
                            target.setAttribute('data-mm-y', mmY);
                        }
                    }
                })
                .resizable({
                    edges: { left: true, right: true, bottom: true, top: true },
                    listeners: {
                        move(event) {
                            const target = event.target;
                            let x = (parseFloat(target.getAttribute('data-x')) || 0);
                            let y = (parseFloat(target.getAttribute('data-y')) || 0);

                            // Aplicar dimensiones en píxeles
                            target.style.width = event.rect.width + 'px';
                            target.style.height = event.rect.height + 'px';

                            // Traducir si se arrastra del borde izquierdo/superior
                            x += event.deltaRect.left;
                            y += event.deltaRect.top;

                            target.style.transform = `translate(${x}px, ${y}px)`;
                            target.setAttribute('data-x', x);
                            target.setAttribute('data-y', y);

                            // Guardar tamaño en mm
                            const mmW = parseFloat((event.rect.width * workspaceScaleX).toFixed(2));
                            const mmH = parseFloat((event.rect.height * workspaceScaleY).toFixed(2));
                            const mmX = parseFloat((x * workspaceScaleX).toFixed(2));
                            const mmY = parseFloat((y * workspaceScaleY).toFixed(2));

                            target.setAttribute('data-mm-w', mmW);
                            target.setAttribute('data-mm-h', mmH);
                            target.setAttribute('data-mm-x', mmX);
                            target.setAttribute('data-mm-y', mmY);

                            updateInspectorDimensions(target);
                        }
                    },
                    modifiers: [
                        interact.modifiers.restrictSize({
                            min: { width: 10, height: 5 }
                        }),
                        interact.modifiers.restrictRect({
                            restriction: 'parent'
                        })
                    ],
                    inertia: false
                });

            // ──────────────────────────────────────────────────────────
            // Compilar lienzo interactivo a JSON (Formato compatible PDFme)
            // ──────────────────────────────────────────────────────────
            function compileTemplateJson() {
                const fields = document.querySelectorAll('.draggable-field');
                const pageSchema = {};
                const counts = {};

                fields.forEach(el => {
                    const baseKey = el.getAttribute('data-variable');
                    counts[baseKey] = (counts[baseKey] || 0) + 1;
                });

                const currentCounts = {};
                fields.forEach(el => {
                    const baseKey = el.getAttribute('data-variable');
                    currentCounts[baseKey] = (currentCounts[baseKey] || 0) + 1;
                    
                    let finalKey = baseKey;
                    if (counts[baseKey] > 1) {
                        finalKey = `${baseKey}_${currentCounts[baseKey]}`;
                    }

                    const mmX = parseFloat(el.getAttribute('data-mm-x')) || 0;
                    const mmY = parseFloat(el.getAttribute('data-mm-y')) || 0;
                    const mmW = parseFloat(el.getAttribute('data-mm-w')) || 10;
                    const mmH = parseFloat(el.getAttribute('data-mm-h')) || 5;

                    pageSchema[finalKey] = {
                        name: finalKey,
                        type: el.getAttribute('data-type') || 'text',
                        position: { x: mmX, y: mmY },
                        width: mmW,
                        height: mmH,
                        fontSize: parseInt(el.getAttribute('data-font-size')) || 12,
                        fontColor: el.getAttribute('data-font-color') || '#000000',
                        alignment: el.getAttribute('data-alignment') || 'left',
                        valignment: el.getAttribute('data-valignment') || 'center',
                        content: el.getAttribute('data-content') || ''
                    };

                    // Guardar propiedades específicas de tabla
                    if (el.getAttribute('data-type') === 'table') {
                        pageSchema[finalKey].columnsWidths = el.getAttribute('data-columns-widths') || '15,40,15,10,20';
                    }
                });

                return {
                    width: pageWidthMm,
                    height: pageHeightMm,
                    basePdf: currentTemplate.basePdf || generateBlankBackground(),
                    schemas: [pageSchema]
                };
            }

            // ──────────────────────────────────────────────────────────
            // Lógica para cargar un PDF base del usuario
            // ──────────────────────────────────────────────────────────
            document.getElementById('basePdfFile').addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (!file) return;

                Swal.fire({
                    title: 'Procesando PDF...',
                    text: 'Convirtiendo páginas a imágenes de alta definición...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                loadTemplateBackground(file).then(() => {
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'PDF Convertido con Éxito',
                        text: 'La plantilla de fondo se ha cargado de manera interactiva.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
            });

            // ──────────────────────────────────────────────────────────
            // Acción: Guardar plantilla vía Fetch a Laravel
            // ──────────────────────────────────────────────────────────
            document.getElementById('btnSaveTemplate').addEventListener('click', function () {
                const templateName = document.getElementById('templateName').value;
                const docType = document.getElementById('documentType').value;
                
                if (!templateName.trim()) {
                    Swal.fire('Error', 'Por favor ingresa un nombre para la plantilla.', 'error');
                    return;
                }

                const compiledTemplate = compileTemplateJson();

                Swal.fire({
                    title: 'Guardando...',
                    text: 'Almacenando diseño de plantilla en el sistema...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("{{ route('pdf-templates.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: templateName,
                        document_type: docType,
                        template_json: JSON.stringify(compiledTemplate)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Guardado con Éxito!',
                            text: data.message,
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            window.location.href = "{{ route('pdf-templates.designer') }}?id=" + data.template.id;
                        });
                    } else {
                        Swal.fire('Error de Seguridad', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error al guardar la plantilla:', error);
                    Swal.fire('Error de Conexión', 'Ocurrió un problema de comunicación con el ERP.', 'error');
                });
            });

            // ──────────────────────────────────────────────────────────
            // Lógica para Cargar Plantilla Existente desde el Selector
            // ──────────────────────────────────────────────────────────
            document.getElementById('templateSelect').addEventListener('change', function (e) {
                const option = e.target.options[e.target.selectedIndex];
                const val = e.target.value;

                if (val === 'new') {
                    document.getElementById('templateName').value = '';
                    document.getElementById('documentType').value = 'producto';
                    window.lastSavedTemplateId = null;
                    document.getElementById('btnDeleteTemplate').style.display = 'none';
                    highlightAccordionForDocType('producto');
                    
                    initDesigner({
                        basePdf: generateBlankBackground(),
                        schemas: [{}]
                    });
                } else {
                    const docType = option.dataset.type;
                    const name = option.dataset.name;

                    document.getElementById('templateName').value = name;
                    document.getElementById('documentType').value = docType;
                    window.lastSavedTemplateId = val;
                    document.getElementById('btnDeleteTemplate').style.display = 'inline-block';
                    highlightAccordionForDocType(docType);

                    Swal.fire({
                        title: 'Cargando Plantilla...',
                        text: 'Obteniendo diseño de la base de datos...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`/pdf-templates/${val}/preview/default`)
                    .then(response => response.json())
                    .then(res => {
                        Swal.close();
                        if (res.success) {
                            initDesigner(res.template);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.close();
                        console.error("Error al cargar plantilla:", err);
                        Swal.fire('Error', 'No se pudo obtener la plantilla del servidor.', 'error');
                    });
                }
            });

            // ──────────────────────────────────────────────────────────
            // Acción: Eliminar plantilla vía Fetch (DELETE)
            // ──────────────────────────────────────────────────────────
            document.getElementById('btnDeleteTemplate').addEventListener('click', function () {
                const templateId = window.lastSavedTemplateId;
                if (!templateId) return;

                Swal.fire({
                    title: '¿Eliminar Plantilla?',
                    text: 'Esta acción no se puede deshacer y ocultará la plantilla del sistema.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando...',
                            text: 'Procesando baja de la plantilla...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(`/pdf-templates/${templateId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Plantilla Eliminada',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = "{{ route('pdf-templates.designer') }}";
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            Swal.close();
                            console.error('Error al eliminar la plantilla:', error);
                            Swal.fire('Error de Conexión', 'Ocurrió un problema de comunicación con el ERP.', 'error');
                        });
                    }
                });
            });

            // ──────────────────────────────────────────────────────────
            // Controles de Zoom
            // ──────────────────────────────────────────────────────────
            document.getElementById('btnZoomIn').addEventListener('click', function () {
                if (zoomLevel < 3.0) {
                    zoomLevel = parseFloat((zoomLevel + 0.1).toFixed(1));
                    updateZoomUI();
                }
            });

            document.getElementById('btnZoomOut').addEventListener('click', function () {
                if (zoomLevel > 0.5) {
                    zoomLevel = parseFloat((zoomLevel - 0.1).toFixed(1));
                    updateZoomUI();
                }
            });

            document.getElementById('btnZoomReset').addEventListener('click', function () {
                zoomLevel = 1.0;
                updateZoomUI();
            });

            function updateZoomUI() {
                document.getElementById('zoomPercent').textContent = `${Math.round(zoomLevel * 100)}%`;
                setWorkspaceSize();
            }

            // ──────────────────────────────────────────────────────────
            // Configuración del Formato (Milímetros Ancho/Alto y Presets Rápidos)
            // ──────────────────────────────────────────────────────────
            const paperPresets = {
                carta: { width: 215.9, height: 279.4 },
                carta_landscape: { width: 279.4, height: 215.9 },
                a4: { width: 210, height: 297 },
                a4_landscape: { width: 297, height: 210 },
                media_carta: { width: 139.7, height: 215.9 },
                media_carta_landscape: { width: 215.9, height: 139.7 },
                ticket_80: { width: 80, height: 200 },
                ticket_58: { width: 58, height: 150 },
                termica_4x6: { width: 101.6, height: 152.4 },
                brother_62x100: { width: 62, height: 100 }
            };

            function syncPaperPresetDropdown(w, h) {
                const presetSelect = document.getElementById('paperPreset');
                if (!presetSelect) return;
                let matched = 'custom';
                for (const [key, val] of Object.entries(paperPresets)) {
                    if (Math.abs(val.width - w) < 0.8 && Math.abs(val.height - h) < 0.8) {
                        matched = key;
                        break;
                    }
                }
                presetSelect.value = matched;
            }

            document.getElementById('paperPreset').addEventListener('change', function () {
                const val = this.value;
                if (paperPresets[val]) {
                    const p = paperPresets[val];
                    pageWidthMm = p.width;
                    pageHeightMm = p.height;
                    document.getElementById('templateWidth').value = p.width;
                    document.getElementById('templateHeight').value = p.height;
                    setWorkspaceSize();
                }
            });

            document.getElementById('templateWidth').addEventListener('input', function (e) {
                const val = parseFloat(e.target.value);
                if (val > 0) {
                    pageWidthMm = val;
                    syncPaperPresetDropdown(pageWidthMm, pageHeightMm);
                    setWorkspaceSize();
                }
            });

            document.getElementById('templateHeight').addEventListener('input', function (e) {
                const val = parseFloat(e.target.value);
                if (val > 0) {
                    pageHeightMm = val;
                    syncPaperPresetDropdown(pageWidthMm, pageHeightMm);
                    setWorkspaceSize();
                }
            });

            // Enfoque dinámico del acordeón según el tipo de documento seleccionado
            function highlightAccordionForDocType(docType) {
                let targetId = 'collapseProduct';
                if (docType === 'orden_entrega') {
                    targetId = 'collapseDelivery';
                } else if (docType === 'orden_venta') {
                    targetId = 'collapseSales';
                } else if (docType === 'abono' || docType === 'anticipo') {
                    targetId = 'collapseReceipts';
                }

                document.querySelectorAll('#accordionElements .accordion-collapse').forEach(el => {
                    if (el.id === targetId) {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el);
                        bsCollapse.show();
                    }
                });
            }

            document.getElementById('documentType').addEventListener('change', function () {
                highlightAccordionForDocType(this.value);
            });

            // Función de Impresión/Previsualización Nativa (HTML/CSS)
            function imprimirEtiquetaNativa(template, data) {
                const pageSchema = template.schemas[0] || {};
                const mmW = template.width || 100;
                const mmH = template.height || 50;
                const bgImage = template.basePdf || '';

                let fields = [];
                if (Array.isArray(pageSchema)) {
                    fields = pageSchema.filter(f => f && f.name).map(f => ({ ...f, name: f.name }));
                } else {
                    fields = Object.entries(pageSchema)
                        .filter(([, f]) => f)
                        .map(([key, f]) => ({ ...f, name: f.name || key }));
                }

                const mappedFields = {};
                fields.forEach(field => {
                    const key = field.name;
                    const baseKey = key.replace(/_(?!(3|6|9|12)$)\d+$/, '');
                    let val = '';
                    if (data.hasOwnProperty(baseKey)) {
                        val = data[baseKey] ?? '';
                    } else if (data.hasOwnProperty(key)) {
                        val = data[key] ?? '';
                    } else {
                        val = field.content || '';
                    }
                    mappedFields[key] = {
                        ...field,
                        value: val
                    };
                });

                const printWindow = window.open('', '_blank');
                if (!printWindow) {
                    Swal.fire('Error', 'El navegador bloqueó la ventana de impresión emergente. Por favor habilítala.', 'error');
                    return;
                }

                let fieldsHtml = '';
                fields.forEach(field => {
                    const mapped = mappedFields[field.name];
                    const val = mapped.value;
                    const left = field.position.x;
                    const top = field.position.y;
                    const width = field.width;
                    const height = field.height;

                    let contentHtml = '';
                    if (field.type === 'barcodes.code128') {
                        contentHtml = `<svg class="barcode-svg" data-value="${val}" style="width: 100%; height: 100%;"></svg>`;
                    } else if (field.type === 'qrcode') {
                        contentHtml = `<canvas class="qrcode-canvas" data-value="${val}" style="width: 100%; height: 100%;"></canvas>`;
                    } else if (field.type === 'image') {
                        contentHtml = `<img src="${val}" style="width: 100%; height: 100%; object-fit: contain;">`;
                    } else if (field.type === 'table') {
                        let tableHeader = '';
                        let tableBody = '';
                        if (Array.isArray(val) && val.length > 0) {
                            const colWidths = (field.columnsWidths || '').split(',').map(n => parseInt(n.trim()));
                            val.forEach((row, rIdx) => {
                                let rowHtml = '';
                                const isHeader = rIdx === 0;
                                if (Array.isArray(row)) {
                                    row.forEach((cellText, cIdx) => {
                                        const w = colWidths[cIdx] ? `width: ${colWidths[cIdx]}%;` : '';
                                        if (isHeader) {
                                            rowHtml += `<th style="${w}; padding: 4px 6px; font-weight: 700; background-color: #f1f5f9; border-bottom: 2px solid #475569; text-align: left; font-size: inherit;">${cellText}</th>`;
                                        } else {
                                            rowHtml += `<td style="${w}; padding: 3px 6px; border-bottom: 1px solid #e2e8f0; font-size: inherit;">${cellText}</td>`;
                                        }
                                    });
                                }
                                if (isHeader) {
                                    tableHeader = `<thead><tr>${rowHtml}</tr></thead>`;
                                } else {
                                    tableBody += `<tr>${rowHtml}</tr>`;
                                }
                            });
                        }
                        const fontSize = field.fontSize || 10;
                        const fontColor = field.fontColor || '#000000';
                        contentHtml = `
                            <div style="font-size:${fontSize}pt;color:${fontColor};width:100%;height:100%;overflow:hidden;font-family:'Roboto',sans-serif;">
                                <table style="width: 100%; border-collapse: collapse; font-size: inherit; color: inherit;">
                                    ${tableHeader}
                                    <tbody>${tableBody}</tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        const fontSize = field.fontSize || 12;
                        const fontColor = field.fontColor || '#000000';
                        const alignment = field.alignment || 'left';
                        const valignment = field.valignment || 'center';
                        const strVal = typeof val === 'object' ? JSON.stringify(val) : String(val);
                        contentHtml = `<div style="font-size:${fontSize}pt;color:${fontColor};text-align:${alignment};width:100%;height:100%;white-space:pre-wrap;word-break:break-word;font-family:'Roboto',sans-serif;display:flex;align-items:${valignment === 'top' ? 'flex-start' : (valignment === 'bottom' ? 'flex-end' : 'center')};justify-content:${alignment === 'center' ? 'center' : (alignment === 'right' ? 'flex-end' : 'flex-start')};">${strVal}</div>`;
                    }

                    fieldsHtml += `
                        <div class="field-item" style="left: ${left}mm; top: ${top}mm; width: ${width}mm; height: ${height}mm;">
                            ${contentHtml}
                        </div>
                    `;
                });

                printWindow.document.write(`
<!DOCTYPE html>
<html>
<head>
    <title>Imprimir Etiqueta</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            body { margin: 0; }
            @page { margin: 0; size: ${mmW}mm ${mmH}mm; }
        }
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .label-page {
            position: relative;
            width: ${mmW}mm;
            height: ${mmH}mm;
            background-image: url('${bgImage}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            box-sizing: border-box;
            background-color: #ffffff;
            overflow: hidden;
            page-break-after: always;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .field-item {
            position: absolute;
            box-sizing: border-box;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="label-page">
        ${fieldsHtml}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"><\/script>
    <script>
        window.onload = function() {
            // Códigos de barra
            document.querySelectorAll('.barcode-svg').forEach(el => {
                const val = el.getAttribute('data-value');
                if (val) {
                    try {
                        JsBarcode(el, val, {
                            format: "CODE128",
                            displayValue: false,
                            margin: 0,
                            height: el.clientHeight,
                            width: 2
                        });
                    } catch (e) {
                        console.error('Error drawing barcode:', e);
                    }
                }
            });

            // Códigos QR
            document.querySelectorAll('.qrcode-canvas').forEach(el => {
                const val = el.getAttribute('data-value');
                if (val) {
                    try {
                        const size = Math.min(el.clientWidth, el.clientHeight);
                        QRCode.toCanvas(el, val, {
                            margin: 0,
                            width: size
                        });
                    } catch (e) {
                        console.error('Error drawing QR:', e);
                    }
                }
            });

            setTimeout(() => {
                window.print();
                window.close();
            }, 600);
        };
    <\/script>
</body>
</html>
                `);
                printWindow.document.close();
            }

            // ──────────────────────────────────────────────────────────
            // Acción: Renderizar y Descargar/Imprimir desde el Frontend
            // ──────────────────────────────────────────────────────────
            document.getElementById('btnGeneratePreview').addEventListener('click', function () {
                const templateId = window.lastSavedTemplateId;

                if (!templateId) {
                    Swal.fire('Atención', 'Primero debes presionar "Guardar Plantilla" para poder descargar una prueba de impresión.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Generando Vista Previa...',
                    text: 'Obteniendo variables de simulación...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/pdf-templates/${templateId}/preview/default`)
                .then(response => response.json())
                .then(res => {
                    Swal.close();
                    if (res.success) {
                        imprimirEtiquetaNativa(res.template, res.data[0]);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.close();
                    console.error("Error al generar vista previa:", err);
                    Swal.fire('Error', 'No se pudo obtener la plantilla de prueba del servidor.', 'error');
                });
            });

            // Inicialización por Defecto
            async function loadFontsAndInit() {
                const urlParams = new URLSearchParams(window.location.search);
                const templateIdFromUrl = urlParams.get('id');

                if (templateIdFromUrl) {
                    const selectEl = document.getElementById('templateSelect');
                    if (selectEl.querySelector(`option[value="${templateIdFromUrl}"]`)) {
                        selectEl.value = templateIdFromUrl;
                        selectEl.dispatchEvent(new Event('change'));
                    } else {
                        initDesigner({
                            basePdf: generateBlankBackground(),
                            schemas: [{}]
                        });
                    }
                } else {
                    initDesigner({
                        basePdf: generateBlankBackground(),
                        schemas: [{}]
                    });
                }
            }

            loadFontsAndInit();
        });
    </script>
@endsection
