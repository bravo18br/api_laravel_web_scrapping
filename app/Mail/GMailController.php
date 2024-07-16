<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailData['titulo'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->emailData['layout'],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        if (isset($this->emailData['qrcodepath'])) {
            $attachments[] = new \Illuminate\Mail\Mailables\Attachment(
                $this->emailData['qrcodepath'],
                'qrcode.png'
            );
        }
        return $attachments;
    }

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
