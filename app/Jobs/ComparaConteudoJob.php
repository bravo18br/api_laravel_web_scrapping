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

/**
 * @OA\Tag(
 *     name="Jobs",
 *     description="Gerenciamento de Jobs"
 * )
 */
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
     * @OA\Post(
     *     path="/jobs/compara-conteudo",
     *     summary="Compara o conteúdo de um alvo e envia notificações se houver alterações",
     *     tags={"Jobs"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Alvo")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job executado com sucesso",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao executar o job"
     *     )
     * )
     */
    public function handle(): void
    {
        try {
            $alvoController = new AlvoController;
            if ($this->alvo['alerta'] < 3) {
                $conteudoOriginal = $this->alvo->conteudo;
                $conteudoAtual = $alvoController->geraConteudo($this->alvo);
                if ($conteudoOriginal != $conteudoAtual) {
                    Log::channel('jobs')->info($this->alvo->nome . ' Alterado.');
                    try {
                        $wppController = App::make(WppController::class);
                        $wppController->mensagemWhats($this->alvo);
                    } catch (Exception $e) {
                        Log::channel('jobs')->error('ERRO - Notificação whats: ' . $e->getMessage());
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
                        Log::channel('jobs')->info($emailData['nome'] . ' Email enviado.');
                    } catch (Exception $e) {
                        Log::channel('jobs')->error('ERRO - Email não enviado: ' . $e->getMessage());
                    }
                    $this->alvo['alerta'] = $this->alvo['alerta'] + 1;
                    $this->alvo->save();
                } else {
                    Log::channel('jobs')->info($this->alvo->nome . ' Permanece igual.');
                }
            } else {
                Log::channel('jobs')->info('Enviado ' . $this->alvo['alerta'] . ' alertas. Conteúdo do alvo atualizado.');
                $this->alvo['conteudo'] = $alvoController->geraConteudo($this->alvo);
                $this->alvo['alerta'] = 0;
                $this->alvo->save();
            }
        } catch (Exception $e) {
            Log::channel('jobs')->error('ERRO - ComparaConteudoJob: ' . $e->getMessage());
        }
        sleep(1);
    }
}

/**
 * @OA\Schema(
 *     schema="Alvo",
 *     type="object",
 *     required={"nome", "url", "elemento"},
 *     @OA\Property(property="id", type="integer", readOnly=true),
 *     @OA\Property(property="nome", type="string"),
 *     @OA\Property(property="url", type="string", format="url"),
 *     @OA\Property(property="elemento", type="string"),
 *     @OA\Property(property="conteudo", type="string", nullable=true),
 *     @OA\Property(property="alerta", type="integer", nullable=true)
 * )
 */
