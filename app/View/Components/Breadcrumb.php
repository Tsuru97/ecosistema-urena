<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Breadcrumb extends Component
{
    public string $title;
    public array $crumbs;

    /**
     * Componente dinámico de breadcrumb.
     *
     * Auto-genera las migas desde la URL:
     *   /stores/5/edit → Dashboard > Sucursales > Editar
     *
     * @param string|null $title  Título personalizado (override del último segmento)
     * @param array       $items  Migas manuales (si se pasan, desactiva auto-generación)
     */
    public function __construct(?string $title = null, array $items = [])
    {
        if (!empty($items)) {
            // Modo manual (retrocompatible)
            $this->crumbs = $items;
            $this->title = $title ?? '';
        } else {
            // Modo auto-generado desde la URL
            [$this->crumbs, $autoTitle] = $this->buildFromUrl();
            $this->title = $title ?? $autoTitle;
        }
    }

    /**
     * Parsear la URL actual y construir las migas dinámicamente.
     *
     * @return array [crumbs[], title]
     */
    private function buildFromUrl(): array
    {
        $segments = request()->segments();
        $crumbs = [];
        $urlAccumulator = '';
        $lastLabel = '';

        foreach ($segments as $i => $segment) {
            $urlAccumulator .= '/' . $segment;

            // Omitir segmentos numéricos (IDs) en el texto visible
            if (is_numeric($segment)) {
                continue;
            }

            // Traducir el segmento: busca en translation.php
            $label = $this->translateSegment($segment);
            $isLast = ($i === count($segments) - 1);

            if ($isLast) {
                // El último segmento se usa como título, no como crumb
                $lastLabel = $label;
            } else {
                $crumbs[] = [
                    'label' => $label,
                    'url'   => $urlAccumulator,
                ];
            }
        }

        return [$crumbs, $lastLabel];
    }

    /**
     * Traducir un segmento de URL.
     * Busca en translation.php primero, luego humaniza el slug.
     */
    private function translateSegment(string $segment): string
    {
        // Intentar traducción directa: "stores" → "Sucursales"
        $key = 'translation.' . $segment;
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        // Intentar con guiones bajos: "file-manager" → "file_manager"
        $key = 'translation.' . str_replace('-', '_', $segment);
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        // Fallback: humanizar el slug
        return ucfirst(str_replace(['-', '_'], ' ', $segment));
    }

    public function render()
    {
        return view('components.breadcrumb');
    }
}
