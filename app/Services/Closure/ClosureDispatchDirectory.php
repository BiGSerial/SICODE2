<?php

namespace App\Services\Closure;

use App\Models\{Company, User};
use Illuminate\Support\Facades\Cache;

/**
 * Empresas e usuários habilitados no serviço Encerramento (destinos válidos de despacho).
 *
 * `all()` é embutido na página do Despacho (cache curto) para o modal popular os selects sem ir ao servidor.
 * `company()`/`assignee()` fazem a checagem AO VIVO e são a única autoridade na confirmação: o cliente não é confiável.
 */
class ClosureDispatchDirectory
{
    public const CACHE_KEY = 'closure:dispatch-directory:v1';

    public const TTL = 600;

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<int, array{id: string, name: string, users: array<int, array{id: string, name: string}>}> */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            $users = $this->enabledUsers()->whereNotNull('company_id')->orderBy('name')->get(['id', 'name', 'company_id'])->groupBy('company_id');

            return Company::query()->whereIn('id', $users->keys())->orderBy('name')->get(['id', 'name'])
                ->map(fn (Company $company) => [
                    'id'    => (string) $company->id,
                    'name'  => (string) $company->name,
                    'users' => $users[$company->id]->map(fn (User $user) => ['id' => (string) $user->id, 'name' => (string) $user->name])->values()->all(),
                ])->values()->all();
        });
    }

    /** Empresa com ao menos um usuário habilitado no Encerramento (regra viva, sem cache). */
    public function company(?string $companyId): ?Company
    {
        if (!$companyId) {
            return null;
        }

        return Company::query()
            ->whereKey($companyId)
            ->whereHas('toUsers', fn ($users) => $users->whereRelation('ToServices', fn ($q) => $q->where('service_id', ClosureServiceLocator::uuid())->where('service', true)))
            ->first();
    }

    /** Usuário habilitado no Encerramento e pertencente à empresa informada (regra viva, sem cache). */
    public function assignee(?string $userId, Company $company): ?User
    {
        if (!$userId) {
            return null;
        }

        return $this->enabledUsers()->whereKey($userId)->where('company_id', $company->id)->first();
    }

    private function enabledUsers()
    {
        return User::query()->whereRelation('ToServices', fn ($q) => $q->where('service_id', ClosureServiceLocator::uuid())->where('service', true));
    }
}
