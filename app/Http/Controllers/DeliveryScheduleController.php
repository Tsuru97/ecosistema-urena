<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SpecialOrder;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DeliveryScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-envios')->only(['index', 'events']);
        $this->middleware('permission:reprogramar-envios')->only(['reschedule']);
        $this->middleware('permission:cambiar-estado-envios')->only(['updateStatus']);
    }

    /**
     * Muestra la vista principal del calendario de reservas de envíos a domicilio.
     */
    public function index(Request $request)
    {
        Sale::ensureFoliosStandardized();

        $storeId = (!is_global_view() && current_store_id()) ? current_store_id() : $request->input('store_id');
        $stores = Store::where('status', 1)->orderBy('name')->get();

        $summary = $this->buildDashboardSummary($storeId ? (int) $storeId : null);
        $stats = $summary['stats'];
        $upcomingDeliveries = $summary['upcomingDeliveries'];

        return view('admin.deliveries.calendar', compact('stats', 'upcomingDeliveries', 'stores', 'storeId'));
    }

    /**
     * Construye las métricas operativas (KPIs) y la lista de próximas entregas para actualización en tiempo real.
     */
    private function buildDashboardSummary(?int $storeId = null): array
    {
        $salesQuery = Sale::where('is_delivery', true)->where('status', '!=', 'cancelada');
        $ordersQuery = SpecialOrder::where('is_delivery', true)->where('status', '!=', 'cancelled');

        if ($storeId) {
            $salesQuery->where('store_id', $storeId);
            $ordersQuery->where('store_id', $storeId);
        }

        $todayStr = Carbon::today()->toDateString();

        $todaySales = (clone $salesQuery)->whereDate('delivery_date', $todayStr)->count();
        $todayOrders = (clone $ordersQuery)->where(function ($q) use ($todayStr) {
            $q->whereDate('delivery_date', $todayStr)
              ->orWhere(function ($q2) use ($todayStr) {
                  $q2->whereNull('delivery_date')->whereDate('expected_delivery_date', $todayStr);
              });
        })->count();

        $pendingCount = (clone $salesQuery)->where(function ($q) {
                $q->where('delivery_status', 'pendiente')->orWhereNull('delivery_status');
            })->count()
            + (clone $ordersQuery)->where(function ($q) {
                $q->where('delivery_status', 'pendiente')->orWhereNull('delivery_status');
            })->count();

        $inRouteCount = (clone $salesQuery)->where('delivery_status', 'en_ruta')->count()
            + (clone $ordersQuery)->where('delivery_status', 'en_ruta')->count();

        $deliveredCount = (clone $salesQuery)->where('delivery_status', 'entregado')->count()
            + (clone $ordersQuery)->where('delivery_status', 'entregado')->count();

        $stats = [
            'today'     => $todaySales + $todayOrders,
            'pending'   => $pendingCount,
            'in_route'  => $inRouteCount,
            'delivered' => $deliveredCount,
        ];

        // Próximas entregas (desde hoy en adelante)
        $upcomingSales = (clone $salesQuery)
            ->with(['customer', 'store'])
            ->whereDate('delivery_date', '>=', $todayStr)
            ->orderBy('delivery_date')
            ->orderBy('delivery_time')
            ->limit(15)
            ->get()
            ->map(function (Sale $sale) {
                return [
                    'event_id'       => 'sale_' . $sale->id,
                    'entity_type'    => 'sale',
                    'entity_id'      => $sale->id,
                    'type_label'     => 'Venta',
                    'folio'          => $sale->ticket_folio,
                    'recipient_name' => $sale->delivery_recipient_name ?: ($sale->customer?->full_name ?? 'Público en General'),
                    'address'        => $sale->delivery_address ?: 'Sin dirección especificada',
                    'date_formatted' => $sale->delivery_date ? $sale->delivery_date->format('d/m/Y') : 'Por definir',
                    'time_formatted' => $sale->formatted_delivery_time ?: 'Por definir',
                    'status'         => $sale->delivery_status ?: 'pendiente',
                    'has_evidence'   => !empty($sale->delivery_evidence_path),
                    'sort_timestamp' => $sale->scheduled_delivery_datetime ? $sale->scheduled_delivery_datetime->timestamp : PHP_INT_MAX,
                ];
            });

        $upcomingOrders = (clone $ordersQuery)
            ->with(['customer', 'store'])
            ->where(function ($q) use ($todayStr) {
                $q->whereDate('delivery_date', '>=', $todayStr)
                  ->orWhere(function ($q2) use ($todayStr) {
                      $q2->whereNull('delivery_date')->whereDate('expected_delivery_date', '>=', $todayStr);
                  });
            })
            ->orderBy('delivery_date')
            ->orderBy('delivery_time')
            ->limit(15)
            ->get()
            ->map(function (SpecialOrder $order) {
                $effDate = $order->delivery_date ?: $order->expected_delivery_date;
                return [
                    'event_id'       => 'order_' . $order->id,
                    'entity_type'    => 'special_order',
                    'entity_id'      => $order->id,
                    'type_label'     => 'Pedido Esp.',
                    'folio'          => $order->folio,
                    'recipient_name' => $order->delivery_recipient_name ?: ($order->customer?->full_name ?? 'Público en General'),
                    'address'        => $order->delivery_address ?: 'Sin dirección especificada',
                    'date_formatted' => $effDate ? $effDate->format('d/m/Y') : 'Por definir',
                    'time_formatted' => $order->formatted_delivery_time ?: 'Por definir',
                    'status'         => $order->delivery_status ?: 'pendiente',
                    'has_evidence'   => !empty($order->delivery_evidence_path),
                    'sort_timestamp' => $order->scheduled_delivery_datetime ? $order->scheduled_delivery_datetime->timestamp : PHP_INT_MAX,
                ];
            });

        $upcomingDeliveries = $upcomingSales
            ->concat($upcomingOrders)
            ->sortBy('sort_timestamp')
            ->take(15)
            ->values();

        return [
            'stats'              => $stats,
            'upcomingDeliveries' => $upcomingDeliveries,
        ];
    }

    /**
     * Retorna los eventos en formato JSON compatible con FullCalendar.
     */
    public function events(Request $request)
    {
        $storeId = (!is_global_view() && current_store_id()) ? current_store_id() : $request->input('store_id');
        $statusFilter = $request->input('status');
        $typeFilter = $request->input('type');

        $startParam = $request->input('start') ? Carbon::parse($request->input('start'))->startOfDay() : Carbon::now()->subMonths(2)->startOfDay();
        $endParam = $request->input('end') ? Carbon::parse($request->input('end'))->endOfDay() : Carbon::now()->addMonths(3)->endOfDay();

        $events = [];

        // 1. Ventas Normales con Envío a Domicilio
        if (!$typeFilter || $typeFilter === 'all' || $typeFilter === 'sale') {
            $salesQuery = Sale::with(['customer', 'store', 'items.product', 'deliveredByUser'])
                ->where('is_delivery', true)
                ->where('status', '!=', 'cancelada');

            if ($storeId) {
                $salesQuery->where('store_id', $storeId);
            }
            if ($statusFilter && $statusFilter !== 'all') {
                if ($statusFilter === 'pendiente') {
                    $salesQuery->where(function ($q) {
                        $q->where('delivery_status', 'pendiente')->orWhereNull('delivery_status');
                    });
                } else {
                    $salesQuery->where('delivery_status', $statusFilter);
                }
            }

            $salesQuery->where(function ($q) use ($startParam, $endParam) {
                $q->whereBetween('delivery_date', [$startParam->toDateString(), $endParam->toDateString()])
                  ->orWhere(function ($q2) use ($startParam, $endParam) {
                      $q2->whereNull('delivery_date')->whereBetween('created_at', [$startParam, $endParam]);
                  });
            });

            foreach ($salesQuery->get() as $sale) {
                $events[] = $this->formatSaleEvent($sale);
            }
        }

        // 2. Pedidos Especiales con Envío a Domicilio
        if (!$typeFilter || $typeFilter === 'all' || $typeFilter === 'special_order') {
            $ordersQuery = SpecialOrder::with(['customer', 'store', 'items.product', 'deliveredByUser'])
                ->where('is_delivery', true)
                ->where('status', '!=', 'cancelled');

            if ($storeId) {
                $ordersQuery->where('store_id', $storeId);
            }
            if ($statusFilter && $statusFilter !== 'all') {
                if ($statusFilter === 'pendiente') {
                    $ordersQuery->where(function ($q) {
                        $q->where('delivery_status', 'pendiente')->orWhereNull('delivery_status');
                    });
                } else {
                    $ordersQuery->where('delivery_status', $statusFilter);
                }
            }

            $ordersQuery->where(function ($q) use ($startParam, $endParam) {
                $q->whereBetween('delivery_date', [$startParam->toDateString(), $endParam->toDateString()])
                  ->orWhere(function ($q2) use ($startParam, $endParam) {
                      $q2->whereNull('delivery_date')->whereBetween('expected_delivery_date', [$startParam->toDateString(), $endParam->toDateString()]);
                  })
                  ->orWhere(function ($q3) use ($startParam, $endParam) {
                      $q3->whereNull('delivery_date')->whereNull('expected_delivery_date')->whereBetween('created_at', [$startParam, $endParam]);
                  });
            });

            foreach ($ordersQuery->get() as $order) {
                $events[] = $this->formatSpecialOrderEvent($order);
            }
        }

        return response()->json($events);
    }

    /**
     * Reprograma la fecha y hora exacta de un envío (vía Drag & Drop o Modal).
     */
    public function reschedule(Request $request, string $type, int $id)
    {
        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'delivery_time' => 'required|string|max:10',
        ]);

        $model = $this->resolveDeliveryModel($type, $id);
        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Envío no encontrado.'], 404);
        }

        $timeRaw = substr(trim($validated['delivery_time']), 0, 5);
        $formattedSlot = $this->formatTime12h($timeRaw);

        $updatePayload = [
            'delivery_date'      => $validated['delivery_date'],
            'delivery_time'      => $timeRaw . ':00',
            'delivery_time_slot' => $formattedSlot,
        ];

        $model->update($updatePayload);
        $model->refresh();
        $model->load(['customer', 'store', 'items.product', 'deliveredByUser']);

        $folio = $type === 'sale' ? $model->ticket_folio : $model->folio;
        activity('deliveries')
            ->performedOn($model)
            ->causedBy(Auth::user())
            ->log("Reprogramó entrega a domicilio {$folio} para {$validated['delivery_date']} a las {$formattedSlot}");

        $storeId = (!is_global_view() && current_store_id()) ? current_store_id() : $request->input('store_id');

        return response()->json([
            'success' => true,
            'message' => "Entrega {$folio} reprogramada para el " . Carbon::parse($validated['delivery_date'])->format('d/m/Y') . " a las {$formattedSlot}.",
            'event'   => $type === 'sale' ? $this->formatSaleEvent($model) : $this->formatSpecialOrderEvent($model),
            'summary' => $this->buildDashboardSummary($storeId ? (int) $storeId : null),
        ]);
    }

    /**
     * Actualiza el estado del envío, fecha/hora y procesa la evidencia firmada (Foto o PDF) al marcar como Entregado.
     */
    public function updateStatus(Request $request, string $type, int $id)
    {
        $validated = $request->validate([
            'delivery_status'       => 'required|in:pendiente,en_ruta,entregado,cancelado',
            'delivery_date'         => 'nullable|date',
            'delivery_time'         => 'nullable|string|max:10',
            'delivery_instructions' => 'nullable|string|max:500',
            'evidence_file'         => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $model = $this->resolveDeliveryModel($type, $id);
        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Registro de envío no encontrado.'], 404);
        }

        $newStatus = $validated['delivery_status'];

        // Si el estado es ENTREGADO, exigir respaldo de orden de entrega firmada (Foto o PDF)
        if ($newStatus === 'entregado' && !$request->hasFile('evidence_file') && empty($model->delivery_evidence_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Para registrar el envío como ENTREGADO es obligatorio adjuntar una foto o PDF de la orden de entrega firmada.',
            ], 422);
        }

        $updateData = [
            'delivery_status' => $newStatus,
        ];

        if (!empty($validated['delivery_date'])) {
            $updateData['delivery_date'] = $validated['delivery_date'];
        }

        if (!empty($validated['delivery_time'])) {
            $timeRaw = substr(trim($validated['delivery_time']), 0, 5);
            $updateData['delivery_time'] = $timeRaw . ':00';
            $updateData['delivery_time_slot'] = $this->formatTime12h($timeRaw);
        }

        if ($request->has('delivery_instructions')) {
            $updateData['delivery_instructions'] = $validated['delivery_instructions'];
        }

        // Procesar archivo de evidencia (Foto o PDF de orden de entrega firmada)
        if ($request->hasFile('evidence_file')) {
            if (!empty($model->delivery_evidence_path) && Storage::disk('public')->exists($model->delivery_evidence_path)) {
                Storage::disk('public')->delete($model->delivery_evidence_path);
            }

            $file = $request->file('evidence_file');
            $folder = "deliveries/evidence/{$type}_{$model->id}";
            $filename = 'orden_firmada_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folder, $filename, 'public');

            $updateData['delivery_evidence_path'] = $path;
        }

        if ($newStatus === 'entregado') {
            $updateData['delivered_at'] = $model->delivered_at ?: now();
            $updateData['delivered_by'] = Auth::id();
        }

        $model->update($updateData);
        $model->refresh();
        $model->load(['customer', 'store', 'items.product', 'deliveredByUser']);

        $folio = $type === 'sale' ? $model->ticket_folio : $model->folio;
        activity('deliveries')
            ->performedOn($model)
            ->causedBy(Auth::user())
            ->log("Actualizó estado de entrega {$folio} a [" . strtoupper($newStatus) . "]");

        $storeId = (!is_global_view() && current_store_id()) ? current_store_id() : $request->input('store_id');

        return response()->json([
            'success' => true,
            'message' => $newStatus === 'entregado'
                ? "¡Envío {$folio} marcado como ENTREGADO con su orden firmada de respaldo!"
                : "Información y estado del envío {$folio} actualizados correctamente.",
            'event'   => $type === 'sale' ? $this->formatSaleEvent($model) : $this->formatSpecialOrderEvent($model),
            'summary' => $this->buildDashboardSummary($storeId ? (int) $storeId : null),
        ]);
    }

    /**
     * Formatea un registro de Venta para FullCalendar.
     */
    private function formatSaleEvent(Sale $sale): array
    {
        $dateObj = $sale->delivery_date ?: ($sale->created_at ?: now());
        $dateStr = $dateObj->format('Y-m-d');
        $timeRaw = $sale->delivery_time ? substr($sale->delivery_time, 0, 5) : '11:00';
        $startIso = "{$dateStr}T{$timeRaw}:00";
        $endIso = Carbon::parse("{$dateStr} {$timeRaw}:00")->addHour()->format('Y-m-d\TH:i:s');

        $status = $sale->delivery_status ?: 'pendiente';
        $recipient = $sale->delivery_recipient_name ?: ($sale->customer?->full_name ?? 'Cliente');
        $prefix = $status === 'entregado' ? '✓ ' : ($status === 'en_ruta' ? '🚚 ' : '');

        $itemsList = $sale->items
            ->filter(fn($item) => !in_array($item->product?->sku, ['SERV-FLETE', 'SERV-ARMADO']))
            ->map(fn($item) => ((int)$item->quantity) . 'x ' . ($item->product?->name ?? 'Artículo'))
            ->implode(', ');

        return [
            'id'        => 'sale_' . $sale->id,
            'title'     => "{$prefix}{$sale->ticket_folio} — {$recipient}",
            'start'     => $startIso,
            'end'       => $endIso,
            'allDay'    => false,
            'className' => $this->resolveEventClass($status, 'sale'),
            'editable'  => $status !== 'entregado',
            'extendedProps' => [
                'entity_type'             => 'sale',
                'entity_id'               => $sale->id,
                'type_label'              => 'Venta Normal',
                'folio'                   => $sale->ticket_folio,
                'store_name'              => $sale->store?->name ?? 'Sucursal',
                'customer_name'           => $sale->customer?->full_name ?? 'Público en General',
                'recipient_name'          => $recipient,
                'phone'                   => $sale->delivery_phone ?: ($sale->customer?->phone ?? ''),
                'address'                 => $sale->delivery_address ?: 'Sin dirección registrada',
                'references'              => $sale->delivery_references ?: '',
                'instructions'            => $sale->delivery_instructions ?: '',
                'delivery_date'           => $dateStr,
                'delivery_date_formatted' => $dateObj->format('d/m/Y'),
                'delivery_time'           => $timeRaw,
                'delivery_time_formatted' => $sale->formatted_delivery_time ?: $this->formatTime12h($timeRaw),
                'delivery_status'         => $status,
                'delivery_cost'           => (float) $sale->delivery_cost,
                'installation_cost'       => (float) $sale->installation_cost,
                'total'                   => (float) $sale->total,
                'maps_url'                => $sale->delivery_maps_url ?: (($sale->delivery_lat && $sale->delivery_lng) ? "https://www.google.com/maps?q={$sale->delivery_lat},{$sale->delivery_lng}" : null),
                'items_summary'           => $itemsList ?: 'Artículos de venta',
                'delivery_order_url'      => route('admin.pos.delivery-order', $sale->id),
                'print_ticket_url'        => route('pos.sales.print', $sale->id),
                'has_evidence'            => !empty($sale->delivery_evidence_path),
                'evidence_url'            => $sale->delivery_evidence_url,
                'is_evidence_pdf'         => $sale->isEvidencePdf(),
                'delivered_at'            => $sale->delivered_at ? $sale->delivered_at->format('d/m/Y h:i A') : null,
                'delivered_by_name'       => $sale->deliveredByUser ? trim($sale->deliveredByUser->name . ' ' . $sale->deliveredByUser->lastname) : null,
            ],
        ];
    }

    /**
     * Formatea un registro de Pedido Especial para FullCalendar.
     */
    private function formatSpecialOrderEvent(SpecialOrder $order): array
    {
        $dateObj = $order->delivery_date ?: ($order->expected_delivery_date ?: ($order->created_at ?: now()));
        $dateStr = $dateObj->format('Y-m-d');
        $timeRaw = $order->delivery_time ? substr($order->delivery_time, 0, 5) : '11:00';
        $startIso = "{$dateStr}T{$timeRaw}:00";
        $endIso = Carbon::parse("{$dateStr} {$timeRaw}:00")->addHour()->format('Y-m-d\TH:i:s');

        $status = $order->delivery_status ?: 'pendiente';
        $recipient = $order->delivery_recipient_name ?: ($order->customer?->full_name ?? 'Cliente');
        $prefix = $status === 'entregado' ? '✓ ' : ($status === 'en_ruta' ? '🚚 ' : '');

        $itemsList = $order->items
            ->map(fn($item) => ((int)$item->quantity) . 'x ' . ($item->product?->name ?? 'Pedido Especial'))
            ->implode(', ');

        return [
            'id'        => 'order_' . $order->id,
            'title'     => "{$prefix}{$order->folio} — {$recipient}",
            'start'     => $startIso,
            'end'       => $endIso,
            'allDay'    => false,
            'className' => $this->resolveEventClass($status, 'special_order'),
            'editable'  => $status !== 'entregado',
            'extendedProps' => [
                'entity_type'             => 'special_order',
                'entity_id'               => $order->id,
                'type_label'              => 'Pedido Especial',
                'folio'                   => $order->folio,
                'store_name'              => $order->store?->name ?? 'Sucursal',
                'customer_name'           => $order->customer?->full_name ?? 'Público en General',
                'recipient_name'          => $recipient,
                'phone'                   => $order->delivery_phone ?: ($order->customer?->phone ?? ''),
                'address'                 => $order->delivery_address ?: 'Sin dirección registrada',
                'references'              => $order->delivery_references ?: '',
                'instructions'            => $order->delivery_instructions ?: '',
                'delivery_date'           => $dateStr,
                'delivery_date_formatted' => $dateObj->format('d/m/Y'),
                'delivery_time'           => $timeRaw,
                'delivery_time_formatted' => $order->formatted_delivery_time ?: $this->formatTime12h($timeRaw),
                'delivery_status'         => $status,
                'delivery_cost'           => (float) $order->delivery_cost,
                'installation_cost'       => (float) $order->installation_cost,
                'total'                   => (float) $order->total,
                'maps_url'                => $order->delivery_maps_url ?: (($order->delivery_lat && $order->delivery_lng) ? "https://www.google.com/maps?q={$order->delivery_lat},{$order->delivery_lng}" : null),
                'items_summary'           => $itemsList ?: 'Artículos de pedido especial',
                'delivery_order_url'      => route('special-orders.delivery-order', $order->id),
                'print_ticket_url'        => route('special-orders.show', $order->id),
                'has_evidence'            => !empty($order->delivery_evidence_path),
                'evidence_url'            => $order->delivery_evidence_url,
                'is_evidence_pdf'         => $order->isEvidencePdf(),
                'delivered_at'            => $order->delivered_at ? $order->delivered_at->format('d/m/Y h:i A') : null,
                'delivered_by_name'       => $order->deliveredByUser ? trim($order->deliveredByUser->name . ' ' . $order->deliveredByUser->lastname) : null,
            ],
        ];
    }

    /**
     * Determina las clases CSS del evento en FullCalendar según estado y tipo.
     */
    private function resolveEventClass(string $status, string $type): string
    {
        return match ($status) {
            'entregado' => 'bg-success-subtle text-success border border-success fw-semibold',
            'en_ruta'   => 'bg-info-subtle text-info border border-info fw-semibold',
            'cancelado' => 'bg-danger-subtle text-danger border border-danger text-decoration-line-through',
            default     => $type === 'special_order'
                ? 'bg-warning-subtle text-warning border border-warning'
                : 'bg-primary-subtle text-primary border border-primary',
        };
    }

    /**
     * Resuelve la instancia de Sale o SpecialOrder.
     */
    private function resolveDeliveryModel(string $type, int $id)
    {
        if ($type === 'sale') {
            return Sale::with(['customer', 'store', 'items.product', 'deliveredByUser'])->find($id);
        }
        if ($type === 'special_order' || $type === 'order') {
            return SpecialOrder::with(['customer', 'store', 'items.product', 'deliveredByUser'])->find($id);
        }
        return null;
    }

    /**
     * Convierte hora 24h (HH:MM) a formato 12h (hh:mm AM/PM).
     */
    private function formatTime12h(string $time24): string
    {
        try {
            return Carbon::createFromFormat('H:i', substr($time24, 0, 5))->format('h:i A');
        } catch (\Exception $e) {
            return $time24;
        }
    }
}
