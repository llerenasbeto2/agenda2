<?php

namespace App\Http\Controllers\Users;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
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
                // Usuario autenticado - usar su email
                $userEmail = auth()->user()->email;
                $validated['email'] = $userEmail;
            } else {
                // Usuario no autenticado - usar email predeterminado
                $validated['email'] = 'agendaudec@gmail.com';
            }

            $senderEmail = $validated['anonymous'] ? config('mail.from.address') : $validated['email'];
            $senderName = $validated['anonymous'] ? 'Anónimo' : ($validated['full_name'] ?: 'Usuario');
            $recipientEmail = 'agendaudec@gmail.com'; // Siempre enviar a este destinatario

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

            // Envío de correo con Mail::raw
            Mail::raw($messageContent, function ($message) use ($senderEmail, $senderName, $validated, $recipientEmail) {
                $message->to($recipientEmail)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->replyTo($senderEmail, $senderName)
                        ->subject("Nueva {$validated['category']}");
            });

            Log::info('Correo enviado exitosamente');
            return redirect()->route('quejas.index')->with('success', 'Queja/Sugerencia enviada correctamente');
        } catch (\Exception $e) {
            Log::error('Error al enviar queja/sugerencia', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withErrors(['error' => 'Error al enviar: ' . $e->getMessage()]);
        }
    }
}