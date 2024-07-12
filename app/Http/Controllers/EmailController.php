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
            // Seleciona o template baseado no conteúdo da mensagem
            $template = $this->isBase64($mensagem) ? 'emails.qrcode' : 'emails.mensagem';

            // Se for base64, limpa caracteres de formatação extra
            if ($this->isBase64($mensagem)) {
                $mensagem = preg_replace('/[\r\n]+/', '', $mensagem);
            }

            // Gera o HTML usando o template Blade
            $html = View::make($template, compact('titulo', 'mensagem'))->render();

            // Envia o email usando a biblioteca Resend
            Resend::emails()->send([
                'from' => $origem,
                'to' => $destino,
                'subject' => $titulo,
                'html' => $html,
            ]);

            Log::channel('jobs')->info("Email {$titulo} enviado.");
        } catch (Exception $e) {
            Log::channel('jobs')->error("Erro ao enviar email {$titulo}. Erro: " . $e->getMessage());
        }
    }

    /**
     * Verifica se uma string é uma base64 válida.
     *
     * @param string $string A string a ser verificada.
     * @return bool
     */
    private function isBase64($string)
    {
        return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string);
    }
}
