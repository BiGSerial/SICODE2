<?php

namespace App\Custom;

use App\Models\UpdateExecutionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegistroJson
{
    private const FAIL_REASON_SAFE_MAX = 240;

    /*** Política de retenção (0 = desativa) ***/
    private int $pruneDays = 60;

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

    private bool $finalized = false;

    public function __construct(string $task, $options = null, $total = null, $created = null, $updated = null, $noteUpdated = null)
    {
        $this->last_id       = null;
        $this->task          = $task;
        $this->options       = $options;
        $this->total         = $total;
        $this->created       = $created;
        $this->updated       = $updated;
        $this->noteUpdated   = $noteUpdated;
        $this->errors        = 0;
        $this->erroMsg       = [];
        $this->datetime_init = now()->toDateTimeString();

        $this->bootRunningLog();
    }

    public function __destruct()
    {
        if (!$this->finalized && $this->last_id !== null) {
            $this->fail('Execucao encerrada sem finalizacao explicita.');
        }
    }

    /** Getters úteis */
    public function getLastId()
    {
        return $this->last_id;
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
        $this->erroMsg[] = (string) $erroMsg;
        $this->errors++;
    }

    /** Finaliza com DONE */
    public function save(): void
    {
        if ($this->finalized || $this->last_id === null) {
            return;
        }

        UpdateExecutionLog::whereKey($this->last_id)->update([
            'status' => UpdateExecutionLog::STATUS_DONE,
            'options' => $this->normalizeOptions($this->options),
            'total' => (int) ($this->total ?? 0),
            'updated' => (int) ($this->updated ?? 0),
            'created' => (int) ($this->created ?? 0),
            'noteupdated' => $this->noteUpdated !== null ? (int) $this->noteUpdated : null,
            'erros' => (int) $this->errors,
            'errosMSGs' => $this->erroMsg,
            'date_fim' => now()->toDateTimeString(),
            'failed_at' => null,
            'fail_reason' => null,
        ]);

        $this->finalized = true;
    }

    /** Finaliza com FAIL */
    public function fail(?string $reason = null): void
    {
        if ($this->finalized || $this->last_id === null) {
            return;
        }

        if ($reason) {
            $this->setErrorMessage($reason);
        }

        $now = now()->toDateTimeString();

        UpdateExecutionLog::whereKey($this->last_id)->update([
            'status' => UpdateExecutionLog::STATUS_FAIL,
            'options' => $this->normalizeOptions($this->options),
            'total' => (int) ($this->total ?? 0),
            'updated' => (int) ($this->updated ?? 0),
            'created' => (int) ($this->created ?? 0),
            'noteupdated' => $this->noteUpdated !== null ? (int) $this->noteUpdated : null,
            'erros' => (int) $this->errors,
            'errosMSGs' => $this->erroMsg,
            'date_fim' => $now,
            'failed_at' => $now,
            'fail_reason' => $this->normalizeFailReason($reason),
        ]);

        $this->finalized = true;
    }

    private function bootRunningLog(): void
    {
        try {
            DB::transaction(function () {
                $startAt = Carbon::parse($this->datetime_init);

                // Fecha execuções antigas abertas da mesma tarefa.
                UpdateExecutionLog::query()
                    ->where('task', $this->task)
                    ->where('status', UpdateExecutionLog::STATUS_RUNNING)
                    ->whereNull('date_fim')
                    ->update([
                        'status' => UpdateExecutionLog::STATUS_FAIL,
                        'failed_at' => $startAt->toDateTimeString(),
                        'date_fim' => $startAt->toDateTimeString(),
                        'fail_reason' => $this->normalizeFailReason('Execucao anterior ficou em aberto e foi encerrada ao iniciar nova execucao.'),
                    ]);

                // Retenção de 2 meses.
                if ($this->pruneDays > 0) {
                    UpdateExecutionLog::query()
                        ->where('date_inicio', '<', $startAt->copy()->subDays($this->pruneDays))
                        ->delete();
                }

                $row = UpdateExecutionLog::create([
                    'task' => $this->task,
                    'status' => UpdateExecutionLog::STATUS_RUNNING,
                    'options' => $this->normalizeOptions($this->options),
                    'total' => (int) ($this->total ?? 0),
                    'updated' => (int) ($this->updated ?? 0),
                    'created' => (int) ($this->created ?? 0),
                    'noteupdated' => $this->noteUpdated !== null ? (int) $this->noteUpdated : null,
                    'erros' => 0,
                    'errosMSGs' => [],
                    'date_inicio' => $startAt->toDateTimeString(),
                ]);

                $this->last_id = $row->id;
            });
        } catch (Throwable $e) {
            // Sem interromper o script principal por falha de log
            $this->last_id = null;
        }
    }

    private function normalizeOptions($options)
    {
        if (is_array($options)) {
            return $options;
        }

        if (is_object($options)) {
            return (array) $options;
        }

        if ($options === null || $options === '') {
            return null;
        }

        return ['value' => $options];
    }

    private function normalizeFailReason(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }

        $reason = trim($reason);
        if ($reason === '') {
            return null;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($length <= self::FAIL_REASON_SAFE_MAX) {
            return $reason;
        }

        $slice = function_exists('mb_substr')
            ? mb_substr($reason, 0, self::FAIL_REASON_SAFE_MAX - 13)
            : substr($reason, 0, self::FAIL_REASON_SAFE_MAX - 13);

        return $slice . '...(truncado)';
    }
}
