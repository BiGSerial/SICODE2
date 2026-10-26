<?php

namespace App\Jobs\Home;

use App\Exports\Home\MyPanel\MyPanelExport;
use App\Models\{Service, User};
use App\Notifications\SystemNotification;
use App\Services\Files\ExportStorage;
use App\Services\Home\MyPanel\MyPanelAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, URL};
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Excel do "Meu painel" do usuário logado: o mesmo conteúdo da tela (abertas por tempo, produtividade por atividade, etapas,
 * série diária e mensal) mais a lista completa das concluídas do período. Só enxerga as produções do próprio usuário.
 *
 * Params: service (array de UUID, no máximo 1 é usado), dt_init/dt_end ('Y-m-d'), include_ri (bool).
 */
class PersonalProductionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;

    public string|int $userId;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 1800; // 30 min para exportacoes grandes

    public function __construct(array $params, string|int $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
        $this->onConnection('database');
        $this->onQueue('exports');
    }

    public function handle(MyPanelAnalytics $analytics, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $services = array_values(array_filter((array) ($this->params['service'] ?? [])));
        $service  = $services[0] ?? null;
        $label    = $service ? (Service::where('uuid', $service)->value('service') ?? '') : '';
        $filePath = null;

        try {
            $report = $analytics->report($ownerId, [
                'service'    => $service,
                'from'       => $this->params['dt_init'] ?? null,
                'to'         => $this->params['dt_end'] ?? null,
                'include_ri' => (bool) ($this->params['include_ri'] ?? false),
            ]);

            // Arquivo direto em exports/ (a rota assinada exports.download só serve esse formato); o dono vai no nome.
            $suffix   = $label ? '_' . Str::slug($label, '_') : '';
            $fileName = now()->format('YmdHis') . "_{$ownerId}{$suffix}_meu_painel.xlsx";
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            if (!Excel::store(new MyPanelExport($report, (string) ($user?->name ?? 'Usuário'), $label ?: null), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user?->notify(new SystemNotification(
                'Exportação concluída!',
                'Seu painel' . ($label ? ' de ' . $label : '') . ' está pronto para download em Excel.<br><br>Clique para baixar.',
                URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                4,
                []
            ));
        } catch (Throwable $e) {
            Log::error('PersonalProductionsJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            $user?->notify(new SystemNotification(
                'Erro na exportação',
                'Não foi possível gerar o Excel do seu painel' . ($label ? ' de ' . $label : '') . ' no momento. Tente novamente com um período menor ou fale com o suporte.',
                null,
                5,
                []
            ));

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('PersonalProductionsJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(
            'Exportação falhou',
            'A geração do Excel do seu painel falhou após novas tentativas. Tente novamente mais tarde.',
            null,
            5,
            []
        ));
    }
}
