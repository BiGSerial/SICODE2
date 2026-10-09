<?php

namespace App\Jobs\Dispatchs;

use App\Exports\{DispatchDesenhoMain, DispatchDesenhoStack, ExportDDExcel};
use App\Exports\Dispatchs\{DispatchPaymentMain, DispatchPaymentStack, PublicationExportControl, PublicationExportList, SupervisionExportList, SurveyExportList};
use App\Exports\Services\analisesExport;
use App\Models\{Service, User};
use App\Notifications\SystemNotification;
use App\Services\Files\ExportStorage;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Auth, Log, URL};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportPartnerDispatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $payload;

    public string $userId;

    public int $tries = 2;

    public array $backoff = [30, 120];

    public function __construct(array $payload, string $userId)
    {
        $this->onQueue('exports');
        $this->payload = $payload;
        $this->userId  = $userId;
    }

    public function handle(ExportStorage $exports): void
    {
        $user           = User::find($this->userId);
        $service        = Service::where('uuid', $this->payload['service_uuid'] ?? null)->with('Status')->firstOrFail();
        $componentClass = $this->payload['component'] ?? null;

        if (!$componentClass || !is_a($componentClass, \Livewire\Component::class, true)) {
            throw new \RuntimeException('Componente Partner inválido para exportação.');
        }

        Auth::onceUsingId($this->userId);
        session(['filter' => $this->payload['filters'] ?? []]);

        $component = app($componentClass);

        if (method_exists($component, "boot")) {
            $bootMethod = new \ReflectionMethod($component, "boot");
            $bootArgs   = [];

            foreach ($bootMethod->getParameters() as $parameter) {
                $type       = $parameter->getType();
                $bootArgs[] = $type && !$type->isBuiltin() ? app($type->getName()) : null;
            }
            $component->boot(...$bootArgs);
        }
        $segments = explode("\\", $componentClass);
        $kind     = strtolower((string) ($segments[count($segments) - 2] ?? ""));
        $mode     = $this->payload["mode"] ?? "main";
        $isStack  = $mode === "stack";
        $component->mount($service->uuid);

        foreach (($this->payload["state"] ?? []) as $name => $value) {
            if (property_exists($component, $name)) {
                $component->{$name} = $value;
            }
        }

        $selected = collect((array) ($this->payload["state"]["selected"] ?? []))
            ->map(function ($key) {
                return is_string($key) && str_contains($key, ":") ? explode(":", $key, 2)[0] : $key;
            })
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $query = (!$isStack && $kind === "payment" && method_exists($component, "baseQuery"))
            ? $component->baseQuery()
            : ($mode === "stack" && method_exists($component, "getExportsProperty")
                ? $component->getExportsProperty()
                : $component->getListsProperty());

        $selectedWorkReportIds = [];
        $selectedPartialIds    = [];

        foreach ((array) ($this->payload["state"]["selected"] ?? []) as $key) {
            if (is_string($key) && str_contains($key, ":")) {
                $parts                   = array_pad(explode(":", $key, 3), 3, null);
                $selectedWorkReportIds[] = (int) ($parts[1] ?? 0);
                $selectedPartialIds[]    = (int) ($parts[2] ?? 0);
            }
        }
        $selectedWorkReportIds = array_values(array_filter(array_unique($selectedWorkReportIds)));
        $selectedPartialIds    = array_values(array_filter(array_unique($selectedPartialIds)));

        if ($query instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $query = collect($query->items());
        }

        if ($query instanceof \Illuminate\Database\Eloquent\Builder && $selected && $kind !== "supervision") {
            $query->whereIn($isStack ? "productions.id" : "notes.id", $selected);
        }

        if (!$selected && in_array($kind, ["default", "comission"], true)
            && $query instanceof \Illuminate\Database\Eloquent\Builder) {
            $selected = $query->pluck("id")->all();
        }

        $export   = $this->resolveExport($kind, $isStack, $query, $selected, $service, $selectedWorkReportIds, $selectedPartialIds);
        $filePath = 'exports/' . now()->format('YmdHis') . '_partner_' . $kind . '_' . ($isStack ? 'stack' : 'main') . '.xlsx';
        $exports->ensureDirectory();

        try {
            Excel::store($export, $filePath, $exports->diskName());

            if (!$exports->exists($filePath)) {
                throw new \RuntimeException('Arquivo de exportação não foi criado.');
            }

            $user?->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'O export da tela de despacho está pronto para download.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), [
                    'user' => $user->id,
                    'file' => basename($filePath),
                ]),
                status: 'download',
                extras: ['toast' => true],
            )));
        } catch (Throwable $exception) {
            Log::error('ExportPartnerDispatchJob falhou', [
                'user_id' => $this->userId,
                'payload' => $this->payload,
                'error'   => $exception->getMessage(),
            ]);

            if ($exports->exists($filePath)) {
                $exports->delete($filePath);
            }

            throw $exception;
        }
    }

    private function resolveExport(string $kind, bool $stack, mixed $query, array $selected, Service $service, array $selectedWorkReportIds = [], array $selectedPartialIds = []): object
    {
        $data = $query instanceof \Illuminate\Database\Eloquent\Builder ? $query : collect($query);

        return match (true) {
            $stack && in_array($kind, ['analises', 'analisespre', 'desenho', 'reverse'], true) => new DispatchDesenhoStack($data instanceof \Illuminate\Database\Eloquent\Builder ? $data->get() : $data),
            $stack && $kind === 'payment'                                                      => new DispatchPaymentStack($data, $service->uuid),
            $stack && $kind === 'publication'                                                  => new PublicationExportControl($data),
            !$stack && in_array($kind, ['analises', 'reverse'], true)                          => new analisesExport($data, $service->uuid),
            !$stack && in_array($kind, ['analisespre', 'desenho'], true)                       => new DispatchDesenhoMain($data instanceof \Illuminate\Database\Eloquent\Builder ? $data->get() : $data, $service->uuid),
            !$stack && $kind === 'publication'                                                 => new PublicationExportList($data, $service),
            !$stack && $kind === "payment"                                                     => new DispatchPaymentMain($data, $service->uuid, $selectedWorkReportIds, $selectedPartialIds),
            !$stack && $kind === 'survey'                                                      => new SurveyExportList($data, $service->uuid, $selected),
            !$stack && $kind === "supervision"                                                 => new SupervisionExportList($data, $service->uuid, $selectedWorkReportIds, $selectedPartialIds),
            in_array($kind, ['default', 'comission'], true)                                    => (new ExportDDExcel())->exportDD($selected, $service->service),
            default                                                                            => throw new \RuntimeException("Export Partner não mapeado: {$kind}/" . ($stack ? 'stack' : 'main')),
        };
    }

    public function failed(Throwable $exception): void
    {
        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Exportação falhou',
            message: 'Não foi possível gerar o export da tela de despacho.',
            status: 'error',
            extras: ['toast' => true],
        )));
    }
}
