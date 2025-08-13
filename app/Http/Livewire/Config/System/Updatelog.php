<?php

namespace App\Http\Livewire\Config\System;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

/**
 * Updatelog – leitura rápida estilo LogViewer, usando JSONL:
 * - Lê do fim para o início, em blocos (tail-like).
 * - Carrega N itens por vez (loadMore).
 * - Filtra por tarefa on-the-fly (sem carregar tudo).
 * - Antes de ler, garante que .jsonl existe (migrando do .json legado, se necessário).
 *
 * Formato do JSONL esperado (uma linha por registro):
 *   {"id":"1","tarefa":"upd_baseOV","options":{...},"total":0,"updated":0,"created":0,"noteupdated":null,"erros":0,"errosMSGs":[],"date_inicio":"YYYY-MM-DD HH:MM:SS","date_fim":"YYYY-MM-DD HH:MM:SS"}
 */
class Updatelog extends Component
{
    /** Buffer exibido na tela */
    public array $logs = [];

    /** Filtros */
    public array $tasks = [];
    public ?string $singleTask = null;

    /** Paginação por cursor (quantos registros matching já retornamos) */
    public int $pageSize = 20;
    public int $skipMatched = 0;
    public bool $hasMore = true;

    /** Caminhos */
    public string $legacyPath; // registroUpdate.json (objeto legado)
    public string $jsonlPath;  // registroUpdate.jsonl (rápido)

    /** Tamanho do bloco de leitura reversa (1MB) */
    private int $blockBytes = 1048576;

    protected $queryString = [
        'singleTask' => ['as' => 'task', 'except' => ''],
    ];

    public function mount()
    {
        // Ajuste os caminhos se quiser salvar/ler de outro lugar
        $this->legacyPath = base_path('registroUpdate.json');
        $this->jsonlPath  = base_path('registroUpdate.jsonl');

        // 1) Garante que temos um JSONL pronto (migrando do legado se necessário)
        $this->ensureJsonlReady();

        // 2) Descobre tarefas rapidamente olhando só as últimas linhas
        $this->tasks = $this->discoverTasksFast($this->jsonlPath, 3000);
        sort($this->tasks);

        // 3) Primeira carga
        $this->loadMore();
    }

    public function updatedSingleTask()
    {
        $this->resetCursor();
        $this->loadMore();
    }

    public function resetCursor(): void
    {
        $this->logs = [];
        $this->skipMatched = 0;
        $this->hasMore = true;
    }

    /** Carrega mais itens do fim do arquivo (JSONL) */
    public function loadMore(): void
    {
        if (!$this->hasMore) {
            return;
        }

        $resp = $this->readJsonlFromEnd(
            path: $this->jsonlPath,
            task: $this->singleTask ?: null,
            skip: $this->skipMatched,
            limit: $this->pageSize,
            blockBytes: $this->blockBytes
        );

        $items = $resp['items'];
        usort($items, function ($a, $b) {
            $aFim = isset($a['date_fim']) ? strtotime($a['date_fim']) : 0;
            $bFim = isset($b['date_fim']) ? strtotime($b['date_fim']) : 0;
            // se empatar (ou faltar), mantém estável usando id numérico quando possível
            if ($aFim === $bFim) {
                $ai = is_numeric($a['id'] ?? null) ? (int)$a['id'] : -PHP_INT_MAX;
                $bi = is_numeric($b['id'] ?? null) ? (int)$b['id'] : -PHP_INT_MAX;
                return $bi <=> $ai; // id desc como critério secundário
            }
            return $bFim <=> $aFim; // desc
        });

        foreach ($items as $item) {
            // Normalizações mínimas para o Blade
            $item['id']          = (string) ($item['id'] ?? md5(json_encode($item)));
            $item['tarefa']      = $item['tarefa']      ?? 'N/A';
            $item['date_inicio'] = $item['date_inicio'] ?? null;
            $item['date_fim']    = $item['date_fim']    ?? null;
            $item['created']     = $item['created']     ?? 0;
            $item['updated']     = $item['updated']     ?? 0;
            $item['total']       = $item['total']       ?? 0;
            $item['erros']       = $item['erros']       ?? 0;
            $item['errosMSGs']   = $item['errosMSGs']   ?? [];
            $item['options']     = $item['options']     ?? [];

            $this->logs[] = $item;
        }

        $this->skipMatched += count($resp['items']);
        $this->hasMore = $resp['has_more'];
    }

    /**
     * MIGRAÇÃO: garante que existe JSONL pronto para leitura.
     * Regras:
     * - Se só existe .json → converte TUDO para .jsonl (cada "1": {...} vira {"id":"1", ...}\n).
     * - Se existem os dois → insere no .jsonl apenas os ids do .json que ainda não estão no .jsonl.
     * - Se só existe .jsonl → ok.
     * - Se nenhum existe → cria .jsonl vazio.
     */
    private function ensureJsonlReady(): void
    {
        $legacyExists = is_readable($this->legacyPath);
        $jsonlExists  = is_readable($this->jsonlPath);

        if (!$legacyExists && !$jsonlExists) {
            @file_put_contents($this->jsonlPath, '');
            return;
        }

        if ($legacyExists && !$jsonlExists) {
            $this->convertLegacyObjectToJsonl($this->legacyPath, $this->jsonlPath);
            return;
        }

        if ($legacyExists && $jsonlExists) {
            $this->upsertMissingFromLegacy($this->legacyPath, $this->jsonlPath);
            return;
        }

        // só jsonl existe -> nada a fazer
    }

