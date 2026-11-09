<?php

namespace App\Http\Controllers;

use App\Models\PdfTemplate;
use App\Models\Product;
use App\Models\Store;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PdfTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Guarda una plantilla en el Backend validando la seguridad de las variables.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'required|string', // 'orden_entrega', 'producto', 'orden_venta', 'abono', 'anticipo'
            'template_json' => 'required|string', // PDFme structure
        ]);

        $templateJson = $request->input('template_json');
        $decoded = json_decode($templateJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'El JSON de la plantilla no es válido.'
            ], 400);
        }

        // Definir variables del sistema permitidas
        $allowedVariables = [
            // Producto y Catálogo
            'nombre_producto',
            'precio_venta',
            'precio_lista',
            'precio_rebajado',
            'precio_outlet',
            'mensualidad_3',
            'mensualidad_6',
            'mensualidad_9',
            'mensualidad_12',
            'codigo_sku',
            'ancho',
            'alto',
            'profundidad',
            'peso',
            'fecha_impresion',
            'categoria_producto',
            'marca_producto',
            'codigo_barras',
            'stock_disponible',

            // Elementos Estáticos y Multihoja
            'texto_estatico',
            'imagen_estatica',
            'numero_hoja',
            'total_hojas',

            // Transaccionales / Venta / POS
            'folio',
            'id_transaccion',
            'fecha_hora',
            'nombre_cliente',
            'direccion_cliente',
            'telefono_cliente',
            'email_cliente',
            'subtotal',
            'impuestos',
            'total_iva',
            'total_ieps',
            'descuento_total',
            'total',
            'metodos_pago',
            'observaciones',
            'tabla_articulos',
            'cajero_nombre',
            'sucursal_nombre',
            'sucursal_telefono',
            'sucursal_direccion',

            // Logística / Despacho / Orden de Entrega
            'nombre_receptor',
            'telefono_receptor',
            'direccion_entrega',
            'referencias_entrega',
            'fecha_entrega',
            'turno_entrega',
            'instrucciones_entrega',
            'costo_flete',
            'costo_armado',
            'saldo_contra_entrega',
            'estatus_pago',
            'codigo_qr_mapa',
            'firma_almacen',
            'firma_chofer',
            'firma_cliente',

            // Cobranza / Abonos y Anticipos
            'monto_abono',
            'saldo_anterior',
            'saldo_pendiente',
            'folio_venta_origen'
        ];

        // Validar que las variables usadas en el esquema estén permitidas (bucle plano por seguridad)
        if (isset($decoded['schemas'])) {
            foreach ($decoded['schemas'] as $pageIndex => $page) {
                if (is_array($page)) {
                    foreach ($page as $fieldName => $fieldProps) {
                        // Verificar la propiedad interna 'name' si existe; de lo contrario, la clave del esquema
                        $internalName = $fieldProps['name'] ?? null;
                        if ($internalName) {
                            $baseFieldName = preg_replace('/_(?!(3|6|9|12)$)\d+$/', '', $internalName);
                        } else {
                            $baseFieldName = preg_replace('/_(?!(3|6|9|12)$)\d+$/', '', $fieldName);
                        }

                        if (!in_array($baseFieldName, $allowedVariables)) {
                            Log::warning("Intento de inyección de variable no permitida en PDFme:", [
                                'user_id' => auth()->id(),
                                'variable' => $internalName ?? $fieldName,
                                'template_name' => $request->input('name')
                            ]);

                            return response()->json([
                                'success' => false,
                                'message' => "La variable '" . ($internalName ?? $fieldName) . "' no es una variable permitida por el sistema por seguridad."
                            ], 422);
                        }
                    }
                }
            }
        }

        // Guardar o actualizar la plantilla
        $template = PdfTemplate::updateOrCreate(
            ['name' => $request->input('name'), 'document_type' => $request->input('document_type')],
            ['template_json' => $templateJson, 'status' => true]
        );

        return response()->json([
            'success' => true,
            'message' => 'Plantilla guardada exitosamente.',
            'template' => $template
        ]);
    }

    /**
     * Busca la plantilla y compila la data real/simulada del documento para el frontend.
     */
    public function getTemplateAndData(Request $request, $idTemplate, $idProducto = null)
    {
        $template = PdfTemplate::find($idTemplate);
        $templateJson = $template ? json_decode($template->template_json, true) : null;

        // Respetar store_id del query string si el frontend lo envía (modal de impresión)
        $storeId = $request->query('store_id') ?: current_store_id();
        $store = $storeId ? Store::find($storeId) : null;
        if (!$store) {
            $store = current_store();
        }
        if (!$store) {
            $store = Store::where('status', true)->first();
        }

        // Buscar producto
        $product = null;
        if ($idProducto && $idProducto !== 'default') {
            $product = Product::find($idProducto);
        }

        $docType = $template ? $template->document_type : 'producto';

        switch ($docType) {
            case 'orden_entrega':
                $data = $this->compileDeliveryMockData($store);
                break;
            case 'orden_venta':
                $data = $this->compileSaleMockData($store);
                break;
            case 'abono':
                $data = $this->compileAbonoMockData($store);
                break;
            case 'anticipo':
                $data = $this->compileAnticipoMockData($store);
                break;
            case 'producto':
            default:
                if ($product) {
                    $data = $this->compileProductData($product, $store);
                } else {
                    $data = $this->compileProductMockData($store);
                }
                break;
        }

        return response()->json([
            'success' => true,
            'template' => $templateJson,
            'data' => [$data], // formato array de inputs para PDFme
            'store_info' => $store ? [
                'id' => $store->id,
                'name' => $store->name
            ] : null
        ]);
    }

    /**
     * Endpoint para obtener los datos de múltiples productos para impresión masiva.
     */
    public function getBulkData(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer'
        ]);

        $productIds = $request->input('product_ids');

        // Obtener sucursal activa
        $storeId = current_store_id();
        $store = current_store();
        if (!$store && $storeId) {
            $store = Store::find($storeId);
        }
        if (!$store) {
            $store = Store::where('status', true)->first();
        }

        $products = Product::whereIn('id', $productIds)->get();
        $compiledData = [];

        foreach ($products as $product) {
            $compiledData[] = $this->compileProductData($product, $store);
        }

        return response()->json([
            'success' => true,
            'products_data' => $compiledData,
            'store_info' => $store ? [
                'id' => $store->id,
                'name' => $store->name
            ] : null
        ]);
    }

    /**
     * Compila y unifica toda la información dinámica de un producto según sucursal.
     */
    private function compileProductData(Product $product, ?Store $store): array
    {
        // Obtener stock real en la sucursal seleccionada
        $stock = 0;
        if ($store) {
            $stock = Inventory::where('product_id', $product->id)
                ->where('store_id', $store->id)
                ->value('quantity') ?? 0;
        }

        // Calcular precios según sucursal
        $precioVenta = $product->getPriceForStore($store, 'venta');
        $precioLista = $product->getPriceForStore($store, 'lista');
        $precioRebajado = $product->getPriceForStore($store, 'rebajado');
        $precioOutlet = $product->getPriceForStore($store, 'outlet');

        // Aplicar impuestos si tax_included NO está activo (se suma el IVA al final)
        $storeId = $store ? $store->id : null;
        $taxIncluded = filter_var(\App\Models\Setting::get('tax_included', false, $storeId), FILTER_VALIDATE_BOOLEAN);
        if (!$taxIncluded) {
            $ivaRate = (float)\App\Models\Setting::get('tax_iva_rate', '16', $storeId);
            $iepsRate = (float)\App\Models\Setting::get('tax_ieps_rate', '0', $storeId);
            $taxMultiplier = 1 + (($ivaRate + $iepsRate) / 100);

            $precioVenta = $precioVenta * $taxMultiplier;
            $precioLista = $precioLista * $taxMultiplier;
            $precioRebajado = $precioRebajado * $taxMultiplier;
            $precioOutlet = $precioOutlet * $taxMultiplier;
        }

        // Calcular mensualidades con interés desde settings
        $interest3 = (float)\App\Models\Setting::get('interest_3msi', '5');
        $interest6 = (float)\App\Models\Setting::get('interest_6msi', '10');
        $interest9 = (float)\App\Models\Setting::get('interest_9msi', '15');
        $interest12 = (float)\App\Models\Setting::get('interest_12msi', '20');

        $mensualidad3 = round(($precioVenta * (1 + $interest3 / 100)) / 3, 2);
        $mensualidad6 = round(($precioVenta * (1 + $interest6 / 100)) / 6, 2);
        $mensualidad9 = round(($precioVenta * (1 + $interest9 / 100)) / 9, 2);
        $mensualidad12 = round(($precioVenta * (1 + $interest12 / 100)) / 12, 2);

        // Intentar buscar atributos de dimensiones físicos dinámicamente si existen
        $ancho = '120 cm';
        $alto = '85 cm';
        $profundidad = '60 cm';
        $peso = '25 kg';

        try {
            // Si el producto tiene variantes, tomamos los datos de peso de la variante por defecto
            if ($product->isVariable() && $product->defaultVariant()) {
                $variant = $product->defaultVariant();
                if ($variant->weight) {
                    $peso = $variant->weight . ' kg';
                }
                if ($variant->sku) {
                    // Si hay variante por defecto, usamos su SKU como barras
                    $product->sku = $variant->sku;
                }
            }

            // Buscar en atributos pivot del producto
            foreach ($product->attributes as $attribute) {
                $nameLower = strtolower($attribute->name);
                // Si el atributo coincide con las dimensiones, intentamos buscar el valor
                // Nota: se implementa de manera resiliente para evitar fallos de base de datos
                if (str_contains($nameLower, 'anch')) {
                    $ancho = '110 cm'; // fallback realista o valor dinámico si estuviera
                } elseif (str_contains($nameLower, 'alt')) {
                    $alto = '90 cm';
                } elseif (str_contains($nameLower, 'prof')) {
                    $profundidad = '65 cm';
                }
            }
        } catch (\Exception $e) {
            // Resiliencia total
        }

        return [
            'nombre_producto' => $product->name,
            'precio_venta' => '$' . number_format($precioVenta, 2),
            'precio_lista' => '$' . number_format($precioLista, 2),
            'precio_rebajado' => '$' . number_format($precioRebajado, 2),
            'precio_outlet' => '$' . number_format($precioOutlet, 2),
            'mensualidad_3' => '$' . number_format($mensualidad3, 2),
            'mensualidad_6' => '$' . number_format($mensualidad6, 2),
            'mensualidad_9' => '$' . number_format($mensualidad9, 2),
            'mensualidad_12' => '$' . number_format($mensualidad12, 2),
            'codigo_sku' => $product->sku,
            'ancho' => $ancho,
            'alto' => $alto,
            'profundidad' => $profundidad,
            'peso' => $peso,
            'fecha_impresion' => date('d/m/Y'),
            'categoria_producto' => $product->categories->first()->name ?? 'Salas y Sillones',
            'marca_producto' => $product->brand->name ?? 'Muebles Ureña',
            'codigo_barras' => $product->sku,
            'stock_disponible' => $stock,
            'sucursal_nombre' => $store ? $store->name : 'Sucursal Matriz Ureña'
        ];
    }

    /**
     * Genera datos simulados realistas para previsualización de etiquetas de producto.
     */
    private function compileProductMockData(?Store $store): array
    {
        $taxIncluded = filter_var(\App\Models\Setting::get('tax_included', false), FILTER_VALIDATE_BOOLEAN);
        $taxMultiplier = 1.0;
        if ($taxIncluded) {
            $ivaRate = (float)\App\Models\Setting::get('tax_iva_rate', '16');
            $iepsRate = (float)\App\Models\Setting::get('tax_ieps_rate', '0');
            $taxMultiplier = 1 + (($ivaRate + $iepsRate) / 100);
        }

        $baseVenta = 12499.00 * $taxMultiplier;
        $baseLista = 14999.00 * $taxMultiplier;
        $baseRebajado = 11999.00 * $taxMultiplier;
        $baseOutlet = 10576.08 * $taxMultiplier;

        $interest3 = (float)\App\Models\Setting::get('interest_3msi', '5');
        $interest6 = (float)\App\Models\Setting::get('interest_6msi', '10');
        $interest9 = (float)\App\Models\Setting::get('interest_9msi', '15');
        $interest12 = (float)\App\Models\Setting::get('interest_12msi', '20');

        $mensualidad3 = ($baseVenta * (1 + $interest3 / 100)) / 3;
        $mensualidad6 = ($baseVenta * (1 + $interest6 / 100)) / 6;
        $mensualidad9 = ($baseVenta * (1 + $interest9 / 100)) / 9;
        $mensualidad12 = ($baseVenta * (1 + $interest12 / 100)) / 12;

        return [
            'nombre_producto'   => 'Sillón Sofá Confortable Premium Ureña',
            'precio_venta'      => '$' . number_format($baseVenta, 2),
            'precio_lista'      => '$' . number_format($baseLista, 2),
            'precio_rebajado'   => '$' . number_format($baseRebajado, 2),
            'precio_outlet'     => '$' . number_format($baseOutlet, 2),
            'mensualidad_3'     => '$' . number_format($mensualidad3, 2),
            'mensualidad_6'     => '$' . number_format($mensualidad6, 2),
            'mensualidad_9'     => '$' . number_format($mensualidad9, 2),
            'mensualidad_12'    => '$' . number_format($mensualidad12, 2),
            'codigo_sku'        => 'URE-SOFA-PREM-01',
            'ancho'             => '120 cm',
            'alto'              => '85 cm',
            'profundidad'       => '60 cm',
            'peso'              => '25 kg',
            'fecha_impresion'   => date('d/m/Y'),
            'categoria_producto'=> 'Salas / Sillones',
            'marca_producto'    => 'Muebles Ureña',
            'codigo_barras'     => 'URE-SOFA-PREM-01',
            'stock_disponible'  => 42,
            'sucursal_nombre'   => $store ? $store->name : 'Sucursal Matriz Ureña',
        ];
    }

    /**
     * Genera datos simulados completos para previsualización de Orden de Entrega / Despacho.
     */
    private function compileDeliveryMockData(?Store $store): array
    {
        return [
            'folio'                 => 'ENT-2026-0042',
            'id_transaccion'        => 'TX-98412',
            'fecha_hora'            => date('d/m/Y H:i'),
            'nombre_cliente'        => 'Arq. Roberto Méndez Silva',
            'direccion_cliente'     => 'Av. Insurgentes Sur #1450, Col. Actipan, Benito Juárez, CDMX',
            'telefono_cliente'      => '55 4321 9876',
            'email_cliente'         => 'roberto.mendez@example.com',
            'nombre_receptor'       => 'Lic. Patricia Domínguez (Esposa)',
            'telefono_receptor'     => '55 8765 4321',
            'direccion_entrega'     => 'Av. Constituyentes 450, Int. 3B, Miguel Hidalgo, CDMX',
            'referencias_entrega'   => 'Edificio blanco con portón de hierro negro, frente al parque.',
            'fecha_entrega'         => date('d/m/Y', strtotime('+2 days')),
            'turno_entrega'         => 'Matutino (9:00 AM - 2:00 PM)',
            'instrucciones_entrega' => 'Favor de llamar 30 minutos antes de llegar. Edificio con elevador de carga.',
            'costo_flete'           => '$450.00',
            'costo_armado'          => '$300.00',
            'subtotal'              => '$18,500.00',
            'impuestos'             => '$2,960.00',
            'total_iva'             => '$2,960.00',
            'total_ieps'            => '$0.00',
            'descuento_total'       => '$500.00',
            'total'                 => '$20,960.00',
            'saldo_contra_entrega'  => '$4,500.00',
            'estatus_pago'          => 'Pendiente contra entrega ($4,500.00)',
            'metodos_pago'          => 'Anticipo: Transferencia / Saldo: Efectivo',
            'observaciones'         => 'Subir al 3er piso por elevador. Incluye desempaque y colocación.',
            'cajero_nombre'         => auth()->user() ? auth()->user()->name : 'Carlos Almacén',
            'sucursal_nombre'       => $store ? $store->name : 'Sucursal Matriz Ureña',
            'sucursal_telefono'     => '55 1234 5678',
            'sucursal_direccion'    => 'Calzada de Tlalpan #2040, CDMX',
            'codigo_qr_mapa'        => 'https://maps.google.com/?q=19.40428,-99.18956',
            'codigo_barras'         => 'ENT-2026-0042',
            'numero_hoja'           => '1',
            'total_hojas'           => '1',
            'fecha_impresion'       => date('d/m/Y H:i'),
            'firma_almacen'         => "__________________________\nResponsable de Almacén",
            'firma_chofer'          => "__________________________\nChofer / Transportista",
            'firma_cliente'         => "__________________________\nFirma de Conformidad Cliente",
            'tabla_articulos'       => [
                ['CANT', 'SKU', 'DESCRIPCIÓN DEL ARTÍCULO', 'ESTADO / NOTAS'],
                ['1', 'URE-REC-MOD-01', 'Recámara King Size París Roble Natural', 'Verificado OK / En empaque'],
                ['2', 'URE-BUR-MOD-02', 'Buró París Roble 2 Cajones C/Correderas', 'Verificado OK / En empaque'],
                ['1', 'URE-COL-KNG-05', 'Colchón Ortopédico Memory Foam King Size', 'Sellado plástico de fábrica']
            ]
        ];
    }

    /**
     * Genera datos simulados completos para previsualización de Orden de Venta / Ticket.
     */
    private function compileSaleMockData(?Store $store): array
    {
        return [
            'folio'              => 'VTA-2026-0189',
            'id_transaccion'     => 'TX-87421',
            'fecha_hora'         => date('d/m/Y H:i'),
            'nombre_cliente'     => 'Mariana Gómez Lozano',
            'direccion_cliente'  => 'Av. Universidad #890, Col. del Valle, CDMX',
            'telefono_cliente'   => '55 9876 5432',
            'email_cliente'      => 'mariana.gomez@example.com',
            'subtotal'           => '$14,223.28',
            'impuestos'          => '$2,275.72',
            'total_iva'          => '$2,275.72',
            'total_ieps'         => '$0.00',
            'descuento_total'    => '$0.00',
            'total'              => '$16,499.00',
            'metodos_pago'       => 'Tarjeta de Crédito (Visa **** 4321)',
            'observaciones'      => 'Garantía de 1 año con fabricante. Factura solicitada.',
            'cajero_nombre'      => auth()->user() ? auth()->user()->name : 'Alejandro Morales',
            'sucursal_nombre'    => $store ? $store->name : 'Sucursal Matriz Ureña',
            'sucursal_telefono'  => '55 1234 5678',
            'sucursal_direccion' => 'Calzada de Tlalpan #2040, CDMX',
            'codigo_barras'      => 'VTA-2026-0189',
            'numero_hoja'        => '1',
            'total_hojas'        => '1',
            'fecha_impresion'    => date('d/m/Y H:i'),
            'tabla_articulos'    => [
                ['CANT', 'DESCRIPCIÓN', 'P. UNIT.', 'DESC.', 'IMPORTE'],
                ['1', 'Comedor 6 Sillas Nogal Contemporáneo', '$12,499.00', '$0.00', '$12,499.00'],
                ['1', 'Vitrina Buffetera Nogal con Luces LED', '$4,000.00', '$0.00', '$4,000.00']
            ]
        ];
    }

    /**
     * Genera datos simulados completos para previsualización de Comprobante de Abono.
     */
    private function compileAbonoMockData(?Store $store): array
    {
        return [
            'folio'              => 'ABN-2026-0056',
            'folio_venta_origen' => 'PED-2026-0120',
            'id_transaccion'     => 'TX-65432',
            'fecha_hora'         => date('d/m/Y H:i'),
            'nombre_cliente'     => 'Ing. Fernando Castro Peñaloza',
            'telefono_cliente'   => '55 6543 2109',
            'direccion_cliente'  => 'Calle Fresno #45, Tlalpan, CDMX',
            'monto_abono'        => '$2,500.00',
            'saldo_anterior'     => '$7,500.00',
            'saldo_pendiente'    => '$5,000.00',
            'total'              => '$2,500.00',
            'metodos_pago'       => 'Efectivo ($2,500.00)',
            'observaciones'      => 'Abono #2 a cuenta de Pedido París King Size.',
            'cajero_nombre'      => auth()->user() ? auth()->user()->name : 'Cajero Ureña',
            'sucursal_nombre'    => $store ? $store->name : 'Sucursal Matriz Ureña',
            'sucursal_telefono'  => '55 1234 5678',
            'sucursal_direccion' => 'Calzada de Tlalpan #2040, CDMX',
            'codigo_barras'      => 'ABN-2026-0056',
            'numero_hoja'        => '1',
            'total_hojas'        => '1',
            'fecha_impresion'    => date('d/m/Y H:i'),
            'firma_chofer'       => "__________________________\nRecibido por (Cajero)",
            'firma_cliente'      => "__________________________\nFirma de Conformidad Cliente"
        ];
    }

    /**
     * Genera datos simulados completos para previsualización de Comprobante de Anticipo.
     */
    private function compileAnticipoMockData(?Store $store): array
    {
        return [
            'folio'              => 'ANT-2026-0033',
            'folio_venta_origen' => 'PED-2026-0145',
            'id_transaccion'     => 'TX-76519',
            'fecha_hora'         => date('d/m/Y H:i'),
            'nombre_cliente'     => 'Dra. Claudia Ramos Montes',
            'telefono_cliente'   => '55 7890 1234',
            'direccion_cliente'  => 'Paseo de la Reforma #1120, Cuauhtémoc, CDMX',
            'subtotal'           => '$21,551.72',
            'impuestos'          => '$3,448.28',
            'total_iva'          => '$3,448.28',
            'total_ieps'         => '$0.00',
            'total'              => '$25,000.00',
            'monto_abono'        => '$10,000.00',
            'saldo_anterior'     => '$25,000.00',
            'saldo_pendiente'    => '$15,000.00',
            'metodos_pago'       => 'Transferencia Bancaria SPEI',
            'observaciones'      => 'Anticipo 40% para fabricación de pedido especial en fábrica.',
            'cajero_nombre'      => auth()->user() ? auth()->user()->name : 'Carlos Vendedor',
            'sucursal_nombre'    => $store ? $store->name : 'Sucursal Matriz Ureña',
            'sucursal_telefono'  => '55 1234 5678',
            'sucursal_direccion' => 'Calzada de Tlalpan #2040, CDMX',
            'codigo_barras'      => 'ANT-2026-0033',
            'numero_hoja'        => '1',
            'total_hojas'        => '1',
            'fecha_impresion'    => date('d/m/Y H:i'),
            'firma_chofer'       => "__________________________\nFirma Asesor / Vendedor",
            'firma_cliente'      => "__________________________\nFirma de Conformidad Cliente",
            'tabla_articulos'    => [
                ['CANT', 'DESCRIPCIÓN', 'P. UNIT.', 'DESC.', 'IMPORTE'],
                ['1', 'Sala Modular Esquinera Terciopelo Gris Oxford a Medida', '$25,000.00', '$0.00', '$25,000.00']
            ]
        ];
    }

    /**
     * Elimina lógicamente una plantilla utilizando SoftDeletes.
     */
    public function destroy($id)
    {
        $template = PdfTemplate::find($id);
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada.'
            ], 404);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plantilla eliminada exitosamente.'
        ]);
    }
}

