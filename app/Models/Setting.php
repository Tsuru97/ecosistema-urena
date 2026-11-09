<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['store_id', 'group', 'key', 'value', 'type'];

    /**
     * Obtener un ajuste con herencia: tienda → global → default.
     */
    public static function get(string $key, mixed $default = null, ?int $storeId = null): mixed
    {
        $storeId = $storeId ?? current_store_id();

        $cacheKey = "settings.{$storeId}.{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default, $storeId) {
            // 1. Buscar en la tienda específica
            if ($storeId && $storeId !== 'global') {
                $setting = static::where('store_id', $storeId)->where('key', $key)->first();
                if ($setting) {
                    return static::castValue($setting->value, $setting->type);
                }
            }

            // 2. Buscar ajuste global (store_id = null)
            $setting = static::whereNull('store_id')->where('key', $key)->first();
            if ($setting) {
                return static::castValue($setting->value, $setting->type);
            }

            // 3. Valor por defecto
            return $default;
        });
    }

    /**
     * Guardar un ajuste para una tienda o global.
     */
    public static function set(string $key, mixed $value, ?int $storeId = null, string $group = 'general', string $type = 'string'): void
    {
        static::updateOrCreate(
            ['store_id' => $storeId, 'key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type]
        );

        // Limpiar caché
        Cache::forget("settings.{$storeId}.{$key}");
        Cache::forget("settings.global.{$key}");
        Cache::forget("settings..{$key}");
        
        try {
            foreach (\App\Models\Store::pluck('id') as $id) {
                Cache::forget("settings.{$id}.{$key}");
            }
        } catch (\Exception $e) {
            // Evitar fallas si la tabla de sucursales aún no está disponible
        }
    }

    /**
     * Obtener todos los ajustes de un grupo para una tienda (con herencia).
     */
    public static function getGroup(string $group, ?int $storeId = null): array
    {
        $storeId = $storeId ?? current_store_id();

        // Global settings as base
        $globals = static::whereNull('store_id')
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray();

        // Store overrides
        if ($storeId && $storeId !== 'global') {
            $storeSettings = static::where('store_id', $storeId)
                ->where('group', $group)
                ->pluck('value', 'key')
                ->toArray();

            return array_merge($globals, $storeSettings);
        }

        return $globals;
    }

    /**
     * Cast del valor según su tipo.
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
