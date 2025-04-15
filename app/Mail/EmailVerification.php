<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Votre code de vérification Fish App')
                    ->view('emails.verify-email')
                    ->with([
                        'code' => $this->user->verification_code,
                        'expires' => $this->user->verification_code_expires_at->format('d/m/Y H:i'),
                    ]);
    }
}
