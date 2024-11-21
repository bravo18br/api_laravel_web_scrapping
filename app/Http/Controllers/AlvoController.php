<?php 

namespace App\Http\Controllers;

use App\Models\Alvo;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AlvoController extends Controller
{
    public function index()
    {
        $alvos = Alvo::all();
        return response()->json($alvos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'url' => 'required|url',
            'elemento' => 'required|string',
        ]);

        return $this->createAlvo($request->all());
    }

    public function createAlvo(array $data)
    {
        $alvo = Alvo::create($data);
        $alvo->conteudo = $this->geraConteudo($alvo);
        $alvo->save();

        return $alvo;
    }

    public function show(Alvo $alvo)
    {
        return response()->json($alvo);
    }

    public function edit(Alvo $alvo)
    {
        // Não utilizado em contexto de API
    }

    public function update(Request $request, Alvo $alvo)
    {
        $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|url',
            'elemento' => 'sometimes|required|string',
        ]);

        $alvo->update($request->all());
        $alvo->conteudo = $this->geraConteudo($alvo);
        $alvo->save();

        return response()->json($alvo);
    }

    public function destroy(Alvo $alvo)
    {
        $alvo->delete();
        return response()->json(null, 204);
    }

    public function geraConteudo(Alvo $alvo)
    {
        $message = ['text'=>'Conteúdo atualizado - '.$alvo->nome,'tipo'=>'info'];
        libxml_use_internal_errors(true);
        try {
            $html = file_get_contents($alvo->url);
        } catch (Exception $e) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $alvo->url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36');
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'Cache-Control: no-cache',
                ]);
                curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');
                $html = curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new Exception('Erro ao acessar URL: ' . curl_error($ch));
                    $message = ['text'=>'ERRO - ' . curl_error($ch) . ' - '.$alvo->nome,'tipo'=>'error'];
                }
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode >= 400) {
                    throw new Exception('HTTP Code ' . $httpCode);
                    $message = ['text'=>'ERRO - ' . $httpCode . ' - '.$alvo->nome,'tipo'=>'error'];
                }
                curl_close($ch);
            } catch (Exception $e) {
                $html = '<main>Pagina não encontrada.</main>';
                $message = ['text'=>'ERRO - '. $e->getMessage() . ' - '.$alvo->nome,'tipo'=>'error'];
            }
        }

        $domDocument = new DOMDocument();
        $domDocument->loadHTML($html);
        libxml_clear_errors();
        $html_filtrado = null;
        $elemento = $domDocument->getElementById($alvo->elemento);
        if ($elemento) {
            $html_filtrado = $elemento;
        } else {
            $elementos = $domDocument->getElementsByTagName($alvo->elemento);
            if ($elementos->length > 0) {
                $html_filtrado = $elementos->item(0);
            } else {
                $elementosMain = $domDocument->getElementsByTagName('main');
                if ($elementosMain->length > 0) {
                    $html_filtrado = $elementosMain->item(0);
                } else {
                    $elementosHtml = $domDocument->getElementsByTagName('html');
                    $html_filtrado = $elementosHtml->item(0);
                }
            }
        }
        $retorno = [
            'html'=>$html_filtrado ? $domDocument->saveHTML($html_filtrado) : null,
            'message'=>$message
        ];
        return $retorno;
    }

    public function atualizaConteudoOriginal()
    {
        try {
            $alvos = Alvo::all();
            foreach ($alvos as $alvo) {
                $retorno = $this->geraConteudo($alvo);
                $alvo->conteudo = $retorno['html'];
                $alvo->save();
                if($retorno['message']['tipo'] == 'error'){
                    Log::channel('integrado')->error($retorno['message']['text']);
                }else{
                    Log::channel('integrado')->info($retorno['message']['text']);
                }  
            }
            return response()->json('OK', 200);
        } catch (Exception $e) {
            $erro = 'ERRO - atualizaConteudoOriginal: ' . $e->getMessage();
            Log::channel('integrado')->error($erro);
            return response()->json($erro, 500);
        }
    }
}