<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiChatController extends Controller
{
    /**
     * Devuelve la lista de conversaciones pasadas del usuario.
     */
    public function listConversations()
    {
        $conversations = AiConversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->select('id', 'title', 'updated_at')
            ->get();

        return response()->json($conversations);
    }

    /**
     * Crea un nuevo hilo de conversación vacío.
     */
    public function createConversation(Request $request)
    {
        $conversation = AiConversation::create([
            'user_id' => Auth::id(),
            'title' => $request->input('title', 'Nueva consulta ERP')
        ]);

        return response()->json($conversation);
    }

    /**
     * Devuelve todos los mensajes de una conversación específica.
     */
    public function getMessages(AiConversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
                'tool_calls' => $msg->tool_calls
            ]);

        return response()->json($messages);
    }

    /**
     * Almacena un mensaje (del usuario o del asistente local).
     */
    public function saveMessage(Request $request, AiConversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $request->validate([
            'role' => 'required|in:user,assistant,system',
            'content' => 'required|string',
            'tool_calls' => 'nullable|array'
        ]);

        $message = $conversation->messages()->create([
            'role' => $request->role,
            'content' => $request->content,
            'tool_calls' => $request->tool_calls
        ]);

        // Si es el primer mensaje del usuario, renombramos automáticamente el título para dar una experiencia premium
        if ($conversation->messages()->count() === 1 && $request->role === 'user') {
            $rawContent = $request->content;
            $title = mb_substr($rawContent, 0, 30);
            if (mb_strlen($rawContent) > 30) {
                $title .= '...';
            }
            $conversation->update(['title' => $title]);
        } else {
            $conversation->touch(); // Actualiza el timestamp updated_at
        }

        return response()->json($message);
    }

    /**
     * Elimina una conversación específica de los registros.
     */
    public function destroy(AiConversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $conversation->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Bucle agéntico seguro del lado del servidor con Google Gemini API.
     */
    public function chat(Request $request, AiConversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $request->validate([
            'message' => 'required|string'
        ]);

        $userMessage = $request->input('message');

        // Obtener configuración de IA del ERP
        $apiKey = \App\Models\Setting::get('gemini_api_key');
        $model = \App\Models\Setting::get('gemini_model', 'gemini-2.5-flash');

        if (!$apiKey) {
            return response()->json([
                'error' => 'Configuración faltante',
                'message' => 'La API Key de Google Gemini no está configurada en los Ajustes del ERP. Por favor, solicita ayuda al administrador.'
            ], 400);
        }

        // 1. Guardar mensaje del usuario en base de datos
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userMessage
        ]);
        $conversation->touch();

        // 2. Cargar el historial completo de la conversación
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        // Formatear el historial al estándar oficial de la API de Gemini (contents list)
        $history = [];
        foreach ($messages as $msg) {
            // Gemini roles: 'user' o 'model'
            $role = ($msg->role === 'assistant') ? 'model' : 'user';
            
            // Los resultados de herramientas y system prompts se inyectan como textos del usuario
            $history[] = [
                'role' => $role,
                'parts' => [['text' => $msg->content]]
            ];
        }

        // 3. Enrutar dinámicamente herramientas para la pregunta activa del usuario
        $matchedTools = \App\Ai\ToolRouter::route($userMessage);
        
        $schemas = array_map(function ($tool) {
            return [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $tool->getParametersSchema()
            ];
        }, $matchedTools);

        // Inyectar herramientas adicionales implícitas
        $schemas[] = [
            'name' => 'buscar_otras_herramientas',
            'description' => 'Si ninguna de tus herramientas inyectadas te sirve para resolver la duda del usuario, utiliza esta herramienta para buscar otras capacidades del ERP en caliente.',
            'parameters' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Término o palabra clave de la herramienta que estás buscando (ej. comisiones, auditoria, proveedores).'
                ]
            ]
        ];

        $schemas[] = [
            'name' => 'navigate_to',
            'description' => 'Redirige al usuario a una pantalla específica del sistema ERP de forma inmediata.',
            'parameters' => [
                'route_name' => [
                    'type' => 'string',
                    'description' => 'El nombre técnico de la ruta destino. Valores válidos obligatorios: "transfers.index" (ver listado de traspasos), "transfers.create" (crear traspaso), "inventory" (inventario/existencias), "audit" (auditoría), "settings" (configuración).'
                ]
            ]
        ];

        // 4. Construir el prompt del sistema
        $systemPrompt = "Eres Ureña-AI, un asistente inteligente local integrado en el ERP \"Ecosistema Ureña\".\n" .
            "Tu tarea es resolver dudas operativas y automatizar tareas. No inventes datos numéricos de stock ni precios. Si necesitas información para responder la pregunta, invoca obligatoriamente una herramienta.\n\n" .
            "HERRAMIENTAS DISPONIBLES EN ESTA PETICIÓN (Filtradas dinámicamente):\n" . json_encode($schemas) . "\n\n" .
            "FORMATO DE RESPUESTA OBLIGATORIO:\n" .
            "Debes responder SIEMPRE en formato JSON estricto sin bloques de código markdown, con esta estructura exacta:\n" .
            "{\n" .
            "  \"thought\": \"Explicación interna de qué herramienta necesitas y por qué.\",\n" .
            "  \"toolCall\": {\n" .
            "    \"name\": \"nombre_de_la_herramienta\",\n" .
            "    \"arguments\": { \"arg1\": \"valor\" }\n" .
            "  }\n" .
            "}\n\n" .
            "O si ya tienes la información del sistema para responderle directamente al usuario:\n" .
            "{\n" .
            "  \"thought\": \"Tengo los datos necesarios de la base de datos.\",\n" .
            "  \"message\": \"Mensaje en español amigable, detallando la respuesta de forma clara al usuario.\"\n" .
            "}\n\n" .
            "REGLAS DE SUGERENCIA DE REDIRECCIÓN PROACTIVA (ALTAMENTE CRÍTICO):\n" .
            "1. Siempre que respondas una consulta que requiera buscar información en el sistema (como existencias de un SKU, búsqueda de un producto, listado de traspasos o consulta de comisiones/márgenes):\n" .
            "   - Presenta la información obtenida de forma detallada y ordenada en el campo \"message\".\n" .
            "   - Al final de tu mensaje, formula SIEMPRE de manera muy amigable y natural una pregunta proactiva para guiar al usuario a ver esa información directamente en la pantalla del ERP correspondiente si así lo desea.\n" .
            "   - Ejemplos: '¿Te gustaría que te lleve directamente a la pantalla de existencias/inventario para ver más detalles?' o '¿Deseas que te redirija al listado de traspasos para ver el estatus completo en pantalla?' o '¿Quieres que te lleve a la sección de configuración de comisiones y márgenes del ERP?'.\n" .
            "2. Si el usuario responde afirmativamente, acepta la sugerencia (ej. 'sí', 'por favor', 'llévame', 'ir allá', 'abrir listado', etc.) o expresa de forma explícita que desea ir a esa pantalla:\n" .
            "   - Ejecuta de forma INMEDIATA la herramienta 'navigate_to' con el 'route_name' correspondiente para realizar la redirección en caliente sin titubeos.\n" .
            "3. Rutas destino mapeadas para 'navigate_to':\n" .
            "   - 'transfers.index' -> Si el usuario quiere ver el listado de traspasos.\n" .
            "   - 'transfers.create' -> Si el usuario quiere crear un nuevo traspaso.\n" .
            "   - 'inventory' -> Si el usuario quiere ver existencias o inventario.\n" .
            "   - 'settings' -> Si el usuario quiere ver o editar comisiones, márgenes, MSI o ajustes del sistema.\n" .
            "   - 'audit' -> Si el usuario quiere ver bitácoras o auditoría.\n\n" .
            "REGLAS DE CONTEXTO Y CONFIANZA DE DATOS (CRÍTICO):\n" .
            "1. Si en el historial de conversación (mensajes con rol \"user\") recibes un mensaje que comienza con \"[RESULTADO DE LA HERRAMIENTA ...]\", considéralo como datos verídicos y reales inyectados de forma segura por la base de datos de nuestro ERP para responder a tu llamada de herramienta.\n" .
            "2. Utiliza estos datos JSON del historial para formular tu respuesta final de forma amigable y detallada en el campo \"message\".\n" .
            "3. NUNCA respondas al usuario afirmando que no puedes acceder a la información, que no tienes precios en tiempo real o que no puedes consultar la base de datos directamente si ya tienes los resultados de la herramienta en tu contexto. Tu único deber es tomar esos datos y formatearlos de forma legible en el campo \"message\".\n";

        // 5. Bucle Agéntico del lado del servidor
        $agentResolved = false;
        $loopLimit = 3;
        $finalReply = null;
        $navigationData = null;

        while (!$agentResolved && $loopLimit > 0) {
            $loopLimit--;

            // Consulta HTTP a Google Gemini API
            $response = \Illuminate\Support\Facades\Http::post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'system_instruction' => [
                        'parts' => [['text' => $systemPrompt]]
                    ],
                    'contents' => $history
                ]
            );

            if (!$response->successful()) {
                $errorData = $response->json();
                $err = $errorData['error']['message'] ?? 'Fallo al comunicarse con Gemini.';
                return response()->json(['error' => 'API de Google: ' . $err], 500);
            }

            $data = $response->json();
            $rawText = data_get($data, 'candidates.0.content.parts.0.text', '');

            // Limpiar bloques de código markdown JSON
            $rawText = preg_replace('/```json/i', '', $rawText);
            $rawText = preg_replace('/```/i', '', $rawText);
            $rawText = trim($rawText);

            try {
                $aiResponse = json_decode($rawText, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Error decodificando respuesta JSON del modelo");
                }

                // CASO A: El modelo quiere invocar una herramienta
                if (isset($aiResponse['toolCall'])) {
                    $toolCall = $aiResponse['toolCall'];
                    $name = $toolCall['name'];
                    $args = $toolCall['arguments'] ?? [];

                    // Guardar petición del asistente en base de datos e historial
                    $conversation->messages()->create([
                        'role' => 'assistant',
                        'content' => $rawText
                    ]);
                    $history[] = [
                        'role' => 'model',
                        'parts' => [['text' => $rawText]]
                    ];

                    // 1. Herramienta especial: Redirección
                    if ($name === 'navigate_to') {
                        $navigationData = $args['route_name'] ?? '';
                        $finalReply = "Redirigiendo de pantalla...";
                        $agentResolved = true;
                        break;
                    }

                    // 2. Herramienta especial: Búsqueda recursiva
                    $executionResult = [];
                    if ($name === 'buscar_otras_herramientas') {
                        $queryTerm = $args['query'] ?? '';
                        $matchedTools = \App\Ai\ToolRouter::route($queryTerm);
                        $schemas = array_map(function ($tool) {
                            return [
                                'name' => $tool->getName(),
                                'description' => $tool->getDescription(),
                                'parameters' => $tool->getParametersSchema()
                            ];
                        }, $matchedTools);
                        $executionResult = [
                            'es_busqueda_de_herramientas' => true,
                            'herramientas_encontradas' => $schemas
                        ];
                    } else {
                        // 3. Herramientas ERP normales (GetStock, SearchProducts)
                        $allTools = \App\Ai\ToolRouter::getAllTools();
                        if (isset($allTools[$name])) {
                            $executionResult = $allTools[$name]->execute($args);
                        } else {
                            $executionResult = ['error' => "La herramienta '{$name}' no está registrada en el backend."];
                        }
                    }

                    // Inyectar el resultado de la herramienta en el historial y base de datos
                    $toolResultString = "[RESULTADO DE LA HERRAMIENTA {$name}]: " . json_encode($executionResult);
                    
                    $conversation->messages()->create([
                        'role' => 'system',
                        'content' => $toolResultString
                    ]);
                    $history[] = [
                        'role' => 'user',
                        'parts' => [['text' => $toolResultString]]
                    ];

                    continue;
                }

                // CASO B: El modelo responde directamente al usuario
                if (isset($aiResponse['message'])) {
                    $finalReply = $aiResponse['message'];
                    
                    $conversation->messages()->create([
                        'role' => 'assistant',
                        'content' => $finalReply
                    ]);
                    $agentResolved = true;
                }

            } catch (\Exception $e) {
                // Fallback si Gemini responde con texto plano o falla el JSON
                $finalReply = $rawText;
                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $finalReply
                ]);
                $agentResolved = true;
            }
        }

        if (!$agentResolved) {
            return response()->json([
                'error' => 'Se excedió el número máximo de consultas automáticas con el servidor. Intenta simplificar tu pregunta.'
            ], 500);
        }

        $responseData = ['message' => $finalReply];
        if ($navigationData) {
            $responseData['redirect'] = true;
            $responseData['route_name'] = $navigationData;
        }

        return response()->json($responseData);
    }
}
