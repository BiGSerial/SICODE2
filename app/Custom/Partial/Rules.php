<?php

namespace App\Custom\Partial;

use App\Models\Partial;

class Rules
{
    // Prazo para nova analise de parcial enviada em dias;
    private static $days = 25;

    public static function checkBlock($note_id)
    {
        $partial = Partial::where('note_id', $note_id)->orderBy('created_at', 'DESC')->first();

        if ($partial) {
            if (!$partial->allow && !$partial->deny) {
                return 1;
            } elseif ($partial->allow && $partial->created_at->diffInDays(now()) < self::$days) {
                return 0; // NOTE: Retornoar para 2
            } elseif ((!$partial->payment || !$partial->supervision) && !$partial->deny) {
                return 3;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
