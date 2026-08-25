<?php

namespace App\Support;

class SicodeRules
{
    public static function ruleset(): string
    {
        return (string) config('sicode.ruleset', 'es');
    }

    public static function displayName(string $fallback = 'sicode'): string
    {
        return (string) config('sicode.display_name', $fallback);
    }
}
