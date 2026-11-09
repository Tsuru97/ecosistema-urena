<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Arr;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * IDEMPOTENTE: re-ejecutar solo agrega nuevos permisos/roles sin duplicar.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────────────────
        // CATÁLOGO COMPLETO DE PERMISOS MAESTRO ERP
        // ─────────────────────────────────────────────────
        $permissions = [
            // ── M0: Sucursales ──
            ['name' => 'ver-sucursales',               'name_public' => 'Ver Sucursales',                'description' => 'Permite ver el listado de sucursales.',                                'type' => 'Sucursales'],
            ['name' => 'crear-sucursales',             'name_public' => 'Crear Sucursales',              'description' => 'Permite crear nuevas sucursales.',                                      'type' => 'Sucursales'],
            ['name' => 'editar-sucursales',            'name_public' => 'Editar Sucursales',             'description' => 'Permite editar sucursales existentes.',                                  'type' => 'Sucursales'],
            ['name' => 'eliminar-sucursales',          'name_public' => 'Eliminar Sucursales',           'description' => 'Permite eliminar sucursales del sistema.',                               'type' => 'Sucursales'],
            ['name' => 'vista-global',                 'name_public' => 'Vista Global Multi-sucursal',    'description' => 'Permite ver datos consolidados de todas las sucursales.',                'type' => 'Sucursales'],

            // ── M1: Usuarios y Roles ──
            ['name' => 'ver-usuarios',                 'name_public' => 'Ver Usuarios',                  'description' => 'Permite ver el listado de usuarios.',                                   'type' => 'Usuarios y Roles'],
            ['name' => 'crear-usuarios',               'name_public' => 'Crear Usuarios',                'description' => 'Permite crear nuevos usuarios.',                                        'type' => 'Usuarios y Roles'],
            ['name' => 'editar-usuarios',              'name_public' => 'Editar Usuarios',               'description' => 'Permite editar usuarios existentes.',                                    'type' => 'Usuarios y Roles'],
            ['name' => 'eliminar-usuarios',            'name_public' => 'Eliminar Usuarios',             'description' => 'Permite eliminar usuarios del sistema.',                                 'type' => 'Usuarios y Roles'],
            ['name' => 'ver-roles',                    'name_public' => 'Ver Roles',                     'description' => 'Permite ver roles y sus permisos.',                                      'type' => 'Usuarios y Roles'],
            ['name' => 'crear-roles',                  'name_public' => 'Crear Roles',                   'description' => 'Permite crear nuevos roles.',                                           'type' => 'Usuarios y Roles'],
            ['name' => 'editar-roles',                 'name_public' => 'Editar Roles',                  'description' => 'Permite editar roles y asignar permisos.',                               'type' => 'Usuarios y Roles'],
            ['name' => 'eliminar-roles',               'name_public' => 'Eliminar Roles',                'description' => 'Permite eliminar roles del sistema.',                                    'type' => 'Usuarios y Roles'],

            // ── M2: Catálogo Global ──
            ['name' => 'ver-catalogo',                 'name_public' => 'Ver Módulo Catálogo',           'description' => 'Permite el acceso y navegación al módulo de catálogo de productos.',     'type' => 'Catálogo'],
            ['name' => 'editar-catalogo',              'name_public' => 'Editar Datos de Catálogo',      'description' => 'Permite crear y modificar productos, variantes y atributos.',             'type' => 'Catálogo'],
            ['name' => 'ver-categorias',               'name_public' => 'Ver Categorías',                'description' => 'Permite ver el árbol de categorías.',                                    'type' => 'Catálogo'],
            ['name' => 'crear-categorias',             'name_public' => 'Crear Categorías',              'description' => 'Permite crear categorías y subcategorías.',                              'type' => 'Catálogo'],
            ['name' => 'editar-categorias',            'name_public' => 'Editar Categorías',             'description' => 'Permite editar categorías existentes.',                                  'type' => 'Catálogo'],
            ['name' => 'eliminar-categorias',          'name_public' => 'Eliminar Categorías',           'description' => 'Permite eliminar categorías del sistema.',                               'type' => 'Catálogo'],
            ['name' => 'ver-marcas',                   'name_public' => 'Ver Marcas',                    'description' => 'Permite ver el listado de marcas comerciales.',                          'type' => 'Catálogo'],
            ['name' => 'crear-marcas',                 'name_public' => 'Crear Marcas',                  'description' => 'Permite registrar nuevas marcas de productos.',                          'type' => 'Catálogo'],
            ['name' => 'editar-marcas',                'name_public' => 'Editar Marcas',                 'description' => 'Permite modificar marcas existentes.',                                   'type' => 'Catálogo'],
            ['name' => 'eliminar-marcas',              'name_public' => 'Eliminar Marcas',               'description' => 'Permite eliminar marcas del catálogo.',                                  'type' => 'Catálogo'],
            ['name' => 'ver-atributos',                'name_public' => 'Ver Atributos',                 'description' => 'Permite ver atributos de variantes.',                                    'type' => 'Catálogo'],
            ['name' => 'crear-atributos',              'name_public' => 'Crear Atributos',               'description' => 'Permite crear atributos y valores.',                                     'type' => 'Catálogo'],
            ['name' => 'editar-atributos',             'name_public' => 'Editar Atributos',              'description' => 'Permite editar atributos existentes.',                                   'type' => 'Catálogo'],
            ['name' => 'eliminar-atributos',           'name_public' => 'Eliminar Atributos',            'description' => 'Permite eliminar atributos del sistema.',                                'type' => 'Catálogo'],

            // ── M3: Productos ──
            ['name' => 'ver-productos',                'name_public' => 'Ver Productos',                 'description' => 'Permite ver el catálogo de productos.',                                  'type' => 'Productos'],
            ['name' => 'crear-productos',              'name_public' => 'Crear Productos',               'description' => 'Permite crear nuevos productos.',                                        'type' => 'Productos'],
            ['name' => 'editar-productos',             'name_public' => 'Editar Productos',              'description' => 'Permite editar productos existentes.',                                   'type' => 'Productos'],
            ['name' => 'eliminar-productos',           'name_public' => 'Eliminar Productos',            'description' => 'Permite eliminar productos del sistema.',                                'type' => 'Productos'],
            ['name' => 'ver-costo-base',               'name_public' => 'Ver Costo Base',                'description' => 'Permite ver el costo base de productos (información privada).',          'type' => 'Productos'],

            // ── M4: Clientes y Crédito ──
            ['name' => 'ver-clientes',                 'name_public' => 'Ver Clientes',                  'description' => 'Permite ver el listado de clientes.',                                    'type' => 'Clientes'],
            ['name' => 'crear-clientes',               'name_public' => 'Crear Clientes',                'description' => 'Permite registrar nuevos clientes.',                                     'type' => 'Clientes'],
            ['name' => 'editar-clientes',              'name_public' => 'Editar Clientes',               'description' => 'Permite editar datos de clientes.',                                      'type' => 'Clientes'],
            ['name' => 'eliminar-clientes',            'name_public' => 'Eliminar Clientes',             'description' => 'Permite eliminar clientes del sistema.',                                 'type' => 'Clientes'],
            ['name' => 'gestionar-credito',            'name_public' => 'Gestionar Línea de Crédito',     'description' => 'Permite activar/desactivar crédito y definir límites a clientes.',       'type' => 'Clientes'],

            // ── M5: Precios, Comisiones y Descuentos ──
            ['name' => 'ver-precios',                  'name_public' => 'Ver Reglas de Precios',         'description' => 'Permite ver la configuración de precios y márgenes.',                    'type' => 'Precios y Finanzas'],
            ['name' => 'editar-precios',               'name_public' => 'Editar Precios y Márgenes',      'description' => 'Permite modificar márgenes y reglas de precio.',                         'type' => 'Precios y Finanzas'],
            ['name' => 'ver-comisiones',               'name_public' => 'Ver Comisiones',                'description' => 'Permite ver el reporte y matriz de comisiones.',                         'type' => 'Precios y Finanzas'],
            ['name' => 'editar-comisiones',            'name_public' => 'Configurar Reglas Comisión',    'description' => 'Permite modificar porcentajes y reglas de comisión.',                    'type' => 'Precios y Finanzas'],
            ['name' => 'liquidar-comisiones',          'name_public' => 'Liquidar Comisiones',           'description' => 'Permite marcar comisiones como pagadas individualmente o por lote.',      'type' => 'Precios y Finanzas'],
            ['name' => 'aplicar-descuentos',           'name_public' => 'Aplicar Descuentos en POS',     'description' => 'Permite aplicar descuentos en ventas.',                                  'type' => 'Precios y Finanzas'],
            ['name' => 'autorizar-descuentos',         'name_public' => 'Autorizar Descuentos Vía OTP',  'description' => 'Permite autorizar descuentos que superan los límites permitidos.',       'type' => 'Precios y Finanzas'],
            ['name' => 'ver-finanzas',                 'name_public' => 'Ver Métricas Financieras',      'description' => 'Permite ver reportes financieros y márgenes ejecutivos.',                'type' => 'Precios y Finanzas'],

            // ── M6: Inventario y Traspasos ──
            ['name' => 'ver-inventario',               'name_public' => 'Ver Inventario',                'description' => 'Permite ver existencias de stock por sucursal.',                         'type' => 'Inventario'],
            ['name' => 'gestionar-inventario',         'name_public' => 'Gestionar Ajustes Stock',       'description' => 'Permite realizar ajustes de existencias y definir stock mínimo.',        'type' => 'Inventario'],
            ['name' => 'ver-traspasos',                'name_public' => 'Ver Traspasos',                 'description' => 'Permite ver traspasos entre sucursales.',                                'type' => 'Inventario'],
            ['name' => 'crear-traspasos',              'name_public' => 'Crear Traspasos',               'description' => 'Permite crear solicitudes de traspaso de mercancía.',                    'type' => 'Inventario'],
            ['name' => 'autorizar-traspasos',          'name_public' => 'Autorizar Envíos Traspaso',     'description' => 'Permite autorizar y despachar traspasos en tránsito.',                   'type' => 'Inventario'],
            ['name' => 'recibir-traspasos',            'name_public' => 'Recibir Traspasos',             'description' => 'Permite confirmar recepción física de traspasos.',                       'type' => 'Inventario'],

            // ── M7: Punto de Venta (POS) y Ventas ──
            ['name' => 'acceder-pos',                  'name_public' => 'Acceder al POS',                'description' => 'Permite ingresar a la pantalla de venta en mostrador.',                  'type' => 'Punto de Venta'],
            ['name' => 'crear-ventas',                 'name_public' => 'Cobrar y Concretar Ventas',     'description' => 'Permite registrar cobros y ventas directas en el POS.',                  'type' => 'Punto de Venta'],
            ['name' => 'ver-ventas',                   'name_public' => 'Ver Historial de Ventas',       'description' => 'Permite consultar el historial de ventas y apartados.',                  'type' => 'Punto de Venta'],
            ['name' => 'anular-ventas',                'name_public' => 'Anular Ventas',                 'description' => 'Permite cancelar ventas y restituir existencias a inventario.',          'type' => 'Punto de Venta'],
            ['name' => 'registrar-abonos-ventas',      'name_public' => 'Registrar Abonos de Venta',     'description' => 'Permite registrar pagos y abonos a ventas en apartado.',                 'type' => 'Punto de Venta'],
            ['name' => 'imprimir-ventas',              'name_public' => 'Imprimir Comprobantes Venta',   'description' => 'Permite imprimir tickets, órdenes de venta y recibos de abono.',         'type' => 'Punto de Venta'],
            ['name' => 'ver-cotizaciones',             'name_public' => 'Ver Cotizaciones',              'description' => 'Permite acceder al listado e historial de cotizaciones.',                'type' => 'Punto de Venta'],
            ['name' => 'crear-cotizaciones',           'name_public' => 'Crear Cotizaciones',            'description' => 'Permite generar y guardar cotizaciones de prospectos.',                 'type' => 'Punto de Venta'],
            ['name' => 'convertir-cotizaciones',       'name_public' => 'Convertir Cotización a Venta',  'description' => 'Permite cargar una cotización vigente al carrito del POS para cobrar.',  'type' => 'Punto de Venta'],
            ['name' => 'imprimir-cotizaciones',        'name_public' => 'Imprimir Cotizaciones',         'description' => 'Permite imprimir comprobantes formales de cotización.',                  'type' => 'Punto de Venta'],

            // ── M8: Pedidos Especiales ──
            ['name' => 'ver-pedidos-especiales',       'name_public' => 'Ver Pedidos Especiales',        'description' => 'Permite consultar pedidos especiales de clientes.',                      'type' => 'Pedidos Especiales'],
            ['name' => 'crear-pedidos-especiales',     'name_public' => 'Crear Pedidos Especiales',      'description' => 'Permite levantar pedidos especiales desde el punto de venta.',           'type' => 'Pedidos Especiales'],
            ['name' => 'gestionar-pedidos-especiales', 'name_public' => 'Gestionar Flujo Pedidos',       'description' => 'Permite actualizar el estatus (ordenado, listo, entregado).',             'type' => 'Pedidos Especiales'],
            ['name' => 'recibir-pedidos-especiales',   'name_public' => 'Recibir Mercancía Pedidos',     'description' => 'Permite registrar la recepción física de piezas enviadas por proveedor.','type' => 'Pedidos Especiales'],
            ['name' => 'abonar-pedidos-especiales',    'name_public' => 'Cobrar Abonos Pedidos Esp.',    'description' => 'Permite recibir anticipos y liquidaciones de pedidos especiales.',       'type' => 'Pedidos Especiales'],
            ['name' => 'cancelar-pedidos-especiales',  'name_public' => 'Cancelar Pedidos Especiales',   'description' => 'Permite anular pedidos especiales y gestionar reembolsos.',              'type' => 'Pedidos Especiales'],
            ['name' => 'imprimir-pedidos-especiales',  'name_public' => 'Imprimir Pedidos Especiales',   'description' => 'Permite imprimir comprobantes y órdenes de entrega de pedidos.',         'type' => 'Pedidos Especiales'],

            // ── M9: Agenda y Envíos a Domicilio ──
            ['name' => 'ver-envios',                   'name_public' => 'Ver Agenda de Envíos',          'description' => 'Permite consultar el calendario de entregas programadas.',               'type' => 'Logística y Envíos'],
            ['name' => 'reprogramar-envios',           'name_public' => 'Reprogramar Envíos',            'description' => 'Permite cambiar fecha, hora o instrucciones de entrega en calendario.',  'type' => 'Logística y Envíos'],
            ['name' => 'cambiar-estado-envios',        'name_public' => 'Actualizar Estado Entrega',     'description' => 'Permite marcar envíos como En Ruta, Fallido o Entregado.',              'type' => 'Logística y Envíos'],
            ['name' => 'subir-evidencia-envios',       'name_public' => 'Subir Evidencia de Entrega',    'description' => 'Permite cargar fotos u orden de entrega firmada por el cliente.',        'type' => 'Logística y Envíos'],
            ['name' => 'imprimir-orden-entrega',       'name_public' => 'Imprimir Orden de Entrega',     'description' => 'Permite imprimir la orden de entrega física para el chofer.',            'type' => 'Logística y Envíos'],

            // ── M10: Cupones y Promociones ──
            ['name' => 'ver-cupones',                  'name_public' => 'Ver Cupones',                   'description' => 'Permite consultar el catálogo y estadísticas de cupones de descuento.',  'type' => 'Cupones'],
            ['name' => 'crear-cupones',                'name_public' => 'Crear Cupones',                 'description' => 'Permite registrar nuevos cupones de descuento y reglas de vigencia.',    'type' => 'Cupones'],
            ['name' => 'editar-cupones',               'name_public' => 'Editar Cupones',                'description' => 'Permite actualizar montos, vigencias y límites de cupones.',             'type' => 'Cupones'],
            ['name' => 'eliminar-cupones',             'name_public' => 'Eliminar / Pausar Cupones',     'description' => 'Permite pausar o eliminar cupones de descuento del sistema.',             'type' => 'Cupones'],

            // ── M11: Finanzas, Gastos y Tesorería ──
            ['name' => 'ver-gastos',                   'name_public' => 'Ver Gastos Empresariales',      'description' => 'Permite consultar el registro de egresos y gastos de sucursal.',         'type' => 'Finanzas y Gastos'],
            ['name' => 'crear-gastos',                 'name_public' => 'Registrar Gastos de Caja Chica','description' => 'Permite registrar salidas de dinero por gastos operativos o compras menores.', 'type' => 'Finanzas y Gastos'],
            ['name' => 'eliminar-gastos',              'name_public' => 'Eliminar Gastos',               'description' => 'Permite anular o eliminar registros de gastos.',                          'type' => 'Finanzas y Gastos'],
            ['name' => 'ver-terminales-pago',          'name_public' => 'Ver Terminales de Pago',        'description' => 'Permite ver las terminales bancarias configuradas.',                     'type' => 'Finanzas y Gastos'],
            ['name' => 'gestionar-terminales-pago',    'name_public' => 'Gestionar Terminales de Pago',  'description' => 'Permite dar de alta o editar terminales bancarias y PIN pads.',          'type' => 'Finanzas y Gastos'],

            // ── M12: Facturación CFDI ──
            ['name' => 'ver-facturas',                 'name_public' => 'Ver Facturas',                  'description' => 'Permite ver facturas electrónicas emitidas.',                             'type' => 'Facturación'],
            ['name' => 'crear-facturas',               'name_public' => 'Crear Facturas CFDI',           'description' => 'Permite generar CFDI desde ventas concretadas.',                         'type' => 'Facturación'],
            ['name' => 'cancelar-facturas',            'name_public' => 'Cancelar Facturas',             'description' => 'Permite solicitar cancelación de facturas ante el SAT.',                 'type' => 'Facturación'],

            // ── M13: Reportes ──
            ['name' => 'ver-reportes',                 'name_public' => 'Ver Reportes y KPIs',           'description' => 'Permite consultar reportes ejecutivos y tableros analíticos.',           'type' => 'Reportes'],
            ['name' => 'exportar-reportes',            'name_public' => 'Exportar Reportes',             'description' => 'Permite exportar datos a Excel y PDF.',                                  'type' => 'Reportes'],

            // ── M14: Configuración ──
            ['name' => 'ver-configuracion',            'name_public' => 'Ver Configuración',             'description' => 'Permite ver parámetros del sistema.',                                    'type' => 'Configuración'],
            ['name' => 'editar-configuracion',         'name_public' => 'Editar Configuración General',  'description' => 'Permite modificar configuración y políticas del sistema.',               'type' => 'Configuración'],
            ['name' => 'limpiar-base-datos',           'name_public' => 'Limpiar Base de Datos',         'description' => 'Permite purgar o resetear tablas transaccionales del ERP (Destructivo).', 'type' => 'Configuración'],

            // ── M15: Proveedores y Compras ──
            ['name' => 'ver-proveedores',              'name_public' => 'Ver Proveedores',               'description' => 'Permite ver el directorio de proveedores.',                              'type' => 'Compras'],
            ['name' => 'crear-proveedores',            'name_public' => 'Crear Proveedores',             'description' => 'Permite registrar nuevos proveedores.',                                  'type' => 'Compras'],
            ['name' => 'editar-proveedores',           'name_public' => 'Editar Proveedores',            'description' => 'Permite actualizar datos de proveedores.',                               'type' => 'Compras'],
            ['name' => 'eliminar-proveedores',         'name_public' => 'Eliminar Proveedores',          'description' => 'Permite eliminar proveedores del sistema.',                              'type' => 'Compras'],
            ['name' => 'ver-ordenes-compra',           'name_public' => 'Ver Órdenes de Compra',         'description' => 'Permite consultar órdenes de compra a proveedores.',                      'type' => 'Compras'],
            ['name' => 'crear-ordenes-compra',         'name_public' => 'Crear Órdenes de Compra',       'description' => 'Permite generar solicitudes de compra.',                                 'type' => 'Compras'],
            ['name' => 'autorizar-ordenes-compra',     'name_public' => 'Autorizar Órdenes de Compra',   'description' => 'Permite validar y aprobar compras a proveedores.',                       'type' => 'Compras'],
            ['name' => 'recibir-ordenes-compra',       'name_public' => 'Recibir Mercancía Compra',      'description' => 'Permite confirmar la entrada de mercancía al almacén.',                  'type' => 'Compras'],
            ['name' => 'pagar-ordenes-compra',         'name_public' => 'Registrar Pagos a Proveedor',   'description' => 'Permite registrar abonos y finiquitos de órdenes de compra.',            'type' => 'Compras'],
            ['name' => 'modificar-costos-compra',      'name_public' => 'Modificar Costos de Compra',    'description' => 'Permite ajustar el costo unitario de compra de ítems en orden.',         'type' => 'Compras'],
            ['name' => 'cancelar-ordenes-compra',      'name_public' => 'Cancelar Órdenes de Compra',    'description' => 'Permite anular órdenes de compra antes de ser recibidas.',               'type' => 'Compras'],

            // ── M16: Comunicación y Feed ──
            ['name' => 'ver-comunicados',              'name_public' => 'Ver Comunicados',               'description' => 'Permite consultar el muro de anuncios internos.',                        'type' => 'Comunicación'],
            ['name' => 'crear-comunicados',            'name_public' => 'Publicar Comunicados',          'description' => 'Permite publicar avisos internos a la organización.',                    'type' => 'Comunicación'],
            ['name' => 'editar-comunicados',           'name_public' => 'Editar Comunicados',            'description' => 'Permite modificar comunicados existentes.',                              'type' => 'Comunicación'],
            ['name' => 'eliminar-comunicados',         'name_public' => 'Eliminar Comunicados',          'description' => 'Permite eliminar comunicados del feed.',                                 'type' => 'Comunicación'],
            ['name' => 'ver-lecturas',                 'name_public' => 'Ver Auditoría de Lecturas',     'description' => 'Permite ver qué empleados leyeron cada anuncio.',                        'type' => 'Comunicación'],

            // ── M17: Auditoría del Sistema ──
            ['name' => 'ver-auditoria',                'name_public' => 'Ver Auditoría del Sistema',     'description' => 'Permite revisar la bitácora de actividad y cambios.',                    'type' => 'Auditoría'],

            // ── M18: Cortes de Caja y Arqueos ──
            ['name' => 'ver-caja',                     'name_public' => 'Ver Estado de Caja',            'description' => 'Permite consultar el turno activo y movimientos de caja.',               'type' => 'Caja'],
            ['name' => 'abrir-caja',                   'name_public' => 'Abrir Turno de Caja',           'description' => 'Permite realizar apertura con fondo inicial.',                           'type' => 'Caja'],
            ['name' => 'cerrar-caja',                  'name_public' => 'Cerrar Turno (Cortes X y Z)',   'description' => 'Permite realizar arqueo físico y cierre de turno.',                       'type' => 'Caja'],
            ['name' => 'registrar-movimientos-caja',   'name_public' => 'Entradas / Sangrías a Bóveda',  'description' => 'Permite registrar depósitos extraordinarios o retiros a bóveda.',        'type' => 'Caja'],
            ['name' => 'gestionar-pagos',              'name_public' => 'Configurar Métodos de Pago',    'description' => 'Permite configurar comisiones y condiciones de medios de pago.',          'type' => 'Caja'],
            ['name' => 'auditar-cortes-caja',          'name_public' => 'Auditar Todos los Cortes',      'description' => 'Permite consultar cortes de caja de todos los cajeros y sucursales.',    'type' => 'Caja'],
            ['name' => 'exportar-cortes-caja',         'name_public' => 'Exportar Cortes a Excel',       'description' => 'Permite descargar el archivo CSV/Excel consolidado de turnos.',          'type' => 'Caja'],
            ['name' => 'reportes-cortes-caja',         'name_public' => 'Reporte Ejecutivo de Caja',     'description' => 'Permite ver e imprimir el reporte ejecutivo consolidado de caja.',       'type' => 'Caja'],

            // ── M19: Documentos y Plantillas ──
            ['name' => 'ver-plantillas',               'name_public' => 'Ver Plantillas PDF',            'description' => 'Permite ver plantillas de documentos dinámicos.',                        'type' => 'Documentos'],
            ['name' => 'crear-plantillas',             'name_public' => 'Diseñar Plantillas PDF',        'description' => 'Permite crear o diseñar plantillas PDF.',                                'type' => 'Documentos'],
            ['name' => 'editar-plantillas',            'name_public' => 'Editar Plantillas PDF',         'description' => 'Permite modificar esquemas de diseño de plantillas.',                     'type' => 'Documentos'],
            ['name' => 'generar-documentos',           'name_public' => 'Generar Documentos PDF',        'description' => 'Permite emitir documentos en formato PDF desde plantillas.',             'type' => 'Documentos'],

            // ── M20: Asistente de Inteligencia Artificial ──
            ['name' => 'usar-asistente-ia',            'name_public' => 'Consultar Copiloto IA',         'description' => 'Permite interactuar con el asistente de IA y consultar información ERP.', 'type' => 'Inteligencia Artificial'],
        ];

        // Crear o actualizar permisos (idempotente: actualiza metadatos sin duplicar)
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                Arr::except($perm, 'name')
            );
        }

        // ─────────────────────────────────────────────────
        // ROLES Y ASIGNACIÓN DE PRIVILEGIOS
        // ─────────────────────────────────────────────────

        // 1. Super Admin: bypassa todo mediante Gate::before en AuthServiceProvider
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        // 2. Dirección: Acceso total explícito
        $direccion = Role::firstOrCreate(['name' => 'Dirección', 'guard_name' => 'web']);
        $direccion->syncPermissions(Permission::all());

        // 3. Gerente: Operación total de sucursal, personal, inventario, finanzas locales y compras
        $gerente = Role::firstOrCreate(['name' => 'Gerente', 'guard_name' => 'web']);
        $gerente->syncPermissions([
            // Sucursales
            'ver-sucursales', 'vista-global',
            // Usuarios
            'ver-usuarios', 'crear-usuarios', 'editar-usuarios', 'eliminar-usuarios',
            'ver-roles',
            // Catálogo Maestro
            'ver-catalogo', 'editar-catalogo',
            'ver-categorias', 'crear-categorias', 'editar-categorias', 'eliminar-categorias',
            'ver-marcas', 'crear-marcas', 'editar-marcas', 'eliminar-marcas',
            'ver-atributos', 'crear-atributos', 'editar-atributos', 'eliminar-atributos',
            // Productos
            'ver-productos', 'crear-productos', 'editar-productos', 'eliminar-productos',
            // Clientes y Crédito
            'ver-clientes', 'crear-clientes', 'editar-clientes', 'eliminar-clientes', 'gestionar-credito',
            // Precios, Comisiones y Descuentos
            'ver-precios', 'editar-precios',
            'ver-comisiones', 'editar-comisiones', 'liquidar-comisiones',
            'aplicar-descuentos', 'autorizar-descuentos',
            // Inventario y Traspasos
            'ver-inventario', 'gestionar-inventario',
            'ver-traspasos', 'crear-traspasos', 'autorizar-traspasos', 'recibir-traspasos',
            // POS y Ventas
            'acceder-pos', 'crear-ventas', 'ver-ventas', 'anular-ventas', 'registrar-abonos-ventas', 'imprimir-ventas',
            'ver-cotizaciones', 'crear-cotizaciones', 'convertir-cotizaciones', 'imprimir-cotizaciones',
            // Pedidos Especiales
            'ver-pedidos-especiales', 'crear-pedidos-especiales', 'gestionar-pedidos-especiales',
            'recibir-pedidos-especiales', 'abonar-pedidos-especiales', 'cancelar-pedidos-especiales', 'imprimir-pedidos-especiales',
            // Agenda de Envíos
            'ver-envios', 'reprogramar-envios', 'cambiar-estado-envios', 'subir-evidencia-envios', 'imprimir-orden-entrega',
            // Cupones
            'ver-cupones', 'crear-cupones', 'editar-cupones', 'eliminar-cupones',
            // Finanzas y Gastos
            'ver-gastos', 'crear-gastos', 'ver-terminales-pago',
            // Facturación y Reportes
            'ver-facturas', 'crear-facturas',
            'ver-reportes', 'exportar-reportes',
            // Configuración
            'ver-configuracion',
            // Proveedores y Compras
            'ver-proveedores', 'crear-proveedores', 'editar-proveedores',
            'ver-ordenes-compra', 'crear-ordenes-compra', 'autorizar-ordenes-compra', 'recibir-ordenes-compra', 'pagar-ordenes-compra', 'cancelar-ordenes-compra',
            // Comunicación
            'ver-comunicados', 'crear-comunicados', 'editar-comunicados', 'eliminar-comunicados', 'ver-lecturas',
            // Auditoría
            'ver-auditoria',
            // Caja
            'ver-caja', 'abrir-caja', 'cerrar-caja', 'registrar-movimientos-caja',
            'auditar-cortes-caja', 'exportar-cortes-caja', 'reportes-cortes-caja',
            // Documentos e Inteligencia Artificial
            'ver-plantillas', 'generar-documentos', 'usar-asistente-ia',
        ]);

        // 4. Cajero: Operación diaria de caja, cobros, gastos de caja chica, cortes y atención a clientes
        $cajero = Role::firstOrCreate(['name' => 'Cajero', 'guard_name' => 'web']);
        $cajero->syncPermissions([
            // Catálogo (consulta de productos y existencias)
            'ver-catalogo', 'ver-productos', 'ver-categorias', 'ver-marcas', 'ver-atributos',
            // Clientes (atención y registro de datos)
            'ver-clientes', 'crear-clientes', 'editar-clientes',
            // POS y Cobros
            'acceder-pos', 'crear-ventas', 'ver-ventas', 'registrar-abonos-ventas', 'imprimir-ventas', 'aplicar-descuentos',
            // Cotizaciones
            'ver-cotizaciones', 'crear-cotizaciones', 'convertir-cotizaciones', 'imprimir-cotizaciones',
            // Pedidos Especiales (levantar y cobrar anticipos)
            'ver-pedidos-especiales', 'crear-pedidos-especiales', 'abonar-pedidos-especiales', 'imprimir-pedidos-especiales',
            // Caja, Turnos y Movimientos
            'ver-caja', 'abrir-caja', 'cerrar-caja', 'registrar-movimientos-caja',
            // Gastos de Caja Chica
            'ver-gastos', 'crear-gastos',
            // Inventario y Envíos (consulta y comprobantes)
            'ver-inventario', 'ver-envios', 'imprimir-orden-entrega',
            // Comunicación y Documentos
            'ver-comunicados', 'generar-documentos',
        ]);

        // 5. Vendedor: Ventas, cotizaciones, clientes, pedidos especiales, caja chica y consulta comercial
        $vendedor = Role::firstOrCreate(['name' => 'Vendedor', 'guard_name' => 'web']);
        $vendedor->syncPermissions([
            // Catálogo / Productos (solo lectura)
            'ver-catalogo', 'ver-categorias', 'ver-marcas', 'ver-atributos', 'ver-productos',
            // Clientes
            'ver-clientes', 'crear-clientes', 'editar-clientes',
            // Precios y Descuentos
            'aplicar-descuentos',
            // Inventario (solo ver)
            'ver-inventario',
            // POS y Cobros
            'acceder-pos', 'crear-ventas', 'ver-ventas', 'registrar-abonos-ventas', 'imprimir-ventas',
            // Cotizaciones
            'ver-cotizaciones', 'crear-cotizaciones', 'convertir-cotizaciones', 'imprimir-cotizaciones',
            // Pedidos Especiales
            'ver-pedidos-especiales', 'crear-pedidos-especiales', 'abonar-pedidos-especiales', 'imprimir-pedidos-especiales',
            // Caja y Gastos de Caja Chica (cuando el vendedor opera en caja)
            'ver-caja', 'abrir-caja', 'cerrar-caja', 'registrar-movimientos-caja',
            'ver-gastos', 'crear-gastos',
            // Cupones y Comisiones
            'ver-cupones', 'ver-comisiones',
            // Envíos
            'ver-envios', 'imprimir-orden-entrega',
            // Comunicación y Documentos
            'ver-comunicados', 'generar-documentos',
        ]);

        // 6. Operativo: Bodega, almacén, traspasos, recepción de mercancías y logística
        $operativo = Role::firstOrCreate(['name' => 'Operativo', 'guard_name' => 'web']);
        $operativo->syncPermissions([
            // Catálogo (consulta de productos)
            'ver-catalogo', 'ver-productos',
            // Inventario y Traspasos
            'ver-inventario', 'gestionar-inventario',
            'ver-traspasos', 'crear-traspasos', 'recibir-traspasos',
            // Proveedores y Compras (recepción de mercancía en muelle)
            'ver-proveedores', 'recibir-ordenes-compra',
            // Pedidos Especiales (recepción de piezas recibidas de proveedor)
            'ver-pedidos-especiales', 'gestionar-pedidos-especiales', 'recibir-pedidos-especiales',
            // Agenda de Envíos y Choferes
            'ver-envios', 'cambiar-estado-envios', 'subir-evidencia-envios', 'imprimir-orden-entrega',
            // Comunicación
            'ver-comunicados',
        ]);

        // 7. Cliente: Portal y acceso de clientes
        $cliente = Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
        $cliente->syncPermissions([
            'ver-comunicados',
        ]);

        // 8. Proveedor: Portal y acceso de proveedores
        $proveedor = Role::firstOrCreate(['name' => 'Proveedor', 'guard_name' => 'web']);
        $proveedor->syncPermissions([
            'ver-comunicados',
        ]);

        // Auto-sincronizar roles a usuarios con expedientes existentes
        foreach (\App\Models\Customer::whereNotNull('user_id')->get() as $c) {
            if ($c->user && !$c->user->hasRole('Cliente')) {
                $c->user->assignRole($cliente);
            }
        }
        foreach (\App\Models\Supplier::whereNotNull('user_id')->get() as $s) {
            if ($s->user && !$s->user->hasRole('Proveedor')) {
                $s->user->assignRole($proveedor);
            }
        }

        $this->command->info('✅ ' . count($permissions) . ' permisos y ' . Role::count() . ' roles sincronizados correctamente.');
    }
}
