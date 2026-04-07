<?php

namespace App\Jobs\Protests;

use App\Exports\Protests\ProtestJobsExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportProtestJobsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;
    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct(
        protected array $filters,
        protected string $userId
    ) {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $filePath = 'exports/protests/' . now()->format('YmdHis') . '_protest_jobs.xlsx';
        $disk = Storage::disk('local');

        try {
            $disk->makeDirectory('exports/protests');
            (new ProtestJobsExport($this->filters))->store($filePath, 'local');

            if (! $disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado no disco configurado.');
            }

            $user->notify(new SystemNotification(
                titulo: 'Exportação concluída!',
                mensagem: 'O relatório de atividades do Protest foi gerado e está disponível para download.',
                link: Storage::url($filePath),
                status: 4,
                extras: []
            ));
        } catch (\Throwable $e) {
            Log::error('ExportProtestJobsJob falhou', [
                'user_id' => $this->userId,
                'filters' => $this->filters,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->notifyFailure($exception->getMessage());
    }

    protected function notifyFailure(string $message): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                titulo: 'Erro na exportação',
                mensagem: 'Não foi possível gerar o relatório solicitado. ' . $message,
                link: null,
                status: 5,
                extras: []
            ));
        }
    }
}
