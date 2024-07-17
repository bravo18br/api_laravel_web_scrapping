<?php

namespace App\Http\Controllers;

use App\Models\Alvo;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Alvos",
 *     description="Gerenciamento de Alvos"
 * )
 */
class AlvoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/alvos",
     *     summary="Lista todos os alvos",
     *     tags={"Alvos"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de alvos",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Alvo"))
     *     )
     * )
     */
    public function index()
    {
        $alvos = Alvo::all();
        return response()->json($alvos);
    }

    /**
     * @OA\Post(
     *     path="/alvos",
     *     summary="Cria um novo alvo",
     *     tags={"Alvos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Alvo")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Alvo criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Alvo")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dados inválidos"
     *     )
     * )
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
     * @OA\Get(
     *     path="/alvos/{id}",
     *     summary="Exibe um alvo específico",
     *     tags={"Alvos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do alvo",
     *         @OA\JsonContent(ref="#/components/schemas/Alvo")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alvo não encontrado"
     *     )
     * )
     */
    public function show(Alvo $alvo)
    {
        return response()->json($alvo);
    }

    /**
     * Mostra o formulário para editar o recurso especificado.
     * @OA\Get(
     *     path="/alvos/{id}/edit",
     *     summary="Mostra o formulário para editar um alvo",
     *     tags={"Alvos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Formulário de edição",
     *         @OA\JsonContent(ref="#/components/schemas/Alvo")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alvo não encontrado"
     *     )
     * )
     */
    public function edit(Alvo $alvo)
    {
        // Não utilizado em contexto de API
    }

    /**
     * @OA\Put(
     *     path="/alvos/{id}",
     *     summary="Atualiza um alvo específico",
     *     tags={"Alvos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Alvo")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alvo atualizado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Alvo")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dados inválidos"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alvo não encontrado"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/alvos/{id}",
     *     summary="Remove um alvo específico",
     *     tags={"Alvos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Alvo removido com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alvo não encontrado"
     *     )
     * )
     */
    public function destroy(Alvo $alvo)
    {
        $alvo->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/alvos/{id}/conteudo",
     *     summary="Gera o conteúdo do alvo a partir da URL e do elemento especificado",
     *     tags={"Alvos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conteúdo gerado",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Alvo não encontrado"
     *     )
     * )
     */
    public function geraConteudo(Alvo $alvo)
    {
        libxml_use_internal_errors(true);
        try {
            $html = file_get_contents($alvo->url);
        } catch (Exception $e) {
            $html = '<main>Pagina inacessivel</</main>';
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
     * @OA\Post(
     *     path="/alvos/atualiza-conteudo-original",
     *     summary="Atualiza o conteúdo original de todos os alvos",
     *     tags={"Alvos"},
     *     @OA\Response(
     *         response=200,
     *         description="Conteúdo original atualizado com sucesso",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao atualizar o conteúdo original"
     *     )
     * )
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

/**
 * @OA\Schema(
 *     schema="Alvo",
 *     type="object",
 *     required={"nome", "url", "elemento"},
 *     @OA\Property(property="id", type="integer", readOnly=true),
 *     @OA\Property(property="nome", type="string"),
 *     @OA\Property(property="url", type="string", format="url"),
 *     @OA\Property(property="elemento", type="string"),
 *     @OA\Property(property="conteudo", type="string", nullable=true)
 * )
 */

