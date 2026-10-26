<?php

namespace App\Services\Admin\Company;

use App\Models\{Andresscompany, Centerjob, Company, Contract, Employee, Service};
use Illuminate\Support\{Carbon, Str};
use Illuminate\Support\Facades\{Cache, DB};
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

/**
 * Lê a planilha em massa de empresas, valida TUDO contra o banco e contra o
 * próprio arquivo e só então aplica, em uma única transação.
 *
 * Regras de segurança:
 *  - "Salvar" é idempotente (cria se não existe, atualiza se existe, nada se igual);
 *  - qualquer erro bloqueia a importação inteira (tudo ou nada);
 *  - a análise roda de novo na hora de aplicar (o banco pode ter mudado);
 *  - nunca apaga em definitivo empresa; só desativa (soft delete).
 */
class CompanyBulkImporter
{
    private const KINDS = ['company' => 'Empresas', 'center' => 'Centros e depósitos', 'contract' => 'Contratos', 'activity' => 'Atividades'];

    /** @var array<string, array> */
    private array $units = [];

    /** @var array<string, array> */
    private array $contracts = [];

    /** @var array<string, \App\Models\Service> */
    private array $services = [];

    private array $ops = [];

    public function analyze(string $path): array
    {
        $sheets    = $this->read($path);
        $this->ops = [];
        $this->loadReference();

        $this->planCompanies($sheets[CompanyBulkSchema::COMPANIES]);
        $this->planCenters($sheets[CompanyBulkSchema::CENTERS]);
        $this->planContracts($sheets[CompanyBulkSchema::CONTRACTS]);
        $this->planActivities($sheets[CompanyBulkSchema::ACTIVITIES]);
        $this->finalChecks();

        return ['ops' => $this->ops, 'summary' => $this->summarize()];
    }

    public function apply(string $path): array
    {
        $lock = Cache::lock('company-bulk-import', 300);

        if (!$lock->get()) {
            throw new RuntimeException('Outra importação em massa está em andamento. Aguarde e tente novamente.');
        }

        try {
            $plan = $this->analyze($path);

            if ($plan['summary']['errors'] > 0) {
                throw new RuntimeException('A planilha possui erros. Corrija e envie novamente.');
            }

            DB::transaction(fn () => $this->execute($plan['ops']));

            return $plan['summary'];
        } finally {
            $lock->release();
        }
    }

    // ───────────────────────────── leitura ─────────────────────────────

    private function read(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $book = $reader->load($path);
        } catch (Throwable) {
            throw new RuntimeException('Não foi possível ler o arquivo. Envie a planilha .xlsx gerada pelo sistema.');
        }

        $result = [];

        foreach (CompanyBulkSchema::headers() as $title => $expected) {
            $sheet = $book->getSheetByName($title);

            if (!$sheet) {
                throw new RuntimeException("A aba \"{$title}\" não foi encontrada. Use a planilha gerada pelo sistema, sem renomear ou excluir abas.");
            }

            $rows = $sheet->toArray(null, true, false, false);
            $head = array_map(fn ($h) => CompanyBulkSchema::norm((string) $h), array_slice($rows[0] ?? [], 0, count($expected)));
            $want = array_map(fn ($h) => CompanyBulkSchema::norm($h), $expected);

            if ($head !== $want) {
                throw new RuntimeException("Os cabeçalhos da aba \"{$title}\" foram alterados. Baixe a planilha novamente e preencha sem mexer na primeira linha.");
            }

            $dataCols = count($expected) - ($title === CompanyBulkSchema::COMPANIES || $title === CompanyBulkSchema::CONTRACTS ? 1 : 0);
            $data     = [];

            foreach (array_slice($rows, 1, null, true) as $index => $row) {
                $cells = array_map(fn ($v) => $this->cell($v), array_slice(array_pad($row, count($expected), null), 0, $dataCols));

                // Linha "vazia" = tudo vazio exceto a coluna Ação.
                if (collect($cells)->skip(1)->every(fn ($v) => $v === '')) {
                    continue;
                }

                $data[] = ['line' => $index + 1, 'raw' => array_slice(array_pad($row, count($expected), null), 0, $dataCols), 'cells' => $cells];
            }

            if (count($data) > CompanyBulkSchema::MAX_ROWS) {
                throw new RuntimeException('A aba "' . $title . '" tem mais de ' . CompanyBulkSchema::MAX_ROWS . ' linhas. Divida em mais de um arquivo.');
            }

            $result[$title] = $data;
        }

