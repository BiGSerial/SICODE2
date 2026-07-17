<?php

namespace App\Jobs\Services;

use App\Exports\Oexterno\ReleasedWorksExport;
use App\Models\ExternalOrganRelease;
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
use Throwable;

class ExportExternalOrganReleasedWorksJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<int, int> */
    public array $releaseIds;
    public string $userId;

    public $tries = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200;

    /**
     * @param array<int, int> $releaseIds
     */
    public function __construct(array $releaseIds, string $userId)
    {
        $this->onQueue('exports');
        $this->releaseIds = array_values(array_unique(array_map('intval', $releaseIds)));
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;

        try {
            if (!count($this->releaseIds)) {
                throw new \RuntimeException('Nenhum registro informado para exportação.');
            }

            $stamp = now()->format('YmdHis');
            $filePath = "exports/obras_liberadas_oe_{$stamp}.xlsx";

            Storage::disk('local')->makeDirectory('exports');
            Excel::store(new ReleasedWorksExport($this->releaseIds), $filePath, 'local');

            if (!$filePath || !Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            ExternalOrganRelease::query()
                ->whereIn('id', $this->releaseIds)
                ->whereNull('released_at')
                ->whereNull('exported_at')
                ->update([
                    'exported_at' => now(),
                    'exported_by' => $user?->id,
                    'updated_at' => now(),
                ]);

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportação - Obras Liberadas OE',
                    'Seu arquivo de Obras Liberadas para Órgão Externo está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (Throwable $exception) {
            Log::error('ExportExternalOrganReleasedWorksJob falhou', [
                'error_message' => $exception->getMessage(),
                'release_ids' => $this->releaseIds,
                'attempt' => $this->attempts(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro ao exportar Obras Liberadas OE',
                "Ocorreu um erro ao gerar o arquivo.\n" . $exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }
}
