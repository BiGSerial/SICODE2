<?php

namespace App\Jobs\Ads;

use App\Enum\AdsRequestStatus;
use App\Exports\Ads\AdsRequestsHistoryExport;
use App\Models\AdsRequest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportAdsRequestsHistoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $filters;
    public string $userId;
    public string $scope;

    public $tries   = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200; // 20 min

    public function __construct(array $filters, string $userId, string $scope = 'partner')
    {
        $this->onQueue('exports');
        $this->filters = $filters;
        $this->userId = $userId;
        $this->scope = $scope;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;
        $disk = Storage::disk('local');

        if (!$user) {
            return;
        }

        try {
            $query = AdsRequest::query()
                ->with(['note', 'company', 'requestedBy'])
                ->whereIn('status', [
                    AdsRequestStatus::DONE->value,
                    AdsRequestStatus::FAILED->value,
                    AdsRequestStatus::CANCELED->value,
                ]);

            if ($this->scope === 'partner') {
                if (!$user->superadm) {
                    $query->where('requested_by', $user->id);
                }
            } else {
                if (!$user->superadm) {
                    $visibleUserIds = $user->descendantsQuery(true)->pluck('users.id');
                    $query->whereIn('requested_by', $visibleUserIds);
                }
            }

            $search = trim((string) ($this->filters['search'] ?? ''));
            if ($search !== '') {
                $query->whereHas('note', function ($q) use ($search) {
                    $q->where('note', 'like', '%' . $search . '%');
                });
            }

            if (!empty($this->filters['company_id'])) {
                $query->where('company_id', $this->filters['company_id']);
            }

            if (!empty($this->filters['start'])) {
                $query->whereDate('created_at', '>=', $this->filters['start']);
            }

            if (!empty($this->filters['end'])) {
                $query->whereDate('created_at', '<=', $this->filters['end']);
            }

            $filePath = 'exports/' . now()->format('YmdHis') . '-ads-requests-history.xlsx';
            $disk->makeDirectory('exports');

            $stored = (new AdsRequestsHistoryExport($query))->store($filePath, 'local');

            if ($stored && $disk->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportacao concluida!',
                    'O historico de solicitacoes ADS esta pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo nao foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportAdsRequestsHistoryJob falhou', [
                'user_id' => $this->userId,
                'filters' => $this->filters,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro na exportacao',
                'Nao foi possivel gerar o historico de solicitacoes ADS no momento.',
                null,
                5,
                []
            ));
        }
    }
}
