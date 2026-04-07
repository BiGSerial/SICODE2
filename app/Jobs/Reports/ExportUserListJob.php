<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\UserListExport;
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

class ExportUserListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $params;

    public string $userId;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 1200;

    private array $allowedRoleFilters = [
        'superadm',
        'admin',
        'management',
        'engineer',
        'responsible',
        'operator',
        'user',
        'onlyparner',
        'analyst',
    ];

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = array_merge([
            'search' => null,
            'searchBy' => 'all',
            'selectedCompany' => null,
            'multiSearch' => [],
            'statusFilter' => 'all',
            'deletedFilter' => 'active',
            'roleFilter' => '',
        ], $params);

        $this->userId = $userId;
    }

    public function handle(): void
    {
        $requestUser = User::find($this->userId);
        $filePath = null;
        $disk = Storage::disk('local');

        if (!$requestUser) {
            return;
        }

        try {
            $stamp = now()->format('YmdHis');
            $filePath = "exports/user_list_{$stamp}.xlsx";
            $disk->makeDirectory('exports');

            $users = $this->buildQuery($requestUser)->get();

            Excel::store(new UserListExport($users), $filePath, 'local');

            if (!$disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado.');
            }

            $requestUser->notify(new SystemNotification(
                'Exportacao de Usuarios',
                'Seu arquivo de usuarios foi gerado e esta pronto para download.',
                Storage::url($filePath),
                4,
                []
            ));
        } catch (\Throwable $exception) {
            Log::error('ExportUserListJob falhou', [
                'error_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'params' => $this->params,
                'attempt' => $this->attempts(),
            ]);

            if ($filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro ao gerar exportacao de usuarios',
                "Ocorreu um erro ao gerar o arquivo.\n".$exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }

    private function buildQuery(User $viewer)
    {
        return User::query()
            ->when(
                $viewer->contract,
                function ($q) use ($viewer) {
                    if ($viewer->Companies->count()) {
                        return $q->whereRelation('Employee.Contract.company', function ($sq) use ($viewer) {
                            return $sq->whereIn('id', $viewer->Companies->pluck('id'));
                        });
                    }

                    if (isset($viewer->Employee->Contract->company->id)) {
                        return $q->whereRelation('Employee.Contract.company', function ($sq) use ($viewer) {
                            return $sq->whereIn('id', [$viewer->Employee->Contract->company->id]);
                        });
                    }

                    return $q->whereRaw('1 = 0');
                }
            )
            ->withTrashed()
            ->when(($this->params['deletedFilter'] ?? 'active') === 'active', function ($q) {
                return $q->whereNull('users.deleted_at');
            })
            ->when(($this->params['deletedFilter'] ?? 'active') === 'deleted', function ($q) {
                return $q->onlyTrashed();
            })
            ->when($this->params['search'] ?? null, function ($q, $s) {
                return $q->where(function ($searchQuery) use ($s) {
                    $term = trim((string) $s);
                    $like = '%'.$term.'%';
                    $searchBy = (string) ($this->params['searchBy'] ?? 'all');

                    if ($searchBy === 'email') {
                        return $searchQuery->where('email', 'like', $like);
                    }

                    if ($searchBy === 'registration') {
                        return $searchQuery->where('Registration', 'like', $like);
                    }

                    if ($searchBy === 'id') {
                        return $searchQuery->where('id', 'like', $like);
                    }

                    return $searchQuery->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('Registration', 'like', $like)
                        ->orWhere('id', 'like', $like);
                });
            })
            ->when($this->params['selectedCompany'] ?? null, function ($q, $companyId) {
                return $q->whereRelation('Employee.Contract', 'company_id', $companyId);
            })
            ->when($this->params['multiSearch'] ?? null, function ($q) {
                $multiSearch = (array) ($this->params['multiSearch'] ?? []);

                return $q->where(function ($multiQuery) use ($multiSearch) {
                    $multiQuery->whereIn('id', $multiSearch)
                        ->orWhereIn('email', $multiSearch)
                        ->orWhereIn('Registration', $multiSearch);
                });
            })
            ->when(($this->params['statusFilter'] ?? 'all') === 'online', function ($q) {
                return $q->whereRelation('Watchdog', 'watchdog', true);
            })
            ->when(($this->params['statusFilter'] ?? 'all') === 'offline', function ($q) {
                return $q->where(function ($offlineQuery) {
                    $offlineQuery->whereDoesntHave('Watchdog')
                        ->orWhereRelation('Watchdog', 'watchdog', false);
                });
            })
            ->when(in_array((string) ($this->params['roleFilter'] ?? ''), $this->allowedRoleFilters, true), function ($q) {
                $role = (string) $this->params['roleFilter'];
                return $q->where($role, true);
            })
            ->with('Employee.Contract.Company', 'Watchdog', 'ToServices.Service')
            ->orderBy('name');
    }
}
