<?php

namespace App\Http\Controllers\Users;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;

class QuejasController extends Controller
{
    public function index()
    {
        return Inertia::render('Quejas/Index');
    }

    public function store(Request $request)
    {
        Log::info('Inicio de store complaint', ['request' => $request->all()]);

        $validated = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'anonymous' => 'boolean',
            'category' => 'required|in:Queja,Sugerencia,Comentario',
            'message' => 'required|string|max:1000',
        ]);

        try {
            // Determinar el email según el estado de autenticación
            if (Auth::check()) {
                $userEmail = auth()->user()->email;
                $validated['email'] = $userEmail;
            } else {
                $validated['email'] = 'agendaudec@gmail.com';
            }

            $senderEmail = $validated['anonymous'] ? config('mail.from.address') : $validated['email'];
            $senderName = $validated['anonymous'] ? 'Anónimo' : ($validated['full_name'] ?: 'Usuario');
            $recipientEmail = 'agendaudec@gmail.com';

            // Log de configuración de mail para debugging
            Log::info('Configuración de mail', [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
            ]);

            Log::info('Intentando enviar correo de queja/sugerencia', [
                'to' => $recipientEmail,
                'from' => config('mail.from.address'),
                'replyTo' => $senderEmail,
                'category' => $validated['category'],
                'authenticated' => Auth::check(),
            ]);

            // Construir el contenido del mensaje
            $messageContent = $this->buildEmailContent($validated);

            // Verificar conexión antes de enviar
            $this->testMailConnection();

            // Envío de correo con timeout personalizado
            Mail::raw($messageContent, function ($message) use ($senderEmail, $senderName, $validated, $recipientEmail) {
                $message->to($recipientEmail)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->replyTo($senderEmail, $senderName)
                        ->subject("Nueva {$validated['category']}");
            });

            Log::info('Correo enviado exitosamente');
            
            return redirect()->route('quejas.index')->with('success', 'Queja/Sugerencia enviada correctamente');
            
        } catch (\Swift_TransportException $e) {
            Log::error('Error de transporte SMTP', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            
            return redirect()->back()->withErrors([
                'error' => 'Error de conexión con el servidor de correo. Por favor, inténtalo más tarde.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error general al enviar queja/sugerencia', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()->withErrors([
                'error' => 'Error al enviar el mensaje. Por favor, inténtalo más tarde.'
            ]);
        }
    }

    /**
     * Construye el contenido del email
     */
    private function buildEmailContent($validated)
    {
        $messageContent = "=== NUEVA {$validated['category']} ===\n\n";
        $messageContent .= "Categoría: {$validated['category']}\n";
        $messageContent .= "Fecha: " . now()->format('d/m/Y H:i:s') . "\n";
        $messageContent .= "Remitente: " . (Auth::check() ? 'Usuario autenticado' : 'Usuario no autenticado') . "\n";
        
        if (!empty($validated['full_name'])) {
            $messageContent .= "Nombre: {$validated['full_name']}\n";
        }
        
        $messageContent .= "Email: {$validated['email']}\n";
        $messageContent .= str_repeat("=", 50) . "\n\n";
        $messageContent .= "MENSAJE:\n";
        $messageContent .= $validated['message'];
        $messageContent .= "\n\n" . str_repeat("=", 50);

        return $messageContent;
    }

    /**
     * Prueba la conexión de mail
     */
    private function testMailConnection()
    {
        try {
            $transport = Mail::getSwiftMailer()->getTransport();
            if (method_exists($transport, 'start')) {
                $transport->start();
            }
        } catch (\Exception $e) {
            Log::warning('Test de conexión falló', ['error' => $e->getMessage()]);
            // No lanzamos excepción aquí, solo loggeamos
        }
    }
}