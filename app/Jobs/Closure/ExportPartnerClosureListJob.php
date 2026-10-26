<?php

namespace App\Jobs\Closure;

use App\Exports\Closure\ClosureListExport;
use App\Http\Livewire\Partner\ClosureIssues\{History, Index};
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Closure\{ClosureEventLabels, ClosurePartnerExporter};
use App\Services\PartnerAccess\PartnerAccessGate;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Auth, Log, Storage};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Exporta, em segundo plano, as listas da parceira (pendências e histórico) com os filtros que ela tinha na tela e SEMPRE no escopo
 * de empresa do solicitante (a consulta é a da própria tela). Avisa o usuário por SystemNotification com o link do arquivo.
 */
class ExportPartnerClosureListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const TITLES = [
        Index::class   => 'Pendências de Encerramento',
        History::class => 'Meu histórico de retornos - Encerramento',
    ];

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 600;

    /** @param array<string, mixed> $filters propriedades públicas que definem o recorte da tela */
    public function __construct(public string $component, public array $filters, public string $userId)
    {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $user  = User::find($this->userId);
        $title = self::TITLES[$this->component] ?? 'Lista do Encerramento';

        try {
            abort_unless($user && isset(self::TITLES[$this->component]), 403);
            abort_unless(PartnerAccessGate::allows($user, 'closure.issues.list'), 403);
            Auth::setUser($user);

            $list = new $this->component();

            foreach ($this->filters as $property => $value) {
                if (property_exists($list, $property)) {
                    $list->{$property} = $value;
                }
            }

            $exporter = app(ClosurePartnerExporter::class);

            if ($this->component === Index::class) {
                $records  = $list->exportQuery()->get();
                $headings = ClosurePartnerExporter::ISSUE_HEADINGS;
                $rows     = $exporter->issueRows($records);
                $summary  = $exporter->summaryRows('Situação', $records->countBy(fn ($issue) => ClosureEventLabels::issueStatusLabel($issue->status))->all());
                $sheet    = 'Pendências';
            } else {
                $records  = $list->exportQuery()->get();
                $headings = ClosurePartnerExporter::HISTORY_HEADINGS;
                $rows     = $exporter->historyRows($records);
                $summary  = $exporter->summaryRows('Resultado desta resposta', $records->countBy(fn ($event) => ClosurePartnerExporter::outcomeOf($event))->all());
                $sheet    = 'Retornos';
            }

            $filePath = 'exports/encerramento_parceira_' . now()->format('YmdHis') . '_' . substr(md5($this->component . $this->userId), 0, 6) . '.xlsx';
            Storage::disk('local')->makeDirectory('exports');

            $audit = array_merge([
                ['Usuário solicitante', $user->name],
                ['Email', $user->email],
                ['Data/Hora da solicitação', now()->format('d/m/Y H:i:s')],
                ['Tipo de exportação', $title],
            ], $this->filterRows(), [
                ['Registros exportados', number_format(count($rows), 0, ',', '.') . ' registros'],
            ]);

            Excel::store(new ClosureListExport($title, $headings, $rows, $summary, $audit, $sheet), $filePath, 'local');

            if (!Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação - ' . $title,
                message: 'Sua lista está pronta para download (' . count($rows) . ' registros).',
                link: Storage::url($filePath),
                status: 'download',
            )));
        } catch (Throwable $exception) {
            Log::error('ExportPartnerClosureListJob falhou', ['error_message' => $exception->getMessage(), 'component' => $this->component]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Erro ao exportar a lista',
            message: 'Ocorreu um erro ao gerar o arquivo. ' . $exception->getMessage(),
            status: 'error',
        )));
    }

    /** @return array<int, array<int, mixed>> */
    private function filterRows(): array
    {
        $labels = ['search' => 'Busca', 'status' => 'Situação', 'categoryId' => 'Categoria', 'from' => 'De', 'to' => 'Até', 'partnerCompanyFilter' => 'Empresa'];
        $rows   = [];

        foreach ($labels as $key => $label) {
            if (array_key_exists($key, $this->filters)) {
                $text   = (string) $this->filters[$key];
                $rows[] = ['Filtro - ' . $label, $text === '' ? '---' : $text];
            }
        }

        return $rows;
    }
}
