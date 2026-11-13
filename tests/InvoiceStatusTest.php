<?php

namespace Modules\Sviat\Monobank;

use Okay\Modules\Sviat\Monobank\Payment\InvoiceStatus;
use PHPUnit\Framework\TestCase;

class InvoiceStatusTest extends TestCase
{
    public function testPaidStatuses(): void
    {
        self::assertTrue(InvoiceStatus::isPaid(InvoiceStatus::SUCCESS));
        self::assertTrue(InvoiceStatus::isPaid(InvoiceStatus::HOLD));
        self::assertFalse(InvoiceStatus::isPaid(InvoiceStatus::PROCESSING));
        self::assertFalse(InvoiceStatus::isPaid(InvoiceStatus::FAILURE));
    }

    public function testPaidIsCaseInsensitive(): void
    {
        self::assertTrue(InvoiceStatus::isPaid('SUCCESS'));
        self::assertTrue(InvoiceStatus::isPaid('Hold'));
    }

    public function testFinalStatuses(): void
    {
        self::assertTrue(InvoiceStatus::isFinal(InvoiceStatus::SUCCESS));
        self::assertTrue(InvoiceStatus::isFinal(InvoiceStatus::FAILURE));
        self::assertTrue(InvoiceStatus::isFinal(InvoiceStatus::REVERSED));
        self::assertTrue(InvoiceStatus::isFinal(InvoiceStatus::EXPIRED));
        self::assertFalse(InvoiceStatus::isFinal(InvoiceStatus::CREATED));
        self::assertFalse(InvoiceStatus::isFinal(InvoiceStatus::HOLD));
    }
}
