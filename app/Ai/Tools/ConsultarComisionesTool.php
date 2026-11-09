<?php

namespace App\Ai\Tools;

use App\Models\Setting;

class ConsultarComisionesTool implements AiToolInterface
{
    public function getName(): string
    {
        return "consultar_comisiones";
    }

    public function getDescription(): string
    {
        return "Consulta la configuración actual del ERP sobre márgenes de utilidad de precios, intereses por financiamiento (MSI) y comisiones de venta para vendedores.";
    }

    public function getKeywords(): array
    {
        return ["comisiones", "comision", "margen", "margenes", "msi", "financiamiento", "interes", "intereses", "efectivo", "tarjeta", "descuento", "descuentos"];
    }

    public function getParametersSchema(): array
    {
        return [
            "grupo" => [
                "type" => "string",
                "description" => "Filtrar por grupo específico. Opcional. Valores permitidos: 'margenes' (márgenes de precios), 'financiamiento' (plazos MSI), 'comisiones' (comisiones del vendedor)."
            ]
        ];
    }

    public function execute(array $arguments)
    {
        $grupo = $arguments['grupo'] ?? null;

        $results = [];

        // 1. Márgenes de Utilidad
        if (!$grupo || $grupo === 'margenes') {
            $results['margenes'] = [
                'titulo' => 'Márgenes de Utilidad por Tipo de Precio',
                'descripcion' => 'Multiplicadores que se aplican sobre el costo base para calcular los diferentes precios de venta.',
                'valores' => [
                    'precio_lista' => 'Costo * ' . Setting::get('margin_lista', '2.50'),
                    'precio_venta' => 'Costo * ' . Setting::get('margin_venta', '2.00'),
                    'precio_rebajado' => 'Costo * ' . Setting::get('margin_rebajado', '1.80'),
                    'precio_outlet' => 'Costo * ' . Setting::get('margin_outlet', '1.50')
                ]
            ];
        }

        // 2. Financiamiento MSI
        if (!$grupo || $grupo === 'financiamiento') {
            $results['financiamiento'] = [
                'titulo' => 'Tasas de Financiamiento a Meses Sin Intereses (MSI)',
                'descripcion' => 'Porcentajes de interés o recargo que se añaden al precio para diferir el cobro a plazos.',
                'valores' => [
                    '3_meses' => Setting::get('interest_3msi', '5') . '%',
                    '6_meses' => Setting::get('interest_6msi', '10') . '%',
                    '9_meses' => Setting::get('interest_9msi', '15') . '%',
                    '12_meses' => Setting::get('interest_12msi', '20') . '%'
                ]
            ];
        }

        // 3. Comisiones de Ventas
        if (!$grupo || $grupo === 'comisiones') {
            $results['comisiones'] = [
                'titulo' => 'Matriz de Comisiones de Venta para Vendedores',
                'descripcion' => 'Comisión porcentual que se otorga al vendedor según la forma de pago y el descuento aplicado al cliente.',
                'valores' => [
                    'efectivo_o_transferencia_sin_descuento' => Setting::get('comm_cash', '1.00') . '%',
                    'con_descuento_aplicado_hasta_3_porciento' => Setting::get('comm_discount_low', '0.70') . '%',
                    'con_descuento_aplicado_mayor_a_3_porciento' => Setting::get('comm_discount_high', '0.50') . '%',
                    'venta_a_meses_sin_intereses_msi' => Setting::get('comm_msi', '0.50') . '%'
                ]
            ];
        }

        return [
            'mensaje' => 'Configuración de comisiones y márgenes del ERP consultada con éxito.',
            'datos' => $results
        ];
    }
}
