<?php

namespace Modules\Sviat\Monobank;

use Okay\Core\EntityFactory;
use Okay\Core\Notify;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\OrderHistoryEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Modules\Sviat\Monobank\Api\MonobankClient;
use Okay\Modules\Sviat\Monobank\Payment\MoneyConverter;
use Okay\Modules\Sviat\Monobank\Payment\PaymentProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Невдалу оплату і повернення Monobank теж надсилає вебхуком (errCode і
 * failureReason — див. «Помилки в процесі оплати»). Без запису в історії менеджер
 * не бачив, чому покупець не заплатив і що гроші вже повернули.
 */
class PaymentOutcomeHistoryTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private array $history = [];
    /** @var list<array<string, mixed>> */
    private array $orderUpdates = [];

    public function testFailureReasonGoesToHistory(): void
    {
        $this->processor(paid: 0)->apply([
            'reference' => 'secret', 'status' => 'failure', 'invoiceId' => 'inv1',
            'errCode' => '59', 'failureReason' => 'На картці недостатньо коштів <b>',
        ]);

        self::assertCount(1, $this->history);
        self::assertSame(7, $this->history[0]['order_id']);
        self::assertStringContainsString('На картці недостатньо коштів &lt;b&gt;', $this->history[0]['text']);
        self::assertStringContainsString('59', $this->history[0]['text']);
        self::assertSame([], $this->orderUpdates);
    }

    public function testFailureOfAnOldInvoiceIsSkippedOnceTheOrderIsPaid(): void
    {
        $this->processor(paid: 1)->apply(['reference' => 'secret', 'status' => 'failure', 'invoiceId' => 'inv0', 'errCode' => '8002']);

        self::assertSame([], $this->history);
    }

    public function testReversalIsRecordedButPaidFlagIsLeftToTheManager(): void
    {
        $this->processor(paid: 1)->apply(['reference' => 'secret', 'status' => 'reversed', 'invoiceId' => 'inv1', 'amount' => 10000, 'finalAmount' => 0]);

        self::assertCount(1, $this->history);
        self::assertStringContainsString('inv1', $this->history[0]['text']);
        self::assertSame([], $this->orderUpdates);
    }

    public function testIntermediateStatusesLeaveNoTrace(): void
    {
        foreach (['created', 'processing'] as $status) {
            $this->processor(paid: 0)->apply(['reference' => 'secret', 'status' => $status, 'invoiceId' => 'inv1']);
        }

        self::assertSame([], $this->history);
    }

    private function processor(int $paid): PaymentProcessor
    {
        $orders = $this->createStub(OrdersEntity::class);
        $orders->method('findOne')->willReturn((object) ['id' => 7, 'paid' => $paid, 'payment_method_id' => 3, 'total_price' => 100]);
        $orders->method('update')->willReturnCallback(function ($id, $data) { $this->orderUpdates[] = $data; return $id; });

        $payments = $this->createStub(PaymentsEntity::class);
        $payments->method('findOne')->willReturn((object) ['id' => 3, 'currency_id' => 1]);
        $payments->method('getPaymentSettings')->willReturn(['monobank_api_token' => '']);

        $currencies = $this->createStub(CurrenciesEntity::class);
        $currencies->method('findOne')->willReturn((object) ['id' => 1, 'code' => 'UAH', 'rate_from' => 1, 'rate_to' => 1]);

        $history = $this->createStub(OrderHistoryEntity::class);
        $history->method('add')->willReturnCallback(function ($data) { $this->history[] = $data; return 1; });

        $factory = $this->createStub(EntityFactory::class);
        $factory->method('get')->willReturnCallback(fn (string $class) => match ($class) {
            OrdersEntity::class => $orders,
            PaymentsEntity::class => $payments,
            CurrenciesEntity::class => $currencies,
            OrderHistoryEntity::class => $history,
        });

        $client = new class extends MonobankClient {
            public function __construct() {}
        };

        return new PaymentProcessor(new NullLogger(), $client, new MoneyConverter(), $factory, $this->createStub(Notify::class));
    }
}
