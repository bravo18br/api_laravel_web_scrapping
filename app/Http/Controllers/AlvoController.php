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
        libxml_use_internal_errors(true);
        try {
            $html = file_get_contents($alvo->url);
        } catch (Exception $e) {
            $html = '<main>Pagina inacessivel</main>';
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
        return $html_filtrado ? $domDocument->saveHTML($html_filtrado) : null;
    }

    public function atualizaConteudoOriginal()
    {
        try {
            $alvos = Alvo::all();
            foreach ($alvos as $alvo) {
                $alvo->conteudo = $this->geraConteudo($alvo);
                $alvo->save();
            }
            $message = 'Conteúdo original atualizado';
            Log::channel('integrado')->info($message);
            dd(storage_path('logs/integrado.log'));
            return response()->json($message, 200);
        } catch (Exception $e) {
            $erro = 'ERRO - atualizaConteudoOriginal: ' . $e->getMessage();
            Log::channel('integrado')->error($erro);
            return response()->json($erro, 500);
        }
    }
}
