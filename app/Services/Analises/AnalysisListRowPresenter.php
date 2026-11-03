<?php

namespace App\Services\Analises;

use App\Custom\Notestatus;
use App\Models\{Note, Production};
use App\Services\Design\BlockEvaluator;
use App\Services\Production\ProductionActions;
use App\Support\Ops\{Deadline, RowState, Tone};
use Carbon\Carbon;

/**
 * Monta, para as listas de Análises (Despacho, Parceira, Controle e Serviços), tudo o que uma linha mostra já com tom
 * semântico (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; regra de prazo/cor mora aqui.
 *
 * Prazo real: 30 dias corridos a partir da criação da Nota/OV (regra original da atividade, preservada).
 */
class AnalysisListRowPresenter
{
    /** Prazo real, em dias corridos desde `dt_created`. */
    public const DEADLINE_DAYS = 30;

    public const DEADLINE_HINT = 'Prazo real: 30 dias corridos desde a criação da Nota/OV. Verde: 10 dias ou mais para vencer. Âmbar: de 6 a 9 dias. Vermelho: até 5 dias. Cinza: vencido.';

    /** Converte o nível do `Design\BlockEvaluator` (o de Análises) na situação visual da linha. */
    public static function stateFromBlock(int $block, ?string $reason = null): string
    {
        // Inconsistência: concluída esperando confirmação é "Concluída"; qualquer outra atividade em aberto é "Com usuário".
        if ($block === BlockEvaluator::HOLD_INCONST && $reason === 'production_completed_waiting_confirmation') {
            return RowState::DONE;
        }

        return match ($block) {
            BlockEvaluator::HOLD_BLUE, BlockEvaluator::HOLD_INCONST => RowState::ASSIGNED,
            BlockEvaluator::HOLD_YELLOW => RowState::STACK,
            BlockEvaluator::HOLD_GREEN  => RowState::DONE,
            BlockEvaluator::HOLD_RED    => RowState::LATE,
            default                     => RowState::FREE,
        };
    }

    /** Motivo do bloqueio em português (o avaliador devolve um código interno). */
    public static function reasonText(?string $code): ?string
    {
        return match ($code) {
            'no_production'                             => 'Sem atividade aberta.',
            'different_and_completed_confirmed'         => 'Houve nova atualização no SAP desde a última atividade; pode ser atribuída novamente.',
            'same_and_completed_confirmed'              => 'Concluída e confirmada, mas o SAP ainda não atualizou o status.',
            'same_completed_not_confirmed'              => 'Concluída; aguardando confirmação.',
            'production_without_assignment'             => 'Na pilha da empresa, ainda sem usuário.',
            'production_status_2_not_completed'         => 'Atribuída a um usuário e em andamento.',
            'production_completed_waiting_confirmation' => 'Concluída, aguardando confirmação.',
            'prod_inconst_open_generic'                 => 'Atividade em aberto.',
            default                                     => $code,
        };
    }

    /** Prazo real da Nota/OV: data de vencimento, tom pela regra da atividade e texto de apoio. */
    public static function deadline(?Carbon $createdAt): array
    {
        if (!$createdAt) {
            return ['date' => null, 'tone' => 'muted', 'elapsed' => null, 'remaining' => null, 'sub' => null, 'hint' => 'Sem data de criação. ' . self::DEADLINE_HINT];
        }

        $elapsed   = (int) $createdAt->copy()->startOfDay()->diffInDays(Carbon::now()->startOfDay());
        $remaining = self::DEADLINE_DAYS - $elapsed;

        return [
            'date' => $createdAt->copy()->startOfDay()->addDays(self::DEADLINE_DAYS),
            'tone' => match (true) {
                $remaining < 0  => 'muted',
                $remaining < 6  => 'red',
                $remaining < 10 => 'amber',
                default         => 'green',
            },
            'elapsed'   => $elapsed,
            'remaining' => $remaining,
            'sub'       => Deadline::remainingText($remaining),
            'hint'      => $elapsed . ($elapsed === 1 ? ' dia corrido' : ' dias corridos') . ' desde a criação. ' . self::DEADLINE_HINT,
        ];
    }

    /** Situação do prazo do SAP (`pze_parecer`). */
    public static function sapDeadline(?string $parecer): array
    {
        return match ($parecer) {
            'Vencido'     => ['tone' => 'red', 'label' => 'Vencido', 'hint' => 'Prazo do SAP vencido.'],
            'Não vencido' => ['tone' => 'green', 'label' => 'Em prazo', 'hint' => 'Prazo do SAP em dia.'],
            default       => ['tone' => 'muted', 'label' => 'Desconhecido', 'hint' => 'O SAP não informou a situação do prazo.'],
        };
    }

    public static function shortName(?string $name): ?string
    {
        if (!filled($name)) {
            return null;
        }

        $parts = explode(' ', trim($name));

        return count($parts) > 1 ? $parts[0] . ' ' . end($parts) : $parts[0];
    }

    /**
     * Linha das listas de Despacho (e Parceira).
     *
     * @param array{eval: array, stack: ?Production, canDispatch: bool} $state resultado de `rowDispatchState()`
     */
    public function present(Note $list, array $state, string $profile = 'dispatch'): array
    {
        $eval       = $state['eval'];
        $stack      = $state['stack'];
        $production = $stack ?: ($eval['production'] ?? null);
        $assignee   = self::shortName($production?->User?->name ?: $production?->Company?->name);
        $block      = (int) ($eval['block'] ?? 0);
        $count      = (int) ($eval['count'] ?? 0);

        return $this->baseFields($list) + [
            'key'         => (string) $list->id,
            'noteId'      => (int) $list->id,
            'dtStatus'    => $list->dt_status,
            'count'       => $count,
            'lastUser'    => $count ? ($assignee ?? 'Desconhecido') : null,
            'state'       => $stack ? RowState::FREE : self::stateFromBlock($block, $eval['reason'] ?? null),
            'reason'      => $stack ? 'Disponível na pilha da empresa para atribuição individual.' : self::reasonText($eval['reason'] ?? null),
            'command'     => (bool) ($eval['command'] ?? false),
            'canDispatch' => (bool) $state['canDispatch'],
            'stackOpen'   => (bool) $stack,
            'assignee'    => $assignee,
            'payload'     => [(int) $list->id],
        ];
    }

