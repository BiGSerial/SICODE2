<?php

namespace App\Jobs\Partner;

use App\Exports\Partner\DeclaredEquipmentListExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Partner\DeclaredEquipmentQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportDeclaredEquipmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_CSV = 'csv';

    public array $params;
    public string $userId;
    public string $format;

    public $tries = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200;

    /**
     * @param array<string,mixed> $params
     */
    public function __construct(array $params, string $userId, string $format)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
        $this->format = $format === self::FORMAT_XLSX ? self::FORMAT_XLSX : self::FORMAT_CSV;
    }

    public function handle(DeclaredEquipmentQueryService $queryService): void
    {
        $user = User::with(['Companies', 'Company'])->find($this->userId);
        $filePath = null;

        if (!$user) {
            return;
        }

        try {
            $stamp = now()->format('YmdHis');
            $extension = $this->format === self::FORMAT_XLSX ? 'xlsx' : 'csv';
            $filePath = "exports/declared_equipment_{$stamp}.{$extension}";

            Storage::disk('local')->makeDirectory('exports');

            $query = $queryService->build($this->params, $user)
                ->with(['WorkReport.Note', 'WorkReport.Orders', 'WorkReport.Company', 'WorkReport.User']);

            if ($this->format === self::FORMAT_XLSX) {
                Excel::store(new DeclaredEquipmentListExport($query), $filePath, 'local');
            } else {
                $this->storeCsv($query, $filePath);
            }

            if (!Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(
                'Exportacao de Equipamentos Declarados',
                'Seu arquivo esta pronto para download.',
                Storage::url($filePath),
                4,
                ['format' => $this->format]
            ));
        } catch (Throwable $exception) {
            Log::error('ExportDeclaredEquipmentJob falhou', [
                'user_id' => $this->userId,
                'format' => $this->format,
                'params' => $this->params,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $exception;
        }
    }

    private function storeCsv($query, string $filePath): void
    {
        $absolutePath = storage_path('app/' . $filePath);
        $handle = fopen($absolutePath, 'wb');

        if (!$handle) {
            throw new \RuntimeException('Nao foi possivel criar o arquivo CSV.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $this->headings(), ';');

        $query->reorder()->chunkById(1000, function ($rows) use ($handle) {
            foreach ($rows as $row) {
                fputcsv($handle, $this->mapRow($row), ';');
            }
        });

        fclose($handle);
    }

    private function headings(): array
    {
        return [
            'Patrimônio',
            'Tipo',
            'Movimento',
            'Poste Referencia',
            'Nota/OV',
            'Ordem',
            'Rubrica',
            'Município',
            'Empreiteira',
            'Responsável',
            'Informado em',
            'Usuário',
        ];
    }

    private function mapRow($row): array
    {
        $workReport = $row->WorkReport;

        return [
            $row->patrimony,
            $row->type,
            $row->installed ? 'INSTALAÇÃO' : 'RETIRADA',
            $row->pole,
            $workReport?->Note?->note,
            $workReport?->Orders?->pluck('ordem')->implode(' | '),
            $workReport?->Note?->rubrica,
            $workReport?->Note?->lexp,
            $workReport?->Company?->name,
            $workReport?->responsible,
            $workReport?->informed_at ? $workReport->informed_at->format('d/m/Y H:i') : '',
            $workReport?->User?->name,
        ];
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ExportDeclaredEquipmentJob FAILED', [
            'user_id' => $this->userId,
            'format' => $this->format,
            'error' => $exception->getMessage(),
        ]);

        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro ao gerar exportacao',
                'A geração da exportação de equipamentos declarados falhou após novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
