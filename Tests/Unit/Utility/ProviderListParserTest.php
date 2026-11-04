<?php
declare(strict_types=1);

namespace Belsignum\DisposableEmail\Tests\Unit\Utility;

use Belsignum\DisposableEmail\Utility\ProviderListParser;
use PHPUnit\Framework\TestCase;

final class ProviderListParserTest extends TestCase
{
    public function testParsesOneDomainPerLine(): void
    {
        self::assertSame(
            ['gmail.com', 'web.de', 'gmx.de'],
            ProviderListParser::parse("gmail.com\r\n  web.de \n\ngmx.de\n")
        );
    }

    public function testParsesJsonArrayOfStrings(): void
    {
        self::assertSame(
            ['0-mail.com', 'gmail.com'],
            ProviderListParser::parse("[\n  \"0-mail.com\",\n  \"gmail.com\",\n  42,\n  \"\"\n]\n")
        );
    }

    public function testEmptyContentReturnsEmptyList(): void
    {
        self::assertSame([], ProviderListParser::parse("  \n"));
    }

    public function testBrokenJsonThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        ProviderListParser::parse('["gmail.com",');
    }
}
