<?php

return [
    'ruleset' => env('SICODE_RULESET', 'es'),

    'display_name' => env('SICODE_DISPLAY_NAME', env('APP_NAME', 'sicode')),

    // Cada valor lido via SicodeRules já tem um default (o comportamento padrão/histórico).
    // Overrides persistidos em system_settings têm prioridade sobre este arquivo.
    // Formato da chave: sicode.rules.{ruleset}.{caminho_da_regra}
    // Exemplos:
    // - sicode.rules.sp.analysis.environment_without_reason = true
    // - sicode.rules.sp.analysis.conclusions = {"ISR - LIBERADO":"ISR - LIBERADO","ENVIADO PARA O STATUS 3":"ENVIADO PARA O STATUS 3"}
    //
    // Só é preciso declarar aqui a regra de uma região quando ela foge desse padrão —
    // cada região roda em seu próprio banco/deployment, então não existe "es vs sp" a
    // resolver em runtime, é só documentar a exceção daquele deployment específico.
    //
    // work_report.fields: campos Sim/Não do Informe de Obra (default true = aparece e é
    // obrigatório). 'team' é texto (não Sim/Não): quando false, some e fica nulo.
    // work_report.dd_mode: required (default) | optional | hidden.
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
                'blocks_by_note_status' => true,
                'split_btzero_ep_final_flows' => false,
                'final_scope_order_prefixes'  => [
                    'network'    => ['150', '170', '190'],
                    'connection' => [],
                ],
            ],

            'analysis' => [
                'environment_without_reason' => false,
                'conclusions' => [
                    'ISR - LIBERADO' => 'ISR - LIBERADO',
                    'ENVIADO A CAMPO' => 'ENVIADO A CAMPO',
                    'ENVIADO AO DESENHO' => 'ENVIADO AO DESENHO',
                    'ENVIADO CARTA AO CLIENTE' => 'ENVIADO CARTA AO CLIENTE',
                    'ENVIADO RESPOSTA EMPRESA' => 'ENVIADO RESPOSTA EMPRESA',
                    'ENVIADO PARA O STATUS 21' => 'ENVIADO PARA O STATUS 21',
                ],
                'pre_analysis_conclusions' => [
                    'ISR - LIBERADO' => 'ISR - LIBERADO',
                    'ENVIADO A CAMPO' => 'ENVIADO A CAMPO',
                    'ENVIADO AO DESENHO/ORÇAMENTO' => 'ENVIADO AO DESENHO/ORÇAMENTO',
                    'ENVIADO CARTA AO CLIENTE' => 'ENVIADO CARTA AO CLIENTE',
                    'ENVIADO RESPOSTA EMPRESA' => 'ENVIADO RESPOSTA EMPRESA',
                    'ENVIADO PARA CONSTRUÇÃO' => 'ENVIADO PARA CONSTRUÇÃO',
                    'ARQUIVADO' => 'ARQUIVADO',
                ],
            ],
        ],

        'sp' => [
            'dispatch' => [
                // SP tem sistema próprio de despacho; DD não é exigida aqui.
                'allows_company_stack'            => true,
                'partner_can_claim_company_stack' => true,
                'survey'      => ['requires_dd' => false],
                'supervision' => ['requires_dd' => false],
            ],

            'work_report' => [
                // SP já cobre isso em sistema paralelo, então esses campos ficam fora do Informe de Obra.
                'fields' => [
                    'equipment'  => false,
                    'changes'    => false,
                    'damage'     => true,
                    'connection' => false,
                    'meeters'    => false,
                    'team'       => false,
                ],
                'dd_mode'               => 'required',
                'requires_files'        => false,
                'blocks_by_note_status' => false,
                'split_btzero_ep_final_flows' => true,
                'final_scope_order_prefixes'  => [
                    'network'    => ['150', '170', '190'],
                    'connection' => [],
                ],
            ],

            'analysis' => [
                'environment_without_reason' => true,
                'conclusions' => [
                    'ISR - LIBERADO' => 'ISR - LIBERADO',
                    'ENVIADO A CAMPO' => 'ENVIADO A CAMPO',
                    'ENVIADO AO DESENHO' => 'ENVIADO AO DESENHO',
                    'ENVIADO CARTA AO CLIENTE' => 'ENVIADO CARTA AO CLIENTE',
                    'ENVIADO RESPOSTA EMPRESA' => 'ENVIADO RESPOSTA EMPRESA',
                    'ENVIADO PARA O STATUS 3' => 'ENVIADO PARA O STATUS 3',
                    'ENVIADO PARA O STATUS 4' => 'ENVIADO PARA O STATUS 4',
                ],
                'pre_analysis_conclusions' => [
                    'ISR - LIBERADO' => 'ISR - LIBERADO',
                    'ENVIADO A CAMPO' => 'ENVIADO A CAMPO',
                    'ENVIADO AO DESENHO/ORÇAMENTO' => 'ENVIADO AO DESENHO/ORÇAMENTO',
                    'ENVIADO CARTA AO CLIENTE' => 'ENVIADO CARTA AO CLIENTE',
                    'ENVIADO RESPOSTA EMPRESA' => 'ENVIADO RESPOSTA EMPRESA',
                    'ENVIADO PARA CONSTRUÇÃO' => 'ENVIADO PARA CONSTRUÇÃO',
                    'ARQUIVADO' => 'ARQUIVADO',
                    'ENVIADO PARA O STATUS 3' => 'ENVIADO PARA O STATUS 3',
                    'ENVIADO PARA O STATUS 4' => 'ENVIADO PARA O STATUS 4',
                ],
            ],
        ],
    ],
];
