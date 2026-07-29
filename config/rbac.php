<?php

return [
    'sections' => [],

    'modules' => [],

    'presets' => [
        'super-admin' => [
            'name' => 'Super Admin',
            'description' => 'Full system access.',
            'permissions' => ['*' => ['*']],
            'is_system' => true,
            'is_super_admin' => true,
        ],
    ],
];
