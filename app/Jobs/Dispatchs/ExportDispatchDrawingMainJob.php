<?php

namespace App\Jobs\Dispatchs;

use App\Exports\DispatchDesenhoMain;
use App\Models\Service;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Dispatchs\DesignDispatchMainQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportDispatchDrawingMainJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;
    public string $userId;

    public $tries = 2;
    public $backoff = [60, 180];

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(DesignDispatchMainQueryService $queryService): void
    {
        $user = User::find($this->userId);
        $service = Service::where('uuid', $this->params['service_uuid'] ?? null)
            ->with('Status')
            ->first();

        if (!$user || !$service) {
            throw new \RuntimeException('Usuário ou serviço não encontrado para exportação.');
        }

        $disk = Storage::disk('local');
        $filePath = 'exports/' . now()->format('Ymd_His') . '_dispatch_desenho_' . $service->id . '.xlsx';
        $disk->makeDirectory(dirname($filePath));

        try {
            $query = $queryService->build($service, $user, $this->params);

            if (!empty($this->params['selected'])) {
                $query->whereIn('id', (array) $this->params['selected']);
            }

            $notes = $query->get();

            $stored = Excel::store(
                new DispatchDesenhoMain($notes, $service->uuid, $user->name),
                $filePath,
                'local'
            );

            if ($stored && $disk->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório da lista para desenho foi gerado com sucesso.',
                    Storage::url($filePath),
                    4,
                    ['total' => $notes->count()]
                ));

                return;
            }

            throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
        } catch (Throwable $e) {
            Log::error('ExportDispatchDrawingMainJob falhou', [
                'user_id' => $this->userId,
                'params' => $this->params,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do relatório da lista para desenho falhou após novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
