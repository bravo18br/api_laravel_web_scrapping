<?php

namespace App\Jobs;

use App\Http\Controllers\AlvoController;
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

    /**
     * Create a new job instance.
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
                Log::channel('jobs')->info($this->alvo->url . ' Alterado.');
                // CRIAR UM ALARME VIA WHATS
            } else {
                Log::channel('jobs')->info($this->alvo->url . ' Permanece igual.');
            }
        } catch (Exception $e) {
            Log::channel('jobs')->error($this->alvo->url . ' ERRO: ' . $e->getMessage());
        }
    }
}
