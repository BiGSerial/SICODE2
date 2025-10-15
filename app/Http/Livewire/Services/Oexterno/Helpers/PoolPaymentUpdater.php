<?php

namespace App\Http\Livewire\Services\Oexterno\Helpers;

use App\Models\ExternalPoolpayment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class PoolPaymentUpdater extends Component
{
    use WithFileUploads;

    /** @var \Livewire\TemporaryUploadedFile|null */
    public $file = null;

    public $readyToProcess = false;

    public $preview = [
        'filename'   => null,
        'size'       => null,
        'rows'       => 0,
        'columns'    => [],
        'missing'    => [],
        'typeErrors' => [], // ['row'=>n,'column'=>'col','value'=>'x','expected'=>'type']
    ];

    private const COL_ALIASES = [
    'ID da solicitação' => [
        'id da solicitacao',     // sem acento
        'id_da_solicitacao',     // snake_case
        'id da solicitacaoo',    // typo comum (oo)
        'id_da_solicitacaoo',    // snake + typo
    ],
    // se quiser, adicione aliases para outros cabeçalhos sensíveis (N° vs No vs Numero, etc.)
];

    /**
     * "Cabeçalho do arquivo" => "coluna_no_model".
     * Ajuste as chaves à ESQUERDA p/ os nomes exatos do seu XLSX/CSV.
     */
    private const COLMAP = [
    // Identificador
    'ID da solicitação'                 => 'pool_id',
    // 'Id pedido'                         => 'id_pedido',

    // Metadados principais
    'Solicitação de Pagamento'          => 'solicitacao_pagamento',
    'Criação de Pedido'                 => 'criacao_pedido',
    'Material ou Serviço'               => 'material_servico',
    'Status do pedido'                  => 'status_pedido',

    // Contrato / fornecedor / parceiro
    'Local de Prestação de Serviço'     => 'local_prestacao_servico',
    'N° Contrato'                       => 'numero_contrato',
    'Código de Fornecedor'              => 'codigo_fornecedor',
    'Fornecedor'                        => 'fornecedor',
    'Tipo Documento Fornecedor'         => 'tipo_documento_fornecedor',
    'Código Parceiro SAP'               => 'codigo_parceiro_sap',

    // Pessoas/Organização
    'Gestor'                            => 'gestor',
    'Empresa EDP'                       => 'empresa_edp',
    'CNPJ da Empresa do grupo EDP'      => 'cnpj_empresa_grupo_edp',
    'Centro Logístico'                  => 'centro_logistico',
    'Responsável Pool'                  => 'responsavel_pool',

    // NF / contábil
    'Data de recebimento da NF'         => 'data_recebimento_nf',
    'Mês'                               => 'mes',
    'Ano'                               => 'ano',
    'N° NF'                             => 'numero_nf',
    'Classe Contábil'                   => 'classe_contabil',
    'Rateio'                            => 'rateio',
    'Centro/Ordem/Diagrama'             => 'centro_ordem_diagrama',
    'Operação Diagrama'                 => 'operacao_diagrama',
    'Data de Emissão da NF'             => 'data_emissao_nf',

    // Pagamento
    'Forma de Pagamento'                => 'forma_pagamento',
    'Baixa de Adiantamento'             => 'baixa_adiantamento',
    'Data de Vencimento'                => 'data_vencimento',
    'Moeda'                             => 'moeda',
    'Valor'                             => 'valor',
    'Observações'                       => 'observacoes',
    'Solicitante'                       => 'solicitante',

    // Marcos de processo
    'FI (FBV0)'                         => 'fi_fbv0',
    'Pedido (ME28)'                     => 'pedido_me28',
    'Medição (ML85)'                    => 'medicao_ml85',
    'MIRO (NÚMERO)'                     => 'miro_numero',

    // Workflow
    'Data Envio Pedido Aprovação'       => 'data_envio_pedido_aprovacao',
    'Data da Aprovação Pedido'          => 'data_aprovacao_pedido',
    'Data Envio Medição Aprovação'      => 'data_envio_medicao_aprovacao',
    'Data de Aprovação da Medição'      => 'data_aprovacao_medicao',
    'Data de Envio ao Financeiro'       => 'data_envio_financeiro',

    // Flags / link
    'Aprovado'                          => 'aprovado',
    'Link da Solicitação'               => 'link_solicitacao',
];


    /**
     * Tipagem esperada (espelho dos $casts do model).
     * Use as chaves de DESTINO (valores do COLMAP).
     */
    private const TYPES = [
        'pool_id'     => 'int',

        // datetimes
        'criacao_pedido'               => 'datetime',
        'data_envio_pedido_aprovacao'  => 'datetime',
        'data_aprovacao_pedido'        => 'datetime',
        'data_envio_medicao_aprovacao' => 'datetime',
        'data_aprovacao_medicao'       => 'datetime',
        'data_envio_financeiro'        => 'datetime',

        // dates
        'data_recebimento_nf' => 'date',
        'data_emissao_nf'     => 'date',
        'data_vencimento'     => 'date',

        // numéricos
        'mes'   => 'int',
        'ano'   => 'int',
        'valor' => 'decimal:2',

        // booleanos
        'baixa_adiantamento' => 'bool',
        'aprovado'           => 'bool',
    ];

    protected function rules()
    {
        return [
            'file' => 'required|file|max:10240|mimetypes:text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,application/csv',
        ];
    }

    /* ===================== Ciclo ===================== */

    public function updatedFile()
    {
        $this->resetPreview();

        $this->validate();

        $original = $this->file->getClientOriginalName();
        $size     = $this->file->getSize();

        $rows = $this->loadRows(); // arrays associativos (cabeçalho => valor)

        // dd($rows[0]);

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'file' => 'Não foi possível ler linhas do arquivo (verifique o conteúdo).',
            ]);
        }

        // Normaliza cabeçalho do arquivo
        $rows = array_map(function ($row) {
            $norm = [];
            foreach ($row as $k => $v) {
                $norm[$this->normHeader($k)] = $v;
            }
            return $norm;
        }, $rows);

        $header = array_keys($rows[0]);
        $presentMap = $this->buildPresentMap($header);

        // Verificação mínima obrigatória: pool_id
        $missing = [];
        if (!in_array('pool_id', array_values($presentMap), true)) {
            $missing[] = 'pool_id (precisa estar no arquivo via algum cabeçalho mapeado p/ pool_id)';
        }

        // Validação de tipos
        $typeErrors = [];
        foreach ($rows as $i => $row) {
            foreach ($presentMap as $srcHeader => $destAttr) {
                $value = Arr::get($row, $srcHeader);
                if ($value === null || $value === '') {
                    continue;
                }
                if (isset(self::TYPES[$destAttr]) && !$this->isValueCompatible(self::TYPES[$destAttr], $value)) {
                    $typeErrors[] = [
                        'row'      => $i + 2,
                        'column'   => $destAttr,
                        'value'    => is_scalar($value) ? (string)$value : gettype($value),
                        'expected' => self::TYPES[$destAttr],
                    ];
                }
            }
        }

        $this->preview = [
            'filename'   => $original,
            'size'       => $size,
            'rows'       => count($rows),
            'columns'    => array_values($presentMap),
            'missing'    => $missing,
            'typeErrors' => $typeErrors,
        ];

        $this->readyToProcess = empty($missing) && empty($typeErrors);
        $this->dispatchBrowserEvent('poolpayment:loaded');
        $this->emitSelf('fileLoaded');
    }

    public function removeFile(): void
    {
        $this->resetPreview();
        $this->file?->delete();
        $this->file = null;
        $this->dispatchBrowserEvent('poolpayment:cleared');
    }

    public function process(): void
    {
        if (!$this->file || !$this->readyToProcess) {
            throw ValidationException::withMessages(['file' => 'Selecione um arquivo válido antes de processar.']);
        }

        $rows = $this->loadRows();
        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'Arquivo sem linhas legíveis.']);
        }

        $rows = array_map(function ($row) {
            $norm = [];
            foreach ($row as $k => $v) {
                $norm[$this->normHeader($k)] = $v;
            }
            return $norm;
        }, $rows);

        $header        = array_keys($rows[0]);
        $presentMap    = $this->buildPresentMap($header);
        $poolHeaderKey = array_search('pool_id', $presentMap, true);

        if ($poolHeaderKey === false) {
            throw ValidationException::withMessages(['file' => 'Nenhum cabeçalho mapeado para pool_id foi encontrado.']);
        }

        // Extrai pool_ids
        $poolIds = [];
        foreach ($rows as $row) {
            $v = $row[$poolHeaderKey] ?? null;
            if ($v !== null && $v !== '') {
                $i = $this->toInt($v);
                if ($i !== null) {
                    $poolIds[] = $i;
                }
            }
        }
        $poolIds = array_values(array_unique(array_filter($poolIds, fn ($v) => $v !== null)));

        if (empty($poolIds)) {
            throw ValidationException::withMessages(['file' => 'Nenhum pool_id válido encontrado no arquivo.']);
        }

        // Busca existentes
        $existing = ExternalPoolpayment::query()
            ->whereIn('pool_id', $poolIds)
            ->pluck('id', 'pool_id'); // [pool_id => id]

        if ($existing->isEmpty()) {
            $this->removeFile();
            $this->dispatchBrowserEvent('poolpayment:done', ['updated' => 0, 'skipped' => count($poolIds)]);
            session()->flash('pp_ok', 'Nenhum pool_id do arquivo existe na base. Nada atualizado.');
            return;
        }

        $updates = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $poolIdSrcHeader = array_search('pool_id', $presentMap, true);
            $poolId = $this->toInt(Arr::get($row, $poolIdSrcHeader));

            if (!$poolId || !$existing->has($poolId)) {
                $skipped++;
                continue;
            }

            $attrs = [];
            foreach ($presentMap as $srcHeader => $destAttr) {
                $raw = Arr::get($row, $srcHeader);
                $attrs[$destAttr] = $this->normalizeForAttr($destAttr, $raw);
            }

            unset($attrs['pool_id']); // não troca a chave

            // remove marcadores de skip
            $attrs = array_filter($attrs, fn ($v) => $v !== '__SKIP__');

            if (!empty($attrs)) {
                $updates[] = ['id' => $existing[$poolId], 'attrs' => $attrs];
            }
        }

        $totalUpdated = 0;
        DB::transaction(function () use ($updates, &$totalUpdated) {
            foreach (array_chunk($updates, 300) as $pack) {
                foreach ($pack as $u) {
                    DB::table((new ExternalPoolpayment())->getTable())
                        ->where('id', $u['id'])
                        ->update($u['attrs']);
                    $totalUpdated++;
                }
            }
        });

        $this->removeFile();
        $this->dispatchBrowserEvent('poolpayment:done', [
            'updated' => $totalUpdated,
            'skipped' => $skipped,
        ]);

        $this->emitUp('refreshList');

        session()->flash('pp_ok', "Atualização concluída. Registros atualizados: {$totalUpdated}. Ignorados: {$skipped}.");
    }

    public function render()
    {
        return view('livewire.services.oexterno.helpers.pool-payment-updater');
    }

    /* ===================== Helpers ===================== */

    private function resetPreview(): void
    {
        $this->readyToProcess = false;
        $this->preview = [
            'filename'   => null,
            'size'       => null,
            'rows'       => 0,
            'columns'    => [],
            'missing'    => [],
            'typeErrors' => [],
        ];
    }

    private function normHeader($s): string
    {
        $s = trim((string)$s);
        // normaliza espaços e snake_case
        $s = str_replace(['_'], ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);

        // remove acentos
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $s = preg_replace('/[^A-Za-z0-9\/\- ]/', '', $s);

        // minúsculo
        $s = mb_strtolower($s, 'UTF-8');

        return $s;
    }

    private function buildPresentMap(array $normalizedHeader): array
    {
        // Mapa base normalizado a partir do COLMAP
        $normMap = [];
        foreach (self::COLMAP as $src => $dst) {
            $normMap[$this->normHeader($src)] = $dst;
        }

        // Expande com aliases
        foreach (self::COL_ALIASES as $canonical => $aliasList) {
            $dst = self::COLMAP[$canonical] ?? null;
            if (!$dst) {
                continue;
            }
            foreach ($aliasList as $alias) {
                $normMap[$this->normHeader($alias)] = $dst;
            }
        }

        $present = [];

        // 1ª passada: match exato
        foreach ($normalizedHeader as $h) {
            $hNorm = $this->normHeader($h);
            if (isset($normMap[$hNorm])) {
                $present[$hNorm] = $normMap[$hNorm];
            }
        }

        // 2ª passada: aproximação (para sobras não mapeadas)
        // tenta ligar headers não mapeados ao destino mais parecido
        $unmapped = array_filter($normalizedHeader, function ($h) use ($present) {
            return !isset($present[$this->normHeader($h)]);
        });

        $keysNormMap = array_keys($normMap);
        foreach ($unmapped as $h) {
            $hNorm = $this->normHeader($h);
            $best = null;
            $bestScore = 0;

            foreach ($keysNormMap as $cand) {
                // similar_text é leve e funciona bem para typos simples
                similar_text($hNorm, $cand, $pct);
                if ($pct > $bestScore) {
                    $bestScore = $pct;
                    $best = $cand;
                }
            }

            // só aceita se estiver bem parecido (threshold 85%)
            if ($best && $bestScore >= 85 && !isset($present[$hNorm])) {
                $present[$hNorm] = $normMap[$best];
            }
        }

        return $present; // ['src_normalizado' => 'dest_attr']
    }

    private function loadRows(): array
    {
        $ext    = strtolower($this->file->getClientOriginalExtension());
        $reader = $ext === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        $import = new PoolPaymentArrayImport();
        Excel::import($import, $this->file->getRealPath(), null, $reader);

        $rows = $import->rows ?? collect();

        // Converte cada linha para array associativo sem vazar props internas
        return $rows
            ->map(function ($row) {
                if ($row instanceof Collection) {
                    return $row->toArray();            // OK para Collection
                }
                if ($row instanceof Arrayable) {
                    return $row->toArray();            // OK para objetos Arrayable do Laravel Excel
                }
                if (is_object($row)) {
                    // última linha de defesa: tenta pegar propriedades públicas apenas
                    return get_object_vars($row) ?: (array) json_decode(json_encode($row), true);
                }
                // já é array
                return (array) $row;
            })
            ->values()
            ->all();
    }

    private function isValueCompatible(string $expected, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (str_starts_with($expected, 'decimal')) {
            return is_numeric($value);
        }
        switch ($expected) {
            case 'int':   return is_numeric($value);
            case 'bool':
                if (is_bool($value)) {
                    return true;
                }
                $v = mb_strtolower((string)$value);
                return in_array($v, ['1','0','true','false','sim','não','nao','yes','no'], true);
            case 'date':
            case 'datetime':
                return $this->looksLikeDate($value);
            default:
                return true;
        }
    }

    private function cleanCell($v)
    {
        if (is_null($v)) {
            return null;
        }
        if (is_string($v)) {
            $s = trim($v);
            // Placeholders que significam "sem valor"
            if ($s === '' || $s === '-' || $s === '--' || $s === "'-" || strcasecmp($s, 'N/A') === 0) {
                return null;
            }
            // Excel às vezes manda " - " com espaços
            if (preg_match('/^[-–—]+$/u', preg_replace('/\s+/', '', $s))) {
                return null;
            }
            return $s;
        }
        // 0 em campos de data muitas vezes é "sem data"
        if ($v === 0) {
            return null;
        }
        return $v;
    }


    private function looksLikeDate($v): bool
    {
        $v = $this->cleanCell($v);
        if ($v === null) {
            return true;
        } // vazio é aceitável

        // Serial Excel (número inteiro/granular)
        if (is_numeric($v)) {
            return true;
        }

        if (!is_string($v)) {
            return false;
        }

        // Formatos aceitos (BR primeiro)
        $fmts = [
            'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
            'Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d',
        ];

        foreach ($fmts as $f) {
            try {
                $dt = \Carbon\CarbonImmutable::createFromFormat($f, $v);
                if ($dt !== false) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }

        // Última tentativa: parse “livre”
        try {
            \Carbon\CarbonImmutable::parse($v);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }


    private function toInt($v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (!is_numeric($v)) {
            return null;
        }
        return (int)$v;
    }

    private function normalizeForAttr(string $attr, $raw)
    {
        $raw = $this->cleanCell($raw);   // <<< ADICIONE
        if ($raw === null) {
            return null;
        }

        if (isset(self::TYPES[$attr])) {
            $type = self::TYPES[$attr];

            if (str_starts_with($type, 'decimal')) {
                return str_replace(',', '.', (string)$raw);
            }

            switch ($type) {
                case 'int':
                    return $this->toInt($raw);

                case 'bool':
                    $v = mb_strtolower((string)$raw);
                    return in_array($v, ['1','true','sim','yes','y'], true);

                case 'date':
                    return $this->toCarbonDate($raw, false)?->format('Y-m-d');

                case 'datetime':
                    return $this->toCarbonDate($raw, true)?->format('Y-m-d H:i:s');
            }
        }

        return is_scalar($raw) ? trim((string)$raw) : '__SKIP__';
    }


    private function toCarbonDate($v, bool $withTime = false): ?\Carbon\CarbonImmutable
    {
        $v = $this->cleanCell($v);
        if ($v === null) {
            return null;
        }

        // Serial do Excel
        if (is_numeric($v)) {
            // 25569 = 1970-01-01 (sistema 1900)
            $unix = ((float)$v - 25569) * 86400;
            $dt = \Carbon\CarbonImmutable::createFromTimestampUTC((int)$unix);
            return $withTime ? $dt : $dt->startOfDay();
        }

        // Tenta primeiro formatos BR (com/sem hora)
        $fmts = $withTime
            ? ['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d']
            : ['d/m/Y', 'Y-m-d'];

        foreach ($fmts as $f) {
            try {
                $dt = \Carbon\CarbonImmutable::createFromFormat($f, $v);
                if ($dt !== false) {
                    return $withTime ? $dt : $dt->startOfDay();
                }
            } catch (\Throwable $e) {
            }
        }

        // Fallback
        try {
            $dt = \Carbon\CarbonImmutable::parse($v);
            return $withTime ? $dt : $dt->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

}
