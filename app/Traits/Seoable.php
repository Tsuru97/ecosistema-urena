<?php

namespace App\Traits;

use App\Models\SeoMetadata;

trait Seoable
{
    /**
     * Relación polimórfica para obtener el SEO Metadata de este modelo
     */
    public function seoMetadata()
    {
        return $this->morphOne(SeoMetadata::class, 'seoable')->withDefault();
    }

    /**
     * Helper param actualizar o crear metadata de SEO.
     */
    public function updateSeo(array $data)
    {
        $request = request();

        if ($request->hasFile('seo_og_image')) {
            $uploadedFile = $request->file('seo_og_image');
            $extension = $uploadedFile->getClientOriginalExtension();
            $filename = \Illuminate\Support\Str::uuid() . '.' . $extension;
            $path = $uploadedFile->storeAs('uploads/seo/' . $this->id, $filename, 'public');

            $fileRecord = \App\Models\File::create([
                'fileable_type' => get_class($this),
                'fileable_id' => $this->id,
                'type' => 'og_image',
                'name' => $uploadedFile->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
            ]);

            $data['og_image_id'] = $fileRecord->id;
        }

        return $this->seoMetadata()->updateOrCreate(
            ['seoable_id' => $this->id, 'seoable_type' => get_class($this)],
            $data
        );
    }
}
