<?php

namespace App\Services\PartnerAccess;

use App\Models\{Company, PartnerAdminAuditEvent, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{DB, Hash};

/**
 * Gerência dos usuários de uma empresa terceira (e das filiais dela) pelo administrador da própria empresa. Uma só implementação para a
 * tela de usuários (Livewire) e para o controller (edição, ações em massa): escopo, regras de proteção e auditoria ficam aqui.
 *
 * Regras:
 *  - O alvo é sempre um usuário de parceira (`onlyparner`) da empresa gerenciada ou de uma filial dela; nunca outro.
 *  - Ninguém desativa a si mesmo nem tira o próprio acesso de administrador.
 *  - Redefinir senha devolve a senha temporária e força a troca no próximo acesso (`first_pass`). Só em usuário ativo.
 *  - Desativar é reversível (soft delete); reativar devolve o acesso com a senha atual.
 *  - Toda ação grava um evento de auditoria da empresa.
 */
class PartnerUserManager
{
    public const TEMP_PASSWORD = '123456';

    public const ONLINE_MINUTES = 5;

    /** Em ação em massa, os eventos individuais são suprimidos: vale um só, com a lista de usuários. */
    private bool $quiet = false;

    public function __construct(private readonly User $actor)
    {
    }

    public function managedCompanyId(): string
    {
        $id = PartnerAccessGate::permissionCompanyIdFor($this->actor);

        abort_unless($id, 403);

        return $id;
    }

    /** Empresa gerenciada + filiais. @return array<int, string> */
    public function companyIds(): array
    {
        $id = $this->managedCompanyId();

        return Company::query()->where('id', $id)->orWhere('parent_id', $id)->pluck('id')->all();
    }

    public function companies()
    {
        return Company::query()->whereIn('id', $this->companyIds())->orderBy('name')->get(['id', 'parent_id', 'name']);
    }

    /** Usuários de parceira no escopo (ativos e desativados). */
    public function scope(): Builder
    {
        return User::withTrashed()->where('onlyparner', true)->whereIn('company_id', $this->companyIds());
    }

    public function find(string $userId, bool $trashed = false): User
    {
        $user = $this->scope()->whereKey($userId)->firstOrFail();

        abort_if($trashed !== $user->trashed(), 422, $trashed ? 'O usuário não está desativado.' : 'O usuário está desativado.');

        return $user;
    }

    public function resetPassword(string $userId): User
    {
        abort_unless(PartnerAccessGate::allows($this->actor, 'admin_users.update'), 403);

        $user = $this->find($userId);

        $user->forceFill(['password' => Hash::make(self::TEMP_PASSWORD), 'first_pass' => true])->save();
        $this->audit('reset_user_password', $user);

        return $user;
    }

    public function disable(string $userId): User
    {
        abort_unless(PartnerAccessGate::allows($this->actor, 'admin_users.disable'), 403);

        $user = $this->find($userId);

        abort_if($user->id === $this->actor->id, 403, 'Você não pode desativar o próprio acesso.');

        $user->delete();
        $this->audit('disabled_user', $user);

        return $user;
    }

    public function restore(string $userId): User
    {
        abort_unless(PartnerAccessGate::allows($this->actor, 'admin_users.disable'), 403);

        $user = $this->find($userId, true);

        $user->restore();
        $this->audit('restored_user', $user);

        return $user;
    }

    public function setAdmin(string $userId, bool $admin): User
    {
        abort_unless(PartnerAccessGate::allows($this->actor, 'admin_users.update'), 403);

        $user = $this->find($userId);

        abort_if(!$admin && $user->id === $this->actor->id, 403, 'Você não pode remover o próprio acesso de administrador.');

        $user->update(['admin' => $admin]);
        $this->audit($admin ? 'granted_admin' : 'revoked_admin', $user);

        return $user;
    }

    public function moveCompany(string $userId, string $companyId): User
    {
        abort_unless(PartnerAccessGate::allows($this->actor, 'admin_users.update'), 403);
        abort_unless(in_array($companyId, $this->companyIds(), true), 403);

        $user = $this->find($userId);

        $user->update(['company_id' => $companyId]);
        $user->Companies()->syncWithoutDetaching([$companyId]);
        $this->audit('moved_user_company', $user, ['company_id' => $companyId]);

        return $user;
    }

    /**
     * Ação em massa, em uma transação e com um único evento de auditoria.
     *
     * @param array<int, string> $ids
     */
    public function bulk(string $action, array $ids, ?string $companyId = null): int
    {
        abort_unless(in_array($action, ['make_admin', 'remove_admin', 'reset_password', 'move_company', 'disable', 'restore'], true), 422);
        abort_if($action === 'move_company' && !$companyId, 422, 'Selecione a empresa de destino.');

        $ids = array_values(array_unique($ids));

        abort_if(count($ids) === 0, 422);
        abort_if($this->scope()->whereIn('id', $ids)->count() !== count($ids), 403);

        DB::transaction(function () use ($action, $ids, $companyId) {
            // Um usuário só: vale o evento individual da própria ação. Vários: um evento único, com a lista.
            if (count($ids) === 1) {
                $this->applyBulk($action, $ids, $companyId);

                return;
            }

            $this->quiet = true;

            try {
                $this->applyBulk($action, $ids, $companyId);
            } finally {
                $this->quiet = false;
            }

            $this->audit('bulk_updated_users', null, ['action' => $action, 'users' => $ids, 'company_id' => $companyId]);
        });

        return count($ids);
    }

    private function applyBulk(string $action, array $ids, ?string $companyId): void
    {
        {
            foreach ($ids as $id) {
                match ($action) {
                    'make_admin'     => $this->setAdmin($id, true),
                    'remove_admin'   => $this->setAdmin($id, false),
                    'reset_password' => $this->resetPassword($id),
                    'move_company'   => $this->moveCompany($id, (string) $companyId),
                    'disable'        => $this->disable($id),
                    'restore'        => $this->restore($id),
                };
            }
        }
    }

    public function audit(string $event, ?User $target = null, array $payload = []): void
    {
        if ($this->quiet) {
            return;
        }

        PartnerAdminAuditEvent::query()->create([
            'company_id'     => $this->managedCompanyId(),
            'actor_user_id'  => $this->actor->id,
            'target_user_id' => $target?->id,
            'event_type'     => $event,
            'payload'        => $payload,
        ]);
    }
}
