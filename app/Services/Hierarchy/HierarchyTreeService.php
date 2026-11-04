<?php

namespace App\Services\Hierarchy;

use App\Models\{User, UserDelegation, UserObservation};
use App\Services\HierarchyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dados da tela de Hierarquia de usuários (Administração → Hierarquia). Tela e testes leem daqui.
 * A árvore sai plana e leve (uma linha por usuário); o navegador monta, expande e busca sem ida ao servidor.
 * Docs: docs/admin/HIERARQUIA.md.
 */
class HierarchyTreeService
{
    /**
     * Uma linha por usuário ativo: i=id, n=nome, e=e-mail, m=gestor, c=empresa.
     *
     * @return array<int, array{i:string,n:string,e:?string,m:?string,c:?string,x:int}>
     */
    public function nodes(): array
    {
        // `x` = tamanho da equipe na user_closure: desempata quem fica no topo quando users.manager_id forma um ciclo.
        $team = DB::table('user_closure')->where('depth', '>', 0)->select('ancestor_id', DB::raw('COUNT(*) as total'))->groupBy('ancestor_id')->pluck('total', 'ancestor_id');

        return User::query()
            ->leftJoin('companies', 'companies.id', '=', 'users.company_id')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email', 'users.manager_id', 'companies.name as company'])
            ->map(fn ($u) => [
                'i' => (string) $u->id,
                'n' => (string) $u->name,
                'e' => $u->email,
                'm' => $u->manager_id ? (string) $u->manager_id : null,
                'c' => $u->company,
                'x' => (int) ($team[$u->id] ?? 0),
            ])
            ->all();
    }

    /**
     * Coberturas vigentes agora: titular → delegado (quem cobre quem) e observações por observador.
     *
     * @return array{covers: array<string, array{d:string,until:?string}>, observers: array<string,int>}
     */
    public function badges(): array
    {
        $covers = [];

        foreach ($this->activeDelegations() as $row) {
            $covers[(string) $row->principal_id] = [
                'd'     => (string) $row->delegate_id,
                'until' => $row->valid_to?->format('d/m/Y'),
            ];
        }

        $observers = UserObservation::query()->active()
            ->select('observer_id', DB::raw('COUNT(*) as total'))
            ->groupBy('observer_id')
            ->pluck('total', 'observer_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return ['covers' => $covers, 'observers' => $observers, 'links' => $this->relationLinks()];
    }

    /**
     * Relações além do gestor direto, para desenhar como linhas no organograma: delegação (titular → quem cobre) e
     * observação (observador → observado), vigentes ou agendadas. k=cover|obs, s=agendada, u=fim, m=alcance da observação.
     *
     * @return array<int, array{f:string,t:string,k:string,s:bool,u:?string,m:?string}>
     */
    public function relationLinks(): array
    {
        $open = fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());

        $cover = UserDelegation::query()->where($open)->get(['principal_id', 'delegate_id', 'valid_from', 'valid_to'])
            ->map(fn ($r) => ['f' => (string) $r->principal_id, 't' => (string) $r->delegate_id, 'k' => 'cover', 's' => $r->valid_from->isFuture(), 'u' => $r->valid_to?->format('d/m/Y'), 'm' => null]);

        $obs = UserObservation::query()->where($open)->get(['observer_id', 'target_id', 'mode', 'valid_from', 'valid_to'])
            ->map(fn ($r) => ['f' => (string) $r->observer_id, 't' => (string) $r->target_id, 'k' => 'obs', 's' => $r->valid_from->isFuture(), 'u' => $r->valid_to?->format('d/m/Y'), 'm' => $r->mode]);

        return $cover->concat($obs)->values()->all();
    }

    /** Delegações vigentes agora (data inicial já chegou e ainda não terminaram). */
    public function activeDelegations(): Collection
    {
        return UserDelegation::query()->active()->get(['principal_id', 'delegate_id', 'reason', 'valid_from', 'valid_to']);
    }

