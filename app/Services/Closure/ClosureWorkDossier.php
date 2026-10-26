<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureInternalReturn;
use App\Models\{File, Note, Reclaim, WorkReport};
use App\Services\Reclaims\ReclaimRowPresenter;
use Illuminate\Support\{Collection, Str};

/**
 * Dossiê da obra para o executor do Encerramento (somente leitura): informes registrados, todos os arquivos da Nota e o
 * histórico de Retorno Interno com o andamento no Desenho. Nada aqui altera dados de outras atividades.
 */
class ClosureWorkDossier
{
    /** Rótulos por tipo de arquivo (classificação simples por nome/extensão; o gerenciador de arquivos usa a mesma ideia). */
    public const FILE_TYPES = [
        'ads'      => 'ADS',
        'projeto'  => 'Projeto',
        'foto'     => 'Fotos',
        'planilha' => 'Planilhas',
        'outros'   => 'Outros',
    ];

    /** @return Collection<int, array<string, mixed>> informes (WorkReport) da obra, do mais recente ao mais antigo */
    public function informs(Note $note): Collection
    {
        return WorkReport::query()
            ->where('note_id', $note->id)
            ->with(['Company:id,name', 'User:id,name', 'Adsform', 'Orders:id,ordem'])
            ->withCount('Files')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WorkReport $report) => [
                'id'            => $report->id,
                'date'          => $report->date,
                'informed_at'   => $report->informed_at,
                'company'       => $report->Company?->name,
                'informer'      => $report->informer ?: $report->User?->name,
                'status_label'  => $report->current_status_label ?: ($report->canceled ? 'Cancelado' : ($report->approved ? 'Aprovado' : ($report->rejected ? 'Rejeitado' : 'Em análise'))),
                'status_class'  => $report->current_status_class ?: 'bg-secondary',
                'canceled'      => (bool) $report->canceled,
                'orders'        => $report->Orders->pluck('ordem')->all(),
                'amount'        => $report->Adsform?->amount !== null ? (float) $report->Adsform->amount : null,
                'tacit'         => (bool) $report->Adsform?->tacit,
                'tacit_due_at'  => $report->Adsform?->tacit_due_at,
                'tacit_done_at' => $report->Adsform?->tacit_delivered_at,
                'description'   => $report->description,
                'observation'   => $report->observation,
                'files'         => (int) $report->files_count,
            ]);
    }

    /**
     * Todos os arquivos da Nota (de qualquer atividade), com tipo, origem e se pertencem a uma ADS tácita.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function files(Note $note, string $type = '', string $search = ''): Collection
    {
        $storage = app(\App\Services\Files\FileStorageService::class);
        $search  = trim($search);

        return File::query()
            ->where('note_id', $note->id)
            ->with(['Service:id,uuid,service', 'User:id,name,company_id', 'User.Company:id,name'])
            ->withExists(['Adsforms as is_ads', 'Adsforms as is_tacit' => fn ($q) => $q->where('tacit', true)->whereNotNull('work_report_id')])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('file_name', 'like', "%{$search}%")->orWhere('original_name', 'like', "%{$search}%")))
            ->orderByDesc('id')
            ->get()
            ->map(function (File $file) use ($storage) {
                $kind = $this->classify($file);

                return [
                    'id'      => $file->id,
                    'name'    => $file->file_name ?: $file->original_name,
                    'ext'     => strtoupper((string) $file->extension),
                    'kind'    => $kind,
                    'origin'  => $file->Service?->service,
                    'author'  => $file->User?->name,
                    'company' => $file->User?->Company?->name,
                    'at'      => $file->created_at,
                    'size'    => $file->size,
                    'tacit'   => (bool) $file->is_tacit,
                    'exists'  => !$file->noexists && $storage->exists($file),
                ];
            })
            ->when($type !== '', fn (Collection $items) => $items->where('kind', $type))
            ->values();
    }

    /** Arquivos pedidos que de fato pertencem à Nota (nunca confiar nos ids vindos do cliente). */
    public function ownedFiles(Note $note, array $ids): Collection
    {
        return File::query()->where('note_id', $note->id)->whereIn('id', array_map('intval', $ids))->get();
    }

    /**
     * Histórico de RI da Nota com o andamento no Desenho (status do retorno, responsável, conclusão e comentários).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function internalReturns(Note $note): Collection
    {
        return ClosureInternalReturn::query()
            ->where('note_id', $note->id)
            ->with(['SentBy:id,name', 'CancelledBy:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(function (ClosureInternalReturn $ri) {
                $reclaim = $ri->reclaim_id ? Reclaim::query()->with(['Production.User', 'Production.Company', 'Comments.User', 'Subcategory.Category'])->find($ri->reclaim_id) : null;

                return [
                    'ri'           => $ri,
                    'stage'        => $this->riStage($ri, $reclaim),
                    'category'     => $reclaim?->category,
                    'subcategory'  => $reclaim?->Subcategory?->name,
                    'desenho_step' => $reclaim ? ReclaimRowPresenter::status($reclaim) : null,
                    'responsible'  => $reclaim?->Production?->User?->name,
                    'company'      => $reclaim?->Production?->Company?->name,
                    'completed_at' => $reclaim?->completed_at,
                    'comments'     => $reclaim ? $reclaim->Comments->map(fn ($c) => ['author' => $c->User?->name, 'message' => $c->message, 'at' => $c->created_at])->all() : [],
                ];
            });
    }

    /**
     * Situação do RI para quem espera: enviado, em tratamento (Desenho já pegou), retornado (Desenho concluiu e a obra foi liberada)
     * ou cancelado (a gestão liberou as Ordens sem esperar).
     *
     * @return array{key: string, label: string, tone: string, hint: string}
     */
    public function riStage(ClosureInternalReturn $ri, ?Reclaim $reclaim): array
    {
        return match (true) {
            $ri->status === ClosureInternalReturn::STATUS_RETURNED  => ['key' => 'returned', 'label' => 'Retornado pelo Desenho', 'tone' => 'green', 'hint' => 'O Desenho concluiu e as Ordens foram destravadas.'],
            $ri->status === ClosureInternalReturn::STATUS_CANCELLED => ['key' => 'cancelled', 'label' => 'Cancelado', 'tone' => 'muted', 'hint' => 'A gestão liberou as Ordens sem esperar o Desenho.'],
            $reclaim?->production_id !== null                       => ['key' => 'working', 'label' => 'Em tratamento no Desenho', 'tone' => 'blue', 'hint' => 'O Desenho já assumiu o retorno. As Ordens seguem travadas.'],
            default                                                 => ['key' => 'sent', 'label' => 'Aguardando o Desenho', 'tone' => 'amber', 'hint' => 'Enviado ao Desenho; ainda não foi assumido. As Ordens seguem travadas.'],
        };
    }

    private function classify(File $file): string
    {
        if ($file->is_ads ?? false) {
            return 'ads';
        }

        $name = Str::lower(Str::ascii(($file->original_name ?: '') . ' ' . ($file->file_name ?: '')));
        $ext  = Str::lower((string) $file->extension);

        return match (true) {
            str_contains($name, 'ads')                                         => 'ads',
            in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif'], true) => 'foto',
            in_array($ext, ['xls', 'xlsx', 'csv', 'ods'], true)                => 'planilha',
            in_array($ext, ['dwg', 'dxf', 'pdf', 'kml', 'kmz'], true)          => 'projeto',
            default                                                            => 'outros',
        };
    }
}
