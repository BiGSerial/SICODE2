<?php

return [

    'class_namespace' => 'App\\Http\\Livewire',

    'view_path' => resource_path('views/livewire'),

    'layout' => 'layouts.app',

    'asset_url' => null,

    'app_url' => null,

    'middleware_group' => 'web',

    'temporary_file_upload' => [
        'disk'       => null,
        // Limite alinhado com as regras de validação do componente FileRevisionModal (~41 MB).
        // O padrão do Livewire é 12 MB (12288), insuficiente para arquivos de projeto (DWG, PDF, etc.).
        'rules'      => ['required', 'file', 'max:51200'], // 50 MB
        'directory'  => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
    ],

    'manifest_path' => null,

    'back_button_cache' => false,

    'render_on_redirect' => false,

];
