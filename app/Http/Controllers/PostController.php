<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-comunicados')->only(['index', 'show']);
        $this->middleware('permission:crear-comunicados')->only(['create', 'store']);
        $this->middleware('permission:editar-comunicados')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-comunicados')->only(['destroy']);
    }

    /**
     * Feed principal: lista de comunicados.
     */
    public function index(Request $request)
    {
        $query = Post::with(['author', 'store', 'reads', 'reactions', 'files'])
            ->published()
            ->orderByDesc('pinned')
            ->orderByDesc('published_at');

        // Filtro por sucursal activa
        if (!is_global_view()) {
            // Vista de sucursal: comunicados de esa sucursal + comunicados globales
            $storeId = current_store_id();
            if ($storeId) {
                $query->where(function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                      ->orWhereNull('store_id'); // Globales visibles para todos
                });
            } else {
                $query->whereNull('store_id');
            }
        }
        // Vista global: sin filtro → todos los comunicados

        // Filtro por tipo
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtro por prioridad
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $posts = $query->paginate(10);

        // Marcar como leídos automáticamente
        foreach ($posts as $post) {
            $post->markAsRead();
        }

        return view('admin.feed.index', compact('posts'));
    }

    /**
     * Formulario para crear un comunicado.
     * Crea un "Borrador" vacío para poder adjuntar archivos antes de publicar.
     */
    public function create()
    {
        // store_id correcto según la vista activa
        $storeId = is_global_view() ? null : current_store_id();

        // Buscar si el usuario ya tiene un borrador (published_at = null)
        $post = Post::firstOrCreate(
            ['user_id' => auth()->id(), 'published_at' => null],
            [
                'title'    => '',
                'summary'  => null,
                'body'     => '',
                'priority' => 'normal',
                'type'     => 'comunicado',
                'store_id' => $storeId,
                'pinned'   => false,
            ]
        );

        // Si el borrador existía pero su store_id no coincide con la sesión actual,
        // actualizarlo para que el nuevo comunicado vaya a la sucursal correcta.
        if ($post->wasRecentlyCreated === false && $post->store_id !== $storeId) {
            $post->update(['store_id' => $storeId]);
        }

        return view('admin.feed.create', compact('post'));
    }

    /**
     * Guardar (Publicar) un comunicado.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_id'  => 'required|exists:posts,id',
            'title'    => 'required|string|max:255',
            'summary'  => 'nullable|string',
            'body'     => 'required|string',
            'gjs_data' => 'nullable|string',
            'priority' => 'required|in:normal,importante,urgente',
            'type'     => 'required|in:comunicado,anuncio,tarea',
            'pinned'   => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        $post = Post::findOrFail($request->post_id);
        
        // Evitar que editen borradores de otros
        if ($post->user_id !== auth()->id()) {
            abort(403);
        }

        $validated['pinned'] = $request->has('pinned');
        // Usar el store_id del borrador (ya actualizado en create()) en lugar de la sesión,
        // porque el borrador ya tiene la sucursal correcta asignada al abrirse el formulario.
        // Solo se sobreescribe si la sesión tiene una sucursal específica seleccionada.
        $validated['store_id'] = is_global_view() ? null : current_store_id();
        
        $validated['published_at'] = $request->filled('published_at') 
            ? \Carbon\Carbon::parse($request->published_at) 
            : now();

        // Limpiamos post_id del array de update
        unset($validated['post_id']);

        $post->update($validated);

        // Enviar notificación a los usuarios correspondientes según sucursal
        $usersQuery = \App\Models\User::where('status', true);
        // Nota: el autor también recibe la notificación para poder verificar el envío.

        if ($post->store_id) {
            // Comunicado de una sucursal específica: solo usuarios de esa sucursal
            $usersQuery->whereHas('stores', function ($q) use ($post) {
                $q->where('store_id', $post->store_id);
            });
        }
        // Si store_id es null → comunicado global → se notifica a todos los usuarios activos

        $users = $usersQuery->get();

        if ($users->isNotEmpty()) {
            \Illuminate\Support\Facades\Notification::send(
                $users,
                new \App\Notifications\PostPublishedNotification($post)
            );
        }

        return redirect()->route('feed.index')
            ->with('success', 'Comunicado publicado exitosamente.');
    }

    /**
     * Ver detalle de un comunicado con sus lecturas.
     */
    public function show(Post $post)
    {
        $post->load(['author', 'store', 'reads.user', 'reactions.user', 'files', 'comments.user']);
        $post->markAsRead();

        return view('admin.feed.show', compact('post'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Post $post)
    {
        return view('admin.feed.edit', compact('post'));
    }

    /**
     * Actualizar comunicado.
     */
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'summary'  => 'nullable|string',
            'body'     => 'required|string',
            'gjs_data' => 'nullable|string',
            'priority' => 'required|in:normal,importante,urgente',
            'type'     => 'required|in:comunicado,anuncio,tarea',
            'pinned'   => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['pinned'] = $request->has('pinned');
        
        if ($request->filled('published_at')) {
            $validated['published_at'] = \Carbon\Carbon::parse($request->published_at);
        }

        $post->update($validated);

        return redirect()->route('feed.index')
            ->with('success', 'Comunicado actualizado.');
    }

    /**
     * Eliminar comunicado (soft delete).
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('feed.index')
            ->with('success', 'Comunicado eliminado.');
    }

    /**
     * Toggle de reacción emoji.
     */
    public function react(Request $request, Post $post)
    {
        $request->validate(['emoji' => 'required|string|max:10']);

        $existing = $post->reactions()
            ->where('user_id', auth()->id())
            ->where('emoji', $request->emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $post->reactions()->create([
                'user_id' => auth()->id(),
                'emoji' => $request->emoji,
            ]);
        }

        return back();
    }

    /**
     * Ver quién leyó un comunicado (solo con permiso ver-lecturas).
     */
    public function readers(Post $post)
    {
        $this->authorize('ver-lecturas');

        $post->load(['reads.user']);
        $allUsers = User::where('status', true)->get();
        $readUserIds = $post->reads->pluck('user_id')->toArray();

        $unreadUsers = $allUsers->filter(fn($u) => !in_array($u->id, $readUserIds));

        return view('admin.feed.readers', compact('post', 'unreadUsers'));
    }

    /**
     * Publicar un comentario en un comunicado.
     */
    public function comment(Request $request, Post $post)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $post->comments()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);

        return redirect()->route('feed.show', $post)->with('success', 'Comentario añadido exitosamente.');
    }

    /**
     * Generar bloque HTML usando Gemini AI.
     */
    public function generateAiBlock(Request $request)
    {
        $request->validate(['prompt' => 'required|string']);
        
        $apiKey = Setting::get('gemini_api_key');
        if (!$apiKey) {
            return response()->json(['error' => 'API Key de Gemini no configurada en los ajustes del sistema.']);
        }

        $systemPrompt = "Eres un asistente experto en diseño web usando Bootstrap 5 y HTML. El usuario te pedirá componentes web. Debes devolver ÚNICAMENTE el código HTML puro, sin markdown, sin explicaciones ni etiquetas extra. Usa clases de Bootstrap 5 y trata de usar utilidades (p-3, m-2, bg-light, text-dark, shadow-sm, rounded, etc) para que luzca bien y moderno. No incluyas head ni body.";
        
        try {
            $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]]
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $request->prompt]]]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Si Gemini bloquea el prompt por seguridad o falla, 'content' podría no existir
                $html = data_get($data, 'candidates.0.content.parts.0.text', '');
                
                if (empty($html)) {
                    $finishReason = data_get($data, 'candidates.0.finishReason', 'Desconocido');
                    return response()->json(['error' => 'Gemini no pudo generar contenido. Razón: ' . $finishReason]);
                }

                // Limpiar posibles bloques markdown (```html ... ```)
                $html = preg_replace('/```html/i', '', $html);
                $html = preg_replace('/```/i', '', $html);
                return response()->json(['html' => trim($html)]);
            }

            $errorData = $response->json();
            $errorMessage = (is_array($errorData) && isset($errorData['error']['message'])) 
                ? $errorData['error']['message'] 
                : 'Fallo al comunicarse con Gemini. Código HTTP: ' . $response->status();

            return response()->json([
                'error' => 'Google API: ' . $errorMessage, 
                'details' => $response->body()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Relanzar notificación de un comunicado ya publicado.
     */
    public function resendNotification(Post $post)
    {
        // El permiso ya está validado por el middleware de la ruta (editar-comunicados)

        $usersQuery = \App\Models\User::where('status', true);

        if ($post->store_id) {
            $usersQuery->whereHas('stores', function ($q) use ($post) {
                $q->where('store_id', $post->store_id);
            });
        }

        $users = $usersQuery->get();

        if ($users->isNotEmpty()) {
            \Illuminate\Support\Facades\Notification::send(
                $users,
                new \App\Notifications\PostPublishedNotification($post)
            );
        }

        return redirect()->route('feed.show', $post)
            ->with('success', "Notificación relanzada a {$users->count()} usuario(s).");
    }
}
