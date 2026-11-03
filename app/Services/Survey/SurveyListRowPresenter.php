<?php

namespace App\Services\Survey;

use App\Custom\{Notestatus, WpaStatus};
use App\Helpers\DaysLeft;
use App\Models\{Note, Production};
use App\Services\Design\BlockEvaluator;
use App\Support\Ops\{RowState, Tone};
use Carbon\Carbon;

/**
 * Monta o que uma linha do Levantamento mostra (Despacho e Controle), já com tom semântico (padrão:
 * docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; regra de prazo/cor mora aqui.
 */
class SurveyListRowPresenter
{
    /** Prazo de execução do levantamento, em dias, contados da criação da nota (colunas do Controle). */
    public const EXECUTION_DAYS = 30;

    /** Dias após a atribuição para o aviso da coluna "Em atribuição" do Controle. */
    public const ATTRIBUTION_LIMIT = 9;

    /**
     * Lista operacional (Despacho). `$state` vem de `rowDispatchState()` (avaliação de bloqueio + pilha da empresa).
     *
     * @param array{eval: array, stack: ?Production, canDispatch: bool} $state
     */
    public function present(Note $list, array $state, string $serviceUuid, bool $partner = false): array
    {
        $eval       = $state['eval'];
        $stack      = $state['stack'];
        $production = $eval['production'] ?? null;

        $done     = $list->Productions->where('service_id', $serviceUuid)->where('completed', true);
        $daysLeft = (new DaysLeft($list))->getDaysLeft();

        $informes = [];

        if ($partner) {
            foreach (($list->WorkForms ?? collect()) as $workForm) {
                $informes[] = 'Informe #' . $workForm->id;
            }

            foreach (($list->Partials ?? collect()) as $partial) {
                $informes[] = 'Parcial #' . $partial->id;
            }
        }

        return [
            'key'       => (string) $list->id,
            'noteId'    => (int) ($list->note_id ?? $list->id),
            'id'        => (int) $list->id,
            'note'      => (string) $list->note,
            'express'   => (bool) $list->is45,
            'dd'        => \App\Support\SicodeRules::dispatchDdFor($list, $serviceUuid),
            'mmgd'      => (bool) $list->mmgd,
            'createdAt' => $list->dt_created ? Carbon::parse($list->dt_created) : null,
            'numPedido' => $list->numPedido ? mb_strtoupper($list->numPedido) : null,
            'rubrica'   => $list->rubrica,
            'city'      => $list->lexp,
            'groups'    => [
                'g1' => $list->group1,
                'g2' => $list->group2,
                'g4' => $list->group4,
                'g5' => $list->group5,
            ],
            'doneCount'   => $done->count(),
            'lastUser'    => $done->count() ? $this->shortName($done->last()->User?->name) : null,
            'nstats'      => $list->nstats,
            'centerjob'   => $list->centerjob,
            'deadline'    => $this->realDeadline($daysLeft),
            'pze'         => $this->pzeBadge($list->pze_parecer),
            'informes'    => $informes,
            'dispatchAt'  => $production?->dispatch_at,
            'state'       => $stack ? RowState::FREE : $this->rowState((int) $eval['block']),
            'canDispatch' => $state['canDispatch'],
            'stackOpen'   => (bool) $stack,
            'reason'      => $stack ? 'Disponível na pilha da empresa para atribuição individual.' : $this->reasonText($eval['reason'] ?? null),
            'assignee'    => $production?->User ? $this->shortName($production->User->name) : 'Pilha',
            'assigneeCo'  => $production?->Company ? $this->companyName($production->Company->name) : 'Desconhecido',
        ];
    }

    /**
     * Avaliação do Design (`BlockEvaluator`) nas cinco situações da lista: produção em aberto sem leitura clara do SAP
     * (HOLD_INCONST) continua "com usuário".
     */
    public function rowState(int $block): string
    {
        return match ($block) {
            BlockEvaluator::HOLD_BLUE, BlockEvaluator::HOLD_INCONST => RowState::ASSIGNED,
            BlockEvaluator::HOLD_YELLOW => RowState::STACK,
            BlockEvaluator::HOLD_GREEN  => RowState::DONE,
            BlockEvaluator::HOLD_RED    => RowState::LATE,
            default                     => RowState::FREE,
        };
    }

