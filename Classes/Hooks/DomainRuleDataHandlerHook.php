<?php

declare(strict_types=1);

namespace Belsignum\DisposableEmail\Hooks;

use Belsignum\DisposableEmail\Utility\DomainNormalizationUtility;
use Belsignum\DisposableEmail\Utility\DomainRuleConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Stores rule domains in the normalized list format (a full email address is reduced to its domain)
 * and rejects invalid or duplicate domains instead of saving them.
 */
final class DomainRuleDataHandlerHook
{
    private const LL = 'LLL:EXT:disposable_email/Resources/Private/Language/locallang_db.xlf:tx_disposableemail_domain_rule.domain.';

    public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, DataHandler $dataHandler): void
    {
        if ($table !== DomainRuleConfiguration::TABLE_NAME || !is_array($incomingFieldArray)) {
            return;
        }

        $isNew = !MathUtility::canBeInterpretedAsInteger($id);
        if (!$isNew && !array_key_exists('domain', $incomingFieldArray)) {
            // e.g. toggling "hidden" from the module
            return;
        }

        $input = trim((string)($incomingFieldArray['domain'] ?? ''));
        $atPosition = strrpos($input, '@');
        $domain = DomainNormalizationUtility::normalizeDomain(
            $atPosition !== false ? substr($input, $atPosition + 1) : $input
        );

        if ($domain === '') {
            $this->reject($incomingFieldArray, $table, $id, $isNew, $dataHandler, 'invalid', $input);
            return;
        }

        if ($this->domainExists($domain, $isNew ? 0 : (int)$id)) {
            $this->reject($incomingFieldArray, $table, $id, $isNew, $dataHandler, 'duplicate', $domain);
            return;
        }

        $incomingFieldArray['domain'] = $domain;
    }

    private function domainExists(string $domain, int $ignoreUid): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(DomainRuleConfiguration::TABLE_NAME);
        // hidden rules count as well, one rule per domain
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return $queryBuilder
            ->select('uid')
            ->from(DomainRuleConfiguration::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('domain', $queryBuilder->createNamedParameter($domain)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($ignoreUid, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne() !== false;
    }

    /**
     * Skips the record completely and reports the reason as error message in FormEngine.
     */
    private function reject(&$incomingFieldArray, string $table, $id, bool $isNew, DataHandler $dataHandler, string $reason, string $value): void
    {
        $languageService = $GLOBALS['LANG'] ?? null;
        $message = $languageService instanceof LanguageService ? $languageService->sL(self::LL . $reason) : '';
        if ($message === '') {
            $message = $reason === 'duplicate'
                ? 'A rule for "%s" already exists. The entry was not saved.'
                : '"%s" is not a valid domain. The entry was not saved.';
        }

        $dataHandler->log(
            $table,
            $isNew ? 0 : (int)$id,
            $isNew ? SystemLogDatabaseAction::INSERT : SystemLogDatabaseAction::UPDATE,
            0,
            SystemLogErrorClassification::USER_ERROR,
            sprintf($message, $value)
        );
        $incomingFieldArray = null;
    }
}
