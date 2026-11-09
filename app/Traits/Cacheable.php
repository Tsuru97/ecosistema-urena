<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Closure;

trait Cacheable
{
    /**
     * Método principal para recordar una consulta usando etiquetas.
     *
     * @param string $key La clave única para esta consulta específica.
     * @param Closure $callback La función que ejecuta la consulta a la base de datos si la caché falla.
     * @return mixed El resultado de la consulta, ya sea desde la caché o la base de datos.
     */
    public static function rememberWithTags(string $key, Closure $callback)
    {
        $tag = self::getCacheTag();

        // Prefijo por tienda para evitar colisiones entre sucursales
        $storePrefix = function_exists('current_store_id') && current_store_id()
            ? 'store.' . current_store_id() . '.'
            : 'global.';
        $fullKey = $storePrefix . self::getCacheKeyPrefix() . '.' . $key;

        if (Cache::supportsTags()) {
            return Cache::tags($tag)->rememberForever($fullKey, $callback);
        }

        return Cache::rememberForever($fullKey, $callback);
    }

    
    /**
     * NUEVO MÉTODO: Recuerda una consulta para una instancia específica del modelo.
     * Incorpora el ID del modelo en la clave de la caché.
     *
     * @param string $key La clave única para esta consulta (ej. "products").
     * @param Closure $callback La función que ejecuta la consulta a la base de datos si la caché falla.
     * @return mixed El resultado de la consulta.
     */
    public function rememberInstanceWithTags(string $key, Closure $callback)
    {
        $tag = self::getCacheTag();

        $storePrefix = function_exists('current_store_id') && current_store_id()
            ? 'store.' . current_store_id() . '.'
            : 'global.';
        $fullKey = $storePrefix . self::getCacheKeyPrefix() . '.' . $this->getKey() . '.' . $key;

        if (Cache::supportsTags()) {
            return Cache::tags($tag)->rememberForever($fullKey, $callback);
        }

        return Cache::rememberForever($fullKey, $callback);
    }

    /**
     * Vacía toda la caché asociada a la etiqueta de este modelo.
     * Este método será llamado por el Observer.
     */
    public static function flushCache(): void
    {
        $tag = self::getCacheTag();
        // dd("flushCache: Flushing Tag -> {$tag}"); // DEBUG
        if (Cache::supportsTags()) {
            Cache::tags($tag)->flush();
        } else {
            Cache::flush();
        }
    }

    /**
     * Obtiene la etiqueta de caché para este modelo.
     * (ej. para el modelo Category, devuelve 'categories').
     *
     * @return string
     */
    protected static function getCacheTag(): string
    {
        $tag = strtolower(class_basename(self::class)) . 's';
        // dd("getCacheTag: Generated Tag -> {$tag}"); // DEBUG
        return $tag;
    }

    /**
     * Obtiene el prefijo para las claves de caché de este modelo.
     * (ej. para el modelo Category, devuelve 'category').
     *
     * @return string
     */
    protected static function getCacheKeyPrefix(): string
    {
        return strtolower(class_basename(self::class));
    }

    public function getCacheTagForDebug(): string
    {
        return self::getCacheTag();
    }
}
