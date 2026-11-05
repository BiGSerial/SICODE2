<?php

namespace App\Services\Payment;

use App\Models\{FiveNote, MeasurementProductionContext, Notetimeline, Production, User, WorkReport, WorkReportFlowProduction};
use App\Services\D5\D5WorkflowService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * D5 criada pela Medição/Pagamento ao encerrar o 1º ciclo, para os casos em que o Fiscal encerrou a atividade sem solicitar a D5.
 *
 * Nada é gravado enquanto o usuário preenche o formulário: `create()` só roda dentro da transação do encerramento. Ele cadastra a D5,
 * associa a produção do fiscal (quando informada) e a própria produção, e marca a própria produção como D5 (`dfive`). Liberar a D5 à
 * empreiteira continua sendo o trabalho do encerramento normal do ciclo 1.
 */
class PaymentD5CreationService
{
    /** Valor do seletor de fiscal para "não havia fiscal na atividade anterior". */
    public const NO_FISCAL = 'none';

    /** Pode criar D5 daqui? Só produção de ciclo 1 (ou legada, sem contexto), não parcial e ainda sem D5. */
    public function canCreate(Production $production): bool
    {
        if ($production->partial || $production->completed || $production->dfive) {
            return false;
        }

        $context = $production->measurementContext;

        if ($context && ($context->cycle !== MeasurementProductionContext::CYCLE_ONE || $context->five_note_id)) {
            return false;
        }

        return true;
    }

    /**
     * Produções de Fiscalização já encerradas na Nota (a mais recente de cada fiscal) para o usuário escolher quem encerrou.
     *
     * @return Collection<int, array{id: int, user: string, completed_at: ?string}>
     */
    public function fiscalOptions(Production $production): Collection
    {
        return Production::query()
            ->where('note_id', $production->note_id)
            ->where('completed', true)
            ->whereNotNull('user_id')
            ->whereHas('Service', fn ($service) => $service->where('service', 'Fiscalização'))
            ->with('User:id,name')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->map(fn (Production $fiscal) => [
                'id'           => $fiscal->id,
                'user'         => $fiscal->User?->name ?? 'Usuário removido',
                'completed_at' => $fiscal->completed_at ? \Carbon\Carbon::parse($fiscal->completed_at)->format('d/m/Y H:i') : null,
            ])
            ->values();
    }

    /** A produção de fiscalização escolhida precisa ser uma das opções da Nota (nunca confiar no valor vindo do navegador). */
    public function resolveFiscal(Production $production, $choice): ?Production
    {
        if ($choice === null || $choice === '' || $choice === self::NO_FISCAL) {
            return null;
        }

        if (!$this->fiscalOptions($production)->contains('id', (int) $choice)) {
            throw new DomainException('O fiscal selecionado não encerrou uma atividade desta Nota.');
        }

        return Production::with('User:id,name')->find((int) $choice);
    }

    /** Valores iniciais do formulário: empresa do informe, conjunto da Nota e PEP da ordem principal. */
    public function defaults(Production $production): array
    {
        $workReport = $this->currentWorkReport($production);
        $order      = $workReport?->Orders
            ?->sortBy('ordem')
            ->first(function ($order) {
                $number = preg_replace('/\D+/', '', (string) ($order->ordem ?? ''));

                return str_starts_with($number, '200') || str_starts_with($number, '170') || str_starts_with($number, '190');
            }) ?? $workReport?->Orders?->sortBy('ordem')->first();

        return [
            'note_d5'     => '',
            'company_id'  => $workReport?->company_id ?: '',
            'reason'      => '',
            'codify'      => '',
            'loc_install' => '',
            'sintoms'     => '',
            'description' => '',
            'conjunto'    => ($conjunto = $production->Note?->num_material) === null ? null : (string) $conjunto,
            'pep'         => $order?->pep === null ? null : (string) $order->pep,
        ];
    }

    public function auditLine(User $actor, ?Production $fiscal, ?FiveNote $five = null): string
    {
        $number = $five?->note_d5 ? " nº {$five->note_d5}" : '';
        $when   = now()->format('d/m/Y H:i');

        return $fiscal?->User
            ? "D5{$number} gerada pelo usuário {$actor->name} em nome do fiscal {$fiscal->User->name} ({$when})."
            : "D5{$number} gerada pelo usuário {$actor->name} ({$when}).";
    }

    /**
     * Cadastra a D5 e liga as produções. Chamar dentro da transação do encerramento.
     *
     * @throws DomainException
     */
    public function create(Production $production, array $data, ?Production $fiscal, User $actor): FiveNote
    {
        if (!$this->canCreate($production)) {
            throw new DomainException('Esta atividade não pode criar uma D5 (já tem D5, é parcial ou não é de 1º ciclo).');
        }

        $workReportId = $this->currentWorkReport($production)?->id;

        if ($workReportId && FiveNote::query()->where('note_id', $production->note_id)->where('work_report_id', $workReportId)->exists()) {
            throw new DomainException('Já existe uma D5 para o informe desta Nota. Use a opção "é sobre a D5" ao abrir a atividade.');
        }

        try {
            $five = FiveNote::create([
                'note_id'        => $production->note_id,
                'work_report_id' => $workReportId,
                'note_d5'        => trim((string) $data['note_d5']),
                'company_id'     => $data['company_id'],
                'reason'         => $data['reason'],
                'codify'         => $this->nullableTrim($data['codify'] ?? null),
                'loc_install'    => $this->nullableTrim($data['loc_install'] ?? null),
                'sintoms'        => $this->nullableTrim($data['sintoms'] ?? null),
                'description'    => $data['description'] ?? '',
                'conjunto'       => $this->nullableTrim($data['conjunto'] ?? null),
                'pep'            => $this->nullableTrim($data['pep'] ?? null),
                'e_pep'          => $this->nullableTrim($data['pep'] ?? null),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new DomainException('Já existe uma D5 com este número ou para este informe.');
            }

            throw $e;
        }

        $production->update(['dfive' => true]);

        $five->Productions()->syncWithoutDetaching(array_values(array_filter([$fiscal?->id, $production->id])));

        $production->measurementContext()
            ->where('cycle', MeasurementProductionContext::CYCLE_ONE)
            ->whereNull('five_note_id')
            ->update(['five_note_id' => $five->id]);

        app(D5WorkflowService::class)->onCreatedManual($five, (string) $actor->id, $production);

        Notetimeline::create([
            'note_id'       => $production->note_id,
            'service_id'    => $production->service_id,
            'user_id'       => $actor->id,
            'info'          => $this->auditLine($actor, $fiscal, $five),
            'status'        => $production->status,
            'production_id' => $production->id,
        ]);

        return $five;
    }

    private function currentWorkReport(Production $production): ?WorkReport
    {
        foreach ([WorkReportFlowProduction::STAGE_PAYMENT, WorkReportFlowProduction::STAGE_FISCALIZATION] as $stage) {
            $id = $production->WorkReportFlowProductions()
                ->where('stage', $stage)
                ->where('is_current', true)
                ->latest('id')
                ->value('work_report_id');

            if ($id) {
                return WorkReport::with('Orders')->find($id);
            }
        }

        return null;
    }

    private function nullableTrim($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
