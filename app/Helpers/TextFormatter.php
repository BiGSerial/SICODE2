<?php

namespace App\Helpers;

trait TextFormatter
{

    /**
     * Formata para um arrau em um padrao pré-definido.
     *
     * @param string $text
     * @return array
     */
    public function formatTextToArray(string $text): array
    {
        $lines = explode("\n", $text);
        $result = [];

        foreach ($lines as $line) {
            $result = array_merge($result, $this->splitByDelimiter($line));
        }

        $result = array_filter($result, function ($value) {
            return trim($value) !== '';
        });

        return $result;
    }

    /**
     * Separar por delimitadores
     *
     * @param [type] $line
     * @return array
     */
    private function splitByDelimiter($line): array
    {
        $delimiters = ["\t", ",", ";", " "];

        foreach ($delimiters as $delimiter) {
            if (strpos($line, $delimiter) !== false) {
                return explode($delimiter, $line);
            }
        }

        return [$line];
    }
}
