<?php

namespace App\Jobs\Protests;

use App\Exports\Protests\DispatcherMeasuresExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportDispatcherMeasuresJob implements ShouldQueue
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

        $filePath = 'exports/protests/' . now()->format('YmdHis') . '_medidas_mede.xlsx';
        $disk = Storage::disk('local');

        try {
            $disk->makeDirectory('exports/protests');
            (new DispatcherMeasuresExport($this->filters))->store($filePath, 'local');

            if (! $disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado no disco configurado.');
            }

            $user->notify(new SystemNotification(
                titulo: 'Exportacao concluida!',
                mensagem: 'O relatorio de reclamacao (base MEDE) foi gerado e esta disponivel para download.',
                link: Storage::url($filePath),
                status: 4,
                extras: []
            ));
        } catch (\Throwable $e) {
            Log::error('ExportDispatcherMeasuresJob falhou', [
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
                titulo: 'Erro na exportacao',
                mensagem: 'Nao foi possivel gerar o relatorio solicitado. ' . $message,
                link: null,
                status: 5,
                extras: []
            ));
        }
    }
}
