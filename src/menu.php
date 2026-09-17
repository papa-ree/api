<?php

/**
 * Menu definisi untuk package bale/api (Landlord Layout).
 */

return [
    'type' => 'landlord',

    'groups' => [
        [
            'key' => 'api',
            'label' => 'API',
            'icon' => 'key-round',
            'items' => [
                [
                    'label' => 'Token',
                    'url' => 'api/tokens',
                    'icon' => 'key-round',
                    'permission' => 'api-token.read',
                ],
            ],
        ],
    ],
];
