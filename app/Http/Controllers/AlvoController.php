<?php

namespace App\Http\Controllers;

use App\Models\Alvo;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AlvoController extends Controller
{
    /**
     * Exibe uma lista de recursos.
     */
    public function index()
    {
        $alvos = Alvo::all();
        return response()->json($alvos);
    }

    /**
     * Armazena um novo recurso no armazenamento.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'url' => 'required|url',
            'elemento' => 'required|string',
        ]);

        return $this->createAlvo($request->all());
    }

    /**
     * Método auxiliar para criar um novo recurso.
     */
    public function createAlvo(array $data)
    {
        $alvo = Alvo::create($data);
        $alvo->conteudo = $this->geraConteudo($alvo);
        $alvo->save();

        return $alvo;
    }

    /**
     * Exibe o recurso especificado.
     */
    public function show(Alvo $alvo)
    {
        return response()->json($alvo);
    }

    /**
     * Mostra o formulário para editar o recurso especificado.
     */
    public function edit(Alvo $alvo)
    {
        // Não utilizado em contexto de API
    }

    /**
     * Atualiza o recurso especificado no armazenamento.
     */
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

    /**
     * Remove o recurso especificado do armazenamento.
     */
    public function destroy(Alvo $alvo)
    {
        $alvo->delete();
        return response()->json(null, 204);
    }

    /**
     * Gera o conteúdo do alvo a partir da URL e do elemento especificado.
     */
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

    /**
     * Atualiza o conteúdo original de todos os alvos.
     */
    public function atualizaConteudoOriginal()
    {
        try {
            $alvos = Alvo::all();
            foreach ($alvos as $alvo) {
                $alvo->conteudo = $this->geraConteudo($alvo);
                $alvo->save();
            }
            $message = 'Conteúdo original atualizado';
            Log::channel('jobs')->info($message);
            return response()->json($message, 200);
        } catch (Exception $e) {
            $erro = 'ERRO - atualizaConteudoOriginal: ' . $e->getMessage();
            Log::channel('jobs')->error($erro);
            return response()->json($erro, 200);
        }
    }
}
