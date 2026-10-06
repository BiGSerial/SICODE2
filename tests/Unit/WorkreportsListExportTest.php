<?php

namespace Tests\Unit;

use App\Exports\Workreports\WorkreportsListExport;
use App\Models\{Company, Note, User, WorkReport};
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class WorkreportsListExportTest extends TestCase
{
    public function test_acceptance_column_has_compact_summary_and_full_audit_comment(): void
    {
        $workReport = new WorkReport([
            'acceptance_accepted' => true,
            'acceptance_name'     => 'Maria Assinante',
            'acceptance_at'       => '2026-10-06 14:30:00',
            'acceptance_meta'     => [
                'current' => [
                    'app_user'  => ['name' => 'Usuário SICODE'],
                    'signature' => [
                        'signed_name' => 'Maria Assinante',
                        'signed_at'   => '2026-10-06T14:30:00-03:00',
                        'hash'        => str_repeat('a', 64),
                    ],
                ],
                'history' => [['event' => 'reinform', 'captured_at' => '2026-10-05 10:00:00']],
            ],
            'informed_at' => Carbon::parse('2026-10-06 14:30:00'),
        ]);
        $workReport->setRelation('Note', new Note(['note' => '4000000111']));
        $workReport->setRelation('Orders', new Collection());
        $workReport->setRelation('Equipment', new Collection());
        $workReport->setRelation('Company', new Company(['name' => 'Compel']));
        $workReport->setRelation('User', new User(['name' => 'Usuário SICODE']));

        $export = new WorkreportsListExport([]);
        $row    = $export->map($workReport);

        $this->assertStringContainsString('Maria Assinante', $row[13]);
        $this->assertStringContainsString('Usuário SICODE', $row[13]);
        $this->assertStringContainsString('aaaaaaaaaaaaaaaa…', $row[13]);

        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();
        $worksheet->fromArray([$export->headings(), $row]);
        $events = $export->registerEvents();
        $events[AfterSheet::class](new AfterSheet(new Sheet($worksheet), $export));

        $comment = $worksheet->getComment('N2')->getText()->getPlainText();

        $this->assertStringContainsString('Hash da assinatura: ' . str_repeat('a', 64), $comment);
        $this->assertStringContainsString('"history"', $comment);
        $this->assertSame(42.0, $worksheet->getColumnDimension('N')->getWidth());
        $this->assertSame('A2', $worksheet->getFreezePane());
        $this->assertSame('A1:N2', $worksheet->getAutoFilter()->getRange());
        $this->assertFalse($worksheet->getShowGridlines());
    }
}
