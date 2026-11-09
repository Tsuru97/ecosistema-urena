<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Store a newly created file in storage.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB mix
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'type' => 'required|string',
        ]);

        $modelClass = $request->input('model_type');
        $modelId = $request->input('model_id');
        $type = $request->input('type');

        if (!class_exists($modelClass)) {
            return response()->json(['error' => 'Model does not exist'], 400);
        }

        $model = $modelClass::find($modelId);
        if (!$model) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        $uploadedFile = $request->file('file');
        
        // Determinar si debemos reemplazar el archivo, si es avatar.
        if ($type === 'avatar') {
            // Eliminar avatar anterior del modelo si existe
            $oldAvatar = $model->files()->where('type', 'avatar')->first();
            if ($oldAvatar) {
                Storage::disk('public')->delete($oldAvatar->path);
                $oldAvatar->delete();
            }
        }

        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $uploadedFile->storeAs('uploads/' . strtolower(class_basename($modelClass)) . '/' . $modelId, $filename, 'public');

        $fileRecord = File::create([
            'fileable_type' => $modelClass,
            'fileable_id' => $modelId,
            'type' => $type,
            'name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => $fileRecord,
            'url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Remove the specified file.
     */
    public function destroy(File $file)
    {
        Storage::disk('public')->delete($file->path);
        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }
}
