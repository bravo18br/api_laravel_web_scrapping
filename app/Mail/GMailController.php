<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GMailController extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $qrCodePath;

    /**
     * Create a new message instance.
     *
     * @param array $email
     */
    public function __construct(array $email)
    {
        $this->email = $email;
        $this->qrCodePath = $email['qrcodePath'] ?? null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->email['titulo'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->email['layout'],
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

        if ($this->qrCodePath) {
            $attachments[] = new \Illuminate\Mail\Mailables\Attachment(
                $this->qrCodePath,
                'qrcode.png'
            );
        }

        return $attachments;
    }

    public function build()
    {
        $email = $this->view($this->email['layout'])
            ->subject($this->email['titulo'])
            ->with($this->email)
            ->to($this->email['destino']);

        if ($this->qrCodePath) {
            $email->attach($this->qrCodePath, [
                'as' => 'qrcode.png',
                'mime' => 'image/png',
            ]);
        }

        return $email;
    }
}
