<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        // Convert base64 QR code to an image and save it
        if (isset($email['qrcode'])) {
            $this->qrCodePath = $this->saveQrCodeAsPng($email['qrcode']);
        }
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
        Log::channel('jobs')->info('Gerado email');
        return $email;
    }

    /**
     * Save base64 QR code as PNG.
     *
     * @param string $base64Data
     * @return string|null
     */
    private function saveQrCodeAsPng($base64Data)
    {
        try {
            $base64Image = str_replace('data:image/png;base64,', '', $base64Data);
            $base64Image = str_replace(' ', '+', $base64Image);
            $imageData = base64_decode($base64Image);

            $filePath = storage_path('app/public/qrcode.png');

            if (file_put_contents($filePath, $imageData) === false) {
                Log::error('Failed to write QR code image to ' . $filePath);
                return null;
            }
            Log::channel('jobs')->info('QR code image saved to ' . $filePath);
            return $filePath;
        } catch (Exception $e) {
            Log::error('Error saving QR code image: ' . $e->getMessage());
            return null;
        }
    }
}
