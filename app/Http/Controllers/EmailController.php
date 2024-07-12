<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;
use Illuminate\Support\Facades\View;

class EmailController extends Controller
{
    /**
     * Envia um email com a mensagem formatada em HTML usando um template Blade.
     *
     * @param string $titulo O título do email.
     * @param string $mensagem A mensagem a ser enviada.
     * @param string $origem O endereço de email de origem.
     * @param string $destino O endereço de email de destino.
     * @return void
     */
    public function sendMessageEmail($titulo, $mensagem, $origem, $destino)
    {
        try {
            // Gerar HTML a partir do template Blade
            $html = View::make('emails.mensagem', compact('titulo', 'mensagem'))->render();
            Resend::emails()->send([
                'from' => $origem,
                'to' => $destino,
                'subject' => $titulo,
                'html' => $html,
            ]);
            Log::channel('jobs')->info("Email {$titulo} enviado.");
        } catch (Exception $e) {
            Log::channel('jobs')->error("Erro ao enviar email {$titulo}. Não enviado.");
        }
    }
}
