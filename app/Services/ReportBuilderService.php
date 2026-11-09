<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleCommission;
use App\Models\SpecialOrder;
use App\Models\SpecialOrderPayment;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportBuilderService
{
    /**
     * Determina si el usuario autenticado puede visualizar costos base y márgenes de utilidad.
     */
    public function canViewFinancialCosts(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return false;
        }

        return $user->can('ver-costo-base')
            || $user->can('ver-finanzas')
            || $user->hasRole(['Super Admin', 'Dirección', 'Gerente']);
    }

    /**
     * Catálogo de los 7 Dominios Analíticos del Centro de Reportes BI.
     */
    public function getDomainsCatalog(?User $user = null): array
    {
        $canSeeCosts = $this->canViewFinancialCosts($user);

        $domains = [
            'sales' => [
                'key'         => 'sales',
                'name'        => 'Ventas e Ingresos Consolidados',
                'short_name'  => 'Ventas (POS + Especiales)',
                'icon'        => 'ri-shopping-bag-3-line',
                'color'       => 'primary',
                'description' => 'Auditoría integral de Ventas POS, Apartados y Pedidos Especiales con desglose de Venta Neta (Subtotal - Descuentos), impuestos, cobros, saldos y utilidad bruta.',
                'columns'     => [
                    'date'             => ['label' => 'Fecha / Hora',       'type' => 'text',     'default' => true],
                    'folio'            => ['label' => 'Folio',              'type' => 'badge',    'default' => true],
                    'operation_type'   => ['label' => 'Tipo Operación',     'type' => 'badge',    'default' => true],
                    'store'            => ['label' => 'Sucursal',           'type' => 'text',     'default' => true],
                    'customer'         => ['label' => 'Cliente',            'type' => 'text',     'default' => true],
                    'seller'           => ['label' => 'Vendedor',           'type' => 'text',     'default' => true],
                    'cashier'          => ['label' => 'Cajero',             'type' => 'text',     'default' => false],
                    'status'           => ['label' => 'Estado',             'type' => 'status',   'default' => true],
                    'subtotal'         => ['label' => 'Subtotal',           'type' => 'currency', 'default' => false, 'sum' => true],
                    'discounts'        => ['label' => 'Descuentos / Cupón', 'type' => 'currency', 'default' => false, 'sum' => true],
                    'net_amount'       => ['label' => 'Venta Neta',         'type' => 'currency', 'default' => true,  'sum' => true],
                    'taxes'            => ['label' => 'Impuestos (IVA)',    'type' => 'currency', 'default' => false, 'sum' => true],
                    'total'            => ['label' => 'Total Venta',        'type' => 'currency', 'default' => true,  'sum' => true],
                    'amount_paid'      => ['label' => 'Monto Cobrado',      'type' => 'currency', 'default' => true,  'sum' => true],
                    'balance'          => ['label' => 'Saldo Deudor',       'type' => 'currency', 'default' => true,  'sum' => true],
                    'base_cost'        => ['label' => 'Costo Base',         'type' => 'currency', 'default' => false, 'sum' => true, 'requires_cost' => true],
                    'gross_profit'     => ['label' => 'Utilidad Bruta',     'type' => 'currency', 'default' => false, 'sum' => true, 'requires_cost' => true],
                    'gross_margin_pct' => ['label' => '% Margen Bruto',     'type' => 'percent',  'default' => false, 'requires_cost' => true],
                ],
                'group_by' => [
                    'none'       => 'Sin agrupar (Detalle por Operación)',
                    'store'      => 'Agrupar por Sucursal',
                    'seller'     => 'Agrupar por Vendedor',
                    'type'       => 'Agrupar por Tipo de Operación (POS vs Especial)',
                    'date_day'   => 'Agrupar por Día',
                    'date_month' => 'Agrupar por Mes',
                    'status'     => 'Agrupar por Estado',
                ],
            ],

            'collections' => [
                'key'         => 'collections',
                'name'        => 'Flujo de Cobranza y Comisiones de Terminal',
                'short_name'  => 'Cobros y Terminales',
                'icon'        => 'ri-bank-card-2-line',
                'color'       => 'success',
                'description' => 'Desglose de pagos recibidos de clientes (POS y Anticipos de Pedidos Especiales), métodos de pago, terminales bancarias, MSI y comisiones retenidas.',
                'columns'     => [
                    'date'           => ['label' => 'Fecha / Hora Cobro',  'type' => 'text',     'default' => true],
                    'folio'          => ['label' => 'Folio Operación',     'type' => 'badge',    'default' => true],
                    'origin_type'    => ['label' => 'Origen',              'type' => 'badge',    'default' => true],
                    'store'          => ['label' => 'Sucursal',            'type' => 'text',     'default' => true],
                    'customer'       => ['label' => 'Cliente',             'type' => 'text',     'default' => true],
                    'cashier'        => ['label' => 'Cajero / Registró',   'type' => 'text',     'default' => true],
                    'payment_method' => ['label' => 'Método de Pago',      'type' => 'text',     'default' => true],
                    'terminal'       => ['label' => 'Terminal Bancaria',   'type' => 'text',     'default' => true],
                    'msi'            => ['label' => 'Plazo MSI',           'type' => 'text',     'default' => false],
                    'gross_amount'   => ['label' => 'Monto Cobrado Bruto', 'type' => 'currency', 'default' => true, 'sum' => true],
                    'commission'     => ['label' => 'Comisión Terminal',   'type' => 'currency', 'default' => true, 'sum' => true],
                    'net_amount'     => ['label' => 'Ingreso Neto Real',   'type' => 'currency', 'default' => true, 'sum' => true],
                ],
                'group_by' => [
                    'none'     => 'Sin agrupar (Detalle por Pago)',
                    'method'   => 'Agrupar por Método de Pago',
                    'terminal' => 'Agrupar por Terminal Bancaria',
                    'store'    => 'Agrupar por Sucursal',
                    'cashier'  => 'Agrupar por Cajero',
                    'origin'   => 'Agrupar por Origen (POS vs Pedido Especial)',
                    'date_day' => 'Agrupar por Día',
                ],
            ],

            'receivables' => [
                'key'         => 'receivables',
                'name'        => 'Cartera de Clientes (CxC) y Antigüedad de Saldos',
                'short_name'  => 'Cartera Clientes (CxC)',
                'icon'        => 'ri-user-received-2-line',
                'color'       => 'warning',
                'description' => 'Auditoría de cuentas por cobrar activas en Apartados POS y Pedidos Especiales con clasificación por tramos de antigüedad (1-15, 16-30, 31-60 y +60 días).',
                'columns'     => [
                    'date'           => ['label' => 'Fecha Emisión',       'type' => 'text',     'default' => true],
                    'folio'          => ['label' => 'Folio',               'type' => 'badge',    'default' => true],
                    'operation_type' => ['label' => 'Tipo Operación',      'type' => 'badge',    'default' => true],
                    'store'          => ['label' => 'Sucursal',            'type' => 'text',     'default' => true],
                    'customer'       => ['label' => 'Cliente Deudor',      'type' => 'text',     'default' => true],
                    'customer_phone' => ['label' => 'Teléfono',            'type' => 'text',     'default' => true],
                    'seller'         => ['label' => 'Vendedor',            'type' => 'text',     'default' => false],
                    'days_elapsed'   => ['label' => 'Días Antigüedad',     'type' => 'integer',  'default' => true],
                    'aging_bucket'   => ['label' => 'Tramo Antigüedad',    'type' => 'status',   'default' => true],
                    'total'          => ['label' => 'Total Operación',     'type' => 'currency', 'default' => true, 'sum' => true],
                    'amount_paid'    => ['label' => 'Total Abonado',       'type' => 'currency', 'default' => true, 'sum' => true],
                    'paid_pct'       => ['label' => '% Liquidado',         'type' => 'percent',  'default' => false],
                    'balance'        => ['label' => 'Saldo Deudor Activo', 'type' => 'currency', 'default' => true, 'sum' => true],
                ],
                'group_by' => [
                    'none'     => 'Sin agrupar (Detalle por Cuenta)',
                    'aging'    => 'Agrupar por Tramo de Antigüedad (Aging)',
                    'customer' => 'Agrupar por Cliente Deudor',
                    'store'    => 'Agrupar por Sucursal',
                    'seller'   => 'Agrupar por Vendedor',
                ],
            ],

            'inventory' => [
                'key'         => 'inventory',
                'name'        => 'Inventario, Valoración Financiera y Stock Crítico',
                'short_name'  => 'Inventario y Valoración',
                'icon'        => 'ri-archive-drawer-line',
                'color'       => 'info',
                'description' => 'Existencias físicas, reservadas y disponibles por sucursal y bodega, con valoración a costo vs valor comercial de venta y alertas de punto de reorden.',
                'columns'     => [
                    'sku'                => ['label' => 'SKU',                 'type' => 'badge',    'default' => true],
                    'product_name'       => ['label' => 'Producto / Variante', 'type' => 'text',     'default' => true],
                    'brand'              => ['label' => 'Marca',               'type' => 'text',     'default' => false],
                    'supplier'           => ['label' => 'Proveedor',           'type' => 'text',     'default' => false],
                    'store'              => ['label' => 'Sucursal',            'type' => 'text',     'default' => true],
                    'warehouse'          => ['label' => 'Bodega',              'type' => 'text',     'default' => true],
                    'quantity'           => ['label' => 'Stock Físico',        'type' => 'integer',  'default' => true,  'sum' => true],
                    'reserved'           => ['label' => 'Reservado',           'type' => 'integer',  'default' => true,  'sum' => true],
                    'available'          => ['label' => 'Disponible',          'type' => 'integer',  'default' => true,  'sum' => true],
                    'min_stock'          => ['label' => 'Stock Mínimo',        'type' => 'integer',  'default' => true],
                    'stock_status'       => ['label' => 'Estado Stock',        'type' => 'status',   'default' => true],
                    'unit_cost'          => ['label' => 'Costo Unitario',      'type' => 'currency', 'default' => false, 'requires_cost' => true],
                    'total_cost_value'   => ['label' => 'Valor Total Costo',   'type' => 'currency', 'default' => true,  'sum' => true, 'requires_cost' => true],
                    'unit_price'         => ['label' => 'Precio Comercial',    'type' => 'currency', 'default' => false],
                    'total_retail_value' => ['label' => 'Valor Comercial',     'type' => 'currency', 'default' => true,  'sum' => true],
                    'potential_profit'   => ['label' => 'Utilidad Potencial',  'type' => 'currency', 'default' => false, 'sum' => true, 'requires_cost' => true],
                ],
                'group_by' => [
                    'none'         => 'Sin agrupar (Detalle por SKU / Bodega)',
                    'store'        => 'Agrupar por Sucursal',
                    'brand'        => 'Agrupar por Marca',
                    'supplier'     => 'Agrupar por Proveedor',
                    'stock_status' => 'Agrupar por Estado de Stock',
                ],
            ],

            'purchases' => [
                'key'         => 'purchases',
                'name'        => 'Compras y Cuentas por Pagar a Proveedores (CxP)',
                'short_name'  => 'Compras y Proveedores (CxP)',
                'icon'        => 'ri-truck-line',
                'color'       => 'secondary',
                'description' => 'Control de Órdenes de Compra emitidas, folios RV, estado de recepción en almacén, pagos efectuados a proveedores y deuda activa por liquidar.',
                'columns'     => [
                    'issued_date'     => ['label' => 'Fecha Emisión',      'type' => 'text',     'default' => true],
                    'folio'           => ['label' => 'Folio OC',           'type' => 'badge',    'default' => true],
                    'rv_folio'        => ['label' => 'Folio RV / Factura', 'type' => 'text',     'default' => true],
                    'supplier'        => ['label' => 'Proveedor',          'type' => 'text',     'default' => true],
                    'store'           => ['label' => 'Sucursal Destino',   'type' => 'text',     'default' => true],
                    'expected_date'   => ['label' => 'Fecha Esperada',     'type' => 'text',     'default' => false],
                    'status'          => ['label' => 'Estado Recepción',   'type' => 'status',   'default' => true],
                    'payment_status'  => ['label' => 'Estado de Pago',     'type' => 'status',   'default' => true],
                    'gross_amount'    => ['label' => 'Importe Bruto',      'type' => 'currency', 'default' => false, 'sum' => true],
                    'discount_amount' => ['label' => 'Descuentos',         'type' => 'currency', 'default' => false, 'sum' => true],
                    'net_amount'      => ['label' => 'Total Neto OC',      'type' => 'currency', 'default' => true,  'sum' => true],
                    'amount_paid'     => ['label' => 'Monto Pagado',       'type' => 'currency', 'default' => true,  'sum' => true],
                    'pending_balance' => ['label' => 'Saldo por Pagar',    'type' => 'currency', 'default' => true,  'sum' => true],
                ],
                'group_by' => [
                    'none'           => 'Sin agrupar (Detalle por Orden de Compra)',
                    'supplier'       => 'Agrupar por Proveedor',
                    'store'          => 'Agrupar por Sucursal',
                    'status'         => 'Agrupar por Estado de Recepción',
                    'payment_status' => 'Agrupar por Estado de Pago',
                ],
            ],

            'expenses' => [
                'key'         => 'expenses',
                'name'        => 'Gastos Empresariales y Egresos Operativos',
                'short_name'  => 'Gastos Empresariales',
                'icon'        => 'ri-wallet-3-line',
                'color'       => 'danger',
                'description' => 'Auditoría detallada de gastos empresariales por sucursal, categoría operativa, pagos a proveedores y egresos de caja chica.',
                'columns'     => [
                    'date'          => ['label' => 'Fecha Gasto',     'type' => 'text',     'default' => true],
                    'folio'         => ['label' => 'ID Registro',     'type' => 'badge',    'default' => true],
                    'concept'       => ['label' => 'Concepto',        'type' => 'text',     'default' => true],
                    'category'      => ['label' => 'Categoría',       'type' => 'status',   'default' => true],
                    'store'         => ['label' => 'Sucursal',        'type' => 'text',     'default' => true],
                    'registered_by' => ['label' => 'Registrado Por',  'type' => 'text',     'default' => true],
                    'notes'         => ['label' => 'Observaciones',   'type' => 'text',     'default' => true],
                    'amount'        => ['label' => 'Monto del Gasto', 'type' => 'currency', 'default' => true, 'sum' => true],
                ],
                'group_by' => [
                    'none'       => 'Sin agrupar (Detalle por Gasto)',
                    'category'   => 'Agrupar por Categoría de Gasto',
                    'store'      => 'Agrupar por Sucursal',
                    'date_month' => 'Agrupar por Mes',
                    'date_day'   => 'Agrupar por Día',
                ],
            ],

            'sellers' => [
                'key'         => 'sellers',
                'name'        => 'Productividad Comercial, Comisiones y Entregas',
                'short_name'  => 'Vendedores, Comisiones y Entregas',
                'icon'        => 'ri-medal-line',
                'color'       => 'dark',
                'description' => 'Evaluación de desempeño por vendedor, comisiones devengadas/pagadas y control logístico de pedidos pendientes de entrega con semáforo de días restantes.',
                'columns'     => [
                    'date'              => ['label' => 'Fecha Operación',     'type' => 'text',     'default' => true],
                    'folio'             => ['label' => 'Folio',               'type' => 'badge',    'default' => true],
                    'operation_type'    => ['label' => 'Tipo Operación',      'type' => 'badge',    'default' => true],
                    'seller'            => ['label' => 'Vendedor',            'type' => 'text',     'default' => true],
                    'store'             => ['label' => 'Sucursal',            'type' => 'text',     'default' => true],
                    'customer'          => ['label' => 'Cliente / Receptor',  'type' => 'text',     'default' => true],
                    'scheduled_date'    => ['label' => 'Fecha Entrega Prog.', 'type' => 'text',     'default' => true],
                    'days_label'        => ['label' => 'Días Restantes',      'type' => 'status',   'default' => true],
                    'logistics_status'  => ['label' => 'Estado Logístico',    'type' => 'status',   'default' => true],
                    'sale_amount'       => ['label' => 'Monto Operación',     'type' => 'currency', 'default' => true, 'sum' => true],
                    'balance'           => ['label' => 'Saldo Pendiente',     'type' => 'currency', 'default' => true, 'sum' => true],
                    'commission_amount' => ['label' => 'Comisión Generada',   'type' => 'currency', 'default' => true, 'sum' => true],
                    'commission_status' => ['label' => 'Estado Comisión',     'type' => 'status',   'default' => false],
                ],
                'group_by' => [
                    'none'             => 'Sin agrupar (Detalle Operativo / Logístico)',
                    'seller'           => 'Agrupar por Vendedor (Productividad y Comisiones)',
                    'store'            => 'Agrupar por Sucursal',
                    'logistics_status' => 'Agrupar por Estado Logístico de Entrega',
                ],
            ],
        ];

        // Filtrar columnas que requieren permiso de costos si el usuario no lo posee
        if (!$canSeeCosts) {
            foreach ($domains as $dKey => &$domain) {
                foreach ($domain['columns'] as $cKey => $col) {
                    if (!empty($col['requires_cost'])) {
                        unset($domain['columns'][$cKey]);
                    }
                }
            }
            unset($domain);
        }

        return $domains;
    }

    /**
     * Catálogo de Plantillas Rápidas de 1-Clic (Quick Presets) alineadas con el Dashboard y Dirección.
     */
    public function getQuickPresets(): array
    {
        return [
            'monthly_sales' => [
                'key'         => 'monthly_sales',
                'title'       => 'Ventas del Mes (POS + Especiales)',
                'subtitle'    => 'Total vendido, Venta Neta y Saldos Deudores',
                'icon'        => 'ri-funds-box-line',
                'color'       => 'primary',
                'domain'      => 'sales',
                'group_by'    => 'none',
                'date_preset' => 'this_month',
                'params'      => ['domain' => 'sales', 'group_by' => 'none', 'date_preset' => 'this_month'],
            ],
            'daily_collections' => [
                'key'         => 'daily_collections',
                'title'       => 'Cobros y Comisiones de Terminal',
                'subtitle'    => 'Pagos de clientes, métodos y neto real',
                'icon'        => 'ri-bank-card-line',
                'color'       => 'success',
                'domain'      => 'collections',
                'group_by'    => 'none',
                'date_preset' => 'this_month',
                'params'      => ['domain' => 'collections', 'group_by' => 'none', 'date_preset' => 'this_month'],
            ],
            'pending_deliveries' => [
                'key'         => 'pending_deliveries',
                'title'       => 'Pedidos por Entregar y Días Restantes',
                'subtitle'    => 'Envíos POS y Pedidos Especiales activos',
                'icon'        => 'ri-truck-line',
                'color'       => 'warning',
                'domain'      => 'sellers',
                'group_by'    => 'none',
                'date_preset' => 'this_year',
                'params'      => ['domain' => 'sellers', 'group_by' => 'none', 'status' => 'pending_delivery', 'date_preset' => 'this_year'],
            ],
            'monthly_expenses' => [
                'key'         => 'monthly_expenses',
                'title'       => 'Gastos Empresariales del Mes',
                'subtitle'    => 'Egresos operativos, caja y proveedores',
                'icon'        => 'ri-wallet-3-line',
                'color'       => 'danger',
                'domain'      => 'expenses',
                'group_by'    => 'none',
                'date_preset' => 'this_month',
                'params'      => ['domain' => 'expenses', 'group_by' => 'none', 'date_preset' => 'this_month'],
            ],
            'aging_receivables' => [
                'key'         => 'aging_receivables',
                'title'       => 'Cartera de Clientes (Antigüedad CxC)',
                'subtitle'    => 'Saldos pendientes por tramos de mora',
                'icon'        => 'ri-hand-coin-line',
                'color'       => 'info',
                'domain'      => 'receivables',
                'group_by'    => 'none',
                'date_preset' => 'all_time',
                'params'      => ['domain' => 'receivables', 'group_by' => 'none', 'date_preset' => 'all_time'],
            ],
            'inventory_valuation' => [
                'key'         => 'inventory_valuation',
                'title'       => 'Valoración de Inventario y Alertas',
                'subtitle'    => 'Existencias a costo vs valor comercial',
                'icon'        => 'ri-stack-line',
                'color'       => 'secondary',
                'domain'      => 'inventory',
                'group_by'    => 'none',
                'date_preset' => 'this_month',
                'params'      => ['domain' => 'inventory', 'group_by' => 'none', 'date_preset' => 'this_month'],
            ],
            'supplier_payables' => [
                'key'         => 'supplier_payables',
                'title'       => 'Deuda con Proveedores (CxP)',
                'subtitle'    => 'Órdenes de compra y saldos por pagar',
                'icon'        => 'ri-file-list-3-line',
                'color'       => 'dark',
                'domain'      => 'purchases',
                'group_by'    => 'none',
                'date_preset' => 'this_year',
                'params'      => ['domain' => 'purchases', 'group_by' => 'none', 'date_preset' => 'this_year'],
            ],
            'seller_commissions' => [
                'key'         => 'seller_commissions',
                'title'       => 'Productividad y Comisiones Vendedores',
                'subtitle'    => 'Ranking comercial, conversión y comisiones',
                'icon'        => 'ri-award-line',
                'color'       => 'primary',
                'domain'      => 'sellers',
                'group_by'    => 'seller',
                'date_preset' => 'this_month',
                'params'      => ['domain' => 'sellers', 'group_by' => 'seller', 'date_preset' => 'this_month'],
            ],
        ];
    }

    /**
     * Obtiene los catálogos para los selectores de filtros del constructor de reportes.
     */
    public function getFilterOptions(?int $forcedStoreId = null): array
    {
        $stores = Store::where('status', true)
            ->when($forcedStoreId, fn($q) => $q->where('id', $forcedStoreId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $sellers = User::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = Brand::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'stores'          => $stores,
            'sellers'         => $sellers,
            'suppliers'       => $suppliers,
            'payment_methods' => $paymentMethods,
            'brands'          => $brands,
        ];
    }

    /**
     * Resuelve el rango de fechas a partir de `date_preset`, `month`/`year` o `date_from`/`date_to`.
     */
    public function resolveDateRange(array $filters): array
    {
        $preset = $filters['date_preset'] ?? 'this_month';

        if (!empty($filters['month']) && !empty($filters['year']) && empty($filters['date_from'])) {
            $m     = max(1, min(12, (int) $filters['month']));
            $y     = max(2020, min(2035, (int) $filters['year']));
            $start = Carbon::createFromDate($y, $m, 1)->startOfMonth();
            $end   = (clone $start)->endOfMonth();
            return [
                'start'  => $start,
                'end'    => $end,
                'preset' => 'custom',
                'label'  => $start->format('d/m/Y') . ' al ' . $end->format('d/m/Y'),
            ];
        }

        $today = Carbon::today();

        switch ($preset) {
            case 'today':
                $start = $today->copy()->startOfDay();
                $end   = $today->copy()->endOfDay();
                $label = 'Hoy (' . $start->format('d/m/Y') . ')';
                break;

            case 'yesterday':
                $start = $today->copy()->subDay()->startOfDay();
                $end   = $today->copy()->subDay()->endOfDay();
                $label = 'Ayer (' . $start->format('d/m/Y') . ')';
                break;

            case 'last_7_days':
                $start = $today->copy()->subDays(6)->startOfDay();
                $end   = $today->copy()->endOfDay();
                $label = 'Últimos 7 días (' . $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y') . ')';
                break;

            case 'last_month':
                $start = $today->copy()->subMonth()->startOfMonth();
                $end   = $today->copy()->subMonth()->endOfMonth();
                $label = 'Mes Anterior (' . $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y') . ')';
                break;

            case 'this_quarter':
                $start = $today->copy()->firstOfQuarter()->startOfDay();
                $end   = $today->copy()->lastOfQuarter()->endOfDay();
                $label = 'Trimestre Actual (' . $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y') . ')';
                break;

            case 'this_year':
                $start = $today->copy()->startOfYear();
                $end   = $today->copy()->endOfYear();
                $label = 'Año en Curso ' . $today->year;
                break;

            case 'all_time':
                $start = Carbon::createFromDate(2020, 1, 1)->startOfDay();
                $end   = $today->copy()->endOfYear();
                $label = 'Histórico Completo';
                break;

            case 'custom':
                $start = !empty($filters['date_from'])
                    ? Carbon::parse($filters['date_from'])->startOfDay()
                    : $today->copy()->startOfMonth();
                $end = !empty($filters['date_to'])
                    ? Carbon::parse($filters['date_to'])->endOfDay()
                    : $today->copy()->endOfMonth();
                if ($start->gt($end)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                $label = $start->format('d/m/Y') . ' al ' . $end->format('d/m/Y');
                break;

            case 'this_month':
            default:
                $preset = 'this_month';
                $start  = $today->copy()->startOfMonth();
                $end    = $today->copy()->endOfMonth();
                $label  = 'Mes Actual (' . $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y') . ')';
                break;
        }

        return [
            'start'  => $start,
            'end'    => $end,
            'preset' => $preset,
            'label'  => $label,
        ];
    }

    /**
     * Ejecuta el motor de reportes para el dominio, filtros, columnas y agrupación solicitados.
     */
    public function buildReport(array $filters, ?User $user = null): array
    {
        $user    = $user ?: auth()->user();
        $catalog = $this->getDomainsCatalog($user);

        $domainKey = $filters['domain'] ?? 'sales';
        if (!isset($catalog[$domainKey])) {
            $domainKey = 'sales';
        }

        $domainMeta = $catalog[$domainKey];
        $dateRange  = $this->resolveDateRange($filters);

        // Gobernanza de sucursal: respetar aislamiento si no tiene vista-global
        $storeId = null;
        if (!$user || (!$user->can('vista-global') && !$user->hasRole(['Super Admin', 'Dirección']))) {
            $storeId = current_store_id();
        } elseif (!empty($filters['store_id']) && $filters['store_id'] !== 'all') {
            $storeId = (int) $filters['store_id'];
        } elseif (!is_global_view() && current_store_id() && empty($filters['store_id'])) {
            $storeId = (int) current_store_id();
        }

        $groupBy = $filters['group_by'] ?? 'none';
        if (!isset($domainMeta['group_by'][$groupBy])) {
            $groupBy = 'none';
        }

        $normFilters = [
            'domain'            => $domainKey,
            'group_by'          => $groupBy,
            'date_preset'       => $dateRange['preset'],
            'date_from'         => $dateRange['start']->toDateString(),
            'date_to'           => $dateRange['end']->toDateString(),
            'date_label'        => $dateRange['label'],
            'store_id'          => $storeId,
            'store_label'       => $storeId ? (Store::find($storeId)?->name ?? 'Sucursal #' . $storeId) : 'Todas las Sucursales (Consolidado Global)',
            'seller_id'         => !empty($filters['seller_id']) && $filters['seller_id'] !== 'all' ? (int) $filters['seller_id'] : null,
            'supplier_id'       => !empty($filters['supplier_id']) && $filters['supplier_id'] !== 'all' ? (int) $filters['supplier_id'] : null,
            'payment_method_id' => !empty($filters['payment_method_id']) && $filters['payment_method_id'] !== 'all' ? (int) $filters['payment_method_id'] : null,
            'brand_id'          => !empty($filters['brand_id']) && $filters['brand_id'] !== 'all' ? (int) $filters['brand_id'] : null,
            'operation_type'    => !empty($filters['operation_type']) && $filters['operation_type'] !== 'all' ? (string) $filters['operation_type'] : 'all',
            'status'            => !empty($filters['status']) && $filters['status'] !== 'all' ? (string) $filters['status'] : 'all',
            'search'            => trim((string) ($filters['search'] ?? '')),
        ];

        // Ejecutar el dominio correspondiente
        $rawResult = match ($domainKey) {
            'sales'       => $this->executeSalesDomain($dateRange['start'], $dateRange['end'], $normFilters, $user),
            'collections' => $this->executeCollectionsDomain($dateRange['start'], $dateRange['end'], $normFilters),
            'receivables' => $this->executeReceivablesDomain($dateRange['start'], $dateRange['end'], $normFilters),
            'inventory'   => $this->executeInventoryDomain($normFilters, $user),
            'purchases'   => $this->executePurchasesDomain($dateRange['start'], $dateRange['end'], $normFilters),
            'expenses'    => $this->executeExpensesDomain($dateRange['start'], $dateRange['end'], $normFilters),
            'sellers'     => $this->executeSellersDomain($dateRange['start'], $dateRange['end'], $normFilters),
        };

        // Si el usuario solicitó agrupación dinámica (group_by != 'none'), transformar filas y columnas
        if ($groupBy !== 'none') {
            $grouped = $this->applyDynamicGrouping($domainKey, $groupBy, $domainMeta, $rawResult['rows'], $user);
            $activeColumns = $grouped['columns'];
            $finalRows     = $grouped['rows'];
            $totals        = $grouped['totals'];
            $chart         = $grouped['chart'];
        } else {
            // Resolver columnas seleccionadas por el usuario o usar las default del dominio
            $requestedCols = $filters['columns'] ?? [];
            if (is_string($requestedCols)) {
                $requestedCols = array_filter(explode(',', $requestedCols));
            }

            $activeColumns = [];
            if (!empty($requestedCols) && is_array($requestedCols)) {
                foreach ($domainMeta['columns'] as $colKey => $colDef) {
                    if (in_array($colKey, $requestedCols, true)) {
                        $activeColumns[$colKey] = $colDef;
                    }
                }
            }

            if (empty($activeColumns)) {
                foreach ($domainMeta['columns'] as $colKey => $colDef) {
                    if (!empty($colDef['default'])) {
                        $activeColumns[$colKey] = $colDef;
                    }
                }
            }

            $finalRows = $rawResult['rows'];
            $totals    = $this->computeColumnTotals($activeColumns, $finalRows);
            $chart     = $rawResult['chart'];
        }

        return [
            'domain'            => $domainMeta,
            'filters'           => $normFilters,
            'available_columns' => $domainMeta['columns'],
            'columns'           => $activeColumns,
            'rows'              => $finalRows,
            'totals'            => $totals,
            'kpis'              => $rawResult['kpis'],
            'chart'             => $chart,
            'generated_at'      => now()->format('d/m/Y H:i:s'),
            'generated_by'      => $user?->name ?? 'Sistema',
        ];
    }

    /**
     * Calcula automáticamente las sumatorias de pie de tabla (`<tfoot>`) para las columnas activas.
     */
    private function computeColumnTotals(array $columns, array $rows): array
    {
        $totals = ['_count' => count($rows)];

        foreach ($columns as $key => $col) {
            if (!empty($col['sum'])) {
                $sum = 0.0;
                foreach ($rows as $r) {
                    if (!empty($r['_is_cancelled'])) {
                        continue;
                    }
                    $sum += (float) ($r[$key] ?? 0);
                }
                $totals[$key] = round($sum, 2);
            }
        }

        return $totals;
    }

    /**
     * Aplica agrupación multidimensional en memoria sobre el dataset del dominio y genera gráfica automática.
     */
    private function applyDynamicGrouping(
        string $domainKey,
        string $groupBy,
        array $domainMeta,
        array $rows,
        ?User $user
    ): array {
        $canSeeCosts = $this->canViewFinancialCosts($user);
        $groups      = [];

        foreach ($rows as $row) {
            if (!empty($row['_is_cancelled']) && $groupBy !== 'status') {
                continue;
            }

            $groupKey = match ($groupBy) {
                'store'            => $row['store'] ?? 'Sin Sucursal',
                'seller'           => $row['seller'] ?? 'Sin Vendedor',
                'type', 'origin'   => $row['operation_type'] ?? ($row['origin_type'] ?? 'General'),
                'date_day'         => $row['_date_day'] ?? substr((string) ($row['date'] ?? ''), 0, 10),
                'date_month'       => $row['_date_month'] ?? substr((string) ($row['date'] ?? ''), 3, 7),
                'status'           => $row['status'] ?? 'General',
                'method'           => $row['payment_method'] ?? 'Otro',
                'terminal'         => $row['terminal'] ?: 'Sin Terminal (Efectivo/Transf.)',
                'cashier'          => $row['cashier'] ?? 'Sistema',
                'aging'            => $row['aging_bucket'] ?? 'Al Corriente',
                'customer'         => $row['customer'] ?? 'Público en General',
                'brand'            => $row['brand'] ?? 'Sin Marca',
                'supplier'         => $row['supplier'] ?? 'Sin Proveedor',
                'stock_status'     => $row['stock_status'] ?? 'Normal',
                'payment_status'   => $row['payment_status'] ?? 'Pendiente',
                'category'         => $row['category'] ?? 'General',
                'logistics_status' => $row['logistics_status'] ?? 'Pendiente',
                default            => 'General',
            };

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'group_name'         => $groupKey,
                    'records_count'      => 0,
                    'subtotal'           => 0.0,
                    'discounts'          => 0.0,
                    'net_amount'         => 0.0,
                    'taxes'              => 0.0,
                    'total'              => 0.0,
                    'amount_paid'        => 0.0,
                    'balance'            => 0.0,
                    'base_cost'          => 0.0,
                    'gross_profit'       => 0.0,
                    'gross_amount'       => 0.0,
                    'commission'         => 0.0,
                    'quantity'           => 0,
                    'reserved'           => 0,
                    'available'          => 0,
                    'total_cost_value'   => 0.0,
                    'total_retail_value' => 0.0,
                    'potential_profit'   => 0.0,
                    'pending_balance'    => 0.0,
                    'amount'             => 0.0,
                    'sale_amount'        => 0.0,
                    'commission_amount'  => 0.0,
                ];
            }

            $groups[$groupKey]['records_count']++;

            foreach ([
                'subtotal', 'discounts', 'net_amount', 'taxes', 'total',
                'amount_paid', 'balance', 'base_cost', 'gross_profit',
                'gross_amount', 'commission', 'total_cost_value',
                'total_retail_value', 'potential_profit', 'pending_balance',
                'amount', 'sale_amount', 'commission_amount',
            ] as $numField) {
                if (isset($row[$numField])) {
                    $groups[$groupKey][$numField] += (float) $row[$numField];
                }
            }

            foreach (['quantity', 'reserved', 'available'] as $intField) {
                if (isset($row[$intField])) {
                    $groups[$groupKey][$intField] += (int) $row[$intField];
                }
            }
        }

        // Definir columnas de salida según el dominio agrupado
        $groupLabel = $domainMeta['group_by'][$groupBy] ?? 'Agrupación';
        $groupCols  = [
            'group_name'    => ['label' => str_replace('Agrupar por ', '', $groupLabel), 'type' => 'text', 'default' => true],
            'records_count' => ['label' => 'N.º Registros', 'type' => 'integer', 'default' => true, 'sum' => true],
        ];

        $metricOrder = match ($domainKey) {
            'sales'       => ['net_amount', 'taxes', 'total', 'amount_paid', 'balance', 'base_cost', 'gross_profit', 'gross_margin_pct', 'avg_ticket'],
            'collections' => ['gross_amount', 'commission', 'net_amount'],
            'receivables' => ['total', 'amount_paid', 'balance'],
            'inventory'   => ['quantity', 'reserved', 'available', 'total_cost_value', 'total_retail_value', 'potential_profit'],
            'purchases'   => ['net_amount', 'amount_paid', 'pending_balance'],
            'expenses'    => ['amount'],
            'sellers'     => ['sale_amount', 'balance', 'commission_amount'],
            default       => ['total'],
        };

        foreach ($metricOrder as $mKey) {
            if ($mKey === 'avg_ticket') {
                $groupCols['avg_ticket'] = ['label' => 'Ticket Promedio', 'type' => 'currency', 'default' => true];
                continue;
            }
            if (isset($domainMeta['columns'][$mKey])) {
                $groupCols[$mKey] = $domainMeta['columns'][$mKey];
            }
        }

        $primarySortMetric = match ($domainKey) {
            'sales', 'receivables' => 'total',
            'collections'          => 'gross_amount',
            'inventory'            => 'total_retail_value',
            'purchases'            => 'net_amount',
            'expenses'             => 'amount',
            'sellers'              => 'sale_amount',
            default                => 'records_count',
        };

        $groupedRows = array_values($groups);
        foreach ($groupedRows as &$gRow) {
            $gRow['avg_ticket'] = $gRow['records_count'] > 0
                ? round($gRow['total'] / $gRow['records_count'], 2)
                : 0.0;
            $gRow['gross_margin_pct'] = $gRow['net_amount'] > 0
                ? round(($gRow['gross_profit'] / $gRow['net_amount']) * 100, 1)
                : 0.0;
        }
        unset($gRow);

        usort($groupedRows, fn($a, $b) => ($b[$primarySortMetric] ?? 0) <=> ($a[$primarySortMetric] ?? 0));

        $totals = $this->computeColumnTotals($groupCols, $groupedRows);

        // Construir gráfica de la agrupación
        $topSlice = array_slice($groupedRows, 0, 12);
        $chart    = [
            'title'      => 'Comparativa por ' . str_replace('Agrupar por ', '', $groupLabel),
            'type'       => 'bar',
            'categories' => array_map(fn($r) => (string) $r['group_name'], $topSlice),
            'series'     => [
                [
                    'name' => $groupCols[$primarySortMetric]['label'] ?? 'Total',
                    'data' => array_map(fn($r) => round((float) ($r[$primarySortMetric] ?? 0), 2), $topSlice),
                ],
            ],
        ];

        return [
            'columns' => $groupCols,
            'rows'    => $groupedRows,
            'totals'  => $totals,
            'chart'   => $chart,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DOMINIO 1: VENTAS E INGRESOS CONSOLIDADOS (POS + PEDIDOS ESPECIALES)
    // ══════════════════════════════════════════════════════════════════════════
    private function executeSalesDomain(Carbon $start, Carbon $end, array $filters, ?User $user): array
    {
        $storeId  = $filters['store_id'];
        $sellerId = $filters['seller_id'];
        $opType   = $filters['operation_type'];
        $status   = $filters['status'];
        $search   = $filters['search'];

        // Pre-cargar costos base por venta POS y por pedido especial en 2 consultas agregadas (evita N+1)
        $posCosts = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
            ->whereBetween('s.created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('s.store_id', $storeId))
            ->groupBy('si.sale_id')
            ->selectRaw('si.sale_id, COALESCE(SUM(si.quantity * COALESCE(NULLIF(si.base_cost, 0), p.base_cost, 0)), 0) as total_cost')
            ->pluck('total_cost', 'sale_id');

        $specialCosts = DB::table('special_order_items as soi')
            ->join('special_orders as so', 'so.id', '=', 'soi.special_order_id')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->whereBetween('so.created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('so.store_id', $storeId))
            ->groupBy('soi.special_order_id')
            ->selectRaw('soi.special_order_id, COALESCE(SUM(soi.quantity * COALESCE(p.base_cost, 0)), 0) as total_cost')
            ->pluck('total_cost', 'special_order_id');

        $rows = [];

        // 1. Consultar Ventas POS si aplica
        if ($opType === 'all' || $opType === 'pos' || $opType === 'layaway') {
            $posQuery = Sale::with(['customer', 'seller', 'cashier', 'store'])
                ->whereBetween('created_at', [$start, $end])
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->when($sellerId, fn($q) => $q->where(function ($sq) use ($sellerId) {
                    $sq->where('seller_id', $sellerId)->orWhere('user_id', $sellerId);
                }))
                ->when($opType === 'layaway', fn($q) => $q->where('status', 'apartado'))
                ->when($status !== 'all', function ($q) use ($status) {
                    if ($status === 'active') {
                        $q->where('status', '!=', 'cancelada');
                    } elseif ($status === 'cancelled') {
                        $q->where('status', 'cancelada');
                    } elseif ($status === 'with_balance') {
                        $q->where('status', '!=', 'cancelada')->where('balance', '>', 0);
                    }
                })
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('ticket_folio', 'like', "%{$search}%")
                           ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%"));
                    });
                })
                ->orderByDesc('created_at')
                ->get();

            foreach ($posQuery as $sale) {
                $isCancelled = $sale->status === 'cancelada';
                $subtotal    = (float) $sale->subtotal;
                $discounts   = (float) $sale->discount_total + (float) ($sale->coupon_discount ?? 0);
                $net         = max(0, $subtotal - (float) ($sale->coupon_discount ?? 0));
                $taxes       = (float) $sale->tax_total;
                $total       = (float) $sale->total;
                $paid        = (float) $sale->amount_paid;
                $balance     = (float) $sale->balance;
                $cost        = (float) ($posCosts[$sale->id] ?? 0);
                $profit      = $isCancelled ? 0.0 : ($net - $cost);
                $marginPct   = (!$isCancelled && $net > 0) ? round(($profit / $net) * 100, 1) : 0.0;

                $typeLabel = $sale->status === 'apartado' ? 'Apartado POS' : 'Venta POS';
                $typeColor = $sale->status === 'apartado' ? 'warning' : 'primary';

                $statusLabel = match ($sale->status) {
                    'completada' => 'Completada',
                    'apartado'   => 'Apartado (Por Cobrar)',
                    'cancelada'  => 'Cancelada',
                    default      => ucfirst((string) $sale->status),
                };
                $statusColor = match ($sale->status) {
                    'completada' => 'success',
                    'apartado'   => 'warning',
                    'cancelada'  => 'danger',
                    default      => 'secondary',
                };

                $rows[] = [
                    '_timestamp'       => $sale->created_at?->timestamp ?? 0,
                    '_date_day'        => $sale->created_at?->format('Y-m-d') ?? '',
                    '_date_month'      => $sale->created_at?->format('Y-m') ?? '',
                    '_is_cancelled'    => $isCancelled,
                    '_url'             => route('pos.sales.show', $sale->id),
                    'date'             => $sale->created_at?->format('d/m/Y H:i') ?? '—',
                    'folio'            => $sale->ticket_folio,
                    'operation_type'   => $typeLabel,
                    '_type_color'      => $typeColor,
                    'store'            => $sale->store?->name ?? 'General',
                    'customer'         => $sale->customer?->full_name ?? 'Público en General',
                    'seller'           => $sale->seller?->name ?? ($sale->cashier?->name ?? 'Sistema'),
                    'cashier'          => $sale->cashier?->name ?? 'Sistema',
                    'status'           => $statusLabel,
                    '_status_color'    => $statusColor,
                    'subtotal'         => $subtotal,
                    'discounts'        => $discounts,
                    'net_amount'       => $net,
                    'taxes'            => $taxes,
                    'total'            => $total,
                    'amount_paid'      => $paid,
                    'balance'          => $balance,
                    'base_cost'        => $cost,
                    'gross_profit'     => $profit,
                    'gross_margin_pct' => $marginPct,
                ];
            }
        }

        // 2. Consultar Pedidos Especiales si aplica
        if ($opType === 'all' || $opType === 'special') {
            $specQuery = SpecialOrder::with(['customer', 'seller', 'store'])
                ->withSum('payments as paid_sum', 'amount')
                ->whereBetween('created_at', [$start, $end])
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->when($sellerId, fn($q) => $q->where('user_id', $sellerId))
                ->when($status !== 'all', function ($q) use ($status) {
                    if ($status === 'active') {
                        $q->where('status', '!=', 'cancelled');
                    } elseif ($status === 'cancelled') {
                        $q->where('status', 'cancelled');
                    }
                })
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('folio', 'like', "%{$search}%")
                           ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%"));
                    });
                })
                ->orderByDesc('created_at')
                ->get();

            foreach ($specQuery as $order) {
                $isCancelled = $order->status === 'cancelled';
                $total       = (float) $order->total;
                $paid        = (float) ($order->paid_sum ?? 0);
                $balance     = max(0, $total - $paid);

                if ($status === 'with_balance' && ($isCancelled || $balance <= 0.01)) {
                    continue;
                }

                $net       = round($total / 1.16, 2);
                $taxes     = max(0, round($total - $net, 2));
                $cost      = (float) ($specialCosts[$order->id] ?? 0);
                $profit    = $isCancelled ? 0.0 : ($net - $cost);
                $marginPct = (!$isCancelled && $net > 0) ? round(($profit / $net) * 100, 1) : 0.0;

                $statusColor = match ($order->status) {
                    'delivered'          => 'success',
                    'ready'              => 'info',
                    'ordered', 'partial' => 'primary',
                    'pending'            => 'warning',
                    'cancelled'          => 'danger',
                    default              => 'secondary',
                };

                $rows[] = [
                    '_timestamp'       => $order->created_at?->timestamp ?? 0,
                    '_date_day'        => $order->created_at?->format('Y-m-d') ?? '',
                    '_date_month'      => $order->created_at?->format('Y-m') ?? '',
                    '_is_cancelled'    => $isCancelled,
                    '_url'             => route('special-orders.show', $order->id),
                    'date'             => $order->created_at?->format('d/m/Y H:i') ?? '—',
                    'folio'            => $order->folio,
                    'operation_type'   => 'Pedido Especial',
                    '_type_color'      => 'info',
                    'store'            => $order->store?->name ?? 'General',
                    'customer'         => $order->customer?->full_name ?? 'Público en General',
                    'seller'           => $order->seller?->name ?? 'Sistema',
                    'cashier'          => $order->seller?->name ?? 'Sistema',
                    'status'           => $order->statusLabel(),
                    '_status_color'    => $statusColor,
                    'subtotal'         => $net,
                    'discounts'        => 0.0,
                    'net_amount'       => $net,
                    'taxes'            => $taxes,
                    'total'            => $total,
                    'amount_paid'      => $paid,
                    'balance'          => $balance,
                    'base_cost'        => $cost,
                    'gross_profit'     => $profit,
                    'gross_margin_pct' => $marginPct,
                ];
            }
        }

        usort($rows, fn($a, $b) => $b['_timestamp'] <=> $a['_timestamp']);

        // KPIs Ejecutivos del Dominio Ventas
        $activeRows   = array_filter($rows, fn($r) => !$r['_is_cancelled']);
        $cancelledCnt = count($rows) - count($activeRows);
        $sumTotal     = array_sum(array_column($activeRows, 'total'));
        $sumNet       = array_sum(array_column($activeRows, 'net_amount'));
        $sumBalance   = array_sum(array_column($activeRows, 'balance'));
        $sumProfit    = array_sum(array_column($activeRows, 'gross_profit'));
        $avgTicket    = count($activeRows) > 0 ? round($sumTotal / count($activeRows), 2) : 0.0;

        $kpis = [
            [
                'label'    => 'Total Vendido (Activo)',
                'value'    => '$' . number_format($sumTotal, 2),
                'sublabel' => count($activeRows) . ' operaciones (' . $cancelledCnt . ' canceladas)',
                'icon'     => 'ri-shopping-bag-3-line',
                'color'    => 'primary',
            ],
            [
                'label'    => 'Venta Neta (Sin Imp./Desc.)',
                'value'    => '$' . number_format($sumNet, 2),
                'sublabel' => 'Ticket Promedio: $' . number_format($avgTicket, 2),
                'icon'     => 'ri-funds-box-line',
                'color'    => 'success',
            ],
            [
                'label'    => 'Saldo Deudor en Ventas',
                'value'    => '$' . number_format($sumBalance, 2),
                'sublabel' => 'Cobrado: $' . number_format(array_sum(array_column($activeRows, 'amount_paid')), 2),
                'icon'     => 'ri-hand-coin-line',
                'color'    => 'warning',
            ],
            [
                'label'    => $this->canViewFinancialCosts($user) ? 'Utilidad Bruta Estimada' : 'Ticket Promedio General',
                'value'    => $this->canViewFinancialCosts($user) ? ('$' . number_format($sumProfit, 2)) : ('$' . number_format($avgTicket, 2)),
                'sublabel' => $this->canViewFinancialCosts($user) && $sumNet > 0
                    ? ('Margen Bruto: ' . round(($sumProfit / $sumNet) * 100, 1) . '%')
                    : 'Consolidado POS + Especiales',
                'icon'     => 'ri-line-chart-line',
                'color'    => 'info',
            ],
        ];

        // Gráfica por día/fecha
        $byDay = [];
        foreach (array_reverse($activeRows) as $r) {
            $day = $r['_date_day'] ?: 'S/F';
            $byDay[$day] = ($byDay[$day] ?? 0) + $r['total'];
        }

        $chart = [
            'title'      => 'Evolución de Ingresos en el Período Seleccionado',
            'type'       => 'area',
            'categories' => array_keys($byDay),
            'series'     => [
                ['name' => 'Total Vendido ($)', 'data' => array_map(fn($v) => round($v, 2), array_values($byDay))],
            ],
        ];

        return [
            'rows'  => $rows,
            'kpis'  => $kpis,
            'chart' => $chart,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DOMINIO 2: FLUJO DE COBRANZA Y COMISIONES DE TERMINAL
    // ══════════════════════════════════════════════════════════════════════════
    private function executeCollectionsDomain(Carbon $start, Carbon $end, array $filters): array
    {
        $storeId  = $filters['store_id'];
        $methodId = $filters['payment_method_id'];
        $opType   = $filters['operation_type'];
        $search   = $filters['search'];

        $rows = [];

        if ($opType === 'all' || $opType === 'pos') {
            $posPayments = Payment::with(['sale.customer', 'sale.store', 'cashier', 'paymentMethod', 'terminal'])
                ->whereBetween('payments.created_at', [$start, $end])
                ->when($methodId, fn($q) => $q->where('payment_method_id', $methodId))
                ->whereHas('sale', function ($q) use ($storeId) {
                    $q->where('status', '!=', 'cancelada')
                      ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
                })
                ->orderByDesc('created_at')
                ->get();

            foreach ($posPayments as $p) {
                $folio    = $p->sale?->ticket_folio ?? ('VTA #' . $p->sale_id);
                $customer = $p->sale?->customer?->full_name ?? 'Público en General';

                if ($search !== '' && !str_contains(strtolower($folio . ' ' . $customer), strtolower($search))) {
                    continue;
                }

                $gross      = (float) $p->amount;
                $commission = (float) ($p->terminal_commission ?? 0);
                $net        = max(0, $gross - $commission);

                $rows[] = [
                    '_timestamp'     => $p->created_at?->timestamp ?? 0,
                    '_date_day'      => $p->created_at?->format('Y-m-d') ?? '',
                    '_date_month'    => $p->created_at?->format('Y-m') ?? '',
                    '_url'           => $p->sale_id ? route('pos.sales.show', $p->sale_id) : '#',
                    'date'           => $p->created_at?->format('d/m/Y H:i') ?? '—',
                    'folio'          => $folio,
                    'origin_type'    => 'Venta POS',
                    '_type_color'    => 'primary',
                    'store'          => $p->sale?->store?->name ?? 'General',
                    'customer'       => $customer,
                    'cashier'        => $p->cashier?->name ?? 'Sistema',
                    'payment_method' => $p->paymentMethod?->name ?? 'Efectivo',
                    'terminal'       => $p->terminal?->name ?? '—',
                    'msi'            => (int) ($p->msi_applied ?? 0) > 0 ? ($p->msi_applied . ' MSI') : 'Contado',
                    'gross_amount'   => $gross,
                    'commission'     => $commission,
                    'net_amount'     => $net,
                ];
            }
        }

        if ($opType === 'all' || $opType === 'special') {
            $specPayments = SpecialOrderPayment::with(['specialOrder.customer', 'specialOrder.store', 'registeredBy', 'paymentMethod', 'paymentTerminal'])
                ->whereBetween('special_order_payments.created_at', [$start, $end])
                ->when($methodId, fn($q) => $q->where('payment_method_id', $methodId))
                ->whereHas('specialOrder', function ($q) use ($storeId) {
                    $q->where('status', '!=', 'cancelled')
                      ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
                })
                ->orderByDesc('created_at')
                ->get();

            foreach ($specPayments as $sp) {
                $folio    = $sp->specialOrder?->folio ?? ('PED #' . $sp->special_order_id);
                $customer = $sp->specialOrder?->customer?->full_name ?? 'Público en General';

                if ($search !== '' && !str_contains(strtolower($folio . ' ' . $customer), strtolower($search))) {
                    continue;
                }

                $gross = (float) $sp->amount;

                $rows[] = [
                    '_timestamp'     => $sp->created_at?->timestamp ?? 0,
                    '_date_day'      => $sp->created_at?->format('Y-m-d') ?? '',
                    '_date_month'    => $sp->created_at?->format('Y-m') ?? '',
                    '_url'           => $sp->special_order_id ? route('special-orders.show', $sp->special_order_id) : '#',
                    'date'           => $sp->created_at?->format('d/m/Y H:i') ?? '—',
                    'folio'          => $folio,
                    'origin_type'    => 'Pedido Especial',
                    '_type_color'    => 'info',
                    'store'          => $sp->specialOrder?->store?->name ?? 'General',
                    'customer'       => $customer,
                    'cashier'        => $sp->registeredBy?->name ?? 'Sistema',
                    'payment_method' => $sp->paymentMethod?->name ?? 'Anticipo',
                    'terminal'       => $sp->paymentTerminal?->name ?? '—',
                    'msi'            => 'Contado',
                    'gross_amount'   => $gross,
                    'commission'     => 0.0,
                    'net_amount'     => $gross,
                ];
            }
        }

        usort($rows, fn($a, $b) => $b['_timestamp'] <=> $a['_timestamp']);

        $sumGross = array_sum(array_column($rows, 'gross_amount'));
        $sumComm  = array_sum(array_column($rows, 'commission'));
        $sumNet   = array_sum(array_column($rows, 'net_amount'));

        $byMethod = [];
        foreach ($rows as $r) {
            $m = $r['payment_method'];
            $byMethod[$m] = ($byMethod[$m] ?? 0) + $r['gross_amount'];
        }
        arsort($byMethod);

        $kpis = [
            [
                'label'    => 'Total Cobrado Bruto',
                'value'    => '$' . number_format($sumGross, 2),
                'sublabel' => count($rows) . ' transacciones registradas',
                'icon'     => 'ri-Checkbox-circle-line',
                'color'    => 'success',
            ],
            [
                'label'    => 'Comisiones de Terminal',
                'value'    => '-$' . number_format($sumComm, 2),
                'sublabel' => $sumGross > 0 ? (round(($sumComm / $sumGross) * 100, 2) . '% sobre cobro bruto') : '0% comisión',
                'icon'     => 'ri-bank-card-line',
                'color'    => 'danger',
            ],
            [
                'label'    => 'Ingreso Neto en Tesorería',
                'value'    => '$' . number_format($sumNet, 2),
                'sublabel' => 'Descontando retención bancaria',
                'icon'     => 'ri-safe-2-line',
                'color'    => 'primary',
            ],
            [
                'label'    => 'Método Principal',
                'value'    => array_key_first($byMethod) ?? 'Sin cobros',
                'sublabel' => !empty($byMethod) ? ('$' . number_format(reset($byMethod), 2) . ' recaudados') : '—',
                'icon'     => 'ri-pie-chart-2-line',
                'color'    => 'info',
            ],
        ];

        $chart = [
            'title'      => 'Distribución de Cobranza por Método de Pago',
            'type'       => 'bar',
            'categories' => array_keys($byMethod),
            'series'     => [
                ['name' => 'Monto Cobrado ($)', 'data' => array_map(fn($v) => round($v, 2), array_values($byMethod))],
            ],
        ];

        return [
            'rows'  => $rows,
            'kpis'  => $kpis,
            'chart' => $chart,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DOMINIO 3: CARTERA DE CLIENTES (CxC) Y ANTIGÜEDAD DE SALDOS (AGING)
    // ══════════════════════════════════════════════════════════════════════════
    private function executeReceivablesDomain(Carbon $start, Carbon $end, array $filters): array
    {
        $storeId  = $filters['store_id'];
        $sellerId = $filters['seller_id'];
        $opType   = $filters['operation_type'];
        $search   = $filters['search'];
        $today    = Carbon::today();

        $rows = [];

        // 1. Ventas / Apartados POS con saldo pendiente > 0
        if ($opType === 'all' || $opType === 'pos' || $opType === 'layaway') {
            $posSales = Sale::with(['customer', 'seller', 'store'])
                ->where('status', '!=', 'cancelada')
                ->where('balance', '>', 0)
                ->when($filters['date_preset'] !== 'all_time', fn($q) => $q->whereBetween('created_at', [$start, $end]))
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->when($sellerId, fn($q) => $q->where('seller_id', $sellerId))
                ->get();

            foreach ($posSales as $sale) {
                $customerName = $sale->customer?->full_name ?? 'Público en General';
                if ($search !== '' && !str_contains(strtolower($sale->ticket_folio . ' ' . $customerName), strtolower($search))) {
                    continue;
                }

                $days  = $sale->created_at ? (int) $sale->created_at->diffInDays($today) : 0;
                $aging = $this->resolveAgingBucket($days);
                $total = (float) $sale->total;
                $paid  = (float) $sale->amount_paid;
                $bal   = (float) $sale->balance;

                $rows[] = [
                    '_timestamp'     => $sale->created_at?->timestamp ?? 0,
                    '_url'           => route('pos.sales.show', $sale->id),
                    'date'           => $sale->created_at?->format('d/m/Y') ?? '—',
                    'folio'          => $sale->ticket_folio,
                    'operation_type' => 'Apartado POS',
                    '_type_color'    => 'warning',
                    'store'          => $sale->store?->name ?? 'General',
                    'customer'       => $customerName,
                    'customer_phone' => $sale->customer?->phone ?? 'Sin teléfono',
                    'seller'         => $sale->seller?->name ?? 'Sistema',
                    'days_elapsed'   => $days,
                    'aging_bucket'   => $aging['label'],
                    '_status_color'  => $aging['color'],
                    'total'          => $total,
                    'amount_paid'    => $paid,
                    'paid_pct'       => $total > 0 ? round(($paid / $total) * 100, 1) : 0.0,
                    'balance'        => $bal,
                ];
            }
        }

        // 2. Pedidos Especiales activos con saldo pendiente > 0
        if ($opType === 'all' || $opType === 'special') {
            $specials = SpecialOrder::with(['customer', 'seller', 'store'])
                ->withSum('payments as paid_sum', 'amount')
                ->whereNotIn('status', ['cancelled'])
                ->when($filters['date_preset'] !== 'all_time', fn($q) => $q->whereBetween('created_at', [$start, $end]))
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->when($sellerId, fn($q) => $q->where('user_id', $sellerId))
                ->get();

            foreach ($specials as $so) {
                $total = (float) $so->total;
                $paid  = (float) ($so->paid_sum ?? 0);
                $bal   = max(0, $total - $paid);

                if ($bal <= 0.01) {
                    continue;
                }

                $customerName = $so->customer?->full_name ?? 'Público en General';
                if ($search !== '' && !str_contains(strtolower($so->folio . ' ' . $customerName), strtolower($search))) {
                    continue;
                }

                $days  = $so->created_at ? (int) $so->created_at->diffInDays($today) : 0;
                $aging = $this->resolveAgingBucket($days);

                $rows[] = [
                    '_timestamp'     => $so->created_at?->timestamp ?? 0,
                    '_url'           => route('special-orders.show', $so->id),
                    'date'           => $so->created_at?->format('d/m/Y') ?? '—',
                    'folio'          => $so->folio,
                    'operation_type' => 'Pedido Especial',
                    '_type_color'    => 'info',
                    'store'          => $so->store?->name ?? 'General',
                    'customer'       => $customerName,
                    'customer_phone' => $so->customer?->phone ?? 'Sin teléfono',
                    'seller'         => $so->seller?->name ?? 'Sistema',
                    'days_elapsed'   => $days,
                    'aging_bucket'   => $aging['label'],
                    '_status_color'  => $aging['color'],
                    'total'          => $total,
                    'amount_paid'    => $paid,
                    'paid_pct'       => $total > 0 ? round(($paid / $total) * 100, 1) : 0.0,
                    'balance'        => $bal,
                ];
            }
        }

        // Ordenar de mayor a menor antigüedad / saldo
        usort($rows, fn($a, $b) => $b['days_elapsed'] <=> $a['days_elapsed']);

        $totalBalance = array_sum(array_column($rows, 'balance'));
        $buckets = [
            '0-15 días (Reciente)' => 0.0,
            '16-30 días (Regular)' => 0.0,
            '31-60 días (Vencido)' => 0.0,
            '+60 días (Crítico)'   => 0.0,
        ];
        foreach ($rows as $r) {
            $b = $r['aging_bucket'];
            $buckets[$b] = ($buckets[$b] ?? 0) + $r['balance'];
        }

        $kpis = [
            [
                'label'    => 'Cartera Total por Cobrar',
                'value'    => '$' . number_format($totalBalance, 2),
                'sublabel' => count($rows) . ' cuentas con saldo pendiente',
                'icon'     => 'ri-hand-coin-line',
                'color'    => 'warning',
            ],
            [
                'label'    => '0 a 30 Días (Corriente)',
                'value'    => '$' . number_format($buckets['0-15 días (Reciente)'] + $buckets['16-30 días (Regular)'], 2),
                'sublabel' => 'Riesgo bajo de incobrabilidad',
                'icon'     => 'ri-time-line',
                'color'    => 'success',
            ],
            [
                'label'    => '31 a 60 Días (Atención)',
                'value'    => '$' . number_format($buckets['31-60 días (Vencido)'], 2),
                'sublabel' => 'Requiere gestión de cobranza',
                'icon'     => 'ri-error-warning-line',
                'color'    => 'info',
            ],
            [
                'label'    => '+60 Días (Cartera Crítica)',
                'value'    => '$' . number_format($buckets['+60 días (Crítico)'], 2),
                'sublabel' => $totalBalance > 0 ? (round(($buckets['+60 días (Crítico)'] / $totalBalance) * 100, 1) . '% de la cartera total') : '0% mora crítica',
                'icon'     => 'ri-alarm-warning-line',
                'color'    => 'danger',
            ],
        ];

        $chart = [
            'title'      => 'Antigüedad de Saldos en Cartera de Clientes (Aging CxC)',
            'type'       => 'bar',
            'categories' => array_keys($buckets),
            'series'     => [
                ['name' => 'Saldo por Cobrar ($)', 'data' => array_map(fn($v) => round($v, 2), array_values($buckets))],
            ],
        ];

        return [
            'rows'  => $rows,
            'kpis'  => $kpis,
            'chart' => $chart,
        ];
    }

    private function resolveAgingBucket(int $days): array
    {
        if ($days <= 15) {
            return ['label' => '0-15 días (Reciente)', 'color' => 'success'];
        }
        if ($days <= 30) {
            return ['label' => '16-30 días (Regular)', 'color' => 'info'];
        }
        if ($days <= 60) {
            return ['label' => '31-60 días (Vencido)', 'color' => 'warning'];
        }
        return ['label' => '+60 días (Crítico)', 'color' => 'danger'];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DOMINIO 4: INVENTARIO, VALORACIÓN FINANCIERA Y STOCK CRÍTICO
    // ══════════════════════════════════════════════════════════════════════════
    private function executeInventoryDomain(array $filters, ?User $user): array
    {
        $storeId    = $filters['store_id'];
        $supplierId = $filters['supplier_id'];
        $brandId    = $filters['brand_id'];
        $status     = $filters['status'];
        $search     = $filters['search'];

        $inventories = Inventory::with(['product.brand', 'product.supplier', 'variant', 'store', 'warehouse'])
            ->whereHas('product', function ($q) use ($supplierId, $brandId) {
                $q->whereNull('deleted_at')
                  ->when($supplierId, fn($sq) => $sq->where('supplier_id', $supplierId))
                  ->when($brandId, fn($sq) => $sq->where('brand_id', $brandId));
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($status === 'alert', fn($q) => $q->alerts())
            ->when($status === 'out_of_stock', fn($q) => $q->where('quantity', '<=', 0))
            ->when($status === 'in_stock', fn($q) => $q->where('quantity', '>', 0))
            ->orderBy('quantity', 'asc')
            ->get();

        $rows = [];
        foreach ($inventories as $inv) {
            $sku         = $inv->variant?->sku ?: ($inv->product?->sku ?? 'S/N');
            $productName = $inv->variant ? $inv->variant->display_name : ($inv->product?->name ?? 'Producto');

            if ($search !== '' && !str_contains(strtolower($sku . ' ' . $productName), strtolower($search))) {
                continue;
            }

            $qty      = (int) $inv->quantity;
            $reserved = (int) $inv->reserved_quantity;
            $avail    = max(0, $qty - $reserved);
            $minStock = (int) $inv->min_stock;

            $unitCost  = (float) ($inv->variant?->cost > 0 ? $inv->variant->cost : ($inv->product?->base_cost ?? 0));
            $unitPrice = (float) ($inv->variant?->price > 0 ? $inv->variant->price : round($unitCost * 1.45, 2));

            $totalCost   = max(0, $qty) * $unitCost;
            $totalRetail = max(0, $qty) * $unitPrice;
            $potProfit   = max(0, $totalRetail - $totalCost);

            if ($qty <= 0) {
                $stockStatus = 'Agotado (Sin Stock)';
                $statusColor = 'danger';
            } elseif ($minStock > 0 && $qty <= $minStock) {
                $stockStatus = 'Alerta Stock Mínimo';
                $statusColor = 'warning';
            } else {
                $stockStatus = 'Óptimo';
                $statusColor = 'success';
            }

            $rows[] = [
                '_url'               => route('inventory.movements', $inv->product_id),
                'sku'                => $sku,
                'product_name'       => $productName,
                'brand'              => $inv->product?->brand?->name ?? 'Sin Marca',
                'supplier'           => $inv->product?->supplier?->name ?? 'Sin Proveedor',
                'store'              => $inv->store?->name ?? 'General',
                'warehouse'          => $inv->warehouse?->name ?? 'Principal',
                'quantity'           => $qty,
                'reserved'           => $reserved,
                'available'          => $avail,
                'min_stock'          => $minStock,
                'stock_status'       => $stockStatus,
                '_status_color'      => $statusColor,
                'unit_cost'          => $unitCost,
                'total_cost_value'   => $totalCost,
                'unit_price'         => $unitPrice,
                'total_retail_value' => $totalRetail,
                'potential_profit'   => $potProfit,
            ];
        }

        $sumUnits  = array_sum(array_column($rows, 'quantity'));
        $sumCost   = array_sum(array_column($rows, 'total_cost_value'));
        $sumRetail = array_sum(array_column($rows, 'total_retail_value'));
        $alertsCnt = count(array_filter($rows, fn($r) => $r['stock_status'] !== 'Óptimo'));

        $kpis = [
            [
                'label'    => 'Valor Comercial Inventario',
                'value'    => '$' . number_format($sumRetail, 2),
                'sublabel' => number_format($sumUnits) . ' unidades físicas en ' . count($rows) . ' SKUs',
                'icon'     => 'ri-archive-drawer-line',
                'color'    => 'primary',
            ],
            [
                'label'    => $this->canViewFinancialCosts($user) ? 'Valor a Costo Base' : 'Unidades Disponibles',
                'value'    => $this->canViewFinancialCosts($user)
                    ? ('$' . number_format($sumCost, 2))
                    : number_format(array_sum(array_column($rows, 'available'))),
                'sublabel' => $this->canViewFinancialCosts($user)
                    ? ('Utilidad Potencial: $' . number_format($sumRetail - $sumCost, 2))
                    : (number_format(array_sum(array_column($rows, 'reserved'))) . ' unidades reservadas'),
                'icon'     => 'ri-coins-line',
                'color'    => 'success',
            ],
            [
                'label'    => 'SKUs en Alerta / Agotados',
                'value'    => number_format($alertsCnt),
                'sublabel' => 'Requieren reposición o traspaso',
                'icon'     => 'ri-Alert-line',
                'color'    => $alertsCnt > 0 ? 'danger' : 'info',
            ],
            [
                'label'    => 'Unidades Reservadas',
                'value'    => number_format(array_sum(array_column($rows, 'reserved'))),
                'sublabel' => 'Comprometidas en Apartados',
                'icon'     => 'ri-lock-2-line',
                'color'    => 'warning',
            ],
        ];

        $byStore = [];
        foreach ($rows as $r) {
            $st = $r['store'];
            $byStore[$st] = ($byStore[$st] ?? 0) + $r['total_retail_value'];
        }
        arsort($byStore);

        $chart = [
            'title'      => 'Valor Comercial del Inventario por Sucursal',
            'type'       => 'bar',
            'categories' => array_keys($byStore),
            'series'     => [
                ['name' => 'Valor Comercial ($)', 'data' => array_map(fn($v) => round($v, 2), array_values($byStore))],
            ],
        ];

        return [
            'rows'  => $rows,
            'kpis'  => $kpis,
            'chart' => $chart,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DOMINIO 5: COMPRAS Y CUENTAS POR PAGAR A PROVEEDORES (CxP)
    // ══════════════════════════════════════════════════════════════════════════
    private function executePurchasesDomain(Carbon $start, Carbon $end, array $filters): array
    {
        $storeId    = $filters['store_id'];
        $supplierId = $filters['supplier_id'];
        $status     = $filters['status'];
        $search     = $filters['search'];

        $orders = PurchaseOrder::with(['supplier', 'store', 'creator'])
            ->when($filters['date_preset'] !== 'all_time', function ($q) use ($start, $end) {
                $q->where(function ($sq) use ($start, $end) {
                    $sq->whereBetween('issued_at', [$start->toDateString(), $end->toDateString()])
                       ->orWhereBetween('created_at', [$start, $end]);
                });
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
            ->when($status === 'with_balance', fn($q) => $q->where('status', '!=', 'cancelada')->where('payment_status', '!=', 'pagada'))
            ->when($status === 'active', fn($q) => $q->where('status', '!=', 'cancelada'))
            ->orderByDesc('created_at')
            ->get();

        $rows = [];
        foreach ($orders as $po) {
            $supplierName = $po->supplier?->name ?? 'Sin Proveedor';
            if ($search !== '' && !str_contains(strtolower($po->folio . ' ' . $po->rv_folio . ' ' . $supplierName), strtolower($search))) {
                continue;
            }

            $isCancelled = $po->status === 'cancelada';
            $gross       = (float) $po->effectiveGrossAmount();
            $discount    = (float) ($po->discount_amount ?? 0);
            $net         = (float) $po->effectiveNetAmount();
            $paid        = (float) ($po->amount_paid ?? 0);
            $pending     = $isCancelled ? 0.0 : (float) $po->pendingBalance();

            $statusColor = match ($po->status) {
                'recibida'  => 'success',
                'aprobada'  => 'info',
                'pendiente' => 'warning',
                'cancelada' => 'danger',
                default     => 'secondary',
            };

            $payStatusColor = match ($po->payment_status) {
                'pagada'  => 'success',
                'parcial' => 'warning',
                default   => 'danger',
            };

            $rows[] = [
                '_timestamp'      => $po->created_at?->timestamp ?? 0,
                '_is_cancelled'   => $isCancelled,
                '_url'            => route('purchase-orders.show', $po->id),
                'issued_date'     => $po->issued_at ? $po->issued_at->format('d/m/Y') : ($po->created_at?->format('d/m/Y') ?? '—'),
                'folio'           => $po->folio,
                'rv_folio'        => $po->rv_folio ?: '—',
                'supplier'        => $supplierName,
                'store'           => $po->store?->name ?? 'Corporativo',
                'expected_date'   => $po->expected_at ? $po->expected_at->format('d/m/Y') : '—',
                'status'          => ucfirst((string) $po->status),
                '_status_color'   => $statusColor,
                'payment_status'  => ucfirst((string) ($po->payment_status ?: 'pendiente')),
                '_pay_color'      => $payStatusColor,
                'gross_amount'    => $gross,
                'discount_amount' => $discount,
                'net_amount'      => $net,
                'amount_paid'     => $paid,
                'pending_balance' => $pending,
            ];
        }

        $activeRows = array_filter($rows, fn($r) => !$r['_is_cancelled']);
        $sumNet     = array_sum(array_column($activeRows, 'net_amount'));
        $sumPaid    = array_sum(array_column($activeRows, 'amount_paid'));
        $sumPending = array_sum(array_column($activeRows, 'pending_balance'));

        $bySupplier = [];
        foreach ($activeRows as $r) {
            if ($r['pending_balance'] > 0) {
                $sup = $r['supplier'];
                $bySupplier[$sup] = ($bySupplier[$sup] ?? 0) + $r['pending_balance'];
            }
        }
        arsort($bySupplier);

        $kpis = [
            [
                'label'    => 'Total Compras Emitidas',
                'value'    => '$' . number_format($sumNet, 2),
                'sublabel' => count($activeRows) . ' órdenes de compra activas',
                'icon'     => 'ri-shopping-cart-line',
                'color'    => 'primary',
            ],
            [
                'label'    => 'Total Pagado a Proveedores',
                'value'    => '$' . number_format($sumPaid, 2),
                'sublabel' => $sumNet > 0 ? (round(($sumPaid / $sumNet) * 100, 1) . '% liquidado') : '0%',
                'icon'     => 'ri-Checkbox-circle-line',
                'color'    => 'success',
            ],
            [
                'label'    => 'Deuda Pendiente (CxP)',
                'value'    => '$' . number_format($sumPending, 2),
                'sublabel' => count($bySupplier) . ' proveedores con saldo activo',
                'icon'     => 'ri-truck-line',
                'color'    => 'danger',
            ],
            [
                'label'    => 'Principal Acreedor',
                'value'    => array_key_first($bySupplier) ?? 'Sin deuda activa',
                'sublabel' => !empty($bySupplier) ? ('Saldo: $' . number_format(reset($bySupplier), 2)) : 'Al corriente',
                'icon'     => 'ri-building-2-line',
                'color'    => 'warning',
            ],
        ];

        $topSuppliers = array_slice($bySupplier, 0, 10, true);
        $chart = [
            'title'      => 'Cuentas por Pagar (Deuda Activa) por Proveedor',
            'type'       => 'bar',
            'categories' => array_keys($topSuppliers),
            'series'     => [
                ['name' => 'Saldo por Pagar ($)', 'data' => array_map(fn($v) => round($v, 2), array_values($topSuppliers))],
            ],
        ];

        return [
            'rows'  => $rows,
            'kpis'  => $kpis,
            'chart' => $chart,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DOMINIO 6: GASTOS EMPRESARIALES Y EGRESOS OPERATIVOS
    // ══════════════════════════════════════════════════════════════════════════
    private function executeExpensesDomain(Carbon $start, Carbon $end, array $filters): array
    {
        $storeId = $filters['store_id'];
        $search  = $filters['search'];

        $expenses = Expense::with(['store', 'user'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($search !== '', fn($q) => $q->where(function ($sq) use ($search) {
                $sq->where('concept', 'like', "%{$search}%")
                   ->orWhere('notes', 'like', "%{$search}%");
            }))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $rows     = [];
        $byCat    = [];
        $sumTotal = 0.0;

        foreach ($expenses as $exp) {
            $amt       = (float) $exp->amount;
            $sumTotal += $amt;

            $refType = (string) $exp->reference_type;
            if (str_contains($refType, 'PurchaseOrder') || str_contains($refType, 'Supplier')) {
                $catLabel = 'Pago a Proveedor';
                $catColor = 'info';
            } elseif (str_contains($refType, 'CashMovement')) {
                $catLabel = 'Egreso de Caja';
                $catColor = 'warning';
            } else {
                $catLabel = 'Gasto Operativo';
                $catColor = 'secondary';
            }

            $byCat[$catLabel] = ($byCat[$catLabel] ?? 0) + $amt;

            $rows[] = [
                '_date_day'     => $exp->date ? $exp->date->format('Y-m-d') : '',
                '_date_month'   => $exp->date ? $exp->date->format('Y-m') : '',
                'date'          => $exp->date ? $exp->date->format('d/m/Y') : '—',
                'folio'         => 'GTO-' . str_pad((string) $exp->id, 4, '0', STR_PAD_LEFT),
                'concept'       => $exp->concept,
                'category'      => $catLabel,
                '_status_color' => $catColor,
                'store'         => $exp->store?->name ?? 'Corporativo / Global',
                'registered_by' => $exp->user?->name ?? 'Sistema',
                'notes'         => $exp->notes ?: '—',
                'amount'        => $amt,
            ];
        }

        $opAmount  = $byCat['Gasto Operativo'] ?? 0.0;
        $supAmount = $byCat['Pago a Proveedor'] ?? 0.0;
        $cashOut   = $byCat['Egreso de Caja'] ?? 0.0;

        $kpis = [
            [
                'label'    => 'Total Gastos del Período',
                'value'    => '$' . number_format($sumTotal, 2),
                'sublabel' => count($rows) . ' pólizas de egreso registradas',
                'icon'     => 'ri-wallet-3-line',
                'color'    => 'danger',
            ],
            [
                'label'    => 'Gastos Operativos Directos',
                'value'    => '$' . number_format($opAmount, 2),
                'sublabel' => $sumTotal > 0 ? (round(($opAmount / $sumTotal) * 100, 1) . '% del gasto total') : '0%',
                'icon'     => 'ri-building-4-line',
                'color'    => 'primary',
            ],
            [
                'label'    => 'Pagos a Proveedores',
                'value'    => '$' . number_format($supAmount, 2),
                'sublabel' => 'Abonos a órdenes de compra',
                'icon'     => 'ri-truck-line',
                'color'    => 'info',
            ],
            [
                'label'    => 'Egresos desde Caja',
                'value'    => '$' . number_format($cashOut, 2),
                'sublabel' => 'Salidas de efectivo en turnos',
                'icon'     => 'ri-safe-2-line',
                'color'    => 'warning',
            ],
        ];

        $chart = [
            'title'      => 'Distribución de Gastos por Categoría',
            'type'       => 'bar',
            'categories' => array_keys($byCat),
            'series'     => [
                ['name' => 'Monto Egresado ($)', 'data' => array_map(fn($v) => round($v, 2), array_values($byCat))],
            ],
        ];

        return [
            'rows'  => $rows,
            'kpis'  => $kpis,
            'chart' => $chart,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DOMINIO 7: PRODUCTIVIDAD COMERCIAL, COMISIONES Y LOGÍSTICA DE ENTREGAS
    // ══════════════════════════════════════════════════════════════════════════
    private function executeSellersDomain(Carbon $start, Carbon $end, array $filters): array
    {
        $storeId  = $filters['store_id'];
        $sellerId = $filters['seller_id'];
        $status   = $filters['status'];
        $search   = $filters['search'];
        $today    = Carbon::today();

        // Mapear comisiones por sale_id para enriquecer cada fila
        $commissionsBySale = SaleCommission::where('status', '!=', 'cancelled')
            ->whereHas('sale', fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->get()
            ->groupBy('sale_id');

        $rows = [];

        // 1. Ventas POS
        $posSales = Sale::with(['customer', 'seller', 'cashier', 'store'])
            ->where('status', '!=', 'cancelada')
            ->when($status !== 'pending_delivery', fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->when($status === 'pending_delivery', function ($q) {
                $q->where('is_delivery', true)
                  ->where(function ($sq) {
                      $sq->whereIn('delivery_status', ['pendiente', 'en_ruta'])
                         ->orWhereNull('delivery_status');
                  });
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($sellerId, fn($q) => $q->where(function ($sq) use ($sellerId) {
                $sq->where('seller_id', $sellerId)->orWhere('user_id', $sellerId);
            }))
            ->orderByDesc('created_at')
            ->get();

        foreach ($posSales as $sale) {
            $sellerName   = $sale->seller?->name ?? ($sale->cashier?->name ?? 'Sistema');
            $customerName = $sale->delivery_recipient_name ?: ($sale->customer?->full_name ?? 'Público en General');

            if ($search !== '' && !str_contains(strtolower($sale->ticket_folio . ' ' . $sellerName . ' ' . $customerName), strtolower($search))) {
                continue;
            }

            $saleComms  = $commissionsBySale->get($sale->id, collect());
            $commAmount = (float) $saleComms->sum('commission_amount');
            $commStatus = $saleComms->isNotEmpty()
                ? ($saleComms->every(fn($c) => $c->status === 'paid') ? 'Pagada' : 'Devengada')
                : 'Sin Comisión';

            $daysInfo = $sale->is_delivery && $sale->delivery_status !== 'entregado'
                ? $this->calculateDeliveryBadge($sale->delivery_date, $today)
                : ['days' => 999, 'label' => 'Entregado / Mostrador', 'color' => 'success'];

            $logisticsLabel = !$sale->is_delivery
                ? 'Entrega Mostrador'
                : ($sale->delivery_status === 'entregado' ? 'Entregado Domicilio' : ($sale->delivery_status === 'en_ruta' ? 'En Ruta' : 'Pendiente Envío'));

            $rows[] = [
                '_timestamp'        => $sale->created_at?->timestamp ?? 0,
                '_sort_days'        => $daysInfo['days'],
                '_url'              => route('pos.sales.show', $sale->id),
                'date'              => $sale->created_at?->format('d/m/Y H:i') ?? '—',
                'folio'             => $sale->ticket_folio,
                'operation_type'    => $sale->is_delivery ? 'Envío POS' : 'Venta POS',
                '_type_color'       => 'primary',
                'seller'            => $sellerName,
                'store'             => $sale->store?->name ?? 'General',
                'customer'          => $customerName,
                'scheduled_date'    => $sale->delivery_date ? $sale->delivery_date->format('d/m/Y') : 'Inmediata',
                'days_label'        => $daysInfo['label'],
                '_status_color'     => $daysInfo['color'],
                'logistics_status'  => $logisticsLabel,
                'sale_amount'       => (float) $sale->total,
                'balance'           => (float) $sale->balance,
                'commission_amount' => $commAmount,
                'commission_status' => $commStatus,
            ];
        }

        // 2. Pedidos Especiales
        $specialOrders = SpecialOrder::with(['customer', 'seller', 'store'])
            ->withSum('payments as paid_sum', 'amount')
            ->where('status', '!=', 'cancelled')
            ->when($status !== 'pending_delivery', fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->when($status === 'pending_delivery', function ($q) {
                $q->where('status', '!=', 'delivered')
                  ->where(function ($sq) {
                      $sq->where('delivery_status', '!=', 'entregado')
                         ->orWhereNull('delivery_status');
                  });
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($sellerId, fn($q) => $q->where('user_id', $sellerId))
            ->orderByDesc('created_at')
            ->get();

        foreach ($specialOrders as $order) {
            $sellerName   = $order->seller?->name ?? 'Sistema';
            $customerName = $order->delivery_recipient_name ?: ($order->customer?->full_name ?? 'Público en General');

            if ($search !== '' && !str_contains(strtolower($order->folio . ' ' . $sellerName . ' ' . $customerName), strtolower($search))) {
                continue;
            }

            $targetDate = $order->delivery_date ?: $order->expected_delivery_date;
            $isDelivered = $order->status === 'delivered' || $order->delivery_status === 'entregado';
            $daysInfo   = !$isDelivered
                ? $this->calculateDeliveryBadge($targetDate, $today)
                : ['days' => 999, 'label' => 'Entregado', 'color' => 'success'];

            $total   = (float) $order->total;
            $balance = max(0, $total - (float) ($order->paid_sum ?? 0));

            $rows[] = [
                '_timestamp'        => $order->created_at?->timestamp ?? 0,
                '_sort_days'        => $daysInfo['days'],
                '_url'              => route('special-orders.show', $order->id),
                'date'              => $order->created_at?->format('d/m/Y H:i') ?? '—',
                'folio'             => $order->folio,
                'operation_type'    => 'Pedido Especial',
                '_type_color'       => 'info',
                'seller'            => $sellerName,
                'store'             => $order->store?->name ?? 'General',
                'customer'          => $customerName,
                'scheduled_date'    => $targetDate ? $targetDate->format('d/m/Y') : 'Por definir',
                'days_label'        => $daysInfo['label'],
                '_status_color'     => $daysInfo['color'],
                'logistics_status'  => $order->statusLabel(),
                'sale_amount'       => $total,
                'balance'           => $balance,
                'commission_amount' => 0.0,
                'commission_status' => 'Pedido Especial',
            ];
        }

        if ($status === 'pending_delivery') {
            usort($rows, fn($a, $b) => $a['_sort_days'] <=> $b['_sort_days']);
        } else {
            usort($rows, fn($a, $b) => $b['_timestamp'] <=> $a['_timestamp']);
        }

        $sumSales = array_sum(array_column($rows, 'sale_amount'));
        $sumComm  = array_sum(array_column($rows, 'commission_amount'));
        $overdue  = count(array_filter($rows, fn($r) => ($r['_sort_days'] ?? 999) < 0));

        $bySeller = [];
        foreach ($rows as $r) {
            $s = $r['seller'];
            $bySeller[$s] = ($bySeller[$s] ?? 0) + $r['sale_amount'];
        }
        arsort($bySeller);

        $kpis = [
            [
                'label'    => 'Volumen Comercial Asignado',
                'value'    => '$' . number_format($sumSales, 2),
                'sublabel' => count($rows) . ' operaciones procesadas',
                'icon'     => 'ri-medal-line',
                'color'    => 'primary',
            ],
            [
                'label'    => 'Comisiones Generadas',
                'value'    => '$' . number_format($sumComm, 2),
                'sublabel' => 'Matriz de comisiones POS',
                'icon'     => 'ri-award-line',
                'color'    => 'success',
            ],
            [
                'label'    => 'Vendedor Líder del Período',
                'value'    => array_key_first($bySeller) ?? 'Sin registros',
                'sublabel' => !empty($bySeller) ? ('$' . number_format(reset($bySeller), 2) . ' vendidos') : '—',
                'icon'     => 'ri-user-star-line',
                'color'    => 'info',
            ],
            [
                'label'    => 'Entregas con Retraso',
                'value'    => number_format($overdue),
                'sublabel' => $overdue > 0 ? 'Atención logística inmediata' : '0 retrasos activos',
                'icon'     => 'ri-truck-line',
                'color'    => $overdue > 0 ? 'danger' : 'success',
            ],
        ];

        $topSellers = array_slice($bySeller, 0, 10, true);
        $chart = [
            'title'      => 'Productividad Comercial por Vendedor',
            'type'       => 'bar',
            'categories' => array_keys($topSellers),
            'series'     => [
                ['name' => 'Monto Vendido ($)', 'data' => array_map(fn($v) => round($v, 2), array_values($topSellers))],
            ],
        ];

        return [
            'rows'  => $rows,
            'kpis'  => $kpis,
            'chart' => $chart,
        ];
    }

    private function calculateDeliveryBadge(?Carbon $targetDate, Carbon $today): array
    {
        if (!$targetDate) {
            return ['days' => 998, 'label' => 'Sin fecha asignada', 'color' => 'secondary'];
        }
        $target = Carbon::parse($targetDate->toDateString());
        $diff   = (int) $today->diffInDays($target, false);

        if ($diff < 0) {
            $abs = abs($diff);
            return ['days' => $diff, 'label' => "Retrasado {$abs} " . ($abs === 1 ? 'día' : 'días'), 'color' => 'danger'];
        }
        if ($diff === 0) {
            return ['days' => 0, 'label' => 'Entrega Hoy', 'color' => 'warning'];
        }
        if ($diff <= 3) {
            return ['days' => $diff, 'label' => "Faltan {$diff} " . ($diff === 1 ? 'día' : 'días'), 'color' => 'info'];
        }
        return ['days' => $diff, 'label' => "En {$diff} días", 'color' => 'success'];
    }
}