    /**
     * Prazo real: dias que faltam já descontados os status não contabilizáveis (`DaysLeft`); a coluna mostra `30 - dias`.
     * Faixas originais: menos de 6 dias (ou vencido) vermelho, menos de 10 âmbar, 10 ou mais verde.
     *
     * @return array{date: Carbon, days: int, elapsed: int, tone: string, overdue: bool}
     */
    public function realDeadline(int $daysLeft): array
    {
        return [
            'date'    => Carbon::today()->addDays($daysLeft),
            'days'    => $daysLeft,
            'elapsed' => self::EXECUTION_DAYS - $daysLeft,
            'overdue' => $daysLeft < 0,
            'tone'    => match (true) {
                $daysLeft < 6  => 'red',
                $daysLeft < 10 => 'amber',
                default        => 'green',
            },
        ];
    }

    /** @return array{tone: string, label: string, hint: string} */
    public function pzeBadge(?string $parecer): array
    {
        return match ($parecer) {
            'Vencido'     => ['tone' => 'red', 'label' => 'Vencido', 'hint' => 'Prazo da nota vencido'],
            'Não vencido' => ['tone' => 'green', 'label' => 'Em prazo', 'hint' => 'Prazo da nota em dia'],
            default       => ['tone' => 'muted', 'label' => 'Desconhecido', 'hint' => 'Sem leitura do prazo da nota'],
        };
    }

    /** Motivo do bloqueio em português (o código interno do avaliador não vai para o tooltip). */
    public function reasonText(?string $code): ?string
    {
        return match ($code) {
            'no_production'                             => 'Sem atividade aberta: pode ser atribuída.',
            'different_and_completed_confirmed'         => 'Voltou a ficar disponível depois de uma atividade concluída.',
            'same_and_completed_confirmed'              => 'Concluída e confirmada; aguardando o SAP atualizar.',
            'same_completed_not_confirmed'              => 'Concluída há pouco; aguardando confirmação.',
            'production_without_assignment'             => 'Está na pilha da empresa, ainda sem usuário.',
            'production_status_2_not_completed'         => 'Atribuída a um usuário e em andamento.',
            'production_completed_waiting_confirmation' => 'Concluída; aguardando confirmação.',
            'prod_inconst_open_generic'                 => 'Atividade em aberto.',
            default                                     => null,
        };
    }

    /** Controle: linha de uma Production em aberto. */
    public function presentControl(Production $item): array
    {
        $wpa    = $item->wpas?->last();
        $status = WpaStatus::status($wpa?->dd, $wpa?->execstats, $wpa?->completed_at);
        $age    = fn ($date) => $date ? (int) Carbon::parse($date)->startOfDay()->diffInDays(Carbon::now()->startOfDay()) : null;

        $createdAt   = $item->dt_created ? Carbon::parse($item->dt_created) : null;
        $createdAge  = $age($createdAt);
        $dispatchAge = $age($item->dispatch_at);
        $attAge      = $age($item->att_at);

        return [
            'id'           => (int) $item->id,
            'noteId'       => (int) $item->note_id,
            'note'         => (string) $item->note?->note,
            'ri'           => (bool) $item->d5,
            'priority'     => (bool) $item->priority,
            'block'        => (bool) $item->block,
            'completed'    => (bool) $item->completed,
            'hasUser'      => (bool) $item->user_id,
            'dd'           => $wpa?->dd,
            'ddTone'       => $this->bootstrapBg($status->bg_color),
            'ddIcon'       => $status->icon,
            'ddInfo'       => $this->plainInfo($status->info),
            'rubrica'      => $item->note?->rubrica,
            'city'         => $item->note?->lexp,
            'group2'       => $item->note?->group2,
            'dispatcher'   => $this->shortName($item->dispatcher?->name),
            'dispatcherCo' => $item->dispatcher?->Company?->name,
            'user'         => $this->shortName($item->user?->name),
            'userCo'       => $item->company?->name ?? $item->user?->Company?->name,
            'attDays'      => $item->att_at ? (int) $item->att_at->diffInDays(Carbon::now()) : null,
            'dueAt'        => $createdAt?->copy()->addDays(self::EXECUTION_DAYS),
            'dueAge'       => $createdAge,
            'dueTone'      => $createdAge === null ? 'muted' : ($createdAge > 30 ? 'red' : ($createdAge <= 20 ? 'green' : 'amber')),
            'dispatchAt'   => $item->dispatch_at,
            'dispatchAge'  => $dispatchAge,
            'dispatchTone' => $this->limitTone($dispatchAge, 30),
            'attAt'        => $item->att_at,
            'attAge'       => $attAge,
            'attTone'      => $this->limitTone($attAge, self::ATTRIBUTION_LIMIT),
            'status'       => (int) $item->status,
            'statusLabel'  => Notestatus::status($item->status)->status,
            'statusTone'   => Tone::forProductionStatus($item->status),
        ];
    }

