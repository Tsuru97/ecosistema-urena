<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class WhatsAppService
{
    protected string $apiToken;
    protected string $phoneNumberId;
    protected string $apiUrl;

    public function __construct()
    {
        $dbToken = Setting::get('whatsapp_api_token');
        $dbPhoneId = Setting::get('whatsapp_phone_number_id');

        $this->apiToken = !empty($dbToken) ? $dbToken : config('services.whatsapp.api_token');
        $this->phoneNumberId = !empty($dbPhoneId) ? $dbPhoneId : config('services.whatsapp.phone_number_id');
        $this->apiUrl = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";
    }

    /**
     * Enviar un mensaje usando una plantilla de WhatsApp.
     *
     * @param string $to Número de teléfono con código de país (ej: 5218331234567)
     * @param string $templateName Nombre de la plantilla aprobada en Meta
     * @param string $languageCode Código de idioma (ej: es_MX)
     * @param array $bodyParameters Parámetros para el cuerpo de la plantilla
     * @return bool
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'es_MX', array $bodyParameters = [], array $buttonParameters = []): bool
    {
        $to = preg_replace('/[^0-9]/', '', $to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        // Agregar parámetros del body si existen
        if (!empty($bodyParameters)) {
            $params = [];
            foreach ($bodyParameters as $value) {
                $params[] = [
                    'type' => 'text',
                    'text' => (string) $value,
                ];
            }
            $payload['template']['components'][] = [
                'type' => 'body',
                'parameters' => $params,
            ];
        }

        // Agregar parámetros de botón dinámico si existen
        if (!empty($buttonParameters)) {
            $btnParams = [];
            foreach ($buttonParameters as $value) {
                $btnParams[] = [
                    'type' => 'text',
                    'text' => (string) $value,
                ];
            }
            $payload['template']['components'][] = [
                'type' => 'button',
                'sub_type' => 'url', // o 'url'
                'index' => '0', // asume el primer botón
                'parameters' => $btnParams,
            ];
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->post($this->apiUrl, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp plantilla enviada', [
                    'to' => $to,
                    'template' => $templateName,
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::error('WhatsApp error al enviar plantilla', [
                'to' => $to,
                'template' => $templateName,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp excepción al enviar plantilla', [
                'to' => $to,
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Enviar un mensaje de texto por WhatsApp.
     * Solo funciona si el usuario ya respondió (ventana de 24h).
     *
     * @param string $to Número de teléfono con código de país
     * @param string $message Texto del mensaje
     * @return bool
     */
    public function sendTextMessage(string $to, string $message): bool
    {
        $to = preg_replace('/[^0-9]/', '', $to);

        try {
            $response = Http::withToken($this->apiToken)
                ->post($this->apiUrl, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => true,
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp mensaje enviado', [
                    'to' => $to,
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::error('WhatsApp error al enviar mensaje', [
                'to' => $to,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp excepción al enviar mensaje', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
