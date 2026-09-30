<?php

namespace App\Services\Quality;

use App\Models\{QualityMember, QualityProcess, QualityStage, User};
use Illuminate\Database\Eloquent\Builder;

/**
 * Único ponto que define os papéis do domínio Qualidade.
 *
 * - Gestão (`management`, `admin`, `superadm`): Pool, despacho, configuração; vê todas as empresas; atua como N2.
 * - N1 / N2: usuários cadastrados em `quality_members` POR EMPRESA (Qualidade > Equipe). Só enxergam as empresas em que são membros.
 * - Usuário: não é papel de usuário — é quem foi designado numa etapa de execução.
 */
class QualityRoles
{
    /**
     * Quem tem flag de gestão E também é N1/N2 cadastrado atua em um "modo" por vez (Gestão ou Equipe), para poder
     * enxergar a Qualidade exatamente como o N1/N2 enxerga. Em visão de outro usuário (impersonate) o padrão é a Equipe.
     */
    public function viewMode(User $user): string
    {
        if (!$this->hasManagementFlags($user) || !$this->hasAnyMembership($user)) {
            return $this->hasManagementFlags($user) ? 'manager' : 'member';
        }

        // Em visão de outro usuário (impersonate) a Gestão fica totalmente oculta: vê só o que o N1/N2 vê.
        if (session('impersonate')) {
            return 'member';
        }

        return session('quality.view_as', 'manager') === 'member' ? 'member' : 'manager';
    }

    /** O usuário alterna entre os dois modos? */
    public function isDualMode(User $user): bool
    {
        return !session('impersonate') && $this->hasManagementFlags($user) && $this->hasAnyMembership($user);
    }

    public function isSupport(User $user): bool
    {
        return (bool) ($user->superadm || $user->admin) && $this->viewMode($user) === 'manager';
    }

    /** Pool, despacho ao N1, motivos, critérios, atividades e equipe. */
    public function canManage(User $user): bool
    {
        return $this->hasManagementFlags($user) && $this->viewMode($user) === 'manager';
    }

    private function hasManagementFlags(User $user): bool
    {
        return (bool) ($user->superadm || $user->admin || $user->management);
    }

    private function hasAnyMembership(User $user): bool
    {
        return QualityMember::query()->where('user_id', $user->id)->where('active', true)->exists();
    }

    public function canDispatch(User $user): bool
    {
        return $this->canManage($user);
    }

    public function isN1Member(User $user): bool
    {
        return $this->membership($user, QualityMember::N1)->exists();
    }

    public function isN2Member(User $user): bool
    {
        return $this->membership($user, QualityMember::N2)->exists();
    }

    public function isN1(User $user): bool
    {
        return $this->isSupport($user) || $this->isN1Member($user);
    }

    public function isN2(User $user): bool
    {
        return $this->canManage($user) || $this->isN2Member($user);
    }

    public function canAccess(User $user): bool
    {
        return $this->canManage($user) || $this->hasAnyMembership($user);
    }

    /** @return array<int, string> empresas em que o usuário é membro (opcionalmente de um papel). */
    public function companyIds(User $user, ?string $role = null): array
    {
        return QualityMember::query()->where('user_id', $user->id)->where('active', true)
            ->when($role, fn ($q) => $q->where('role', $role))->pluck('company_id')->unique()->values()->all();
    }

    public function seesAllCompanies(User $user): bool
    {
        return $this->canManage($user);
    }

    public function roleLabel(User $user): string
    {
        return match (true) {
            $this->canManage($user)                              => 'Gestão',
            $this->isN1Member($user) && $this->isN2Member($user) => 'N1 e N2',
            $this->isN2Member($user)                             => 'N2',
            $this->isN1Member($user)                             => 'N1',
            default                                              => 'Usuário',
        };
    }

    /** Empresas do usuário, para exibir na lateral. */
    public function companyNames(User $user): string
    {
        return QualityMember::query()->with('Company')->where('user_id', $user->id)->where('active', true)->get()
            ->pluck('Company.name')->filter()->unique()->implode(', ');
    }

    public function canViewProcess(User $user, QualityProcess $process): bool
    {
        if ($this->canManage($user) || in_array($process->company_id, $this->companyIds($user), true)) {
            return true;
        }

        return $process->Stages()->where('assigned_user_id', $user->id)->exists();
    }

    public function canActAsN1(User $user, QualityProcess $process): bool
    {
        return $this->isSupport($user) || $this->isMember($user, QualityMember::N1, $process->company_id);
    }

    public function canActAsN2(User $user, QualityProcess $process): bool
    {
        return $this->canManage($user) || $this->isMember($user, QualityMember::N2, $process->company_id);
    }

    public function canActAsDesigner(User $user, QualityStage $stage): bool
    {
        return $stage->assigned_user_id !== null && (string) $stage->assigned_user_id === (string) $user->id;
    }

    public function scopeVisible(Builder $query, User $user): Builder
    {
        if ($this->canManage($user)) {
            return $query;
        }

        $companies = $this->companyIds($user);

        return $query->where(function ($q) use ($companies, $user) {
            $q->whereIn('company_id', $companies)
                ->orWhereHas('Stages', fn ($stages) => $stages->where('assigned_user_id', $user->id));
        });
    }

    private function isMember(User $user, string $role, string $companyId): bool
    {
        return $this->membership($user, $role)->where('company_id', $companyId)->exists();
    }

    private function membership(User $user, string $role): Builder
    {
        return QualityMember::query()->where('user_id', $user->id)->where('role', $role)->where('active', true);
    }
}
