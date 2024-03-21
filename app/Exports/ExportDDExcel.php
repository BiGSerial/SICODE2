<?php

namespace App\Exports;

use App\Models\Note;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\{Exportable, FromView};

class ExportDDExcel implements FromView
{
    use Exportable;

    public $exports;

    public $service;

    public function exportDD($notes, $service)
    {
        $this->exports = Note::orderBy('type_note', 'DESC')->with('Wpas')->orderBy('days_left')->find($notes);
        $this->service = $service;

        return $this;
    }

    public function view(): View
    {
        return view('exports.exportdd', [
            'exports' => $this->exports,
            'service' => $this->service,
        ]);
    }
}
