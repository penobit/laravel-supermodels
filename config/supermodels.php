<?php

return [
    'models' => [
        'user' => App\Models\User::class,
        'meta' => Penobit\SuperModels\Models\Meta::class,
        'log' => Penobit\SuperModels\Models\Log::class,
    ],
    'tables' => [
        'meta' => 'metadata',
        'log' => 'logs',
    ],
];