<?php

return [
    [
        'parent_menu' => 'global_menu_content',
        'sort' => 400,
        'text' => 'Битрикс профайлер',
        'title' => 'Битрикс профайлер',
        'url' => '_profiler_bitrix.php',
        'items_id' => 'menu_references',
        'items' => [
            [
                'text' => 'Битрикс профайлер',
                'url' => '_profiler_bitrix.php',
                'more_url' => ['_profiler_module.php'],
                'title' => 'Битрикс профайлер',
            ],
            [
                'text' => 'Очистка кэша',
                'url' => '_profilier_module_clearer.php',
                'more_url' => ['_profiler_module.php'],
                'title' => 'Битрикс профайлер',
            ],
        ],
    ],
];