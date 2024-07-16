<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GMailController extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;

    /**
     * Create a new message instance.
     *
     * @param array $emailData
     */
    public function __construct($emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->view($this->emailData['layout'])
            ->subject($this->emailData['titulo'])
            ->with($this->emailData)
            ->to($this->emailData['destino']);

        if (isset($this->emailData['qrcodepath'])) {
            $email->attach($this->emailData['qrcodepath'], [
                'as' => 'qrcode.png',
                'mime' => 'image/png',
            ]);
        }

        return $email;
    }
}