    /**
     * Painel do usuário selecionado: cadeia de gestores, equipe direta, tamanho total e coberturas/observações.
     */
    public function detail(string $userId): ?array
    {
        $user = User::query()->with('company:id,name')->find($userId);

        if (!$user) {
            return null;
        }

        $chain = DB::table('user_closure as uc')
            ->join('users', 'users.id', '=', 'uc.ancestor_id')
            ->where('uc.descendant_id', $userId)
            ->where('uc.depth', '>', 0)
            ->whereNull('users.deleted_at')
            ->orderByDesc('uc.depth')
            ->get(['users.id', 'users.name']);

        $directs = User::query()->where('manager_id', $userId)->orderBy('name')->get(['id', 'name', 'email']);

        $teamSize = (int) DB::table('user_closure as uc')
            ->join('users', 'users.id', '=', 'uc.descendant_id')
            ->where('uc.ancestor_id', $userId)
            ->where('uc.depth', '>', 0)
            ->whereNull('users.deleted_at')
            ->count();

        $delegations = UserDelegation::query()
            ->where(fn ($q) => $q->where('principal_id', $userId)->orWhere('delegate_id', $userId))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->with(['principal:id,name', 'delegate:id,name'])
            ->orderBy('valid_from')
            ->limit(50)
            ->get();

        $observations = UserObservation::query()
            ->where(fn ($q) => $q->where('observer_id', $userId)->orWhere('target_id', $userId))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->with(['observer:id,name', 'target:id,name'])
            ->orderBy('valid_from')
            ->limit(50)
            ->get();

        return [
            'user'         => $user,
            'chain'        => $chain,
            'directs'      => $directs,
            'teamSize'     => $teamSize,
            'delegations'  => $delegations,
            'observations' => $observations,
        ];
    }

    /**
     * Visão geral "quem cobre quem": delegações e observações vigentes ou agendadas, com a situação de cada uma.
     *
     * @return array{delegations: Collection, observations: Collection}
     */
    public function coverageOverview(): array
    {
        $open = fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());

        return [
            'delegations'  => UserDelegation::query()->where($open)->with(['principal:id,name', 'delegate:id,name'])->orderBy('valid_from')->limit(300)->get(),
            'observations' => UserObservation::query()->where($open)->with(['observer:id,name', 'target:id,name'])->orderBy('valid_from')->limit(300)->get(),
        ];
    }

    /**
     * Resumo do que muda ao mover $userId para baixo de $targetId (null = vira raiz). Não grava nada.
     *
     * @return array{ok:bool, error:?string, user:?string, target:?string, subtree:int, from:array<int,string>, to:array<int,string>}
     */
    public function impact(string $userId, ?string $targetId): array
    {
        $user = User::query()->find($userId, ['id', 'name', 'manager_id']);
        $out  = ['ok' => false, 'error' => null, 'user' => $user?->name, 'target' => null, 'subtree' => 0, 'from' => [], 'to' => []];

        if (!$user) {
            return ['error' => 'Usuário não encontrado.'] + $out;
        }

        $target = $targetId ? User::query()->find($targetId, ['id', 'name']) : null;

        if ($targetId && !$target) {
            return ['error' => 'Novo gestor não encontrado.'] + $out;
        }
        $out['target'] = $target?->name;

        if ($targetId === $userId) {
            return ['error' => 'Um usuário não pode gerenciar a si mesmo.'] + $out;
        }

        if ($targetId && DB::table('user_closure')->where('ancestor_id', $userId)->where('descendant_id', $targetId)->exists()) {
            return ['error' => 'O novo gestor faz parte da equipe de ' . $user->name . '; isso criaria um ciclo.'] + $out;
        }

        if ((string) $user->manager_id === (string) $targetId) {
            return ['error' => 'Já está sob este gestor.'] + $out;
        }

        $out['subtree'] = (int) DB::table('user_closure')->where('ancestor_id', $userId)->where('depth', '>', 0)->count();
        $out['from']    = $this->chainNames($userId);
        $out['to']      = $targetId ? array_merge($this->chainNames($targetId), [$target->name]) : [];
        $out['ok']      = true;

        return $out;
    }

    /**
     * Move vários usuários (cada um com sua equipe) para baixo de $targetId.
     *
     * @return array{moved:int, errors:array<int,string>}
     */
    public function move(array $userIds, ?string $targetId): array
    {
        $service = app(HierarchyService::class);
        $moved   = 0;
        $errors  = [];

        foreach (array_unique($userIds) as $uid) {
            $check = $this->impact((string) $uid, $targetId);

            if (!$check['ok']) {
                $errors[] = ($check['user'] ?? 'Usuário') . ': ' . $check['error'];

                continue;
            }

            try {
                $service->moveSubtree((string) $uid, $targetId);
                $moved++;
            } catch (\Throwable $e) {
                $errors[] = ($check['user'] ?? 'Usuário') . ': ' . $e->getMessage();
            }
        }

        return ['moved' => $moved, 'errors' => $errors];
    }

    /** Nomes dos gestores acima de $userId, do topo até o gestor direto. */
    private function chainNames(string $userId): array
    {
        return DB::table('user_closure as uc')
            ->join('users', 'users.id', '=', 'uc.ancestor_id')
            ->where('uc.descendant_id', $userId)
            ->where('uc.depth', '>', 0)
            ->orderByDesc('uc.depth')
            ->pluck('users.name')
            ->all();
    }
}
