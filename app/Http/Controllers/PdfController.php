<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;

use App\Models\Viability;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;

class PdfController extends Controller
{
    public $dados;
    public $viability;

    public function checkList($id = null)
    {
        $this->dados = (object)[
            'note' => null,
            'lexp' => null,
            'company' => null,
            'prazo' => null,
        ];

        if ($id && $this->viability = Viability::with('Company', 'Note')->find($id)) {
            $this->dados->note = $this->viability->Note->note;
            $this->dados->lexp = $this->viability->Note->lexp;
            $this->dados->company = $this->viability->Company->name;
            $this->dados->prazo = Carbon::parse($this->viability->sended_at)->addDays($this->viability->getDays() + 7)->format('d/m/Y');


            if (!Auth()->User()->superadm && Auth()->User()->company_id != $this->viability->company_id) {
                $this->dados = (object)[
                    'note' => null,
                    'lexp' => null,
                    'company' => null,
                    'prazo' => null,
                ];

                $this->viability = Viability::with('Company', 'Note')->find(0);
            }
        }


        return view('pdf.novo', ['dados' => $this->dados, 'viability' => $this->viability]);
    }


    public function checkListFiscal($id = null)
    {
        $this->dados = (object)[
            'note' => null,
            'lexp' => null,
            'company' => null,
            'prazo' => null,
        ];




        return view('pdf.chk_list_fiscal', ['dados' => $this->dados, 'viability' => $this->viability]);
    }
}
