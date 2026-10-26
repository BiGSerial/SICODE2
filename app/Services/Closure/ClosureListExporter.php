<?php

namespace App\Services\Closure;

use App\Enum\ClosureOperationalStatus;
use App\Models\Closure\ClosureOrderState;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Linhas do Excel das listas do Encerramento (Despacho, Controle, Minha atividade, Disponíveis e Histórico).
 * Os cabeçalhos seguem as regras de formatação automática do StyledArraySheetExport (colunas com "Data" viram data,
 * "Nota"/"Ordem"/"Status" ficam centralizadas).
 */
class ClosureListExporter
{
    public const HEADINGS = [
        'Tipo', 'Nota', 'Ordem', 'Competência', 'Município', 'Descrição da obra', 'Empresa (informe)', 'Valor da obra (ADS) R$', 'Status SAP', 'Status usuário',
        'Operações confirmadas', 'Operações total', 'Status da atividade', 'Depósito bloqueado', 'Responsável', 'Empresa (posse atual)',
        'Prioridade', 'Horas no status', 'Data de entrada no status', 'Data fim real OP 0020',
    ];

    /**
     * @param Collection<int, Order> $records resultado de ClosureDispatchListQuery::exportRecords
     * @return array{rows: array<int, array<int, mixed>>, types: array<int, ?string>, statuses: array<int, string>}
     */
    public function dispatchRows(Collection $records, Collection $depositByState): array
    {
        $rows = $types = $statuses = [];

        foreach ($records as $order) {
            $status     = $order->state_id ? (string) $order->operational_status : 'NONE';
            $types[]    = ClosureMetaClassifier::letter($order->meta_type);
            $statuses[] = $status;
            $rows[]     = [
                ClosureMetaClassifier::letter($order->meta_type) ?? '—',
                $order->note_number,
                $order->ordem,
                ClosureMetaClassifier::competenceLabel($order->competence),
                $order->city_name,
                $order->descricao,
                $order->inform_company_name,
                $order->inform_value,
                $order->statusSist,
                $order->statusUser,
                (int) $order->ops_conf,
                (int) $order->ops_total,
                $status === 'NONE' ? 'Não despachada' : ClosureOperationalStatus::labelFor($status),
                $this->deposit($depositByState->get($order->state_id)),
                $order->assignee_name,
                $order->holder_company_name,
                $order->is_priority ? 'Sim' : 'Não',
                null,
                null,
                $this->date($order->fim_real_op20),
            ];
        }

        return compact('rows', 'types', 'statuses');
    }

    /**
     * @param Collection<int, ClosureOrderState> $states
     * @return array{rows: array<int, array<int, mixed>>, types: array<int, ?string>, statuses: array<int, string>}
     */
    public function stateRows(Collection $states, Collection $depositByState): array
    {
        $rows = $types = $statuses = [];

        $values = app(ClosureInformValue::class)->forOrders($states->pluck('order_id'));

        foreach ($states as $state) {
            $order      = $state->Order;
            $ops        = $order?->Operations ?? collect();
            $types[]    = ClosureMetaClassifier::letter($state->meta_type);
            $statuses[] = (string) $state->operational_status;
            $fim        = $ops->filter(fn ($op) => $op->operacao === '0020' && str_starts_with(strtoupper((string) $op->status), 'CONF') && $op->fimReal)->max('fimReal');
            $rows[]     = [
                ClosureMetaClassifier::letter($state->meta_type) ?? '—',
                $order?->Note?->note,
                $order?->ordem,
                ClosureMetaClassifier::competenceLabel($state->competence ?? null),
                $order?->Note?->lexp,
                $order?->descricao,
                null,
                $values[$state->order_id] ?? null,
                $order?->statusSist,
                $order?->statusUser,
                $ops->filter(fn ($op) => str_starts_with(strtoupper((string) $op->status), 'CONF'))->count(),
                $ops->count(),
                ClosureOperationalStatus::labelFor($state->operational_status),
                $this->deposit($depositByState->get($state->id)),
                $state->CurrentHolderUser?->name,
                $state->CurrentHolderCompany?->name,
                $state->priority ? 'Sim' : 'Não',
                $state->entered_current_status_at ? round($state->entered_current_status_at->diffInMinutes(now()) / 60, 1) : null,
                $this->date($state->entered_current_status_at),
                $this->date($fim),
            ];
        }

        return compact('rows', 'types', 'statuses');
    }

    /**
     * @param array<int, ?string> $types
     * @param array<int, string> $statuses
     * @return array<int, array<int, mixed>>
     */
    public function summaryRows(array $types, array $statuses): array
    {
        $rows  = [];
        $total = count($types);

        foreach (['P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura'] as $letter => $label) {
            $count  = count(array_filter($types, fn ($t) => $t === $letter));
            $rows[] = ['Tipo', "{$letter} — {$label}", $count, $total ? round($count / $total * 100, 1) : 0];
        }

        foreach (array_count_values($statuses) as $status => $count) {
            $rows[] = ['Status da atividade', $status === 'NONE' ? 'Não despachada' : ClosureOperationalStatus::labelFor($status), $count, $total ? round($count / $total * 100, 1) : 0];
        }

        $rows[] = ['TOTAL', 'Registros exportados', $total, 100];

        return $rows;
    }

    private function deposit(mixed $signal): ?string
    {
        return $signal ? $signal->deposit_code . ($signal->deposit_label ? ' · ' . $signal->deposit_label : '') : null;
    }

    private function date(mixed $value): ?float
    {
        if (!$value) {
            return null;
        }

        $carbon = $value instanceof CarbonInterface ? $value : \Carbon\Carbon::parse($value);

        return (float) ExcelDate::PHPToExcel($carbon);
    }
}
