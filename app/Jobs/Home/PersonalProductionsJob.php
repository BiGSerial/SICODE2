<?php

namespace App\Jobs\Home;

use App\Exports\Home\PersonalProductionsExport;
use App\Models\Service;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $services     = array_values(array_filter((array)($this->params['service'] ?? [])));
        $multiSearch  = array_values(array_filter((array)($this->params['multisearch'] ?? [])));

        try {
            // Intervalo (vem como 'Y-m-d'); normaliza para timestamps completos
            $start = isset($this->params['dt_init']) ? date('Y-m-d 00:00:00', strtotime($this->params['dt_init'])) : null;
            $end   = isset($this->params['dt_end']) ? date('Y-m-d 23:59:59', strtotime($this->params['dt_end'])) : null;

            // Label do serviço quando há apenas um UUID
            if (count($services) === 1) {
                $serviceLabel = Service::whereIn('uuid', $services)->first()?->service ?? '';
            }

            // Escopo fixo do dashboard pessoal:
            // user_id do usuário logado + concluídas + sem rejeitadas, com opção de incluir RI/aberto.
            $query = DB::table('productions as p')
                ->leftJoin('notes as n', 'n.id', '=', 'p.note_id')
                ->leftJoin('services as s', 's.uuid', '=', 'p.service_id')
                ->leftJoin('analises as a', 'a.production_id', '=', 'p.id')
                ->select([
                    'p.id',
                    'n.note as numero_nota',
                    's.service as servico',
                    'p.dispatch_at as data_despacho',
                    'p.att_at as data_atribuicao',
                    'p.completed_at as data_conclusao',
                    'p.stopped as tempo_parado_segundos',
                    'p.postes_u as postes_utilizados',
                    'p.status as situacao_producao',
                    'a.conclusion as conclusao',
                ])
                ->where('p.user_id', $ownerId)
                ->where('p.rejected', false)
                ->when(!$includeRi, fn ($q) => $q->where('p.d5', false))
                ->when(!$includeOpen, fn ($q) => $q->where('p.completed', true))
                // serviço por UUID (productions.service_id armazena UUID)
                ->when($services !== [], fn ($q) => $q->whereIn('p.service_id', $services))
                // intervalo estrito selecionado (concluídas em completed_at; em aberto em dispatch_at)
                ->when($start && $end, function ($q) use ($start, $end, $includeOpen) {
                    $q->where(function ($w) use ($start, $end, $includeOpen) {
                        $w->where(function ($done) use ($start, $end) {
                            $done->where('p.completed', true)
                                ->whereBetween('p.completed_at', [$start, $end]);
                        });

                        if ($includeOpen) {
                            $w->orWhere(function ($open) use ($start, $end) {
                                $open->where('p.completed', false)
                                    ->where('p.rejected', false)
                                    ->whereBetween('p.dispatch_at', [$start, $end]);
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
                        $w->where('n.note', $type, $wildcard)
                            ->orWhere('n.material', $type, $wildcard)
                            ->orWhereExists(function ($sub) use ($wildcard, $type) {
                                $sub->selectRaw('1')
                                    ->from('orders as o')
                                    ->whereColumn('o.note_id', 'n.id')
                                    ->where('o.ordem', $type, $wildcard);
                            });
                    });
                })
                ->when($multiSearch !== [], function ($q) use ($multiSearch) {
                    $q->where(function ($w) use ($multiSearch) {
                        $w->whereIn('n.note', $multiSearch)
                            ->orWhereIn('n.material', $multiSearch)
                            ->orWhereExists(function ($sub) use ($multiSearch) {
                                $sub->selectRaw('1')
                                    ->from('orders as o')
                                    ->whereColumn('o.note_id', 'n.id')
                                    ->whereIn('o.ordem', $multiSearch);
                            });
                    });
                })
                ->orderBy('p.completed_at')
                ->orderBy('p.id');

            // Sem count extra: mantém estilo de relatório habilitado.
            $rowEstimate = 0;

            // Caminho/nome do arquivo (por usuário)
            $serviceSuffix = $serviceLabel ? '_' . Str::slug($serviceLabel, '_') : '';
            $dir           = "exports/users/{$ownerId}";
            $filePath      = "{$dir}/" . now()->format('YmdHis') . "{$serviceSuffix}_historico_producoes.xlsx";
            $disk          = Storage::disk('local');
            $disk->makeDirectory($dir);

            // Exporta exatamente como na sua chamada de referência
            $stored = (new PersonalProductionsExport($query, $rowEstimate))->store($filePath, 'local');

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
