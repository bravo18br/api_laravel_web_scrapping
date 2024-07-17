<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * @OA\Schema(
 *     schema="GMailController",
 *     type="object",
 *     required={"emailData"},
 *     @OA\Property(
 *         property="emailData",
 *         type="object",
 *         @OA\Property(property="layout", type="string"),
 *         @OA\Property(property="titulo", type="string"),
 *         @OA\Property(property="destino", type="string"),
 *         @OA\Property(property="qrcodepath", type="string", nullable=true)
 *     )
 * )
 */
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
     * @OA\Post(
     *     path="/email/send",
     *     summary="Enviar email com os dados especificados",
     *     tags={"Email"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/GMailController")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email enviado com sucesso",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao enviar o email"
     *     )
     * )
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
