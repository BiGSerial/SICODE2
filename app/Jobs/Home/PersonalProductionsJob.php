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
    public int $timeout = 1800; // 30 min para exportacoes grandes

    public function __construct(array $params, string|int $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
        $this->onConnection('database');
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $ownerId      = (string) $this->userId;
        $user         = User::find($ownerId);
        $filePath     = null;
        $serviceLabel = '';
        $includeOpen  = (bool)($this->params['include_open'] ?? false);
        $includeRi    = (bool)($this->params['include_ri'] ?? false);

        try {
            // Intervalo (vem como 'Y-m-d'); normaliza para timestamps completos
            $start = isset($this->params['dt_init']) ? date('Y-m-d 00:00:00', strtotime($this->params['dt_init'])) : null;
            $end   = isset($this->params['dt_end']) ? date('Y-m-d 23:59:59', strtotime($this->params['dt_end'])) : null;

            // Label do serviço quando há apenas um UUID
            if (!empty($this->params['service']) && count($this->params['service']) === 1) {
                $serviceLabel = Service::whereIn('uuid', $this->params['service'])->first()?->service ?? '';
            }

            // Escopo fixo do dashboard pessoal:
            // user_id do usuário logado + concluídas + sem rejeitadas, com opção de incluir RI/aberto.
            $query = Production::query()
                ->select([
                    'id','user_id','company_id','service_id','dispatch_by',
                    'note_id','att_by',
                    'dt_note','dispatch_at','att_at','completed_at',
                    'odi','odd','ods','eo','iproject','cad','cadastro',
                    'postes_c','postes_u','stopped','d5','confirmed','status','completed',
                    'partial','partial_at','supervision_by_partner_photos',
                ])
                ->where('user_id', $ownerId)
                ->where('rejected', false)
                ->when(!$includeRi, fn ($q) => $q->where('d5', false))
                ->when(!$includeOpen, fn ($q) => $q->where('completed', true))
                // serviço por UUID (productions.service_id armazena UUID)
                ->when(!empty($this->params['service'] ?? []), fn ($q) => $q->whereIn('service_id', $this->params['service']))
                // intervalo estrito selecionado (concluídas em completed_at; em aberto em dispatch_at)
                ->when($start && $end, function ($q) use ($start, $end, $includeOpen) {
                    $q->where(function ($w) use ($start, $end, $includeOpen) {
                        $w->where(function ($done) use ($start, $end) {
                            $done->where('completed', true)
                                ->whereBetween('completed_at', [$start, $end]);
                        });

                        if ($includeOpen) {
                            $w->orWhere(function ($open) use ($start, $end) {
                                $open->where('completed', false)
                                    ->where('rejected', false)
                                    ->whereBetween('dispatch_at', [$start, $end]);
                            });
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

            // Sem count extra: mantém estilo de relatório habilitado.
            $rowEstimate = 0;

            // Caminho/nome do arquivo (por usuário)
            $serviceSuffix = $serviceLabel ? '_' . preg_replace('/\s+/', '_', $serviceLabel) : '';
            $dir           = "exports/users/{$ownerId}";
            $filePath      = "{$dir}/" . now()->format('YmdHis') . "{$serviceSuffix}_my_productions.xlsx";
            $disk          = Storage::disk('local');
            $disk->makeDirectory($dir);

            // Exporta exatamente como na sua chamada de referência
            $stored = (new ProductionsExportList($query, $rowEstimate))->store($filePath, 'local');

            // Notificação de sucesso
            if (!$stored) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            if ($user) {
                $serviceText = $serviceLabel ? (' para ' . $serviceLabel) : '';
                $user->notify(new SystemNotification(
                    'Exportação concluída!',
                    'Seu relatório pessoal de Produções' . $serviceText . ' está pronto para download.<br><br>Clique para baixar.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }


        } catch (Throwable $e) {
            Log::error('PersonalProductionsJob falhou', [
                'user_id' => $this->userId,
                'params'  => $this->params,
                'error'   => $e->getMessage(),
            ]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
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
