<?php

namespace App\Helpers;

trait TextValidator
{
    /**
     * Valida um texto e retorna um array com o resultado e os motivos da falha, se houver.
     *
     * @param string $text O texto a ser validado.
     * @param int $minWords O número mínimo de palavras que o texto deve ter.
     * @param int $maxRepetitions O número máximo de repetições consecutivas de um caractere.
     * @param float $maxFillerRatio A proporção máxima de palavras de preenchimento permitidas.
     * @param int $minUniqueChars O número mínimo de caracteres únicos que o texto deve ter.
     * @param float $minCoherenceRatio A proporção mínima de palavras coerentes (do dicionário) que o texto deve ter.
     * @return array Um array associativo com as chaves 'valid' (bool) e 'reasons' (array de strings).
     */
    public static function isValidText(
        string $text,
        int $minWords = 5,
        int $maxRepetitions = 3,
        float $maxFillerRatio = 0.33,
        int $minUniqueChars = 5,
        float $minCoherenceRatio = 0.05
    ): array {
        $reasons = [];
        $cleanedText = trim(preg_replace('/\s+/', ' ', $text));
        $wordCount = str_word_count($cleanedText);

        if ($wordCount < $minWords) {
            $reasons[] = 'Número insuficiente de palavras (mínimo: ' . $minWords . ')';
        }

        if (self::hasExcessiveRepetitions($cleanedText, $maxRepetitions)) {
            $reasons[] = 'Repetições excessivas de caracteres (máximo: ' . $maxRepetitions . ')';
        }

        if (self::hasRepetitiveWords($cleanedText)) {
            $reasons[] = 'Palavras repetitivas detectadas';
        }

        if (self::hasTooManyFillerWords($cleanedText, $maxFillerRatio)) {
            $reasons[] = 'Quantidade excessiva de palavras de preenchimento (máximo: ' . ($maxFillerRatio * 100) . '%)';
        }

        if (self::hasLowEntropy($cleanedText, $minUniqueChars)) {
            $reasons[] = 'Baixa variedade de caracteres (mínimo: ' . $minUniqueChars . ')';
        }

        if (self::lacksCoherence($cleanedText, $minCoherenceRatio)) {
            $reasons[] = 'Falta de coerência (mínimo: ' . ($minCoherenceRatio * 100) . '% de palavras coerentes)';
        }

        return [
            'valid' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Verifica se o texto contém muitas repetições de caracteres.
     *
     * @param string $text O texto a ser verificado.
     * @param int $maxRepetitions O número máximo de repetições permitidas.
     * @return bool
     */
    private static function hasExcessiveRepetitions(string $text, int $maxRepetitions): bool
    {
        return preg_match('/(.)\\1{' . $maxRepetitions . ',}/', $text) > 0;
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
     * @param float $maxFillerRatio A proporção máxima de palavras de preenchimento permitidas.
     * @return bool
     */
    private static function hasTooManyFillerWords(string $text, float $maxFillerRatio): bool
    {
        static $fillerWords = [
            'teste', 'exemplo', 'bla', 'lorem', 'ipsum', 'aaa', 'zzz', 'asdf', 'qwerty',
            'foo', 'bar', 'baz', 'qux', 'spam', 'eggs', 'ham', 'dolor', 'sit', 'amet',
            'consectetur', 'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor',
            'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua', 'enim',
            'ad', 'minim', 'veniam', 'quis', 'nostrud', 'exercitation', 'ullamco',
            'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo', 'consequat', 'duis',
            'aute', 'irure', 'in', 'reprehenderit', 'voluptate', 'velit', 'esse',
            'cillum', 'eu', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
            'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui',
            'officia', 'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum',
            'tralala', 'fizz', 'buzz', 'ding', 'dong', 'yada', 'yada', 'zing', 'zoom',
            'woop', 'plop', 'glop', 'blob', 'scribble', 'doodle', 'jabber', 'nonsense',
            'gibberish', 'mumbojumbo', 'blather', 'drivel', 'gobbledygook', 'rambling',
            'verbose', 'wordy', 'tedious', 'uninspired', 'hackneyed', 'banal', 'trite',
            'cliched', 'otiose', 'redundant', 'superfluous', 'verbiage', 'tautology',
            'fdsgfdg', 'hjkjhkj', 'poiuytrewq', 'mnbvcxz', 'lkjhgfdsa', 'ytrewq', 'asdfghjkl',
            'zxcvbnm', 'qazwsxedcrfv', 'plokmijnuhbygvtfcrdxeszwaq', 'wsxedcrfvtgbyhnujmikolp',
            'zaqwsxcderfvbgtyhnmjuik', 'poiuytrewqasdfghjklmnbvcxz', 'asdfjklpoiuytrewqmnbvcxz',
            'qwertyuiopasdfghjklzxcvbnm', 'mnbvcxzlkjhgfdsaqwertyuiop', 'poiuylkjhgfdsamnbvcxz',
            'qwertasdfgzxcvb', 'poiulkjhmnbvgy', 'asdfghjklç', 'çlkjhgfdsa', 'trewqpoiuy',
            'mnbvcxzasdfg', 'yuiopqwerty', 'hgfdsapoiuy', 'xcvbnmlkjhg', 'wertyuiop',
            'dfghjklmnbvc', 'trewqasdfghj', 'poiuytrewqmn', 'kjhgfdsaqwert', 'mnbvcxzpoiuyt',
            'lkjhgfdsaqaz', 'plokijmnhytg', 'zaqwsxedcrfv', 'qwertyuioplkj',
            'dfghjkmnbvcf', 'poiuylkjhgfd', 'zaqwertghy', 'plokmnbvcf',
            'aaaa', 'bbbb', 'cccc', 'dddd', 'eeee', 'ffff', 'gggg', 'hhhh', 'iiii', 'jjjj',
            'kkkk', 'llll', 'mmmm', 'nnnn', 'oooo', 'pppp', 'qqqq', 'rrrr', 'ssss', 'tttt',
            'uuuu', 'vvvv', 'wwww', 'xxxx', 'yyyy', 'zzzz',
            '12345', '67890', '09876', '54321',
            '1111', '2222', '3333', '4444', '5555', '6666', '7777', '8888', '9999', '0000',
        ];
        static $fillerWordsLookup = null;
        if ($fillerWordsLookup === null) {
            $fillerWordsLookup = array_flip(array_map('strtolower', $fillerWords));
        }
        $words = explode(' ', strtolower($text));
        $fillerCount = 0;
        foreach ($words as $word) {
            if (isset($fillerWordsLookup[$word])) {
                $fillerCount++;
            }
        }
        return $fillerCount > (count($words) * $maxFillerRatio);
    }

    /**
     * Verifica se o texto possui baixa entropia, indicando baixa variedade de caracteres.
     *
     * @param string $text O texto a ser verificado.
     * @param int $minUniqueChars O número mínimo de caracteres únicos que o texto deve ter.
     * @return bool
     */
    private static function hasLowEntropy(string $text, int $minUniqueChars): bool
    {
        $uniqueChars = count(array_unique(str_split($text)));
        return $uniqueChars < $minUniqueChars;
    }

    /**
     * Verifica se o texto aparenta não ter coerência básica.
     *
     * @param string $text O texto a ser verificado.
     * @param float $minCoherenceRatio A proporção mínima de palavras coerentes (do dicionário) que o texto deve ter.
     * @return bool
     */
    private static function lacksCoherence(string $text, float $minCoherenceRatio): bool
    {
        static $dictionary = [
            'energia', 'energético', 'energética', 'energias', 'energizado', 'desenergizado',
            'transformador', 'transformadores', 'transformação', 'transformando',
            'subestação', 'subestações', 'subestação elevadora', 'subestação abaixadora', 'subestação blindada',
            'linha', 'linhas', 'linha de transmissão', 'linha de distribuição', 'linha aérea', 'linha subterrânea',
            'tensão', 'tensões', 'alta tensão', 'média tensão', 'baixa tensão', 'sobretensão', 'subtensão',
            'corrente', 'correntes', 'corrente nominal', 'corrente de fuga', 'corrente de curto-circuito',
            'potência', 'potências', 'potência ativa', 'potência reativa', 'potência aparente',
            'circuito', 'circuitos', 'circuito primário', 'circuito secundário',
            'equipamento', 'equipamentos', 'equipamento de proteção', 'equipamento de medição',
            'painel', 'painéis', 'painel elétrico',
            'disjuntor', 'disjuntores',
            'relé', 'relés', 'relé de proteção',
            'cabo', 'cabos', 'cabo de guarda',
            'isolador', 'isoladores',
            'condutor', 'condutores',
            'fusível', 'fusíveis',
            'medidor', 'medidores', 'medidor inteligente',
            'poste', 'postes', 'poste de concreto', 'poste de madeira',
            'chave', 'chaves', 'chave seccionadora', 'chave fusível',
            'projeto', 'projetos', 'projeto executivo', 'projeto básico', 'projeto as-built',
            'relatório', 'relatórios', 'relatório de inspeção', 'relatório de manutenção',
            'laudo', 'laudos', 'laudo técnico',
            'diagrama', 'diagramas', 'diagrama unifilar', 'diagrama multifilar',
            'especificação', 'especificações', 'especificação técnica',
            'manutenção', 'manutenções', 'manutenção preventiva', 'manutenção corretiva', 'manutenção preditiva',
            'instalação', 'instalações', 'instalação elétrica',
            'operação', 'operações', 'operacional',
            'teste', 'testes',
            'inspeção', 'inspeções',
            'medição', 'medições',
            'cliente', 'clientes', 'cliente industrial', 'cliente comercial', 'cliente residencial',
            'obra', 'obras', 'canteiro de obras',
            'empresa', 'empresas',
            'gestão', 'gestões', 'gerenciamento',
            'contrato', 'contratos',
            'fornecedor', 'fornecedores',
            'licitação', 'licitações',
            'regulamentação', 'regulamentações', 'norma', 'normas', 'norma técnica', 'segurança', 'seguro',
            'controle', 'controles',
            'risco', 'riscos',
            'processo', 'processos', 'procedimento', 'procedimentos',
            'atividade', 'atividades',
            'dado', 'dados', 'sistema', 'sistemas', 'telemetria', 'SCADA',
            'ligado', 'desligado', 'conectado', 'desconectado',
            'acionado', 'desacionado',
            'habilitado', 'desabilitado',
            'qualidade', 'eficiência', 'confiabilidade',
            'valor', 'custo',
            'aterramento', 'aterrado', 'SPDA', 'EPI', 'EPC',
            'NR-10', 'APR', 'PT', 'DDS',
            'de', 'do', 'da', 'em', 'no', 'na', 'por', 'com', 'para', 'e', 'ou', 'que',
            'mais', 'menos', 'muito', 'pouco', 'todos', 'algum', 'nenhum', 'sempre', 'nunca'
        ];
        static $dictionaryLookup = null; // Garante que seja inicializado apenas uma vez
        if ($dictionaryLookup === null) {
            $dictionaryLookup = array_flip($dictionary);
        }

        $words = explode(' ', strtolower($text));
        $meaningfulWords = 0;

        foreach ($words as $word) {
            if (isset($dictionaryLookup[$word])) {
                $meaningfulWords++;
            }
        }

        return $meaningfulWords < (count($words) * $minCoherenceRatio);
    }
}
