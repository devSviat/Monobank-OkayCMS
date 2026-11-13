<?php

namespace Modules\Sviat\Monobank;

use Okay\Modules\Sviat\Monobank\Payment\MoneyConverter;
use PHPUnit\Framework\TestCase;

class MoneyConverterTest extends TestCase
{
    private MoneyConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new MoneyConverter();
    }

    private function currency(string $code, float $from = 1.0, float $to = 1.0): object
    {
        return (object) ['code' => $code, 'rate_from' => $from, 'rate_to' => $to];
    }

    public function testConvertsHryvniaToKopiykas(): void
    {
        self::assertSame(10000, $this->converter->toMinorUnits(100.0, $this->currency('UAH')));
        self::assertSame(1599, $this->converter->toMinorUnits(15.99, $this->currency('UAH')));
    }

    public function testAppliesCurrencyRate(): void
    {
        // 10 units at rate_from=40, rate_to=1 => 400.00 => 40000 minor units
        self::assertSame(40000, $this->converter->toMinorUnits(10.0, $this->currency('USD', 40.0, 1.0)));
    }

    public function testRoundsHalfUp(): void
    {
        self::assertSame(101, $this->converter->toMinorUnits(1.005, $this->currency('UAH')));
    }

    public function testNumericCodes(): void
    {
        self::assertSame(980, $this->converter->currencyNumericCode($this->currency('UAH')));
        self::assertSame(840, $this->converter->currencyNumericCode($this->currency('USD')));
        self::assertSame(978, $this->converter->currencyNumericCode($this->currency('EUR')));
    }

    public function testUnknownCurrencyFallsBackToUah(): void
    {
        self::assertSame(980, $this->converter->currencyNumericCode($this->currency('GBP')));
    }
}
