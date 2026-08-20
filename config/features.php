<?php

return [
    'legal_subdemands' => (bool) env('FEATURE_LEGAL_SUBDEMANDS', true),
    // Temporario para a semana de testes: remove bloqueios de informe final por status da nota.
    'suspend_work_report_note_status_blocks' => (bool) env('FEATURE_SUSPEND_WORK_REPORT_NOTE_STATUS_BLOCKS', true),
];
