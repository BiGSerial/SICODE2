<?php

namespace App\Http\Controllers;

use App\PDF\Gerador\{DesignPdf, SicodePdf};

class TesteController extends Controller
{
    public function pdf()
    {

        $teste = new SicodePdf();
        $teste->setName_client('Alexandre');
        $teste->setNote('40012020202');
        $teste->setOrdem(['20023221234']);


        $pdf = new DesignPdf($teste);

        $pdf->useEmpreiteira();

    }

    public function teste()
    {

    }

    public function page()
    {
        return view('testes.page');
    }
}
