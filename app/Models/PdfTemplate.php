<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class PdfTemplate extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'document_type',
        'template_json',
        'status',
        'is_system',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Comprueba e inicializa las plantillas estándar en la base de datos si no existen.
     */
    public static function checkAndSeedDefaultTemplates()
    {
        $defaults = [
            [
                'name' => 'Etiqueta A4 Múltiple (4 por hoja) Estándar',
                'document_type' => 'producto',
                'template_json' => json_encode([
                    'width' => 85,
                    'height' => 120,
                    'basePdf' => '',
                    'schemas' => [[
                        'nombre_producto' => [
                            'name' => 'nombre_producto',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 8],
                            'width' => 75,
                            'height' => 18,
                            'fontSize' => 11,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'top',
                            'content' => 'nombre_producto'
                        ],
                        'codigo_sku' => [
                            'name' => 'codigo_sku',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 28],
                            'width' => 75,
                            'height' => 6,
                            'fontSize' => 9,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'codigo_sku'
                        ],
                        'marca_producto' => [
                            'name' => 'marca_producto',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 35],
                            'width' => 75,
                            'height' => 6,
                            'fontSize' => 8,
                            'fontColor' => '#6b7280',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'marca_producto'
                        ],
                        'categoria_producto' => [
                            'name' => 'categoria_producto',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 41],
                            'width' => 75,
                            'height' => 6,
                            'fontSize' => 8,
                            'fontColor' => '#6b7280',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'categoria_producto'
                        ],
                        'texto_estatico_1' => [
                            'name' => 'texto_estatico_1',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 47],
                            'width' => 75,
                            'height' => 6,
                            'fontSize' => 8,
                            'fontColor' => '#6b7280',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'Medidas: 120 x 85 x 60 cm'
                        ],
                        'precio_lista' => [
                            'name' => 'precio_lista',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 55],
                            'width' => 75,
                            'height' => 7,
                            'fontSize' => 10,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'precio_lista'
                        ],
                        'precio_venta' => [
                            'name' => 'precio_venta',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 63],
                            'width' => 75,
                            'height' => 10,
                            'fontSize' => 14,
                            'fontColor' => '#e11d48',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'precio_venta'
                        ],
                        'codigo_barras' => [
                            'name' => 'codigo_barras',
                            'type' => 'barcodes.code128',
                            'position' => ['x' => 5, 'y' => 76],
                            'width' => 75,
                            'height' => 18,
                            'fontSize' => 12,
                            'fontColor' => '#000000',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'codigo_barras'
                        ],
                        'codigo_sku_1' => [
                            'name' => 'codigo_sku_1',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 95],
                            'width' => 75,
                            'height' => 5,
                            'fontSize' => 8,
                            'fontColor' => '#1f2937',
                            'alignment' => 'center',
                            'valignment' => 'center',
                            'content' => 'codigo_sku'
                        ]
                    ]]
                ])
            ],
            [
                'name' => 'Etiqueta Térmica 4x6" Estándar',
                'document_type' => 'producto',
                'template_json' => json_encode([
                    'width' => 101.6,
                    'height' => 152.4,
                    'basePdf' => '',
                    'schemas' => [[
                        'nombre_producto' => [
                            'name' => 'nombre_producto',
                            'type' => 'text',
                            'position' => ['x' => 10, 'y' => 10],
                            'width' => 81.6,
                            'height' => 22,
                            'fontSize' => 14,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'top',
                            'content' => 'nombre_producto'
                        ],
                        'codigo_sku' => [
                            'name' => 'codigo_sku',
                            'type' => 'text',
                            'position' => ['x' => 10, 'y' => 34],
                            'width' => 81.6,
                            'height' => 7,
                            'fontSize' => 10,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'codigo_sku'
                        ],
                        'marca_producto' => [
                            'name' => 'marca_producto',
                            'type' => 'text',
                            'position' => ['x' => 10, 'y' => 42],
                            'width' => 40,
                            'height' => 6,
                            'fontSize' => 9,
                            'fontColor' => '#6b7280',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'marca_producto'
                        ],
                        'categoria_producto' => [
                            'name' => 'categoria_producto',
                            'type' => 'text',
                            'position' => ['x' => 52, 'y' => 42],
                            'width' => 40,
                            'height' => 6,
                            'fontSize' => 9,
                            'fontColor' => '#6b7280',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'categoria_producto'
                        ],
                        'texto_estatico_1' => [
                            'name' => 'texto_estatico_1',
                            'type' => 'text',
                            'position' => ['x' => 10, 'y' => 49],
                            'width' => 81.6,
                            'height' => 6,
                            'fontSize' => 9,
                            'fontColor' => '#6b7280',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'Medidas: 120 x 85 x 60 cm'
                        ],
                        'precio_lista' => [
                            'name' => 'precio_lista',
                            'type' => 'text',
                            'position' => ['x' => 10, 'y' => 57],
                            'width' => 81.6,
                            'height' => 8,
                            'fontSize' => 11,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'precio_lista'
                        ],
                        'precio_venta' => [
                            'name' => 'precio_venta',
                            'type' => 'text',
                            'position' => ['x' => 10, 'y' => 67],
                            'width' => 81.6,
                            'height' => 12,
                            'fontSize' => 16,
                            'fontColor' => '#e11d48',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'precio_venta'
                        ],
                        'codigo_barras' => [
                            'name' => 'codigo_barras',
                            'type' => 'barcodes.code128',
                            'position' => ['x' => 10, 'y' => 82],
                            'width' => 81.6,
                            'height' => 25,
                            'fontSize' => 12,
                            'fontColor' => '#000000',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'codigo_barras'
                        ],
                        'codigo_sku_1' => [
                            'name' => 'codigo_sku_1',
                            'type' => 'text',
                            'position' => ['x' => 10, 'y' => 109],
                            'width' => 81.6,
                            'height' => 6,
                            'fontSize' => 9,
                            'fontColor' => '#1f2937',
                            'alignment' => 'center',
                            'valignment' => 'center',
                            'content' => 'codigo_sku'
                        ]
                    ]]
                ])
            ],
            [
                'name' => 'Etiqueta Térmica Brother 62x100mm Estándar',
                'document_type' => 'producto',
                'template_json' => json_encode([
                    'width' => 62,
                    'height' => 100,
                    'basePdf' => '',
                    'schemas' => [[
                        'nombre_producto' => [
                            'name' => 'nombre_producto',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 5],
                            'width' => 52,
                            'height' => 14,
                            'fontSize' => 9,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'top',
                            'content' => 'nombre_producto'
                        ],
                        'codigo_sku' => [
                            'name' => 'codigo_sku',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 20],
                            'width' => 52,
                            'height' => 5,
                            'fontSize' => 8,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'codigo_sku'
                        ],
                        'marca_producto' => [
                            'name' => 'marca_producto',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 26],
                            'width' => 52,
                            'height' => 5,
                            'fontSize' => 7.5,
                            'fontColor' => '#6b7280',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'marca_producto'
                        ],
                        'texto_estatico_1' => [
                            'name' => 'texto_estatico_1',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 32],
                            'width' => 52,
                            'height' => 5,
                            'fontSize' => 7.5,
                            'fontColor' => '#6b7280',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'Med: 120 x 85 x 60 cm'
                        ],
                        'precio_lista' => [
                            'name' => 'precio_lista',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 39],
                            'width' => 52,
                            'height' => 6,
                            'fontSize' => 9,
                            'fontColor' => '#1f2937',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'precio_lista'
                        ],
                        'precio_venta' => [
                            'name' => 'precio_venta',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 46],
                            'width' => 52,
                            'height' => 8,
                            'fontSize' => 11,
                            'fontColor' => '#e11d48',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'precio_venta'
                        ],
                        'codigo_barras' => [
                            'name' => 'codigo_barras',
                            'type' => 'barcodes.code128',
                            'position' => ['x' => 5, 'y' => 56],
                            'width' => 52,
                            'height' => 16,
                            'fontSize' => 12,
                            'fontColor' => '#000000',
                            'alignment' => 'left',
                            'valignment' => 'center',
                            'content' => 'codigo_barras'
                        ],
                        'codigo_sku_1' => [
                            'name' => 'codigo_sku_1',
                            'type' => 'text',
                            'position' => ['x' => 5, 'y' => 74],
                            'width' => 52,
                            'height' => 5,
                            'fontSize' => 8,
                            'fontColor' => '#1f2937',
                            'alignment' => 'center',
                            'valignment' => 'center',
                            'content' => 'codigo_sku'
                        ]
                    ]]
                ])
            ],
            [
                'name' => 'Plantilla de Orden de Venta (Estándar)',
                'document_type' => 'orden_venta',
                'is_system' => true,
                'template_json' => json_encode([
                    'width' => 215.9, 'height' => 279.4, 'basePdf' => '',
                    'schemas' => [[
                        'sucursal_nombre' => [ 'name' => 'sucursal_nombre', 'type' => 'text', 'position' => ['x' => 15, 'y' => 12], 'width' => 100, 'height' => 8, 'fontSize' => 13, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'sucursal_nombre' ],
                        'texto_estatico_1' => [ 'name' => 'texto_estatico_1', 'type' => 'text', 'position' => ['x' => 15, 'y' => 20], 'width' => 100, 'height' => 5, 'fontSize' => 9, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'ORDEN DE VENTA / TICKET DE COMPRA' ],
                        'folio' => [ 'name' => 'folio', 'type' => 'text', 'position' => ['x' => 135, 'y' => 12], 'width' => 65, 'height' => 8, 'fontSize' => 13, 'fontColor' => '#1f2937', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'folio' ],
                        'fecha_hora' => [ 'name' => 'fecha_hora', 'type' => 'text', 'position' => ['x' => 135, 'y' => 20], 'width' => 65, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#6b7280', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'fecha_hora' ],
                        
                        'nombre_cliente' => [ 'name' => 'nombre_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 28], 'width' => 90, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'nombre_cliente' ],
                        'telefono_cliente' => [ 'name' => 'telefono_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 34], 'width' => 90, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'telefono_cliente' ],
                        'direccion_cliente' => [ 'name' => 'direccion_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 40], 'width' => 110, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'direccion_cliente' ],
                        'cajero_nombre' => [ 'name' => 'cajero_nombre', 'type' => 'text', 'position' => ['x' => 135, 'y' => 28], 'width' => 65, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'cajero_nombre' ],
                        'metodos_pago' => [ 'name' => 'metodos_pago', 'type' => 'text', 'position' => ['x' => 135, 'y' => 34], 'width' => 65, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'metodos_pago' ],

                        'tabla_articulos' => [ 'name' => 'tabla_articulos', 'type' => 'table', 'position' => ['x' => 15, 'y' => 50], 'width' => 185, 'height' => 130, 'columnsWidths' => '10,48,15,12,15', 'fontSize' => 9, 'fontColor' => '#000000', 'content' => 'tabla_articulos' ],

                        'subtotal' => [ 'name' => 'subtotal', 'type' => 'text', 'position' => ['x' => 135, 'y' => 188], 'width' => 65, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#4b5563', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'subtotal' ],
                        'impuestos' => [ 'name' => 'impuestos', 'type' => 'text', 'position' => ['x' => 135, 'y' => 195], 'width' => 65, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#4b5563', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'impuestos' ],
                        'descuento_total' => [ 'name' => 'descuento_total', 'type' => 'text', 'position' => ['x' => 135, 'y' => 202], 'width' => 65, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#4b5563', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'descuento_total' ],
                        'total' => [ 'name' => 'total', 'type' => 'text', 'position' => ['x' => 135, 'y' => 210], 'width' => 65, 'height' => 10, 'fontSize' => 14, 'fontColor' => '#e11d48', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'total' ],

                        'observaciones' => [ 'name' => 'observaciones', 'type' => 'text', 'position' => ['x' => 15, 'y' => 188], 'width' => 110, 'height' => 25, 'fontSize' => 8, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'top', 'content' => 'observaciones' ],
                        'codigo_barras' => [ 'name' => 'codigo_barras', 'type' => 'barcodes.code128', 'position' => ['x' => 15, 'y' => 230], 'width' => 70, 'height' => 18, 'content' => 'codigo_barras' ]
                    ]]
                ])
            ],
            [
                'name' => 'Plantilla de Orden de Entrega (Estándar)',
                'document_type' => 'orden_entrega',
                'is_system' => true,
                'template_json' => json_encode([
                    'width' => 215.9, 'height' => 279.4, 'basePdf' => '',
                    'schemas' => [[
                        'sucursal_nombre' => [ 'name' => 'sucursal_nombre', 'type' => 'text', 'position' => ['x' => 15, 'y' => 12], 'width' => 100, 'height' => 8, 'fontSize' => 13, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'sucursal_nombre' ],
                        'texto_estatico_1' => [ 'name' => 'texto_estatico_1', 'type' => 'text', 'position' => ['x' => 15, 'y' => 20], 'width' => 100, 'height' => 5, 'fontSize' => 9, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'ORDEN DE ENTREGA Y CONTROL DE DESPACHO' ],
                        'folio' => [ 'name' => 'folio', 'type' => 'text', 'position' => ['x' => 135, 'y' => 12], 'width' => 65, 'height' => 8, 'fontSize' => 13, 'fontColor' => '#1f2937', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'folio' ],
                        'fecha_hora' => [ 'name' => 'fecha_hora', 'type' => 'text', 'position' => ['x' => 135, 'y' => 20], 'width' => 65, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#6b7280', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'fecha_hora' ],

                        'nombre_cliente' => [ 'name' => 'nombre_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 28], 'width' => 90, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'nombre_cliente' ],
                        'telefono_cliente' => [ 'name' => 'telefono_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 34], 'width' => 90, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'telefono_cliente' ],
                        'nombre_receptor' => [ 'name' => 'nombre_receptor', 'type' => 'text', 'position' => ['x' => 110, 'y' => 28], 'width' => 90, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'nombre_receptor' ],
                        'telefono_receptor' => [ 'name' => 'telefono_receptor', 'type' => 'text', 'position' => ['x' => 110, 'y' => 34], 'width' => 90, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'telefono_receptor' ],

                        'direccion_entrega' => [ 'name' => 'direccion_entrega', 'type' => 'text', 'position' => ['x' => 15, 'y' => 41], 'width' => 150, 'height' => 9, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'top', 'content' => 'direccion_entrega' ],
                        'referencias_entrega' => [ 'name' => 'referencias_entrega', 'type' => 'text', 'position' => ['x' => 15, 'y' => 51], 'width' => 150, 'height' => 7, 'fontSize' => 8, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'top', 'content' => 'referencias_entrega' ],
                        'codigo_qr_mapa' => [ 'name' => 'codigo_qr_mapa', 'type' => 'qrcode', 'position' => ['x' => 172, 'y' => 36], 'width' => 28, 'height' => 28, 'content' => 'codigo_qr_mapa' ],

                        'fecha_entrega' => [ 'name' => 'fecha_entrega', 'type' => 'text', 'position' => ['x' => 15, 'y' => 60], 'width' => 70, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'fecha_entrega' ],
                        'turno_entrega' => [ 'name' => 'turno_entrega', 'type' => 'text', 'position' => ['x' => 90, 'y' => 60], 'width' => 75, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'turno_entrega' ],
                        'instrucciones_entrega' => [ 'name' => 'instrucciones_entrega', 'type' => 'text', 'position' => ['x' => 15, 'y' => 67], 'width' => 185, 'height' => 6, 'fontSize' => 8, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'instrucciones_entrega' ],

                        'tabla_articulos' => [ 'name' => 'tabla_articulos', 'type' => 'table', 'position' => ['x' => 15, 'y' => 76], 'width' => 185, 'height' => 105, 'columnsWidths' => '12,25,43,20', 'fontSize' => 8.5, 'fontColor' => '#000000', 'content' => 'tabla_articulos' ],

                        'costo_flete' => [ 'name' => 'costo_flete', 'type' => 'text', 'position' => ['x' => 15, 'y' => 188], 'width' => 60, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#4b5563', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'costo_flete' ],
                        'costo_armado' => [ 'name' => 'costo_armado', 'type' => 'text', 'position' => ['x' => 80, 'y' => 188], 'width' => 60, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#4b5563', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'costo_armado' ],
                        'saldo_contra_entrega' => [ 'name' => 'saldo_contra_entrega', 'type' => 'text', 'position' => ['x' => 145, 'y' => 187], 'width' => 55, 'height' => 8, 'fontSize' => 11, 'fontColor' => '#e11d48', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'saldo_contra_entrega' ],
                        'estatus_pago' => [ 'name' => 'estatus_pago', 'type' => 'text', 'position' => ['x' => 15, 'y' => 195], 'width' => 125, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'estatus_pago' ],
                        'observaciones' => [ 'name' => 'observaciones', 'type' => 'text', 'position' => ['x' => 15, 'y' => 202], 'width' => 185, 'height' => 8, 'fontSize' => 8, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'top', 'content' => 'observaciones' ],

                        'firma_almacen' => [ 'name' => 'firma_almacen', 'type' => 'text', 'position' => ['x' => 15, 'y' => 218], 'width' => 55, 'height' => 22, 'fontSize' => 8, 'fontColor' => '#1f2937', 'alignment' => 'center', 'valignment' => 'bottom', 'content' => 'firma_almacen' ],
                        'firma_chofer' => [ 'name' => 'firma_chofer', 'type' => 'text', 'position' => ['x' => 80, 'y' => 218], 'width' => 55, 'height' => 22, 'fontSize' => 8, 'fontColor' => '#1f2937', 'alignment' => 'center', 'valignment' => 'bottom', 'content' => 'firma_chofer' ],
                        'firma_cliente' => [ 'name' => 'firma_cliente', 'type' => 'text', 'position' => ['x' => 145, 'y' => 218], 'width' => 55, 'height' => 22, 'fontSize' => 8, 'fontColor' => '#1f2937', 'alignment' => 'center', 'valignment' => 'bottom', 'content' => 'firma_cliente' ]
                    ]]
                ])
            ],
            [
                'name' => 'Comprobante de Abono (Estándar)',
                'document_type' => 'abono',
                'is_system' => true,
                'template_json' => json_encode([
                    'width' => 215.9, 'height' => 139.7, 'basePdf' => '', // Media carta horizontal
                    'schemas' => [[
                        'sucursal_nombre' => [ 'name' => 'sucursal_nombre', 'type' => 'text', 'position' => ['x' => 15, 'y' => 10], 'width' => 100, 'height' => 7, 'fontSize' => 12, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'sucursal_nombre' ],
                        'texto_estatico_1' => [ 'name' => 'texto_estatico_1', 'type' => 'text', 'position' => ['x' => 15, 'y' => 18], 'width' => 100, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'COMPROBANTE DE ABONO A CUENTA' ],
                        'folio' => [ 'name' => 'folio', 'type' => 'text', 'position' => ['x' => 135, 'y' => 10], 'width' => 65, 'height' => 7, 'fontSize' => 12, 'fontColor' => '#1f2937', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'folio' ],
                        'fecha_hora' => [ 'name' => 'fecha_hora', 'type' => 'text', 'position' => ['x' => 135, 'y' => 18], 'width' => 65, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'fecha_hora' ],

                        'nombre_cliente' => [ 'name' => 'nombre_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 27], 'width' => 90, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'nombre_cliente' ],
                        'telefono_cliente' => [ 'name' => 'telefono_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 33], 'width' => 90, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'telefono_cliente' ],
                        'folio_venta_origen' => [ 'name' => 'folio_venta_origen', 'type' => 'text', 'position' => ['x' => 120, 'y' => 27], 'width' => 80, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'folio_venta_origen' ],
                        'cajero_nombre' => [ 'name' => 'cajero_nombre', 'type' => 'text', 'position' => ['x' => 120, 'y' => 33], 'width' => 80, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'cajero_nombre' ],

                        'saldo_anterior' => [ 'name' => 'saldo_anterior', 'type' => 'text', 'position' => ['x' => 20, 'y' => 45], 'width' => 55, 'height' => 8, 'fontSize' => 10, 'fontColor' => '#4b5563', 'alignment' => 'center', 'valignment' => 'center', 'content' => 'saldo_anterior' ],
                        'monto_abono' => [ 'name' => 'monto_abono', 'type' => 'text', 'position' => ['x' => 80, 'y' => 43], 'width' => 55, 'height' => 12, 'fontSize' => 15, 'fontColor' => '#16a34a', 'alignment' => 'center', 'valignment' => 'center', 'content' => 'monto_abono' ],
                        'saldo_pendiente' => [ 'name' => 'saldo_pendiente', 'type' => 'text', 'position' => ['x' => 140, 'y' => 45], 'width' => 55, 'height' => 8, 'fontSize' => 10, 'fontColor' => '#e11d48', 'alignment' => 'center', 'valignment' => 'center', 'content' => 'saldo_pendiente' ],

                        'metodos_pago' => [ 'name' => 'metodos_pago', 'type' => 'text', 'position' => ['x' => 15, 'y' => 60], 'width' => 90, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'metodos_pago' ],
                        'observaciones' => [ 'name' => 'observaciones', 'type' => 'text', 'position' => ['x' => 15, 'y' => 67], 'width' => 185, 'height' => 6, 'fontSize' => 8, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'observaciones' ],

                        'firma_chofer' => [ 'name' => 'firma_chofer', 'type' => 'text', 'position' => ['x' => 30, 'y' => 80], 'width' => 65, 'height' => 20, 'fontSize' => 8, 'fontColor' => '#1f2937', 'alignment' => 'center', 'valignment' => 'bottom', 'content' => 'firma_chofer' ],
                        'firma_cliente' => [ 'name' => 'firma_cliente', 'type' => 'text', 'position' => ['x' => 120, 'y' => 80], 'width' => 65, 'height' => 20, 'fontSize' => 8, 'fontColor' => '#1f2937', 'alignment' => 'center', 'valignment' => 'bottom', 'content' => 'firma_cliente' ]
                    ]]
                ])
            ],
            [
                'name' => 'Comprobante de Anticipo (Estándar)',
                'document_type' => 'anticipo',
                'is_system' => true,
                'template_json' => json_encode([
                    'width' => 215.9, 'height' => 139.7, 'basePdf' => '', // Media carta horizontal
                    'schemas' => [[
                        'sucursal_nombre' => [ 'name' => 'sucursal_nombre', 'type' => 'text', 'position' => ['x' => 15, 'y' => 10], 'width' => 100, 'height' => 7, 'fontSize' => 12, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'sucursal_nombre' ],
                        'texto_estatico_1' => [ 'name' => 'texto_estatico_1', 'type' => 'text', 'position' => ['x' => 15, 'y' => 18], 'width' => 100, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'RECIBO DE ANTICIPO DE PEDIDO ESPECIAL' ],
                        'folio' => [ 'name' => 'folio', 'type' => 'text', 'position' => ['x' => 135, 'y' => 10], 'width' => 65, 'height' => 7, 'fontSize' => 12, 'fontColor' => '#1f2937', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'folio' ],
                        'fecha_hora' => [ 'name' => 'fecha_hora', 'type' => 'text', 'position' => ['x' => 135, 'y' => 18], 'width' => 65, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'fecha_hora' ],

                        'nombre_cliente' => [ 'name' => 'nombre_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 27], 'width' => 90, 'height' => 6, 'fontSize' => 9, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'nombre_cliente' ],
                        'telefono_cliente' => [ 'name' => 'telefono_cliente', 'type' => 'text', 'position' => ['x' => 15, 'y' => 33], 'width' => 90, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'telefono_cliente' ],
                        'folio_venta_origen' => [ 'name' => 'folio_venta_origen', 'type' => 'text', 'position' => ['x' => 120, 'y' => 27], 'width' => 80, 'height' => 6, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'folio_venta_origen' ],
                        'cajero_nombre' => [ 'name' => 'cajero_nombre', 'type' => 'text', 'position' => ['x' => 120, 'y' => 33], 'width' => 80, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#6b7280', 'alignment' => 'right', 'valignment' => 'center', 'content' => 'cajero_nombre' ],

                        'total' => [ 'name' => 'total', 'type' => 'text', 'position' => ['x' => 20, 'y' => 45], 'width' => 55, 'height' => 8, 'fontSize' => 10, 'fontColor' => '#4b5563', 'alignment' => 'center', 'valignment' => 'center', 'content' => 'total' ],
                        'monto_abono' => [ 'name' => 'monto_abono', 'type' => 'text', 'position' => ['x' => 80, 'y' => 43], 'width' => 55, 'height' => 12, 'fontSize' => 15, 'fontColor' => '#2563eb', 'alignment' => 'center', 'valignment' => 'center', 'content' => 'monto_abono' ],
                        'saldo_pendiente' => [ 'name' => 'saldo_pendiente', 'type' => 'text', 'position' => ['x' => 140, 'y' => 45], 'width' => 55, 'height' => 8, 'fontSize' => 10, 'fontColor' => '#e11d48', 'alignment' => 'center', 'valignment' => 'center', 'content' => 'saldo_pendiente' ],

                        'metodos_pago' => [ 'name' => 'metodos_pago', 'type' => 'text', 'position' => ['x' => 15, 'y' => 60], 'width' => 90, 'height' => 5, 'fontSize' => 8.5, 'fontColor' => '#1f2937', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'metodos_pago' ],
                        'observaciones' => [ 'name' => 'observaciones', 'type' => 'text', 'position' => ['x' => 15, 'y' => 67], 'width' => 185, 'height' => 6, 'fontSize' => 8, 'fontColor' => '#6b7280', 'alignment' => 'left', 'valignment' => 'center', 'content' => 'observaciones' ],

                        'firma_chofer' => [ 'name' => 'firma_chofer', 'type' => 'text', 'position' => ['x' => 30, 'y' => 80], 'width' => 65, 'height' => 20, 'fontSize' => 8, 'fontColor' => '#1f2937', 'alignment' => 'center', 'valignment' => 'bottom', 'content' => 'firma_chofer' ],
                        'firma_cliente' => [ 'name' => 'firma_cliente', 'type' => 'text', 'position' => ['x' => 120, 'y' => 80], 'width' => 65, 'height' => 20, 'fontSize' => 8, 'fontColor' => '#1f2937', 'alignment' => 'center', 'valignment' => 'bottom', 'content' => 'firma_cliente' ]
                    ]]
                ])
            ]
        ];

        foreach ($defaults as $d) {
            $existing = self::where('name', $d['name'])
                ->where('document_type', $d['document_type'])
                ->withTrashed()
                ->first();

            if (!$existing) {
                self::create($d);
            } else {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                // Si es plantilla del sistema, actualizar schema con la versión más reciente
                if (!empty($existing->is_system) || !empty($d['is_system'])) {
                    $existing->update([
                        'template_json' => $d['template_json'],
                        'is_system' => true
                    ]);
                }
            }
        }
    }
}
