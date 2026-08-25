<?php

namespace App\Services\PartnerAccess;

use Illuminate\Support\Collection;

class PartnerPermissionCatalog
{
    public const GROUP_VIABILITY = 'viability';
    public const GROUP_CONCLUSION_REPORTS = 'conclusion_reports';
    public const GROUP_PARTIAL_REPORTS = 'partial_reports';
    public const GROUP_COMPLAINTS = 'complaints';
    public const GROUP_D5_NOTES = 'd5_notes';
    public const GROUP_ADMIN = 'admin';

    public static function groups(): array
    {
        return [
            self::GROUP_VIABILITY => [
                'label' => 'Viabilidade',
                'items' => [
                    'viability.dashboard' => 'Principal',
                    'viability.search_notes' => 'Pesquisar notas',
                    'viability.list' => 'Listar pendentes',
                    'viability.history' => 'Histórico',
                    'viability.rejected' => 'Tratativas',
                    'viability.tacit' => 'Tácitas',
                    'viability.respond' => 'Responder',
                    'viability.return' => 'Devolver',
                    'viability.export' => 'Exportar',
                    'viability.view_files' => 'Visualizar arquivos',
                ],
            ],
            self::GROUP_CONCLUSION_REPORTS => [
                'label' => 'Informes Conclusão',
                'items' => [
                    'conclusion_reports.create' => 'Informar conclusão',
                    'conclusion_reports.rejected' => 'Informe conclusão rejeitados',
                    'conclusion_reports.reinform' => 'Reinformar conclusão',
                    'conclusion_reports.ads_delivery' => 'Entregar ADS',
                    'conclusion_reports.ads_requests' => 'Solicitações ADS',
                    'conclusion_reports.list' => 'Obras concluídas informadas',
                    'conclusion_reports.equipment' => 'Equipamentos declarados',
                    'conclusion_reports.export' => 'Exportar',
                    'conclusion_reports.respond' => 'Responder',
                ],
            ],
            self::GROUP_PARTIAL_REPORTS => [
                'label' => 'Informes Parcial',
                'items' => [
                    'partial_reports.create' => 'Informar parcialmente',
                    'partial_reports.list' => 'Obras parciais informadas',
                    'partial_reports.show' => 'Visualizar',
                    'partial_reports.export' => 'Exportar',
                ],
            ],
            self::GROUP_COMPLAINTS => [
                'label' => 'Reclamações',
                'items' => [
                    'complaints.index' => 'Aguardando resolução',
                    'complaints.show' => 'Visualizar',
                    'complaints.history' => 'Meu histórico',
                    'complaints.respond' => 'Responder',
                ],
            ],
            self::GROUP_D5_NOTES => [
                'label' => 'Notas D5',
                'items' => [
                    'd5_notes.list' => 'Aguardando resolução',
                    'd5_notes.returned' => 'Notas rejeitadas',
                    'd5_notes.history' => 'Meu histórico',
                    'd5_notes.show' => 'Visualizar',
                    'd5_notes.finish' => 'Finalizar',
                    'd5_notes.export' => 'Exportar',
                ],
            ],
            self::GROUP_ADMIN => [
                'label' => 'Administração',
                'items' => [
                    'admin_panel.access' => 'Acessar administração',
                    'admin_users.view' => 'Visualizar usuários',
                    'admin_users.create' => 'Criar usuários',
                    'admin_users.update' => 'Editar usuários',
                    'admin_users.disable' => 'Desativar usuários',
                    'admin_users.assign_branches' => 'Vincular filiais',
                    'admin_users.bulk_import' => 'Importar usuários',
                    'admin_users.template_export' => 'Exportar modelo',
                    'admin_user_exceptions.manage' => 'Gerenciar exceções',
                    'admin_audit.view' => 'Visualizar auditoria',
                ],
            ],
        ];
    }

    public static function groupFor(string $permissionKey): ?string
    {
        if (array_key_exists($permissionKey, self::groups())) {
            return $permissionKey;
        }

        foreach (self::groups() as $groupKey => $group) {
            if (array_key_exists($permissionKey, $group['items'])) {
                return $groupKey;
            }
        }

        return null;
    }

    public static function itemKeysForGroup(string $groupKey): Collection
    {
        return collect(self::groups()[$groupKey]['items'] ?? [])->keys();
    }

    public static function allPermissionKeys(): Collection
    {
        return collect(self::groups())
            ->flatMap(fn (array $group, string $groupKey) => collect([$groupKey])->merge(array_keys($group['items'])))
            ->values();
    }

    public static function filterGroupsByKeys(Collection $allowedKeys): array
    {
        return collect(self::groups())
            ->map(function (array $group, string $groupKey) use ($allowedKeys) {
                $items = collect($group['items'])
                    ->filter(fn (string $label, string $permissionKey) => $allowedKeys->contains($permissionKey))
                    ->all();

                if (!$allowedKeys->contains($groupKey) && $items === []) {
                    return null;
                }

                return [
                    'label' => $group['label'],
                    'items' => $items,
                ];
            })
            ->filter()
            ->all();
    }

    public static function routePermissionMap(): array
    {
        return [
            'partner.main.viability' => 'portal.access',
            'partner.search.notes' => 'viability.search_notes',
            'partner.search.notes.legacy' => 'viability.search_notes',
            'partner.todo.viability' => 'viability.list',
            'partner.hist.viability' => 'viability.history',
            'partner.rejected.viability' => 'viability.rejected',
            'partner.tacit.viability' => 'viability.tacit',
            'partner.report.workreport' => 'conclusion_reports.create',
            'partner.report.workedlist' => 'conclusion_reports.list',
            'partner.report.rejectedWorked' => 'conclusion_reports.rejected',
            'partner.report.reinformWorkreport' => 'conclusion_reports.reinform',
            'partner.report.sendAdsForm' => 'conclusion_reports.ads_delivery',
            'partner.ads.requests' => 'conclusion_reports.ads_requests',
            'partner.declared.equipment' => 'conclusion_reports.equipment',
            'partner.report.partial' => 'partial_reports.create',
            'partner.report.partiallist' => 'partial_reports.list',
            'partner.note_d5.list' => 'd5_notes.list',
            'partner.note_d5.returned' => 'd5_notes.returned',
            'partner.note_d5.historic' => 'd5_notes.history',
            'protests.partner.main' => 'complaints.index',
            'protests.partner.view' => 'complaints.show',
            'protests.partner.view_only' => 'complaints.show',
            'protests.partner.history' => 'complaints.history',
        ];
    }
}
