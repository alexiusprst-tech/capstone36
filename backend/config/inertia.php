<?php

return [
    'ssr' => ['enabled' => false],
    'ensure_pages_exist' => false,
    'page_paths' => [base_path('../frontend/src/views')],
    'page_extensions' => ['vue'],
    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [base_path('../frontend/src/views')],
        'page_extensions' => ['vue'],
    ],
    'history' => ['encrypt' => true],
];
