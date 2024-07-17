<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="Documentação Monitora Sites API",
 *     version="1.0.0",
 *     description="Documentação API para o Monitora Sites."
 * )
 */
class WppController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Envia uma mensagem via WhatsApp se o status estiver "Connected".
     *
     * @OA\Post(
     *     path="/api/mensagem-whats",
     *     summary="Enviar mensagem via WhatsApp",
     *     description="Envia uma mensagem via WhatsApp se o status estiver 'Connected'.",
     *     @OA\Parameter(
     *         name="alvo",
     *         in="query",
     *         description="Objeto Alvo contendo as informações do site",
     *         required=true,
     *         @OA\Schema(type="object")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensagem enviada com sucesso",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao enviar a mensagem",
     *         @OA\JsonContent(type="string")
     *     )
     * )
     *
     * @param object $alvo Objeto Alvo contendo as informações do site.
     * @return void|string
     */
    public function mensagemWhats($alvo)
    {
        $statusWPP = $this->statusWPP();
        switch ($statusWPP['status']) {
            case 'CLOSED':
            case 'QRCODE':
            case 'INITIALIZING':
                return $this->geraQRCodePNG();
            case 'CONNECTED':
                return $this->sendMessageWPP('Site ' . $alvo->nome . ' alterado.' . PHP_EOL . 'URL: ' . $alvo->url);
            default:
                $mensagem = $statusWPP['ERRO'];
                Log::channel('jobs')->error($mensagem);
                return $mensagem;
        }
    }

    /**
     * Envia uma mensagem via WhatsApp.
     *
     * @OA\Post(
     *     path="/api/send-message",
     *     summary="Enviar mensagem via WhatsApp",
     *     description="Envia uma mensagem via WhatsApp.",
     *     @OA\Parameter(
     *         name="mensagem",
     *         in="query",
     *         description="A mensagem a ser enviada.",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensagem enviada com sucesso",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao enviar a mensagem",
     *         @OA\JsonContent(type="string")
     *     )
     * )
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
     * Salva o QR code base64 como PNG.
     *
     * @OA\Post(
     *     path="/api/gera-qrcode-png",
     *     summary="Gerar QR code em PNG",
     *     description="Gera e salva o QR code base64 como PNG.",
     *     @OA\Response(
     *         response=200,
     *         description="QR code gerado e salvo com sucesso",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao gerar ou salvar o QR code",
     *         @OA\JsonContent(type="string")
     *     )
     * )
     *
     * @return string|null
     */
    public function geraQRCodePNG()
    {
        try {
            $base64Data = $this->geraQRCodeWPP();
            if (isset($base64Data['ERRO'])) {
                Log::channel('jobs')->error('Error generating QR code: ' . $base64Data['ERRO']);
                return null;
            }

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
            return $filePath;
        } catch (Exception $e) {
            Log::channel('jobs')->error('Error saving QR code image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Inicia uma nova sessão do WhatsApp e retorna o QRCode.
     *
     * @OA\Post(
     *     path="/api/gera-qrcode-wpp",
     *     summary="Gerar QR code do WhatsApp",
     *     description="Inicia uma nova sessão do WhatsApp e retorna o QR code.",
     *     @OA\Response(
     *         response=200,
     *         description="QR code gerado com sucesso",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao gerar o QR code",
     *         @OA\JsonContent(type="string")
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/status-wpp",
     *     summary="Verificar status do WhatsApp",
     *     description="Verifica o status da conexão do WPP.",
     *     @OA\Response(
     *         response=200,
     *         description="Status obtido com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao obter o status",
     *         @OA\JsonContent(type="string")
     *     )
     * )
     *
     * @return array O status da conexão ou uma mensagem de erro.
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
                return $response->json();
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
     * @OA\Post(
     *     path="/api/gera-bearer-wpp",
     *     summary="Gerar token Bearer do WhatsApp",
     *     description="Gera e retorna o token Bearer para a sessão do WPP.",
     *     @OA\Response(
     *         response=200,
     *         description="Token Bearer gerado com sucesso",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao gerar o token Bearer",
     *         @OA\JsonContent(type="string")
     *     )
     * )
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
                return 'Bearer ' . $responseBody['token'];
            } else {
                $erro = "Erro function gerar_bearerWPP: " . $response->status();
                Log::channel('jobs')->error($erro . " | Corpo: " . $response->body());
                return $erro;
            }
        } catch (Exception $e) {
            $erro = "Erro function gerar_bearerWPP: " . $e->getMessage();
            Log::channel('jobs')->error($erro);
            return $erro;
        }
    }
}
