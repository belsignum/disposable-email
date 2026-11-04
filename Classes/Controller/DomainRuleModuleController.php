<?php

declare(strict_types=1);

namespace Belsignum\DisposableEmail\Controller;

use Belsignum\DisposableEmail\Service\DisposableEmailService;
use Belsignum\DisposableEmail\Utility\DomainNormalizationUtility;
use Belsignum\DisposableEmail\Utility\DomainRuleConfiguration;
use Belsignum\DisposableEmail\Utility\ExtensionConfigurationUtility;
use Belsignum\DisposableEmail\Utility\ListTypeConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Backend module to maintain allow (whitelist) and deny (blacklist) domain rules
 * and to look up why a domain is accepted or rejected.
 * Records are created and edited with the regular FormEngine, deleted and toggled via DataHandler.
 */
#[AsController]
final class DomainRuleModuleController
{
    private const MODULE_IDENTIFIER = 'system_disposableemail';
    private const LL = 'LLL:EXT:disposable_email/Resources/Private/Language/locallang_mod.xlf:';

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly ConnectionPool $connectionPool,
        private readonly DisposableEmailService $disposableEmailService,
        private readonly PageRenderer $pageRenderer,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle($this->getLanguageService()->sL(self::LL . 'mlang_tabs_tab'));
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/modal.js');

        $check = trim((string)($request->getParsedBody()['check'] ?? $request->getQueryParams()['check'] ?? ''));
        $returnUrl = (string)$this->uriBuilder->buildUriFromRoute(
            self::MODULE_IDENTIFIER,
            $check !== '' ? ['check' => $check] : []
        );

        $rules = $this->getRules($returnUrl);
        $moduleTemplate->assignMultiple([
            'moduleUrl' => (string)$this->uriBuilder->buildUriFromRoute(self::MODULE_IDENTIFIER),
            'canModify' => $this->getBackendUser()->check('tables_modify', DomainRuleConfiguration::TABLE_NAME),
            'allowRules' => array_values(array_filter($rules, static fn(array $rule): bool => $rule['rule_type'] === DomainRuleConfiguration::RULE_ALLOW)),
            'denyRules' => array_values(array_filter($rules, static fn(array $rule): bool => $rule['rule_type'] === DomainRuleConfiguration::RULE_DENY)),
            'newAllowUrl' => $this->buildNewRecordUrl(DomainRuleConfiguration::RULE_ALLOW, $returnUrl),
            'newDenyUrl' => $this->buildNewRecordUrl(DomainRuleConfiguration::RULE_DENY, $returnUrl),
            'check' => $check,
            'checkResult' => $check !== '' ? $this->buildCheckResult($check, $returnUrl) : null,
            'listType' => ExtensionConfigurationUtility::getListTypeFromExtensionConfiguration(),
        ]);

        return $moduleTemplate->renderResponse('DomainRule/Index');
    }

    private function getRules(string $returnUrl): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(DomainRuleConfiguration::TABLE_NAME);
        // show hidden rules too, so they can be re-enabled
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $rows = $queryBuilder
            ->select('uid', 'domain', 'rule_type', 'note', 'hidden', 'tstamp')
            ->from(DomainRuleConfiguration::TABLE_NAME)
            ->orderBy('domain')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as &$row) {
            $uid = (int)$row['uid'];
            $row['editUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [DomainRuleConfiguration::TABLE_NAME => [$uid => 'edit']],
                'returnUrl' => $returnUrl,
            ]);
            $row['toggleUrl'] = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                'data' => [DomainRuleConfiguration::TABLE_NAME => [$uid => ['hidden' => (int)$row['hidden'] === 1 ? 0 : 1]]],
                'redirect' => $returnUrl,
            ]);
            $row['deleteUrl'] = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                'cmd' => [DomainRuleConfiguration::TABLE_NAME => [$uid => ['delete' => 1]]],
                'redirect' => $returnUrl,
            ]);
        }

        return $rows;
    }

    private function buildNewRecordUrl(string $ruleType, string $returnUrl, string $domain = ''): string
    {
        $defaultValues = ['rule_type' => $ruleType];
        if ($domain !== '') {
            $defaultValues['domain'] = $domain;
        }

        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [DomainRuleConfiguration::TABLE_NAME => [0 => 'new']],
            'defVals' => [DomainRuleConfiguration::TABLE_NAME => $defaultValues],
            'returnUrl' => $returnUrl,
        ]);
    }

    /**
     * Explains the result for a domain or email address: which imported lists contain it,
     * whether a rule exists, and whether the current configuration rejects it.
     */
    private function buildCheckResult(string $input, string $returnUrl): array
    {
        $atPosition = strrpos($input, '@');
        $domain = DomainNormalizationUtility::normalizeDomain(
            $atPosition !== false ? substr($input, $atPosition + 1) : $input
        );
        if ($domain === '') {
            return ['valid' => false];
        }

        $listType = ExtensionConfigurationUtility::getListTypeFromExtensionConfiguration();
        $activeProviderTypes = ListTypeConfiguration::getProviderTypesByListType($listType);
        $providerTypes = [];
        foreach ($this->disposableEmailService->getProviderTypesForDomain($domain) as $providerType) {
            $providerTypes[] = [
                'type' => $providerType,
                'active' => in_array($providerType, $activeProviderTypes, true),
            ];
        }

        return [
            'valid' => true,
            'domain' => $domain,
            'providerTypes' => $providerTypes,
            'rule' => $this->disposableEmailService->findDomainRule($domain),
            'blocked' => $this->disposableEmailService->checkDomain($domain),
            'validationDisabled' => ListTypeConfiguration::isValidationDisabled($listType),
            'newAllowUrl' => $this->buildNewRecordUrl(DomainRuleConfiguration::RULE_ALLOW, $returnUrl, $domain),
            'newDenyUrl' => $this->buildNewRecordUrl(DomainRuleConfiguration::RULE_DENY, $returnUrl, $domain),
        ];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
