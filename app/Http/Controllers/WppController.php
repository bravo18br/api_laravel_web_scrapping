<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WppController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Envia uma mensagem via WhatsApp se o status estiver "Connected".
     *
     * @param string $mensagem A mensagem a ser enviada.
     * @return void|string
     */
    public function mensagemWhats($alvo)
    {
        $statusWPP = $this->statusWPP();
        switch ($statusWPP['status']) {
            case 'CLOSED':
            case 'QRCODE':
                return $this->geraQRCodePNG();
                break;
            case 'CONNECTED':
                return $this->sendMessageWPP('Site ' . $alvo->nome . ' alterado.' . PHP_EOL . 'URL: ' . $alvo->url);
                break;
            default:
                $mensagem = $statusWPP['ERRO'];
                Log::channel('jobs')->error($mensagem);
                return $mensagem;
                break;
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
            ])->withBody(json_encode($body), 'application/json')->post($url);
            if ($response->successful()) {
                Log::channel('jobs')->info($mensagem . ' - enviada');
                return 'SUCESSO';
            } else {
                $erro = "Erro function sendMessageWPP: " . $response->status();
                Log::channel('jobs')->error($erro);
                return $erro;
            }
        } catch (Exception $e) {
            $erro = "Erro function sendMessageWPP: " . $e->getMessage();
            Log::channel('jobs')->error($erro);
            return $erro;
        }
    }

    /**
     * Save base64 QR code as PNG.
     *
     * @param string $base64Data
     * @return string|null
     */
    public function geraQRCodePNG()
    {
        try {
            $base64Data  = $this->geraQRCodeWPP();
            $base64Image = str_replace('data:image/png;base64,', '', $base64Data);
            $base64Image = str_replace(' ', '+', $base64Image);
            $imageData = base64_decode($base64Image);

            $filePath = storage_path('app/public/qrcode.png');
            $directory = dirname($filePath);

            if (!file_exists($directory)) {
                mkdir($directory, 0775, true);
            }

            if (file_put_contents($filePath, $imageData) === false) {
                Log::channel('jobs')->error('Failed to write QR code image to ' . $filePath);
                return null;
            }

            Log::channel('jobs')->info('QR code image saved to ' . $filePath);
            return $filePath;
        } catch (Exception $e) {
            Log::channel('jobs')->error('Error saving QR code image: ' . $e->getMessage());
            return null;
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
                "webhook" => "",
                "waitQrCode" => true,
            ];
            $response = Http::withHeaders([
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
                'Authorization' => $wpp_bearer,
            ])->withBody(json_encode($body), 'application/json')->post($url);
            if ($response->successful()) {
                $responseJson = $response->json();
                return $responseJson['qrcode'];
            } else {
                return ['ERRO' => "Erro function geraQRCodeWPP: " . $response->status()];
            }
        } catch (Exception $e) {
            return ['ERRO' => "Erro function geraQRCodeWPP: " . $e->getMessage()];
        }
    }

    /**
     * Verifica o status da conexão do WPP.
     *
     * @return string O status da conexão ou uma mensagem de erro.
     */
    public function statusWPP()
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
                return $responseJson['status'];
            } else {
                return "Erro function statusWPP: " . $response->status();
            }
        } catch (Exception $e) {
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
