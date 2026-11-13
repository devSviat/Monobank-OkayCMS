<?php

namespace Okay\Modules\Sviat\Monobank\Controllers;

use Okay\Controllers\AbstractController;
use Okay\Modules\Sviat\Monobank\Payment\PaymentProcessor;
use Okay\Modules\Sviat\Monobank\Security\WebhookVerifier;
use Okay\Modules\Sviat\Monobank\Support\LogSanitizer;
use Psr\Log\LoggerInterface;
use Throwable;

class CallbackController extends AbstractController
{
    public function handle(
        LoggerInterface $logger,
        WebhookVerifier $webhookVerifier,
        PaymentProcessor $paymentProcessor
    ): void {
        $rawBody = (string) $this->request->post();
        $data    = json_decode($rawBody, true);

        if (!is_array($data) || empty($data['reference']) || empty($data['status'])) {
            http_response_code(200);
            echo 'OK';
            return;
        }

        $reference    = (string) $data['reference'];
        $logReference = LogSanitizer::text($reference);

        $token = $this->resolveToken($reference);
        if ($token === '') {
            $logger->warning('Monobank webhook: token not resolved', ['reference' => $logReference]);
            http_response_code(200);
            echo 'OK';
            return;
        }

        $sign = (string) ($_SERVER['HTTP_X_SIGN'] ?? '');
        if ($sign === '' || !$webhookVerifier->verify($rawBody, $sign, $token)) {
            $logger->warning('Monobank webhook: invalid signature', ['reference' => $logReference]);
            http_response_code(200);
            echo 'OK';
            return;
        }

        // Never let a processing error escape: the webhook must always return 200
        // so Monobank does not enter an immediate retry storm. The error is logged
        // for follow-up; a transient failure can be reconciled out of band.
        try {
            $paymentProcessor->apply($data);
        } catch (Throwable $e) {
            $logger->error('Monobank webhook: processing failed', [
                'reference' => $logReference,
                'error'     => $e->getMessage(),
            ]);
        }

        http_response_code(200);
        echo 'OK';
    }

    private function resolveToken(string $reference): string
    {
        $ordersEntity   = $this->entityFactory->get(\Okay\Entities\OrdersEntity::class);
        $paymentsEntity = $this->entityFactory->get(\Okay\Entities\PaymentsEntity::class);

        $order = $ordersEntity->findOne(['url' => $reference]);
        if (empty($order) || empty($order->payment_method_id)) {
            return '';
        }
        $settings = $paymentsEntity->getPaymentSettings((int) $order->payment_method_id);
        return (string) ($settings['monobank_api_token'] ?? '');
    }
}
