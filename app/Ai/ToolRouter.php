<?php

namespace App\Ai;

use App\Ai\Tools\AiToolInterface;
use Illuminate\Support\Facades\File;

class ToolRouter
{
    /**
     * Escanea dinámicamente el directorio de herramientas del ERP.
     */
    public static function getAllTools(): array
    {
        $tools = [];
        $directory = app_path('Ai/Tools');

        if (!File::isDirectory($directory)) {
            return $tools;
        }

        $files = File::files($directory);

        foreach ($files as $file) {
            $className = 'App\\Ai\\Tools\\' . $file->getBasename('.php');
            
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                if ($reflection->implementsInterface(AiToolInterface::class) && !$reflection->isAbstract()) {
                    $instance = new $className();
                    $tools[$instance->getName()] = $instance;
                }
            }
        }

        return $tools;
    }

    /**
     * Busca las herramientas más relevantes para la pregunta del usuario basándose en coincidencia de texto.
     */
    public static function route(string $userQuery): array
    {
        $allTools = self::getAllTools();
        $queryLower = mb_strtolower($userQuery);
        $scoredTools = [];

        foreach ($allTools as $name => $tool) {
            $score = 0;

            // 1. Coincidencia directa en la descripción
            if (str_contains(mb_strtolower($tool->getDescription()), $queryLower)) {
                $score += 5;
            }

            // 2. Coincidencia en las palabras clave del Toolkit
            foreach ($tool->getKeywords() as $keyword) {
                if (str_contains($queryLower, mb_strtolower($keyword))) {
                    $score += 3;
                }
            }

            // 3. Coincidencia en el nombre técnico
            if (str_contains(mb_strtolower($tool->getName()), $queryLower)) {
                $score += 2;
            }

            if ($score > 0) {
                $scoredTools[] = [
                    'tool' => $tool,
                    'score' => $score
                ];
            }
        }

        // Ordenamos las herramientas por su relevancia de mayor a menor
        usort($scoredTools, fn($a, $b) => $b['score'] <=> $a['score']);

        // Extraemos solo los objetos de herramientas filtradas
        $filtered = array_map(fn($item) => $item['tool'], $scoredTools);

        // Si no se encontró ninguna coincidencia relevante, devolvemos las 3 herramientas básicas principales
        if (empty($filtered)) {
            return array_slice(array_values($allTools), 0, 3);
        }

        // Retornamos únicamente las top 3 herramientas más relevantes
        return array_slice($filtered, 0, 3);
    }
}
