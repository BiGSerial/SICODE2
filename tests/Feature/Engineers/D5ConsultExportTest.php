<?php

namespace Tests\Feature\Engineers;

use App\Jobs\Engineers\ExportWaitingFiveNotesJob;
use App\Models\{Company, FiveNote, Note, User};
use App\Notifications\SystemNotification;
use App\Services\Engineers\D5ConsultAnalytics;
use App\Services\Files\ExportStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, Notification, Storage};
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class D5ConsultExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('local');
        config(['filesystems.exports_disk' => 'local']);
        Notification::fake();

        $company    = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
        $this->user = User::factory()->create(['company_id' => $company->id, 'superadm' => true, 'name' => 'Ana Engenheira']);

        foreach ([['1', now()->subDays(9), false], ['2', now()->subDays(1), false], ['3', now()->subDays(12), true]] as [$d5, $dispatch, $archived]) {
            $note = Note::create(['note' => '40000000' . $d5, 'dt_status' => now()->toDateTimeString(), 'nstats' => 'NEW']);

            FiveNote::create([
                'note_id'         => $note->id, 'note_d5' => $d5, 'company_id' => $company->id, 'reason' => '1_REPARO',
                'visible_partner' => true, 'dispatch_at' => $dispatch, 'is_archived' => $archived,
            ]);
        }
    }

    private function generate(array $params): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        (new ExportWaitingFiveNotesJob($params, (string) $this->user->id))
            ->handle(app(ExportStorage::class), app(D5ConsultAnalytics::class));

        $files = Storage::disk('local')->files('exports');
        $this->assertCount(1, $files);

        return IOFactory::load(Storage::disk('local')->path($files[0]));
    }

    public function test_workbook_has_the_summary_block_sheets_list_and_control(): void
    {
        $book = $this->generate([]);

        $names = $book->getSheetNames();

        foreach (['Por etapa', 'Por empreiteira', 'Por motivo', 'Faixas de idade', 'Fluxo mensal', 'Lista D5', 'Controle Exportacao'] as $expected) {
            $this->assertContains($expected, $names);
        }

        $this->assertCount(8, $names);
        Notification::assertSentTo($this->user, SystemNotification::class);
    }

    public function test_stage_sheet_always_covers_every_stage_even_when_the_list_is_filtered(): void
    {
        $book  = $this->generate(['statusFilter' => 'finalizado']);
        $stage = $book->getSheetByName('Por etapa')->toArray();

        $byStage = collect($stage)->filter(fn ($r) => is_string($r[0] ?? null))->mapWithKeys(fn ($r) => [$r[0] => $r[1]]);

        $this->assertSame(2, (int) $byStage['Fornecedor']);
        $this->assertSame(1, (int) $byStage['Finalizado']);

        // A lista segue a etapa escolhida: só a finalizada (cabeçalho + 1 linha).
        $this->assertCount(2, array_filter($book->getSheetByName('Lista D5')->toArray(), fn ($r) => array_filter($r, fn ($c) => $c !== null && $c !== '')));
    }

    public function test_control_sheet_records_who_when_and_the_filters(): void
    {
        $book    = $this->generate(['search' => 'REPARO', 'statusFilter' => 'aguardando_fornecedor']);
        $control = collect($book->getSheetByName('Controle Exportacao')->toArray())->mapWithKeys(fn ($r) => [$r[0] => $r[1]]);

        $this->assertSame('Ana Engenheira', $control['Usuário']);
        $this->assertSame('Consulta geral D5', $control['Tipo']);
        $this->assertSame('REPARO', $control['Busca']);
        $this->assertSame('Fornecedor', $control['Etapa da lista']);
    }

    public function test_screen_without_finalized_leaves_them_out_of_every_sheet(): void
    {
        $book  = $this->generate(['hideFinalized' => true]);
        $stage = collect($book->getSheetByName('Por etapa')->toArray())->filter(fn ($r) => is_string($r[0] ?? null))->pluck(1, 0);

        $this->assertArrayNotHasKey('Finalizado', $stage->all());
        $this->assertSame(2, (int) $stage['Fornecedor']);

        $listRows = array_filter($book->getSheetByName('Lista D5')->toArray(), fn ($r) => array_filter($r, fn ($c) => $c !== null && $c !== ''));
        $this->assertCount(3, $listRows); // cabeçalho + 2 D5 em andamento
    }
}
