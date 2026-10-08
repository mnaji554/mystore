<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'إعادة تعيين كلمة المرور');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset', with: [
            'user' => $this->user,
            'url' => $this->url,
            'expire' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
        ]);
    }
}
