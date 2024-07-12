<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WppController extends Controller
{
    /**
     * @var EmailController
     */
    protected $emailController;

    /**
     * Cria uma nova instância do WppController.
     *
     * @param EmailController $emailController Instância do EmailController para envio de emails.
     */
    public function __construct(EmailController $emailController)
    {
        $this->emailController = $emailController;
    }
    /**
     * Envia uma mensagem via WhatsApp se o status estiver "Connected".
     *
     * @param string $mensagem A mensagem a ser enviada.
     * @return void|string
     */
    public function mensagemWhats($mensagem)
    {
        $statusWPP = $this->statusWPP();
        switch ($statusWPP['status']) {
            case 'CLOSED':
                // NESSE STATUS, A SESSION EXISTE, MAS ESTÁ FECHADA. PRECISA GERAR O QRCODE
                $this->handleClosedStatus();
                break;
            case 'QRCODE':
                // NESSE STATUS, O QRCODE FOI GERADO, MAS AINDA NÃO FOI LIDO/AUTORIZADO NO APARELHO
                $mensagem = $statusWPP['qrcode'];
                $titulo = 'QR Code - Monitora Sites';
                $destino = 'bravo18br@gmail.com';
                $origem = 'Admin <onboarding@resend.dev>';
                $this->emailController->sendMessageEmail($titulo, $mensagem, $origem, $destino);
                break;
            case 'CONNECTED':
                // NESSE STATUS, O SISTEMA ESTÁ PRONTO PARA ENVIAR MENSAGENS
                $resultado = $this->sendMessageWPP($mensagem);
                if ($resultado['status'] == 'SUCESSO') {
                    Log::channel('jobs')->info($resultado['mensagem']);
                } else {
                    Log::channel('jobs')->error($resultado['mensagem']);
                }
                break;
            case 'ERRO':
                // NESSE STATUS, EXISTE ALGUM ERRO, ENVIA PARA LOG
                $mensagem = $statusWPP['ERRO'];
                Log::channel('jobs')->error($mensagem);
                break;
            default:
                Log::channel('jobs')->error('Status desconhecido (função mensagemWhats): ' . $statusWPP);
                break;
        }
    }

    /**
     * Manipula o status "CLOSED".
     */
    private function handleClosedStatus()
    {
        try {
            $qr_codeWPP = $this->geraQRCodeWPP();
            $mensagem = $qr_codeWPP['qrcode'];
            $titulo = 'QR Code - Monitora Sites';
            $destino = 'bravo18br@gmail.com';
            $origem = 'Admin <onboarding@resend.dev>';
            $this->emailController->sendMessageEmail($titulo, $mensagem, $origem, $destino);
        } catch (Exception $e) {
            Log::channel('jobs')->error("Erro function handleClosedStatus: " . $e->getMessage());
        }
    }

    /**
     * Envia uma mensagem via WhatsApp.
     *
     * @param string $mensagem A mensagem a ser enviada.
     * @return void|string
     */
    private function sendMessageWPP($mensagem)
    {
        try {
            $wpp_server = env('MY_WPP_SERVER');
            $wpp_session = env('MY_WPP_SESSION');
            $url = "{$wpp_server}/api/{$wpp_session}/send-message";
            $wpp_bearer = $this->gerar_bearerWPP();
            $body = [
                "phone" => "554184191656",
                "isGroup" => false,
                "isNewsletter" => false,
                "message" => $mensagem,
            ];
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $wpp_bearer,
            ])->withBody(json_encode($body))->post($url);
            if ($response->successful()) {
                return [
                    'status' => 'SUCESSO',
                    'mensagem' => 'Whats enviado: ' . $mensagem
                ];
            } else {
                return [
                    'status' => 'ERRO',
                    'mensagem' => "Erro function sendMessageWPP: " . $response->status()
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'ERRO',
                'mensagem' => "Erro function sendMessageWPP: " . $e->getMessage()
            ];
        }
    }

    /**
     * Inicia uma nova sessão do WhatsApp e retorna o QRCode.
     *
     * @return string|void O QRCode da nova sessão ou uma mensagem de erro.
     */
    private function geraQRCodeWPP()
    {
        try {
            $wpp_server = env('MY_WPP_SERVER');
            $wpp_session = env('MY_WPP_SESSION');
            $url = "{$wpp_server}/api/{$wpp_session}/start-session";
            $wpp_bearer = $this->gerar_bearerWPP();
            $body = [
                "webhook" => "", // esse endpoint possui um webhook, talvez seja interessante avaliar a utilização
                "waitQrCode" => true,
            ];
            $response = Http::withHeaders([
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
                'Authorization' => $wpp_bearer,
            ])->withBody(json_encode($body))->post($url);
            if ($response->successful()) {
                $responseJson = $response->json();
                return $responseJson;
            } else {
                return [
                    'qrcode' => 'ERRO',
                    'ERRO' => "Erro function geraQRCodeWPP: " . $response->status()
                ];
            }
        } catch (Exception $e) {
            return [
                'qrcode' => 'ERRO',
                'ERRO' => "Erro function geraQRCodeWPP: " . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica o status da conexão do WPP.
     *
     * @return string O status da conexão ou uma mensagem de erro.
     */
    private function statusWPP()
    {
        try {
            $wpp_server = env('MY_WPP_SERVER');
            $wpp_session = env('MY_WPP_SESSION');
            $url = "{$wpp_server}/api/{$wpp_session}/status-session";
            $wpp_bearer = $this->gerar_bearerWPP();
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $wpp_bearer,
            ])->get($url);
            if ($response->successful()) {
                $responseJson = $response->json();
                return $responseJson;
            } else {
                return [
                    'status' => 'ERRO',
                    'ERRO' => "Erro function statusWPP: " . $response->status()
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'ERRO',
                'ERRO' => "Erro function statusWPP: " . $e->getMessage()
            ];
        }
    }

    /**
     * Gera e retorna o token Bearer para a sessão do WPP.
     *
     * @return string O token Bearer ou uma mensagem de erro.
     */
    private function gerar_bearerWPP()
    {
        try {
            $wpp_server = env('MY_WPP_SERVER');
            $wpp_session = env('MY_WPP_SESSION');
            $wpp_secure_token = env('MY_WPP_SECURE_TOKEN');
            $url = "{$wpp_server}/api/{$wpp_session}/{$wpp_secure_token}/generate-token";
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url);
            if ($response->successful()) {
                $responseBody = $response->json();
                $wpp_bearer = 'Bearer ' . $responseBody['token'];
                return $wpp_bearer;
            } else {
                Log::channel('jobs')->error("Erro function gerar_bearerWPP: " . $url . $response->status() . " | Corpo: " . $response->body());
                return "Erro function gerar_bearerWPP: " . $response->status();
            }
        } catch (Exception $e) {
            Log::channel('jobs')->error("Erro function gerar_bearerWPP: " . $e->getMessage());
            return "Erro function gerar_bearerWPP: " . $e->getMessage();
        }
    }
}
