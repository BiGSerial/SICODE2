<?php

namespace App\Services\Audit;

use App\Custom\Notestatus;
use App\Models\{Audit, CancellationRequest, Company, Note, Production, Service, User};
use App\Support\Ops\Tone;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Transforma auditorias em linhas prontas para a tela: ação/modelo com tom, nota, atividade e o resumo do que mudou
 * (`Status: Atribuído → Em Andamento`). Os rótulos (usuário, empresa, atividade, nota) são resolvidos em lote para a página
 * inteira, nunca um a um.
 */
class AuditPresenter
{
    /** ação => [rótulo, tom] */
    public const ACTIONS = [
        'created'       => ['Criado', 'green'],
        'updated'       => ['Alterado', 'blue'],
        'deleted'       => ['Excluído', 'red'],
        'restored'      => ['Restaurado', 'teal'],
        'force_deleted' => ['Excluído definitivamente', 'red'],
    ];

    public const MODELS = [
        Production::class          => 'Atividade',
        CancellationRequest::class => 'Solicitação de cancelamento',
        User::class                => 'Usuário',
    ];

    /** Rótulo dos campos que mais aparecem; os demais mostram o nome técnico. */
    public const FIELDS = [
        'status'                        => 'Status',
        'user_id'                       => 'Responsável',
        'company_id'                    => 'Empresa',
        'service_id'                    => 'Atividade',
        'note_id'                       => 'Nota/OV',
        'dispatch_by'                   => 'Despachado por',
        'att_by'                        => 'Atribuído por',
        'dispatch_at'                   => 'Despachado em',
        'att_at'                        => 'Atribuído em',
        'completed_at'                  => 'Concluído em',
        'completed'                     => 'Concluído',
        'confirmed'                     => 'Confirmado',
        'returned'                      => 'Devolvido',
        'priority'                      => 'Prioridade',
        'partial'                       => 'Parcial',
        'd5'                            => 'D5',
        'dfive'                         => 'Trata D5',
        'rejected'                      => 'Rejeitado',
        'block'                         => 'Bloqueado',
        'transferred'                   => 'Transferido',
        'stopped'                       => 'Tempo parado (s)',
        'postes_u'                      => 'Ativos (usuário)',
        'postes_c'                      => 'Ativos (calculado)',
        'start_at'                      => 'Iniciado em',
        'status_note'                   => 'Status SAP da Nota',
        'dt_note'                       => 'Data do status SAP',
        'dhstats'                       => 'Data do status SAP',
        'partial_at'                    => 'Parcial em',
        'confirmed_at'                  => 'Confirmado em',
        'ma'                            => 'Meio ambiente',
        'eo'                            => 'Entidade externa',
        'iproject'                      => 'Projeto de investimento',
        'mmgd'                          => 'MMGD',
        'cad'                           => 'Cadastro',
        'cadastro'                      => 'Cadastro',
        'block_wpa'                     => 'Bloqueio WPA',
        'noinconsistency'               => 'Sem inconsistência',
        'supervision_by_partner_photos' => 'Fiscalização por fotos da parceira',
        'centroTrab'                    => 'Centro de trabalho',
    ];

    private const USER_FIELDS = ['user_id', 'dispatch_by', 'att_by', 'dispatcher_by', 'engineer_id', 'responsible_id', 'created_by', 'updated_by'];

    private const BOOL_FIELDS = ['completed', 'confirmed', 'returned', 'priority', 'partial', 'd5', 'dfive', 'rejected', 'block', 'transferred', 'manual', 'conf_manual',
        'ma', 'eo', 'iproject', 'mmgd', 'cad', 'cadastro', 'block_wpa', 'noinconsistency', 'supervision_by_partner_photos'];

    /** Quantos campos aparecem no resumo da linha; o resto vira "+N". */
    private const SUMMARY_LIMIT = 3;

    /** @var array<string, array<string, string>> */
    private array $labels = ['user' => [], 'company' => [], 'service' => [], 'note' => []];

    /**
     * @param Collection<int, Audit> $audits
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(Collection $audits): array
    {
        $parsed = $audits->map(fn (Audit $audit) => [
            'audit'   => $audit,
            'changes' => $audit->action === 'updated'
                ? AuditPayload::diff(AuditPayload::decode($audit->before), AuditPayload::decode($audit->after))
                : [],
        ]);

        $this->loadLabels(
            $audits,
            $parsed->flatMap(fn ($item) => collect($item['changes'])->take(self::SUMMARY_LIMIT)->map(fn ($pair, $field) => [$field, $pair])->values())
        );

        return $parsed->map(function (array $item) {
            /** @var Audit $audit */
            $audit  = $item['audit'];
            $action = self::ACTIONS[$audit->action] ?? [mb_strtoupper((string) $audit->action), 'muted'];

            $summary = collect($item['changes'])->take(self::SUMMARY_LIMIT)->map(fn ($pair, $field) => [
                'field'  => self::FIELDS[$field] ?? $field,
                'before' => $this->format($field, $pair[0]),
                'after'  => $this->format($field, $pair[1]),
            ])->values()->all();

