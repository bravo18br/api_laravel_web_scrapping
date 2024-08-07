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

    public function __construct(Alvo $alvo)
    {
        $this->alvo = $alvo;
    }

    public function handle(): void
    {
        try {
            $alvoController = new AlvoController;
            if ($this->alvo['alerta'] < 3) {
                if($this->alvo->conteudo==null){
                    $this->alvo->conteudo = $alvoController->geraConteudo($this->alvo);
                    $this->alvo->save();
                }
                $conteudoOriginal = $this->alvo->conteudo;
                $conteudoAtual = $alvoController->geraConteudo($this->alvo);
                if ($conteudoOriginal != $conteudoAtual) {
                    Log::channel('integrado')->info($this->alvo->nome . ' Alterado.');
                    try {
                        $wppController = App::make(WppController::class);
                        $wppController->mensagemWhats($this->alvo);
                    } catch (Exception $e) {
                        Log::channel('integrado')->error('ERRO - Notificação whats: ' . $e->getMessage());
                    }
                    try {
                        $wppController = App::make(WppController::class);
                        $wppStatus = $wppController->statusWPP();
                        $emailData = $this->alvo->toArray();
                        $emailData['destino'] = 'bravo18br@gmail.com';
                        $emailData['layout'] = 'emails.mensagem';
                        $emailData['statusWPP'] = $wppStatus['status'];
                        $emailData['titulo'] = 'Site ' . $emailData['nome'] . ' alterado';
                        if ($wppStatus['status'] == 'CLOSED' || $wppStatus['status'] == 'QRCODE') {
                            $wppQRCodePNG = $wppController->geraQRCodePNG();
                            $emailData['qrcodepath'] = $wppQRCodePNG;
                        }
                        Mail::to($emailData['destino'])->send(new GMailController($emailData));
                        Log::channel('integrado')->info($emailData['nome'] . ' Email enviado.');
                    } catch (Exception $e) {
                        Log::channel('integrado')->error('ERRO - Email não enviado: ' . $e->getMessage());
                    }
                    $this->alvo['alerta'] = $this->alvo['alerta'] + 1;
                    $this->alvo->save();
                } else {
                    Log::channel('integrado')->info($this->alvo->nome . ' Permanece igual.');
                }
            } else {
                Log::channel('integrado')->info('Enviado ' . $this->alvo['alerta'] . ' alertas. Conteúdo do alvo atualizado.');
                $this->alvo['conteudo'] = $alvoController->geraConteudo($this->alvo);
                $this->alvo['alerta'] = 0;
                $this->alvo->save();
            }
        } catch (Exception $e) {
            Log::channel('integrado')->error('ERRO - ComparaConteudoJob: ' . $e->getMessage());
        }
        sleep(1);
    }
}
