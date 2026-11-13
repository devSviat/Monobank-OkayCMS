<?php

namespace Okay\Modules\Sviat\Monobank\Support;

/**
 * Sanitizes untrusted values before they reach log messages/context:
 * strips control characters and caps length so an attacker cannot inject
 * newlines (log forging) or flood log lines.
 */
final class LogSanitizer
{
    public static function text(string $value, int $maxLength = 128): string
    {
        return mb_substr((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value), 0, $maxLength);
    }
}
