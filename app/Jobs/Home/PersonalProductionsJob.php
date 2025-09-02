<?php

namespace App\Jobs\Home;

use App\Exports\Reports\ProductionsExportList;
use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PersonalProductionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;
    public string|int $userId;

    public $tries   = 2;
    public $backoff = [30, 120];

    public function __construct(array $params, string|int $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user         = User::find($this->userId);
        $filePath     = null;
        $serviceLabel = '';

        try {
            $includeOpen = (bool)($this->params['complete'] ?? false); // dashboard: false
            $wantD5      = (bool)($this->params['d5'] ?? false);       // dashboard: false

            // Intervalo (vem como 'Y-m-d'); normaliza para timestamps completos
            $start = isset($this->params['dt_init']) ? date('Y-m-d 00:00:00', strtotime($this->params['dt_init'])) : null;
            $end   = isset($this->params['dt_end']) ? date('Y-m-d 23:59:59', strtotime($this->params['dt_end'])) : null;

            // Label do serviço quando há apenas um UUID
            if (!empty($this->params['service']) && count($this->params['service']) === 1) {
                $serviceLabel = Service::whereIn('uuid', $this->params['service'])->first()?->service ?? '';
            }

            // Query no mesmo padrão do ExportProductionJob — só que **pessoal** e com filtros da dashboard
            $query = Production::query()
                ->select([
                    'id','user_id','company_id','service_id','dispatch_by',
                    'note_id','att_by',
                    'dt_note','dispatch_at','att_at','completed_at',
                    'odi','odd','ods','eo','iproject','cad','cadastro',
                    'postes_c','postes_u','stopped','d5','confirmed','status','completed',
                    'partial','partial_at',
                ])
                ->where('rejected', false)
                // ESCOPANDO PARA O USUÁRIO DA DASHBOARD
                ->where('user_id', $this->userId)
                // dashboard: apenas concluídos?
                ->when(!$includeOpen, fn ($q) => $q->where('completed', true))
                // dashboard: exclui D5?
                ->when(!$wantD5, fn ($q) => $q->where('d5', false))
                // serviço por UUID (productions.service_id armazena UUID)
                ->when(!empty($this->params['service'] ?? []), fn ($q) => $q->whereIn('service_id', $this->params['service']))
                // intervalo de datas (completed_at)
                ->when($start || $end, function ($q) use ($start, $end, $includeOpen) {
                    $q->where(function ($w) use ($start, $end, $includeOpen) {
                        if ($start) {
                            $w->where('completed_at', '>=', $start);
                        }
                        if ($end) {
                            $w->where('completed_at', '<=', $end);
                        }
                        if ($includeOpen) {
                            // mantém compatibilidade com sua referência (OR abre OS não concluídas)
                            $w->orWhere('completed', false);
                        }
                    });
                })
                // buscas (se futuramente você quiser ligar no dashboard)
                ->when(strlen(trim($this->params['search'] ?? '')) > 0, function ($q) {
                    $search = trim($this->params['search']);
                    $wildcard = (str_contains($search, '*') || str_contains($search, '%'))
                        ? str_replace('*', '%', $search)
                        : $search;
                    $type = str_contains($wildcard, '%') ? 'like' : '=';
                    $q->where(function ($w) use ($wildcard, $type) {
                        $w->whereRelation('note', 'note', $type, $wildcard)
                          ->orWhereRelation('note.orders', 'ordem', $type, $wildcard)
                          ->orWhereRelation('note', 'material', $type, $wildcard);
                    });
                })
                ->when(!empty($this->params['multisearch'] ?? []), function ($q) {
                    $arr = array_values(array_filter($this->params['multisearch']));
                    $q->where(function ($w) use ($arr) {
                        $w->whereRelation('Note', function ($qs) use ($arr) {
                            $qs->whereIn('note', $arr)
                               ->orWhereIn('material', $arr);
                        })
                          ->orWhereRelation('Note.Orders', function ($qs) use ($arr) {
                              $qs->whereIn('ordem', $arr);
                          });
                    });
                })
                ->with([
                    'Dispatcher:id,name',
                    'Dispatcher.Employee.Contract.company:id,name',
                    'Att:id,name',
                    'Att.Employee.Contract.company:id,name',
                    'User:id,name',
                    'Company:id,name',
                    'Service:uuid,service',
                    'Note:id,note,material,group2,group5,lexp,postes,nexp,doe,rubrica,type_note',
                    'Note.RamalForm:id,note_id,created_at',
                    'Note.WorkForm:id,note_id,informed_at,rejected,created_at',
                    'Analise',
                    'Reclaim:id,category',
                ])
                ->orderBy('completed_at');

            // Estimativa (para o AfterSheet do export decidir efeitos caros)
            $rowEstimate = (clone $query)->toBase()->count();

            // Caminho/nome do arquivo (por usuário)
            $serviceSuffix = $serviceLabel ? '_' . preg_replace('/\s+/', '_', $serviceLabel) : '';
            $dir           = "exports/users/{$this->userId}";
            $filePath      = "{$dir}/" . now()->format('YmdHis') . "{$serviceSuffix}_my_productions.xlsx";
            Storage::makeDirectory($dir);

            // Exporta exatamente como na sua chamada de referência
            (new ProductionsExportList($query, $rowEstimate))->store($filePath, 'local');

            // Notificação de sucesso
            if ($user && Storage::disk('local')->exists($filePath)) {
                $serviceText = $serviceLabel ? (' para ' . $serviceLabel) : '';
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório pessoal de Produções' . $serviceText . ' está pronto para download.<br><br>Clique para baixar.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

        } catch (Throwable $e) {
            Log::error('PersonalProductionsJob falhou', [
                'user_id' => $this->userId,
                'params'  => $this->params,
                'error'   => $e->getMessage(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            if ($user) {
                $serviceText = $serviceLabel ? (' para ' . $serviceLabel) : '';
                $user->notify(new SystemNotification(
                    'Erro na exportação',
                    'Não foi possível gerar o seu relatório pessoal de Produções' . $serviceText . ' no momento. Tente novamente com um filtro menor ou fale com o suporte.',
                    null,
                    5,
                    []
                ));
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('PersonalProductionsJob FAILED', [
            'user_id' => $this->userId,
            'error'   => $exception->getMessage(),
        ]);

        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportação falhou',
                'A geração do seu relatório pessoal de Produções falhou após novas tentativas. Tente novamente mais tarde.',
                null,
                5,
                []
            ));
        }
    }
}
