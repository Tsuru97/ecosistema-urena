<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use App\Mail\VerificationCodeMail;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Display the user's profile settings page.
     */
    public function edit()
    {
        $user = Auth::user();
        
        // Cargar sesiones activas del usuario (requiere que el driver de session sea database)
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        return view('admin.profile.edit', compact('user', 'sessions'));
    }

    /**
     * Update the user's personal details.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'rfc' => 'nullable|string|max:13',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string|max:1000',
        ]);

        // Si cambia el correo, requerir validación OTP
        if ($request->input('email') !== $user->email) {
            $request->validate(['otp_code' => 'required|string|size:6']);
            
            $sessionCode = session('profile_otp_code');
            $sessionExpires = session('profile_otp_expires');

            if (!$sessionCode || $sessionCode !== $request->input('otp_code') || Carbon::now()->greaterThan($sessionExpires)) {
                return back()->with('error', 'El código de verificación es inválido o ha expirado.')->withInput();
            }
            
            // Si validó bien, limpiar sesión OTP
            session()->forget(['profile_otp_code', 'profile_otp_expires']);
        }

        $user->update($validated);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        // Validar código OTP desde sesión
        $sessionCode = session('profile_otp_code');
        $sessionExpires = session('profile_otp_expires');

        if (!$sessionCode || $sessionCode !== $request->input('otp_code') || Carbon::now()->greaterThan($sessionExpires)) {
            return back()->with('error', 'El código de verificación es inválido o ha expirado.');
        }

        // Todo correcto, limpiar sesión y actualizar password
        session()->forget(['profile_otp_code', 'profile_otp_expires']);

        Auth::user()->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    /**
     * Delete a specific session (revoke access from a device).
     */
    public function destroySession($id)
    {
        DB::table('sessions')->where('id', $id)->where('user_id', Auth::id())->delete();
        
        return back()->with('success', 'Sesión cerrada exitosamente en ese dispositivo.');
    }

    /**
     * Endpoint asíncrono que genera OTP y lo manda a correo y WhatsApp.
     */
    public function generateOtp(Request $request)
    {
        $user = Auth::user();
        $key = 'generate-otp:' . $user->id;

        // Limitar a 1 intento cada 2 minutos
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false, 
                'message' => 'Por favor espera '.$seconds.' segundos antes de solicitar otro código.'
            ], 429);
        }
        RateLimiter::hit($key, 120);

        // Generar código y guardarlo en sesión 5 mins
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        session([
            'profile_otp_code' => $code,
            'profile_otp_expires' => Carbon::now()->addMinutes(5)
        ]);

        // Determinar acción y destinatarios
        $action = $request->input('action', 'profile_edit');
        $messageText = 'Recientemente solicitaste realizar un cambio sensible en tu perfil de Ecosistema Ureña (como cambiar tu correo electrónico o tu contraseña).';
        
        $recipients = collect();
        if (in_array($action, ['pos_custom_price', 'msi_campaign'])) {
            $recipients = \App\Models\User::role(['Super Admin', 'Dirección'])->get();
            if ($action === 'pos_custom_price') {
                $messageText = 'Se ha solicitado una **autorización para aplicar un Precio Personalizado / Descuento de Regateo** en la caja del Punto de Venta (POS) por el usuario <strong>' . e($user->name) . '</strong> (Correo: ' . e($user->email) . ').';
            } elseif ($action === 'msi_campaign') {
                $messageText = 'Se ha solicitado una **autorización para activar o modificar la Campaña de Meses Sin Intereses (MSI)** global en los ajustes del sistema por el usuario <strong>' . e($user->name) . '</strong> (Correo: ' . e($user->email) . ').';
            }
        }

        // Si no es especial o no hay destinatarios con el rol, enviar al usuario actual
        if ($recipients->isEmpty()) {
            $recipients->push($user);
        }

        try {
            // Guardar en sesión primero (antes del envío para que la respuesta sea inmediata)
            // Capturar valores para closure (evitar referencia tardía)
            $recipientsData = $recipients->map(fn($r) => [
                'email' => $r->email,
                'phone' => $r->phone ?? null,
                'name'  => $r->name,
            ])->toArray();
            $codeSnapshot    = $code;
            $messageSnapshot = $messageText;

            // Responder inmediatamente al frontend y enviar los mensajes DESPUÉS de la respuesta
            app()->terminating(function () use ($recipientsData, $codeSnapshot, $messageSnapshot) {
                foreach ($recipientsData as $recipientData) {
                    try {
                        Mail::to($recipientData['email'])->send(new VerificationCodeMail($codeSnapshot, $messageSnapshot));
                    } catch (\Exception $mailErr) {
                        \Illuminate\Support\Facades\Log::error('OTP Mail error: ' . $mailErr->getMessage());
                    }

                    if (!empty($recipientData['phone'])) {
                        try {
                            $whatsapp   = new \App\Services\WhatsAppService();
                            $dbTemplate = \App\Models\Setting::get('whatsapp_template_otp');
                            $templateName = !empty($dbTemplate) ? $dbTemplate : 'codigo_verificacion';
                            $whatsapp->sendTemplate(
                                $recipientData['phone'],
                                $templateName,
                                'es_MX',
                                [$codeSnapshot],
                                [$codeSnapshot]
                            );
                        } catch (\Exception $waErr) {
                            \Illuminate\Support\Facades\Log::error('OTP WhatsApp error: ' . $waErr->getMessage());
                        }
                    }
                }
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generando OTP: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al intentar enviar el correo o mensaje.'], 500);
        }
    }

    /**
     * Endpoint asíncrono para verificar OTP antes de hacer el envío del HTML Form
     */
    public function validateOtp(Request $request)
    {
        $request->validate(['otp_code' => 'required|string|size:6']);
        
        $sessionCode = session('profile_otp_code');
        $sessionExpires = session('profile_otp_expires');

        if (!$sessionCode || $sessionCode !== $request->input('otp_code') || Carbon::now()->greaterThan($sessionExpires)) {
            return response()->json([
                'success' => false,
                'message' => 'Código incorrecto o expirado.'
            ], 400);
        }

        return response()->json(['success' => true]);
    }
}
