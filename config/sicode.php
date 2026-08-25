<?php

return [
    'ruleset' => env('SICODE_RULESET', 'es'),

    'display_name' => env('SICODE_DISPLAY_NAME', env('APP_NAME', 'sicode')),

    'rules' => [
        'es' => [
            'dispatch' => [
                'allows_company_stack'            => true,
                'partner_can_claim_company_stack' => true,
                'survey'                          => [
                    'requires_dd' => true,
                ],
                'supervision' => [
                    'requires_dd' => true,
                ],
            ],

            'work_report' => [
                // Campos de Sim/Não do Informe de Obra. Quando false, o campo some
                // do formulário e o valor salvo é sempre forçado para "Não".
                'fields' => [
                    'equipment'  => true,
                    'changes'    => true,
                    'damage'     => true,
                    'connection' => true,
                ],
                // required: campo visível e obrigatório | optional: visível, não obrigatório | hidden: some do formulário
                'dd_mode'               => 'required',
                'requires_files'        => true,
                'blocks_by_note_status' => false,
            ],
        ],

        'sp' => [
            'dispatch' => [
                'allows_company_stack'            => true,
                'partner_can_claim_company_stack' => true,
                'survey'                          => [
                    'requires_dd' => true,
                ],
                'supervision' => [
                    'requires_dd' => true,
                ],
            ],

            'work_report' => [
                'fields' => [
                    'equipment'  => true,
                    'changes'    => true,
                    'damage'     => true,
                    'connection' => true,
                ],
                'dd_mode'               => 'required',
                'requires_files'        => true,
                'blocks_by_note_status' => false,
            ],
        ],
    ],
];
