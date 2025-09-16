<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class SendComplaintEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailData;
    
    // Número de intentos antes de fallar
    public $tries = 3;
    
    // Tiempo máximo de ejecución (en segundos)
    public $timeout = 60;
    
    // Tiempo entre reintentos (en segundos)
    public $backoff = [10, 30, 60];

    public function __construct($emailData)
    {
        $this->emailData = $emailData;
        
        // Configurar la cola específica
        $this->onQueue('emails');
    }

    public function handle()
    {
        try {
            Log::info('Iniciando envío de email desde cola', [
                'recipient' => $this->emailData['recipient'],
                'subject' => $this->emailData['subject'],
                'attempt' => $this->attempts()
            ]);

            // Configurar timeout específico para evitar el error de 30 segundos
            ini_set('max_execution_time', 50);
            
            $data = $this->emailData;
            
            Mail::raw($data['content'], function ($message) use ($data) {
                $message->to($data['recipient'])
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->replyTo($data['replyTo'], $data['senderName'])
                        ->subject($data['subject']);
            });

            Log::info('Email enviado exitosamente desde cola', [
                'recipient' => $data['recipient'],
                'subject' => $data['subject'],
                'attempt' => $this->attempts()
            ]);

        } catch (Exception $e) {
            Log::error('Error enviando email desde cola', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'attempt' => $this->attempts(),
                'data' => $this->emailData
            ]);

            // Si es el último intento, no reintentes
            if ($this->attempts() >= $this->tries) {
                Log::error('Email falló definitivamente después de todos los intentos', [
                    'data' => $this->emailData,
                    'error' => $e->getMessage()
                ]);
                
                // Aquí podrías guardar en BD para revisión manual
                $this->fail($e);
                return;
            }

            // Relanzar excepción para reintento automático
            throw $e;
        }
    }

    public function failed(Exception $exception)
    {
        Log::error('Job de email falló definitivamente', [
            'error' => $exception->getMessage(),
            'data' => $this->emailData,
            'attempts' => $this->attempts()
        ]);

        // Aquí puedes implementar notificación al administrador
        // o guardar en una tabla de emails fallidos
    }

    // Configurar el delay entre reintentos
    public function retryAfter()
    {
        return now()->addSeconds($this->backoff[$this->attempts() - 1] ?? 60);
    }
}