<?php

namespace Belsignum\DisposableEmail\Service;

use Belsignum\DisposableEmail\Utility\DomainNormalizationUtility;
use Belsignum\DisposableEmail\Utility\DomainRuleConfiguration;
use Belsignum\DisposableEmail\Utility\ExtensionConfigurationUtility;
use Belsignum\DisposableEmail\Utility\ListTypeConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class DisposableEmailService
{
    private const TABLE_NAME = 'tx_disposableemail_list';
    protected ConnectionPool $connectionPool;

    public function __construct(
        ConnectionPool $connectionPool
    )
    {
        $this->connectionPool = $connectionPool;
    }

    public function checkEmail(string $email, ?string $listType = null): bool
    {
        $atPosition = strpos($email, '@', 0);
        if ($atPosition === false) {
            return false;
        }

        $domain = trim(substr($email, $atPosition + 1));
        if ($domain === '') {
            return false;
        }

        return $this->checkDomain($domain, $listType);
    }

    public function checkDomain(string $domain, ?string $listType = null): bool
    {
        $normalizedDomain = DomainNormalizationUtility::normalizeDomain($domain);
        if ($normalizedDomain === '') {
            return false;
        }

        $effectiveListType = $listType ?? ExtensionConfigurationUtility::getListTypeFromExtensionConfiguration();
        $providerTypes = ListTypeConfiguration::getProviderTypesByListType($effectiveListType);
        if ($providerTypes === []) {
            return false;
        }

        // Editorial rules from the backend module take precedence over all provider lists.
        $ruleType = $this->findDomainRule($normalizedDomain);
        if ($ruleType === DomainRuleConfiguration::RULE_ALLOW) {
            return false;
        }
        if ($ruleType === DomainRuleConfiguration::RULE_DENY) {
            return true;
        }

        $queryBuilder = $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createQueryBuilder();
        $queryBuilder
            ->select('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'domain',
                    $queryBuilder->createNamedParameter($normalizedDomain)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->in(
                    'provider_type',
                    $queryBuilder->createNamedParameter($providerTypes, Connection::PARAM_STR_ARRAY)
                )
            )
            ->setMaxResults(1);

        return $queryBuilder->executeQuery()->fetchOne() !== false;
    }

    /**
     * Returns the active rule type (allow|deny) for the domain, or null when no rule exists.
     */
    public function findDomainRule(string $domain): ?string
    {
        $normalizedDomain = DomainNormalizationUtility::normalizeDomain($domain);
        if ($normalizedDomain === '') {
            return null;
        }

        // default restrictions exclude deleted and hidden rules
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(DomainRuleConfiguration::TABLE_NAME);
        $ruleType = $queryBuilder
            ->select('rule_type')
            ->from(DomainRuleConfiguration::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'domain',
                    $queryBuilder->createNamedParameter($normalizedDomain)
                )
            )
            ->orderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return is_string($ruleType) && DomainRuleConfiguration::isValidRuleType($ruleType) ? $ruleType : null;
    }

    /**
     * Returns all provider types (disposable|freemail|custom) whose imported lists contain the domain,
     * regardless of the configured list type. Used for diagnosis in the backend module.
     *
     * @return string[]
     */
    public function getProviderTypesForDomain(string $domain): array
    {
        $normalizedDomain = DomainNormalizationUtility::normalizeDomain($domain);
        if ($normalizedDomain === '') {
            return [];
        }

        $queryBuilder = $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->createQueryBuilder();

        return $queryBuilder
            ->select('provider_type')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'domain',
                    $queryBuilder->createNamedParameter($normalizedDomain)
                )
            )
            ->orderBy('provider_type')
            ->executeQuery()
            ->fetchFirstColumn();
    }
}
