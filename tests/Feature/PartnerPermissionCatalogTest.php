<?php

use App\Services\PartnerAccess\PartnerPermissionCatalog;
use Illuminate\Support\Collection;

it('exposes the closure partner permissions as one catalog group', function () {
    $groups = PartnerPermissionCatalog::groups();

    expect($groups)->toHaveKey(PartnerPermissionCatalog::GROUP_CLOSURE)
        ->and($groups[PartnerPermissionCatalog::GROUP_CLOSURE]['label'])->toBe('Encerramento')
        ->and($groups[PartnerPermissionCatalog::GROUP_CLOSURE]['items'])->toBe([
            'closure.issues.list'       => 'Listar pendências',
            'closure.issues.respond'    => 'Responder pendências',
            'closure.issues.history'    => 'Histórico de pendências',
            'closure.issues.view_files' => 'Visualizar arquivos',
        ]);
});

it('resolves closure permissions through the catalog and maps the partner mailbox route', function () {
    $keys                    = PartnerPermissionCatalog::allPermissionKeys();
    $closureRoutePermissions = collect(PartnerPermissionCatalog::routePermissionMap())
        ->filter(fn (string $permissionKey) => str_starts_with($permissionKey, 'closure.'));

    expect(PartnerPermissionCatalog::groupFor('closure.issues.respond'))->toBe(PartnerPermissionCatalog::GROUP_CLOSURE)
        ->and(PartnerPermissionCatalog::itemKeysForGroup(PartnerPermissionCatalog::GROUP_CLOSURE)->all())->toBe([
            'closure.issues.list',
            'closure.issues.respond',
            'closure.issues.history',
            'closure.issues.view_files',
        ])
        ->and($keys)->toBeInstanceOf(Collection::class)
        ->and($keys->all())->toContain(PartnerPermissionCatalog::GROUP_CLOSURE)
        ->and($keys->all())->toContain('closure.issues.list')
        ->and($keys->all())->toContain('closure.issues.respond')
        ->and($keys->all())->toContain('closure.issues.history')
        ->and($keys->all())->toContain('closure.issues.view_files')
        ->and($closureRoutePermissions->all())->toBe([
            'partner.closure.issues' => 'closure.issues.list',
        ]);
});
