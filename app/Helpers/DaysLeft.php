<?php

namespace App\Helpers;

use App\Models\Note;
use Carbon\Carbon;

final class DaysLeft
{
    private ?Note $note = null;

    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    private function convertMensalizationToDate($mesalization)
    {
        if ($mesalization && $mesalization != 'erro') {
            preg_match('/\d+\/\d+/', $mesalization, $matches);

            if (!empty($matches)) {
                [$mes, $ano] = explode('/', $matches[0]);

                if ($mes >= 1) {
                    $data = "{$ano}-{$mes}-28 23:59:59";

                } else {
                    $data = "{$ano}-12-28 23:59:59";
                }
            }

            return $data;

        } else {

            return null;
        }
    }

    public function getDaysLeft()
    {
        if ($this->note->type_note == 1) {

            // dd($this->note->mesalization, $this->convertMensalizationToDate($this->note->mesalization));

            $hoje = Carbon::now();

            $dataCarbon = Carbon::parse( $this->convertMensalizationToDate($this->note->mesalization));
            $days_left = $hoje->diffInDays($dataCarbon, false);

            return $days_left;
        } else {
            return $this->note->days_left;
        }
    }

    public function getLastDate()
    {
        if ($this->note->type_note == 1) {
            return Carbon::parse($this->convertMensalizationToDate($this->note->mesalization))->format('d/m/Y');
        } else {
            return Carbon::now()->addDays($this->note->days_left)->format('d/m/Y');
        }
    }

}
