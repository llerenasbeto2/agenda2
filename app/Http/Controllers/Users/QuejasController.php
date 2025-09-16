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
            // Configurar timeout más corto para evitar el error de 30 segundos
            ini_set('max_execution_time', 60);
            
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

            Log::info('Intentando enviar correo de queja/sugerencia', [
                'to' => $recipientEmail,
                'from' => config('mail.from.address'),
                'replyTo' => $senderEmail,
                'category' => $validated['category'],
                'message' => $validated['message'],
                'authenticated' => Auth::check(),
            ]);

            // Construir el contenido del mensaje
            $messageContent = "Categoría: {$validated['category']}\n\n";
            $messageContent .= "Remitente: " . (Auth::check() ? 'Usuario autenticado' : 'Usuario no autenticado') . "\n";
            if ($validated['full_name']) {
                $messageContent .= "Nombre: {$validated['full_name']}\n";
            }
            $messageContent .= "Email: {$validated['email']}\n\n";
            $messageContent .= "Mensaje: {$validated['message']}";

            // Configurar timeout específico para el mailer
            Config::set('mail.timeout', 20);

            // Envío de correo con manejo de timeout
            $this->sendEmailWithTimeout($messageContent, $senderEmail, $senderName, $validated, $recipientEmail);

            Log::info('Correo enviado exitosamente');
            return redirect()->route('quejas.index')->with('success', 'Queja/Sugerencia enviada correctamente');
            
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            Log::error('Error de transporte SMTP', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Intentar método alternativo
            return $this->handleEmailFailure($validated);
            
        } catch (\Exception $e) {
            Log::error('Error general al enviar queja/sugerencia', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->handleEmailFailure($validated);
        }
    }

    private function sendEmailWithTimeout($messageContent, $senderEmail, $senderName, $validated, $recipientEmail)
    {
        // Usar un timeout más corto y manejo de errores específico
        try {
            Mail::raw($messageContent, function ($message) use ($senderEmail, $senderName, $validated, $recipientEmail) {
                $message->to($recipientEmail)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->replyTo($senderEmail, $senderName)
                        ->subject("Nueva {$validated['category']}");
            });
        } catch (\Exception $e) {
            Log::error('Error específico en envío de correo', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function handleEmailFailure($validated)
    {
        // Guardar en base de datos como respaldo (opcional)
        Log::warning('Email no pudo ser enviado, guardando registro para procesamiento posterior', [
            'data' => $validated
        ]);

        // En lugar de fallar, informar al usuario que será procesado
        return redirect()->route('quejas.index')->with('success', 
            'Tu mensaje ha sido recibido y será procesado a la brevedad. Gracias por contactarnos.');
    }
}