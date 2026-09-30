<?php

namespace App\Services\Quality;

use App\Enum\{QualityProcessState as S, QualityProcessStatus, QualityStageType as Phase};
use App\Models\{QualityProcess, User};
use Illuminate\Database\Eloquent\Builder;

/** Filas operacionais por papel. Cada fila é só um filtro sobre `state` + `phase` (sem estado próprio). */
class QualityQueues
{
    public function __construct(private readonly QualityRoles $roles)
    {
    }

    private function asN2(User $user): bool
    {
        return $this->roles->isN2($user) && !$this->roles->isN1Member($user);
    }

    /** @return array<string, array{label:string, hint:string, apply:callable}> */
    public function definitions(User $user): array
    {
        $state = fn (array $states, ?Phase $phase = null) => function (Builder $q) use ($states, $phase) {
            $q->active()->whereIn('state', array_map(fn (S $s) => $s->value, $states))->when($phase, fn ($q) => $q->where('phase', $phase->value));
        };

        $mine = function (Builder $q) use ($user) {
            $states = $this->asN2($user)
                ? [S::AWAITING_N2_REVIEW, S::CLOSING, S::SAP_FAILED]
                : [S::AWAITING_N1_DISPATCH, S::N2_RETURNED, S::AWAITING_N1_REVIEW];
            $q->active()->whereIn('state', array_map(fn (S $s) => $s->value, $states));
        };

        $all = [
            'minha-fila'          => ['Minha fila', 'Itens que dependem de você agora.', $mine],
            'em-andamento'        => ['Em andamento', 'Todos os processos ativos do seu escopo.', fn (Builder $q) => $q->active()],
            'aguardando-despacho' => ['Aguardando despacho ao usuário', 'O N1 precisa escolher o usuário da atividade.', $state([S::AWAITING_N1_DISPATCH])],
            'ciclo1-analise'      => ['1º ciclo · aguardando análise do N1', '1º ciclo aguardando análise do N1.', $state([S::AWAITING_N1_REVIEW], Phase::PROJECT)],
            'ciclo1-usuario'      => ['1º ciclo · com o usuário', '1º ciclo com o usuário.', $state([S::AWAITING_DESIGNER], Phase::PROJECT)],
            'ciclo2-analise'      => ['2º ciclo · aguardando análise do N1', '2º ciclo aguardando análise do N1.', $state([S::AWAITING_N1_REVIEW], Phase::BUDGET)],
            'ciclo2-usuario'      => ['2º ciclo · com o usuário', '2º ciclo com o usuário.', $state([S::AWAITING_DESIGNER], Phase::BUDGET)],
            'devolvidos-n2'       => ['Devolvidos pelo N2', 'O N2 devolveu: encaminhe ao usuário ou questione o N2.', $state([S::N2_RETURNED])],
            'encaminhados-n2'     => ['Encaminhados ao N2', 'Aprovados pelo N1, aguardando o N2.', $state([S::AWAITING_N2_REVIEW])],
            'aguardando-n2'       => ['Aguardando N2', 'Análise do N2 e encerramentos pendentes.', $state([S::AWAITING_N2_REVIEW, S::CLOSING, S::SAP_FAILED])],
            'aguardando-usuário'  => ['Aguardando usuário', 'Atividades com os usuários.', $state([S::AWAITING_DESIGNER])],
            'rejeitados'          => ['Com rejeições', 'Processos que já tiveram rejeição.', fn (Builder $q) => $q->whereHas('Rejections')],
            'concluidos'          => ['Concluídos', 'Processos encerrados.', fn (Builder $q) => $q->where('status', QualityProcessStatus::COMPLETED->value)],
        ];

        $allowed = $this->asN2($user)
            ? ['minha-fila', 'em-andamento', 'aguardando-n2', 'aguardando-despacho', 'aguardando-usuário', 'devolvidos-n2', 'rejeitados', 'concluidos']
            : ['minha-fila', 'aguardando-despacho', 'ciclo1-analise', 'ciclo1-usuario', 'ciclo2-analise', 'ciclo2-usuario', 'devolvidos-n2', 'encaminhados-n2', 'em-andamento', 'concluidos'];

        $result = [];

        foreach ($allowed as $key) {
            [$label, $hint, $apply] = $all[$key];
            $result[$key]           = ['label' => $label, 'hint' => $hint, 'apply' => $apply];
        }

        return $result;
    }

    public function apply(Builder $query, User $user, ?string $key): array
    {
        $definitions = $this->definitions($user);
        $key         = isset($definitions[$key]) ? $key : 'minha-fila';
        ($definitions[$key]['apply'])($query);

        return [$key, $definitions[$key]];
    }

    /** @return array<string,int> */
    public function counts(User $user): array
    {
        $counts = [];

        foreach ($this->definitions($user) as $key => $definition) {
            $query = QualityProcess::query()->visibleTo($user);
            ($definition['apply'])($query);
            $counts[$key] = $query->count();
        }

        return $counts;
    }
}
