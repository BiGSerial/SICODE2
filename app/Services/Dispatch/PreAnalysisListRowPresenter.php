<?php

namespace App\Services\Dispatch;

use App\Custom\Notestatus;
use App\Models\{Note, Production};
use App\Services\Production\ProductionActions;
use App\Support\Ops\{RowState, Tone};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Linha das listas de Análises Pré (Despacho, Parceira e Serviços) já com tom semântico, para a view só desenhar
 * (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). A regra de cor do prazo e a situação da linha moram aqui, não na view.
 */
class PreAnalysisListRowPresenter
{
    /** Prazo real da Pré-análise, em dias corridos a partir da criação da Nota/OV. */
    public const DEADLINE_DAYS = 30;

    public const DEADLINE_HINT = 'Prazo real: 30 dias corridos desde a criação da Nota/OV. Verde: 10 dias ou mais para vencer. Âmbar: de 6 a 9 dias. Vermelho: até 5 dias. Cinza: vencido.';

    /**
     * Despacho / Parceira. `$state` vem de `Main::rowDispatchState()`: avaliação de bloqueio (`Services\Design\BlockEvaluator`)
     * e produção aberta na pilha da empresa.
     *
     * @param array{eval: array, stack: ?\App\Models\Production, canDispatch: bool} $state
     */
    public function dispatch(Note $note, array $state, bool $legal = false): array
    {
        $eval       = $state['eval'];
        $stack      = $state['stack'];
        $production = $stack ?: ($eval['production'] ?? null);
        $rowState   = $stack ? RowState::FREE : $this->stateFromBlock((int) $eval['block']);

        return $this->base($note) + [
            'canDispatch' => $state['canDispatch'],
            'command'     => (bool) ($eval['command'] ?? false),
            'stackOpen'   => (bool) $stack,
            'state'       => $rowState,
            'reason'      => $stack ? 'Disponível na pilha da empresa para atribuição individual.' : $this->reasonFor($eval['reason'] ?? null),
            'assignee'    => $this->shortName($production?->User?->name ?: $production?->Company?->name),
            'returns'     => (int) ($eval['count'] ?? 0),
            'legal'       => $legal,
        ];
    }

    /**
     * Serviços (auto-atribuição). A situação sai da última produção da atividade que não é "sem inconsistência":
     * 1 = pilha, 2 = com usuário, 5 = concluída (vermelha quando já confirmada), qualquer outra = com usuário.
     */
    public function service(Note $note, string $serviceUuid, bool $legal = false): array
    {
        $all   = $note->Productions->where('service_id', $serviceUuid);
        $valid = $all->where('noinconsistency', false);
        $last  = $valid->last();

        $blocked  = $last && ($last->dt_note == $note->dt_status || !$last->confirmed);
        $state    = RowState::FREE;
        $assignee = null;

        if ($blocked) {
            $state = match (true) {
                (int) $last->status === 1                      => RowState::STACK,
                (int) $last->status === 5 && !$last->confirmed => RowState::DONE,
                (int) $last->status === 5 && $last->confirmed  => RowState::LATE,
                default                                        => RowState::ASSIGNED,
            };
            $assignee = $this->shortName($last->User?->name) ?? 'Desconhecido';
        }

        return $this->base($note) + [
            'canDispatch' => !$blocked,
            'state'       => $state,
            'assignee'    => $assignee,
            'returns'     => $all->where('completed', true)->where('noinconsistency', true)->count(),
            'mmgd'        => (bool) $note->mmgd,
            'legal'       => $legal,
        ];
    }

    /**
     * Controle (Stack / Parceira): uma Production em aberto, com a Nota carregada. Sem cor de situação na linha;
     * a situação vive no selo de Status (`Tone::forProductionStatus`) e a única marca de linha é a prioridade.
     */
    public function control(Production $production, bool $legal): array
    {
        $note  = $production->Note;
        $today = Carbon::now()->startOfDay();
        $days  = fn ($date) => $date ? (int) Carbon::parse($date)->startOfDay()->diffInDays($today) : null;

        return $this->base($note) + [
            'id'             => (int) $production->id,
            'priority'       => (bool) $production->priority,
            'isRi'           => (bool) $production->d5,
            'completed'      => (bool) $production->completed,
            'statusCode'     => (int) $production->status,
            'statusTone'     => Tone::forProductionStatus($production->status),
            'statusLabel'    => Notestatus::status($production->status)->status,
            'centerjob'      => $production->centroTrab ?: $note->centerjob,
            'description'    => $note->rubrica == 'BT Zero' ? $note->numPedido : $note->material,
            'company'        => $production->Company ? explode(' ', $production->Company->name)[0] : null,
            'user'           => $production->User,
            'dispatchAt'     => $production->dispatch_at ? Carbon::parse($production->dispatch_at) : null,
            'dispatchDays'   => $days($production->dispatch_at),
            'attAt'          => $production->att_at ? Carbon::parse($production->att_at) : null,
            'attDays'        => $days($production->att_at),
            'legal'          => $legal,
            'canReattribute' => app(ProductionActions::class)->canReattribute($production),
            'canRemove'      => app(ProductionActions::class)->canRemove($production),
        ];
    }

