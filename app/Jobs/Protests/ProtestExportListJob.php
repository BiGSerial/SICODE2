<?php

namespace App\Jobs\Protests;

use App\Models\Protest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProtestExportListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $params;
    public $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($params, $userId)
    {
        // dd('ProtestExportListJob initialized with params:', $params, 'and userId:', $userId);
        $this->params = $params;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = Protest::query()
            ->whereHas('medProtests', function ($query) {
                $query->where('statusSist', 'MEDA')
                    ->orWhere(function ($query) {
                        $query->where('needsConfirmation', true)
                            ->where('completed', false);
                    });
            })
            ->when($this->params['search'], function ($query) {
                $query->where('nota', 'like', "%{$this->params['search']}%");
            })
            ->when($this->params['type'], function ($query) {
                $query->where('tipoNota', $this->params['type']);
            })
            ->when($this->params['multisearch'], function ($query) {
                $query->whereIn('nota', $this->params['multisearch']);
            })->orderBy('dtConclusaoDesej');
        // ->when($this->params['filter'], function ($query) {
        //     $query->whereIn('cidade', $this->params['filter']);
        // });

        // Execute the query and get the results


        // Dispatch the export job
        $filePath = 'exports/' . now()->format('YmdHis') . '-exportProtestsList.xlsx';
        (new \App\Exports\Protests\ProtestsExportList($query))
        ->store($filePath);

        $user = User::find($this->userId);
        if ($user) {
            if (Storage::exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório de Reclamação está pronto para download.<br><br>Clique para baixar.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                $user->notify(new SystemNotification(
                    'Erro na exportação',
                    'Seu relatório de Reclamação não pôde ser gerado.',
                    null,
                    5,
                    []
                ));
            }
        }

    }
}
