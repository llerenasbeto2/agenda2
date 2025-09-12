<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationComment extends Mailable
{
    use Queueable, SerializesModels;

    public $comment;
    public $senderEmail;

    public function __construct($comment, $senderEmail)
    {
        $this->comment = $comment;
        $this->senderEmail = $senderEmail;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->replyTo($this->senderEmail)
                    ->subject('Notificación')
                    ->markdown('emails.reservations_comment')
                    ->with(['comment' => $this->comment]);
    }
}