<?php

namespace App\Jobs;

use App\Http\Controllers\AlvoController;
use App\Models\Alvo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComparaConteudoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Alvo $alvo)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle($alvo): void
    {
        $alvoController = new AlvoController;
        $conteudoOriginal = $alvo->conteudo;
        $conteudoAtual = $alvoController->geraConteudo($alvo);
        if ($conteudoOriginal != $conteudoAtual) {
            Log::info(now() . ' - ' . $alvo->url . ' Alterado.');
        } else {
            Log::info(now() . ' - ' . $alvo->url . ' Permanece igual.');
        }
    }
}
