@extends('layouts.master')
@section('title') Configuración @endsection

@section('css')
    <!-- SweetAlert2 -->
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <x-breadcrumb />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">
                            <i class="ri-settings-3-line me-1"></i> Configuración
                            @if($currentStore)
                                <span class="badge bg-primary-subtle text-primary ms-2">{{ $currentStore->name }}</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning ms-2">Global</span>
                            @endif
                        </h5>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Tabs para grupos -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-primary" role="tablist">
                        @foreach($groups as $groupName => $fields)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                               data-bs-toggle="tab"
                               href="#tab-{{ $groupName }}"
                               role="tab">
                                @php
                                    $icons = [
                                        'fiscal' => 'ri-bank-line',
                                        'impuestos' => 'ri-percent-line',
                                        'tickets' => 'ri-printer-line',
                                        'moneda' => 'ri-money-dollar-circle-line',
                                        'margenes' => 'ri-line-chart-line',
                                        'financiamiento' => 'ri-bank-card-line',
                                        'comisiones' => 'ri-hand-coin-line',
                                        'whatsapp' => 'ri-whatsapp-line',
                                        'inteligencia_artificial' => 'ri-robot-line',
                                        'apartados' => 'ri-bookmark-3-line',
                                        'pdf_templates' => 'ri-file-pdf-line',
                                        'mantenimiento' => 'ri-delete-bin-5-line',
                                    ];
                                    $labels = [
                                        'fiscal' => 'Fiscal',
                                        'impuestos' => 'Impuestos',
                                        'tickets' => 'Tickets',
                                        'moneda' => 'Moneda',
                                        'margenes' => 'Márgenes de Ganancia',
                                        'financiamiento' => 'Financiamiento / MSI',
                                        'comisiones' => 'Matriz de Comisiones',
                                        'whatsapp' => 'WhatsApp (API)',
                                        'inteligencia_artificial' => 'Inteligencia Artificial',
                                        'apartados' => 'Apartados y Políticas POS',
                                        'pdf_templates' => 'Plantillas PDF',
                                        'mantenimiento' => 'Mantenimiento / Limpieza BD',
                                    ];
                                @endphp
                                <i class="{{ $icons[$groupName] ?? 'ri-settings-3-line' }} me-1"></i>
                                {{ $labels[$groupName] ?? ucfirst($groupName) }}
                            </a>
                        </li>
                        @endforeach
                    </ul>

                    <div class="tab-content pt-4">
                        @foreach($groups as $groupName => $fields)
                        <div class="tab-pane {{ $loop->first ? 'active show' : '' }}" id="tab-{{ $groupName }}" role="tabpanel">
                            @if($groupName === 'mantenimiento')
                                @can('limpiar-base-datos')
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="card border border-danger shadow-sm">
                                            <div class="card-header bg-danger-subtle border-danger p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm flex-shrink-0 me-3">
                                                        <div class="avatar-title bg-danger text-white rounded-circle fs-20">
                                                            <i class="ri-delete-bin-5-line"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="card-title text-danger mb-1 fw-bold">Limpiador de Base de Datos - Datos de Prueba</h5>
                                                        <p class="text-muted fs-12 mb-0">Elimina de manera segura las ventas, cotizaciones, inventarios, movimientos, compras, pedidos especiales, gastos y registros de prueba.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body p-4">
                                                <form id="dbCleanerForm" action="{{ route('settings.clean-database') }}" method="POST">
                                                    @csrf

                                                    <div class="row mb-4">
                                                        <div class="col-md-6">
                                                            <div class="card bg-success-subtle border-success shadow-none mb-0">
                                                                <div class="card-body py-3">
                                                                    <h6 class="fs-14 text-success mb-2 fw-semibold">
                                                                        <i class="ri-shield-check-fill me-1"></i> Datos Críticos PRESERVADOS (Intactos):
                                                                    </h6>
                                                                    <ul class="text-muted fs-13 mb-0 ps-3">
                                                                        <li>Usuarios del sistema (Personal, Administradores, Vendedores, Cajeros)</li>
                                                                        <li>Sucursales, Tiendas y Bodegas configuradas</li>
                                                                        <li>Roles, Permisos y Matriz Spatie RBAC</li>
                                                                        <li>Configuración y Parámetros del Sistema</li>
                                                                        <li>Métodos y Terminales de Pago configurados</li>
                                                                        <li>Plantillas de Impresión y Facturación PDF</li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card bg-info-subtle border-info shadow-none mb-0">
                                                                <div class="card-body py-3">
                                                                    <h6 class="fs-14 text-info mb-2 fw-semibold">
                                                                        <i class="ri-magic-line me-1"></i> Políticas de Integridad y Huérfanos:
                                                                    </h6>
                                                                    <ul class="text-muted fs-13 mb-0 ps-3">
                                                                        <li><strong>Usuarios Huérfanos:</strong> Se eliminan solo cuentas cuyo único rol sea Cliente o Proveedor; el personal con roles mixtos se conserva.</li>
                                                                        <li><strong>Crédito de Clientes:</strong> Al limpiar ventas, el crédito disponible se restaura al límite asignado.</li>
                                                                        <li><strong>Inventario en 0:</strong> Al limpiar inventario sin borrar productos, el stock y apartados se resetean a 0.00.</li>
                                                                        <li><strong>Vaciado Universal de Caché:</strong> Al finalizar la limpieza se purga toda la caché del sistema.</li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Barra de Acciones Rápidas -->
                                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 bg-light rounded border mb-4">
                                                        <div>
                                                            <h6 class="fs-14 fw-bold text-dark mb-1">
                                                                <i class="ri-checkbox-line text-primary me-1"></i> Selecciona los Módulos a Limpiar:
                                                            </h6>
                                                            <p class="text-muted fs-12 mb-0">Marca las casillas de los datos transaccionales que deseas depurar.</p>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectTestData">
                                                                <i class="ri-check-double-line me-1"></i> Datos de Prueba (Recomendado)
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" id="btnSelectAll">
                                                                <i class="ri-checkbox-multiple-line me-1"></i> Seleccionar Todo
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">
                                                                <i class="ri-close-circle-line me-1"></i> Deseleccionar Todo
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Malla de Módulos por Checkboxes -->
                                                    <div class="row g-3 mb-4">
                                                        <!-- 1. Operaciones y Ventas -->
                                                        <div class="col-md-6">
                                                            <div class="card border h-100 mb-0 shadow-none">
                                                                <div class="card-header bg-primary-subtle py-2">
                                                                    <h6 class="fs-13 fw-bold text-primary mb-0">
                                                                        <i class="ri-shopping-cart-2-line me-1"></i> 1. Ventas, POS y Clientes
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body p-3">
                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_sales" value="1" id="clean_sales">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_sales">
                                                                            <span>Ventas, Cotizaciones y Cortes de Caja</span>
                                                                            <span class="badge bg-primary-subtle text-primary">{{ number_format($cleanerStats['sales']['sales'] ?? 0) }} ventas / {{ number_format($cleanerStats['sales']['shifts'] ?? 0) }} turnos</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina tickets, pagos, comisiones, cotizaciones y turnos de caja. Restaura el crédito disponible de los clientes.</div>
                                                                    </div>

                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_special_orders" value="1" id="clean_special_orders">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_special_orders">
                                                                            <span>Pedidos Especiales y Anticipos</span>
                                                                            <span class="badge bg-primary-subtle text-primary">{{ number_format($cleanerStats['special_orders']['orders'] ?? 0) }} pedidos</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina pedidos bajo encargo, abonos y anticipos registrados.</div>
                                                                    </div>

                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_customers" value="1" id="clean_customers">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_customers">
                                                                            <span>Clientes y Cuentas Huérfanas</span>
                                                                            <span class="badge bg-primary-subtle text-primary">{{ number_format($cleanerStats['customers']['customers'] ?? 0) }} clientes</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina expedientes de clientes y cuentas de usuario asociadas sin roles de personal/administración.</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 2. Cadena de Suministro y Stock -->
                                                        <div class="col-md-6">
                                                            <div class="card border h-100 mb-0 shadow-none">
                                                                <div class="card-header bg-warning-subtle py-2">
                                                                    <h6 class="fs-13 fw-bold text-warning-emphasis mb-0">
                                                                        <i class="ri-truck-line me-1"></i> 2. Cadena de Suministro y Stock
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body p-3">
                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_inventory" value="1" id="clean_inventory">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_inventory">
                                                                            <span>Kardex, Traspasos y Stock en 0</span>
                                                                            <span class="badge bg-warning-subtle text-warning-emphasis">{{ number_format($cleanerStats['inventory']['movements'] ?? 0) }} movs / {{ number_format($cleanerStats['inventory']['transfers'] ?? 0) }} traspasos</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina historial de movimientos y traspasos entre sucursales. Resetea el stock y apartados de productos a 0.00.</div>
                                                                    </div>

                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_purchases" value="1" id="clean_purchases">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_purchases">
                                                                            <span>Órdenes de Compra a Proveedores</span>
                                                                            <span class="badge bg-warning-subtle text-warning-emphasis">{{ number_format($cleanerStats['purchases']['orders'] ?? 0) }} compras</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina órdenes de compra generadas y su historial de pagos a proveedores.</div>
                                                                    </div>

                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_suppliers" value="1" id="clean_suppliers">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_suppliers">
                                                                            <span>Proveedores y Cuentas Huérfanas</span>
                                                                            <span class="badge bg-warning-subtle text-warning-emphasis">{{ number_format($cleanerStats['suppliers']['suppliers'] ?? 0) }} proveedores</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina expedientes de proveedores y sus cuentas de usuario asociadas sin roles de administración.</div>
                                                                    </div>

                                                                    <div class="form-check form-switch border-top pt-2 mt-2">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_catalog" value="1" id="clean_catalog">
                                                                        <label class="form-check-label fw-bold text-danger fs-13 d-flex justify-content-between align-items-center" for="clean_catalog">
                                                                            <span><i class="ri-alert-fill text-danger me-1"></i> Catálogo de Productos Completo</span>
                                                                            <span class="badge bg-danger-subtle text-danger">{{ number_format($cleanerStats['catalog']['products'] ?? 0) }} prods / {{ number_format($cleanerStats['catalog']['categories'] ?? 0) }} cats</span>
                                                                        </label>
                                                                        <div class="text-danger-emphasis fs-12 ms-4">Elimina productos, variantes, categorías, marcas y atributos. <em>(Requiere limpiar ventas, compras e inventarios dependientes)</em>.</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 3. Finanzas y Promociones -->
                                                        <div class="col-md-6">
                                                            <div class="card border h-100 mb-0 shadow-none">
                                                                <div class="card-header bg-success-subtle py-2">
                                                                    <h6 class="fs-13 fw-bold text-success mb-0">
                                                                        <i class="ri-money-dollar-box-line me-1"></i> 3. Finanzas y Promociones
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body p-3">
                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_expenses" value="1" id="clean_expenses">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_expenses">
                                                                            <span>Gastos Empresariales</span>
                                                                            <span class="badge bg-success-subtle text-success">{{ number_format($cleanerStats['expenses']['expenses'] ?? 0) }} gastos</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina gastos de operación, viáticos y salidas de efectivo registradas.</div>
                                                                    </div>

                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_coupons" value="1" id="clean_coupons">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_coupons">
                                                                            <span>Cupones de Descuento y Redenciones</span>
                                                                            <span class="badge bg-success-subtle text-success">{{ number_format($cleanerStats['coupons']['coupons'] ?? 0) }} cupones</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina cupones promocionales y registros de canje.</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 4. Sistema, Auditoría y Archivos -->
                                                        <div class="col-md-6">
                                                            <div class="card border h-100 mb-0 shadow-none">
                                                                <div class="card-header bg-secondary-subtle py-2">
                                                                    <h6 class="fs-13 fw-bold text-secondary mb-0">
                                                                        <i class="ri-settings-4-line me-1"></i> 4. Sistema, Auditoría y Archivos
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body p-3">
                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_audit" value="1" id="clean_audit">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_audit">
                                                                            <span>Bitácora de Auditoría del Sistema</span>
                                                                            <span class="badge bg-secondary-subtle text-secondary">{{ number_format($cleanerStats['audit']['logs'] ?? 0) }} registros</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina el registro histórico de actividades y cambios del sistema.</div>
                                                                    </div>

                                                                    <div class="form-check form-switch mb-3">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_social_ai" value="1" id="clean_social_ai">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_social_ai">
                                                                            <span>Feed Social, Comunicados y Chats IA</span>
                                                                            <span class="badge bg-secondary-subtle text-secondary">{{ number_format(($cleanerStats['social_ai']['posts'] ?? 0) + ($cleanerStats['social_ai']['conversations'] ?? 0)) }} items</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina publicaciones, comentarios, reacciones, notificaciones y conversaciones con el agente de IA.</div>
                                                                    </div>

                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input cleaner-checkbox" type="checkbox" name="clean_storage" value="1" id="clean_storage">
                                                                        <label class="form-check-label fw-semibold text-dark fs-13 d-flex justify-content-between align-items-center" for="clean_storage">
                                                                            <span>Archivos Multimedia en Storage</span>
                                                                            <span class="badge bg-secondary-subtle text-secondary">{{ number_format($cleanerStats['storage']['files'] ?? 0) }} archivos</span>
                                                                        </label>
                                                                        <div class="text-muted fs-12 ms-4">Elimina PDFs adjuntos de compras, imágenes de posts y archivos temporales <em>(los avatares de usuarios siempre se preservan)</em>.</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="text-end pt-2 border-top">
                                                        <button type="button" class="btn btn-danger btn-lg px-4" id="btnTriggerDbClean">
                                                            <i class="ri-delete-bin-line me-1"></i> Ejecutar Limpieza Seleccionada
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="alert alert-warning border-warning shadow-none my-3">
                                    <i class="ri-lock-line me-2 fs-16 align-middle"></i>
                                    Esta sección requiere el permiso especial <strong>[limpiar-base-datos]</strong> asignado a tu rol para purgar información del ERP.
                                </div>
                                @endcan
                            @else
                                <form action="{{ route('settings.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="group" value="{{ $groupName }}">

                                @if($groupName === 'pdf_templates')
                                <div class="card bg-light border shadow-none mb-4">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm flex-shrink-0 me-3">
                                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                                                    <i class="ri-palette-line"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="fs-15 text-primary mb-1">Diseñador Visual de Plantillas PDF</h5>
                                                <p class="text-muted fs-12 mb-0">Personaliza visualmente las etiquetas de tus productos, órdenes de entrega, órdenes de venta, abonos y anticipos mediante arrastrar y soltar con PDFme.</p>
                                            </div>
                                        </div>
                                        <div class="text-start">
                                            <a href="{{ route('pdf-templates.designer') }}" class="btn btn-primary btn-sm fs-12">
                                                <i class="ri-external-link-line me-1"></i> Abrir Diseñador de PDFs
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="row">
                                    @foreach($fields as $field)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="setting-{{ $field['key'] }}" class="form-label">
                                                {{ $field['label'] }}
                                            </label>

                                            @if($field['type'] === 'boolean')
                                                <div class="form-check form-switch">
                                                    <input type="checkbox"
                                                           class="form-check-input"
                                                           name="settings[{{ $field['key'] }}]"
                                                           id="setting-{{ $field['key'] }}"
                                                           value="1"
                                                           {{ $settings[$field['key']] ?? $field['default'] ? 'checked' : '' }}>
                                                </div>
                                            @elseif($field['type'] === 'text')
                                                <textarea name="settings[{{ $field['key'] }}]"
                                                          id="setting-{{ $field['key'] }}"
                                                          class="form-control form-control-sm"
                                                          rows="3">{{ $settings[$field['key']] ?? $field['default'] }}</textarea>
                                            @elseif($field['type'] === 'select')
                                                <select name="settings[{{ $field['key'] }}]"
                                                        id="setting-{{ $field['key'] }}"
                                                        class="form-select form-select-sm">
                                                    @foreach($field['options'] ?? [] as $val => $lbl)
                                                        <option value="{{ $val }}" {{ ($settings[$field['key']] ?? $field['default']) == $val ? 'selected' : '' }}>
                                                            {{ $lbl }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="{{ $field['type'] === 'integer' ? 'number' : 'text' }}"
                                                       name="settings[{{ $field['key'] }}]"
                                                       id="setting-{{ $field['key'] }}"
                                                       class="form-control form-control-sm"
                                                       value="{{ $settings[$field['key']] ?? $field['default'] }}">
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                @if(!$isGlobal)
                                <div class="alert alert-info alert-borderless" role="alert">
                                    <i class="ri-information-line me-2"></i>
                                    Estos ajustes aplican solo a <strong>{{ $currentStore->name }}</strong>.
                                    Los valores vacíos heredarán la configuración global.
                                </div>
                                @endif

                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-save-line me-1"></i> Guardar {{ $labels[$groupName] ?? ucfirst($groupName) }}
                                    </button>
                                </div>
                            </form>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal OTP Settings Verification -->
    <div class="modal fade" id="settingsOtpVerificationModal" tabindex="-1" aria-labelledby="settingsOtpVerificationModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-soft-primary p-3">
                    <h5 class="modal-title" id="settingsOtpVerificationModalLabel">Verificación de Seguridad Requerida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnSettingsCloseOtpModal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-4">
                        <i class="ri-shield-keyhole-line text-primary" style="font-size: 72px;"></i>
                    </div>
                    <div class="mt-2">
                        <h4 class="mb-3">Código de Verificación</h4>
                        <p class="text-muted mb-4">Hemos enviado un código de 6 dígitos a tu correo electrónico y a tu WhatsApp. Ingrésalo para autorizar la activación de la campaña de MSI.</p>
                        
                        <form id="settingsOtpFormValidation">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3" id="settings-otp-container">
                                <input type="text" class="form-control text-center settings-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center settings-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center settings-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center settings-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center settings-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                                <input type="text" class="form-control text-center settings-otp-input form-control-sm" maxlength="1" pattern="[0-9]" style="width: 45px; height: 50px; font-size: 20px; font-weight: bold;">
                            </div>
                            <input type="hidden" id="settings-full-otp-code" name="otp_code">
                            <div class="text-danger mb-3 d-none" id="settings-otp-error-msg">El código ingresado es incorrecto o expiró. Intenta de nuevo.</div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-success w-100 btn-sm" id="btnSettingsVerifyOtp">Verificar Código</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<!-- SweetAlert2 -->
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const msiToggle = document.getElementById('setting-msi_campaign_active');
        const settingsOtpInputs = document.querySelectorAll(".settings-otp-input");
        const settingsFullOtpInput = document.getElementById("settings-full-otp-code");
        const settingsOtpModalEl = document.getElementById('settingsOtpVerificationModal');
        let settingsOtpModal = null;
        let isAuthorized = false;
        let previousState = msiToggle ? msiToggle.checked : false;

        if (msiToggle && settingsOtpModalEl) {
            settingsOtpModal = new bootstrap.Modal(settingsOtpModalEl, {
                backdrop: 'static'
            });

            // Lógica de Inputs del OTP
            settingsOtpInputs.forEach((input, index) => {
                input.addEventListener("input", (e) => {
                    if (input.value.length === 1 && index < settingsOtpInputs.length - 1) {
                        settingsOtpInputs[index + 1].focus();
                    }
                    updateFullOtp();
                });

                input.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace" && input.value === "" && index > 0) {
                        settingsOtpInputs[index - 1].focus();
                    }
                });
                
                input.addEventListener("paste", (e) => {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').slice(0, 6).replace(/[^0-9]/g, '');
                    if (pastedData) {
                        [...pastedData].forEach((char, i) => {
                            if(settingsOtpInputs[i]) {
                                settingsOtpInputs[i].value = char;
                            }
                        });
                        if(pastedData.length < 6) {
                            settingsOtpInputs[pastedData.length].focus();
                        } else {
                            settingsOtpInputs[5].focus();
                            settingsOtpInputs[5].blur();
                        }
                        updateFullOtp();
                    }
                });
            });

            function updateFullOtp() {
                let code = "";
                settingsOtpInputs.forEach(input => code += input.value);
                settingsFullOtpInput.value = code;
            }

            msiToggle.addEventListener('change', function(e) {
                // Solo si el usuario lo activa (pasa de false a true)
                if (this.checked && !previousState && !isAuthorized) {
                    this.checked = false; // Desmarcar momentáneamente

                    // Mostrar cargando
                    Swal.fire({
                        title: 'Generando Código OTP...',
                        text: 'Por favor espera un momento.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch('/user/profile/generate-otp', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ action: 'msi_campaign' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            // Limpiar modal
                            settingsOtpInputs.forEach(input => input.value = "");
                            settingsFullOtpInput.value = "";
                            document.getElementById('settings-otp-error-msg').classList.add('d-none');
                            
                            // Mostrar modal
                            settingsOtpModal.show();
                            
                            // Foco
                            settingsOtpInputs[0].focus();
                        } else {
                            Swal.fire('Error', data.message || 'Error al generar el código OTP.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.close();
                        Swal.fire('Error', 'Ocurrió un error al contactar al servidor.', 'error');
                    });
                } else {
                    previousState = this.checked;
                    if (!this.checked) {
                        isAuthorized = false;
                    }
                }
            });

            // Acción Verificar
            document.getElementById('btnSettingsVerifyOtp').addEventListener('click', async function() {
                const otpCode = settingsFullOtpInput.value;
                if (otpCode.length < 6) {
                    document.getElementById('settings-otp-error-msg').innerText = 'Debes ingresar los 6 dígitos.';
                    document.getElementById('settings-otp-error-msg').classList.remove('d-none');
                    return;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                try {
                    const response = await fetch('/user/profile/validate-otp', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ otp_code: otpCode })
                    });
                    const data = await response.json();

                    if (data.success) {
                        isAuthorized = true;
                        msiToggle.checked = true;
                        previousState = true;
                        settingsOtpModal.hide();
                        Swal.fire('Autorizado', 'Campaña de MSI autorizada. Guarda los cambios para hacerla efectiva.', 'success');
                    } else {
                        document.getElementById('settings-otp-error-msg').innerText = data.message || 'El código ingresado es incorrecto.';
                        document.getElementById('settings-otp-error-msg').classList.remove('d-none');
                    }
                } catch(err) {
                    document.getElementById('settings-otp-error-msg').innerText = 'Error al conectar con el servidor.';
                    document.getElementById('settings-otp-error-msg').classList.remove('d-none');
                }
            });

            // Cerrar o cancelar modal
            document.getElementById('btnSettingsCloseOtpModal').addEventListener('click', function() {
                msiToggle.checked = false;
                previousState = false;
                isAuthorized = false;
            });

            settingsOtpModalEl.addEventListener('hidden.bs.modal', function () {
                if (!isAuthorized) {
                    msiToggle.checked = false;
                    previousState = false;
                }
            });
        }

        // Manejador del Limpiador de Base de Datos Modular
        const btnTriggerDbClean = document.getElementById('btnTriggerDbClean');
        const btnSelectTestData = document.getElementById('btnSelectTestData');
        const btnSelectAll = document.getElementById('btnSelectAll');
        const btnDeselectAll = document.getElementById('btnDeselectAll');
        const chkCleanCatalog = document.getElementById('clean_catalog');

        if (btnSelectTestData) {
            btnSelectTestData.addEventListener('click', function () {
                document.querySelectorAll('.cleaner-checkbox').forEach(cb => {
                    cb.checked = (cb.id !== 'clean_catalog');
                });
            });
        }

        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', function () {
                document.querySelectorAll('.cleaner-checkbox').forEach(cb => cb.checked = true);
            });
        }

        if (btnDeselectAll) {
            btnDeselectAll.addEventListener('click', function () {
                document.querySelectorAll('.cleaner-checkbox').forEach(cb => cb.checked = false);
            });
        }

        if (chkCleanCatalog) {
            chkCleanCatalog.addEventListener('change', function () {
                if (this.checked) {
                    // Auto-marcar dependencias críticas de productos
                    const depSales = document.getElementById('clean_sales');
                    const depOrders = document.getElementById('clean_special_orders');
                    const depInv = document.getElementById('clean_inventory');
                    const depPurchases = document.getElementById('clean_purchases');

                    if (depSales) depSales.checked = true;
                    if (depOrders) depOrders.checked = true;
                    if (depInv) depInv.checked = true;
                    if (depPurchases) depPurchases.checked = true;

                    Swal.fire({
                        title: 'Dependencia del Catálogo',
                        text: 'Al eliminar el catálogo de productos se marcaron automáticamente ventas, compras, pedidos especiales e inventarios, ya que estos módulos dependen de la existencia de productos.',
                        icon: 'info',
                        confirmButtonColor: '#3085d6',
                    });
                }
            });
        }

        if (btnTriggerDbClean) {
            btnTriggerDbClean.addEventListener('click', function() {
                const checkedBoxes = Array.from(document.querySelectorAll('.cleaner-checkbox:checked'));

                if (checkedBoxes.length === 0) {
                    Swal.fire({
                        title: 'Ningún módulo seleccionado',
                        text: 'Por favor selecciona al menos una casilla de los módulos que deseas limpiar.',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                    });
                    return;
                }

                let selectedLabels = checkedBoxes.map(cb => {
                    const label = document.querySelector(`label[for="${cb.id}"] span`);
                    return `• ${label ? label.textContent.trim() : cb.id}`;
                }).join('<br>');

                let warningHtml = `<div class="text-start fs-13"><strong class="text-danger">Se eliminarán los datos de los siguientes módulos:</strong><br><br>${selectedLabels}</div>`;

                if (chkCleanCatalog && chkCleanCatalog.checked) {
                    warningHtml += `<br><div class="alert alert-danger p-2 text-start fs-12 mb-0 fw-bold">⚠️ ¡ALERTA! Se eliminará TODO el catálogo de productos, categorías y marcas.</div>`;
                }

                Swal.fire({
                    title: '¿Confirmar Limpieza Modular de BD?',
                    html: warningHtml + '<br><br>Escribe <code>ELIMINAR DATOS DE PRUEBA</code> para confirmar:',
                    input: 'text',
                    inputPlaceholder: 'ELIMINAR DATOS DE PRUEBA',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, ejecutar limpieza',
                    cancelButtonText: 'Cancelar',
                    inputValidator: (value) => {
                        if (value !== 'ELIMINAR DATOS DE PRUEBA') {
                            return 'Debes escribir exactamente "ELIMINAR DATOS DE PRUEBA"';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Limpiando Base de Datos...',
                            text: 'Por favor espera mientras se procesa la solicitud y se purgan las cachés.',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const form = document.getElementById('dbCleanerForm');
                        const formData = new FormData(form);
                        formData.append('confirmation', result.value);

                        fetch("{{ route('settings.clean-database') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Limpieza Completada!',
                                    text: data.message,
                                    icon: 'success'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message || 'No se pudo completar la limpieza.', 'error');
                            }
                        })
                        .catch(err => {
                            Swal.close();
                            Swal.fire('Error', 'Ocurrió un error inesperado al procesar la solicitud.', 'error');
                        });
                    }
                });
            });
        }
    });
</script>
@endsection
