<?php
declare(strict_types=1);

namespace Belsignum\DisposableEmail\Utility;

/**
 * Splits a downloaded provider list into raw domain entries.
 * Supports plain text / CSV with one domain per line and JSON arrays of strings
 * (e.g. Kikobeats/free-email-domains). Normalization happens later in the import.
 */
final class ProviderListParser
{
    /**
     * @return string[]
     */
    public static function parse(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        if ($content[0] === '[') {
            $entries = json_decode($content, true);
            if (!is_array($entries)) {
                throw new \RuntimeException('The provider list looks like JSON but could not be decoded.');
            }

            return array_values(array_filter(
                array_map('trim', array_filter($entries, 'is_string')),
                static fn(string $entry): bool => $entry !== ''
            ));
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        return array_values(array_filter(
            array_map('trim', $lines),
            static fn(string $line): bool => $line !== ''
        ));
    }
}