    /** Converte JSON legado (objeto) → JSONL (uma linha por registro), preservando o id da chave. */
    private function convertLegacyObjectToJsonl(string $src, string $dest): void
    {
        $content = @file_get_contents($src);
        if ($content === false || $content === '') {
            @file_put_contents($dest, '');
            return;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            @file_put_contents($dest, '');
            return;
        }

        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $out = fopen($dest, 'w');
        if (!$out) {
            return;
        }

        // ksort($data, SORT_NUMERIC); // opcional: ordena pelas chaves
        foreach ($data as $id => $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['id'] = (string) $id;
            fwrite($out, json_encode($row, JSON_UNESCAPED_UNICODE) . "\n");
        }

        fclose($out);
    }

    /** Insere no .jsonl apenas os ids do legado que ainda não existem no .jsonl (sem duplicar). */
    private function upsertMissingFromLegacy(string $legacy, string $jsonl): void
    {
        $legacyContent = @file_get_contents($legacy);
        if ($legacyContent === false || $legacyContent === '') {
            return;
        }

        $legacyData = json_decode($legacyContent, true);
        if (!is_array($legacyData)) {
            return;
        }

        // Conjunto de ids já presentes no JSONL
        $idsJsonl = $this->getJsonlIdSet($jsonl);

        $out = fopen($jsonl, 'a');
        if (!$out) {
            return;
        }

        foreach ($legacyData as $id => $row) {
            if (!is_array($row)) {
                continue;
            }
            $idStr = (string) $id;
            if (isset($idsJsonl[$idStr])) {
                continue;
            } // já existe

            $row['id'] = $idStr;
            fwrite($out, json_encode($row, JSON_UNESCAPED_UNICODE) . "\n");
        }

        fclose($out);
    }

    /** Retorna um set (array associativo) com todos os ids presentes no JSONL. */
    private function getJsonlIdSet(string $jsonlPath): array
    {
        $ids = [];

        if (!is_readable($jsonlPath)) {
            return $ids;
        }

        $fp = fopen($jsonlPath, 'r');
        if (!$fp) {
            return $ids;
        }

        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $data = json_decode($line, true);
            if (!is_array($data)) {
                continue;
            }

            if (!empty($data['id'])) {
                $ids[(string) $data['id']] = true;
            }
        }

        fclose($fp);
        return $ids;
    }

    /**
     * Lê um arquivo JSONL de TRÁS pra FRENTE, por blocos, retornando somente $limit
     * registros que batem com $task, pulando $skip correspondências anteriores.
     */
    private function readJsonlFromEnd(string $path, ?string $task, int $skip, int $limit, int $blockBytes = 1048576): array
    {
        $items = [];
        $hasMore = false;

        if (!is_readable($path)) {
            Log::error("Arquivo JSONL não legível: {$path}");
            return ['items' => [], 'has_more' => false];
        }

        $size = filesize($path);
        if ($size === 0) {
            return ['items' => [], 'has_more' => false];
        }

        $fp = fopen($path, 'r');
        if (!$fp) {
            Log::error("Não foi possível abrir o arquivo: {$path}");
            return ['items' => [], 'has_more' => false];
        }

        $buffer = '';
        $pos = $size;
        $matchedSeen = 0;

        while ($pos > 0 && count($items) < $limit) {
            $read = ($pos >= $blockBytes) ? $blockBytes : $pos;
            $pos -= $read;
            fseek($fp, $pos);
            $chunk = fread($fp, $read);
            if ($chunk === false) {
                break;
            }

            // prefixa: mantemos ordem temporal do fim p/ início
            $buffer = $chunk . $buffer;

            // quebra por linhas
            $lines = explode("\n", $buffer);

            // a primeira posição pode ser linha incompleta; guardamos para o próximo ciclo
            $buffer = array_shift($lines);

            // percorre de trás pra frente (linhas mais novas primeiro)
            for ($i = count($lines) - 1; $i >= 0 && count($items) < $limit; $i--) {
                $line = trim($lines[$i]);
                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true);
                if (!is_array($data)) {
                    continue;
                }

                if ($task !== null && ($data['tarefa'] ?? null) !== $task) {
                    continue;
                }

                if ($matchedSeen < $skip) {
                    $matchedSeen++;
                    continue;
                }

                $items[] = $data;
            }
        }

        if ($pos > 0) {
            $hasMore = true;
        } else {
            // processa última linha possivelmente no $buffer (sem \n no EOF)
            $line = trim($buffer);
            if ($line !== '' && count($items) < $limit) {
                $data = json_decode($line, true);
                if (is_array($data)) {
                    $okTask = $task === null || (($data['tarefa'] ?? null) === $task);
                    if ($okTask) {
                        if ($matchedSeen < $skip) {
                            $matchedSeen++;
                        } else {
                            $items[] = $data;
                        }
                    }
                }
            }
            // heurística: se bateu o limite, provavelmente há mais
            $hasMore = count($items) === $limit;
        }

        fclose($fp);

        return ['items' => $items, 'has_more' => $hasMore];
    }

    /** Lê poucas linhas do fim para listar tarefas sem varrer o arquivo todo */
    private function discoverTasksFast(string $path, int $maxScan = 2000): array
    {
        $tasks = [];
        $seen = 0;

        $resp = $this->readJsonlFromEnd($path, null, 0, $maxScan, $this->blockBytes);
        foreach ($resp['items'] as $row) {
            if (!empty($row['tarefa'])) {
                $tasks[$row['tarefa']] = true;
                $seen++;
                if ($seen >= $maxScan) {
                    break;
                }
            }
        }

        return array_keys($tasks);
    }

    public function render()
    {
        return view('livewire.config.system.updatelog', [
            'logs'    => $this->logs,
            'hasMore' => $this->hasMore,
            'tasks'   => $this->tasks,
        ]);
    }
}
