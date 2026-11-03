<?php

namespace App\Services\Accompany;

use App\Support\Ops\PrazoReal;

/**
 * Característica de cada atividade no ACOMPANHAMENTO de Serviços (a fila de trabalho do usuário), no padrão de
 * docs/PADRAO_LISTAS_OPERACIONAIS.md. A tela e o comportamento são os mesmos; o que muda por atividade mora aqui:
 *
 *  - `form` / `event` / `modal`: o formulário da atividade (componente Livewire) e o evento que o abre no modal;
 *  - `limitPause`: quantas notas o usuário pode manter pausadas na atividade (ao atingir, o aviso aparece ao iniciar outra);
 *  - `bulk`: encerramento em massa (`analysis`, `preanalysis` ou `reverse`); null = a atividade não encerra em massa;
 *  - `prazo`: de onde vem o prazo real (`PrazoReal::SOURCE_*`) e `order`: a ordem da fila (`created` ou `days_left`);
 *  - `group2` e `pauseColumn`: colunas que só algumas atividades têm.
 *
 * A chave é a pasta do serviço (`services.folder`); atividade sem perfil usa o de Análises.
 */
final class ActivityAccompanyProfile
{
    public const MODAL = 'analise_form';

    /** @var array<string, array<string, mixed>> */
    private const PROFILES = [
        'analises' => [
            'form'  => 'services.analises.forms.analise', 'event' => 'open_analise_analise', 'limitPause' => 50, 'bulk' => 'analysis',
            'prazo' => PrazoReal::SOURCE_CREATED, 'order' => 'created', 'group2' => true, 'pauseColumn' => false,
        ],
        'analises_pre' => [
            'form'  => 'services.analises_pre.forms.analise', 'event' => 'open_analise_preanalise', 'limitPause' => 50, 'bulk' => 'preanalysis',
            'prazo' => PrazoReal::SOURCE_CREATED, 'order' => 'created', 'group2' => true, 'pauseColumn' => false,
        ],
        'cadastro' => [
            'form'  => 'services.cadastro.forms.analise', 'event' => 'open_analise_cadastro', 'limitPause' => 5000, 'bulk' => null,
            'prazo' => PrazoReal::SOURCE_SAP, 'order' => 'created', 'group2' => false, 'pauseColumn' => true,
        ],
        'analises_inc' => [
            'form'  => 'services.incorporation.forms.analise', 'event' => 'open_analise_incorporation', 'limitPause' => 3, 'bulk' => null,
            'prazo' => PrazoReal::SOURCE_SAP, 'order' => 'created', 'group2' => true, 'pauseColumn' => false,
        ],
        'fluxo_reverso' => [
            'form'  => 'services.reverse.forms.analise', 'event' => 'open_analise_analise', 'limitPause' => 1000, 'bulk' => 'reverse',
            'prazo' => PrazoReal::SOURCE_SAP, 'order' => 'days_left', 'group2' => true, 'pauseColumn' => false,
        ],
    ];

    /** O Desenho usa o acompanhamento e o formulário de Análises. */
    private const ALIASES = ['desenho' => 'analises'];

    /** @return array<string, mixed> */
    public static function for(?string $folder): array
    {
        $key = self::ALIASES[$folder] ?? $folder;

        return (self::PROFILES[$key] ?? self::PROFILES['analises']) + ['key' => isset(self::PROFILES[$key]) ? $key : 'analises', 'modal' => self::MODAL];
    }

    /** @return array<int, string> pastas com perfil próprio (o teste confere que todas resolvem) */
    public static function folders(): array
    {
        return array_merge(array_keys(self::PROFILES), array_keys(self::ALIASES));
    }
}
