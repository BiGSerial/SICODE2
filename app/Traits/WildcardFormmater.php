<?php

namespace App\Traits;

trait WildcardFormmater
{
    /**
     * Format a string and return an object with search and type parameters.
     *
     * @param string $string The string to format.
     * @param string $value The value to replace wildcards with.
     * @return object The formatted object with search and type.
     */
    public function formatWithWildcard(string $string): object
    {
        $hasWildcard = str_contains($string, '*') || str_contains($string, '%') || str_contains($string, '?');

        // Replace asterisk and question mark with percentage
        $formattedString = str_replace(['*', '?'], '%', $string);

        return (object)[
            'search' => trim($formattedString),
            'type' => $hasWildcard ? 'like' : '='
        ];
    }
}
