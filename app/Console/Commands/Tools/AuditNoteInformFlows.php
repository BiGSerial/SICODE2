<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Artisan, DB};

class AuditNoteInformFlows extends Command
{
    protected $signature = 'sicode:audit-note-inform-flows
        {--fix : Recalcula as notas inconsistentes (sync por nota) e desativa orfaos}
        {--limit=20 : Quantidade de exemplos exibidos por verificacao}';

    protected $description = 'Compara note_inform_flows com as tabelas de origem e aponta linhas faltando, vazias ou orfas.';

    public function handle(): int
    {
        $limit    = max(1, (int) $this->option('limit'));
        $noteIds  = [];
        $orphans  = 0;
        $problems = 0;

        $checks = [
            'Informes finais sem linha ativa no consolidado' => DB::table('work_reports as wr')
                ->where('wr.canceled', false)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('note_inform_flows as nif')
                    ->whereColumn('nif.work_report_id', 'wr.id')->where('nif.flow_type', 'final')->where('nif.active', true))
                ->select('wr.note_id', 'wr.id as ref'),

            'Parciais sem linha ativa no consolidado' => DB::table('partials as p')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('note_inform_flows as nif')
                    ->whereColumn('nif.partial_id', 'p.id')->where('nif.flow_type', 'partial')->where('nif.active', true))
                ->select('p.note_id', 'p.id as ref'),

            'Vinculo de Fiscalizacao existente, mas fiscalization_production_id vazio' => $this->missingLinkQuery('fiscalization', 'fiscalization_production_id'),

            'Vinculo de Pagamento existente, mas measurement_production_id vazio' => $this->missingLinkQuery('payment', 'measurement_production_id'),

            'Adsform existente, mas ads_form_id vazio' => DB::table('note_inform_flows as nif')
                ->where('nif.flow_type', 'final')->where('nif.active', true)->where('nif.publication_required', true)
                ->whereNull('nif.ads_form_id')
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('adsforms as af')->whereColumn('af.work_report_id', 'nif.work_report_id'))
                ->select('nif.note_id', 'nif.id as ref'),

            'D5 vinculada ao informe, mas has_d5/five_note_number vazio' => DB::table('note_inform_flows as nif')
                ->where('nif.flow_type', 'final')->where('nif.active', true)
                ->where(fn ($q) => $q->where('nif.has_d5', false)->orWhereNull('nif.five_note_number'))
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('five_notes as fn')->whereColumn('fn.work_report_id', 'nif.work_report_id'))
                ->select('nif.note_id', 'nif.id as ref'),

            'D5 com Fiscalizacao/Pagamento D5, mas marcos vazios' => DB::table('note_inform_flows as nif')
                ->where('nif.flow_type', 'final')->where('nif.active', true)->whereNotNull('nif.five_note_id')
                ->whereNull('nif.fiscalization_production_id')
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('productionables as pa')
                    ->whereColumn('pa.productionable_id', 'nif.five_note_id')
                    ->where('pa.productionable_type', 'App\\Models\\FiveNote'))
                ->select('nif.note_id', 'nif.id as ref'),
        ];

        foreach ($checks as $title => $query) {
            $rows  = $query->get();
            $count = $rows->count();

            $this->line(sprintf('[%s] %s: %d', $count ? 'X' : 'OK', $title, $count));

            if ($count) {
                $problems += $count;
                $this->line('    exemplos (note_id/ref): ' . $rows->take($limit)->map(fn ($r) => "{$r->note_id}/{$r->ref}")->implode(', '));
                $noteIds = array_merge($noteIds, $rows->pluck('note_id')->all());
            }
        }

        // Orfaos: a origem foi apagada (hard delete) ou cancelada e a linha segue ativa.
        $orphans = DB::table('note_inform_flows as nif')->where('nif.active', true)
            ->where(function ($q) {
                $q->where(fn ($w) => $w->where('nif.flow_type', 'partial')
                    ->whereNotExists(fn ($s) => $s->selectRaw('1')->from('partials as p')->whereColumn('p.id', 'nif.partial_id')))
                    ->orWhere(fn ($w) => $w->where('nif.flow_type', 'final')
                        ->whereNotExists(fn ($s) => $s->selectRaw('1')->from('work_reports as wr')
                            ->whereColumn('wr.id', 'nif.work_report_id')->where('wr.canceled', false)));
            })->count();
        $this->line(sprintf('[%s] Linhas ativas sem origem (deletadas/canceladas): %d', $orphans ? 'X' : 'OK', $orphans));

        if (!$this->option('fix')) {
            return ($problems + $orphans) ? self::FAILURE : self::SUCCESS;
        }

        foreach (array_unique(array_filter($noteIds)) as $noteId) {
            Artisan::call('sicode:sync-note-inform-flows', ['--note_id' => $noteId]);
        }

        // Sem --note_id e sem janela: so reconcilia orfaos, sem recalcular a base.
        Artisan::call('sicode:sync-note-inform-flows', ['--hours' => 1]);

        $this->info('Notas recalculadas: ' . count(array_unique(array_filter($noteIds))) . '. Rode novamente sem --fix para confirmar.');

        return self::SUCCESS;
    }

    private function missingLinkQuery(string $stage, string $column)
    {
        return DB::table('note_inform_flows as nif')
            ->where('nif.flow_type', 'final')->where('nif.active', true)
            ->whereNull("nif.{$column}")
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('work_report_flow_productions as wrfp')
                ->whereColumn('wrfp.work_report_id', 'nif.work_report_id')
                ->where('wrfp.stage', $stage)->where('wrfp.is_current', true))
            ->select('nif.note_id', 'nif.id as ref');
    }
}
