<?php
if (!defined('TYPO3'))
{
    die ('Access denied.');
}

if (
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('powermail')
    && \Belsignum\DisposableEmail\Utility\ExtensionConfigurationUtility::isOverloadEmailValidationActive() === true
)
{
    // overload email validation rule for powermail email fields
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][In2code\Powermail\Domain\Validator\InputValidator::class] = [
        'className' => \Belsignum\DisposableEmail\Xclass\Powermail\Validator\InputValidator::class,
    ];
}

// domain rules: normalize input (domain or full email address), reject invalid and duplicate domains
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['disposable_email']
    = \Belsignum\DisposableEmail\Hooks\DomainRuleDataHandlerHook::class;
