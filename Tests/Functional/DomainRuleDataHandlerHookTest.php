<?php
declare(strict_types=1);

namespace Belsignum\DisposableEmail\Tests\Functional;

use Belsignum\DisposableEmail\Utility\DomainRuleConfiguration;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DomainRuleDataHandlerHookTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'belsignum/disposable-email',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    public function testEmailAddressIsStoredAsNormalizedDomain(): void
    {
        $uid = $this->createRule(' Max@SFS.com. ');

        self::assertGreaterThan(0, $uid);
        self::assertSame('sfs.com', $this->fetchDomain($uid));
    }

    public function testInvalidDomainIsRejected(): void
    {
        self::assertSame(0, $this->createRule('not a domain'));
        self::assertSame(0, $this->countRules());
    }

    public function testDuplicateDomainIsRejectedInsteadOfSuffixed(): void
    {
        $uid = $this->createRule('sfs.com');

        self::assertSame(0, $this->createRule('someone@sfs.com', DomainRuleConfiguration::RULE_DENY));
        self::assertSame(1, $this->countRules());
        self::assertSame('sfs.com', $this->fetchDomain($uid));
    }

    public function testUpdateWithoutDomainKeepsRecord(): void
    {
        $uid = $this->createRule('sfs.com');

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([DomainRuleConfiguration::TABLE_NAME => [$uid => ['hidden' => 1]]], []);
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
    }

    private function createRule(string $domain, string $ruleType = DomainRuleConfiguration::RULE_ALLOW): int
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([
            DomainRuleConfiguration::TABLE_NAME => [
                'NEW1' => ['pid' => 0, 'domain' => $domain, 'rule_type' => $ruleType],
            ],
        ], []);
        $dataHandler->process_datamap();

        return (int)($dataHandler->substNEWwithIDs['NEW1'] ?? 0);
    }

    private function fetchDomain(int $uid): string
    {
        return (string)$this->getConnectionPool()
            ->getConnectionForTable(DomainRuleConfiguration::TABLE_NAME)
            ->select(['domain'], DomainRuleConfiguration::TABLE_NAME, ['uid' => $uid])
            ->fetchOne();
    }

    private function countRules(): int
    {
        return (int)$this->getConnectionPool()
            ->getConnectionForTable(DomainRuleConfiguration::TABLE_NAME)
            ->count('*', DomainRuleConfiguration::TABLE_NAME, ['deleted' => 0]);
    }
}
