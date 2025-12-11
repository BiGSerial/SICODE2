<?php

namespace App\Console\Commands\Tools;

use App\Models\Company;
use App\Models\Edp_depc\BaseD5;
use App\Models\FiveNote;
use App\Models\Note;
use Illuminate\Console\Command;

class MigrateFiveNotesFromBaseD5 extends Command
{
    protected $signature = 'sicode:migrate-five-notes
                            {--limit=100 : Limite de registros da base D5 para processar}';

    protected $description = 'Migra registros da tbld_usr_baseD5 (SQL Server) para five_notes (MySQL)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Iniciando migração das D5 (limit = {$limit})...");

        $query = BaseD5::query()
            ->whereNull('dtEncerramento')
            ->whereNotNull('obra');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $rows = $query->get();

        $this->info("Encontrados {$rows->count()} registros elegíveis na base D5.");

        $created            = 0;
        $skippedExisting    = 0;
        $skippedNoNote      = 0;
        $skippedNoOrder     = 0;
        $skippedNoOp0010    = 0;
        $skippedNoCompanyId = 0;

        foreach ($rows as $row) {
            // 1) Evitar duplicar por note_d5
            if (FiveNote::where('note_d5', $row->nota)->exists()) {
                $skippedExisting++;
                continue;
            }

            // 2) Encontrar Note via Orders (ordem = obra)
            $note = Note::whereHas('orders', function ($q) use ($row) {
                $q->where('ordem', $row->obra);
            })->first();

            if (! $note) {
                $skippedNoNote++;
                $this->warn("Sem Note para obra {$row->obra} (nota D5 {$row->nota})");
                continue;
            }

            // 3) Garantir apenas 1 FiveNote por Note
            if (FiveNote::where('note_id', $note->id)->exists()) {
                $skippedExisting++;
                continue;
            }

            // 4) Pegar Order correspondente a essa obra
            $order = $note->orders()
                ->where('ordem', $row->obra)
                ->first();

            if (! $order) {
                $skippedNoOrder++;
                $this->warn("Sem Order para obra {$row->obra} (nota D5 {$row->nota})");
                continue;
            }

            /**
             * 5) Resolver company_id com fallback:
             *    1) Order (com regra CONSTR)
             *    2) Order + BaseD5
             *    3) Operation 0010
             */
            $companyId = $this->resolveCompanyId($order, $row, $skippedNoOp0010);

            // 6) Não incluir se não houver company_id válido
            if (! $companyId) {
                $skippedNoCompanyId++;
                $this->warn(
                    "Sem company_id válido para obra {$row->obra} "
                    . "(cenTrabOrder={$order->cenTrab}, cenPlanOrder={$order->cenPlan}) "
                    . "- nota D5 {$row->nota}"
                );
                continue;
            }

            // 6.1) reason vem da descricao (ex.: "170000011489 (Chave)" -> "07_CHAVE")
            $mappedReason = $this->mapReasonFromDescricao($row->descricao ?? null);

            // 6.2) codify vem do txtCodeCodific com de->para 001..012
            $mappedCodify = $this->mapCodifyFromTxtCodeCodific($row->txtCodeCodific ?? null);

            // 7) Criar FiveNote
            FiveNote::create([
                'note_d5'         => $row->nota,
                'note_id'         => $note->id,
                'loc_install'     => $row->denomLocalInstal ?? null,
                'conjunto'        => isset($row->conjunto) ? (int) $row->conjunto : null,
                'description'     => $row->denomConjunto ?? $row->descricao ?? null,

                // codify padronizado
                'codify'          => $mappedCodify,

                'company_id'      => $companyId,
                'pep'             => $order->pep ?? null,

                'sintoms'         => null,

                // reason padronizado quando possível
                'reason'          => $mappedReason ?? null,

                'name'            => null,

                'dispatch_at'     => $row->dtCriacao,
                'payed_at'        => $row->dtCriacao,
                'visible_partner' => true,
                'is_payed'        => true,

                'is_completed'      => false,
                'completed_at'      => null,
                'is_supervisioned'  => false,
                'supervisioned_at'  => null,
                'is_archived'       => false,
                'isPassive'         => true,
            ]);

            $created++;
        }

