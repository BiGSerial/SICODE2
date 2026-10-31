<?php

namespace App\Http\Livewire\Config\System;

use App\Services\System\QueueMonitorService;
use Illuminate\Support\Facades\{Artisan, DB, Gate};
use Livewire\Component;
use Symfony\Component\Process\Process;
use Throwable;

class JobsServices extends Component
{
    /** Confirmação em duas etapas do "Excluir tudo" dos falhados (sem JS). */
    public bool $confirmingDeleteAll = false;

    public function render(QueueMonitorService $monitor)
    {
        return view('livewire.config.system.jobs-services', [
            'snap' => $monitor->snapshot(),
        ]);
    }

    /* ========== Ações ========== */

    public function restartJob($failedId): void
    {
        $job = DB::table('failed_jobs')->find($failedId);

        if (!$job) {
            session()->flash('error', 'Job falhado não encontrado.');

            return;
        }

        DB::transaction(function () use ($job, $failedId) {
            DB::table('failed_jobs')->where('id', $failedId)->delete();
            $this->requeue($job);
        });

        session()->flash('message', "Job #{$failedId} reenfileirado.");
    }

    public function deleteFailed($failedId): void
    {
        DB::table('failed_jobs')->where('id', $failedId)->delete();
        session()->flash('message', "Job falhado #{$failedId} excluído.");
    }

    public function retryAllFailed(): void
    {
        $count = 0;

        DB::table('failed_jobs')->select('id', 'queue', 'payload')->orderBy('id')->chunkById(500, function ($chunk) use (&$count) {
            DB::transaction(function () use ($chunk, &$count) {
                foreach ($chunk as $job) {
                    $this->requeue($job);
                    $count++;
                }
                DB::table('failed_jobs')->whereIn('id', $chunk->pluck('id'))->delete();
            });
        });

        session()->flash('message', "{$count} job(s) falhado(s) reenfileirado(s).");
    }

    public function askDeleteAllFailed(): void
    {
        $this->confirmingDeleteAll = true;
    }

    public function cancelDeleteAllFailed(): void
    {
        $this->confirmingDeleteAll = false;
    }

    public function deleteAllFailed(): void
    {
        abort_unless(Gate::allows('superadm'), 403);

        $this->confirmingDeleteAll = false;
        $count                     = DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->delete();

        session()->flash('message', "{$count} job(s) falhado(s) excluído(s).");
    }

    public function restartAll(): void
    {
        abort_unless(Gate::allows('superadm'), 403);

        Artisan::call('queue:restart');
        session()->flash('message', 'queue:restart enviado. Os workers reiniciam ao terminar o job atual.');
    }

    public function restartWorker(string $name, QueueMonitorService $monitor): void
    {
        abort_unless(Gate::allows('superadm'), 403);

        $worker = collect($monitor->supervisorWorkers())->firstWhere('name', $name);

        if (!$worker) {
            session()->flash('error', 'Worker não encontrado no supervisorctl status.');

            return;
        }

        $process = new Process(['supervisorctl', 'restart', $name]);
        $process->setTimeout(20);

        try {
            $process->run();
        } catch (Throwable $e) {
            session()->flash('error', 'Falha ao executar supervisorctl: ' . $e->getMessage());

            return;
        }

        $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());

        $process->isSuccessful()
            ? session()->flash('message', "Restart enviado para {$worker['process']}. {$output}")
            : session()->flash('error', "Falha ao reiniciar {$worker['process']}. " . ($output ?: 'Sem retorno do supervisorctl.'));
    }

    private function requeue(object $failed): void
    {
        DB::table('jobs')->insert([
            'queue'        => $failed->queue,
            'payload'      => $failed->payload,
            'attempts'     => 0,
            'reserved_at'  => null,
            'available_at' => now()->timestamp,
            'created_at'   => now()->timestamp,
        ]);
    }
}
