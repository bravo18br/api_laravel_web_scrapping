<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WppController extends Controller
{
    public function __construct()
    {
        //
    }

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

    public function getQRCodePNG()
    {
        if ($this->geraQRCodePNG()) {
            return response()->json('QRCode em PNG não gerado, falha no servidor.', 500);
        } else {
            return response()->download($this->geraQRCodePNG(), 200);
        }
    }

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

    public function getQRCodeBIN()
    {
        $geraQRCodeWPP = $this->geraQRCodeWPP();
        if (isset($geraQRCodeWPP['ERRO'])) {
            $retorno = [
                'qrcode' => $geraQRCodeWPP['ERRO'],
                'status' => 'Falha'
            ];
            return response()->json($retorno, 500);
        } else {
            $retorno = [
                'qrcode' => $this->geraQRCodeWPP(),
                'status' => 'Sucesso'
            ];
            return response()->json($retorno, 200);
        }
    }

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

    public function getStatusWPP()
    {
        return response()->json($this->statusWPP());
    }

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
