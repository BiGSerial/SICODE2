<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Identidade persistente de uma demanda de Medição/Pagamento.
 *
 * Esta tabela não substitui os pivots legados. Durante a migração, o serviço de
 * despacho deve gravar contexto e pivots na mesma transação. O contexto é a
 * fonte de verdade para decidir qual Informe, Parcial, D5 e ciclo pertencem à
 * Production.
 */
class MeasurementProductionContext extends Model
{
    use HasFactory;

    public const CYCLE_PARTIAL   = 'partial';
    public const CYCLE_ONE       = 'cycle_1';
    public const CYCLE_D5_RETURN = 'd5_return';

    protected $fillable = [
        'production_id',
        'work_report_id',
        'partial_id',
        'five_note_id',
        'cycle',
        'active_demand_key',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $context): void {
            $context->assertValidIdentity();
        });
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function workReport(): BelongsTo
    {
        return $this->belongsTo(WorkReport::class);
    }

    public function partial(): BelongsTo
    {
        return $this->belongsTo(Partial::class);
    }

    public function fiveNote(): BelongsTo
    {
        return $this->belongsTo(FiveNote::class);
    }

    /**
     * A chave é deliberadamente apagável no encerramento. Não a use como
     * histórico; cycle_1 e d5_return possuem unicidades permanentes próprias.
     */
    public static function partialActiveDemandKey(int $partialId): string
    {
        return "partial:{$partialId}";
    }

    public function assertValidIdentity(): void
    {
        $hasWorkReport = !is_null($this->work_report_id);
        $hasPartial    = !is_null($this->partial_id);

        $isPassiveD5ReturnWithoutReport = $this->cycle === self::CYCLE_D5_RETURN
            && !$hasWorkReport
            && !$hasPartial
            && !is_null($this->five_note_id);

        if ($hasWorkReport === $hasPartial && !$isPassiveD5ReturnWithoutReport) {
            throw new InvalidArgumentException('O contexto de Medição deve possuir exatamente um Informe Final ou Informe Parcial.');
        }

        if (!in_array($this->cycle, self::cycles(), true)) {
            throw new InvalidArgumentException('O ciclo de Medição informado é inválido.');
        }

        if ($this->cycle === self::CYCLE_PARTIAL && (!$hasPartial || !is_null($this->five_note_id))) {
            throw new InvalidArgumentException('Medição parcial exige Informe Parcial e não aceita D5.');
        }

        if ($this->cycle === self::CYCLE_ONE && !$hasWorkReport) {
            throw new InvalidArgumentException('Ciclo 1 exige Informe Final.');
        }

        if ($this->cycle === self::CYCLE_D5_RETURN && is_null($this->five_note_id)) {
            throw new InvalidArgumentException('Retorno de D5 exige D5 específica.');
        }

        if ($isPassiveD5ReturnWithoutReport) {
            $fiveNote = FiveNote::find($this->five_note_id);

            if (!$fiveNote || !$fiveNote->isPassive || !$fiveNote->isEligibleForPaymentReturn()) {
                throw new InvalidArgumentException('Retorno de D5 sem Informe exige D5 passiva e elegível.');
            }
        }
    }

    public static function cycles(): array
    {
        return [
            self::CYCLE_PARTIAL,
            self::CYCLE_ONE,
            self::CYCLE_D5_RETURN,
        ];
    }
}
