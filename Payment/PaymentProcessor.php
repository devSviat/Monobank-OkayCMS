<?php

namespace Okay\Modules\Sviat\Monobank\Payment;

use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Notify;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\OrderHistoryEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Modules\Sviat\Monobank\Api\MonobankClient;
use Okay\Modules\Sviat\Monobank\Support\LogSanitizer;
use Psr\Log\LoggerInterface;

/**
 * Applies a verified Monobank webhook payload to the corresponding order.
 */
class PaymentProcessor
{
    public function __construct(
        private LoggerInterface $logger,
        private MonobankClient $client,
        private MoneyConverter $moneyConverter,
        private EntityFactory $entityFactory,
        private Notify $notify
    ) {
    }

    public function apply(array $webhookData): bool
    {
        $rawStatus = (string) ($webhookData['status'] ?? '');
        $status    = strtolower($rawStatus);
        if ($status === InvoiceStatus::FAILURE || $status === InvoiceStatus::REVERSED) {
            $this->recordOutcome($status, $webhookData);
            return ExtenderFacade::execute(__METHOD__, false, func_get_args());
        }
        if (!InvoiceStatus::isPaid($status)) {
            return ExtenderFacade::execute(__METHOD__, false, func_get_args());
        }

        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $this->entityFactory->get(OrdersEntity::class);
        /** @var PaymentsEntity $paymentsEntity */
        $paymentsEntity = $this->entityFactory->get(PaymentsEntity::class);
        /** @var CurrenciesEntity $currenciesEntity */
        $currenciesEntity = $this->entityFactory->get(CurrenciesEntity::class);

        $reference = (string) ($webhookData['reference'] ?? '');
        $order = $reference !== '' ? $ordersEntity->findOne(['url' => $reference]) : null;
        if (empty($order)) {
            $this->logger->warning('Monobank webhook: order not found', ['reference' => LogSanitizer::text($reference)]);
            return ExtenderFacade::execute(__METHOD__, false, func_get_args());
        }

        $paymentMethod = !empty($order->payment_method_id)
            ? $paymentsEntity->findOne(['id' => (int) $order->payment_method_id])
            : null;
        if (empty($paymentMethod)) {
            $this->logger->warning('Monobank webhook: payment method not found', ['order_id' => (int) $order->id]);
            return ExtenderFacade::execute(__METHOD__, false, func_get_args());
        }

        $settings = $paymentsEntity->getPaymentSettings($paymentMethod->id);
        $token    = (string) ($settings['monobank_api_token'] ?? '');
        $currency = $currenciesEntity->findOne(['id' => $paymentMethod->currency_id]);

        if (empty($currency)) {
            $this->logger->warning('Monobank webhook: currency not found', ['order_id' => (int) $order->id]);
            return ExtenderFacade::execute(__METHOD__, false, func_get_args());
        }

        $expectedAmount = $this->moneyConverter->toMinorUnits((float) $order->total_price, $currency);
        if ((int) ($webhookData['amount'] ?? -1) !== $expectedAmount) {
            $this->logger->warning('Monobank webhook: amount mismatch', [
                'order_id' => (int) $order->id,
                'expected' => $expectedAmount,
                'received' => $webhookData['amount'] ?? null,
            ]);
            return ExtenderFacade::execute(__METHOD__, false, func_get_args());
        }

        if ((int) ($webhookData['ccy'] ?? -1) !== $this->moneyConverter->currencyNumericCode($currency)) {
            $this->logger->warning('Monobank webhook: currency mismatch', ['order_id' => (int) $order->id]);
            return ExtenderFacade::execute(__METHOD__, false, func_get_args());
        }

        if ((int) $order->paid === 0) {
            $ordersEntity->update((int) $order->id, ['paid' => 1, 'payment_details' => $rawStatus]);
            $this->notify->emailOrderUser((int) $order->id);
            $this->notify->emailOrderAdmin((int) $order->id);
            $ordersEntity->close((int) $order->id);
            $this->recordPaymentHistory((int) $order->id, $token, $webhookData);
        }

        return ExtenderFacade::execute(__METHOD__, true, func_get_args());
    }

    /**
     * Невдала оплата чи повернення: лише запис в історії. Знімати позначку «оплачено»
     * після повернення вирішує менеджер, бо повернення буває й частковим.
     *
     * @param array<string,mixed> $webhookData
     */
    private function recordOutcome(string $status, array $webhookData): void
    {
        $reference = (string) ($webhookData['reference'] ?? '');
        $order = $reference !== ''
            ? $this->entityFactory->get(OrdersEntity::class)->findOne(['url' => $reference])
            : null;
        if (empty($order)) {
            return;
        }

        $e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES);
        $invoiceId = $e($webhookData['invoiceId'] ?? '—');

        if ($status === InvoiceStatus::FAILURE) {
            // Вебхуки приходять не по черзі, тож відмова за старим рахунком може прийти вже після оплати новим.
            if ((int) $order->paid === 1) {
                return;
            }
            $reason = trim((string) ($webhookData['failureReason'] ?? ''));
            $lines = [
                'Monobank: оплата не пройшла',
                ($reason !== '' ? $e($reason) : 'Причину Monobank не вказав')
                    . (isset($webhookData['errCode']) ? ' (код ' . $e($webhookData['errCode']) . ')' : ''),
                'InvoiceID: ' . $invoiceId,
            ];
        } else {
            $lines = [
                'Monobank: оплату скасовано, кошти повертаються покупцю',
                'InvoiceID: ' . $invoiceId,
            ];
        }

        $this->entityFactory->get(OrderHistoryEntity::class)->add([
            'order_id'   => (int) $order->id,
            'manager_id' => null,
            'text'       => implode('<br>', $lines),
        ]);
    }

    /**
     * @param array<string,mixed> $webhookData full webhook payload (== InvoiceStatusResponse)
     */
    private function recordPaymentHistory(int $orderId, string $token, array $webhookData): void
    {
        $invoiceId = (string) ($webhookData['invoiceId'] ?? '');
        if ($invoiceId === '') {
            return;
        }

        // The webhook body already carries paymentInfo — no extra API call needed.
        $payment = is_array($webhookData['paymentInfo'] ?? null) ? $webhookData['paymentInfo'] : [];

        $lines = [
            'Monobank payment details',
            'InvoiceID: ' . $invoiceId,
            'Masked card: ' . ($payment['maskedPan'] ?? '—'),
            'RRN: ' . ($payment['rrn'] ?? '—'),
            'Approval code: ' . ($payment['approvalCode'] ?? '—'),
            'Payment system: ' . ($payment['paymentSystem'] ?? '—'),
            'Payment method: ' . ($payment['paymentMethod'] ?? '—'),
            'Fee: ' . ($payment['fee'] ?? 'Not specified'),
            'Country: ' . ($payment['country'] ?? '—'),
        ];

        // Best-effort fiscal check — no blocking wait. If not ready yet, skip the link.
        if ($token !== '') {
            $check = $this->client->getFiscalCheck($token, $invoiceId);
            if (!empty($check['id'])) {
                $lines[] = 'CheckID: ' . $check['id'];
                if (!empty($check['taxUrl'])) {
                    $lines[] = 'Чек: ' . $check['taxUrl'];
                }
            }
        }

        /** @var OrderHistoryEntity $orderHistoryEntity */
        $orderHistoryEntity = $this->entityFactory->get(OrderHistoryEntity::class);
        $orderHistoryEntity->add([
            'order_id'   => $orderId,
            'manager_id' => null,
            'text'       => implode('<br>', $lines),
        ]);
    }
}
