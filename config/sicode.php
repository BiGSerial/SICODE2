<?php

return [
    'ruleset' => env('SICODE_RULESET', 'es'),

    'display_name' => env('SICODE_DISPLAY_NAME', env('APP_NAME', 'sicode')),

    // Cada valor lido via SicodeRules já tem um default (o comportamento padrão/histórico).
    // Só é preciso declarar aqui a regra de uma região quando ela foge desse padrão —
    // cada região roda em seu próprio banco/deployment, então não existe "es vs sp" a
    // resolver em runtime, é só documentar a exceção daquele deployment específico.
    //
    // work_report.fields: campos Sim/Não do Informe de Obra (default true = aparece e é
    // obrigatório). 'team' é texto (não Sim/Não): quando false, some e fica nulo.
    // work_report.dd_mode: required (default) | optional | hidden.
    'rules' => [
        'es' => [
            'work_report' => [
                // ES ativou o bloqueio por status de construção (default é desligado).
                'blocks_by_note_status' => true,
            ],
        ],

        'sp' => [
            'dispatch' => [
                // SP tem sistema próprio de despacho; DD não é exigida aqui.
                'survey'      => ['requires_dd' => false],
                'supervision' => ['requires_dd' => false],
            ],

            'work_report' => [
                // SP já cobre isso em sistema paralelo, então esses campos ficam fora do Informe de Obra.
                'fields' => [
                    'equipment'  => false,
                    'changes'    => false,
                    'connection' => false,
                    'meeters'    => false,
                    'team'       => false,
                ],
                'requires_files' => false,
            ],
        ],
    ],
];
