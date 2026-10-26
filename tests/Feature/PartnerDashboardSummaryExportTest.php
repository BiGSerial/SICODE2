<?php

namespace Tests\Feature;

use App\Exports\Partner\DashboardSummaryExport;
use App\Http\Livewire\Partner\Main;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PartnerDashboardSummaryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_workbook_has_summary_sheet_and_tables(): void
    {
        $summary = [
            'headline' => 'Dashboard da Parceira — Resumo executivo',
            'context'  => ['Empresa' => 'Compel', 'Período' => '01/10/2026 a 31/10/2026'],
            'sections' => [
                ['title' => 'Indicadores operacionais', 'headings' => ['Indicador', 'Quantidade', 'Situação'], 'rows' => [['ADS em atraso', 3, 'Crítico'], ['D5 devolvidos', 0, 'Em dia']], 'status' => 2],
                ['title' => 'Vencimentos', 'headings' => ['Prazo', 'ADS', 'Viabilidades', 'Total'], 'rows' => [['Hoje', 1, 0, 1]]],
            ],
            'footnote' => 'Valores meramente informativos.',
        ];

        $tables = [
            ['title' => 'Atraso de ADS', 'headings' => ['Nota', 'Empresa', 'Dias de atraso'], 'rows' => [['4000000001', 'Compel', 5]]],
        ];

        $binary = Excel::raw(new DashboardSummaryExport($summary, $tables), ExcelWriter::XLSX);

        $path = tempnam(sys_get_temp_dir(), 'dash') . '.xlsx';
        file_put_contents($path, $binary);
        $book = IOFactory::load($path);
        unlink($path);

        $this->assertSame(['Resumo', 'Atraso de ADS'], $book->getSheetNames());

        $resumo = $book->getSheetByName('Resumo');
        $this->assertSame('Dashboard da Parceira — Resumo executivo', $resumo->getCell('A1')->getValue());
        $this->assertSame('Compel', $resumo->getCell('B2')->getValue());
        $this->assertSame('Crítico', $resumo->getCell('C7')->getValue());

        $this->assertSame('4000000001', (string) $book->getSheetByName('Atraso de ADS')->getCell('A3')->getValue());
    }

    public function test_dashboard_component_exports_summary_workbook(): void
    {
        $user = User::factory()->create(['superadm' => true]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('exportSummaryXlsx')
            ->assertFileDownloaded();
    }
}
