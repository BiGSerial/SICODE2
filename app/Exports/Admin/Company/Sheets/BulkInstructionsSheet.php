<?php

namespace App\Exports\Admin\Company\Sheets;

use App\Exports\Admin\Company\Sheets\Concerns\StylesCompanyWorkbook;
use Maatwebsite\Excel\Concerns\{FromArray, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BulkInstructionsSheet implements FromArray, WithEvents, WithTitle
{
    use StylesCompanyWorkbook;

    public function __construct(private readonly bool $withData)
    {
    }

    public function title(): string
    {
        return 'Leia-me';
    }

    public function array(): array
    {
        return [
            ['PLANILHA EM MASSA DE EMPRESAS'],
            [$this->withData ? 'Exportação completa do cadastro em ' . now()->format('d/m/Y H:i') . '. Edite, acrescente ou remova linhas e envie de volta.' : 'Modelo em branco. Preencha as abas e envie pelo sistema.'],
            [],
            ['COMO USAR'],
            ['1. Preencha somente as abas Empresas, Centros e Depositos, Contratos e Atividades. A aba Listas é automática.'],
            ['2. Células cinza são calculadas e protegidas. Campos com seta têm lista: escolha o valor, não digite.'],
            ['3. Coluna Ação: "Salvar" cria se não existe e atualiza se já existe (pode enviar o mesmo arquivo várias vezes sem duplicar). "Remover" retira o item. "Manter" ignora a linha.'],
            ['4. Para cadastrar um centro/contrato/atividade de uma empresa NOVA, cadastre a empresa na aba Empresas na mesma planilha: ela aparece nas listas das demais abas.'],
            ['5. Envie o arquivo em Empresas > Planilha em massa. O sistema confere tudo e mostra o resultado ANTES de gravar. Se houver qualquer erro, nada é gravado.'],
            [],
            ['REGRAS'],
            ['• Campos com * são obrigatórios. Linhas incompletas ficam em vermelho; linhas repetidas ficam em vermelho escuro.'],
            ['• Células em branco ao atualizar uma empresa/contrato existente NÃO apagam o valor atual.'],
            ['• Unidade de (matriz): deixe vazio para uma matriz; escolha a matriz para cadastrar uma unidade.'],
            ['• Remover empresa apenas a desativa (pode ser reativada na tela). Não é possível remover empresa com usuários ativos, nem matriz com unidades ativas.'],
            ['• Remover contrato com usuários vinculados não é permitido. Remover um contrato também remove as atividades liberadas dele.'],
            ['• Tipo do contrato limita as atividades: Serviço (projeto/pós-obra), Obra (obra/pós-obra) ou Serviço e Obra (todas).'],
            ['• Não renomeie abas, não altere a primeira linha, não insira nem exclua colunas.'],
            ['• Limite de ' . \App\Services\Admin\Company\CompanyBulkSchema::MAX_ROWS . ' linhas por aba.'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $this->styleTitle($sheet, 'A1', 'PLANILHA EM MASSA DE EMPRESAS');
                $sheet->getColumnDimension('A')->setWidth(150);
                $sheet->getStyle('A1:A19')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

                foreach (['A4', 'A11'] as $cell) {
                    $this->styleHeader($sheet, $cell);
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                $this->protectSheet($sheet);
                $sheet->getTabColor()->setRGB('0F172A');
            },
        ];
    }
}