        return $result;
    }

    private function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        return trim(preg_replace('/\s+/u', ' ', preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string) $value)));
    }

    private function loadReference(): void
    {
        $this->units     = [];
        $this->contracts = [];
        $this->services  = Service::query()->get()->keyBy(fn ($s) => CompanyBulkSchema::norm($s->service))->all();

        $companies = Company::withTrashed()
            ->with(['Address', 'Centerjobs'])
            ->withCount('toUsers')
            ->get();
        $byId = $companies->keyBy('id');

        foreach ($companies->sortBy(fn ($c) => $c->parent_id ? 1 : 0) as $company) {
            $parent  = $company->parent_id ? $byId->get($company->parent_id) : null;
            $display = CompanyBulkSchema::displayName($parent?->name, $company->name);
            $key     = CompanyBulkSchema::norm($display);

            $this->units[$key] ??= [
                'ref'     => 'db:' . $company->id,
                'model'   => $company,
                'display' => $display,
                'name'    => $company->name,
                'root'    => !$company->parent_id,
                'parent'  => $parent ? CompanyBulkSchema::norm($parent->name) : null,
                'trashed' => $company->trashed(),
                'removed' => false,
                'new'     => false,
            ];
        }

        $dbIds     = $companies->pluck('id');
        $employees = Employee::query()->selectRaw('contract_id, count(*) as total')->groupBy('contract_id')->pluck('total', 'contract_id');

        $keyByCompany = collect($this->units)->mapWithKeys(fn ($u, $k) => [($u['model']->id ?? '') => $k]);

        foreach (Contract::query()->whereIn('company_id', $dbIds)->with('services')->get() as $contract) {
            $unitKey = $keyByCompany[$contract->company_id] ?? null;

            if ($unitKey === null) {
                continue;
            }

            $key = $unitKey . '|' . CompanyBulkSchema::norm($contract->number);

            $this->contracts[$key] ??= [
                'ref'          => 'db:' . $contract->id,
                'model'        => $contract,
                'unit'         => $unitKey,
                'number'       => $contract->number,
                'service'      => (bool) $contract->service,
                'construction' => (bool) $contract->construction,
                'employees'    => (int) ($employees[$contract->id] ?? 0),
                'removed'      => false,
                'new'          => false,
            ];
        }
    }

    // ───────────────────────────── helpers de plano ─────────────────────────────

    private function op(string $sheet, int $line, string $kind, string $label): int
    {
        $this->ops[] = [
            'sheet'  => $sheet, 'line' => $line, 'kind' => $kind, 'label' => $label,
            'action' => 'noop', 'detail' => '', 'errors' => [], 'warnings' => [], 'payload' => [],
        ];

        return array_key_last($this->ops);
    }

    private function fail(int $i, string $message): void
    {
        $this->ops[$i]['errors'][] = $message;
    }

    private function warn(int $i, string $message): void
    {
        $this->ops[$i]['warnings'][] = $message;
    }

    private function set(int $i, string $action, string $detail = '', array $payload = []): void
    {
        $this->ops[$i]['action']  = $action;
        $this->ops[$i]['detail']  = $detail;
        $this->ops[$i]['payload'] = $payload;
    }

    private function action(string $value, int $i): ?string
    {
        $key = CompanyBulkSchema::norm($value);

        if ($key === '') {
            return CompanyBulkSchema::ACTION_SAVE;
        }

        if (!in_array($key, [CompanyBulkSchema::ACTION_SAVE, CompanyBulkSchema::ACTION_REMOVE, CompanyBulkSchema::ACTION_KEEP], true)) {
            $this->fail($i, "Ação \"{$value}\" inválida. Use Salvar, Remover ou Manter.");

            return null;
        }

        return $key;
    }

    private function seen(array &$seen, string $key, int $line, int $i, string $what): void
    {
        if (isset($seen[$key])) {
            $this->fail($i, "{$what} repetido(a): já aparece na linha {$seen[$key]}. Cada item deve aparecer uma única vez.");

            return;
        }

        $seen[$key] = $line;
    }

    private function lengthError(int $i, string $field, string $value, int $max): void
    {
        if (mb_strlen($value) > $max) {
            $this->fail($i, "{$field} passa de {$max} caracteres.");
        }
    }

    private function unsafe(int $i, string $field, string $value): void
    {
        if ($value !== '' && str_contains('=@', $value[0])) {
            $this->fail($i, "{$field} não pode começar com \"{$value[0]}\".");
        }
    }

    // ───────────────────────────── empresas ─────────────────────────────

    private function planCompanies(array $rows): void
    {
        $parsed = [];
        $seen   = [];

        foreach ($rows as $row) {
            [$action, $name, $parent, $email, $phone, $street, $complement, $city, $uf] = array_pad($row['cells'], 9, '');
            $label                                                                      = CompanyBulkSchema::displayName($parent ?: null, $name ?: '(sem nome)');
            $i                                                                          = $this->op(CompanyBulkSchema::COMPANIES, $row['line'], 'company', $label);
            $act                                                                        = $this->action($action, $i);

            if ($act === CompanyBulkSchema::ACTION_KEEP) {
                $this->set($i, 'ignored', 'Linha marcada como Manter.');

                continue;
            }

            foreach (['Empresa' => $name, 'Unidade de' => $parent, 'Endereço' => $street, 'Complemento' => $complement, 'Município' => $city] as $field => $value) {
                $this->unsafe($i, $field, $value);
                $this->lengthError($i, $field, $value, 255);
            }

            if ($name === '') {
                $this->fail($i, 'Informe o nome da empresa.');
            } elseif (str_contains($name, '/') || str_contains($parent, '/')) {
                $this->fail($i, 'Nome da empresa não pode conter "/".');
            }

            $parentKey = CompanyBulkSchema::norm($parent);
            $key       = CompanyBulkSchema::norm(CompanyBulkSchema::displayName($parentKey ? $parent : null, $name));

            if ($name !== '') {
                $this->seen($seen, $key, $row['line'], $i, 'Empresa/unidade');
            }

            $parsed[] = compact('i', 'act', 'name', 'parent', 'parentKey', 'key', 'email', 'phone', 'street', 'complement', 'city', 'uf') + ['line' => $row['line']];
        }

        // Matrizes primeiro: unidades podem apontar para uma matriz criada na mesma planilha.
        foreach (collect($parsed)->sortBy(fn ($p) => $p['parentKey'] === '' ? 0 : 1) as $p) {
            $this->planCompany($p);
        }

        // Matriz removida precisa que todas as unidades ativas dela também sejam removidas.
        foreach ($this->units as $key => $unit) {
            if (!$unit['root'] || !$unit['removed']) {
                continue;
            }

            $stillActive = collect($this->units)->filter(fn ($u) => $u['parent'] === CompanyBulkSchema::norm($unit['name']) && !$u['trashed'] && !$u['removed'] && !$u['new'])->count();

            if ($stillActive) {
                foreach ($this->ops as $idx => $op) {
                    if ($op['kind'] === 'company' && ($op['payload']['key'] ?? null) === $key && $op['action'] === 'remove') {
                        $this->fail($idx, "A matriz possui {$stillActive} unidade(s) ativa(s). Remova as unidades na mesma planilha ou mantenha a matriz.");
                    }
                }
            }
        }
    }

    private function planCompany(array $p): void
    {
        $i = $p['i'];

        if ($p['act'] === null || $p['name'] === '') {
            return;
        }

        $existing = $this->units[$p['key']] ?? null;

        if ($p['parentKey'] !== '') {
            $parent = $this->units[$p['parentKey']] ?? null;

            if (!$parent) {
                $this->fail($i, "A matriz \"{$p['parent']}\" não existe. Cadastre a matriz primeiro (linha sem \"Unidade de\") ou corrija o nome.");

                return;
            }

            if (!$parent['root']) {
                $this->fail($i, "\"{$p['parent']}\" é uma unidade, não uma matriz. Unidades só podem pertencer a uma matriz.");

                return;
            }

            if ($parent['trashed'] || $parent['removed']) {
                $this->fail($i, "A matriz \"{$p['parent']}\" está inativa ou sendo removida.");

                return;
            }
        }

        if ($p['act'] === CompanyBulkSchema::ACTION_REMOVE) {
            if (!$existing || $existing['new']) {
                $this->set($i, 'noop', 'Empresa já não existe.');
                $this->warn($i, 'Empresa não encontrada: nada a remover.');

                return;
            }

            if ($existing['trashed']) {
                $this->set($i, 'noop', 'Empresa já está inativa.');

                return;
            }

            if ($existing['model']->to_users_count > 0) {
                $this->fail($i, "Possui {$existing['model']->to_users_count} usuário(s) ativo(s). Transfira ou remova os usuários antes de desativar a empresa.");

                return;
            }

            $this->units[$p['key']]['removed'] = true;
            $this->set($i, 'remove', 'Será desativada.', ['key' => $p['key'], 'ref' => $existing['ref']]);

            return;
        }

        // ── Salvar ──
        $email = Str::lower($p['email']);
        $uf    = Str::upper($p['uf']);
        $phone = $p['phone'];

        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255)) {
            $this->fail($i, "E-mail \"{$p['email']}\" inválido.");
        }

        if ($phone !== '') {
            $digits = preg_replace('/\D+/', '', $phone);

            if (strlen($digits) < 8 || strlen($digits) > 13 || !preg_match('/^[\d\s().+\-]+$/', $phone)) {
                $this->fail($i, "Telefone \"{$phone}\" inválido. Use DDD + número (8 a 13 dígitos).");
            }
        }

        if ($uf !== '' && !in_array($uf, CompanyBulkSchema::UFS, true)) {
            $this->fail($i, "UF \"{$p['uf']}\" inválida.");
        }

        if (!$existing && $email === '') {
            $this->fail($i, 'Informe o e-mail da nova empresa.');
        }

        if ($existing && $existing['trashed']) {
            $this->fail($i, 'A empresa está inativa. Reative-a pela tela de empresas antes de alterá-la por planilha.');

            return;
        }

        $address = ['street' => $p['street'] === '' ? '' : ucwords(mb_strtolower($p['street'])), 'complement' => $p['complement'], 'city' => $p['city'] === '' ? '' : ucwords(mb_strtolower($p['city'])), 'uf' => $uf];

        if (!$existing) {
            $display                = CompanyBulkSchema::displayName($p['parentKey'] !== '' ? $this->units[$p['parentKey']]['name'] : null, ucwords(mb_strtolower($p['name'])));
            $this->units[$p['key']] = [
                'ref'  => 'new:' . $p['key'], 'model' => null, 'display' => $display, 'name' => ucwords(mb_strtolower($p['name'])),
                'root' => $p['parentKey'] === '', 'parent' => $p['parentKey'] ?: null, 'trashed' => false, 'removed' => false, 'new' => true,
            ];
            $this->ops[$i]['label'] = $display;
            $this->set($i, 'create', 'Nova ' . ($p['parentKey'] === '' ? 'matriz.' : 'unidade.'), [
                'key'   => $p['key'], 'name' => ucwords(mb_strtolower($p['name'])), 'parent_ref' => $p['parentKey'] !== '' ? $this->units[$p['parentKey']]['ref'] : null,
                'email' => $email, 'telephone' => $phone ?: null, 'address' => $address,
            ]);

            return;
        }

        if ($existing['new']) {
            return; // repetida no arquivo: o erro de duplicidade já foi registrado
        }

        $model   = $existing['model'];
        $changes = [];
        $current = $model->Address->first();

        if ($email !== '' && $email !== Str::lower((string) $model->email)) {
            $changes['email'] = $email;
        }

        if ($phone !== '' && $phone !== (string) $model->telephone) {
            $changes['telephone'] = $phone;
        }

        $addressChanges = [];

        foreach ($address as $field => $value) {
            if ($value !== '' && $value !== (string) ($current?->{$field} ?? '')) {
                $addressChanges[$field] = $value;
            }
        }

        if (!$changes && !$addressChanges) {
            $this->set($i, 'noop', 'Sem alterações.');

            return;
        }

        $this->set($i, 'update', 'Atualiza: ' . implode(', ', array_merge(array_keys($changes), array_map(fn ($f) => 'endereço/' . $f, array_keys($addressChanges)))) . '.', [
            'key' => $p['key'], 'ref' => $existing['ref'], 'changes' => $changes, 'address' => $addressChanges,
        ]);
    }

    // ───────────────────────────── centros e depósitos ─────────────────────────────

    private function planCenters(array $rows): void
    {
        $seen = [];

        foreach ($rows as $row) {
            [$action, $unitName, $code, $center, $deposit] = array_pad($row['cells'], 5, '');
            $i                                             = $this->op(CompanyBulkSchema::CENTERS, $row['line'], 'center', trim("{$unitName} · {$code}", ' ·'));
            $act                                           = $this->action($action, $i);

            if ($act === CompanyBulkSchema::ACTION_KEEP) {
                $this->set($i, 'ignored', 'Linha marcada como Manter.');

                continue;
            }

            $unit = $this->resolveUnit($unitName, $i);
            $code = Str::upper($code);

            foreach (['Código' => $code, 'Centro' => $center, 'Depósito' => $deposit] as $field => $value) {
                $this->unsafe($i, $field, $value);
                $this->lengthError($i, $field, $value, 100);
            }

            if ($code === '') {
                $this->fail($i, 'Informe o código (ex.: ENGELCAC).');
            }

            if (!$unit || $act === null || $code === '') {
                continue;
            }

            $this->seen($seen, $unit['ref'] . '|' . $code, $row['line'], $i, 'Código nesta empresa/unidade');

            $existing = $unit['model']?->Centerjobs->filter(fn ($c) => Str::upper(trim((string) $c->centerjob)) === $code) ?? collect();

            if ($act === CompanyBulkSchema::ACTION_REMOVE) {
                if ($existing->isEmpty()) {
                    $this->set($i, 'noop', 'Já não existe.');
                    $this->warn($i, 'Código não encontrado: nada a remover.');
                } else {
                    $this->set($i, 'remove', 'Será removido.', ['ids' => $existing->pluck('id')->all()]);
                }

                continue;
            }

            if ($unit['removed'] || $unit['trashed']) {
                $this->fail($i, 'A empresa/unidade está inativa ou sendo removida.');

                continue;
            }

            $center  = Str::upper($center);
            $deposit = Str::upper($deposit);

            if ($existing->isEmpty()) {
                $this->set($i, 'create', 'Novo código.', ['company_ref' => $unit['ref'], 'centerjob' => $code, 'center' => $center, 'deposit' => $deposit]);

                continue;
            }

            $first = $existing->first();

            if (Str::upper((string) $first->center) === $center && Str::upper((string) $first->deposit) === $deposit) {
                $this->set($i, 'noop', 'Sem alterações.');
            } else {
                $this->set($i, 'update', 'Atualiza centro/depósito.', ['id' => $first->id, 'center' => $center, 'deposit' => $deposit]);
            }
        }
    }

    // ───────────────────────────── contratos ─────────────────────────────

    private function planContracts(array $rows): void
    {
        $seen = [];

        foreach ($rows as $row) {
            [$action, $unitName, $number, $date, $type] = array_pad($row['cells'], 5, '');
            $i                                          = $this->op(CompanyBulkSchema::CONTRACTS, $row['line'], 'contract', trim("{$unitName} · {$number}", ' ·'));
            $act                                        = $this->action($action, $i);

            if ($act === CompanyBulkSchema::ACTION_KEEP) {
                $this->set($i, 'ignored', 'Linha marcada como Manter.');

                continue;
            }

            $unit = $this->resolveUnit($unitName, $i);

            $this->unsafe($i, 'Contrato', $number);
            $this->lengthError($i, 'Contrato', $number, 100);

            if ($number === '') {
                $this->fail($i, 'Informe o número do contrato.');
            } elseif (str_contains($number, '|')) {
                $this->fail($i, 'Número do contrato não pode conter "|".');
            }

            if (!$unit || $act === null || $number === '') {
                continue;
            }

            $unitKey = CompanyBulkSchema::norm($unit['display']);
            $key     = $unitKey . '|' . CompanyBulkSchema::norm($number);
            $this->seen($seen, $key, $row['line'], $i, 'Contrato nesta empresa/unidade');
            $existing = $this->contracts[$key] ?? null;

            if ($act === CompanyBulkSchema::ACTION_REMOVE) {
                if (!$existing || $existing['new']) {
                    $this->set($i, 'noop', 'Já não existe.');
                    $this->warn($i, 'Contrato não encontrado: nada a remover.');
                } elseif ($existing['employees'] > 0) {
                    $this->fail($i, "Contrato com {$existing['employees']} usuário(s) vinculado(s). Desvincule os usuários antes de remover.");
                } else {
                    $this->contracts[$key]['removed'] = true;
                    $this->set($i, 'remove', 'Será removido junto com as atividades liberadas.', ['ref' => $existing['ref']]);
                }

                continue;
            }

            if ($unit['removed'] || $unit['trashed']) {
                $this->fail($i, 'A empresa/unidade está inativa ou sendo removida.');

                continue;
            }

            $dateValue = null;

            if ($date !== '') {
                $dateValue = $this->parseDate($row['raw'][3] ?? $date);

                if (!$dateValue) {
                    $this->fail($i, "Validade \"{$date}\" inválida. Use uma data no formato dd/mm/aaaa.");
                } elseif ($dateValue->lt(now()->startOfDay())) {
                    $this->warn($i, 'Contrato com validade vencida.');
                }
            } elseif (!$existing) {
                $this->fail($i, 'Informe a validade do contrato.');
            }

            $flags = null;

            if ($type !== '') {
                $flags = match (CompanyBulkSchema::norm($type)) {
                    'servico'        => [true, false],
                    'obra'           => [false, true],
                    'servico e obra' => [true, true],
                    default          => null,
                };

                if (!$flags) {
                    $this->fail($i, "Tipo \"{$type}\" inválido. Use Serviço, Obra ou Serviço e Obra.");
                }
            } elseif (!$existing) {
                $this->fail($i, 'Informe o tipo do contrato.');
            }

            if ($this->ops[$i]['errors']) {
                continue;
            }

            if (!$existing) {
                $this->contracts[$key] = [
                    'ref'       => 'new:' . $key, 'model' => null, 'unit' => $unitKey, 'number' => $number, 'service' => $flags[0], 'construction' => $flags[1],
                    'employees' => 0, 'removed' => false, 'new' => true,
                ];
                $this->set($i, 'create', 'Novo contrato.', [
                    'key' => $key, 'company_ref' => $unit['ref'], 'number' => $number, 'date_end' => $dateValue->toDateString(), 'service' => $flags[0], 'construction' => $flags[1],
                ]);

                continue;
            }

            $model   = $existing['model'];
            $changes = [];

            if ($dateValue && $dateValue->toDateString() !== Carbon::parse($model->date_end)->toDateString()) {
                $changes['date_end'] = $dateValue->toDateString();
            }

            if ($flags && ((bool) $model->service !== $flags[0] || (bool) $model->construction !== $flags[1])) {
                $changes['service']                    = $flags[0];
                $changes['construction']               = $flags[1];
                $this->contracts[$key]['service']      = $flags[0];
                $this->contracts[$key]['construction'] = $flags[1];
            }

            $changes
                ? $this->set($i, 'update', 'Atualiza: ' . implode(', ', array_keys($changes)) . '.', ['ref' => $existing['ref'], 'changes' => $changes])
                : $this->set($i, 'noop', 'Sem alterações.');
        }
    }

    private function parseDate(mixed $raw): ?Carbon
    {
        try {
            if (is_numeric($raw)) {
                $date = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw));
            } else {
                $text = trim((string) $raw);
                $date = null;

                foreach (['d/m/Y', 'Y-m-d'] as $format) {
                    $try = Carbon::createFromFormat('!' . $format, $text);

                    if ($try && $try->format($format) === $text) {
                        $date = $try;

                        break;
                    }
                }
            }
        } catch (Throwable) {
            return null;
        }

        return $date && $date->year >= 2000 && $date->year <= 2100 ? $date->startOfDay() : null;
    }

    // ───────────────────────────── atividades ─────────────────────────────

    private function planActivities(array $rows): void
    {
        $seen = [];

        foreach ($rows as $row) {
            [$action, $contractLabel, $serviceName, $dispatch] = array_pad($row['cells'], 4, '');
            $i                                                 = $this->op(CompanyBulkSchema::ACTIVITIES, $row['line'], 'activity', trim("{$contractLabel} · {$serviceName}", ' ·'));
            $act                                               = $this->action($action, $i);

            if ($act === CompanyBulkSchema::ACTION_KEEP) {
                $this->set($i, 'ignored', 'Linha marcada como Manter.');

                continue;
            }

            $contractKey = null;

            if (!str_contains($contractLabel, '|')) {
                $this->fail($i, 'Escolha o contrato na lista (formato "Empresa / Unidade | Nº do contrato").');
            } else {
                [$unitPart, $numberPart] = array_map('trim', explode('|', $contractLabel, 2) + [1 => '']);
                $unit                    = $this->units[CompanyBulkSchema::norm($unitPart)] ?? null;
                $contractKey             = $unit ? CompanyBulkSchema::norm($unit['display']) . '|' . CompanyBulkSchema::norm($numberPart) : null;

                if (!$unit || !isset($this->contracts[$contractKey])) {
                    $this->fail($i, "Contrato \"{$contractLabel}\" não existe nem está na aba Contratos.");
                    $contractKey = null;
                }
            }

            $service = $serviceName === '' ? null : ($this->services[CompanyBulkSchema::norm($serviceName)] ?? null);

            if ($serviceName === '') {
                $this->fail($i, 'Informe a atividade.');
            } elseif (!$service) {
                $this->fail($i, "Atividade \"{$serviceName}\" não existe. Escolha na lista.");
            }

            $dispatchKey = CompanyBulkSchema::norm($dispatch);

            if (!in_array($dispatchKey, ['', 'sim', 'nao'], true)) {
                $this->fail($i, "Despachar \"{$dispatch}\" inválido. Use Sim ou Não.");
            }

            if (!$contractKey || !$service || $act === null || $this->ops[$i]['errors']) {
                continue;
            }

            $contract = $this->contracts[$contractKey];
            $this->seen($seen, $contractKey . '|' . $service->id, $row['line'], $i, 'Atividade neste contrato');
            $current = $contract['model']?->services->firstWhere('id', $service->id);

            if ($act === CompanyBulkSchema::ACTION_REMOVE) {
                if ($contract['removed'] || !$current) {
                    $this->set($i, 'noop', 'Atividade já não está liberada.');
                } else {
                    $this->set($i, 'remove', 'Atividade será desvinculada.', ['contract_ref' => $contract['ref'], 'service_id' => $service->id]);
                }

                continue;
            }

            if ($contract['removed']) {
                $this->fail($i, 'O contrato está sendo removido nesta planilha.');

                continue;
            }

            $allowed = match (true) {
                $contract['service'] && !$contract['construction'] => $service->project || $service->post_construction,
                $contract['construction'] && !$contract['service'] => $service->construction || $service->post_construction,
                default                                            => true,
            };

            if (!$allowed) {
                $this->fail($i, "A atividade \"{$service->service}\" não é compatível com o tipo deste contrato.");

                continue;
            }

            $dispatchFlag = $dispatchKey === '' ? (bool) ($current?->pivot->dispatch ?? false) : $dispatchKey === 'sim';

            if (!$current) {
                $this->set($i, 'create', 'Libera atividade.', ['contract_ref' => $contract['ref'], 'service_id' => $service->id, 'dispatch' => $dispatchFlag]);
            } elseif ((bool) $current->pivot->dispatch !== $dispatchFlag) {
                $this->set($i, 'update', 'Atualiza despacho.', ['contract_ref' => $contract['ref'], 'service_id' => $service->id, 'dispatch' => $dispatchFlag]);
            } else {
                $this->set($i, 'noop', 'Sem alterações.');
            }
        }
    }

    private function finalChecks(): void
    {
        $withActivity = [];

        foreach ($this->ops as $op) {
            if ($op['kind'] === 'activity' && in_array($op['action'], ['create', 'update'], true)) {
                $withActivity[$op['payload']['contract_ref']] = true;
            }
        }

        foreach ($this->ops as $idx => $op) {
            if ($op['kind'] === 'contract' && $op['action'] === 'create' && !isset($withActivity['new:' . $op['payload']['key']])) {
                $this->warn($idx, 'Contrato novo sem atividades liberadas na aba Atividades.');
            }
        }
    }

    // ───────────────────────────── resolução ─────────────────────────────

    private function resolveUnit(string $name, int $i): ?array
    {
        if ($name === '') {
            $this->fail($i, 'Escolha a empresa/unidade na lista.');

            return null;
        }

        $unit = $this->units[CompanyBulkSchema::norm($name)] ?? null;

        if (!$unit) {
            $this->fail($i, "Empresa/unidade \"{$name}\" não existe nem está na aba Empresas. Escolha na lista.");
        }

        return $unit;
    }

    private function summarize(): array
    {
        $summary = ['errors' => 0, 'warnings' => 0, 'rows' => count($this->ops), 'changes' => 0, 'kinds' => []];

        foreach (self::KINDS as $kind => $label) {
            $summary['kinds'][$kind] = ['label' => $label, 'create' => 0, 'update' => 0, 'remove' => 0, 'noop' => 0, 'ignored' => 0, 'error' => 0];
        }

        foreach ($this->ops as $op) {
            $hasError = (bool) $op['errors'];
            $summary['errors'] += count($op['errors']);
            $summary['warnings'] += count($op['warnings']);
            $summary['kinds'][$op['kind']][$hasError ? 'error' : $op['action']]++;

            if (!$hasError && in_array($op['action'], ['create', 'update', 'remove'], true)) {
                $summary['changes']++;
            }
        }

        return $summary;
    }

    // ───────────────────────────── aplicação ─────────────────────────────

    private function execute(array $ops): void
    {
        $companies = [];
        $contracts = [];

        $resolveCompany = function (string $ref) use (&$companies): string {
            return str_starts_with($ref, 'db:') ? substr($ref, 3) : $companies[$ref];
        };
        $resolveContract = function (string $ref) use (&$contracts): int {
            return str_starts_with($ref, 'db:') ? (int) substr($ref, 3) : $contracts[$ref];
        };

        $by     = fn (string $kind, array $actions) => array_filter($ops, fn ($o) => $o['kind'] === $kind && in_array($o['action'], $actions, true));
        $isRoot = fn ($o) => $o['payload']['parent_ref'] === null;

        // 1) empresas: matrizes antes das unidades
        $creates = collect($by('company', ['create']))->sortBy(fn ($o) => $isRoot($o) ? 0 : 1);

        foreach ($creates as $op) {
            $p       = $op['payload'];
            $company = Company::create([
                'parent_id' => $p['parent_ref'] ? $resolveCompany($p['parent_ref']) : null,
                'name'      => $p['name'],
                'email'     => $p['email'],
                'telephone' => $p['telephone'],
            ]);
            $companies['new:' . $p['key']] = $company->id;

            if (array_filter($p['address'])) {
                $company->Address()->save(new Andresscompany($p['address']));
            }
        }

        foreach ($by('company', ['update']) as $op) {
            $p       = $op['payload'];
            $company = Company::withTrashed()->findOrFail($resolveCompany($p['ref']));
            $company->update($p['changes']);

            if ($p['address']) {
                $address = $company->Address()->first() ?: new Andresscompany(['company_id' => $company->id]);
                $address->fill($p['address'])->save();
            }
        }

        // 2) centros
        foreach ($by('center', ['create']) as $op) {
            Centerjob::create([
                'company_id' => $resolveCompany($op['payload']['company_ref']),
                'centerjob'  => $op['payload']['centerjob'],
                'center'     => $op['payload']['center'] ?: null,
                'deposit'    => $op['payload']['deposit'] ?: null,
            ]);
        }

        foreach ($by('center', ['update']) as $op) {
            Centerjob::whereKey($op['payload']['id'])->update(['center' => $op['payload']['center'] ?: null, 'deposit' => $op['payload']['deposit'] ?: null]);
        }

        // 3) contratos
        foreach ($by('contract', ['create']) as $op) {
            $p        = $op['payload'];
            $contract = new Contract();
            $contract->fill(['number' => $p['number'], 'service' => $p['service'], 'construction' => $p['construction'], 'date_end' => $p['date_end']]);
            $contract->company_id = $resolveCompany($p['company_ref']);
            $contract->save();
            $contracts['new:' . $p['key']] = $contract->id;
        }

        foreach ($by('contract', ['update']) as $op) {
            Contract::whereKey($resolveContract($op['payload']['ref']))->update($op['payload']['changes']);
        }

        // 4) atividades
        foreach ($by('activity', ['create', 'update']) as $op) {
            $p = $op['payload'];
            Contract::findOrFail($resolveContract($p['contract_ref']))->services()->syncWithoutDetaching([
                $p['service_id'] => ['posts' => false, 'qtd' => 0, 'days' => 0, 'dispatch' => $p['dispatch']],
            ]);
        }

        // 5) remoções (do mais dependente para o menos)
        foreach ($by('activity', ['remove']) as $op) {
            Contract::find($resolveContract($op['payload']['contract_ref']))?->services()->detach($op['payload']['service_id']);
        }

        foreach ($by('contract', ['remove']) as $op) {
            Contract::whereKey($resolveContract($op['payload']['ref']))->delete();
        }

        foreach ($by('center', ['remove']) as $op) {
            Centerjob::whereIn('id', $op['payload']['ids'])->delete();
        }

        foreach ($by('company', ['remove']) as $op) {
            Company::find($resolveCompany($op['payload']['ref']))?->delete();
        }
    }
}
