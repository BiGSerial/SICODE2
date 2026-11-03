<?php

namespace App\Support\Menu;

use App\Services\PartnerAccess\PartnerAccessGate;
use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral do PORTAL DA PARCEIRA, no padrão de docs/PADRAO_MENU_LATERAL.md, com tema próprio (`snav--partner`: claro e sóbrio, nas cores
 * EDP) para se distinguir do menu da empresa. As permissões são as da parceira (`PartnerAccessGate`, por empresa e por usuário), não gates:
 * o `can` de grupos e itens guarda a chave da permissão (ex.: `viability.list`). O dropdown "BUSCAR" do topo lê daqui (`topItems()`).
 */
final class PartnerNav extends SidebarNav
{
    use NavBuilders;

    public const DEFAULT_MODULE = 'portal';

    public const THEME = 'partner';

    protected static function allows(array $node, ?object $user): bool
    {
        return empty($node['can']) || PartnerAccessGate::allows($user, $node['can']);
    }

    public static function modules(array $context = []): array
    {
        $user = auth()->user();

        // Engenheiro que só atua pela parceira não informa nem entrega ADS: é regra de perfil, não de permissão.
        $operatesAsPartner = !($user?->engineer && $user?->onlyparner);

        $company = (string) ($user?->Company?->name ?? '');

        return self::module('portal', 'Portal da parceira', 'ri-building-4-line', $company !== '' ? $company : 'Acompanhamento de obras', [
            self::group('inicio', 'Visão geral', 'ri-dashboard-3-line', [
                self::item('principal', 'Principal', 'partner.main.viability', 'ri-dashboard-3-line', [], [], [], ['can' => 'viability']),
            ], ['flat' => true, 'can' => 'viability']),

            self::group('viabilidade', 'Viabilidade', 'ri-radar-line', [
                self::item('viab-todo', 'À viabilizar', 'partner.todo.viability', 'ri-play-circle-line', ['forms.viability'], [['partner.count.todoviabilitycount']], [], ['can' => 'viability.list']),
                self::item('viab-tratativa', 'Em tratativa', 'partner.rejected.viability', 'ri-tools-line', [], [['partner.count.rejectedanswercount']], [], ['can' => 'viability.rejected']),
                self::item('viab-tacitas', 'Tácitas a justificar', 'partner.tacit.viability', 'ri-timer-flash-line', [], [['partner.count.tacitcount']], [], ['can' => 'viability.tacit']),
                self::item('viab-hist', 'Histórico', 'partner.hist.viability', 'ri-history-line', [], [], [], ['can' => 'viability.history']),
            ], ['can' => 'viability']),

            self::group('conclusao', 'Informes de conclusão', 'ri-file-list-3-line', array_values(array_filter([
                $operatesAsPartner ? self::item('conc-informar', 'Informar conclusão de obras', 'partner.report.workreport', 'ri-edit-box-line', [], [], [], ['can' => 'conclusion_reports.create']) : null,
                self::item('conc-rejeitados', 'Informes rejeitados', 'partner.report.rejectedWorked', 'ri-close-circle-line', ['partner.report.reinformWorkreport'], [['partner.count.returnworkforms']], [], ['can' => 'conclusion_reports.rejected']),
                $operatesAsPartner ? self::item('conc-ads', 'Entregar ADS', 'partner.report.sendAdsForm', 'ri-file-upload-line', [], [], [], ['can' => 'conclusion_reports.ads_delivery']) : null,
                self::item('conc-solicitacoes', 'Solicitações de ADS', 'partner.ads.requests', 'ri-file-add-line', [], [], [], ['can' => 'conclusion_reports.ads_requests']),
                self::item('conc-lista', 'Obras concluídas informadas', 'partner.report.workedlist', 'ri-checkbox-circle-line', [], [], [], ['can' => 'conclusion_reports.list']),
                self::item('conc-equipamentos', 'Equipamentos declarados', 'partner.declared.equipment', 'ri-device-line', [], [], [], ['can' => 'conclusion_reports.equipment']),
            ])), ['can' => 'conclusion_reports']),

            self::group('parcial', 'Informes parciais', 'ri-file-list-2-line', array_values(array_filter([
                $operatesAsPartner ? self::item('parc-informar', 'Informar parcialmente', 'partner.report.partial', 'ri-edit-box-line', [], [], [], ['can' => 'partial_reports.create']) : null,
                self::item('parc-lista', 'Obras parciais informadas', 'partner.report.partiallist', 'ri-list-check-2', [], [], [], ['can' => 'partial_reports.list']),
            ])), ['can' => 'partial_reports']),

            self::group('reclamacoes', 'Reclamações', 'ri-alarm-warning-line', [
                self::item('recl-aguardando', 'Aguardando resolução', 'protests.partner.main', 'ri-todo-line', ['protests.partner.view', 'protests.partner.view_only'], [['components.count.protest.count-protests', ['type' => 'U']]], [], ['can' => 'complaints.index']),
                self::item('recl-hist', 'Meu histórico', 'protests.partner.history', 'ri-history-line', [], [], [], ['can' => 'complaints.history']),
            ], ['can' => 'complaints']),

            self::group('d5', 'Notas D5', 'ri-file-text-line', [
                self::item('d5-aguardando', 'Aguardando resolução', 'partner.note_d5.list', 'ri-todo-line', [], [['partner.count.d5-open-count', ['returned' => false]]], [], ['can' => 'd5_notes.list']),
                self::item('d5-rejeitadas', 'Notas rejeitadas', 'partner.note_d5.returned', 'ri-error-warning-line', [], [['partner.count.d5-open-count', ['returned' => true]]], [], ['can' => 'd5_notes.returned']),
                self::item('d5-hist', 'Meu histórico', 'partner.note_d5.historic', 'ri-history-line', [], [], [], ['can' => 'd5_notes.history']),
            ], ['can' => 'd5_notes']),

            self::group('encerramento', 'Encerramento', 'ri-checkbox-circle-line', [
                self::item('enc-pendencias', 'Pendências', 'partner.closure.issues', 'ri-error-warning-line', ['partner.closure.issue.show', 'partner.closure.work.show'], [['partner.count.closure-issues-count', ['mode' => 'badge']]], [], ['can' => 'closure.issues.list']),
                self::item('enc-hist', 'Meu histórico', 'partner.closure.history', 'ri-history-line', [], [], [], ['can' => 'closure.issues.list']),
            ], ['can' => 'closure']),

            self::group('administracao', 'Administração', 'ri-settings-3-line', [
                self::item('adm-usuarios', 'Usuários', 'partner.admin.users', 'ri-user-settings-line', ['partner.admin.users.'], [], [], ['can' => 'admin_users.view']),
                self::item('adm-auditoria', 'Auditoria', 'partner.admin.audit', 'ri-history-line', [], [], [], ['can' => 'admin_audit.view']),
            ], ['can' => 'admin_panel.access']),
        ]);
    }

    /**
     * Itens do dropdown "BUSCAR" do topo da parceira, com a permissão de cada um (`can`).
     *
     * @return array<int, array<string, string>>
     */
    public static function topItems(): array
    {
        return [['label' => 'BUSCAR NOTAS', 'route' => 'partner.search.notes', 'icon' => 'ri-search-eye-line', 'can' => 'portal.search_notes']];
    }
}
