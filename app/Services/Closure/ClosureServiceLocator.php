<?php

namespace App\Services\Closure;

use App\Models\Service;

/**
 * O Encerramento é um Service como os demais (folder = `encerramento`); as rotas padrão de
 * despacho/serviço (`dispatch.*`, `services.*`) exigem o uuid dele. Este localizador resolve o
 * uuid para quem precisa gerar links fora das views da pasta (componentes Livewire, notificações,
 * redirects das rotas antigas `closure.*`).
 */
class ClosureServiceLocator
{
    public const FOLDER = 'encerramento';

    public static function service(): ?Service
    {
        return Service::query()->where('folder', self::FOLDER)->first();
    }

    public static function uuid(): ?string
    {
        return self::service()?->uuid;
    }

    /** URL de uma rota padrão do serviço (`dispatch.main`, `services.accompany`...); null sem o Service. */
    public static function url(string $route, array $query = []): ?string
    {
        $uuid = self::uuid();

        return $uuid ? route($route, ['service' => $uuid] + $query) : null;
    }
}
