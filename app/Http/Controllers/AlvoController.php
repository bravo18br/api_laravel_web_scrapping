<?php

namespace App\Http\Controllers;

use App\Models\Alvo;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;

class AlvoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Alvo $alvo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Alvo $alvo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Alvo $alvo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Alvo $alvo)
    {
        //
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
}
