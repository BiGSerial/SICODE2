<?php

namespace App\Traits;

use App\Jobs\Dispatchs\ExportPartnerDispatchJob;

trait QueuesPartnerExports
{
    protected function queuePartnerExport(string $mode): void
    {
        $reflection = new \ReflectionObject($this);
        $state = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (in_array($name, ['service', 'notes', 'items', 'filteredLists'], true)) {
                continue;
            }

            $value = $this->{$name};
            if (is_scalar($value) || is_array($value) || $value === null) {
                $state[$name] = $value;
            }
        }

        ExportPartnerDispatchJob::dispatch([
            'component' => static::class,
            'service_uuid' => (string) $this->service->uuid,
            'mode' => $mode,
            'state' => $state,
            'filters' => session('filter', []),
        ], (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'icon' => 'info',
            'title' => 'Exportação iniciada!',
            'text' => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }
}
