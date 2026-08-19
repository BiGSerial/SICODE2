<?php

namespace App\Console\Commands\Fix;

use App\Models\Edp_depc\BaseOV;
use App\Models\HistoricNote;
use App\Models\Note;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class fixBaseDestiny extends Command
{
    protected $signature = 'sicode:fix_destinyBase
        {--status= : Status/numStat que deve ser conferido}
        {--ov= : OV especifica que deve ser corrigida}
        {--fix : Aplica as correcoes encontradas}
        {--refresh : Com --fix, atualiza todas as OVs atuais da origem para o status informado}
        {--limit=50 : Quantidade maxima de OVs exibidas por grupo}';

    protected $description = 'Compare and fix BaseOV origin records against notes destination records.';

    public function handle(): int
    {
        $status = $this->option('status');
        $ov = $this->option('ov');

        if (!$status && !$ov) {
            $this->error('Informe --status=XX para diagnosticar/corrigir um status ou --ov=XXXX para corrigir uma OV.');

            return Command::FAILURE;
        }

        if ($ov) {
            return $this->fixOv((string) $ov);
        }

        return $this->checkStatus((string) $status);
    }

    private function checkStatus(string $status): int
    {
        $fix = (bool) $this->option('fix');
        $statusValue = ctype_digit($status) ? (int) $status : $status;

        $originOvs = BaseOV::query()
            ->where('numStat', $statusValue)
            ->where('ultimoStatus', 1)
            ->distinct()
            ->pluck('OV')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $destinyOvs = Note::query()
            ->where('nstats', $statusValue)
            ->where('type_note', 2)
            ->distinct()
            ->pluck('note')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $missingInDestiny = $originOvs->diff($destinyOvs)->values();
        $missingInOrigin = $destinyOvs->diff($originOvs)->values();
        $duplicateDestiny = $this->duplicateDestinyOvs($status);

        $this->info("Status {$status}");
        $this->line("Origem BaseOV: {$originOvs->count()} OV(s)");
        $this->line("Destino notes: {$destinyOvs->count()} OV(s)");
        $this->line("Na origem e fora do destino/status {$status}: {$missingInDestiny->count()}");
        $this->line("No destino/status {$status} e fora da origem atual: {$missingInOrigin->count()}");
        $this->line("Duplicadas no destino/status {$status}: {$duplicateDestiny->count()}");

        $this->printOvList('Origem sem destino/status', $missingInDestiny);
        $this->printOvList('Destino sem origem atual neste status', $missingInOrigin);
        $this->printOvList('Duplicadas no destino', $duplicateDestiny);

        if (!$fix) {
            $this->comment('Nenhuma alteracao aplicada. Use --fix para corrigir.');

            return Command::SUCCESS;
        }

        $updated = 0;
        $created = 0;
        $notFound = 0;
        $candidateOrigins = $this->option('refresh')
            ? $originOvs
            : $missingInDestiny;

        if ($originOvs->isEmpty() && $destinyOvs->isNotEmpty()) {
            $this->error('ABORTADO: a origem BaseOV retornou 0 OVs, mas o destino tem registros. Isso indica falha de consulta/conexao ou status invalido; nenhuma correcao foi aplicada.');

            return Command::FAILURE;
        }

        foreach ($this->currentOriginsByOv($candidateOrigins) as $origin) {
            $result = $this->applyOriginToDestiny($origin);
            $updated += $result === 'updated' ? 1 : 0;
            $created += $result === 'created' ? 1 : 0;
        }

        $currentOrigins = $this->currentOriginsByOv($missingInOrigin);

        foreach ($missingInOrigin as $extraDestinyOv) {
            $origin = $currentOrigins->get((string) $extraDestinyOv);

            if (!$origin) {
                $notFound++;
                continue;
            }

            $result = $this->applyOriginToDestiny($origin);
            $updated += $result === 'updated' ? 1 : 0;
            $created += $result === 'created' ? 1 : 0;
        }

        $mode = $this->option('refresh') ? 'com refresh completo' : 'somente divergencias';
        $this->info("Correcoes aplicadas ({$mode}). Atualizadas: {$updated}. Criadas: {$created}. Sem origem atual: {$notFound}.");

        return Command::SUCCESS;
    }

    private function fixOv(string $ov): int
    {
        $this->info("Corrigindo OV: {$ov}");

        $origin = BaseOV::query()
            ->where('OV', $ov)
            ->where('ultimoStatus', 1)
            ->first();

        if (!$origin) {
            $this->error('OV nao encontrada na BaseOV com ultimoStatus=1. Impossivel corrigir.');

            return Command::FAILURE;
        }

        $result = $this->applyOriginToDestiny($origin);

        $this->info("OV {$ov}: {$result}.");

        return Command::SUCCESS;
    }

    private function applyOriginToDestiny(BaseOV $origin): string
    {
        $ov = trim((string) $origin->OV);
        $destiny = Note::query()
            ->where('note', $ov)
            ->where('type_note', 2)
            ->first();

        $data = $this->baseOvToNoteData($origin, $destiny?->lexp);

        if ($destiny) {
            if ((string) $destiny->nstats !== (string) $origin->numStat) {
                HistoricNote::query()->create([
                    'note_id'  => $destiny->id,
                    'old_date' => $destiny->dt_status,
                    'old_stat' => $destiny->nstats,
                    'new_date' => $origin->dhStat,
                    'new_stat' => $origin->numStat,
                ]);
            }

            $destiny->update($data);

            return 'updated';
        }

        Note::query()->create(array_merge(['note' => $ov], $data));

        return 'created';
    }

    private function baseOvToNoteData(BaseOV $origin, ?string $currentLexp = null): array
    {
        return [
            'created_by'    => $origin->criadoPor,
            'dt_created'    => "{$origin->dtCriacao} {$origin->hrCriacao}",
            'dt_status'     => $origin->dhStat,
            'user'          => $origin->usuario,
            'value'         => $origin->valorLiq,
            'currency'      => $origin->moeda,
            'eq_venda'      => $origin->eqVenda,
            'numPedido'     => $origin->numPedido,
            'client'        => $origin->emissorOV,
            'group1'        => $origin->grpCliente1,
            'group2'        => $origin->grpCliente2,
            'group3'        => $origin->grpCliente3,
            'group4'        => $origin->grpCliente4,
            'group5'        => $origin->grpCliente5,
            'pze'           => $origin->PzE,
            'num_material'  => $origin->numMaterial,
            'material'      => $origin->material,
            'nexp'          => $origin->numExp,
            'lexp'          => $origin->localExp ?? $currentLexp,
            'pep'           => $origin->PEP,
            'nstats'        => $origin->numStat,
            'status'        => $origin->status,
            'days'          => $origin->dias,
            'transaction'   => $origin->transicao,
            'validar_prazo' => $origin->considerarPrazo,
            'rubrica'       => $origin->rubrica,
            'pze_tratado'   => $origin->PzETratado,
            'days_stat'     => $origin->diasNoStatus,
            'pze_parecer'   => $origin->parecerPrazo,
            'days_left'     => $origin->diasPVencimento,
            'type_note'     => 2,
        ];
    }

    private function duplicateDestinyOvs(string $status): Collection
    {
        return Note::query()
            ->selectRaw('note, COUNT(*) as total')
            ->where('nstats', $status)
            ->where('type_note', 2)
            ->groupBy('note')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('note')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();
    }

    private function currentOriginsByOv(Collection $ovs): Collection
    {
        if ($ovs->isEmpty()) {
            return collect();
        }

        $origins = collect();

        $ovs->map(fn ($ov) => (string) $ov)
            ->filter()
            ->unique()
            ->chunk(500)
            ->each(function (Collection $chunk) use ($origins) {
                BaseOV::query()
                    ->where('ultimoStatus', 1)
                    ->whereIn('OV', $chunk->all())
                    ->get()
                    ->each(function (BaseOV $origin) use ($origins) {
                        $origins->put(trim((string) $origin->OV), $origin);
                    });
            });

        return $origins;
    }

    private function printOvList(string $title, Collection $ovs): void
    {
        if ($ovs->isEmpty()) {
            return;
        }

        $limit = max((int) $this->option('limit'), 1);
        $shown = $ovs->take($limit)->implode(', ');
        $remaining = $ovs->count() - min($ovs->count(), $limit);

        $this->line("{$title}: {$shown}" . ($remaining > 0 ? " ... (+{$remaining})" : ''));
    }
}
