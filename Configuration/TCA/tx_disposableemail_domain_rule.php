<?php

use Belsignum\DisposableEmail\Utility\DomainRuleConfiguration;

$ll = 'LLL:EXT:disposable_email/Resources/Private/Language/locallang_db.xlf:tx_disposableemail_domain_rule';

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'domain',
        'label_alt' => 'rule_type',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'domain',
        'rootLevel' => 1,
        'security' => [
            // editors with table permissions may maintain the rules, records live on the root level
            'ignoreRootLevelRestriction' => true,
        ],
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_column' => 'rule_type',
        'typeicon_classes' => [
            'default' => 'status-status-permission-granted',
            DomainRuleConfiguration::RULE_ALLOW => 'status-status-permission-granted',
            DomainRuleConfiguration::RULE_DENY => 'status-status-permission-denied',
        ],
        'searchFields' => 'domain,note',
    ],
    'types' => [
        '0' => [
            'showitem' => 'domain, rule_type, note, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'domain' => [
            'label' => $ll . '.domain',
            'description' => $ll . '.domain.description',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'required' => true,
                // normalization and duplicate check: DomainRuleDataHandlerHook
                'eval' => 'trim',
            ],
        ],
        'rule_type' => [
            'label' => $ll . '.rule_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => $ll . '.rule_type.allow', 'value' => DomainRuleConfiguration::RULE_ALLOW, 'icon' => 'status-status-permission-granted'],
                    ['label' => $ll . '.rule_type.deny', 'value' => DomainRuleConfiguration::RULE_DENY, 'icon' => 'status-status-permission-denied'],
                ],
                'default' => DomainRuleConfiguration::RULE_ALLOW,
            ],
        ],
        'note' => [
            'label' => $ll . '.note',
            'description' => $ll . '.note.description',
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'cols' => 40,
            ],
        ],
    ],
];
