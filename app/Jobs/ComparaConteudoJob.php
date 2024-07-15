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
                $wppController = App::make(WppController::class);
                $wppStatus = $wppController->mensagemWhats($this->alvo);
                $email = [
                    'mensagem' => 'Site ' . $this->alvo->nome . ' alterado.' . PHP_EOL . 'URL: ' . $this->alvo->url,
                    'titulo' => 'Monitora Sites - ' . $this->alvo->nome . ' alterado.',
                    'destino' => 'bravo18br@gmail.com',
                    'layout' => 'emails.mensagem',
                    'qrcode' => $wppStatus,
                ];
                Mail::send(new GMailController($email));
            } else {
                Log::channel('jobs')->info($this->alvo->nome . ' Permanece igual.');
            }
        } catch (Exception $e) {
            $erro = $this->alvo->nome . ' ERRO: ' . $e->getMessage();
            Log::channel('jobs')->error($erro);
            // $wppController = App::make(WppController::class);
            // $wppStatus = $wppController->mensagemWhats($this->alvo);
            $email = [
                'mensagem' => $erro,
                'titulo' => 'ERRO - Monitora Sites',
                'destino' => 'bravo18br@gmail.com',
                'layout' => 'emails.mensagem',
                'qrcode' => $wppStatus,
            ];
            Mail::send(new GMailController($email));
        }
    }
}
