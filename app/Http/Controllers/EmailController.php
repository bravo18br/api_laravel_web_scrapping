<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    public function sendMessageEmail($mensagem)
    {
        // TODO enviar email 
        Log::channel('jobs')->info('sendMessageEmail: ' . $mensagem);
    }
}
