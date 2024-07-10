<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WppProxyController extends Controller
{
    public function proxy(Request $request)
    {
        $path = $request->path();
        $query = $request->getQueryString();
        $url = "http://wppconnect:21465/$path" . ($query ? "?$query" : '');
dd ($url);
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->send($request->method(), $url, [
            'body' => $request->getContent()
        ]);

        return response($response->body(), $response->status())->withHeaders($response->headers());
    }
}
