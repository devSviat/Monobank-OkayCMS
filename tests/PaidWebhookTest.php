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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Оплачений рахунок — основний шлях модуля на проді: позначка «оплачено», листи,
 * закриття замовлення (списання залишків) і запис з деталями платежу. Усе це —
 * рівно один раз, і лише коли сума й валюта збігаються із замовленням.
 */
class PaidWebhookTest extends TestCase
{
    private array $calls = [];

    /** @return iterable<string, array{array<string, mixed>, int, bool}> */
    public static function cases(): iterable
    {
        $ok = ['reference' => 'secret', 'status' => 'success', 'invoiceId' => 'inv1', 'amount' => 43000, 'ccy' => 980, 'paymentInfo' => ['maskedPan' => '4444**1902']];
        yield 'success marks the order paid' => [$ok, 0, true];
        yield 'status in another case still counts' => [['status' => 'SUCCESS'] + $ok, 0, true];
        yield 'second webhook for a paid order does nothing' => [$ok, 1, false];
        yield 'amount mismatch is refused' => [['amount' => 42999] + $ok, 0, false];
        yield 'currency mismatch is refused' => [['ccy' => 840] + $ok, 0, false];
        yield 'processing is not a payment' => [['status' => 'processing'] + $ok, 0, false];
    }

    #[DataProvider('cases')]
    public function testPaidWebhook(array $webhook, int $alreadyPaid, bool $applied): void
    {
        $this->processor($alreadyPaid)->apply($webhook);

        if ($applied) {
            self::assertSame([
                'update:paid=1,payment_details=' . $webhook['status'],
                'emailOrderUser:7',
                'emailOrderAdmin:7',
                'close:7',
                'history:7',
            ], $this->calls);
        } else {
            self::assertSame([], $this->calls);
        }
    }

    private function processor(int $paid): PaymentProcessor
    {
        $orders = $this->createStub(OrdersEntity::class);
        $orders->method('findOne')->willReturn((object) ['id' => 7, 'url' => 'secret', 'paid' => $paid, 'payment_method_id' => 3, 'total_price' => 430]);
        $orders->method('update')->willReturnCallback(function ($id, $data) {
            $this->calls[] = 'update:paid=' . $data['paid'] . ',payment_details=' . $data['payment_details'];
            return $id;
        });
        $orders->method('close')->willReturnCallback(function ($id) { $this->calls[] = 'close:' . $id; return true; });

        $payments = $this->createStub(PaymentsEntity::class);
        $payments->method('findOne')->willReturn((object) ['id' => 3, 'currency_id' => 1]);
        $payments->method('getPaymentSettings')->willReturn(['monobank_api_token' => '']);

        $currencies = $this->createStub(CurrenciesEntity::class);
        $currencies->method('findOne')->willReturn((object) ['id' => 1, 'code' => 'UAH', 'cents' => 2, 'rate_from' => 1, 'rate_to' => 1]);

        $history = $this->createStub(OrderHistoryEntity::class);
        $history->method('add')->willReturnCallback(function ($data) { $this->calls[] = 'history:' . $data['order_id']; return 1; });

        $factory = $this->createStub(EntityFactory::class);
        $factory->method('get')->willReturnCallback(fn (string $class) => match ($class) {
            OrdersEntity::class => $orders,
            PaymentsEntity::class => $payments,
            CurrenciesEntity::class => $currencies,
            OrderHistoryEntity::class => $history,
        });

        $notify = $this->createStub(Notify::class);
        $notify->method('emailOrderUser')->willReturnCallback(function ($id) { $this->calls[] = 'emailOrderUser:' . $id; return true; });
        $notify->method('emailOrderAdmin')->willReturnCallback(function ($id) { $this->calls[] = 'emailOrderAdmin:' . $id; return true; });

        $client = new class extends MonobankClient {
            public function __construct() {}
            public function getFiscalCheck(string $token, string $invoiceId): ?array { return null; }
        };

        return new PaymentProcessor(new NullLogger(), $client, new MoneyConverter(), $factory, $notify);
    }
}
