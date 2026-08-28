<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class UsersSheet implements FromArray, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    private Collection $existingUsers;

    public function __construct(private readonly Company $root, private readonly Collection $units)
    {
        $unitIds = $this->units->pluck('id')->filter()->values();

        $this->existingUsers = User::query()
            ->with([
                'Company.parent',
                'Employee.Contract.company.parent',
                'Employee.Service',
                'Watchdog',
            ])
            ->where(function ($query) use ($unitIds) {
                $query->whereIn('company_id', $unitIds)
                    ->orWhereRelation('Employee.Contract', fn ($contractQuery) => $contractQuery->whereIn('company_id', $unitIds));
            })
            ->orderBy('name')
            ->get();
    }

    public function array(): array
    {
        $rows = [
            ['FICHA DE CADASTRO DE USUARIOS'],
            ['Empresa concentradora', $this->root->name, 'Gerado em', now()->format('d/m/Y H:i')],
            ['Preencha uma linha por usuario. Use Criar, Manter ou Remover para indicar a acao desejada.'],
            [],
            [
            'Acao',
            'Nome',
            'Email',
                'Matricula',
                'Empresa/Unidade',
                'Contrato',
                'Atividade Principal',
                'Admin',
                'Operador',
                'Usuario',
            'Gerente',
            'Observacao',
            'Ultimo acesso',
            ],
        ];

        foreach ($this->existingUsers as $user) {
            $company = $user->Company ?: $user->Employee?->Contract?->company ?: $this->root;
            $contract = $user->Employee?->Contract;
            $contractLabel = $contract
                ? "{$contract->number} | {$contract->company?->display_name}"
                : '';
            $primaryServiceName = $user->Employee?->Service?->service
                ?: Service::where('uuid', $user->Employee?->service_id)->value('service')
                ?: '';

            $rows[] = [
                'Manter',
                $user->name,
                $user->email,
                $user->Registration,
                $company->display_name,
                $contractLabel,
                $primaryServiceName,
                $user->admin ? 'Sim' : 'Nao',
                $user->operator ? 'Sim' : 'Nao',
                $user->user ? 'Sim' : 'Nao',
                $user->management ? 'Sim' : 'Nao',
                '',
                optional($user->Watchdog?->updated_at)->format('d/m/Y H:i') ?: 'Sem registro',
            ];
        }

        for ($i = 0; $i < 80; $i++) {
            $rows[] = ['Criar', '', '', '', $this->root->display_name, '', '', 'Nao', 'Nao', 'Sim', 'Nao', '', ''];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Usuarios';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 5;
                $firstDataRow = 6;
                $lastRow = $firstDataRow + $this->existingUsers->count() + 79;

                $sheet->mergeCells('A1:M1');
                $sheet->mergeCells('A3:M3');
                $this->styleTitle($sheet, 'A1', 'FICHA DE CADASTRO DE USUARIOS');
                $this->styleSubtleHeader($sheet, 'A2:D2');
                $sheet->getStyle('A3:M3')->getFont()->setItalic(true);
                $sheet->getStyle('A3:M3')->getAlignment()->setWrapText(true);
                $this->styleHeader($sheet, "A{$headerRow}:M{$headerRow}");
                $this->styleBody($sheet, "A1:M{$lastRow}");
                $this->setWidths($sheet, ['A' => 14, 'B' => 34, 'C' => 36, 'D' => 16, 'E' => 38, 'F' => 44, 'G' => 30, 'H' => 12, 'I' => 14, 'J' => 12, 'K' => 12, 'L' => 34, 'M' => 18]);
                $this->freezeAndFilter($event, "A{$firstDataRow}", "A{$headerRow}:M{$lastRow}");
                $sheet->getTabColor()->setRGB('16A34A');
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(3)->setRowHeight(30);
                $sheet->getStyle("A{$firstDataRow}:M{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getComment("A{$headerRow}")->getText()->createTextRun('Criar: novo usuario. Manter: usuario existente continua no quadro. Remover: usuario nao faz mais parte do quadro e deve ser inativado no importador.');
                $sheet->getComment("F{$headerRow}")->getText()->createTextRun('Se a unidade tiver mais de um contrato, selecione o contrato correto. Se ficar vazio, o importador tentará resolver pelo contrato disponível.');
                $sheet->getComment("G{$headerRow}")->getText()->createTextRun('Atividade principal do usuario. O importador adiciona somente esta atividade ao usuario, sem sincronizar todas as atividades do contrato.');
                $sheet->getComment("H{$headerRow}")->getText()->createTextRun('Colunas booleanas funcionais. A selecao manual de atividades no gerenciamento de usuarios e soberana.');
                $sheet->getComment("M{$headerRow}")->getText()->createTextRun('Coluna apenas informativa. O importador ignora este campo.');
                $sheet->getStyle("M{$headerRow}:M{$lastRow}")->getFont()->getColor()->setRGB('64748B');

                $unitListEnd = max(2, $this->units->count() + 1);
                $contractCount = $this->units->flatMap(fn ($unit) => $unit->contracts)->count();
                $contractListEnd = max(2, $contractCount + 1);
                $serviceCount = $this->units
                    ->flatMap(fn ($unit) => $unit->contracts)
                    ->flatMap(fn ($contract) => $contract->services)
                    ->unique('uuid')
                    ->count();
                $serviceListEnd = max(2, $serviceCount + 1);

                for ($row = $firstDataRow; $row <= $lastRow; $row++) {
                    $this->applyListValidation($sheet->getCell("A{$row}")->getDataValidation(), '"Criar,Manter,Remover"');
                    $this->applyListValidation($sheet->getCell("E{$row}")->getDataValidation(), "'Listas'!\$A\$2:\$A\${$unitListEnd}");
                    $this->applyListValidation($sheet->getCell("F{$row}")->getDataValidation(), "'Listas'!\$B\$2:\$B\${$contractListEnd}", true);
                    $this->applyListValidation($sheet->getCell("G{$row}")->getDataValidation(), "'Listas'!\$E\$2:\$E\${$serviceListEnd}", true);
                    foreach (['H', 'I', 'J', 'K'] as $column) {
                        $this->applyListValidation($sheet->getCell("{$column}{$row}")->getDataValidation(), '"Sim,Nao"');
                    }
                }
            },
        ];
    }

    private function applyListValidation(DataValidation $validation, string $formula, bool $allowBlank = false): void
    {
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank($allowBlank);
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Valor invalido');
        $validation->setError('Selecione uma opção da lista.');
        $validation->setFormula1($formula);
    }
}
