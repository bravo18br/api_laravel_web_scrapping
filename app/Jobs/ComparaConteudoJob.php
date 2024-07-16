<?php

namespace App\Jobs;

use App\Http\Controllers\AlvoController;
use App\Http\Controllers\WppController;
use App\Mail\GMailController;
use App\Models\Alvo;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class ComparaConteudoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Alvo $alvo;

    /**
     * Create a new job instance.
     *
     * @param Alvo $alvo
     */
    public function __construct(Alvo $alvo)
    {
        $this->alvo = $alvo;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $alvoController = new AlvoController;
            $conteudoOriginal = $this->alvo->conteudo;
            $conteudoAtual = $alvoController->geraConteudo($this->alvo);
            if ($conteudoOriginal != $conteudoAtual) {
                Log::channel('jobs')->info($this->alvo->nome . ' Alterado.');
                try {
                    Log::channel('jobs')->info('Rotina enviar notificação WPP Connect iniciada.');
                    // TODO rotina de envio de whats
                } catch (Exception $e) {
                    Log::channel('jobs')->error('ERRO - Rotina enviar notificação WPP Connect: ' . $e->getMessage());
                }
                try {
                    Log::channel('jobs')->info('Rotina enviar email iniciada.');
                    $wppController = App::make(WppController::class);
                    $wppStatus = $wppController->statusWPP();
                    $emailData = $this->alvo->toArray();
                    $emailData['destino'] = 'bravo18br@gmail.com';
                    $emailData['layout'] = 'emails.mensagem';
                    $emailData['statusWPP'] = $wppStatus;
                    $emailData['titulo'] = 'Site ' . $emailData['nome'] . ' alterado';
                    if ($wppStatus == 'CLOSED' || $wppStatus == 'QRCODE') {
                        $wppQRCodePNG = $wppController->geraQRCodePNG();
                        $emailData['qrcodepath'] = $wppQRCodePNG;
                    }
                    // Transform arrays in strings if necessary
                    foreach ($emailData as $key => $value) {
                        if (is_array($value)) {
                            $emailData[$key] = json_encode($value);
                        }
                    }
                    Mail::to($emailData['destino'])->send(new GMailController($emailData));
                } catch (Exception $e) {
                    Log::channel('jobs')->error('ERRO - Rotina enviar email: ' . $e->getMessage());
                }
            } else {
                Log::channel('jobs')->info($this->alvo->nome . ' Permanece igual.');
            }
        } catch (Exception $e) {
            Log::channel('jobs')->error('ERRO - ComparaConteudoJob: ' . $e->getMessage());
        }
    }
}
