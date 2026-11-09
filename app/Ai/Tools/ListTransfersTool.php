<?php

namespace App\Ai\Tools;

use App\Models\Transfer;

class ListTransfersTool implements AiToolInterface
{
    public function getName(): string
    {
        return "list_transfers";
    }

    public function getDescription(): string
    {
        return "Consulta el listado y estado de traspasos (transferencias) recientes de inventario entre sucursales.";
    }

    public function getKeywords(): array
    {
        return ["traspasos", "transferencias", "envios", "historial de traspasos", "estado de traspaso", "traspaso"];
    }

    public function getParametersSchema(): array
    {
        return [
            "limit" => [
                "type" => "integer",
                "description" => "Número máximo de traspasos a retornar. Opcional, por defecto 5."
            ],
            "status" => [
                "type" => "string",
                "description" => "Filtrar por estado del traspaso. Opcional. Valores permitidos: 'pendiente', 'en_transito', 'recibido', 'rechazado', 'cancelado', 'devolucion'."
            ],
            "store_id" => [
                "type" => "integer",
                "description" => "ID de la sucursal origen o destino para filtrar los traspasos. Opcional."
            ]
        ];
    }

    public function execute(array $arguments)
    {
        $limit = $arguments['limit'] ?? 5;
        $status = $arguments['status'] ?? null;
        $storeId = $arguments['store_id'] ?? null;

        $query = Transfer::with([
            'originStore:id,name',
            'destinationStore:id,name',
            'creator:id,name',
            'sender:id,name',
            'receiver:id,name',
            'items.product:id,name,sku',
            'items.variant'
        ]);

        if ($status) {
            $query->where('status', $status);
        }

        if ($storeId) {
            $query->where(function ($q) use ($storeId) {
                $q->where('origin_store_id', $storeId)
                  ->orWhere('destination_store_id', $storeId);
            });
        }

        $transfers = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($transfers->isEmpty()) {
            return [
                'mensaje' => 'No se encontraron traspasos con los filtros seleccionados.',
                'traspasos' => []
            ];
        }

        $formatted = $transfers->map(function ($t) {
            $badge = $t->statusBadge();
            
            $itemsFormatted = $t->items->map(function ($item) {
                return [
                    'producto' => $item->displayName(),
                    'sku' => $item->variant ? $item->variant->sku : ($item->product ? $item->product->sku : '—'),
                    'cantidad_solicitada' => $item->quantity_requested,
                    'cantidad_enviada' => $item->quantity_sent,
                    'cantidad_recibida' => $item->quantity_received,
                    'notas' => $item->notes
                ];
            });

            return [
                'folio' => $t->folio,
                'sucursal_origen' => $t->originStore->name ?? '—',
                'sucursal_destino' => $t->destinationStore->name ?? '—',
                'estado' => $badge['label'] ?? $t->status,
                'estado_tecnico' => $t->status,
                'creado_por' => $t->creator->name ?? '—',
                'creado_el' => $t->created_at ? $t->created_at->format('d/m/Y H:i') : '—',
                'enviado_por' => $t->sender->name ?? '—',
                'enviado_el' => $t->sent_at ? $t->sent_at->format('d/m/Y H:i') : '—',
                'recibido_por' => $t->receiver->name ?? '—',
                'recibido_el' => $t->received_at ? $t->received_at->format('d/m/Y H:i') : '—',
                'notas_creador' => $t->notes,
                'notas_receptor' => $t->receiver_notes,
                'motivo_rechazo' => $t->rejection_reason,
                'articulos' => $itemsFormatted->toArray()
            ];
        });

        return [
            'total_encontrados' => count($formatted),
            'resultados' => $formatted->toArray()
        ];
    }
}