    /**
     * Serviços → Levantamento: linha da produção do próprio usuário. Faixas originais: "Em atribuição" até 5 dias verde, até 8
     * âmbar, acima vermelho; prazo (criação da nota + 30 dias) verde até 20 dias corridos, âmbar até 30, vermelho acima.
     */
    public function presentOwn(Production $item): array
    {
        $note      = $item->Note;
        $wpa       = $item->Wpas->sortByDesc('created_at')->first();
        $createdAt = $note?->dt_created ? Carbon::parse($note->dt_created) : null;
        $age       = $createdAt ? (int) $createdAt->copy()->startOfDay()->diffInDays(Carbon::now()->startOfDay(), false) : null;
        $attDays   = $item->att_at ? (int) $item->att_at->diffInDays(Carbon::now(), false) : null;
        $quality   = $item->activeQualityProcess;

        return [
            'id'       => (int) $item->id,
            'noteId'   => (int) $item->note_id,
            'note'     => (string) $note?->note,
            'priority' => (bool) $item->priority,
            'dd'       => $wpa?->dd,
            'rubrica'  => $note?->rubrica,
            'city'     => $note?->lexp,
            'group2'   => $note?->group2,
            'material' => $note?->material,
            'attDays'  => $attDays,
            'attTone'  => match (true) {
                $attDays === null => 'muted',
                $attDays > 8      => 'red',
                $attDays <= 5     => 'green',
                default           => 'amber',
            },
            'dueAt'   => $createdAt?->copy()->addDays(self::EXECUTION_DAYS),
            'dueAge'  => $age,
            'dueTone' => match (true) {
                $age === null => 'muted',
                $age > 30     => 'red',
                $age <= 20    => 'green',
                default       => 'amber',
            },
            'waitingDispatch' => (bool) ($item->transferred && $item->block_wpa),
            'status'          => (int) $item->status,
            'statusLabel'     => Notestatus::status($item->status)->status,
            'statusTone'      => Tone::forProductionStatus($item->status),
            'quality'         => $quality ? 'QUALIDADE · ' . $quality->phase->short() . ' · rodada ' . $quality->round_number : null,
            'canAct'          => !$item->block && !$item->block_wpa && !$item->completed,
        ];
    }

    /** Idade em dias contra um limite: até 70% do limite verde, até o limite âmbar, acima vermelho. */
    public function limitTone(?int $days, int $limit): string
    {
        if ($days === null) {
            return 'muted';
        }

        return match (true) {
            $days > $limit              => 'red',
            $days <= ceil($limit * 0.7) => 'green',
            default                     => 'amber',
        };
    }

    public function shortName(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return 'Desconhecido';
        }

        $parts = collect(explode(' ', $name))->filter()->values();
        $short = $parts->count() > 1 ? $parts->first() . ' ' . $parts->last() : $parts->first();

        return mb_convert_case(mb_strtolower($short, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    private function companyName(string $name): string
    {
        $first = explode(' ', $name)[0] ?? $name;

        return mb_convert_case(mb_strtolower($first, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    private function bootstrapBg(?string $class): string
    {
        return Tone::fromBootstrap(str_replace('text-bg-', '', (string) $class));
    }

    private function plainInfo(?string $info): string
    {
        return trim(strip_tags(str_replace('</p><p', '</p> · <p', (string) $info)));
    }
}
