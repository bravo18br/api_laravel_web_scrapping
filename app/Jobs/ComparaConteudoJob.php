<?php

namespace App\Jobs;

use App\Http\Controllers\AlvoController;
use App\Http\Controllers\WppController;
use App\Models\Alvo;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComparaConteudoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Alvo $alvo;
    protected $wppController;

    /**
     * Create a new job instance.
     *
     * @param Alvo $alvo
     * @param WppController $wppController
     */
    public function __construct(Alvo $alvo, WppController $wppController)
    {
        $this->alvo = $alvo;
        $this->wppController = $wppController;
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
                $mensagem = $this->alvo->nome . ' Alterado.';
                Log::channel('jobs')->info($mensagem);
                $this->wppController->mensagemWhats($mensagem);
            } else {
                Log::channel('jobs')->info($this->alvo->nome . ' Permanece igual.');
            }
        } catch (Exception $e) {
            $mensagem = $this->alvo->nome . ' ERRO: ' . $e->getMessage();
            Log::channel('jobs')->error($mensagem);
            $this->wppController->mensagemWhats($mensagem);
        }
        sleep(1);
    }
}
