<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ComplaintMail extends Mailable
{
    use Queueable, SerializesModels;

    public $full_name;
    public $email;
    public $category;
    public $message;
    public $anonymous;

    public function __construct($full_name, $email, $category, $message, $anonymous)
    {
        $this->full_name = $full_name;
        $this->email = $email;
        $this->category = $category;
        $this->message = $message;
        $this->anonymous = $anonymous;
    }

    public function build()
    {
        $fromEmail = $this->anonymous || !$this->email ? config('mail.from.address') : $this->email;
        $fromName = $this->anonymous || !$this->full_name ? 'Anónimo' : $this->full_name;

        return $this->from($fromEmail, $fromName)
                    ->to('agendaudec@gmail.com')
                    ->subject('Nueva ' . $this->category)
                    ->markdown('emails.complaint')
                    ->with([
                        'full_name' => $this->full_name,
                        'email' => $this->email,
                        'category' => $this->category,
                        'message' => $this->message,
                        'anonymous' => $this->anonymous,
                    ]);
    }
}