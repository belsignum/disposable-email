<?php
declare(strict_types=1);

namespace Belsignum\DisposableEmail\Utility;

final class DomainRuleConfiguration
{
    public const TABLE_NAME = 'tx_disposableemail_domain_rule';

    /** Whitelist: the domain is never rejected, even if a provider list contains it. */
    public const RULE_ALLOW = 'allow';

    /** Blacklist: the domain is always rejected, unless validation is disabled. */
    public const RULE_DENY = 'deny';

    public static function isValidRuleType(string $ruleType): bool
    {
        return in_array($ruleType, [self::RULE_ALLOW, self::RULE_DENY], true);
    }
}
