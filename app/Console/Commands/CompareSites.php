<?php

namespace App\Console\Commands;

use App\Jobs\ComparaConteudoJob;
use App\Models\Alvo;
use Illuminate\Console\Command;

class CompareSites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compare:sites';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compara o conteúdo salvo do site com o conteúdo atual.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $alvos = Alvo::all();
        foreach ($alvos as $alvo) {
            ComparaConteudoJob::dispatch($alvo);
        }
        return 0;
    }
}
