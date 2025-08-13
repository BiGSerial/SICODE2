<?php

namespace App\Custom;

class RegistroJson
{
    /*** Arquivos ***/
    private string $legacyPath;   // JSON legado em objeto: {"1": {...}, "2": {...}}
    private string $jsonlPath;    // JSONL rápido: 1 linha por registro

    /*** Política de retenção (0 = desativa) ***/
    private int $pruneDays = 5;

    /*** Dados do registro atual ***/
    private ?int $last_id;
    private string $task;
    private $options;
    private $total;
    private $updated;
    private $created;
    private $noteUpdated;
    private int $errors;
    private array $erroMsg;
    private string $datetime_init;

    /*** Perf helpers ***/
    private int $tailBlock = 1048576; // 1MB para leitura reversa

    public function __construct(string $task, $options = null, $total = null, $created = null, $updated = null, $noteUpdated = null)
    {
        $this->legacyPath  = base_path('registroUpdate.json');
        $this->jsonlPath   = base_path('registroUpdate.jsonl');

        $this->last_id       = null;
        $this->task          = $task;
        $this->options       = $options;
        $this->total         = $total;
        $this->created       = $created;
        $this->updated       = $updated;
        $this->noteUpdated   = $noteUpdated;
        $this->errors        = 0;
        $this->erroMsg       = [];
        $this->datetime_init = date('Y-m-d H:i:s');
    }

    /** Getters úteis */
    public function getLastId()
    {
        return $this->last_id;
    }
    public function getLegacyPath()
    {
        return $this->legacyPath;
    }
    public function getJsonlPath()
    {
        return $this->jsonlPath;
    }
    public function getTask()
    {
        return $this->task;
    }
    public function getOptions()
    {
        return $this->options;
    }
    public function getTotal()
    {
        return $this->total;
    }
    public function getUpdated()
    {
        return $this->updated;
    }
    public function getCreated()
    {
        return $this->created;
    }
    public function getNoteUpdated()
    {
        return $this->noteUpdated;
    }
    public function getErrors()
    {
        return $this->errors;
    }
    public function getDatetimeInit()
    {
        return $this->datetime_init;
    }

