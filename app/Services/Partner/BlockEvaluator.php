<?php

namespace App\Services\Partner;

use App\Models\Note;
use App\Models\Partial;
use App\Models\Production;
use App\Models\Service;
use App\Models\WorkReport;

class BlockEvaluator
{
    public const FREE        = 0; // Pode Informar
    public const HOLD_BLUE   = 1; // Agujardando Conclusao de Parcial
    public const HOLD_YELLOW = 2; // Obra Fora de Status de Construção
    public const HOLD_GREEN  = 3; // Concluído
    public const HOLD_RED    = 4; // Obra Já Informada


    private const CONSTRUCTION_NSTATS = [51, 52, 53];


    public function evaluate(Note $note)
    {
        // Carrega WorkForm e a parcial mais recente (via relação currentPartial)
        $note->load(['WorkForm', 'partials']);

        $payment_id = Service::where('service', 'Pagamento')->value('uuid');

        $supervision_id = Service::where('service', 'Fiscalização')->value('uuid');

        $wf = $note->WorkForm;

        $ps = $note->partials()?->orderBy('created_at', 'desc')->first();

        $production = null;
        ;

        // 1) Já informada?
        if ($wf) {
            return $this->res(self::HOLD_RED, false, 'Obra já informada', null, $wf);
        }

        // 2) Parcial ativa aguardando algo?
        if ($ps && $this->isActivePartial($ps)) {
            // Determine service ID based on supervision status
            $serviceId = !$ps->supervision ? $supervision_id : $payment_id;

            // Only execute one query with the correct service ID
            $production = $note->productions()
            ->where('service_id', $serviceId)
            ->where('partial', true)
            ->where('completed', false)
            ->with(['user:id,name', 'service:uuid,service'])
            ->orderBy('created_at', 'desc')
            ->first();

            $reason = !$ps->supervision
            ? 'Aguardando fiscalização da parcial'
            : 'Aguardando pagamento da parcial';

            return $this->res(self::HOLD_BLUE, false, $reason, $ps, null, $production);
        }


        // // 3) Obra fora de status de construção
        if ($note->type_note == 2 && !$this->isConstructionNstats($note->nstats)) {
            return $this->res(self::HOLD_YELLOW, false, '<strong>Obra fora de status de construção</strong> <p>Entre em contato com o engenheiro da sua região</p>');
        }

        // // 4) Obra fora de status de construção
        if ($note->type_note == 1 && $this->centerjobIsSetAndNotCONS($note->centerjob)) {
            return $this->res(self::HOLD_YELLOW, false, '<strong>Obra fora de status de construção</strong> <p>Entre em contato com o engenheiro da sua região</p>');
        }

        // 5) Liberado
        return $this->res(self::FREE, true, 'Liberado para informar');
    }

    private function isActivePartial(Partial $ps): bool
    {
        // Garante boolean
        return (bool)$ps->allow === true
            && (bool)$ps->deny === false
            && (bool)$ps->complete === false;
    }

    private function isConstructionNstats(int $nstats): bool
    {
        return in_array($nstats, self::CONSTRUCTION_NSTATS, true);
    }

    private function centerjobIsSetAndNotCONS(?string $centerjob): bool
    {
        if (!$centerjob) {
            return false; // sem centerjob não dispara essa regra
        }

        $normalized = mb_strtoupper(trim($centerjob));
        // PHP 8+: testa se NÃO inicia com 'CONS'
        return !str_starts_with($normalized, 'CONS');
    }

    private function res(int $block, bool $command, string $reason, ?Partial $partial = null, ?WorkReport $work = null, ?Production $production = null): object
    {
        return (object)[
            'block'      => $block,
            'command'    => $command,
            'color'      => $this->colorFor($block),
            'reason'     => $reason,
            'partial'    => $partial,
            'workform'   => $work,
            'production' => $production,
        ];
    }

    private function colorFor(int $block): string
    {
        return match ($block) {
            self::FREE        => '',
            self::HOLD_BLUE   => 'table-primary',
            self::HOLD_YELLOW => 'table-warning',
            self::HOLD_GREEN  => 'table-success',
            self::HOLD_RED    => 'table-danger',
            default           => '',
        };
    }
}