    /**
     * Notas com demanda jurídica vinculada: 2 consultas para a página toda, em vez de 2 por linha
     * (a etiqueta `x-legal.note-demand-tags` só é desenhada para as que têm vínculo).
     *
     * @param array<int, int|string> $noteIds
     * @return array<int, true>
     */
    public function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }

    /** Níveis do avaliador de Pré-análise: 0..4 como no pagamento; 5 (inconsistente/aberta sem status 2) segue como "com usuário". */
    public function stateFromBlock(int $block): string
    {
        return $block === 5 ? RowState::ASSIGNED : RowState::fromBlock($block);
    }

    private function base(Note $note): array
    {
        $createdAt = $note->dt_created ? Carbon::parse($note->dt_created)->startOfDay() : null;
        $elapsed   = $createdAt ? (int) $createdAt->diffInDays(Carbon::now()->startOfDay()) : null;
        $remaining = $elapsed === null ? null : self::DEADLINE_DAYS - $elapsed;
        $isCenter  = (int) $note->type_note === 1;

        return [
            'key'       => (string) $note->id,
            'noteId'    => (int) $note->id,
            'note'      => $note->note,
            'dtStatus'  => $note->dt_status ? Carbon::parse($note->dt_status) : null,
            'dtCreated' => $createdAt,
            'numPedido' => $note->numPedido ? mb_strtoupper($note->numPedido) : null,
            'rubrica'   => $note->rubrica,
            'city'      => $note->lexp,
            'material'  => $note->material,
            'group1'    => $note->group1,
            'group2'    => $note->group2,
            'status'    => $isCenter ? $note->centerjob : $note->nstats,
            'statusSub' => $isCenter ? $note->nstats : null,
            'pze'       => $note->pze,
            'dueAt'     => $createdAt?->copy()->addDays(self::DEADLINE_DAYS),
            'elapsed'   => $elapsed,
            'dueTone'   => $this->deadlineTone($remaining),
            'parecer'   => $this->parecer($note->pze_parecer),
        ];
    }

    /** Verde: 10 dias ou mais; âmbar: 6 a 9; vermelho: 0 a 5; cinza: vencido ou sem data (mesmos limites da lista anterior). */
    public function deadlineTone(?int $remaining): string
    {
        return match (true) {
            $remaining === null => 'muted',
            $remaining < 0      => 'muted',
            $remaining < 6      => 'red',
            $remaining < 10     => 'amber',
            default             => 'green',
        };
    }

    /** @return array{tone: string, label: string, hint: string} */
    private function parecer(?string $parecer): array
    {
        return match ($parecer) {
            'Vencido'     => ['tone' => 'red', 'label' => 'Vencido', 'hint' => 'Prazo do SAP vencido'],
            'Não vencido' => ['tone' => 'green', 'label' => 'Em prazo', 'hint' => 'Dentro do prazo do SAP'],
            default       => ['tone' => 'muted', 'label' => 'Desconhecido', 'hint' => 'Sem parecer de prazo do SAP'],
        };
    }

    private function shortName(?string $name): ?string
    {
        if (!filled($name)) {
            return null;
        }

        $parts = explode(' ', trim($name));

        return count($parts) > 1 ? $parts[0] . ' ' . end($parts) : $parts[0];
    }

    /** Motivo do bloqueio em português (o avaliador devolve códigos internos). */
    private function reasonFor(?string $code): ?string
    {
        return match ($code) {
            'no_production'                     => null,
            'different_and_completed_confirmed' => 'Já tratada em outro ciclo; pode ser atribuída de novo.',
            'same_and_completed_confirmed'      => 'Concluída e confirmada; o SAP ainda não atualizou a Nota/OV.',
            'same_completed_not_confirmed'      => 'Concluída há menos de 24 h; aguardando atualização do SAP.',
            'production_without_assignment'     => 'Está na pilha da empresa, ainda sem usuário.',
            'production_status_2_not_completed' => 'Atribuída a um usuário e em andamento.',
            'production_completed_waiting_confirmation',
            'prod_inconst_open_generic' => 'Tem atividade aberta; acompanhe pelo Controle.',
            default                     => null,
        };
    }
}
