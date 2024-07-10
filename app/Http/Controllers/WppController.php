<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WppController extends Controller
{
    public function api_docs(Request $request)
    {
        $url = "http://wppconnect:21465/api-docs";
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get($url);
        return response($response->body(), $response->status())->withHeaders($response->headers());
    }
    public function gerar_token(Request $request)
    {
        $session = env('MY_WPP_SESSION');
        $token = env('MY_WPP_SECURE_TOKEN');
        $url = "http://wppconnect:21465/api/{$session}/{$token}/generate-token";
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get($url);
        return response($response->body(), $response->status())->withHeaders($response->headers());
    }
}
