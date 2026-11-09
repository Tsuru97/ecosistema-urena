<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Store;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-configuracion')->only(['index']);
        $this->middleware('permission:editar-configuracion')->only(['update']);
        $this->middleware('permission:limpiar-base-datos')->only(['cleanDatabase']);
    }

    /**
     * Ejecutar la limpieza de datos de prueba desde la interfaz web.
     */
    public function cleanDatabase(Request $request, \App\Services\DatabaseCleanerService $cleaner)
    {
        $request->validate([
            'confirmation' => 'required|string|in:ELIMINAR DATOS DE PRUEBA',
        ], [
            'confirmation.in' => 'Debes escribir exactamente la frase "ELIMINAR DATOS DE PRUEBA" para confirmar.',
        ]);

        $options = [
            'clean_sales'          => $request->boolean('clean_sales'),
            'clean_special_orders' => $request->boolean('clean_special_orders'),
            'clean_inventory'      => $request->boolean('clean_inventory'),
            'clean_purchases'      => $request->boolean('clean_purchases'),
            'clean_suppliers'      => $request->boolean('clean_suppliers'),
            'clean_customers'      => $request->boolean('clean_customers'),
            'clean_expenses'       => $request->boolean('clean_expenses'),
            'clean_coupons'        => $request->boolean('clean_coupons'),
            'clean_catalog'        => $request->boolean('clean_catalog'),
            'clean_audit'          => $request->boolean('clean_audit'),
            'clean_social_ai'      => $request->boolean('clean_social_ai'),
            'clean_storage'        => $request->boolean('clean_storage'),
        ];

        if (!array_filter($options)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes seleccionar al menos una casilla o categoría para limpiar.',
                ], 422);
            }
            return back()->with('error', 'Debes seleccionar al menos una casilla o categoría para limpiar.');
        }

        $summary = $cleaner->clean($options);

        activity('database_cleaner')
            ->causedBy(auth()->user())
            ->withProperties($summary)
            ->log('Se ejecutó la limpieza modular de base de datos desde el módulo de configuración.');

        $msg = "Limpieza completada exitosamente. Registros eliminados: " . number_format($summary['total_rows_deleted']) . ".";
        if (!empty($summary['orphan_users_deleted'])) {
            $msg .= " Usuarios huérfanos eliminados: " . number_format($summary['orphan_users_deleted']) . ".";
        }
        if (!empty($summary['storage_cleaned'])) {
            $msg .= " Archivos multimedia eliminados: " . number_format($summary['files_deleted_count']) . ".";
        }
        $msg .= " Todas las cachés del sistema fueron purgadas.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'summary' => $summary,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Mostrar la página de configuración con tabs por grupo.
     */
    public function index(\App\Services\DatabaseCleanerService $cleaner)
    {
        $storeId = current_store_id();
        $isGlobal = !$storeId || $storeId === 'global';

        // Definir los grupos disponibles con sus claves
        $groups = $this->getSettingsStructure();

        // Cargar valores actuales
        $settings = [];
        foreach ($groups as $groupName => $fields) {
            foreach ($fields as $field) {
                $settings[$field['key']] = Setting::get(
                    $field['key'],
                    $field['default'] ?? null,
                    $isGlobal ? null : $storeId
                );
            }
        }

        $stores = Store::where('status', true)->get();
        $currentStore = $isGlobal ? null : Store::find($storeId);
        $cleanerStats = $cleaner->getStats();

        return view('admin.settings.index', compact('groups', 'settings', 'stores', 'currentStore', 'isGlobal', 'cleanerStats'));
    }

    /**
     * Guardar la configuración (masivo por grupo).
     */
    public function update(Request $request)
    {
        $storeId = current_store_id();
        $isGlobal = !$storeId || $storeId === 'global';
        $targetStoreId = $isGlobal ? null : $storeId;

        $groups = $this->getSettingsStructure();
        $group = $request->input('group', 'general');

        if (!isset($groups[$group])) {
            return back()->with('error', 'Grupo de configuración no válido.');
        }

        $authorizerId = session('otp_authorized_by');
        $authorizer = $authorizerId ? \App\Models\User::find($authorizerId) : null;
        $authName = $authorizer ? $authorizer->name : 'N/A';

        foreach ($groups[$group] as $field) {
            $value = $request->input('settings.' . $field['key'], $field['default'] ?? '');

            // Para checkboxes
            if ($field['type'] === 'boolean') {
                $value = $request->has('settings.' . $field['key']) ? '1' : '0';
            }

            // Si es financiamiento, detectar cambios en el toggle msi_campaign_active para auditar
            if ($group === 'financiamiento' && $field['key'] === 'msi_campaign_active') {
                $oldMsi = Setting::get('msi_campaign_active', '0', $targetStoreId);
                if ($oldMsi !== $value) {
                    $statusStr = $value === '1' ? 'ACTIVADA' : 'DESACTIVADA';
                    activity('settings')
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'authorized_by_id' => $authorizerId,
                            'authorized_by_name' => $authName,
                            'store_id' => $targetStoreId,
                            'event' => 'msi_campaign_toggle',
                            'new_status' => $statusStr
                        ])
                        ->log("Campaña MSI {$statusStr} en configuración. Autorizado por: {$authName}");
                }
            }

            Setting::set($field['key'], $value, $targetStoreId, $group, $field['type']);
        }

        // Limpiar la variable de sesión para que no se arrastre
        session()->forget('otp_authorized_by');

        return back()->with('success', 'Configuración guardada exitosamente.');
    }

    /**
     * Estructura de todos los ajustes del sistema en grupos.
     */
    private function getSettingsStructure(): array
    {
        return [
            'fiscal' => [
                ['key' => 'fiscal_rfc',       'label' => 'RFC',                 'type' => 'string', 'default' => ''],
                ['key' => 'fiscal_razon',     'label' => 'Razón Social',        'type' => 'string', 'default' => ''],
                ['key' => 'fiscal_regimen',   'label' => 'Régimen Fiscal',      'type' => 'string', 'default' => ''],
                ['key' => 'fiscal_cp',        'label' => 'Código Postal Fiscal','type' => 'string', 'default' => ''],
                ['key' => 'fiscal_direccion', 'label' => 'Dirección Fiscal',    'type' => 'text',   'default' => ''],
            ],
            'impuestos' => [
                ['key' => 'tax_iva_rate',           'label' => 'Tasa IVA (%)',                            'type' => 'string',  'default' => '16'],
                ['key' => 'tax_ieps_rate',          'label' => 'Tasa IEPS (%)',                           'type' => 'string',  'default' => '0'],
                ['key' => 'tax_included',           'label' => 'IVA incluido en precios',                 'type' => 'boolean', 'default' => false],
                ['key' => 'tax_hide_zero_iva_notes', 'label' => 'Ocultar IVA en notas si la tasa es 0%', 'type' => 'boolean', 'default' => false],
            ],
            'tickets' => [
                ['key' => 'ticket_header',    'label' => 'Encabezado del ticket', 'type' => 'text', 'default' => ''],
                ['key' => 'ticket_footer',    'label' => 'Pie del ticket',        'type' => 'text', 'default' => ''],
                ['key' => 'ticket_show_logo', 'label' => 'Mostrar logo en ticket','type' => 'boolean', 'default' => true],
            ],
            'moneda' => [
                ['key' => 'currency_code',    'label' => 'Código de moneda',   'type' => 'string', 'default' => 'MXN'],
                ['key' => 'currency_symbol',  'label' => 'Símbolo',           'type' => 'string', 'default' => '$'],
                ['key' => 'decimal_places',   'label' => 'Decimales',         'type' => 'integer', 'default' => '2'],
            ],
            'margenes' => [
                ['key' => 'margin_lista',     'label' => 'Multiplicador Margen Lista (ej: 2.50)',    'type' => 'string', 'default' => '2.50'],
                ['key' => 'margin_venta',     'label' => 'Multiplicador Margen Venta (ej: 2.00)',    'type' => 'string', 'default' => '2.00'],
                ['key' => 'margin_rebajado',  'label' => 'Multiplicador Margen Rebajado (ej: 1.80)', 'type' => 'string', 'default' => '1.80'],
                ['key' => 'margin_outlet',    'label' => 'Multiplicador Margen Outlet (ej: 1.50)',   'type' => 'string', 'default' => '1.50'],
            ],
            'financiamiento' => [
                ['key' => 'interest_3msi',    'label' => 'Interés 3 MSI (%)',   'type' => 'string', 'default' => '5'],
                ['key' => 'interest_6msi',    'label' => 'Interés 6 MSI (%)',   'type' => 'string', 'default' => '10'],
                ['key' => 'interest_9msi',    'label' => 'Interés 9 MSI (%)',   'type' => 'string', 'default' => '15'],
                ['key' => 'interest_12msi',   'label' => 'Interés 12 MSI (%)',  'type' => 'string', 'default' => '20'],
                ['key' => 'msi_campaign_active', 'label' => 'Campaña MSI Activa (0% de recargo financiero en caja)', 'type' => 'boolean', 'default' => false],
            ],
            'comisiones' => [
                ['key' => 'comm_cash',                   'label' => 'Comisión Venta Efectivo / Transferencia (Sin descuento) (%)',   'type' => 'string', 'default' => '1.00'],
                ['key' => 'comm_discount_low',           'label' => 'Comisión Venta con Descuento de hasta 3% (%)',                   'type' => 'string', 'default' => '0.70'],
                ['key' => 'comm_discount_high',          'label' => 'Comisión Venta con Descuento mayor a 3% (%)',                   'type' => 'string', 'default' => '0.50'],
                ['key' => 'comm_msi',                    'label' => 'Comisión Venta a Meses Sin Intereses (MSI) (%)',                 'type' => 'string', 'default' => '0.50'],
                ['key' => 'comm_coupon_owner_split_pct',  'label' => 'Matriz de Reparto: Porcentaje a Vendedor Prospector / Dueño del Cupón (%)', 'type' => 'string', 'default' => '50.00'],
                ['key' => 'comm_closing_seller_split_pct','label' => 'Matriz de Reparto: Porcentaje a Vendedor en Mostrador / Cajero de Cierre (%)', 'type' => 'string', 'default' => '50.00'],
                ['key' => 'comm_layaway_payout_trigger', 'label' => 'Momento de Ganancia de Comisión en Apartados',                 'type' => 'select', 'default' => 'on_liquidation', 'options' => [
                    'on_liquidation' => '✅ Al liquidar el 100% del saldo (Recomendado)',
                    'on_creation'    => '⚡ Al registrar el anticipo inicial (Inmediato)',
                ]],
            ],
            'whatsapp' => [
                ['key' => 'whatsapp_api_token',            'label' => 'Meta API Token (Permanente/Temporal)', 'type' => 'string', 'default' => ''],
                ['key' => 'whatsapp_phone_number_id',      'label' => 'Meta Phone Number ID',                 'type' => 'string', 'default' => ''],
                ['key' => 'whatsapp_template_verification','label' => 'Plantilla: Verificar Cuenta',          'type' => 'string', 'default' => 'verificar_cuenta'],
                ['key' => 'whatsapp_template_otp',         'label' => 'Plantilla: Código OTP',                'type' => 'string', 'default' => 'codigo_verificacion'],
            ],
            'inteligencia_artificial' => [
                ['key' => 'gemini_api_key', 'label' => 'Google Gemini API Key', 'type' => 'string', 'default' => ''],
                ['key' => 'gemini_model',   'label' => 'Modelo de Gemini',       'type' => 'select', 'default' => 'gemini-2.5-flash', 'options' => [
                    'gemini-2.5-flash'      => '⚡ Gemini 2.5 Flash (Recomendado - Ultra-Rápido)',
                    'gemini-3.1-flash-lite' => '⚡ Gemini 3.1 Flash Lite (Ultra-Ligero y Eficiente)',
                    'gemini-2.5-pro'        => '🧠 Gemini 2.5 Pro (Razonamiento Complejo)',
                    'gemini-3.5-flash'      => '⚡ Gemini 3.5 Flash (Generación Futura - Rápido)',
                    'gemma-4-31b-it'        => '🚀 Gemma 4 31B IT (Google Open Weights - Alta Capacidad)',
                    'gemma-2-27b-it'        => '📱 Gemma 2 27B IT (Google Open Weights - Eficiente)',
                ]],
            ],
            'apartados' => [
                ['key' => 'layaway_min_downpayment_percentage', 'label' => 'Porcentaje Mínimo de Anticipo para Apartados (%)', 'type' => 'string', 'default' => '20'],
                ['key' => 'allow_out_of_stock_sales', 'label' => 'Permitir Venta Directa Sin Stock (Venta Sobre Pedido)', 'type' => 'boolean', 'default' => false],
                ['key' => 'customer_credit_check_enabled', 'label' => 'Validar Línea de Crédito del Cliente en Apartados', 'type' => 'boolean', 'default' => true],
                ['key' => 'customer_credit_reward_percentage', 'label' => 'Recompensa de Crédito al Liquidar Compra (%)', 'type' => 'string', 'default' => '0'],
            ],
            'cotizaciones' => [
                ['key' => 'quote_validity_days', 'label' => 'Días Naturales de Vigencia Estándar para Cotizaciones', 'type' => 'string', 'default' => '30'],
            ],
            'pdf_templates' => [
                ['key' => 'pdf_enable_custom_designer', 'label' => 'Permitir personalización de PDFs mediante PDFme', 'type' => 'boolean', 'default' => true],
            ],
            'mantenimiento' => [],
        ];
    }
}
