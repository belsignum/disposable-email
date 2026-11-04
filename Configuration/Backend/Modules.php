<?php

declare(strict_types=1);

use Belsignum\DisposableEmail\Controller\DomainRuleModuleController;

return [
    'system_disposableemail' => [
        'parent' => 'system',
        'position' => ['after' => '*'],
        'access' => 'user',
        'path' => '/module/system/disposable-email',
        'iconIdentifier' => 'disposable-email-module',
        'labels' => 'LLL:EXT:disposable_email/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => DomainRuleModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