    /**
     * Linha da lista de Serviços (auto-atribuição). A atividade usa uma avaliação própria (última Production do serviço):
     * 1 = em aberto (com usuário = azul, sem usuário identificado = âmbar), 2 = concluída (verde), 3 = confirmada (vermelho).
     */
    public function presentSelfAssign(Note $list, string $serviceUuid, bool $canSeeNote): array
    {
        $productions    = $list->Productions->where('service_id', $serviceUuid);
        $lastProduction = $productions->last();
        $block          = 0;

        if ($lastProduction && $lastProduction->dt_note != $list->dt_status) {
            $block = 0;
        } elseif ($lastProduction && !$lastProduction->completed && !$lastProduction->confirmed) {
            $block = 1;
        } elseif ($lastProduction && $lastProduction->completed && !$lastProduction->confirmed) {
            $block = 2;
        } elseif ($lastProduction && $lastProduction->completed && $lastProduction->confirmed) {
            $block = 3;
        } elseif ($lastProduction && $lastProduction->dt_note === $list->dt_status) {
            $block = 3;
        }

        $lastUser = $lastProduction ? (self::shortName($lastProduction->User?->name) ?? 'Desconhecido') : null;
        $state    = match (true) {
            $block === 1 && $lastUser !== 'Desconhecido' => RowState::ASSIGNED,
            $block === 1                                 => RowState::STACK,
            $block === 2                                 => RowState::DONE,
            $block === 3                                 => RowState::LATE,
            default                                      => RowState::FREE,
        };

        return $this->baseFields($list) + [
            'key'       => (string) $list->id,
            'noteId'    => (int) $list->id,
            'dtStatus'  => $list->dt_status,
            'group1'    => $list->group1,
            'showNote'  => $canSeeNote || ($lastProduction && $lastProduction->user_id === Auth()->User()->id),
            'state'     => $state,
            'canAssign' => !$block,
            'assignee'  => $lastUser,
        ];
    }

    /** Linha da lista de Controle: a Production (já com Note, User e Company carregados). */
    public function presentControl(Production $production): array
    {
        $actions  = app(ProductionActions::class);
        $note     = $production->Note;
        $deadline = self::deadline($note?->dt_created);
        $status   = Notestatus::status($production->status);
        $dispatch = $production->dispatch_at ? Carbon::parse($production->dispatch_at) : null;
        $attached = $production->att_at ? Carbon::parse($production->att_at) : null;

        return [
            'id'             => (int) $production->id,
            'noteId'         => (int) $production->note_id,
            'note'           => $note?->note,
            'priority'       => (bool) $production->priority,
            'ri'             => (bool) $production->d5,
            'group2'         => $note?->group2,
            'rubrica'        => $note?->rubrica,
            'center'         => $production->centerjob,
            'city'           => $note?->lexp,
            'zone'           => $note?->group1,
            'description'    => $note?->rubrica == 'BT Zero' ? $note?->numPedido : $note?->material,
            'company'        => $production->Company ? explode(' ', $production->Company->name)[0] : null,
            'companyFull'    => $production->Company?->name,
            'user'           => $production->User,
            'dispatchAt'     => $dispatch,
            'dispatchDays'   => $dispatch ? (int) $dispatch->copy()->startOfDay()->diffInDays(Carbon::now()->startOfDay()) : null,
            'attachedAt'     => $attached,
            'attachedDays'   => $attached ? (int) $attached->copy()->startOfDay()->diffInDays(Carbon::now()->startOfDay()) : null,
            'deadline'       => $deadline,
            'status'         => (int) $production->status,
            'statusLabel'    => $status->status,
            'statusTone'     => Tone::forProductionStatus($production->status),
            'completed'      => (bool) $production->completed,
            'canReattribute' => $this->canReattribute($production),
            'canRemove'      => $actions->canRemove($production),
        ];
    }

    private ?bool $superadm = null;

    /**
     * Mesma regra de `ProductionActions::canReattribute`, mas lendo `superadm` uma vez por requisição (o atributo faz uma
     * consulta de delegação a cada leitura, o que virava uma consulta por linha).
     */
    private function canReattribute(Production $production): bool
    {
        if (!$production->confirmed && $production->completed) {
            return true;
        }

        return $this->superadm ??= (bool) Auth()->User()?->superadm;
    }

    private function baseFields(Note $list): array
    {
        return [
            'note'      => $list->note,
            'createdAt' => $list->dt_created,
            'numPedido' => filled($list->numPedido) ? mb_strtoupper($list->numPedido) : null,
            'rubrica'   => $list->rubrica,
            'city'      => $list->lexp,
            'material'  => $list->material,
            'group1'    => $list->group1,
            'group2'    => $list->group2,
            'isType1'   => (int) $list->type_note === 1,
            'centerjob' => $list->centerjob,
            'nstats'    => $list->nstats,
            'deadline'  => self::deadline($list->dt_created),
            'sap'       => self::sapDeadline($list->pze_parecer),
        ];
    }
}
