<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WppController extends Controller
{
    /**
     * Envia uma mensagem via WhatsApp se o status estiver "Connected".
     *
     * @param string $mensagem A mensagem a ser enviada.
     * @return void|string
     */
    public function mensagemWhats($mensagem)
    {
        if ($this->statusWPP() == 'CLOSED') {
            // tarefa caso WPP esteja off || acionar envio EMAIL com QRCODE
            Log::channel('jobs')->info("ENTROU NO CLOSED STATUSWPP: " . $this->statusWPP());
            $qr_codeWPP = $this->startSessionWPP();
        }
        if ($this->statusWPP() == 'Connected') {
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
                    Log::channel('jobs')->info('Whats enviado: ' . $mensagem);
                    return;
                } else {
                    Log::channel('jobs')->error("Erro function mensagemWhats: " . $response->status());
                    return "Erro function mensagemWhats: " . $response->status();
                }
            } catch (Exception $e) {
                Log::channel('jobs')->error("Erro function mensagemWhats: " . $e->getMessage());
                return "Erro function mensagemWhats: " . $e->getMessage();
            }
        }
    }

    /**
     * Inicia uma nova sessão do WhatsApp e retorna o QRCode.
     *
     * @return string|void O QRCode da nova sessão ou uma mensagem de erro.
     */
    private function startSessionWPP()
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
                $responseBody = $response->json();
                $qr_codeWPP = $responseBody['qrcode'];
                Log::channel('jobs')->info('Gerado QRCode');
                return $qr_codeWPP;
            } else {
                Log::channel('jobs')->error("Erro function startSessionWPP: " . $response->status());
                return "Erro function startSessionWPP: " . $response->status();
            }
        } catch (Exception $e) {
            Log::channel('jobs')->error("Erro function startSessionWPP: " . $e->getMessage());
            return "Erro function startSessionWPP: " . $e->getMessage();
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
                $responseBody = $response->json();
                $wpp_status = $responseBody['status'];
                return $wpp_status;
            } else {
                Log::channel('jobs')->error("Erro function statusWPP: " . $response->status());
                return "Erro function statusWPP: " . $response->status();
            }
        } catch (Exception $e) {
            Log::channel('jobs')->error("Erro function statusWPP: " . $e->getMessage());
            return "Erro function statusWPP: " . $e->getMessage();
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
            $url = "{$wpp_server}api/{$wpp_session}/{$wpp_secure_token}/generate-token";
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url);
            if ($response->successful()) {
                $responseBody = $response->json();
                $wpp_bearer = 'Bearer ' . $responseBody['token'];
                return $wpp_bearer;
            } else {
                Log::channel('jobs')->error("Erro function gerar_bearerWPP: " . $url . $response->status());
                return "Erro function gerar_bearerWPP: " . $response->status();
            }
        } catch (Exception $e) {
            Log::channel('jobs')->error("Erro function gerar_bearerWPP: " . $e->getMessage());
            return "Erro function gerar_bearerWPP: " . $e->getMessage();
        }
    }
}
