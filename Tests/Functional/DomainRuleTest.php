<?php
declare(strict_types=1);

namespace Belsignum\DisposableEmail\Tests\Functional;

use Belsignum\DisposableEmail\Service\DisposableEmailService;
use Belsignum\DisposableEmail\Utility\DomainRuleConfiguration;
use Belsignum\DisposableEmail\Utility\ListTypeConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DomainRuleTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'belsignum/disposable-email',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->getConnectionPool()->getConnectionForTable('tx_disposableemail_list')->insert('tx_disposableemail_list', [
            'domain' => 'listed.example',
            'provider_type' => ListTypeConfiguration::PROVIDER_TYPE_FREEMAIL,
        ]);
    }

    public function testAllowRuleOverridesProviderLists(): void
    {
        $this->insertRule('listed.example', DomainRuleConfiguration::RULE_ALLOW);
        $service = new DisposableEmailService($this->getConnectionPool());

        self::assertFalse($service->checkDomain('LISTED.example.', ListTypeConfiguration::LIST_TYPE_BOTH));
        self::assertSame(DomainRuleConfiguration::RULE_ALLOW, $service->findDomainRule('listed.example'));
        // the diagnosis still reports the list membership
        self::assertSame([ListTypeConfiguration::PROVIDER_TYPE_FREEMAIL], $service->getProviderTypesForDomain('listed.example'));
    }

    public function testDenyRuleBlocksUnlistedDomainForEveryActiveListType(): void
    {
        $this->insertRule('unlisted.example', DomainRuleConfiguration::RULE_DENY);
        $service = new DisposableEmailService($this->getConnectionPool());

        self::assertTrue($service->checkEmail('max@unlisted.example', ListTypeConfiguration::LIST_TYPE_DISPOSABLE));
        self::assertTrue($service->checkEmail('max@unlisted.example', ListTypeConfiguration::LIST_TYPE_CUSTOM_LISTS_ONLY));
        self::assertFalse($service->checkEmail('max@unlisted.example', ListTypeConfiguration::LIST_TYPE_DISABLE));
    }

    public function testHiddenAndDeletedRulesAreIgnored(): void
    {
        $this->insertRule('listed.example', DomainRuleConfiguration::RULE_ALLOW, ['hidden' => 1]);
        $this->insertRule('unlisted.example', DomainRuleConfiguration::RULE_DENY, ['deleted' => 1]);
        $service = new DisposableEmailService($this->getConnectionPool());

        self::assertTrue($service->checkDomain('listed.example', ListTypeConfiguration::LIST_TYPE_BOTH));
        self::assertFalse($service->checkDomain('unlisted.example', ListTypeConfiguration::LIST_TYPE_BOTH));
        self::assertNull($service->findDomainRule('listed.example'));
    }

    private function insertRule(string $domain, string $ruleType, array $additionalFields = []): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable(DomainRuleConfiguration::TABLE_NAME)
            ->insert(DomainRuleConfiguration::TABLE_NAME, array_merge([
                'pid' => 0,
                'domain' => $domain,
                'rule_type' => $ruleType,
            ], $additionalFields));
    }
}
