<?php

namespace App\Jobs\Dispatchs;

use App\Exports\Dispatchs\ReturnD5Export;
use App\Models\{City, Service, User};
use App\Notifications\SystemNotification;
use App\Services\Files\ExportStorage;
use App\Services\Reclaims\ReturnD5Query;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, URL};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Excel da lista de Retorno Interno (RI) do Despacho: os mesmos retornos e filtros da tela (consulta única `ReturnD5Query`), ou só as linhas
 * selecionadas (`ids`). O arquivo vai para o solicitante.
 *
 * Params: service_uuid, search, filters, origin, notAtt, user, sort, direction, ids (opcional).
 */
class ExportReturnD5Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string, mixed> */
    public array $params;

    public string|int $userId;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 900;

    public function __construct(array $params, string|int $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
        $this->onConnection('database');
        $this->onQueue('exports');
    }

    public function handle(ReturnD5Query $query, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $filePath = null;

        if (!$user) {
            Log::warning('ExportReturnD5Job: usuário não encontrado', ['user_id' => $ownerId]);

            return;
        }

        try {
            $service = Service::where('uuid', (string) ($this->params['service_uuid'] ?? ''))->first();

            if (!$service) {
                throw new \RuntimeException('Serviço inválido para exportar os retornos internos.');
            }

            $reclaims = $query->query($service->uuid, $this->params)->get();

            $fileName = now()->format('YmdHis') . "_{$ownerId}_retorno_interno_" . str($service->service)->slug('_') . '.xlsx';
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            $export = new ReturnD5Export($reclaims, $service->service, (string) $user->name, $this->audit($user, $service, $reclaims->count()));

            if (!Excel::store($export, $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'A lista de retornos internos (' . $reclaims->count() . ' registro(s)) está pronta para download em Excel.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('ExportReturnD5Job falhou', ['user_id' => $this->userId, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ExportReturnD5Job FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Exportação falhou',
            message: 'A geração do Excel dos retornos internos falhou após novas tentativas. Tente novamente mais tarde.',
            status: 'error',
        )));
    }

    /**
     * Aba `Controle Exportacao`: quem gerou, quando e com quais filtros (em português, com nomes e não códigos).
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function audit(User $user, Service $service, int $total): array
    {
        $p       = $this->params;
        $filters = (array) ($p['filters'] ?? []);
        $list    = fn (string $key) => collect((array) ($filters[$key] ?? []))->filter()->implode(', ') ?: 'Todas';
        $cities  = collect((array) ($filters['city'] ?? []))->filter();
        $origin  = ReturnD5Query::ORIGINS[$p['origin'] ?? ''] ?? 'Todas';
        $ids     = (array) ($p['ids'] ?? []);

        return [
            ['Usuário', (string) $user->name],
            ['E-mail', (string) $user->email],
            ['Gerado em', now()->format('d/m/Y H:i:s')],
            ['Tipo', 'Retorno interno (RI) · Despacho de ' . $service->service],
            ['Retornos exportados', (string) $total],
            ['Abrangência', $ids ? 'Somente as ' . count($ids) . ' linha(s) selecionada(s) na tela' : 'Todos os retornos em aberto nos filtros da tela'],
            ['Busca', filled($p['search'] ?? null) ? (string) $p['search'] : '—'],
            ['Origem', $origin],
            ['Somente sem atribuição', !empty($p['notAtt']) ? 'Sim' : 'Não'],
            ['Usuário da atividade', filled($p['user'] ?? null) ? (string) User::whereKey($p['user'])->value('name') : 'Todos'],
            ['Rubrica', $list('rubrica')],
            ['Região', $list('region')],
            ['Regional', $list('regional')],
            ['Município', $cities->isNotEmpty() ? City::whereIn('rdMunicipio', $cities->all())->pluck('municipio')->filter()->unique()->sort()->implode(', ') : 'Todos'],
            ['Ordenação', ($p['sort'] ?? 'created_at') . ' (' . (($p['direction'] ?? 'asc') === 'desc' ? 'decrescente' : 'crescente') . ')'],
        ];
    }
}
