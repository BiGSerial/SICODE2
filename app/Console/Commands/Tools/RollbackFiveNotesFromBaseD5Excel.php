<?php

namespace App\Console\Commands\Tools;

use App\Models\FiveNote;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RollbackFiveNotesFromBaseD5Excel extends Command
{
    protected $signature = 'sicode:rollback-five-notes-excel
                            {--excel= : Arquivo Excel de resultado gerado pela importacao}
                            {--allow-original : Permite usar o Excel original sem coluna de resultado, lendo todas as notas da coluna A}
                            {--dry : Simula sem remover registros}
                            {--force : Executa sem pedir confirmacao}';

    protected $description = 'Remove apenas as five_notes criadas pela importacao Excel de D5 passivo';

    public function handle(): int
    {
        $excel = $this->cleanScalar($this->option('excel'));

        if (! $excel) {
            $this->error('Informe o Excel de resultado com --excel=...');
            return self::FAILURE;
        }

        $path = $this->resolvePath($excel);

        if (! is_readable($path)) {
            $this->error("Arquivo Excel nao legivel: {$path}");
            $this->line('Confira se o arquivo existe e remova barras antes de underline no caminho, se houver.');
            $this->showNearbyExcelFiles($path);
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry');
        $force = (bool) $this->option('force');
        $allowOriginal = (bool) $this->option('allow-original');

        $this->info("Lendo Excel de resultado: {$path}");

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $statusColumn = $this->findStatusColumn($sheet);

        if ($statusColumn) {
            $noteD5s = $this->readCreatedNoteD5s($sheet, $statusColumn);
            $sourceDescription = 'linhas SUBIU no Excel de resultado';
        } else {
            if (! $allowOriginal) {
                $this->error('Coluna resultado_importacao_sicode nao encontrada.');
                $this->line('Use o Excel gerado pelo importador ou rode novamente com --allow-original para ler todas as notas da coluna A.');
                return self::FAILURE;
            }

            $noteD5s = $this->readAllNoteD5s($sheet);
            $sourceDescription = 'notas da coluna A do Excel original';
            $this->warn('Excel sem coluna de resultado: usando todas as notas da coluna A.');
        }

        if ($noteD5s === []) {
            $this->info('Nenhuma D5 encontrada para rollback. Nada a remover.');
            return self::SUCCESS;
        }

        $baseQuery = FiveNote::query()
            ->whereIn('note_d5', $noteD5s)
            ->where('isPassive', true);

        $total = (clone $baseQuery)->count();
        $existingNoteD5s = (clone $baseQuery)->pluck('note_d5')->all();
        $missing = array_values(array_diff($noteD5s, $existingNoteD5s));

        $this->line('Modo: ' . ($dryRun ? 'DRY RUN (sem remocao)' : 'EXECUCAO REAL'));
        $this->line(ucfirst($sourceDescription) . ': ' . count($noteD5s));
        $this->line("FiveNotes passivas encontradas para rollback: {$total}");

        if ($missing !== []) {
            $this->warn('D5 marcadas como SUBIU mas nao encontradas/passivas no banco: ' . count($missing));
            foreach (array_slice($missing, 0, 10) as $noteD5) {
                $this->line(" - {$noteD5}");
            }
        }

        if ($total === 0) {
            $this->info('Nenhum registro elegivel encontrado. Nada a remover.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Amostra dos registros que serao removidos:');
        (clone $baseQuery)
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'note_d5', 'note_id', 'company_id', 'created_at'])
            ->each(function (FiveNote $fiveNote): void {
                $this->line(sprintf(
                    ' - ID %s | D5 %s | note_id %s | company_id %s | created_at %s',
                    $fiveNote->id,
                    $fiveNote->note_d5 ?? 'N/A',
                    $fiveNote->note_id ?? 'N/A',
                    $fiveNote->company_id ?? 'N/A',
                    optional($fiveNote->created_at)->toDateTimeString() ?? 'N/A'
                ));
            });

        if (! $dryRun && ! $force) {
            if (! $this->confirm('Confirmar rollback desses registros?', false)) {
                $this->warn('Operacao cancelada pelo usuario.');
                return self::SUCCESS;
            }
        }

        $deleted = 0;

        (clone $baseQuery)
            ->orderBy('id')
            ->chunkById(500, function ($fiveNotes) use ($dryRun, &$deleted): void {
                foreach ($fiveNotes as $fiveNote) {
                    if (! $dryRun) {
                        $fiveNote->delete();
                    }

                    $deleted++;
                }
            });

        $this->newLine();
        $this->info($dryRun ? 'Simulacao concluida.' : 'Rollback concluido.');
        $this->line(($dryRun ? 'Registros que seriam removidos: ' : 'Registros removidos: ') . $deleted);

        return self::SUCCESS;
    }

    protected function readCreatedNoteD5s($sheet, string $statusColumn): array
    {
        $highestRow = $sheet->getHighestDataRow();
        $noteD5s = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $status = $this->cleanScalar($sheet->getCell($statusColumn . $row)->getFormattedValue());

            if (! $status || ! str_starts_with($status, 'SUBIU')) {
                continue;
            }

            $noteD5 = $this->cleanScalar($sheet->getCell('A' . $row)->getFormattedValue());

            if ($noteD5) {
                $noteD5s[] = $noteD5;
            }
        }

        return array_values(array_unique($noteD5s));
    }

    protected function readAllNoteD5s($sheet): array
    {
        $highestRow = $sheet->getHighestDataRow();
        $noteD5s = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $noteD5 = $this->cleanScalar($sheet->getCell('A' . $row)->getFormattedValue());

            if ($noteD5) {
                $noteD5s[] = $noteD5;
            }
        }

        return array_values(array_unique($noteD5s));
    }

    protected function findStatusColumn($sheet): ?string
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $coordinate = Coordinate::stringFromColumnIndex($column);
            $value = $this->cleanScalar($sheet->getCell($coordinate . '1')->getFormattedValue());

            if ($value === 'resultado_importacao_sicode') {
                return $coordinate;
            }
        }

        return null;
    }

    protected function resolvePath(string $path): string
    {
        $path = $this->normalizeShellCopiedPath($path);

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    protected function normalizeShellCopiedPath(string $path): string
    {
        return str_replace('\_', '_', $path);
    }

    protected function showNearbyExcelFiles(string $path): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            return;
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.xlsx') ?: [];

        if ($files === []) {
            return;
        }

        $this->newLine();
        $this->line('Arquivos .xlsx encontrados nessa pasta:');

        foreach (array_slice($files, 0, 10) as $file) {
            $this->line(' - ' . $file);
        }
    }

    protected function cleanScalar($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
