<?php

namespace Okay\Modules\Sviat\Monobank\Payment;

/**
 * Monobank invoice statuses (see acquiring webhook payload `status` field).
 */
final class InvoiceStatus
{
    public const string CREATED    = 'created';
    public const string PROCESSING = 'processing';
    public const string HOLD       = 'hold';
    public const string SUCCESS    = 'success';
    public const string FAILURE    = 'failure';
    public const string REVERSED   = 'reversed';
    public const string EXPIRED    = 'expired';

    private const array PAID  = [self::SUCCESS, self::HOLD];
    private const array FINAL = [self::SUCCESS, self::FAILURE, self::REVERSED, self::EXPIRED];

    public static function isPaid(string $status): bool
    {
        return in_array(strtolower($status), self::PAID, true);
    }

    public static function isFinal(string $status): bool
    {
        return in_array(strtolower($status), self::FINAL, true);
    }
}
