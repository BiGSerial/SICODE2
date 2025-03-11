<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RegistroUpdateSanitizer
{
    private string $jsonPath;

    public function __construct()
    {
        $this->jsonPath = base_path('registroUpdate.json');
    }

    public function sanitize(): void
    {
        if (!File::exists($this->jsonPath)) {
            Log::warning("Arquivo registroUpdate.json não encontrado: {$this->jsonPath}");
            return;
        }

        // Tempo limite (5 dias atrás)
        $cutoffTimestamp = now()->subDays(5)->timestamp;

        // Usar um gerador para processar o arquivo JSON linha por linha
        $dataGenerator = $this->readJsonLines();

        // Array para acumular os registros que serão mantidos
        $filteredData = [];

        foreach ($dataGenerator as $item) {
            if (isset($item['date_inicio'])) {
                try {
                    $timestamp = strtotime($item['date_inicio']);

                    // Manter o registro se estiver dentro do período (mais recente)
                    if ($timestamp >= $cutoffTimestamp) {
                        $filteredData[] = $item;
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao processar data no registro: " . $e->getMessage());
                    // Decide se você quer manter registros com datas inválidas ou removê-los
                    // Neste exemplo, estou mantendo.
                    $filteredData[] = $item;
                }
            } else {
                // Mantém registros sem 'date_inicio'
                $filteredData[] = $item;
            }
        }

        // Reescrever o arquivo JSON com os dados saneados
        $this->writeJson($filteredData);
    }

    /**
     * Gerador para ler o arquivo JSON linha por linha.
     *
     * @return \Generator
     */
    private function readJsonLines(): \Generator
    {
        $stream = fopen($this->jsonPath, 'r');

        if ($stream) {
            while (($line = fgets($stream)) !== false) {
                try {
                    $data = json_decode($line, true);
                    if (is_array($data)) {
                        yield $data;
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao decodificar linha JSON: " . $e->getMessage());
                }
            }
            fclose($stream);
        } else {
            Log::error("Não foi possível abrir o arquivo JSON para leitura.");
        }
    }

    /**
     * Escreve os dados filtrados de volta no arquivo JSON.
     *
     * @param array $data
     * @return void
     */
    private function writeJson(array $data): void
    {
        $jsonData = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($jsonData === false) {
            Log::error("Erro ao codificar os dados para JSON.");
            return;
        }

        if (File::put($this->jsonPath, $jsonData) === false) {
            Log::error("Erro ao escrever no arquivo JSON.");
        }
    }
}