        $this->info('--- Resultado da migração ---');
        $this->info("Criados                          : {$created}");
        $this->info("Ignorados (já existia)           : {$skippedExisting}");
        $this->info("Ignorados (sem Note)             : {$skippedNoNote}");
        $this->info("Ignorados (sem Order)            : {$skippedNoOrder}");
        $this->info("Ignorados (sem Operation 0010)   : {$skippedNoOp0010}");
        $this->info("Ignorados (sem company_id válido): {$skippedNoCompanyId}");

        $this->info('Migração concluída ✅');

        return self::SUCCESS;
    }

    /**
     * Resolve company_id com fallback:
     * 1) Order (com regra CONSTR)
     * 2) Order + BaseD5
     * 3) Operation 0010
     */
    protected function resolveCompanyId($order, $row, int &$skippedNoOp0010): ?string
    {
        // 1) Tentativa pelo Order
        $centrabOrder = $this->resolveCenTrabFromOrder($order, $row);

        $companyId = $this->mapCompanyId($order->cenPlan ?? null, $centrabOrder ?? null);
        if ($this->isValidCompanyId($companyId)) {
            return $companyId;
        }

        // 2) Tentativa Order + BaseD5
        $centrabFromD5 = $row->cenTrabResp
            ?? $row->cenTrab
            ?? $centrabOrder;

        $companyId = $this->mapCompanyId($order->cenPlan ?? null, $centrabFromD5 ?? null);
        if ($this->isValidCompanyId($companyId)) {
            return $companyId;
        }

        // 3) Tentativa pela Operation 0010
        $operation = $order->operations()
            ->whereIn('operacao', ['0010', '010', '10'])
            ->first();

        if (! $operation) {
            $skippedNoOp0010++;
            return null;
        }

        $companyId = $this->mapCompanyId(
            $operation->cenPlan ?? null,
            $operation->cenTrab ?? null
        );

        if ($this->isValidCompanyId($companyId)) {
            return $companyId;
        }

        return null;
    }

    /**
     * Regra de centrab baseada no Order.
     * Se o cenTrab do Order for CONSTR, usa cenTrabResp vindo da BaseD5.
     */
    protected function resolveCenTrabFromOrder($order, $row): ?string
    {
        if (($order->cenTrab ?? null) === 'CONSTR') {
            return $row->cenTrabResp ?? null;
        }

        return $order->cenTrab ?? null;
    }

    /**
     * Valida se o UUID existe em companies.
     */
    protected function isValidCompanyId(?string $companyId): bool
    {
        if (! $companyId) {
            return false;
        }

        return Company::where('id', $companyId)->exists();
    }

    /**
     * reason vem da coluna descricao da BaseD5.
     * Ex:
     * - "170000011489 (Chave)" -> "07_CHAVE"
     * - "(07 - Chave)" -> "07_CHAVE"
     */
    protected function mapReasonFromDescricao(?string $descricao): ?string
    {
        if (! $descricao) {
            return null;
        }

        $descricao = trim($descricao);

        $mapFull  = $this->reasonValueMap();       // "07 - Chave" => "07_CHAVE"
        $mapLabel = $this->reasonLabelValueMap();  // "Chave" => "07_CHAVE"

        // 1) Match direto pelo texto completo
        if (isset($mapFull[$descricao])) {
            return $mapFull[$descricao];
        }

        // 2) Extrair conteúdo dentro de parênteses
        if (preg_match_all('/\(([^)]+)\)/', $descricao, $matches)) {
            foreach ($matches[1] as $inside) {
                $inside = trim($inside);

                if (isset($mapFull[$inside])) {
                    return $mapFull[$inside];
                }

                if (isset($mapLabel[$inside])) {
                    return $mapLabel[$inside];
                }
            }
        }

        // 3) Fallback por "contém" baseado no label
        foreach ($mapLabel as $label => $value) {
            if (str_contains($descricao, $label)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * codify vem da coluna txtCodeCodific da BaseD5.
     * Faz de->para para os "values" 001..012.
     */
    protected function mapCodifyFromTxtCodeCodific(?string $txt): ?string
    {
        if (! $txt) {
            return null;
        }

        $txt = trim($txt);

        $mapFull  = $this->codifyValueMap();       // "001 - Reparo Urgente" => "001_REPARO URGENTE"
        $mapLabel = $this->codifyLabelValueMap();  // "Reparo Urgente" => "001_REPARO URGENTE"

        // 1) Match direto no full
        if (isset($mapFull[$txt])) {
            return $mapFull[$txt];
        }

        // 2) Match direto no label
        if (isset($mapLabel[$txt])) {
            return $mapLabel[$txt];
        }

        // 3) Se vier algo que contenha a chave
        foreach ($mapFull as $label => $value) {
            if (str_contains($txt, $label)) {
                return $value;
            }
        }

        foreach ($mapLabel as $label => $value) {
            if (str_contains($txt, $label)) {
                return $value;
            }
        }

        // 4) Se não achar, retorna null (não inventa codify)
        return null;
    }

    /**
     * Mapa reason completo => value
     */
    protected function reasonValueMap(): array
    {
        return [
            '01 - Falta de Materiais' => '01_FALTA DE MATERIAIS',
            '02 - Reparo Passeio'     => '02_REPARO PASSEIO',
            '03 - Aterramento'        => '03_ATERRAMENTO',
            '04 - Inventário'         => '04_INVENTARIO',
            '05 - Pendência de Poda'  => '05_PENDENCIA DE PODA',
            '06 - Projeto'            => '06_PROJETO',
            '07 - Chave'              => '07_CHAVE',
            '08 - Condutor'           => '08_CONDUTOR',
            '09 - Conexão'            => '09_CONEXAO',
            '10 - Equipamento'        => '10_EQUIPAMENTO',
            '11 - Estrutura/Poste'    => '11_ESTRUTURA/POSTE',
            '12 - Isolador/Cadeia'    => '12_ISOLADOR/CADEIA',
            '13 - Padrão de Entrada'  => '13_PADRAO DE ENTRADA',
            '14 - Para-raio'          => '14_PARA-RAIO',
            '15 - Sinalização'        => '15_SINALIZACAO',
            '16 - Outros'             => '16_OUTROS',
        ];
    }

    /**
     * Mapa label simples => value
     */
    protected function reasonLabelValueMap(): array
    {
        return [
            'Falta de Materiais' => '01_FALTA DE MATERIAIS',
            'Reparo Passeio'     => '02_REPARO PASSEIO',
            'Aterramento'        => '03_ATERRAMENTO',
            'Inventário'         => '04_INVENTARIO',
            'Pendência de Poda'  => '05_PENDENCIA DE PODA',
            'Projeto'            => '06_PROJETO',
            'Chave'              => '07_CHAVE',
            'Condutor'           => '08_CONDUTOR',
            'Conexão'            => '09_CONEXAO',
            'Equipamento'        => '10_EQUIPAMENTO',
            'Estrutura/Poste'    => '11_ESTRUTURA/POSTE',
            'Isolador/Cadeia'    => '12_ISOLADOR/CADEIA',
            'Padrão de Entrada'  => '13_PADRAO DE ENTRADA',
            'Para-raio'          => '14_PARA-RAIO',
            'Sinalização'        => '15_SINALIZACAO',
            'Outros'             => '16_OUTROS',
        ];
    }

    /**
     * Mapa codify completo => value
     */
    protected function codifyValueMap(): array
    {
        return [
            '001 - Reparo Urgente'                 => '001_REPARO URGENTE',
            '002 - Reparo'                         => '002_REPARO',
            '003 - Comunicação'                    => '003_COMUNICACAO',
            '004 - Solicitação'                    => '004_SOLICITACAO',
            '005 - Retorno de Divergência Projeto' => '005_RETORNO DE DIVERGENCIA PROJETO',
            '006 - Retorno de Divergência de Orçamento' => '006_RETORNO DE DIVERGENCIA DE ORCAMENTO',
            '007 - Alteração de Projeto'           => '007_ALTERACAO DE PROJETO',
            '008 - Outros'                         => '008_OUTROS',
            '009 - Multa Prazo de Execução'        => '009_MULTA PRAZO DE EXECUCAO',
            '010 - Multa Prazo de Devolução'       => '010_MULTA PRAZO DE DEVOLUCAO',
            '011 - Multa Prazo Desigamento'        => '011_MULTA PRAZO DESIGAMENTO',
            '012 - Multas'                         => '012_MULTAS',
        ];
    }

    /**
     * Mapa codify por label simples => value
     */
    protected function codifyLabelValueMap(): array
    {
        return [
            'Reparo Urgente'                 => '001_REPARO URGENTE',
            'Reparo'                         => '002_REPARO',
            'Comunicação'                    => '003_COMUNICACAO',
            'Solicitação'                    => '004_SOLICITACAO',
            'Retorno de Divergência Projeto' => '005_RETORNO DE DIVERGENCIA PROJETO',
            'Retorno de Divergência de Orçamento' => '006_RETORNO DE DIVERGENCIA DE ORCAMENTO',
            'Alteração de Projeto'           => '007_ALTERACAO DE PROJETO',
            'Outros'                         => '008_OUTROS',
            'Multa Prazo de Execução'        => '009_MULTA PRAZO DE EXECUCAO',
            'Multa Prazo de Devolução'       => '010_MULTA PRAZO DE DEVOLUCAO',
            'Multa Prazo Desigamento'        => '011_MULTA PRAZO DESIGAMENTO',
            'Multas'                         => '012_MULTAS',
        ];
    }

    /**
     * Faz o de->para de (cenPlan, cenTrab) para company_id.
     */
    protected function mapCompanyId(?string $cenPlan, ?string $cenTrab): ?string
    {
        $cenPlan = $cenPlan ? trim($cenPlan) : null;
        $cenTrab = $cenTrab ? trim($cenTrab) : null;

        if (! $cenTrab || ! $cenPlan) {
            return null;
        }

        $map = [
            'MANSEBT0' => [
                '5100' => '9beecccb-9bce-483c-a6da-f7f3daa15322',
                '5000' => '9c8ba341-2ae1-4a2a-b6d8-4a577160a27e',
            ],
            'MANSERV' => [
                '5100' => '9beecccb-9bce-483c-a6da-f7f3daa15322',
                '5000' => '9c8ba341-2ae1-4a2a-b6d8-4a577160a27e',
            ],
            'COMPEBT0' => [
                '5400' => '9beeb6dd-3516-4916-82d6-3718d383a478',
            ],
            'COMPECAC' => [
                '5700' => '9beeb73f-6215-491f-8954-7138dab71613',
            ],
            'COMPELIN' => [
                '5400' => '9beeb6dd-3516-4916-82d6-3718d383a478',
            ],
            'COMPELSM' => [
                '5400' => '9beeb73f-6215-491f-8954-7138dab71613',
            ],
            'ELETRITA' => [
                '5200' => '9bee8c9f-c51a-490a-a7e9-9c6814f514bf',
            ],
            'ELETROBS' => [
                '5500' => '9bee8ab5-e893-45c8-b83f-95f8391588f6',
            ],
            'ELETRONV' => [
                '5500' => '9bee8a4f-733a-4c60-9db2-bf12a9d79e91',
            ],
            'ELETROVN' => [
                '5700' => '9beecf75-026b-4c97-b223-baa9052b530b',
                '5600' => '9beecf75-026b-4c97-b223-baa9052b530b',
            ],
            'ENGELCAC' => [
                '5600' => '9beecece-5f3b-4024-ad2d-fe679ccfbed3',
                '5700' => '9beecece-5f3b-4024-ad2d-fe679ccfbed3',
            ],
            'ENGELGUA' => [
                '5000' => '9beecf07-8489-45c4-a290-d43e78817ec7',
                '5600' => '9beecf07-8489-45c4-a290-d43e78817ec7',
            ],
            'ELETRARA' => [
                '5200' => '9bee8d71-b324-4838-93dd-2ec4321e62fb',
                '5400' => '9bee8d71-b324-4838-93dd-2ec4321e62fb',
            ],
            'ENGELGCI' => [
                '5700' => '9f761261-b93b-4c1e-81aa-44f83f5aec6a',
            ],
            'ENGELMNV' => [
                '5500' => '9beecf07-8489-45c4-a290-d43e78817ec7',
            ],
            'ENGELMSM' => [
                '5400' => '9beecf07-8489-45c4-a290-d43e78817ec7',
            ],
            'ENGELVIT' => [
                '5000' => '9e5df407-4249-4dda-a1c2-ddba37931249',
            ],
            'PROENG' => [
                '5400' => '9beebbe6-34f4-4ce8-bde7-8e6b604deacd',
            ],
            'PRO' => [
                '5400' => '9beebbe6-34f4-4ce8-bde7-8e6b604deacd',
            ],
            'PRO NV' => [
                '5500' => '9beebefb-09b2-45b3-9a18-a8d0c33ebe75',
            ],
        ];

        return $map[$cenTrab][$cenPlan] ?? null;
    }
}
