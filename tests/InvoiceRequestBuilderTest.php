<?php

namespace Modules\Sviat\Monobank;

use Okay\Modules\Sviat\Monobank\Payment\InvoiceRequestBuilder;
use PHPUnit\Framework\TestCase;

class InvoiceRequestBuilderTest extends TestCase
{
    private InvoiceRequestBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new InvoiceRequestBuilder();
    }

    private function build(int $amount, array $items, ?array $delivery = null): array
    {
        return $this->builder->build(
            $amount,
            980,
            'order-url',
            'Замовлення #1',
            'https://shop/redirect',
            'https://shop/callback',
            'debit',
            $items,
            $delivery
        );
    }

    private function basketTotal(array $payload): int
    {
        $total = 0;
        foreach ($payload['merchantPaymInfo']['basketOrder'] as $line) {
            $total += $line['total'];
        }
        return $total;
    }

    public function testTopLevelFields(): void
    {
        $payload = $this->build(10000, [
            ['name' => 'A', 'qty' => 1, 'sumMinor' => 10000, 'unit' => 'шт', 'code' => 'a'],
        ]);

        self::assertSame(10000, $payload['amount']);
        self::assertSame(980, $payload['ccy']);
        self::assertSame('debit', $payload['paymentType']);
        self::assertSame('https://shop/redirect', $payload['redirectUrl']);
        self::assertSame('https://shop/callback', $payload['webHookUrl']);
        self::assertSame('order-url', $payload['merchantPaymInfo']['reference']);
        self::assertSame('Замовлення #1', $payload['merchantPaymInfo']['destination']);
    }

    public function testLineTotalIsSumTimesQty(): void
    {
        $payload = $this->build(20000, [
            ['name' => 'A', 'qty' => 2, 'sumMinor' => 10000, 'unit' => 'шт', 'code' => 'a'],
        ]);

        $line = $payload['merchantPaymInfo']['basketOrder'][0];
        self::assertSame(10000, $line['sum']);
        self::assertSame(20000, $line['total']);
        self::assertSame(2, $line['qty']);
        self::assertSame(20000, $this->basketTotal($payload));
    }

    public function testOptionalLineFieldsPassThrough(): void
    {
        $payload = $this->build(10000, [
            ['name' => 'A', 'qty' => 1, 'sumMinor' => 10000, 'unit' => 'шт', 'code' => 'a',
             'icon' => 'i.png', 'barcode' => '123', 'uktzed' => '4444', 'tax' => [20]],
        ]);

        $line = $payload['merchantPaymInfo']['basketOrder'][0];
        self::assertSame('i.png', $line['icon']);
        self::assertSame('123', $line['barcode']);
        self::assertSame('4444', $line['uktzed']);
        self::assertSame([20], $line['tax']);
    }

    public function testAddsAdjustmentWhenBasketBelowAmount(): void
    {
        // Lines total 9000, amount 10000 => +1000 adjustment line
        $payload = $this->build(10000, [
            ['name' => 'A', 'qty' => 1, 'sumMinor' => 9000, 'unit' => 'шт', 'code' => 'a'],
        ]);

        self::assertSame(10000, $this->basketTotal($payload));
        $last = end($payload['merchantPaymInfo']['basketOrder']);
        self::assertSame(1000, $last['total']);
        self::assertArrayNotHasKey('discounts', $payload['merchantPaymInfo']);
    }

    public function testAddsDiscountWhenBasketAboveAmount(): void
    {
        // Lines total 11000, amount 10000 => discount VALUE 1000
        $payload = $this->build(10000, [
            ['name' => 'A', 'qty' => 1, 'sumMinor' => 11000, 'unit' => 'шт', 'code' => 'a'],
        ]);

        self::assertSame(11000, $this->basketTotal($payload));
        self::assertSame('DISCOUNT', $payload['merchantPaymInfo']['discounts'][0]['type']);
        self::assertSame('VALUE', $payload['merchantPaymInfo']['discounts'][0]['mode']);
        self::assertSame(1000, $payload['merchantPaymInfo']['discounts'][0]['value']);
    }

    public function testDeliveryLineCappedToRemaining(): void
    {
        // Basket 9000, amount 10000, delivery 5000 => delivery capped to 1000
        $payload = $this->build(10000, [
            ['name' => 'A', 'qty' => 1, 'sumMinor' => 9000, 'unit' => 'шт', 'code' => 'a'],
        ], ['name' => 'Доставка', 'sumMinor' => 5000, 'code' => 'delivery_1']);

        self::assertSame(10000, $this->basketTotal($payload));
        $delivery = $payload['merchantPaymInfo']['basketOrder'][1];
        self::assertSame('Доставка', $delivery['name']);
        self::assertSame(1000, $delivery['total']);
    }

    public function testEmptyItemsProduceFallbackLine(): void
    {
        $payload = $this->build(10000, []);

        $basket = $payload['merchantPaymInfo']['basketOrder'];
        self::assertCount(1, $basket);
        self::assertSame(10000, $basket[0]['total']);
        self::assertSame(10000, $this->basketTotal($payload));
    }
}
