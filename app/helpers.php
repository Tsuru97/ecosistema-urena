<?php

if (!function_exists('current_store_id')) {
    /**
     * Retorna el ID de la tienda activa, o null si está en vista Global.
     */
    function current_store_id(): ?int
    {
        if (app()->bound('current_store_id')) {
            return app('current_store_id'); // puede ser null (global)
        }
        $session = session('current_store_id');
        return ($session === 'global') ? null : $session;
    }
}

if (!function_exists('current_store')) {
    /**
     * Retorna la instancia del modelo Store activo, o null si es vista Global.
     */
    function current_store(): ?\App\Models\Store
    {
        return app()->bound('current_store')
            ? app('current_store')
            : null;
    }
}

if (!function_exists('is_global_view')) {
    /**
     * Retorna true si el usuario está viendo datos consolidados de todas las tiendas.
     */
    function is_global_view(): bool
    {
        return app()->bound('is_global_view')
            ? app('is_global_view')
            : (session('current_store_id') === 'global');
    }
}
