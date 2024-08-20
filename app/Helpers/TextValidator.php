<?php

namespace App\Helpers;

class TextValidator
{
    /**
    * Verifica se o texto contém sentido e não é uma tentativa de burlar a validação.
    *
    * @param string $text O texto a ser verificado.
    * @return bool Retorna true se o texto parecer válido, false caso contrário.
    */
    public static function isValidText(string $text): bool
    {
        // Limpar o texto removendo espaços extras
        $cleanedText = trim(preg_replace('/\s+/', ' ', $text));

        // Verificar se o texto atende ao mínimo de palavras
        if (str_word_count($cleanedText) < 3) {
            return false;
        }

        // Verificar se o texto contém muitos caracteres repetidos
        if (self::hasExcessiveRepetitions($cleanedText)) {
            return false;
        }

        // Verificar se o texto é uma repetição de palavras
        if (self::hasRepetitiveWords($cleanedText)) {
            return false;
        }

        // Verificar se o texto contém uma quantidade excessiva de palavras "vazias"
        if (self::hasTooManyFillerWords($cleanedText)) {
            return false;
        }

        // Verificar se o texto tem baixa entropia (indicando baixa variedade de caracteres)
        if (self::hasLowEntropy($cleanedText)) {
            return false;
        }

        // Verificar se o texto não faz sentido (usando uma técnica básica de detecção de coerência)
        if (self::lacksCoherence($cleanedText)) {
            return false;
        }

        // Se passar por todas as verificações, consideramos o texto válido
        return true;
    }

    /**
     * Verifica se o texto contém muitas repetições de caracteres.
     *
     * @param string $text O texto a ser verificado.
     * @return bool
     */
    private static function hasExcessiveRepetitions(string $text): bool
    {
        return preg_match('/(.)\\1{3,}/', $text) > 0;
    }

    /**
     * Verifica se o texto contém muitas repetições de palavras.
     *
     * @param string $text O texto a ser verificado.
     * @return bool
     */
    private static function hasRepetitiveWords(string $text): bool
    {
        $words = explode(' ', $text);
        $wordCounts = array_count_values($words);

        foreach ($wordCounts as $word => $count) {
            if ($count > 3) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o texto contém uma quantidade excessiva de palavras "vazias".
     *
     * @param string $text O texto a ser verificado.
     * @return bool
     */
    private static function hasTooManyFillerWords(string $text): bool
    {
        $fillerWords = ['teste', 'exemplo', 'bla', 'lorem', 'ipsum', 'aaa', 'zzz', 'asdf', 'qwerty'];
        $words = explode(' ', $text);
        $fillerCount = 0;

        foreach ($words as $word) {
            if (in_array(strtolower($word), $fillerWords)) {
                $fillerCount++;
            }
        }

        return $fillerCount > (count($words) / 3);
    }

    /**
     * Verifica se o texto possui baixa entropia, indicando baixa variedade de caracteres.
     *
     * @param string $text O texto a ser verificado.
     * @return bool
     */
    private static function hasLowEntropy(string $text): bool
    {
        $uniqueChars = count(array_unique(str_split($text)));
        return $uniqueChars < 5;
    }

    /**
     * Verifica se o texto aparenta não ter coerência básica.
     *
     * @param string $text O texto a ser verificado.
     * @return bool
     */
    private static function lacksCoherence(string $text): bool
    {
        // Dicionário resumido com palavras comuns em português
        $dictionary = [
            // Termos técnicos e administrativos
            'energia', 'transformador', 'subestação', 'linha', 'tensão', 'corrente', 'potência', 'circuito',
            'equipamento', 'projeto', 'relatório', 'cliente', 'obra', 'empresa', 'gestão', 'manutenção',
            'instalação', 'regulamentação', 'controle', 'processo', 'atividade',

            // Artigos e preposições
            'o', 'a', 'os', 'as', 'de', 'do', 'da', 'em', 'no', 'na', 'por', 'com', 'para',

            // Conjunções e pronomes
            'e', 'ou', 'mas', 'porque', 'que', 'quem', 'onde', 'como', 'quanto', 'todos', 'algum', 'nenhum',

            // Outros termos comuns
            'sim', 'não', 'sempre', 'nunca', 'hoje', 'amanhã', 'aqui', 'lá', 'mais', 'menos', 'muito', 'pouco'
        ];

        $words = explode(' ', strtolower($text));
        $meaningfulWords = 0;

        foreach ($words as $word) {
            if (in_array($word, $dictionary)) {
                $meaningfulWords++;
            }
        }

        // Considera coerente se pelo menos 30% das palavras forem significativas
        return $meaningfulWords < (count($words) * 0.3);
    }
}