            return [
                'id'         => $audit->id,
                'when'       => Carbon::parse($audit->created_at),
                'user'       => $audit->User?->name,
                'action'     => ['label' => $action[0], 'tone' => $action[1]],
                'model'      => self::MODELS[$audit->model_class] ?? class_basename((string) $audit->model_class),
                'modelClass' => $audit->model_class,
                'note'       => $audit->note_id ? ($this->labels['note'][$audit->note_id] ?? (string) $audit->note_id) : null,
                'service'    => $audit->service_id ? ($this->labels['service'][$audit->service_id] ?? null) : null,
                'recordId'   => $audit->record_id,
                'summary'    => $summary,
                'more'       => max(0, count($item['changes']) - self::SUMMARY_LIMIT),
                'noChange'   => $audit->action === 'updated' && $item['changes'] === [],
            ];
        })->all();
    }

    /**
     * Diferença completa de uma auditoria, para o modal.
     *
     * @return array{rows: array<int, array{field: string, key: string, before: string, after: string}>, row: array<string, mixed>}
     */
    public function detail(Audit $audit): array
    {
        $before = AuditPayload::decode($audit->before);
        $after  = AuditPayload::decode($audit->after);
        // Criação e exclusão não têm "antes"/"depois" comparável: mostra o registro inteiro.
        $changes = in_array($audit->action, ['created', 'deleted', 'force_deleted'], true)
            ? collect($audit->action === 'created' ? $after : $before)->except(AuditPayload::IGNORED_FIELDS)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => $audit->action === 'created' ? [null, $value] : [$value, null])->all()
            : AuditPayload::diff($before, $after);

        $this->loadLabels(collect([$audit]), collect($changes)->map(fn ($pair, $field) => [$field, $pair])->values());

        return [
            'rows' => collect($changes)->map(fn ($pair, $field) => [
                'key'    => (string) $field,
                'field'  => self::FIELDS[$field] ?? $field,
                'before' => $this->format($field, $pair[0]),
                'after'  => $this->format($field, $pair[1]),
                'tones'  => $field === 'status' ? [$this->statusTone($pair[0]), $this->statusTone($pair[1])] : null,
            ])->values()->all(),
            'row' => $this->rows(collect([$audit]))[0],
        ];
    }

    /** Resolve em 4 consultas (no máximo) tudo o que a página precisa nomear. */
    private function loadLabels(Collection $audits, Collection $changePairs): void
    {
        $users      = $audits->pluck('user_id')->filter();
        $noteIds    = $audits->pluck('note_id')->filter();
        $serviceIds = $audits->pluck('service_id')->filter();
        $companies  = collect();

        foreach ($changePairs as [$field, $pair]) {
            foreach ($pair as $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                match (true) {
                    in_array($field, self::USER_FIELDS, true) => $users->push($value),
                    $field === 'company_id'                   => $companies->push($value),
                    $field === 'service_id'                   => $serviceIds->push($value),
                    $field === 'note_id'                      => $noteIds->push($value),
                    default                                   => null,
                };
            }
        }

        $this->labels['user'] += $this->missing('user', $users) ? User::withTrashed()->whereIn('id', $this->missing('user', $users))->pluck('name', 'id')->all() : [];
        $this->labels['company'] += $this->missing('company', $companies) ? Company::withTrashed()->whereIn('id', $this->missing('company', $companies))->pluck('name', 'id')->all() : [];
        $this->labels['service'] += $this->missing('service', $serviceIds) ? Service::whereIn('uuid', $this->missing('service', $serviceIds))->pluck('service', 'uuid')->all() : [];
        $this->labels['note'] += $this->missing('note', $noteIds) ? Note::whereIn('id', $this->missing('note', $noteIds))->pluck('note', 'id')->all() : [];
    }

    /** @return array<int, string> */
    private function missing(string $kind, Collection $ids): array
    {
        return $ids->map(fn ($id) => (string) $id)->unique()->reject(fn ($id) => isset($this->labels[$kind][$id]))->values()->all();
    }

    private function format(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $value = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        return match (true) {
            in_array($field, self::BOOL_FIELDS, true) => in_array($value, ['1', 'true'], true) ? 'Sim' : 'Não',
            in_array($field, self::USER_FIELDS, true) => $this->labels['user'][$value] ?? $value,
            $field === 'company_id'                   => $this->labels['company'][$value] ?? $value,
            $field === 'service_id'                   => $this->labels['service'][$value] ?? $value,
            $field === 'note_id'                      => $this->labels['note'][$value] ?? $value,
            $field === 'status'                       => (Notestatus::status($value)->status ?? $value) . " ({$value})",
            $this->isDateField($field)                => $this->date($value),
            default                                   => $value,
        };
    }

    private function statusTone(mixed $value): string
    {
        return $value === null || $value === '' ? 'muted' : Tone::forProductionStatus($value);
    }

    private function isDateField(string $field): bool
    {
        return str_ends_with($field, '_at') || str_starts_with($field, 'dt_') || $field === 'dhstats';
    }

    private function date(string $value): string
    {
        try {
            return Carbon::parse(AuditPayload::localDate($value) ?? $value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $value;
        }
    }
}
