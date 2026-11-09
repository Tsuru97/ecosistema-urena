<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CacheableObserver
{
    /**
     * Mapa de dependencias: cuando un modelo se modifica,
     * también se invalidan los tags de los modelos dependientes.
     *
     * Ejemplo: Cambiar un Rol → invalidar caché de Usuarios.
     */
    protected static array $dependencies = [];

    /**
     * Registrar dependencias desde fuera del observer.
     */
    public static function registerDependency(string $sourceTag, array $dependentTags): void
    {
        self::$dependencies[$sourceTag] = array_merge(
            self::$dependencies[$sourceTag] ?? [],
            $dependentTags
        );
    }

    public function created(Model $model) { $this->flushWithDependencies($model); }
    public function updated(Model $model) { $this->flushWithDependencies($model); }
    public function deleted(Model $model) { $this->flushWithDependencies($model); }
    public function restored(Model $model) { $this->flushWithDependencies($model); }
    public function forceDeleted(Model $model) { $this->flushWithDependencies($model); }

    protected function flushWithDependencies(Model $model): void
    {
        if (!method_exists($model, 'getCacheTagForDebug')) {
            return; // No es un modelo Cacheable
        }

        if (!Cache::supportsTags()) {
            Cache::flush();
            return;
        }

        // Flush del propio modelo
        $tag = $model::getCacheTag();
        Cache::tags($tag)->flush();

        // Flush de dependientes
        $dependents = self::$dependencies[$tag] ?? [];
        foreach ($dependents as $depTag) {
            Cache::tags($depTag)->flush();
        }
    }
}
