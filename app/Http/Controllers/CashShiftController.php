<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashShift;
use App\Models\CashMovement;
use App\Models\Expense;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashShiftController extends Controller
{
    /**
     * Construye la consulta filtrada de turnos según permisos de usuario y parámetros de búsqueda.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $user        = auth()->user();
        $canAuditAll = $user->hasRole('Super Admin') || $user->can('auditar-cortes-caja') || $user->can('vista-global');

        $query = CashShift::with(['store', 'cashier', 'closedBy']);

        // Filtro de sucursal
        if ($canAuditAll) {
            if ($request->filled('store_id') && $request->store_id !== 'all') {
                $query->where('store_id', $request->store_id);
            }
        } else {
            // Usuario estándar (Vendedor u Operativo sin permiso de auditar todos)
            $storeId = current_store_id();
            if ($storeId && $storeId !== 'global') {
                $query->where('store_id', $storeId);
            }
            if (!$user->can('ver-caja') || $user->hasRole('Vendedor')) {
                $query->where('user_id', $user->id);
            }
        }

        // Filtro por cajero / vendedor
        if ($request->filled('cashier_id') && $request->cashier_id !== 'all') {
            $query->where('user_id', $request->cashier_id);
        }

        // Filtro por estado (abierto/cerrado)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtro por descuadre / diferencia de arqueo
        if ($request->filled('discrepancy') && $request->discrepancy !== 'all') {
            if ($request->discrepancy === 'with_diff') {
                $query->where('status', 'cerrado')->whereRaw('ABS(difference_cash) >= 0.01');
            } elseif ($request->discrepancy === 'shortage') {
                $query->where('status', 'cerrado')->where('difference_cash', '<', -0.01);
            } elseif ($request->discrepancy === 'surplus') {
                $query->where('status', 'cerrado')->where('difference_cash', '>', 0.01);
            } elseif ($request->discrepancy === 'exact') {
                $query->where('status', 'cerrado')->whereRaw('ABS(difference_cash) < 0.01');
            }
        }

        // Búsqueda libre (folio, notas, cajero o sucursal)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                  ->orWhere('opening_notes', 'like', "%{$search}%")
                  ->orWhere('closing_notes', 'like', "%{$search}%")
                  ->orWhereHas('cashier', fn($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%"))
                  ->orWhereHas('store', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        // Periodo predefinido o rango personalizado
        $period = $request->input('period');
        if ($period === 'today') {
            $query->whereDate('opened_at', Carbon::today());
        } elseif ($period === 'yesterday') {
            $query->whereDate('opened_at', Carbon::yesterday());
        } elseif ($period === 'this_week') {
            $query->whereBetween('opened_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            $query->whereBetween('opened_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        } elseif ($period === 'last_month') {
            $start = Carbon::now()->subMonth()->startOfMonth();
            $end   = Carbon::now()->subMonth()->endOfMonth();
            $query->whereBetween('opened_at', [$start, $end]);
        } else {
            if ($request->filled('date_from')) {
                $query->whereDate('opened_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('opened_at', '<=', $request->date_to);
            }
        }

        return $query;
    }

    /**
     * Listado histórico de Turnos y Cortes de Caja con filtros y métricas ejecutivas.
     */
    public function index(Request $request)
    {
        $user        = auth()->user();
        $canAuditAll = $user->hasRole('Super Admin') || $user->can('auditar-cortes-caja') || $user->can('vista-global');
        $canExport   = $user->hasRole('Super Admin') || $user->can('exportar-cortes-caja') || $user->can('exportar-reportes');
        $canReport   = $user->hasRole('Super Admin') || $user->can('reportes-cortes-caja') || $user->can('ver-reportes');

        $query = $this->buildFilteredQuery($request)->orderByDesc('opened_at');
        $shifts = $query->paginate(15)->withQueryString();

        // Cálculo de KPIs sobre la consulta filtrada
        $kpiQuery = $this->buildFilteredQuery($request);

        $totalShifts  = (clone $kpiQuery)->count();
        $openShifts   = (clone $kpiQuery)->where('status', 'abierto')->count();
        $closedShifts = (clone $kpiQuery)->where('status', 'cerrado')->count();

        $closedQuery = (clone $kpiQuery)->where('status', 'cerrado');
        $totalSalesCash     = (float) (clone $closedQuery)->sum('total_sales_cash');
        $totalSalesCard     = (float) (clone $closedQuery)->sum('total_sales_card');
        $totalSalesTransfer = (float) (clone $closedQuery)->sum('total_sales_transfer');
        $totalSalesAmount   = (float) (clone $closedQuery)->sum('total_sales_amount');
        $totalOpeningAmount = (float) (clone $kpiQuery)->sum('opening_amount');
        $totalExpectedCash  = (float) (clone $closedQuery)->sum('expected_cash');
        $totalActualCash    = (float) (clone $closedQuery)->sum('actual_cash');
        $netDiff            = (float) (clone $closedQuery)->sum('difference_cash');
        $shiftsWithDiff     = (clone $closedQuery)->whereRaw('ABS(difference_cash) >= 0.01')->count();

        $kpis = [
            'total_shifts'         => $totalShifts,
            'open_shifts'          => $openShifts,
            'closed_shifts'        => $closedShifts,
            'total_sales'          => $totalSalesAmount,
            'total_sales_cash'     => $totalSalesCash,
            'total_sales_card'     => $totalSalesCard,
            'total_sales_transfer' => $totalSalesTransfer,
            'total_opening'        => $totalOpeningAmount,
            'total_expected_cash'  => $totalExpectedCash,
            'total_actual_cash'    => $totalActualCash,
            'net_diff'             => $netDiff,
            'shifts_with_diff'     => $shiftsWithDiff,
        ];

        $stores = Store::orderBy('name')->get();
        $cashiers = User::whereHas('cashShifts')->orderBy('name')->get();
        if ($cashiers->isEmpty()) {
            $cashiers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Vendedor', 'Gerente', 'Super Admin', 'Dirección']))->orderBy('name')->get();
        }

        return view('admin.cash-shifts.index', compact(
            'shifts', 'kpis', 'stores', 'cashiers', 'canAuditAll', 'canExport', 'canReport'
        ));
    }

    /**
     * Exporta el reporte filtrado de turnos de caja a formato CSV (Excel).
     */
    public function export(Request $request)
    {
        $user      = auth()->user();
        $canExport = $user->hasRole('Super Admin') || $user->can('exportar-cortes-caja') || $user->can('exportar-reportes');

        if (!$canExport) {
            abort(403, 'No tienes el permiso [exportar-cortes-caja] asignado a tu rol para descargar reportes.');
        }

        $shifts   = $this->buildFilteredQuery($request)->orderByDesc('opened_at')->get();
        $filename = 'reporte_cortes_caja_' . date('Y-m-d_His') . '.csv';

        $response = new StreamedResponse(function () use ($shifts) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para apertura correcta en Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Folio Turno',
                'Sucursal',
                'Cajero Responsable',
                'Cerrado Por',
                'Estado',
                'Fecha Apertura',
                'Hora Apertura',
                'Fecha Cierre',
                'Hora Cierre',
                'Fondo Inicial ($)',
                'Ventas Efectivo ($)',
                'Ventas Tarjeta ($)',
                'Ventas Transferencia ($)',
                'Otros Métodos ($)',
                'Anticipos Pedidos Especiales ($)',
                'Entradas Manuales ($)',
                'Retiros / Sangrías Totales ($)',
                'Traslado a Bóveda ($)',
                'Gastos Operativos de Tienda ($)',
                'Gran Total Cobrado ($)',
                'Tickets Emitidos',
                'Efectivo Esperado en Cajón ($)',
                'Efectivo Físico Contado ($)',
                'Diferencia / Descuadre ($)',
                'Resultado Arqueo',
                'Notas de Apertura',
                'Notas de Cierre',
            ]);

            foreach ($shifts as $s) {
                $isClosed = $s->isClosed();
                $diff = (float) ($s->difference_cash ?? 0);
                $diffStatus = 'En Operación';
                if ($isClosed) {
                    if (abs($diff) < 0.01) {
                        $diffStatus = 'Exacto';
                    } elseif ($diff > 0) {
                        $diffStatus = 'Sobrante';
                    } else {
                        $diffStatus = 'Faltante';
                    }
                }

                fputcsv($handle, [
                    $s->folio,
                    $s->store?->name ?? 'N/A',
                    $s->cashier?->name ?? 'N/A',
                    $s->closedBy?->name ?? '—',
                    ucfirst($s->status),
                    $s->opened_at->format('Y-m-d'),
                    $s->opened_at->format('H:i:s'),
                    $s->closed_at ? $s->closed_at->format('Y-m-d') : '—',
                    $s->closed_at ? $s->closed_at->format('H:i:s') : '—',
                    number_format($s->opening_amount, 2, '.', ''),
                    number_format($s->total_sales_cash, 2, '.', ''),
                    number_format($s->total_sales_card, 2, '.', ''),
                    number_format($s->total_sales_transfer, 2, '.', ''),
                    number_format($s->total_sales_other, 2, '.', ''),
                    number_format($s->total_special_orders_cash, 2, '.', ''),
                    number_format($s->total_cash_in, 2, '.', ''),
                    number_format($s->total_cash_out, 2, '.', ''),
                    number_format($s->total_vault_cash, 2, '.', ''),
                    number_format($s->total_expenses_cash, 2, '.', ''),
                    number_format($s->total_sales_amount, 2, '.', ''),
                    $s->sales_count,
                    number_format($s->expected_cash, 2, '.', ''),
                    $isClosed ? number_format($s->actual_cash, 2, '.', '') : '—',
                    $isClosed ? number_format($diff, 2, '.', '') : '—',
                    $diffStatus,
                    $s->opening_notes ?? '',
                    $s->closing_notes ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    /**
     * Vista de Reporte Ejecutivo Imprimible / PDF de Auditoría de Cajas.
     */
    public function report(Request $request)
    {
        $user      = auth()->user();
        $canReport = $user->hasRole('Super Admin') || $user->can('reportes-cortes-caja') || $user->can('ver-reportes');

        if (!$canReport) {
            abort(403, 'No tienes el permiso [reportes-cortes-caja] asignado a tu rol para consultar reportes ejecutivos de caja.');
        }

        $shifts = $this->buildFilteredQuery($request)->orderByDesc('opened_at')->get();
        $closedShifts = $shifts->where('status', 'cerrado');

        $summary = [
            'total_shifts'          => $shifts->count(),
            'open_shifts'           => $shifts->where('status', 'abierto')->count(),
            'closed_shifts'         => $closedShifts->count(),
            'total_opening'         => $shifts->sum('opening_amount'),
            'total_sales_cash'      => $closedShifts->sum('total_sales_cash'),
            'total_sales_card'      => $closedShifts->sum('total_sales_card'),
            'total_sales_transfer'  => $closedShifts->sum('total_sales_transfer'),
            'total_special_orders'  => $closedShifts->sum('total_special_orders_cash'),
            'total_cash_in'         => $closedShifts->sum('total_cash_in'),
            'total_cash_out'        => $closedShifts->sum('total_cash_out'),
            'total_vault_cash'      => $closedShifts->sum('total_vault_cash'),
            'total_expenses_cash'   => $closedShifts->sum('total_expenses_cash'),
            'total_sales_amount'    => $closedShifts->sum('total_sales_amount'),
            'total_sales_count'     => $closedShifts->sum('sales_count'),
            'total_expected_cash'   => $closedShifts->sum('expected_cash'),
            'total_actual_cash'     => $closedShifts->sum('actual_cash'),
            'net_diff'              => $closedShifts->sum('difference_cash'),
            'total_surplus'         => $closedShifts->where('difference_cash', '>', 0.01)->sum('difference_cash'),
            'total_shortage'        => $closedShifts->where('difference_cash', '<', -0.01)->sum('difference_cash'),
            'exact_shifts_count'    => $closedShifts->filter(fn($s) => abs((float)$s->difference_cash) < 0.01)->count(),
            'diff_shifts_count'     => $closedShifts->filter(fn($s) => abs((float)$s->difference_cash) >= 0.01)->count(),
        ];

        return view('admin.cash-shifts.report', compact('shifts', 'summary'));
    }
    /**
     * Retorna el estado del turno actual para la sucursal y usuario logueado.
     */
    public function current(Request $request)
    {
        $storeId = current_store_id();
        if (is_null($storeId) || $storeId === 'global') {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar una sucursal activa en el selector superior.',
                'has_active_shift' => false,
            ]);
        }

        $activeShift = CashShift::activeShift($storeId, auth()->id());

        if (!$activeShift) {
            return response()->json([
                'success'          => true,
                'has_active_shift' => false,
            ]);
        }

        $totals = $activeShift->calculateLiveTotals();

        return response()->json([
            'success'          => true,
            'has_active_shift' => true,
            'shift'            => [
                'id'             => $activeShift->id,
                'folio'          => $activeShift->folio,
                'opening_amount' => (float) $activeShift->opening_amount,
                'opened_at'      => $activeShift->opened_at->format('d/m/Y H:i'),
                'cashier_name'   => $activeShift->cashier->name ?? 'Cajero',
                'status'         => $activeShift->status,
            ],
            'totals'           => $totals,
        ]);
    }

    /**
     * Abre un nuevo turno de caja con fondo inicial.
     */
    public function open(Request $request)
    {
        $storeId = current_store_id();
        if (is_null($storeId) || $storeId === 'global') {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar una sucursal activa antes de abrir caja.'
            ], 422);
        }

        $validated = $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'opening_notes'  => 'nullable|string|max:500',
        ]);

        // Verificar que no haya un turno ya abierto
        $existing = CashShift::activeShift($storeId, auth()->id());
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Ya tienes un turno de caja abierto ({$existing->folio})."
            ], 422);
        }

        try {
            $shift = CashShift::create([
                'store_id'       => $storeId,
                'user_id'        => auth()->id(),
                'folio'          => CashShift::generateFolio(),
                'opening_amount' => $validated['opening_amount'],
                'opened_at'      => now(),
                'status'         => 'abierto',
                'opening_notes'  => $validated['opening_notes'] ?? null,
            ]);

            activity('pos')
                ->performedOn($shift)
                ->causedBy(auth()->user())
                ->log("Apertura de turno de caja {$shift->folio} con fondo inicial de $" . number_format($shift->opening_amount, 2));

            return response()->json([
                'success' => true,
                'message' => "Turno {$shift->folio} abierto exitosamente con $" . number_format($shift->opening_amount, 2),
                'shift'   => $shift,
            ]);

        } catch (\Exception $e) {
            Log::error("Error al abrir turno de caja: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno al abrir turno.'], 500);
        }
    }

    /**
     * Registra un movimiento de efectivo manual (Entrada o Retiro: Bóveda / Gasto Operativo).
     */
    public function movement(Request $request)
    {
        $storeId = current_store_id();
        $shift   = CashShift::activeShift($storeId, auth()->id());

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un turno de caja abierto para registrar movimientos.'
            ], 422);
        }

        $validated = $request->validate([
            'type'     => 'required|in:entrada,salida',
            'category' => 'nullable|string|in:fondo_extra,traslado_boveda,gasto_operativo',
            'amount'   => 'required|numeric|min:0.01',
            'concept'  => 'required|string|max:255',
            'notes'    => 'nullable|string|max:500',
        ]);

        $type = $validated['type'];
        $category = $validated['category'] ?? null;
        if ($type === 'entrada') {
            $category = 'fondo_extra';
        } else {
            if (!$category) {
                $category = 'traslado_boveda';
            }
        }

        try {
            return DB::transaction(function () use ($shift, $storeId, $validated, $type, $category) {
                $expense = null;
                if ($type === 'salida' && $category === 'gasto_operativo') {
                    $expense = Expense::create([
                        'store_id'       => $storeId,
                        'user_id'        => auth()->id(),
                        'concept'        => $validated['concept'],
                        'amount'         => $validated['amount'],
                        'date'           => now()->toDateString(),
                        'reference_type' => CashMovement::class,
                        'reference_id'   => null,
                        'notes'          => !empty($validated['notes'])
                            ? "Gasto registrado desde Turno {$shift->folio}: " . $validated['notes']
                            : "Gasto operativo registrado desde Turno de Caja {$shift->folio}",
                    ]);
                }

                $movement = $shift->movements()->create([
                    'store_id'   => $storeId,
                    'user_id'    => auth()->id(),
                    'type'       => $type,
                    'category'   => $category,
                    'amount'     => $validated['amount'],
                    'concept'    => $validated['concept'],
                    'notes'      => $validated['notes'] ?? null,
                    'expense_id' => $expense ? $expense->id : null,
                ]);

                if ($expense) {
                    $expense->update(['reference_id' => $movement->id]);
                }

                $actionLabel = match ($category) {
                    'gasto_operativo' => 'Gasto Operativo de Tienda',
                    'traslado_boveda' => 'Traslado a Bóveda / Banco',
                    default           => ($type === 'entrada' ? 'Entrada de Efectivo' : 'Retiro / Sangría'),
                };

                activity('pos')
                    ->performedOn($shift)
                    ->causedBy(auth()->user())
                    ->log("{$actionLabel} en caja de $" . number_format($movement->amount, 2) . ". Concepto: {$movement->concept}");

                $msgSuffix = $expense ? " Se registró automáticamente en el módulo de Gastos de la empresa." : "";

                return response()->json([
                    'success'  => true,
                    'message'  => "{$actionLabel} de $" . number_format($movement->amount, 2) . " registrada exitosamente.{$msgSuffix}",
                    'movement' => $movement,
                ]);
            });

        } catch (\Exception $e) {
            Log::error("Error al registrar movimiento de caja: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar movimiento: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Arqueo parcial en tiempo real (Corte X) sin cerrar el turno.
     */
    public function corteX(Request $request)
    {
        $storeId = current_store_id();
        $shift   = CashShift::activeShift($storeId, auth()->id());

        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'No hay turno activo.'], 422);
        }

        $totals = $shift->calculateLiveTotals();

        return response()->json([
            'success' => true,
            'type'    => 'corte_x',
            'shift'   => [
                'id'             => $shift->id,
                'folio'          => $shift->folio,
                'opening_amount' => (float)$shift->opening_amount,
                'opened_at'      => $shift->opened_at->format('d/m/Y H:i'),
                'cashier_name'   => $shift->cashier->name ?? 'Cajero',
            ],
            'totals'  => $totals,
        ]);
    }

    /**
     * Cierre definitivo y arqueo de caja (Corte Z).
     */
    public function corteZ(Request $request)
    {
        $storeId = current_store_id();
        $shift   = CashShift::activeShift($storeId, auth()->id());

        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'No hay turno activo para cerrar.'], 422);
        }

        $validated = $request->validate([
            'actual_cash'   => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($shift, $validated) {
                $totals = $shift->calculateLiveTotals();

                $expectedCash   = (float) $totals['expected_cash'];
                $actualCash     = (float) $validated['actual_cash'];
                $differenceCash = round($actualCash - $expectedCash, 2);

                $shift->update([
                    'closed_by'                 => auth()->id(),
                    'closed_at'                 => now(),
                    'status'                    => 'cerrado',
                    'expected_cash'             => $expectedCash,
                    'actual_cash'               => $actualCash,
                    'difference_cash'           => $differenceCash,
                    'total_sales_cash'          => $totals['total_sales_cash'],
                    'total_sales_card'          => $totals['total_sales_card'],
                    'total_sales_transfer'      => $totals['total_sales_transfer'],
                    'total_sales_other'         => $totals['total_sales_other'],
                    'total_special_orders_cash' => $totals['total_special_orders_cash'],
                    'total_cash_in'             => $totals['total_cash_in'],
                    'total_cash_out'            => $totals['total_cash_out'],
                    'total_expenses_cash'       => $totals['total_expenses_cash'],
                    'total_vault_cash'          => $totals['total_vault_cash'],
                    'total_sales_amount'        => $totals['total_sales_amount'],
                    'sales_count'               => $totals['sales_count'],
                    'closing_notes'             => $validated['closing_notes'] ?? null,
                ]);

                activity('pos')
                    ->performedOn($shift)
                    ->causedBy(auth()->user())
                    ->log("Cierre de caja definitivo (Corte Z) {$shift->folio}. Esperado: $" . number_format($expectedCash, 2) . ", Físico: $" . number_format($actualCash, 2) . ", Diferencia: $" . number_format($differenceCash, 2));

                return response()->json([
                    'success'         => true,
                    'message'         => "Turno de caja cerrado exitosamente ({$shift->folio}).",
                    'shift_id'        => $shift->id,
                    'difference_cash' => $differenceCash,
                ]);
            });

        } catch (\Exception $e) {
            Log::error("Error al cerrar turno de caja (Corte Z): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cerrar el turno.'], 500);
        }
    }

    /**
     * Renderiza el comprobante imprimible de Corte de Caja (Hoja A4 Ejecutiva o Ticket Térmico).
     */
    public function printCorte($id, Request $request)
    {
        $shift = CashShift::with([
            'store',
            'cashier',
            'closedBy',
            'movements' => fn($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc')->with('user'),
            'sales' => fn($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc')->with(['customer', 'payments.paymentMethod']),
            'specialOrderPayments' => fn($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc')->with(['specialOrder.customer', 'paymentMethod']),
        ])->findOrFail($id);

        $shift->setRelation('movements', $shift->movements->sortBy(function ($m) {
            $t = $m->created_at ? $m->created_at->timestamp : 0;
            return sprintf('%010d_%010d', $t, $m->id);
        })->values());

        $shift->setRelation('sales', $shift->sales->sortBy(function ($s) {
            $t = $s->created_at ? $s->created_at->timestamp : 0;
            return sprintf('%010d_%010d', $t, $s->id);
        })->values());

        $shift->setRelation('specialOrderPayments', $shift->specialOrderPayments->sortBy(function ($p) {
            $t = $p->created_at ? $p->created_at->timestamp : 0;
            return sprintf('%010d_%010d', $t, $p->id);
        })->values());

        $type   = $request->query('type', $shift->isClosed() ? 'z' : 'x');
        $format = $request->query('format', 'a4');
        $liveTotals = $shift->calculateLiveTotals();

        return view('admin.pos.print_corte', compact('shift', 'type', 'format', 'liveTotals'));
    }

    /**
     * Detalle y auditoría pormenorizada de un Turno de Caja.
     */
    public function show($id, Request $request)
    {
        $sort = strtolower($request->query('sort', 'asc')) === 'desc' ? 'desc' : 'asc';

        $shift = CashShift::with([
            'store',
            'cashier',
            'closedBy',
            'movements' => fn($q) => $q->reorder('created_at', $sort)->orderBy('id', $sort)->with('user'),
            'sales' => fn($q) => $q->reorder('created_at', $sort)->orderBy('id', $sort)->with(['customer', 'payments.paymentMethod']),
            'specialOrderPayments' => fn($q) => $q->reorder('created_at', $sort)->orderBy('id', $sort)->with(['specialOrder.customer', 'paymentMethod']),
        ])->findOrFail($id);

        $liveTotals = $shift->calculateLiveTotals();

        // Garantizar de manera estricta que las colecciones en memoria estén en el orden solicitado
        if ($sort === 'desc') {
            $shift->setRelation('movements', $shift->movements->sortByDesc(function ($m) {
                $t = $m->created_at ? $m->created_at->timestamp : 0;
                return sprintf('%010d_%010d', $t, $m->id);
            })->values());

            $shift->setRelation('sales', $shift->sales->sortByDesc(function ($s) {
                $t = $s->created_at ? $s->created_at->timestamp : 0;
                return sprintf('%010d_%010d', $t, $s->id);
            })->values());

            $shift->setRelation('specialOrderPayments', $shift->specialOrderPayments->sortByDesc(function ($p) {
                $t = $p->created_at ? $p->created_at->timestamp : 0;
                return sprintf('%010d_%010d', $t, $p->id);
            })->values());
        } else {
            $shift->setRelation('movements', $shift->movements->sortBy(function ($m) {
                $t = $m->created_at ? $m->created_at->timestamp : 0;
                return sprintf('%010d_%010d', $t, $m->id);
            })->values());

            $shift->setRelation('sales', $shift->sales->sortBy(function ($s) {
                $t = $s->created_at ? $s->created_at->timestamp : 0;
                return sprintf('%010d_%010d', $t, $s->id);
            })->values());

            $shift->setRelation('specialOrderPayments', $shift->specialOrderPayments->sortBy(function ($p) {
                $t = $p->created_at ? $p->created_at->timestamp : 0;
                return sprintf('%010d_%010d', $t, $p->id);
            })->values());
        }

        return view('admin.cash-shifts.show', compact('shift', 'liveTotals', 'sort'));
    }
}
