<?php

return [
    'ruleset' => env('SICODE_RULESET', 'es'),

    'display_name' => env('SICODE_DISPLAY_NAME', env('APP_NAME', 'sicode')),

    'rules' => [
        'es' => [
            'dispatch' => [
                'allows_company_stack' => true,
                'partner_can_claim_company_stack' => true,
                'survey' => [
                    'requires_dd' => true,
                ],
                'supervision' => [
                    'requires_dd' => true,
                ],
            ],
        ],

        'sp' => [
            'dispatch' => [
                'allows_company_stack' => true,
                'partner_can_claim_company_stack' => true,
                'survey' => [
                    'requires_dd' => true,
                ],
                'supervision' => [
                    'requires_dd' => true,
                ],
            ],
        ],
    ],
];
