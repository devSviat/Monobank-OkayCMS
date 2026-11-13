<?php

namespace Okay\Modules\Sviat\Monobank;

use Okay\Core\Config;
use Okay\Core\EntityFactory;
use Okay\Core\Notify;
use Okay\Core\Settings;
use Okay\Core\OkayContainer\Reference\ServiceReference;
use Okay\Modules\Sviat\Monobank\Api\MonobankClient;
use Okay\Modules\Sviat\Monobank\Payment\InvoiceRequestBuilder;
use Okay\Modules\Sviat\Monobank\Payment\MoneyConverter;
use Okay\Modules\Sviat\Monobank\Payment\PaymentProcessor;
use Okay\Modules\Sviat\Monobank\Security\WebhookVerifier;
use Psr\Log\LoggerInterface;

return [
    PaymentForm::class => [
        'class'     => PaymentForm::class,
        'arguments' => [
            new ServiceReference(EntityFactory::class),
        ],
    ],
    MoneyConverter::class => [
        'class' => MoneyConverter::class,
    ],
    InvoiceRequestBuilder::class => [
        'class' => InvoiceRequestBuilder::class,
    ],
    MonobankClient::class => [
        'class'     => MonobankClient::class,
        'arguments' => [
            new ServiceReference(LoggerInterface::class),
            new ServiceReference(Config::class),
        ],
    ],
    WebhookVerifier::class => [
        'class'     => WebhookVerifier::class,
        'arguments' => [
            new ServiceReference(MonobankClient::class),
            new ServiceReference(Settings::class),
        ],
    ],
    PaymentProcessor::class => [
        'class'     => PaymentProcessor::class,
        'arguments' => [
            new ServiceReference(LoggerInterface::class),
            new ServiceReference(MonobankClient::class),
            new ServiceReference(MoneyConverter::class),
            new ServiceReference(EntityFactory::class),
            new ServiceReference(Notify::class),
        ],
    ],
];
