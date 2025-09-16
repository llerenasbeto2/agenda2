<?php

namespace App\Http\Controllers\Users;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendComplaintEmailJob;
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

            Log::info('Preparando email para cola', [
                'to' => $recipientEmail,
                'replyTo' => $senderEmail,
                'category' => $validated['category'],
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

            // Preparar datos para el Job
            $emailData = [
                'content' => $messageContent,
                'recipient' => $recipientEmail,
                'replyTo' => $senderEmail,
                'senderName' => $senderName,
                'subject' => "Nueva {$validated['category']}"
            ];

            // Despachar el job a la cola
            SendComplaintEmailJob::dispatch($emailData);

            Log::info('Email agregado a la cola exitosamente', [
                'recipient' => $recipientEmail,
                'subject' => $emailData['subject']
            ]);

            return redirect()->route('quejas.index')->with('success', 
                'Tu mensaje ha sido recibido y será procesado en breve. Gracias por contactarnos.');

        } catch (\Exception $e) {
            Log::error('Error al procesar queja/sugerencia', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'error' => 'Ocurrió un error al procesar tu mensaje. Por favor intenta nuevamente.'
            ]);
        }
    }

    // Método adicional para revisar el estado de la cola (opcional)
    public function queueStatus()
    {
        if (!Auth::check() || !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $pendingJobs = \DB::table('jobs')->count();
        $failedJobs = \DB::table('failed_jobs')->count();

        return response()->json([
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs
        ]);
    }
}