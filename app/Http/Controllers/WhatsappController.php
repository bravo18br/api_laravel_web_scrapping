<?php

namespace App\Http\Controllers;

use App\Models\Alvo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    public function conexaoWPP(): JsonResponse
    {
        try {
            $tokenWPP = $this->generateWPPToken();
            return response()->json(['token' => $tokenWPP], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function geraAlertaWPP(Alvo $alvo, $mensagem)
    {
        try {
            //TODO: Implement alert logic here
        } catch (Exception $e) {
            Log::channel('jobs')->info('Alerta WPP ' . $alvo->nome . ' gerou erro: ' . $e->getMessage());
        }
        return;
    }

    private function generateWPPToken()
    {
        $wpp_server = env('MY_WPP_SERVER');
        $wpp_token = env('MY_WPP_SECURE_TOKEN');
        $wpp_session = env('MY_WPP_SESSION');
        $url = "{$wpp_server}/api/{$wpp_session}/{$wpp_token}/generate-token";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception("Erro na requisição cURL: $error_msg");
        }
        curl_close($ch);
        $responseData = json_decode($response, true);
        if (isset($responseData['token'])) {
            return $responseData['token'];
        } else {
            throw new Exception("Resposta inválida da API: Token não encontrado.");
        }
    }

    private function sendStartSessionRequest($token)
    {
        $wpp_server = env('MY_WPP_SERVER');
        $wpp_session = env('MY_WPP_SESSION');
        $url = "{$wpp_server}/api/{$wpp_session}/start-session";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception("Erro na requisição cURL: $error_msg");
        }
        curl_close($ch);
        return json_decode($response, true);
    }

    public function startSession()
    {
        $token = $this->generateWPPToken();
        $responseData = $this->sendStartSessionRequest($token);
        if (isset($responseData['status']) && $responseData['status'] == 'Success') {
            return $responseData;
        } else {
            throw new Exception("Resposta inválida da API: Sessão não pôde ser iniciada.");
        }
    }

    public function getQRCode()
    {
        $token = $this->generateWPPToken();
        $responseData = $this->sendStartSessionRequest($token);
        if (isset($responseData['qrCode'])) {
            return $responseData['qrCode'];
        } else {
            throw new Exception("Resposta inválida da API: QR Code não encontrado.");
        }
    }
}
