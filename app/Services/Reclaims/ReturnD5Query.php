<?php

namespace App\Services\Reclaims;

use App\Models\{City, Reclaim};
use Illuminate\Database\Eloquent\Builder;

/**
 * Consulta única da lista de Retorno Interno (RI) do Despacho: a tela, o Excel (tudo ou só as selecionadas) e a contagem leem daqui,
 * então nunca divergem. Padrão da lista: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 *
 * A origem do retorno segue a mesma precedência do ReclaimRowPresenter::source() (a última regra que casa vence):
 * Contratação (padrão) < Análise de Projeto < Contratação (aguardando) < Viabilidade < Órgão Externo.
 */
class ReturnD5Query
{
    public const ORIGIN_CONTRACT  = 'contract';
    public const ORIGIN_PROJECT   = 'project';
    public const ORIGIN_VIABILITY = 'viability';
    public const ORIGIN_EXTERNAL  = 'external';

    /** @var array<string, string> */
    public const ORIGINS = [
        self::ORIGIN_CONTRACT  => ReclaimRowPresenter::ORIGIN_CONTRACT,
        self::ORIGIN_PROJECT   => ReclaimRowPresenter::ORIGIN_PROJECT,
        self::ORIGIN_VIABILITY => ReclaimRowPresenter::ORIGIN_VIABILITY,
        self::ORIGIN_EXTERNAL  => ReclaimRowPresenter::ORIGIN_EXTERNAL,
    ];

    /** Colunas em que a tela pode ordenar (coluna da tabela => coluna de `reclaims`/`notes` já com alias no select). */
    public const SORTABLE = ['note', 'rubrica', 'lexp', 'group5', 'material', 'category', 'created_at'];

    /**
     * @param array{search?: ?string, filters?: array<string, array<int, string>>, origin?: ?string, notAtt?: bool, user?: ?string, sort?: ?string, direction?: ?string, ids?: array<int, int|string>} $criteria
     */
    public function query(string $serviceUuid, array $criteria = []): Builder
    {
        $search  = trim((string) ($criteria['search'] ?? ''));
        $filters = (array) ($criteria['filters'] ?? []);
        $sort    = in_array($criteria['sort'] ?? null, self::SORTABLE, true) ? $criteria['sort'] : 'created_at';
        $sort    = $sort === 'created_at' ? 'reclaims.created_at' : $sort;
        $dir     = ($criteria['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query = Reclaim::query()
            ->join('notes as n', 'reclaims.note_id', '=', 'n.id')
            ->where('reclaims.service_id', $serviceUuid)
            ->where('reclaims.completed', false)
            ->select('reclaims.*', 'n.note as note', 'n.rubrica as rubrica', 'n.group5 as group5', 'n.material as material', 'n.lexp as lexp')
            // `service`/`note` em minúsculas: é o nome que o componente de arquivos lê, e o cache de relações do Eloquent diferencia maiúscula.
            ->with(array_merge(ReclaimRowPresenter::RELATIONS, ['Note.Files.service', 'Note.Files.note']))
            ->orderBy($sort, $dir)
            ->orderBy('reclaims.id');

        if (!empty($criteria['ids'])) {
            $query->whereIn('reclaims.id', (array) $criteria['ids']);
        }

        if (filled($criteria['user'] ?? null)) {
            $query->whereRelation('Production', 'user_id', $criteria['user']);
        }

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';

            $query->where(fn ($q) => $q->where('n.note', 'like', $like)
                ->orWhere('n.rubrica', 'like', $like)
                ->orWhere('n.group5', 'like', $like)
                ->orWhere('n.material', 'like', $like)
                ->orWhere('n.lexp', 'like', $like));
        }

        if ($rubricas = $this->values($filters, 'rubrica')) {
            $query->whereIn('n.rubrica', $rubricas);
        }

        if (($codes = $this->cityCodes($filters)) !== null) {
            $query->whereIn('n.nexp', $codes);
        }

        if (!empty($criteria['notAtt'])) {
            $query->whereDoesntHave('Production');
        }

        $this->applyOrigin($query, (string) ($criteria['origin'] ?? ''));

        return $query;
    }

    /**
     * Ids dos arquivos de ADS tácita entre os arquivos das notas informadas, numa consulta só (chave = id). O componente de arquivos da
     * linha recebe isto e não consulta por linha.
     *
     * @param iterable<int, int|string> $fileIds
     *
     * @return array<int, int>
     */
    public function tacitFileIds(iterable $fileIds): array
    {
        $ids = collect($fileIds)->unique()->values()->all();

        if (!$ids) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('adsforms_files as af')
            ->join('adsforms as a', 'a.id', '=', 'af.adsform_id')
            ->whereIn('af.file_id', $ids)
            ->where('a.tacit', true)
            ->whereNotNull('a.work_report_id')
            ->pluck('af.file_id')
            ->flip()
            ->all();
    }

    /**
     * Município (código `rdMunicipio`) das notas que passam pelos filtros de Região, Regional e Município; `null` se nenhum foi marcado.
     * Os três se somam (E): a cascata do filtro já garante que o município marcado pertence à região e à regional marcadas.
     *
     * @param array<string, array<int, string>> $filters
     *
     * @return array<int, string>|null
     */
    public function cityCodes(array $filters): ?array
    {
        $regions   = $this->values($filters, 'region');
        $regionals = $this->values($filters, 'regional');
        $cities    = $this->values($filters, 'city');

        if (!$regions && !$regionals && !$cities) {
            return null;
        }

        return City::query()
            ->when($regions, fn ($q) => $q->whereIn('regiao', $regions))
            ->when($regionals, fn ($q) => $q->whereIn('regional', $regionals))
            ->when($cities, fn ($q) => $q->whereIn('rdMunicipio', $cities))
            ->pluck('rdMunicipio')
            ->filter(fn ($code) => filled($code))
            ->map(fn ($code) => trim((string) $code))
            ->unique()
            ->values()
            ->all();
    }

    /** Filtra pela origem já resolvida pela precedência (as quatro origens particionam a lista, sem sobreposição). */
    private function applyOrigin(Builder $query, string $origin): void
    {
        match ($origin) {
            self::ORIGIN_EXTERNAL  => $query->whereHas('Externals'),
            self::ORIGIN_VIABILITY => $query->whereDoesntHave('Externals')->whereHas('Viabilities'),
            self::ORIGIN_PROJECT   => $query->whereDoesntHave('Externals')->whereDoesntHave('Viabilities')->whereDoesntHave('Waiting')->whereHas('Approvals'),
            self::ORIGIN_CONTRACT  => $query->whereDoesntHave('Externals')->whereDoesntHave('Viabilities')
                ->where(fn ($q) => $q->whereHas('Waiting')->orWhereDoesntHave('Approvals')),
            default => null,
        };
    }

    /** @param array<string, mixed> $filters */
    private function values(array $filters, string $key): array
    {
        return collect((array) ($filters[$key] ?? []))->filter(fn ($v) => filled($v))->map(fn ($v) => (string) $v)->values()->all();
    }
}
