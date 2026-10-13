<?php

namespace App\Jobs\Quality;

use App\Exports\Quality\N1DispatchExport;
use App\Models\{QualityProcess, User};
use App\Notifications\SystemNotification;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\{Queueable};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, Storage, URL};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportN1DispatchListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $userId)
    {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        try {
            $rows = QualityProcess::query()->visibleTo($user)->active()
                ->where('state', \App\Enum\QualityProcessState::AWAITING_N1_DISPATCH->value)
                ->with(['Note', 'Company'])->orderBy('state_changed_at')->get();
            $path = 'exports/qualidade_despacho_n1_' . now()->format('YmdHis') . '.xlsx';
            Storage::disk('local')->makeDirectory('exports');
            Excel::store(new N1DispatchExport($rows), $path, 'local');
            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'A fila de despacho da Qualidade está pronta para download.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $user->id, 'file' => basename($path)]),
                status: 'download',
                extras: ['toast' => true],
            )));
        } catch (Throwable $exception) {
            $this->failed($exception);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falha ao exportar a fila de despacho N1 da Qualidade.', [
            'user_id'   => $this->userId,
            'exception' => $exception,
        ]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Exportação falhou',
            message: 'Não foi possível gerar a fila de despacho da Qualidade.',
            status: 'error',
            extras: ['toast' => true],
        )));
    }
}
