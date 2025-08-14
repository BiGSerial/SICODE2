<?php

namespace App\Jobs\Protests;

use App\Exports\Protests\ProtestsExportList;
use App\Models\Protest;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Traits\AppliesQueryFilters; // Importe o trait
use App\Traits\WildcardFormmater; // Importe o trait
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProtestExportListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use AppliesQueryFilters;
    use WildcardFormmater; // Adicione os traits aqui

    public $params;
    public $userId;

    public function __construct($params, $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        try {
            $query = Protest::query()
                ->whereHas('medProtests', function ($query) {
                    $query->where('statusSist', 'MEDA')
                        ->orWhere(function ($query) {
                            $query->where('needsConfirmation', true)
                                ->where('completed', false);
                        });
                });

            // Defina o mapa de filtros para que o trait entenda como aplicar
            $filtersMap = [
                'city' => ['type' => 'in', 'column' => 'cidade'],
                'type' => ['type' => 'equals', 'column' => 'tipoNota'],
                'desired_between' => ['type' => 'between_dates', 'column' => 'dtConclusaoDesej'],
            ];

            // Aplica os filtros dinâmicos do componente Bar
            $this->applyFilters($query, $this->params['filtersState'] ?? [], $filtersMap);

            // Aplica os filtros de busca simples e busca múltipla
            if (!empty($this->params['search'])) {
                $formatted = $this->formatWithWildcard($this->params['search']);
                $query->where('nota', $formatted->type, $formatted->search);
            }
            if (!empty($this->params['multisearch'])) {
                $query->whereIn('nota', $this->params['multisearch']);
            }

            $query->orderBy('dtConclusaoDesej');

            $filePath = 'exports/' . now()->format('YmdHis') . '-exportProtestsList.xlsx';

            (new \App\Exports\Protests\ProtestsExportList($query))->store($filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Reclamação está pronto para download.<br><br>Clique para baixar.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

        } catch (Throwable $e) {
            Log::error('ProtestExportListJob falhou', [
                'user_id' => $this->userId,
                'params' => $this->params,
                'error' => $e->getMessage(),
            ]);

            if ($user) {
                $user->notify(new SystemNotification(
                    'Erro na exportação',
                    'Seu relatório de Reclamação não pôde ser gerado.',
                    null,
                    5,
                    []
                ));
            }
            // Repasse o erro para que a fila saiba que a job falhou
            throw $e;
        }
    }

    // Este método é necessário para que o trait `AppliesQueryFilters` funcione
    protected function filtersMap(): array
    {
        // Replicamos a mesma lógica do seu componente Livewire, se necessário
        return [
            'city' => ['type' => 'in', 'column' => 'cidade'],
            'type' => ['type' => 'equals', 'column' => 'tipoNota'],
            'desired_between' => ['type' => 'between_dates', 'column' => 'dtConclusaoDesej'],
        ];
    }
}
