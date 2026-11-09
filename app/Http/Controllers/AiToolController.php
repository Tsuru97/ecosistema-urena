<?php

namespace App\Http\Controllers;

use App\Ai\ToolRouter;
use Illuminate\Http\Request;

class AiToolController extends Controller
{
    /**
     * Enruta la consulta del usuario y le devuelve únicamente las firmas de herramientas óptimas.
     */
    public function routeTools(Request $request)
    {
        $request->validate([
            'query' => 'required|string'
        ]);

        $query = $request->input('query');

        // Llama al despachador estático para obtener las 3 herramientas más recomendadas
        $matchedTools = ToolRouter::route($query);

        $schemas = array_map(function ($tool) {
            return [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $tool->getParametersSchema()
            ];
        }, $matchedTools);

        // Agregamos la meta-herramienta global "buscar_otras_herramientas" si la IA la necesita recursivamente
        $schemas[] = [
            'name' => 'buscar_otras_herramientas',
            'description' => 'Si ninguna de tus herramientas inyectadas te sirve para resolver la duda del usuario, utiliza esta herramienta para buscar otras capacidades del ERP en caliente.',
            'parameters' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Término o palabra clave de la herramienta que estás buscando (ej. comisiones, auditoria, proveedores).'
                ]
            ]
        ];

        return response()->json([
            'tools' => $schemas
        ]);
    }

    /**
     * Ejecuta una herramienta del ERP de manera dinámica basándose en su nombre y argumentos.
     */
    public function executeTool(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'arguments' => 'nullable|array'
        ]);

        $name = $request->input('name');
        $arguments = $request->input('arguments', []);

        // Caso especial: Meta-herramienta global de búsqueda recursiva
        if ($name === 'buscar_otras_herramientas') {
            $query = $arguments['query'] ?? '';
            if (empty($query)) {
                return response()->json(['error' => 'Debes proporcionar un término de búsqueda de herramienta.']);
            }

            $matchedTools = ToolRouter::route($query);

            $schemas = array_map(function ($tool) {
                return [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getParametersSchema()
                ];
            }, $matchedTools);

            return response()->json([
                'es_busqueda_de_herramientas' => true,
                'herramientas_encontradas' => $schemas
            ]);
        }

        // Obtener el catálogo completo de herramientas disponibles
        $allTools = ToolRouter::getAllTools();

        if (!isset($allTools[$name])) {
            return response()->json([
                'error' => "La herramienta '{$name}' no está registrada en el backend."
            ], 404);
        }

        try {
            // Ejecutar la consulta SQL/Eloquent de la herramienta de forma dinámica
            $result = $allTools[$name]->execute($arguments);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error durante la ejecución del comando comercial: ' . $e->getMessage()
            ], 500);
        }
    }
}
