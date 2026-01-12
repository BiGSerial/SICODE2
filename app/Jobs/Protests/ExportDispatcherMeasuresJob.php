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
use Illuminate\Support\Facades\Storage;

class ExportDispatcherMeasuresJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public function __construct(
        protected array $filters,
        protected string $userId
    ) {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $filePath = 'exports/protests/' . now()->format('YmdHis') . '_medidas_mede.xlsx';

        try {
            (new DispatcherMeasuresExport($this->filters))->store($filePath, 'local');

            if (! Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado no disco configurado.');
            }

            $user->notify(new SystemNotification(
                titulo: 'Exportacao concluida!',
                mensagem: 'O relatorio de medidas MEDE foi gerado e esta disponivel para download.',
                link: Storage::url($filePath),
                status: 4,
                extras: []
            ));
        } catch (\Throwable $e) {
            $this->notifyFailure($e->getMessage());
            report($e);
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
