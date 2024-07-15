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
use Illuminate\Support\Facades\App;

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
            $wppController = App::make(WppController::class);
            $conteudoOriginal = $this->alvo->conteudo;
            $conteudoAtual = $alvoController->geraConteudo($this->alvo);
            if ($conteudoOriginal != $conteudoAtual) {
                Log::channel('jobs')->info($this->alvo->nome . ' Alterado.');
                $wppController->mensagemWhats($this->alvo);
            } else {
                Log::channel('jobs')->info($this->alvo->nome . ' Permanece igual.');
            }
        } catch (Exception $e) {
            Log::channel('jobs')->error($this->alvo->nome . ' ERRO: ' . $e->getMessage());
            $wppController = App::make(WppController::class);
            $wppController->mensagemWhats($this->alvo);
        }
    }
}
