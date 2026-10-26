<?php

namespace App\Jobs\Closure;

use App\Exports\Closure\ClosureListExport;
use App\Http\Livewire\Closure\Dispatch\{Control, Index as DispatchIndex};
use App\Http\Livewire\Closure\Inbox\Index as InboxIndex;
use App\Http\Livewire\Closure\Work\{All as WorkAll, Available, History, Index as WorkIndex};
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Closure\{ClosureListExporter, ClosurePartnerSignalService};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Auth, Log, Storage};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Exporta, em segundo plano, qualquer lista do Encerramento com os filtros que o usuário tinha na tela.
 * `$component` é a classe Livewire da lista; `$filters`, as propriedades públicas que definem o recorte.
 */
class ExportClosureListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const TITLES = [
        DispatchIndex::class => 'Lista para Encerramento (Despacho)',
        Control::class       => 'Controle de Encerramento',
        WorkIndex::class     => 'Minha Atividade - Encerramento',
        Available::class     => 'Atividades disponíveis - Encerramento',
        History::class       => 'Meu histórico - Encerramento',
        InboxIndex::class    => 'Caixa em acompanhamento - Encerramento',
        WorkAll::class       => 'Lista Encerramento - Todas as Ordens',
    ];

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 1200;

    /** @param array<string, mixed> $filters */
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
            Auth::setUser($user);

            $exporter = app(ClosureListExporter::class);

            if (in_array($this->component, [DispatchIndex::class, WorkAll::class, InboxIndex::class], true)) {
                abort_unless($user->can(match ($this->component) {
                    DispatchIndex::class => 'closure.dispatch',
                    InboxIndex::class    => 'closure.inbox.' . strtolower((string) ($this->filters['area'] ?? '')),
                    default              => 'closure.work',
                }), 403);
                $records = app(ClosureDispatchListQuery::class)->exportRecords($this->filters);
                $built   = $exporter->dispatchRows($records, app(ClosurePartnerSignalService::class)->activeByState($records->pluck('state_id')));
            } else {
                $list = new $this->component();

                foreach ($this->filters as $property => $value) {
                    if (property_exists($list, $property)) {
                        $list->{$property} = $value;
                    }
                }

                $states = $list->exportQuery()->get();
                $built  = $exporter->stateRows($states, app(ClosurePartnerSignalService::class)->activeByState($states->pluck('id')));
            }

            $filePath = 'exports/encerramento_' . now()->format('YmdHis') . '_' . substr(md5($this->component . $this->userId), 0, 6) . '.xlsx';
            Storage::disk('local')->makeDirectory('exports');

            $audit = array_merge([
                ['Usuário solicitante', $user->name],
                ['Email', $user->email],
                ['Data/Hora da solicitação', now()->format('d/m/Y H:i:s')],
                ['Tipo de exportação', $title],
            ], $this->filterRows(), [
                ['Registros exportados', number_format(count($built['rows']), 0, ',', '.') . ' registros'],
            ]);

            Excel::store(new ClosureListExport(
                $title,
                ClosureListExporter::HEADINGS,
                $built['rows'],
                $exporter->summaryRows($built['types'], $built['statuses']),
                $audit,
            ), $filePath, 'local');

            if (!Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação - ' . $title,
                message: 'Sua lista está pronta para download (' . count($built['rows']) . ' registros).',
                link: Storage::url($filePath),
                status: 'download',
            )));
        } catch (Throwable $exception) {
            Log::error('ExportClosureListJob falhou', ['error_message' => $exception->getMessage(), 'component' => $this->component, 'filters' => $this->filters]);

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
        $labels = [
            'area'             => 'Serviço da caixa',
            'prefixes'         => 'Status usuário',
            'holderUserId'     => 'Responsável (Encerramento)',
            'search'           => 'Busca',
            'type'             => 'Tipo',
            'types'            => 'Tipos',
            'status'           => 'Status',
            'statusUsers'      => 'Status usuário',
            'bulkTerms'        => 'Busca em massa',
            'bulk'             => 'Busca em massa',
            'regions'          => 'Regiões',
            'regionals'        => 'Regionais',
            'cityCodes'        => 'Municípios',
            'companyId'        => 'Empresa (informe)',
            'filterCompany'    => 'Empresa (posse atual)',
            'filterUser'       => 'Responsável',
            'assigneeId'       => 'Responsável',
            'onlyUndispatched' => 'Só não despachadas',
            'onlyPriority'     => 'Só prioritárias',
            'competence'       => 'Competência',
        ];

        $rows = [];

        foreach ($labels as $key => $label) {
            if (!array_key_exists($key, $this->filters)) {
                continue;
            }

            $value = $this->filters[$key];
            $text  = is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'Sim' : 'Não') : (string) $value);

            $rows[] = ['Filtro - ' . $label, in_array($text, ['', 'all'], true) ? '---' : $text];
        }

        return $rows;
    }
}
