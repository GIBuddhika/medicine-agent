<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;
    public $name;
    public $email;
    public $token;
    public $is_admin;
    public $resetLink;

    public function __construct($data)
    {
        $siteUrl = config('app.SITE_URL');
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->token = $data['token'];
        $this->is_admin = $data['is_admin'];
        $siteUrl = $data['is_admin'] ? $siteUrl . '/admin' : $siteUrl;
        $this->resetLink = $siteUrl . '/reset-password?token=' . $this->token . '&email=' . $this->email;
    }

    public function build()
    {
        return $this->view('mails.forgot-password-request')
            ->subject('Password reset request - Medicine Agent');
    }
}
