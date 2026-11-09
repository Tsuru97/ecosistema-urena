<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SpecialOrder;
use App\Models\SpecialOrderItem;
use App\Models\SpecialOrderPayment;
use App\Models\Store;
use App\Models\SupplierPayment;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    /**
     * Construye el payload analítico completo del Dashboard (3 Secciones + 6 Extras).
     */
    public function getDashboardData(int $year, int $month, ?int $storeId = null): array
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth   = (clone $startOfMonth)->endOfMonth();

        $prevStart = (clone $startOfMonth)->subMonth()->startOfMonth();
        $prevEnd   = (clone $startOfMonth)->subMonth()->endOfMonth();

        $monthsNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $periodContext = [
            'year'              => $year,
            'month'             => $month,
            'month_name'        => $monthsNames[$month] ?? 'Mes',
            'period_label'      => ($monthsNames[$month] ?? 'Mes') . ' ' . $year,
            'prev_period_label' => ($monthsNames[$prevStart->month] ?? 'Mes') . ' ' . $prevStart->year,
            'is_current_month'  => ($year === (int) now()->year && $month === (int) now()->month),
            'store_id'          => $storeId,
            'months_list'       => $monthsNames,
            'available_years'   => $this->getAvailableYears(),
        ];

        $kpis   = $this->buildKpis($startOfMonth, $endOfMonth, $prevStart, $prevEnd, $storeId);
        $charts = $this->buildCharts($year, $startOfMonth, $endOfMonth, $storeId);
        $tables = $this->buildTables($startOfMonth, $endOfMonth, $storeId);

        return [
            'context' => $periodContext,
            'kpis'    => $kpis,
            'charts'  => $charts,
            'tables'  => $tables,
        ];
    }

    /**
     * Obtiene la lista de años disponibles para el selector global.
     */
    private function getAvailableYears(): array
    {
        $currentYear = (int) now()->year;
        $minSaleYear = Sale::min('created_at');
        $startYear   = $minSaleYear ? (int) Carbon::parse($minSaleYear)->year : ($currentYear - 2);
        $startYear   = min($startYear, $currentYear - 2);

        $years = [];
        for ($y = $currentYear; $y >= $startYear; $y--) {
            $years[] = $y;
        }
        return $years;
    }

    /**
     * Calcula variación porcentual segura entre período actual y anterior.
     */
    private function calcGrowth(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * SECCIÓN 1: Construcción de los 14 KPIs + Extras Financieros y Comerciales.
     */
    private function buildKpis(
        Carbon $start,
        Carbon $end,
        Carbon $prevStart,
        Carbon $prevEnd,
        ?int $storeId
    ): array {
        // ── 1. Consultas base de Ventas POS (Sale) y Pedidos Especiales (SpecialOrder) ──
        $posActiveQuery = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelada')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $posPrevQuery = Sale::whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('status', '!=', 'cancelada')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $specialActiveQuery = SpecialOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $specialPrevQuery = SpecialOrder::whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('status', '!=', 'cancelled')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        // ── KPI 1: Total vendido en el mes (Consolidado POS + Pedidos Especiales) ──
        $posTotalSold     = (float) (clone $posActiveQuery)->sum('total');
        $posSalesCount    = (int) (clone $posActiveQuery)->count();
        $specialTotalSold = (float) (clone $specialActiveQuery)->sum('total');
        $specialCount     = (int) (clone $specialActiveQuery)->count();

        $totalSoldMonth = $posTotalSold + $specialTotalSold;
        $totalOpsCount  = $posSalesCount + $specialCount;

        $prevPosSold     = (float) (clone $posPrevQuery)->sum('total');
        $prevSpecialSold = (float) (clone $specialPrevQuery)->sum('total');
        $prevTotalSold   = $prevPosSold + $prevSpecialSold;
        $prevTotalCount  = (int) (clone $posPrevQuery)->count() + (int) (clone $specialPrevQuery)->count();

        // ── KPI 2: Venta Neta (Subtotal - Descuentos, sin impuestos ni descuentos) ──
        // En Sale: subtotal ya refleja el monto antes de impuestos; descontamos cualquier cupón proporcional o descuento.
        $posAggregates = (clone $posActiveQuery)->selectRaw('
            COALESCE(SUM(subtotal), 0) as sum_subtotal,
            COALESCE(SUM(tax_total), 0) as sum_tax,
            COALESCE(SUM(discount_total), 0) as sum_discount,
            COALESCE(SUM(coupon_discount), 0) as sum_coupon
        ')->first();

        $posSubtotal       = (float) ($posAggregates->sum_subtotal ?? 0);
        $posTaxTotal       = (float) ($posAggregates->sum_tax ?? 0);
        $posDiscountTotal  = (float) ($posAggregates->sum_discount ?? 0);
        $posCouponDiscount = (float) ($posAggregates->sum_coupon ?? 0);

        // Si subtotal + tax_total > total (porque había cupón o descuento no restado del subtotal), ajustamos:
        $posNetSales = max(0, $posSubtotal - $posCouponDiscount);
        if ($posNetSales <= 0 && $posTotalSold > 0) {
            $posNetSales = max(0, $posTotalSold - $posTaxTotal - $posDiscountTotal - $posCouponDiscount);
        }

        // En SpecialOrder: obtenemos el subtotal sin impuestos (usando 16% IVA estándar o la cotización vinculada)
        $specialNetSales = round($specialTotalSold / 1.16, 2);
        $specialTaxes    = max(0, $specialTotalSold - $specialNetSales);

        $netSalesTotal = $posNetSales + $specialNetSales;

        // Venta Neta mes anterior para comparativa %
        $prevPosNet = (float) (clone $posPrevQuery)
            ->selectRaw('COALESCE(SUM(GREATEST(0, subtotal - COALESCE(coupon_discount, 0))), 0) as net')
            ->value('net');
        $prevSpecialNet = round($prevSpecialSold / 1.16, 2);
        $prevNetTotal   = $prevPosNet + $prevSpecialNet;

        // ── KPI 3: Cancelaciones (Métrica operativa independiente) ──
        $posCancelledQuery = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', 'cancelada')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $specialCancelledQuery = SpecialOrder::whereBetween('created_at', [$start, $end])
            ->where('status', 'cancelled')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $posCancelledAmount     = (float) (clone $posCancelledQuery)->sum('total');
        $posCancelledCount      = (int) (clone $posCancelledQuery)->count();
        $specialCancelledAmount = (float) (clone $specialCancelledQuery)->sum('total');
        $specialCancelledCount  = (int) (clone $specialCancelledQuery)->count();

        $totalCancelledAmount = $posCancelledAmount + $specialCancelledAmount;
        $totalCancelledCount  = $posCancelledCount + $specialCancelledCount;
        $allMonthOpsCount     = $totalOpsCount + $totalCancelledCount;
        $cancellationRatePct  = $allMonthOpsCount > 0
            ? round(($totalCancelledCount / $allMonthOpsCount) * 100, 1)
            : 0.0;

        // ── KPI 4: Ticket Promedio (Total y por tipo) ──
        $avgTicketTotal   = $totalOpsCount > 0 ? round($totalSoldMonth / $totalOpsCount, 2) : 0.0;
        $avgTicketPos     = $posSalesCount > 0 ? round($posTotalSold / $posSalesCount, 2) : 0.0;
        $avgTicketSpecial = $specialCount > 0 ? round($specialTotalSold / $specialCount, 2) : 0.0;
        $prevAvgTicket    = $prevTotalCount > 0 ? round($prevTotalSold / $prevTotalCount, 2) : 0.0;

        // ── KPI 5: Venta más alta del mes (comparando POS vs Pedido Especial) ──
        $topPosSale = (clone $posActiveQuery)
            ->with(['customer', 'seller', 'cashier', 'store'])
            ->orderByDesc('total')
            ->first();

        $topSpecialOrder = (clone $specialActiveQuery)
            ->with(['customer', 'seller', 'store'])
            ->orderByDesc('total')
            ->first();

        $highestSale = $this->resolveHighestSale($topPosSale, $topSpecialOrder);

        // ── KPI 6: Mejor vendedor del mes (Consolidado POS + Pedidos Especiales) ──
        $bestSeller = $this->resolveBestSeller($start, $end, $storeId, $totalSoldMonth);

        // ── KPI 7: Mejor Sucursal del mes (Consolidado POS + Pedidos Especiales) ──
        $bestStore = $this->resolveBestStore($start, $end, $totalSoldMonth);

        // ── KPI 8 y 9: Pedidos pendientes de entrega y Entregas con retraso ──
        $deliveryMetrics = $this->resolveDeliveryKpis($storeId);

        // ── KPI 10: Valor del inventario ──
        $inventoryMetrics = $this->resolveInventoryKpi($storeId);

        // ── KPI 11: Cobros del mes + Comisiones de Terminal independientes ──
        $collectionsMetrics = $this->resolveCollectionsKpi($start, $end, $prevStart, $prevEnd, $storeId);

        // ── KPI 12: Gastos del mes ──
        $expensesMetrics = $this->resolveExpensesKpi($start, $end, $prevStart, $prevEnd, $storeId);

        // ── KPI 13: Cartera de clientes (Cuentas por cobrar activas) ──
        $receivablesMetrics = $this->resolveReceivablesKpi($storeId);

        // ── KPI 14: Deuda con proveedores (Cuentas por pagar activas) ──
        $payablesMetrics = $this->resolvePayablesKpi($start, $end, $storeId);

        // ── EXTRA 2: Utilidad Bruta Estimada y Flujo de Caja Neto ──
        $profitabilityMetrics = $this->resolveProfitabilityKpi(
            $start,
            $end,
            $storeId,
            $netSalesTotal,
            $collectionsMetrics['net_collected'],
            $expensesMetrics['total_amount']
        );

        // ── EXTRA 5: Embudo de Conversión de Cotizaciones ──
        $quotesFunnel = $this->resolveQuotesFunnelKpi($start, $end, $storeId);

        return [
            'total_sold' => [
                'total'         => $totalSoldMonth,
                'pos_total'     => $posTotalSold,
                'special_total' => $specialTotalSold,
                'pos_count'     => $posSalesCount,
                'special_count' => $specialCount,
                'total_count'   => $totalOpsCount,
                'prev_total'    => $prevTotalSold,
                'growth_pct'    => $this->calcGrowth($totalSoldMonth, $prevTotalSold),
                'pos_share_pct' => $totalSoldMonth > 0 ? round(($posTotalSold / $totalSoldMonth) * 100, 1) : 0,
            ],
            'net_sales' => [
                'total'           => $netSalesTotal,
                'pos_net'         => $posNetSales,
                'special_net'     => $specialNetSales,
                'taxes_total'     => $posTaxTotal + $specialTaxes,
                'discounts_total' => $posDiscountTotal + $posCouponDiscount,
                'prev_total'      => $prevNetTotal,
                'growth_pct'      => $this->calcGrowth($netSalesTotal, $prevNetTotal),
            ],
            'cancellations' => [
                'total_amount'   => $totalCancelledAmount,
                'total_count'    => $totalCancelledCount,
                'pos_amount'     => $posCancelledAmount,
                'pos_count'      => $posCancelledCount,
                'special_amount' => $specialCancelledAmount,
                'special_count'  => $specialCancelledCount,
                'rate_pct'       => $cancellationRatePct,
            ],
            'avg_ticket' => [
                'total'       => $avgTicketTotal,
                'pos_avg'     => $avgTicketPos,
                'special_avg' => $avgTicketSpecial,
                'prev_avg'    => $prevAvgTicket,
                'growth_pct'  => $this->calcGrowth($avgTicketTotal, $prevAvgTicket),
            ],
            'highest_sale'       => $highestSale,
            'best_seller'        => $bestSeller,
            'best_store'         => $bestStore,
            'pending_deliveries' => $deliveryMetrics['pending'],
            'overdue_deliveries' => $deliveryMetrics['overdue'],
            'inventory_value'    => $inventoryMetrics,
            'collections'        => $collectionsMetrics,
            'expenses'           => $expensesMetrics,
            'receivables'        => $receivablesMetrics,
            'payables'           => $payablesMetrics,
            'profitability'      => $profitabilityMetrics,
            'quotes_funnel'      => $quotesFunnel,
        ];
    }

    /**
     * Determina la venta individual más alta del mes (POS vs Pedido Especial).
     */
    private function resolveHighestSale(?Sale $topPos, ?SpecialOrder $topSpecial): array
    {
        $posAmount     = (float) ($topPos?->total ?? 0);
        $specialAmount = (float) ($topSpecial?->total ?? 0);

        if ($posAmount <= 0 && $specialAmount <= 0) {
            return [
                'amount'        => 0.0,
                'folio'         => 'Sin registros',
                'type'          => 'none',
                'type_label'    => 'Sin ventas en el período',
                'customer_name' => '—',
                'seller_name'   => '—',
                'store_name'    => '—',
                'date'          => null,
                'url'           => '#',
                'secondary'     => null,
            ];
        }

        if ($posAmount >= $specialAmount && $topPos) {
            return [
                'amount'        => $posAmount,
                'folio'         => $topPos->ticket_folio,
                'type'          => 'pos',
                'type_label'    => 'Venta POS',
                'customer_name' => $topPos->customer?->full_name ?? 'Público en General',
                'seller_name'   => $topPos->seller?->name ?? ($topPos->cashier?->name ?? 'Sistema'),
                'store_name'    => $topPos->store?->name ?? 'General',
                'date'          => $topPos->created_at?->format('d/m/Y'),
                'url'           => route('pos.sales.show', $topPos->id),
                'secondary'     => $topSpecial ? [
                    'type_label' => 'Máx. Ped. Especial',
                    'folio'      => $topSpecial->folio,
                    'amount'     => $specialAmount,
                ] : null,
            ];
        }

        return [
            'amount'        => $specialAmount,
            'folio'         => $topSpecial->folio,
            'type'          => 'special',
            'type_label'    => 'Pedido Especial',
            'customer_name' => $topSpecial->customer?->full_name ?? 'Público en General',
            'seller_name'   => $topSpecial->seller?->name ?? 'Sistema',
            'store_name'    => $topSpecial->store?->name ?? 'General',
            'date'          => $topSpecial->created_at?->format('d/m/Y'),
            'url'           => route('special-orders.show', $topSpecial->id),
            'secondary'     => $topPos ? [
                'type_label' => 'Máx. Venta POS',
                'folio'      => $topPos->ticket_folio,
                'amount'     => $posAmount,
            ] : null,
        ];
    }

    /**
     * Determina el mejor vendedor del mes sumando Ventas POS + Pedidos Especiales.
     */
    private function resolveBestSeller(Carbon $start, Carbon $end, ?int $storeId, float $totalSoldMonth): array
    {
        $posBySeller = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelada')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('COALESCE(seller_id, user_id) as uid, SUM(total) as total_amount, COUNT(*) as ops_count')
            ->groupBy('uid')
            ->get();

        $specialBySeller = SpecialOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('user_id as uid, SUM(total) as total_amount, COUNT(*) as ops_count')
            ->groupBy('uid')
            ->get();

        $merged = [];
        foreach ($posBySeller as $row) {
            if (!$row->uid) continue;
            $merged[$row->uid] = [
                'uid'            => (int) $row->uid,
                'pos_amount'     => (float) $row->total_amount,
                'special_amount' => 0.0,
                'total_amount'   => (float) $row->total_amount,
                'ops_count'      => (int) $row->ops_count,
            ];
        }

        foreach ($specialBySeller as $row) {
            if (!$row->uid) continue;
            if (!isset($merged[$row->uid])) {
                $merged[$row->uid] = [
                    'uid'            => (int) $row->uid,
                    'pos_amount'     => 0.0,
                    'special_amount' => 0.0,
                    'total_amount'   => 0.0,
                    'ops_count'      => 0,
                ];
            }
            $merged[$row->uid]['special_amount'] += (float) $row->total_amount;
            $merged[$row->uid]['total_amount']   += (float) $row->total_amount;
            $merged[$row->uid]['ops_count']      += (int) $row->ops_count;
        }

        if (empty($merged)) {
            return [
                'name'           => 'Sin ventas registradas',
                'total_amount'   => 0.0,
                'pos_amount'     => 0.0,
                'special_amount' => 0.0,
                'ops_count'      => 0,
                'share_pct'      => 0.0,
            ];
        }

        usort($merged, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);
        $winner = $merged[0];
        $user   = User::find($winner['uid']);

        return [
            'name'           => $user ? trim($user->name . ' ' . ($user->lastname ?? '')) : 'Usuario #' . $winner['uid'],
            'total_amount'   => $winner['total_amount'],
            'pos_amount'     => $winner['pos_amount'],
            'special_amount' => $winner['special_amount'],
            'ops_count'      => $winner['ops_count'],
            'share_pct'      => $totalSoldMonth > 0 ? round(($winner['total_amount'] / $totalSoldMonth) * 100, 1) : 0.0,
        ];
    }

    /**
     * Determina la mejor sucursal del mes (a nivel global para referencia corporativa).
     */
    private function resolveBestStore(Carbon $start, Carbon $end, float $contextTotalSold): array
    {
        $posByStore = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelada')
            ->selectRaw('store_id, SUM(total) as total_amount, COUNT(*) as ops_count')
            ->groupBy('store_id')
            ->get();

        $specialByStore = SpecialOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('store_id, SUM(total) as total_amount, COUNT(*) as ops_count')
            ->groupBy('store_id')
            ->get();

        $merged = [];
        $companyTotal = 0.0;

        foreach ($posByStore as $row) {
            if (!$row->store_id) continue;
            $amt = (float) $row->total_amount;
            $companyTotal += $amt;
            $merged[$row->store_id] = [
                'store_id'       => (int) $row->store_id,
                'pos_amount'     => $amt,
                'special_amount' => 0.0,
                'total_amount'   => $amt,
                'ops_count'      => (int) $row->ops_count,
            ];
        }

        foreach ($specialByStore as $row) {
            if (!$row->store_id) continue;
            $amt = (float) $row->total_amount;
            $companyTotal += $amt;
            if (!isset($merged[$row->store_id])) {
                $merged[$row->store_id] = [
                    'store_id'       => (int) $row->store_id,
                    'pos_amount'     => 0.0,
                    'special_amount' => 0.0,
                    'total_amount'   => 0.0,
                    'ops_count'      => 0,
                ];
            }
            $merged[$row->store_id]['special_amount'] += $amt;
            $merged[$row->store_id]['total_amount']   += $amt;
            $merged[$row->store_id]['ops_count']      += (int) $row->ops_count;
        }

        if (empty($merged)) {
            return [
                'name'           => 'Sin actividad en el mes',
                'total_amount'   => 0.0,
                'pos_amount'     => 0.0,
                'special_amount' => 0.0,
                'ops_count'      => 0,
                'share_pct'      => 0.0,
            ];
        }

        usort($merged, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);
        $winner = $merged[0];
        $store  = Store::find($winner['store_id']);

        return [
            'name'           => $store?->name ?? ('Sucursal #' . $winner['store_id']),
            'total_amount'   => $winner['total_amount'],
            'pos_amount'     => $winner['pos_amount'],
            'special_amount' => $winner['special_amount'],
            'ops_count'      => $winner['ops_count'],
            'share_pct'      => $companyTotal > 0 ? round(($winner['total_amount'] / $companyTotal) * 100, 1) : 0.0,
        ];
    }

    /**
     * KPIs 8 y 9: Pedidos pendientes de entrega y Entregas con retraso.
     */
    private function resolveDeliveryKpis(?int $storeId): array
    {
        $todayStr = Carbon::today()->toDateString();

        // Ventas POS con entrega a domicilio aún no entregadas
        $posPendingDeliveries = Sale::where('is_delivery', true)
            ->where('status', '!=', 'cancelada')
            ->where(function ($q) {
                $q->whereIn('delivery_status', ['pendiente', 'en_ruta'])
                  ->orWhereNull('delivery_status');
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        // Pedidos Especiales aún no entregados ni cancelados
        $specialPendingDeliveries = SpecialOrder::whereNotIn('status', ['delivered', 'cancelled'])
            ->where(function ($q) {
                $q->where('is_delivery', false)
                  ->orWhereIn('delivery_status', ['pendiente', 'en_ruta'])
                  ->orWhereNull('delivery_status');
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $posPendingCount     = (int) (clone $posPendingDeliveries)->count();
        $specialPendingCount = (int) (clone $specialPendingDeliveries)->count();

        $inRouteCount = (int) Sale::where('is_delivery', true)
            ->where('status', '!=', 'cancelada')
            ->where('delivery_status', 'en_ruta')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->count()
            + (int) SpecialOrder::where('status', '!=', 'cancelled')
                ->where('delivery_status', 'en_ruta')
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->count();

        // Entregas con retraso (fecha programada menor a hoy)
        $posOverdueCount = (int) (clone $posPendingDeliveries)
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '<', $todayStr)
            ->count();

        $specialOverdueCount = (int) (clone $specialPendingDeliveries)
            ->where(function ($q) use ($todayStr) {
                $q->where(function ($q1) use ($todayStr) {
                    $q1->whereNotNull('delivery_date')->whereDate('delivery_date', '<', $todayStr);
                })->orWhere(function ($q2) use ($todayStr) {
                    $q2->whereNull('delivery_date')
                       ->whereNotNull('expected_delivery_date')
                       ->whereDate('expected_delivery_date', '<', $todayStr);
                });
            })
            ->count();

        return [
            'pending' => [
                'total_count'   => $posPendingCount + $specialPendingCount,
                'pos_count'     => $posPendingCount,
                'special_count' => $specialPendingCount,
                'in_route'      => $inRouteCount,
            ],
            'overdue' => [
                'total_count'   => $posOverdueCount + $specialOverdueCount,
                'pos_count'     => $posOverdueCount,
                'special_count' => $specialOverdueCount,
            ],
        ];
    }

    /**
     * KPI 10: Valor del inventario (Costo vs Comercial y Existencias).
     */
    private function resolveInventoryKpi(?int $storeId): array
    {
        $row = DB::table('inventories as inv')
            ->join('products as p', 'p.id', '=', 'inv.product_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'inv.product_variant_id')
            ->whereNull('p.deleted_at')
            ->where('inv.quantity', '>', 0)
            ->when($storeId, fn($q) => $q->where('inv.store_id', $storeId))
            ->selectRaw('
                COALESCE(SUM(inv.quantity * COALESCE(NULLIF(pv.cost, 0), p.base_cost, 0)), 0) as total_cost_value,
                COALESCE(SUM(inv.quantity * COALESCE(NULLIF(pv.price, 0), p.base_cost * 1.45, 0)), 0) as total_retail_value,
                COALESCE(SUM(inv.quantity), 0) as total_units,
                COALESCE(SUM(inv.reserved_quantity), 0) as reserved_units,
                COUNT(DISTINCT inv.id) as active_skus
            ')
            ->first();

        $totalUnits    = (int) ($row->total_units ?? 0);
        $reservedUnits = (int) ($row->reserved_units ?? 0);

        return [
            'cost_value'      => (float) ($row->total_cost_value ?? 0),
            'retail_value'    => (float) ($row->total_retail_value ?? 0),
            'total_units'     => $totalUnits,
            'reserved_units'  => $reservedUnits,
            'available_units' => max(0, $totalUnits - $reservedUnits),
            'active_skus'     => (int) ($row->active_skus ?? 0),
        ];
    }

    /**
     * KPI 11: Cobros del mes y Comisiones de Terminal independientes.
     */
    private function resolveCollectionsKpi(
        Carbon $start,
        Carbon $end,
        Carbon $prevStart,
        Carbon $prevEnd,
        ?int $storeId
    ): array {
        $posPaymentsQuery = Payment::whereBetween('payments.created_at', [$start, $end])
            ->whereHas('sale', function ($q) use ($storeId) {
                $q->where('status', '!=', 'cancelada')
                  ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
            });

        $specialPaymentsQuery = SpecialOrderPayment::whereBetween('special_order_payments.created_at', [$start, $end])
            ->whereHas('specialOrder', function ($q) use ($storeId) {
                $q->where('status', '!=', 'cancelled')
                  ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
            });

        $posAgg = (clone $posPaymentsQuery)->selectRaw('
            COALESCE(SUM(amount), 0) as sum_amount,
            COALESCE(SUM(terminal_commission), 0) as sum_commission,
            COUNT(*) as cnt
        ')->first();

        $specialAgg = (clone $specialPaymentsQuery)->selectRaw('
            COALESCE(SUM(amount), 0) as sum_amount,
            COUNT(*) as cnt
        ')->first();

        $posCollected        = (float) ($posAgg->sum_amount ?? 0);
        $terminalCommissions = (float) ($posAgg->sum_commission ?? 0);
        $specialCollected    = (float) ($specialAgg->sum_amount ?? 0);
        $grossCollected      = $posCollected + $specialCollected;
        $netCollected        = max(0, $grossCollected - $terminalCommissions);
        $paymentsCount       = (int) ($posAgg->cnt ?? 0) + (int) ($specialAgg->cnt ?? 0);

        // Mes anterior
        $prevPosCollected = (float) Payment::whereBetween('payments.created_at', [$prevStart, $prevEnd])
            ->whereHas('sale', function ($q) use ($storeId) {
                $q->where('status', '!=', 'cancelada')
                  ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
            })->sum('amount');

        $prevSpecialCollected = (float) SpecialOrderPayment::whereBetween('special_order_payments.created_at', [$prevStart, $prevEnd])
            ->whereHas('specialOrder', function ($q) use ($storeId) {
                $q->where('status', '!=', 'cancelled')
                  ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
            })->sum('amount');

        $prevGrossCollected = $prevPosCollected + $prevSpecialCollected;

        return [
            'gross_collected'      => $grossCollected,
            'pos_collected'        => $posCollected,
            'special_collected'    => $specialCollected,
            'terminal_commissions' => $terminalCommissions,
            'net_collected'        => $netCollected,
            'payments_count'       => $paymentsCount,
            'prev_collected'       => $prevGrossCollected,
            'growth_pct'           => $this->calcGrowth($grossCollected, $prevGrossCollected),
        ];
    }

    /**
     * KPI 12: Gastos del mes.
     */
    private function resolveExpensesKpi(
        Carbon $start,
        Carbon $end,
        Carbon $prevStart,
        Carbon $prevEnd,
        ?int $storeId
    ): array {
        $expensesQuery = Expense::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $totalExpenses = (float) (clone $expensesQuery)->sum('amount');
        $expensesCount = (int) (clone $expensesQuery)->count();

        $supplierExpenses = (float) (clone $expensesQuery)
            ->where('reference_type', 'like', '%PurchaseOrder%')
            ->sum('amount');

        $prevExpenses = (float) Expense::whereBetween('date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->sum('amount');

        return [
            'total_amount'       => $totalExpenses,
            'operational_amount' => max(0, $totalExpenses - $supplierExpenses),
            'supplier_amount'    => $supplierExpenses,
            'count'              => $expensesCount,
            'prev_amount'        => $prevExpenses,
            'growth_pct'         => $this->calcGrowth($totalExpenses, $prevExpenses),
        ];
    }

    /**
     * KPI 13: Cartera de clientes (Cuentas por cobrar activas en Ventas/Apartados y Pedidos Especiales).
     */
    private function resolveReceivablesKpi(?int $storeId): array
    {
        $posReceivables = Sale::where('status', '!=', 'cancelada')
            ->where('balance', '>', 0)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $posBalance     = (float) (clone $posReceivables)->sum('balance');
        $posDebtorsCnt  = (int) (clone $posReceivables)->count();
        $posCustomerIds = (clone $posReceivables)->whereNotNull('customer_id')->pluck('customer_id')->toArray();

        $activeSpecialOrders = SpecialOrder::whereNotIn('status', ['cancelled', 'delivered'])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->withSum('payments as paid_sum', 'amount')
            ->get();

        $specialBalance    = 0.0;
        $specialDebtorsCnt = 0;
        $specialCustIds    = [];

        foreach ($activeSpecialOrders as $so) {
            $bal = max(0, (float) $so->total - (float) ($so->paid_sum ?? 0));
            if ($bal > 0.01) {
                $specialBalance += $bal;
                $specialDebtorsCnt++;
                if ($so->customer_id) {
                    $specialCustIds[] = $so->customer_id;
                }
            }
        }

        $uniqueCustomersWithDebt = count(array_unique(array_merge($posCustomerIds, $specialCustIds)));
        $totalActiveCustomers    = Customer::where('status', true)->count();

        return [
            'total_balance'           => $posBalance + $specialBalance,
            'pos_balance'             => $posBalance,
            'special_balance'         => $specialBalance,
            'pos_accounts_count'      => $posDebtorsCnt,
            'special_accounts_count'  => $specialDebtorsCnt,
            'customers_with_debt'     => $uniqueCustomersWithDebt,
            'total_active_customers'  => $totalActiveCustomers,
        ];
    }

    /**
     * KPI 14: Deuda con proveedores (Órdenes de compra activas pendientes de liquidar).
     */
    private function resolvePayablesKpi(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $openOrders = PurchaseOrder::where('status', '!=', 'cancelada')
            ->where('payment_status', '!=', 'pagada')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->get();

        $totalDebt    = 0.0;
        $supplierIds  = [];
        $ordersInDebt = 0;

        foreach ($openOrders as $po) {
            $pending = $po->pendingBalance();
            if ($pending > 0.01) {
                $totalDebt += $pending;
                $ordersInDebt++;
                if ($po->supplier_id) {
                    $supplierIds[] = $po->supplier_id;
                }
            }
        }

        $paidThisMonth = (float) SupplierPayment::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->sum('amount');

        return [
            'total_debt'           => $totalDebt,
            'pending_orders_count' => $ordersInDebt,
            'suppliers_count'      => count(array_unique($supplierIds)),
            'paid_this_month'      => $paidThisMonth,
        ];
    }

    /**
     * EXTRA 2: Utilidad Bruta Comercial Estimada y Flujo de Caja Neto del Mes.
     */
    private function resolveProfitabilityKpi(
        Carbon $start,
        Carbon $end,
        ?int $storeId,
        float $netSalesTotal,
        float $netCollections,
        float $totalExpenses
    ): array {
        $posCogs = (float) DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
            ->whereNull('s.deleted_at')
            ->where('s.status', '!=', 'cancelada')
            ->whereBetween('s.created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('s.store_id', $storeId))
            ->selectRaw('COALESCE(SUM(si.quantity * COALESCE(NULLIF(si.base_cost, 0), p.base_cost, 0)), 0) as cogs')
            ->value('cogs');

        $specialCogs = (float) DB::table('special_order_items as soi')
            ->join('special_orders as so', 'so.id', '=', 'soi.special_order_id')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->whereNull('so.deleted_at')
            ->where('so.status', '!=', 'cancelled')
            ->whereBetween('so.created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('so.store_id', $storeId))
            ->selectRaw('COALESCE(SUM(soi.quantity * COALESCE(p.base_cost, 0)), 0) as cogs')
            ->value('cogs');

        $totalCogs      = $posCogs + $specialCogs;
        $grossProfit    = $netSalesTotal - $totalCogs;
        $grossMarginPct = $netSalesTotal > 0 ? round(($grossProfit / $netSalesTotal) * 100, 1) : 0.0;
        $netCashFlow    = $netCollections - $totalExpenses;

        return [
            'cogs'             => $totalCogs,
            'gross_profit'     => $grossProfit,
            'gross_margin_pct' => $grossMarginPct,
            'net_cash_flow'    => $netCashFlow,
        ];
    }

    /**
     * EXTRA 5: Embudo de Conversión de Cotizaciones Formales.
     */
    private function resolveQuotesFunnelKpi(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $formalQuotes = Quote::whereNotNull('folio')
            ->where('folio', '!=', '')
            ->whereBetween('created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId));

        $totalQuotes     = (int) (clone $formalQuotes)->count();
        $convertedQuotes = (int) (clone $formalQuotes)->where('status', 'convertida')->count();
        $openQuotesQuery = (clone $formalQuotes)->whereNotIn('status', ['convertida', 'cancelada']);
        $openQuotesCount = (int) (clone $openQuotesQuery)->count();
        $openQuotesAmt   = (float) (clone $openQuotesQuery)->sum('total');

        return [
            'total_quotes'        => $totalQuotes,
            'converted_quotes'    => $convertedQuotes,
            'open_quotes_count'   => $openQuotesCount,
            'open_quotes_amount'  => $openQuotesAmt,
            'conversion_rate_pct' => $totalQuotes > 0 ? round(($convertedQuotes / $totalQuotes) * 100, 1) : 0.0,
        ];
    }

    /**
     * SECCIÓN 2: Construcción de las 5 Gráficas Requeridas + Extra 4 (Métodos de Pago).
     */
    private function buildCharts(int $year, Carbon $startOfMonth, Carbon $endOfMonth, ?int $storeId): array
    {
        return [
            'annual_sales'    => $this->buildAnnualSalesChart($storeId),
            'monthly_sales'   => $this->buildMonthlySalesChart($year, $storeId),
            'products_donut'  => $this->buildProductsDonutChart($startOfMonth, $endOfMonth, $storeId),
            'store_revenue'   => $this->buildStoreRevenueChart($startOfMonth, $endOfMonth),
            'seller_sales'    => $this->buildSellerSalesChart($startOfMonth, $endOfMonth, $storeId),
            'payment_methods' => $this->buildPaymentMethodsChart($startOfMonth, $endOfMonth, $storeId),
        ];
    }

    /**
     * Gráfica 1: Ventas Anuales (Histórico comparativo por año).
     */
    private function buildAnnualSalesChart(?int $storeId): array
    {
        $years = array_reverse($this->getAvailableYears());

        $categories    = [];
        $posSeries     = [];
        $specialSeries = [];
        $netSeries     = [];
        $totalSeries   = [];

        foreach ($years as $y) {
            $yStart = Carbon::createFromDate($y, 1, 1)->startOfYear();
            $yEnd   = (clone $yStart)->endOfYear();

            $posRow = Sale::whereBetween('created_at', [$yStart, $yEnd])
                ->where('status', '!=', 'cancelada')
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->selectRaw('
                    COALESCE(SUM(total), 0) as sum_total,
                    COALESCE(SUM(GREATEST(0, subtotal - COALESCE(coupon_discount, 0))), 0) as sum_net
                ')->first();

            $specialTotal = (float) SpecialOrder::whereBetween('created_at', [$yStart, $yEnd])
                ->where('status', '!=', 'cancelled')
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->sum('total');

            $posTotal   = round((float) ($posRow->sum_total ?? 0), 2);
            $specialTotal = round($specialTotal, 2);
            $netTotal   = round((float) ($posRow->sum_net ?? 0) + ($specialTotal / 1.16), 2);

            $categories[]    = (string) $y;
            $posSeries[]     = $posTotal;
            $specialSeries[] = $specialTotal;
            $netSeries[]     = $netTotal;
            $totalSeries[]   = round($posTotal + $specialTotal, 2);
        }

        return [
            'categories'     => $categories,
            'pos_series'     => $posSeries,
            'special_series' => $specialSeries,
            'net_series'     => $netSeries,
            'total_series'   => $totalSeries,
        ];
    }

    /**
     * Gráfica 2: Ventas Mensuales del Año en Curso (Ene - Dic).
     */
    private function buildMonthlySalesChart(int $year, ?int $storeId): array
    {
        $categories        = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $posSeries         = array_fill(0, 12, 0.0);
        $specialSeries     = array_fill(0, 12, 0.0);
        $netSeries         = array_fill(0, 12, 0.0);
        $collectionsSeries = array_fill(0, 12, 0.0);

        for ($m = 1; $m <= 12; $m++) {
            $mStart = Carbon::createFromDate($year, $m, 1)->startOfMonth();
            $mEnd   = (clone $mStart)->endOfMonth();
            $idx    = $m - 1;

            $posRow = Sale::whereBetween('created_at', [$mStart, $mEnd])
                ->where('status', '!=', 'cancelada')
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->selectRaw('
                    COALESCE(SUM(total), 0) as sum_total,
                    COALESCE(SUM(GREATEST(0, subtotal - COALESCE(coupon_discount, 0))), 0) as sum_net
                ')->first();

            $specTotal = (float) SpecialOrder::whereBetween('created_at', [$mStart, $mEnd])
                ->where('status', '!=', 'cancelled')
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->sum('total');

            $posCol = (float) Payment::whereBetween('payments.created_at', [$mStart, $mEnd])
                ->whereHas('sale', function ($q) use ($storeId) {
                    $q->where('status', '!=', 'cancelada')
                      ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
                })->sum('amount');

            $specCol = (float) SpecialOrderPayment::whereBetween('special_order_payments.created_at', [$mStart, $mEnd])
                ->whereHas('specialOrder', function ($q) use ($storeId) {
                    $q->where('status', '!=', 'cancelled')
                      ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
                })->sum('amount');

            $posTotal = round((float) ($posRow->sum_total ?? 0), 2);
            $specTotal = round($specTotal, 2);

            $posSeries[$idx]         = $posTotal;
            $specialSeries[$idx]     = $specTotal;
            $netSeries[$idx]         = round((float) ($posRow->sum_net ?? 0) + ($specTotal / 1.16), 2);
            $collectionsSeries[$idx] = round($posCol + $specCol, 2);
        }

        return [
            'categories'         => $categories,
            'pos_series'         => $posSeries,
            'special_series'     => $specialSeries,
            'net_series'         => $netSeries,
            'collections_series' => $collectionsSeries,
        ];
    }

    /**
     * Gráfica 3: Ventas del mes actual por producto (Dona Comparativa Top 5 + Otros).
     */
    private function buildProductsDonutChart(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $posItems = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
            ->whereNull('s.deleted_at')
            ->where('s.status', '!=', 'cancelada')
            ->whereBetween('s.created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('s.store_id', $storeId))
            ->selectRaw('
                COALESCE(p.id, 0) as pid,
                COALESCE(p.name, "Producto Comodín / Servicio") as pname,
                SUM(si.total) as sum_amount,
                SUM(si.quantity) as sum_qty
            ')
            ->groupBy('pid', 'pname')
            ->get();

        $specialItems = DB::table('special_order_items as soi')
            ->join('special_orders as so', 'so.id', '=', 'soi.special_order_id')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->whereNull('so.deleted_at')
            ->where('so.status', '!=', 'cancelled')
            ->whereBetween('so.created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('so.store_id', $storeId))
            ->selectRaw('
                COALESCE(p.id, 0) as pid,
                COALESCE(p.name, "Pedido Especial Personalizado") as pname,
                SUM(soi.quantity * soi.unit_price) as sum_amount,
                SUM(soi.quantity) as sum_qty
            ')
            ->groupBy('pid', 'pname')
            ->get();

        $merged = [];
        foreach ($posItems as $item) {
            $key = $item->pid . '_' . $item->pname;
            $merged[$key] = [
                'name'           => $item->pname,
                'pos_amount'     => (float) $item->sum_amount,
                'special_amount' => 0.0,
                'total_amount'   => (float) $item->sum_amount,
                'total_qty'      => (int) $item->sum_qty,
            ];
        }

        foreach ($specialItems as $item) {
            $key = $item->pid . '_' . $item->pname;
            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'name'           => $item->pname,
                    'pos_amount'     => 0.0,
                    'special_amount' => 0.0,
                    'total_amount'   => 0.0,
                    'total_qty'      => 0,
                ];
            }
            $merged[$key]['special_amount'] += (float) $item->sum_amount;
            $merged[$key]['total_amount']   += (float) $item->sum_amount;
            $merged[$key]['total_qty']      += (int) $item->sum_qty;
        }

        usort($merged, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);

        $topSlice = array_slice($merged, 0, 5);
        $rest     = array_slice($merged, 5);

        if (!empty($rest)) {
            $othersPos     = 0.0;
            $othersSpecial = 0.0;
            $othersTotal   = 0.0;
            $othersQty     = 0;

            foreach ($rest as $r) {
                $othersPos     += $r['pos_amount'];
                $othersSpecial += $r['special_amount'];
                $othersTotal   += $r['total_amount'];
                $othersQty     += $r['total_qty'];
            }

            $topSlice[] = [
                'name'           => 'Otros (' . count($rest) . ' prod.)',
                'pos_amount'     => round($othersPos, 2),
                'special_amount' => round($othersSpecial, 2),
                'total_amount'   => round($othersTotal, 2),
                'total_qty'      => $othersQty,
            ];
        }

        $labels       = [];
        $amountSeries = [];
        $qtySeries    = [];

        foreach ($topSlice as $slice) {
            $labels[]       = \Illuminate\Support\Str::limit($slice['name'], 28);
            $amountSeries[] = round($slice['total_amount'], 2);
            $qtySeries[]    = (int) $slice['total_qty'];
        }

        return [
            'labels'        => $labels,
            'amount_series' => $amountSeries,
            'qty_series'    => $qtySeries,
            'breakdown'     => $topSlice,
        ];
    }

    /**
     * Gráfica 4: Ingreso del mes por sucursal (desglosando Ventas POS vs Pedidos Especiales).
     */
    private function buildStoreRevenueChart(Carbon $start, Carbon $end): array
    {
        $stores = Store::where('status', 1)->orderBy('name')->get();

        $rows = [];
        foreach ($stores as $store) {
            $posAmt = (float) Sale::where('store_id', $store->id)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', '!=', 'cancelada')
                ->sum('total');

            $specAmt = (float) SpecialOrder::where('store_id', $store->id)
                ->whereBetween('created_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $rows[] = [
                'name'           => $store->name,
                'pos_amount'     => round($posAmt, 2),
                'special_amount' => round($specAmt, 2),
                'total_amount'   => round($posAmt + $specAmt, 2),
            ];
        }

        usort($rows, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);

        return [
            'labels'         => array_column($rows, 'name'),
            'pos_series'     => array_column($rows, 'pos_amount'),
            'special_series' => array_column($rows, 'special_amount'),
            'total_series'   => array_column($rows, 'total_amount'),
        ];
    }

    /**
     * Gráfica 5: Ventas del mes por vendedor (desglosando Ventas POS vs Pedidos Especiales).
     */
    private function buildSellerSalesChart(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $posBySeller = Sale::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelada')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('COALESCE(seller_id, user_id) as uid, SUM(total) as total_amount, COUNT(*) as cnt')
            ->groupBy('uid')
            ->get();

        $specBySeller = SpecialOrder::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('user_id as uid, SUM(total) as total_amount, COUNT(*) as cnt')
            ->groupBy('uid')
            ->get();

        $sellersMap = [];
        foreach ($posBySeller as $r) {
            if (!$r->uid) continue;
            $sellersMap[$r->uid] = [
                'uid'            => (int) $r->uid,
                'pos_amount'     => (float) $r->total_amount,
                'special_amount' => 0.0,
                'total_amount'   => (float) $r->total_amount,
                'tickets_count'  => (int) $r->cnt,
            ];
        }

        foreach ($specBySeller as $r) {
            if (!$r->uid) continue;
            if (!isset($sellersMap[$r->uid])) {
                $sellersMap[$r->uid] = [
                    'uid'            => (int) $r->uid,
                    'pos_amount'     => 0.0,
                    'special_amount' => 0.0,
                    'total_amount'   => 0.0,
                    'tickets_count'  => 0,
                ];
            }
            $sellersMap[$r->uid]['special_amount'] += (float) $r->total_amount;
            $sellersMap[$r->uid]['total_amount']   += (float) $r->total_amount;
            $sellersMap[$r->uid]['tickets_count']  += (int) $r->cnt;
        }

        usort($sellersMap, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);
        $topSellers = array_slice($sellersMap, 0, 8);

        $users = User::whereIn('id', array_column($topSellers, 'uid'))->get()->keyBy('id');

        $labels        = [];
        $posSeries     = [];
        $specialSeries = [];
        $totalSeries   = [];
        $ticketsCount  = [];

        foreach ($topSellers as $s) {
            $u = $users->get($s['uid']);
            $labels[]        = $u ? \Illuminate\Support\Str::limit(trim($u->name . ' ' . ($u->lastname ?? '')), 22) : ('Vendedor #' . $s['uid']);
            $posSeries[]     = round($s['pos_amount'], 2);
            $specialSeries[] = round($s['special_amount'], 2);
            $totalSeries[]   = round($s['total_amount'], 2);
            $ticketsCount[]  = $s['tickets_count'];
        }

        return [
            'labels'         => $labels,
            'pos_series'     => $posSeries,
            'special_series' => $specialSeries,
            'total_series'   => $totalSeries,
            'tickets_count'  => $ticketsCount,
        ];
    }

    /**
     * EXTRA 4: Gráfica de Ingresos por Método de Pago y Comisiones de Terminal.
     */
    private function buildPaymentMethodsChart(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $posPayments = Payment::whereBetween('payments.created_at', [$start, $end])
            ->whereHas('sale', function ($q) use ($storeId) {
                $q->where('status', '!=', 'cancelada')
                  ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
            })
            ->with('paymentMethod')
            ->get();

        $specPayments = SpecialOrderPayment::whereBetween('special_order_payments.created_at', [$start, $end])
            ->whereHas('specialOrder', function ($q) use ($storeId) {
                $q->where('status', '!=', 'cancelled')
                  ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
            })
            ->with('paymentMethod')
            ->get();

        $methods = [];

        foreach ($posPayments as $p) {
            $mName = $p->paymentMethod?->name ?? 'Otro Método';
            if (!isset($methods[$mName])) {
                $methods[$mName] = ['name' => $mName, 'amount' => 0.0, 'commission' => 0.0, 'count' => 0];
            }
            $methods[$mName]['amount']     += (float) $p->amount;
            $methods[$mName]['commission'] += (float) ($p->terminal_commission ?? 0);
            $methods[$mName]['count']++;
        }

        foreach ($specPayments as $sp) {
            $mName = $sp->paymentMethod?->name ?? 'Otro Método';
            if (!isset($methods[$mName])) {
                $methods[$mName] = ['name' => $mName, 'amount' => 0.0, 'commission' => 0.0, 'count' => 0];
            }
            $methods[$mName]['amount'] += (float) $sp->amount;
            $methods[$mName]['count']++;
        }

        usort($methods, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return [
            'labels'             => array_column($methods, 'name'),
            'amount_series'      => array_map(fn($m) => round($m['amount'], 2), $methods),
            'commissions_series' => array_map(fn($m) => round($m['commission'], 2), $methods),
            'breakdown'          => $methods,
        ];
    }

    /**
     * SECCIÓN 3: Construcción de las 4 Tablas Operativas + Pestaña 5 (Extra 3).
     */
    private function buildTables(Carbon $start, Carbon $end, ?int $storeId): array
    {
        return [
            'sales'             => $this->buildMonthlySalesTable($start, $end, $storeId),
            'deliveries'        => $this->buildPendingDeliveriesTable($start, $end, $storeId),
            'customer_payments' => $this->buildCustomerPaymentsTable($start, $end, $storeId),
            'expenses'          => $this->buildExpensesTable($start, $end, $storeId),
            'inventory_alerts'  => $this->buildInventoryAndTransfersTable($storeId),
        ];
    }

    /**
     * Tabla 1: Ventas del mes (Consolidada POS + Pedidos Especiales con diferenciador y pie de sumatorias).
     */
    private function buildMonthlySalesTable(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $posSales = Sale::with(['customer', 'seller', 'cashier', 'store'])
            ->whereBetween('created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->orderByDesc('created_at')
            ->get();

        $specialOrders = SpecialOrder::with(['customer', 'seller', 'store'])
            ->withSum('payments as paid_sum', 'amount')
            ->whereBetween('created_at', [$start, $end])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->orderByDesc('created_at')
            ->get();

        $rows = [];
        $sumTotal        = 0.0;
        $sumBalance      = 0.0;
        $sumNet          = 0.0;
        $posSumTotal     = 0.0;
        $posSumBalance   = 0.0;
        $specSumTotal    = 0.0;
        $specSumBalance  = 0.0;

        foreach ($posSales as $sale) {
            $isCancelled = $sale->status === 'cancelada';
            $total       = (float) $sale->total;
            $balance     = $isCancelled ? 0.0 : (float) $sale->balance;
            $net         = max(0, (float) $sale->subtotal - (float) ($sale->coupon_discount ?? 0));

            if (!$isCancelled) {
                $sumTotal      += $total;
                $sumBalance    += $balance;
                $sumNet        += $net;
                $posSumTotal   += $total;
                $posSumBalance += $balance;
            }

            $statusBadge = match ($sale->status) {
                'pagada', 'completada' => ['label' => 'Liquidada', 'color' => 'success'],
                'apartado'             => ['label' => 'Apartado (Saldo)', 'color' => 'warning'],
                'cancelada'            => ['label' => 'Cancelada', 'color' => 'danger'],
                default                => ['label' => ucfirst($sale->status), 'color' => 'info'],
            };

            $rows[] = [
                'timestamp'      => $sale->created_at?->timestamp ?? 0,
                'date_formatted' => $sale->created_at?->format('d/m/Y H:i') ?? '—',
                'folio'          => $sale->ticket_folio,
                'type'           => 'pos',
                'type_label'     => 'Venta POS',
                'type_badge'     => 'primary',
                'customer_name'  => $sale->customer?->full_name ?? 'Público en General',
                'seller_name'    => $sale->seller?->name ?? ($sale->cashier?->name ?? 'Sistema'),
                'store_name'     => $sale->store?->name ?? 'General',
                'status_label'   => $statusBadge['label'],
                'status_color'   => $statusBadge['color'],
                'is_cancelled'   => $isCancelled,
                'net_amount'     => $net,
                'total'          => $total,
                'amount_paid'    => (float) $sale->amount_paid,
                'balance'        => $balance,
                'url'            => route('pos.sales.show', $sale->id),
            ];
        }

        foreach ($specialOrders as $order) {
            $isCancelled = $order->status === 'cancelled';
            $total       = (float) $order->total;
            $paid        = (float) ($order->paid_sum ?? 0);
            $balance     = $isCancelled ? 0.0 : max(0, $total - $paid);
            $net         = round($total / 1.16, 2);

            if (!$isCancelled) {
                $sumTotal       += $total;
                $sumBalance     += $balance;
                $sumNet         += $net;
                $specSumTotal   += $total;
                $specSumBalance += $balance;
            }

            $statusColor = match ($order->status) {
                'delivered' => 'success',
                'ready'     => 'info',
                'ordered', 'partial' => 'primary',
                'pending'   => 'warning',
                'cancelled' => 'danger',
                default     => 'secondary',
            };

            $rows[] = [
                'timestamp'      => $order->created_at?->timestamp ?? 0,
                'date_formatted' => $order->created_at?->format('d/m/Y H:i') ?? '—',
                'folio'          => $order->folio,
                'type'           => 'special',
                'type_label'     => 'Pedido Especial',
                'type_badge'     => 'info',
                'customer_name'  => $order->customer?->full_name ?? 'Público en General',
                'seller_name'    => $order->seller?->name ?? 'Sistema',
                'store_name'     => $order->store?->name ?? 'General',
                'status_label'   => $order->statusLabel(),
                'status_color'   => $statusColor,
                'is_cancelled'   => $isCancelled,
                'net_amount'     => $net,
                'total'          => $total,
                'amount_paid'    => $paid,
                'balance'        => $balance,
                'url'            => route('special-orders.show', $order->id),
            ];
        }

        usort($rows, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return [
            'rows'   => $rows,
            'totals' => [
                'sum_total'          => $sumTotal,
                'sum_balance'        => $sumBalance,
                'sum_net'            => $sumNet,
                'pos_sum_total'      => $posSumTotal,
                'pos_sum_balance'    => $posSumBalance,
                'special_sum_total'  => $specSumTotal,
                'special_sum_balance'=> $specSumBalance,
                'count'              => count($rows),
            ],
        ];
    }

    /**
     * Tabla 2: Pedidos por entregar con cálculo semafórico de "Días Restantes".
     */
    private function buildPendingDeliveriesTable(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $today = Carbon::today();

        // Ventas POS con entrega a domicilio pendiente o programada en el mes
        $posDeliveries = Sale::with(['customer', 'store', 'seller'])
            ->where('is_delivery', true)
            ->where('status', '!=', 'cancelada')
            ->where(function ($q) use ($start, $end) {
                $q->whereIn('delivery_status', ['pendiente', 'en_ruta'])
                  ->orWhereNull('delivery_status')
                  ->orWhereBetween('delivery_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->where(function ($q) {
                $q->where('delivery_status', '!=', 'entregado')
                  ->orWhereNull('delivery_status');
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->get();

        // Pedidos Especiales pendientes de entrega
        $specialDeliveries = SpecialOrder::with(['customer', 'store', 'seller'])
            ->withSum('payments as paid_sum', 'amount')
            ->whereNotIn('status', ['cancelled', 'delivered'])
            ->where(function ($q) {
                $q->where('delivery_status', '!=', 'entregado')
                  ->orWhereNull('delivery_status');
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->get();

        $rows    = [];
        $sumTotal   = 0.0;
        $sumBalance = 0.0;

        foreach ($posDeliveries as $sale) {
            $targetDate = $sale->delivery_date;
            $daysInfo   = $this->calculateDaysRemainingBadge($targetDate, $today);

            $sumTotal   += (float) $sale->total;
            $sumBalance += (float) $sale->balance;

            $rows[] = [
                'sort_days'        => $daysInfo['sort_key'],
                'folio'            => $sale->ticket_folio,
                'type'             => 'pos',
                'type_label'       => 'Envío POS',
                'type_badge'       => 'primary',
                'customer_name'    => $sale->delivery_recipient_name ?: ($sale->customer?->full_name ?? 'Público en General'),
                'address'          => $sale->delivery_address ?: 'Entrega en sucursal / Sin dirección',
                'store_name'       => $sale->store?->name ?? 'General',
                'scheduled_date'   => $targetDate ? $targetDate->format('d/m/Y') : 'Por definir',
                'scheduled_time'   => $sale->formatted_delivery_time ?: '',
                'logistics_status' => $sale->delivery_status === 'en_ruta' ? 'En Ruta' : 'Pendiente Envío',
                'logistics_color'  => $sale->delivery_status === 'en_ruta' ? 'info' : 'warning',
                'days_remaining'   => $daysInfo['days'],
                'days_label'       => $daysInfo['label'],
                'days_color'       => $daysInfo['color'],
                'total'            => (float) $sale->total,
                'balance'          => (float) $sale->balance,
                'url'              => route('pos.sales.show', $sale->id),
            ];
        }

        foreach ($specialDeliveries as $order) {
            $targetDate = $order->delivery_date ?: $order->expected_delivery_date;
            $daysInfo   = $this->calculateDaysRemainingBadge($targetDate, $today);

            $total   = (float) $order->total;
            $balance = max(0, $total - (float) ($order->paid_sum ?? 0));

            $sumTotal   += $total;
            $sumBalance += $balance;

            $rows[] = [
                'sort_days'        => $daysInfo['sort_key'],
                'folio'            => $order->folio,
                'type'             => 'special',
                'type_label'       => 'Pedido Especial',
                'type_badge'       => 'info',
                'customer_name'    => $order->delivery_recipient_name ?: ($order->customer?->full_name ?? 'Público en General'),
                'address'          => $order->is_delivery ? ($order->delivery_address ?: 'Envío a domicilio') : 'Recoge en Sucursal',
                'store_name'       => $order->store?->name ?? 'General',
                'scheduled_date'   => $targetDate ? $targetDate->format('d/m/Y') : 'Por definir',
                'scheduled_time'   => $order->formatted_delivery_time ?: '',
                'logistics_status' => $order->statusLabel(),
                'logistics_color'  => $order->status === 'ready' ? 'success' : 'primary',
                'days_remaining'   => $daysInfo['days'],
                'days_label'       => $daysInfo['label'],
                'days_color'       => $daysInfo['color'],
                'total'            => $total,
                'balance'          => $balance,
                'url'              => route('special-orders.show', $order->id),
            ];
        }

        // Ordenar por urgencia: los más atrasados primero
        usort($rows, fn($a, $b) => $a['sort_days'] <=> $b['sort_days']);

        return [
            'rows'   => $rows,
            'totals' => [
                'count'       => count($rows),
                'sum_total'   => $sumTotal,
                'sum_balance' => $sumBalance,
            ],
        ];
    }

    /**
     * Calcula los días restantes y el semáforo visual de urgencia logística.
     */
    private function calculateDaysRemainingBadge(?Carbon $targetDate, Carbon $today): array
    {
        if (!$targetDate) {
            return [
                'days'     => null,
                'sort_key' => 9999,
                'label'    => 'Sin fecha asignada',
                'color'    => 'secondary',
            ];
        }

        $target = Carbon::parse($targetDate->toDateString());
        $diff   = (int) $today->diffInDays($target, false);

        if ($diff < 0) {
            $abs = abs($diff);
            return [
                'days'     => $diff,
                'sort_key' => $diff,
                'label'    => "Retrasado {$abs} " . ($abs === 1 ? 'día' : 'días'),
                'color'    => 'danger',
            ];
        }

        if ($diff === 0) {
            return [
                'days'     => 0,
                'sort_key' => 0,
                'label'    => 'Entrega Hoy',
                'color'    => 'warning',
            ];
        }

        if ($diff <= 3) {
            return [
                'days'     => $diff,
                'sort_key' => $diff,
                'label'    => "Faltan {$diff} " . ($diff === 1 ? 'día' : 'días'),
                'color'    => 'info',
            ];
        }

        return [
            'days'     => $diff,
            'sort_key' => $diff,
            'label'    => "En {$diff} días",
            'color'    => 'success',
        ];
    }

    /**
     * Tabla 3: Pagos del mes ejecutados por clientes con pie de tabla (Montos, Comisiones y Totales).
     */
    private function buildCustomerPaymentsTable(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $posPayments = Payment::with(['sale.customer', 'sale.store', 'cashier', 'paymentMethod', 'terminal'])
            ->whereBetween('payments.created_at', [$start, $end])
            ->whereHas('sale', function ($q) use ($storeId) {
                $q->where('status', '!=', 'cancelada')
                  ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
            })
            ->orderByDesc('created_at')
            ->get();

        $specPayments = SpecialOrderPayment::with(['specialOrder.customer', 'specialOrder.store', 'registeredBy', 'paymentMethod', 'paymentTerminal'])
            ->whereBetween('special_order_payments.created_at', [$start, $end])
            ->whereHas('specialOrder', function ($q) use ($storeId) {
                $q->where('status', '!=', 'cancelled')
                  ->when($storeId, fn($sq) => $sq->where('store_id', $storeId));
            })
            ->orderByDesc('created_at')
            ->get();

        $rows          = [];
        $sumAmount     = 0.0;
        $sumCommission = 0.0;
        $sumNetTotal   = 0.0;

        foreach ($posPayments as $p) {
            $amount     = (float) $p->amount;
            $commission = (float) ($p->terminal_commission ?? 0);
            $netTotal   = max(0, $amount - $commission);

            $sumAmount     += $amount;
            $sumCommission += $commission;
            $sumNetTotal   += $netTotal;

            $rows[] = [
                'timestamp'      => $p->created_at?->timestamp ?? 0,
                'date_formatted' => $p->created_at?->format('d/m/Y H:i') ?? '—',
                'folio'          => $p->sale?->ticket_folio ?? ('VTA #' . $p->sale_id),
                'origin_type'    => 'pos',
                'origin_label'   => 'Venta POS',
                'origin_badge'   => 'primary',
                'customer_name'  => $p->sale?->customer?->full_name ?? 'Público en General',
                'cashier_name'   => $p->cashier?->name ?? 'Sistema',
                'store_name'     => $p->sale?->store?->name ?? 'General',
                'method_name'    => $p->paymentMethod?->name ?? 'Efectivo',
                'terminal_name'  => $p->terminal?->name ?? null,
                'msi'            => (int) ($p->msi_applied ?? 0),
                'amount'         => $amount,
                'commission'     => $commission,
                'net_total'      => $netTotal,
                'url'            => $p->sale_id ? route('pos.sales.show', $p->sale_id) : '#',
            ];
        }

        foreach ($specPayments as $sp) {
            $amount     = (float) $sp->amount;
            $commission = 0.0;
            $netTotal   = $amount;

            $sumAmount   += $amount;
            $sumNetTotal += $netTotal;

            $rows[] = [
                'timestamp'      => $sp->created_at?->timestamp ?? 0,
                'date_formatted' => $sp->created_at?->format('d/m/Y H:i') ?? '—',
                'folio'          => $sp->specialOrder?->folio ?? ('PED #' . $sp->special_order_id),
                'origin_type'    => 'special',
                'origin_label'   => 'Pedido Especial',
                'origin_badge'   => 'info',
                'customer_name'  => $sp->specialOrder?->customer?->full_name ?? 'Público en General',
                'cashier_name'   => $sp->registeredBy?->name ?? 'Sistema',
                'store_name'     => $sp->specialOrder?->store?->name ?? 'General',
                'method_name'    => $sp->paymentMethod?->name ?? 'Anticipo',
                'terminal_name'  => $sp->paymentTerminal?->name ?? null,
                'msi'            => 0,
                'amount'         => $amount,
                'commission'     => $commission,
                'net_total'      => $netTotal,
                'url'            => $sp->special_order_id ? route('special-orders.show', $sp->special_order_id) : '#',
            ];
        }

        usort($rows, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return [
            'rows'   => $rows,
            'totals' => [
                'count'          => count($rows),
                'sum_amount'     => $sumAmount,
                'sum_commission' => $sumCommission,
                'sum_total'      => $sumNetTotal,
            ],
        ];
    }

    /**
     * Tabla 4: Gastos empresariales del mes con pie de tabla de sumatoria total.
     */
    private function buildExpensesTable(Carbon $start, Carbon $end, ?int $storeId): array
    {
        $expenses = Expense::with(['store', 'user'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $rows     = [];
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

            $rows[] = [
                'id'             => $exp->id,
                'date_formatted' => $exp->date ? $exp->date->format('d/m/Y') : '—',
                'concept'        => $exp->concept,
                'category_label' => $catLabel,
                'category_color' => $catColor,
                'store_name'     => $exp->store?->name ?? 'Corporativo / Global',
                'user_name'      => $exp->user?->name ?? 'Sistema',
                'notes'          => $exp->notes ?: '—',
                'amount'         => $amt,
            ];
        }

        return [
            'rows'   => $rows,
            'totals' => [
                'count'     => count($rows),
                'sum_total' => $sumTotal,
            ],
        ];
    }

    /**
     * Pestaña 5 (Extra 3): Alertas de Stock Crítico y Traspasos en Tránsito.
     */
    private function buildInventoryAndTransfersTable(?int $storeId): array
    {
        $alerts = Inventory::alerts()
            ->with(['product', 'variant', 'store', 'warehouse'])
            ->when($storeId, fn($q) => $q->forStore($storeId))
            ->orderBy('quantity', 'asc')
            ->limit(25)
            ->get()
            ->map(function (Inventory $inv) {
                return [
                    'id'                 => $inv->id,
                    'product_id'         => $inv->product_id,
                    'sku'                => $inv->variant?->sku ?: ($inv->product?->sku ?? 'S/N'),
                    'product_name'       => $inv->variant ? $inv->variant->display_name : ($inv->product?->name ?? 'Producto'),
                    'store_name'         => $inv->store?->name ?? 'General',
                    'warehouse_name'     => $inv->warehouse?->name ?? 'Principal',
                    'quantity'           => (int) $inv->quantity,
                    'reserved_quantity'  => (int) $inv->reserved_quantity,
                    'available_quantity' => (int) $inv->available_quantity,
                    'min_stock'          => (int) $inv->min_stock,
                    'deficit'            => max(0, (int) $inv->min_stock - (int) $inv->quantity),
                ];
            })
            ->toArray();

        $transfers = Transfer::with(['originStore', 'destinationStore', 'creator'])
            ->withCount('items')
            ->whereIn('status', ['pendiente', 'en_transito'])
            ->when($storeId, function ($q) use ($storeId) {
                $q->where(function ($sq) use ($storeId) {
                    $sq->where('origin_store_id', $storeId)
                       ->orWhere('destination_store_id', $storeId);
                });
            })
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(function (Transfer $t) {
                return [
                    'id'                => $t->id,
                    'folio'             => $t->folio,
                    'origin_store'      => $t->originStore?->name ?? 'Origen',
                    'destination_store' => $t->destinationStore?->name ?? 'Destino',
                    'status'            => $t->status,
                    'status_label'      => $t->status === 'en_transito' ? 'En Tránsito' : 'Pendiente Envío',
                    'status_color'      => $t->status === 'en_transito' ? 'info' : 'warning',
                    'items_count'       => (int) $t->items_count,
                    'date_formatted'    => $t->created_at?->format('d/m/Y H:i') ?? '—',
                    'url'               => route('transfers.show', $t->id),
                ];
            })
            ->toArray();

        return [
            'stock_alerts'     => $alerts,
            'active_transfers' => $transfers,
            'alerts_count'     => count($alerts),
            'transfers_count'  => count($transfers),
        ];
    }
}
