<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Sluggable
{
    /**
     * Boot the trait.
     */
    public static function bootSluggable()
    {
        static::creating(function ($model) {
            $model->generateUniqueSlug();
        });

        static::updating(function ($model) {
            $sourceAttr = $model->getSlugSourceAttribute();
            $customSlug = request('custom_slug');
            
            // Si hay un custom slug en el request y es diferente al actual, o si la fuente cambió o si no hay slug actual
            if (
                (!empty($customSlug) && $customSlug !== $model->slug) || 
                $model->isDirty($sourceAttr) || 
                empty($model->slug)
            ) {
                $model->generateUniqueSlug();
            }
        });
    }

    /**
     * Define qué atributo usar para generar el slug.
     * Por defecto es 'name', pero puede sobreescribirse en el modelo.
     */
    public function getSlugSourceAttribute(): string
    {
        return 'name';
    }

    /**
     * Generar un slug único para este modelo.
     */
    protected function generateUniqueSlug()
    {
        // 1. Priorizar el custom_slug que viene del request manual (si existe)
        $customSlug = request('custom_slug');
        if (!empty($customSlug)) {
            $baseSlug = Str::slug($customSlug);
        } else {
            // 2. Fallback a auto-generar desde el source attribute
            $sourceAttr = $this->getSlugSourceAttribute();
            if (!isset($this->{$sourceAttr})) {
                return; // No hay de donde generar
            }
            $baseSlug = Str::slug($this->{$sourceAttr});
        }

        $slug = $baseSlug;
        $count = 1;

        // Comprobar si existe en la BD
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $count;
            $count++;
        }

        $this->slug = $slug;
    }

    /**
     * Verifica si un slug ya existe excluyendo el modelo actual
     */
    protected function slugExists($slug): bool
    {
        return static::where('slug', $slug)
            ->when($this->exists, function ($query) {
                $query->where('id', '!=', $this->id);
            })
            ->exists();
    }
}
