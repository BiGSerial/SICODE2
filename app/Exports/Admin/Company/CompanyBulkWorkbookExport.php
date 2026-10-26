<?php

namespace App\Exports\Admin\Company;

use App\Exports\Admin\Company\Sheets\{BulkDataSheet, BulkInstructionsSheet, BulkListsSheet};
use App\Models\{Company, Contract, Service};
use App\Services\Admin\Company\CompanyBulkSchema as S;
use Maatwebsite\Excel\Concerns\{Exportable, WithMultipleSheets};
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Planilha em massa de empresas. Com $withData = true exporta todo o cadastro
 * (e serve como planilha de edição); com false gera o modelo em branco.
 */
class CompanyBulkWorkbookExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly bool $withData = true)
    {
    }

    public function sheets(): array
    {
        $companies = Company::withTrashed()
            ->with(['Address', 'Centerjobs', 'contracts.services', 'parent'])
            ->orderBy('name')
            ->get();

        $roots = $companies->whereNull('parent_id')->values();
        $units = $roots->flatMap(fn ($root) => [$root, ...$companies->where('parent_id', $root->id)->sortBy('name')->values()->all()])
            ->merge($companies->whereNotNull('parent_id')->filter(fn ($c) => !$roots->contains('id', $c->parent_id)))
            ->values();

        $display   = fn (Company $c) => S::displayName($c->parent?->name, $c->name);
        $headers   = S::headers();
        $companyRs = $contractRs = $centerRs = $activityRs = [];

        foreach ($this->withData ? $units : [] as $company) {
            $keep = $company->trashed() ? 'Manter' : 'Salvar';
            $addr = $company->Address->first();
            $name = $display($company);

            $companyRs[] = [$keep, $company->name, $company->parent?->name, $company->email, $company->telephone, $addr?->street, $addr?->complement, $addr?->city, $addr?->uf, $company->trashed() ? 'Inativa' : 'Ativa'];

            foreach ($company->Centerjobs as $center) {
                $centerRs[] = [$keep, $name, $center->centerjob, $center->center, $center->deposit];
            }

            foreach ($company->contracts as $contract) {
                $type = match (true) {
                    (bool) $contract->service && (bool) $contract->construction => 'Serviço e Obra',
                    (bool) $contract->construction                              => 'Obra',
                    default                                                     => 'Serviço',
                };
                $contractRs[] = [$keep, $name, $contract->number, $contract->date_end ? Date::PHPToExcel(strtotime($contract->date_end)) : null, $type];

                foreach ($contract->services as $service) {
                    $activityRs[] = [$keep, "{$name} | {$contract->number}", $service->service, $service->pivot->dispatch ? 'Sim' : 'Não'];
                }
            }
        }

        $services        = Service::query()->orderBy('service')->pluck('service')->all();
        $staticUnits     = $this->withData ? [] : $units->map($display)->all();
        $staticContracts = $this->withData ? [] : $units->flatMap(fn ($c) => $c->contracts->map(fn (Contract $k) => $display($c) . ' | ' . $k->number))->all();

        $staticRoots  = $this->withData ? [] : $units->whereNull('parent_id')->map(fn ($c) => $c->name)->values()->all();
        $unitList     = '=' . S::LISTS . '!$A$2:$A$' . (S::MAX_ROWS + 1 + count($staticUnits));
        $contractList = '=' . S::LISTS . '!$B$2:$B$' . (S::MAX_ROWS + 1 + count($staticContracts));
        $serviceList  = '=' . S::LISTS . '!$C$2:$C$' . (count($services) + 1);
        $actions      = '"Salvar,Remover,Manter"';
        $last         = S::MAX_ROWS + 1;

        return [
            new BulkInstructionsSheet($this->withData),
            new BulkDataSheet(S::COMPANIES, $headers[S::COMPANIES], $companyRs, [
                'widths'   => [12, 34, 30, 32, 18, 34, 22, 22, 7, 12, 4],
                'tab'      => '0F766E',
                'required' => ['B', 'D'],
                'text'     => ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'],
                'locked'   => ['J', 'K'],
                'hidden'   => ['K'],
                'formulas' => ['K' => fn (int $r) => "=IF(B{$r}=\"\",\"\",IF(C{$r}=\"\",B{$r},C{$r}&\" / \"&B{$r}))"],
                'dupe'     => ['B', 'COUNTIF($K$2:$K$' . $last . ',$K2)>1'],
                'dv'       => [
                    ['A', ['list' => $actions]],
                    ['B', ['len' => [2, 255], 'msg' => 'Informe o nome da empresa (2 a 255 caracteres, sem "/").']],
                    ['C', ['list' => '=' . S::LISTS . '!$D$2:$D$' . (S::MAX_ROWS + 1 + count($staticRoots))]],
                    ['D', ['custom' => 'AND(ISNUMBER(FIND("@",D2)),ISNUMBER(FIND(".",D2,FIND("@",D2))),ISERROR(FIND(" ",D2)))', 'msg' => 'Informe um e-mail válido (nome@dominio.com).']],
                    ['E', ['len' => [0, 20], 'msg' => 'Telefone com até 20 caracteres, ex.: (27) 99999-9999.']],
                    ['I', ['list' => '"' . implode(',', S::UFS) . '"']],
                ],
            ]),
            new BulkDataSheet(S::CENTERS, $headers[S::CENTERS], $centerRs, [
                'widths'   => [12, 44, 20, 16, 16],
                'tab'      => '1D4ED8',
                'required' => ['B', 'C'],
                'text'     => ['B', 'C', 'D', 'E'],
                'dupe'     => ['C', 'COUNTIFS($B$2:$B$' . $last . ',$B2,$C$2:$C$' . $last . ',$C2)>1'],
                'dv'       => [
                    ['A', ['list' => $actions]],
                    ['B', ['list' => $unitList]],
                    ['C', ['len' => [1, 100], 'msg' => 'Informe o código (ex.: ENGELCAC).']],
                    ['D', ['len' => [0, 100]]],
                    ['E', ['len' => [0, 100]]],
                ],
            ]),
            new BulkDataSheet(S::CONTRACTS, $headers[S::CONTRACTS], $contractRs, [
                'widths'   => [12, 44, 24, 14, 18, 4],
                'tab'      => '7C3AED',
                'required' => ['B', 'C', 'D', 'E'],
                'text'     => ['B', 'C'],
                'dates'    => ['D'],
                'locked'   => ['F'],
                'hidden'   => ['F'],
                'formulas' => ['F' => fn (int $r) => "=IF(OR(B{$r}=\"\",C{$r}=\"\"),\"\",B{$r}&\" | \"&C{$r})"],
                'dupe'     => ['C', 'COUNTIF($F$2:$F$' . $last . ',$F2)>1'],
                'dv'       => [
                    ['A', ['list' => $actions]],
                    ['B', ['list' => $unitList]],
                    ['C', ['len' => [1, 100], 'msg' => 'Informe o número do contrato (sem o caractere "|").']],
                    ['D', ['date' => true, 'msg' => 'Informe uma data válida (dd/mm/aaaa) entre 2000 e 2100.']],
                    ['E', ['list' => '"' . implode(',', S::CONTRACT_TYPES) . '"']],
                ],
            ]),
            new BulkDataSheet(S::ACTIVITIES, $headers[S::ACTIVITIES], $activityRs, [
                'widths'   => [12, 62, 40, 12],
                'tab'      => 'D97706',
                'required' => ['B', 'C'],
                'text'     => ['B', 'C'],
                'dupe'     => ['C', 'COUNTIFS($B$2:$B$' . $last . ',$B2,$C$2:$C$' . $last . ',$C2)>1'],
                'dv'       => [
                    ['A', ['list' => $actions]],
                    ['B', ['list' => $contractList]],
                    ['C', ['list' => $serviceList]],
                    ['D', ['list' => '"Sim,Não"']],
                ],
            ]),
            new BulkListsSheet($staticUnits, $staticContracts, $services, $staticRoots),
        ];
    }
}