    public function setPruneDays(int $days)
    {
        $this->pruneDays = max(0, $days);
    }
    public function setTask($task)
    {
        $this->task = $task;
    }
    public function setOptions($options)
    {
        $this->options = $options;
    }
    public function setTotal($total)
    {
        $this->total = $total;
    }
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }
    public function setCreated($created)
    {
        $this->created = $created;
    }
    public function setNoteUpdated($noteUpdated)
    {
        $this->noteUpdated = $noteUpdated;
    }
    public function setDatetimeInit($datetime_init)
    {
        $this->datetime_init = $datetime_init;
    }

    public function setErrorMessage($erroMsg): void
    {
        $this->erroMsg[] = $erroMsg;
        $this->errors++;
    }

    /**
     * SALVAR:
     * 1) Garante JSONL sincronizado com legado (converte/mescla antes).
     * 2) Calcula próximo id a partir do MAIOR id já presente no JSONL.
     * 3) (Opcional) prune por dias (no mesmo inode).
     * 4) Append do novo registro no JSONL.
     */
    public function save(): void
    {
        $this->ensureJsonlMigrated();

        $nextId = $this->getMaxIdFromJsonl() + 1;
        $this->last_id = $nextId;

        $entry = [
            'id'           => (string) $nextId,
            'tarefa'       => $this->task,
            'options'      => $this->options,
            'total'        => $this->total,
            'updated'      => $this->updated,
            'created'      => $this->created,
            'noteupdated'  => $this->noteUpdated,
            'erros'        => $this->errors,
            'errosMSGs'    => $this->erroMsg,
            'date_inicio'  => $this->datetime_init,
            'date_fim'     => date('Y-m-d H:i:s'),
        ];

        if ($this->pruneDays > 0) {
            $this->pruneJsonlByDays($this->pruneDays);
        }

        file_put_contents(
            $this->jsonlPath,
            json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Garante que o JSONL existe e contém os registros do JSON legado.
     * - Se só existe legado: converte TUDO ( "1": {...} -> {"id":"1", ...} por linha ).
     * - Se existem os dois: insere no JSONL apenas os ids do legado que ainda não estão no JSONL.
     * - Se nada existe: cria JSONL vazio (somente se não existir).
     */
    private function ensureJsonlMigrated(): void
    {
        $legacyExists = is_readable($this->legacyPath);
        $jsonlExists  = is_readable($this->jsonlPath);

        if (!$legacyExists && !$jsonlExists) {
            if (!file_exists($this->jsonlPath)) {
                @file_put_contents($this->jsonlPath, '');
            }
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

    /**
     * Converte JSON legado (objeto) para JSONL, preservando o "id" da chave.
     * Ex.: "1": { ... } => {"id":"1", ...}
     * Usa fopen('c+') + ftruncate(0) para evitar novo inode.
     */
    private function convertLegacyObjectToJsonl(string $src, string $dest): void
    {
        $content = @file_get_contents($src);
        if ($content === false || $content === '') {
            if (!file_exists($dest)) {
                @file_put_contents($dest, '');
            }
            return;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            if (!file_exists($dest)) {
                @file_put_contents($dest, '');
            }
            return;
        }

        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // Abre sem recriar inode: cria se não existir, mas se existir mantém inode
        $out = fopen($dest, 'c+');
        if (!$out) {
            return;
        }

        // limpa o conteúdo, mantendo inode/perms/dono
        ftruncate($out, 0);
        rewind($out);

        // ksort($data, SORT_NUMERIC); // opcional
        foreach ($data as $id => $row) {
            if (!is_array($row)) {
                continue;
            }
            $row['id'] = (string) $id;
            fwrite($out, json_encode($row, JSON_UNESCAPED_UNICODE) . "\n");
        }

        fflush($out);
        fclose($out);
    }

    /**
     * Lê IDs já presentes no JSONL (conjunto + maior id) e insere do legado apenas os que faltam.
     * Abre em 'a' (append) — não altera inode quando já existe.
     */
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

        // Conjunto de ids no JSONL
        [$idsJsonl, ] = $this->getJsonlIdSetAndMax($jsonl);

        // Garante existência antes de 'a'
        if (!file_exists($jsonl)) {
            @file_put_contents($jsonl, '');
        }

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
                continue; // já existe, não duplica
            }

            $row['id'] = $idStr;
            fwrite($out, json_encode($row, JSON_UNESCAPED_UNICODE) . "\n");
        }

        fflush($out);
        fclose($out);
    }

    /**
     * Retorna o MAIOR id numérico presente no JSONL (rápido).
     */
    private function getMaxIdFromJsonl(): int
    {
        if (!is_readable($this->jsonlPath)) {
            return 0;
        }

        $size = filesize($this->jsonlPath);
        if ($size === 0) {
            return 0;
        }

        $fp = fopen($this->jsonlPath, 'r');
        if (!$fp) {
            return 0;
        }

        $buffer = '';
        $pos = $size;

        while ($pos > 0) {
            $read = ($pos >= $this->tailBlock) ? $this->tailBlock : $pos;
            $pos -= $read;
            fseek($fp, $pos);
            $chunk = fread($fp, $read);
            if ($chunk === false) {
                break;
            }

            $buffer = $chunk . $buffer;

            $lines = explode("\n", $buffer);
            $buffer = array_shift($lines);

            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = trim($lines[$i]);
                if ($line === '') {
                    continue;
                }
                $data = json_decode($line, true);
                if (!is_array($data)) {
                    continue;
                }

                if (!empty($data['id'])) {
                    $idNum = is_numeric($data['id']) ? (int) $data['id'] : 0;
                    fclose($fp);
                    return max(0, $idNum);
                }
            }
        }

        $line = trim($buffer);
        if ($line !== '') {
            $data = json_decode($line, true);
            if (is_array($data) && !empty($data['id'])) {
                $idNum = is_numeric($data['id']) ? (int) $data['id'] : 0;
                fclose($fp);
                return max(0, $idNum);
            }
        }

        fclose($fp);
        return 0;
    }

    /**
     * Retorna [conjunto_de_ids, max_id] lendo o JSONL sequencialmente.
     * Útil para upsert/mescla.
     */
    private function getJsonlIdSetAndMax(string $jsonlPath): array
    {
        $ids = [];
        $max = 0;

        if (!is_readable($jsonlPath)) {
            return [$ids, $max];
        }

        $fp = fopen($jsonlPath, 'r');
        if (!$fp) {
            return [$ids, $max];
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
                $idStr = (string) $data['id'];
                $ids[$idStr] = true;

                if (is_numeric($idStr)) {
                    $n = (int) $idStr;
                    if ($n > $max) {
                        $max = $n;
                    }
                }
            }
        }

        fclose($fp);
        return [$ids, $max];
    }

    /**
     * Remove linhas do JSONL com date_fim mais antigo que $days dias.
     * Reescreve o arquivo NO MESMO INODE (sem .tmp/rename).
     */
    private function pruneJsonlByDays(int $days): void
    {
        if (!is_readable($this->jsonlPath)) {
            return;
        }

        $threshold = strtotime("-{$days} days");

        // Abre o próprio arquivo em c+ (não cria novo inode)
        $fp = fopen($this->jsonlPath, 'c+');
        if (!$fp) {
            return;
        }

        $linesToKeep = [];

        // lê do início ao fim (não precisa de tail aqui)
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $data = json_decode($line, true);
            if (!is_array($data)) {
                continue;
            }

            $fim = isset($data['date_fim']) ? strtotime($data['date_fim']) : null;
            if ($fim === null || $fim >= $threshold) {
                $linesToKeep[] = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
        }

        // Trunca e regrava no MESMO arquivo
        ftruncate($fp, 0);
        rewind($fp);

        if (!empty($linesToKeep)) {
            fwrite($fp, implode("\n", $linesToKeep) . "\n");
        }

        fflush($fp);
        fclose($fp);
    }
}
