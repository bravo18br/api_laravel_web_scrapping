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
            $emailData = [
                'mensagem' => 'Site ' . $this->alvo->nome . ' alterado.' . PHP_EOL . 'URL: ' . $this->alvo->url,
                'titulo' => 'Monitora Sites - ' . $this->alvo->nome . ' alterado.',
                'destino' => 'bravo18br@gmail.com',
                'layout' => 'emails.mensagem',
                'qrcode' => null,
                'qrcodePath' => null,
            ];
            if ($conteudoOriginal != $conteudoAtual) {
                Log::channel('jobs')->info($this->alvo->nome . ' Alterado.');
                $wppController = App::make(WppController::class);
                $wppStatus = $wppController->mensagemWhats($this->alvo);
                if (isset($wppStatus['qrcode'])) {
                    // Converter o QR code base64 para uma imagem usando GD
                    $qrcodeBase64 = $wppStatus['qrcode'];
                    $qrCodePath = storage_path('app/public/qrcode.png');
                    base64ToPng($qrcodeBase64, $qrCodePath);
                    // Adiciona a imagem do QR code ao email
                    $emailData['qrcodePath'] = $qrCodePath;
                }
                // Se houve erro no envio da mensagem, incluir o QR code
                if ($wppStatus != 'SUCESSO') {
                    $emailData['qrcode'] = $wppStatus;
                }
                Mail::send(new GMailController($emailData));
                Log::channel('jobs')->info('Acionado - Mail::send(new GMailController($emailData));');
            } else {
                Log::channel('jobs')->info($this->alvo->nome . ' Permanece igual.');
            }
        } catch (Exception $e) {
            $erro = $this->alvo->nome . ' ERRO: ' . $e->getMessage();
            Log::channel('jobs')->error($erro);
            $emailData = [
                'mensagem' => $erro,
                'titulo' => 'ERRO - Monitora Sites',
                'destino' => 'bravo18br@gmail.com',
                'layout' => 'emails.mensagem',
                'qrcode' => $wppStatus ?? null,
                'qrcodePath' => null,
            ];
            if (isset($wppStatus['qrcode'])) {
                // Converter o QR code base64 para uma imagem usando GD
                $qrcodeBase64 = $wppStatus['qrcode'];
                $qrCodePath = storage_path('app/public/qrcode.png');
                base64ToPng($qrcodeBase64, $qrCodePath);
                // Adiciona a imagem do QR code ao email
                $emailData['qrcodePath'] = $qrCodePath;
            }
            Mail::send(new GMailController($emailData));
        }
    }
}

function base64ToPng($base64String, $outputFile)
{
    // Verificar se o diretório existe, caso contrário, criar
    $directory = dirname($outputFile);
    if (!file_exists($directory)) {
        mkdir($directory, 0775, true);
    }
    $ifp = fopen($outputFile, 'wb'); 
    $data = explode(',', $base64String);
    fwrite($ifp, base64_decode($data[1])); 
    fclose($ifp); 
    return $outputFile; 
}

