<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\PDF\Gerador\DesignPdf;
use App\PDF\Gerador\SicodePdf;

class TesteController extends Controller
{
    public function pdf()
    {


        $teste = new SicodePdf();
        $teste->setName_client('Alexandre');
        $teste->setOrdem('40012020202');

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
