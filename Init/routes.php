<?php

namespace Okay\Modules\Sviat\Monobank;

return [
    'Sviat_Monobank_callback' => [
        'slug' => 'payment/Sviat/Monobank/callback',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\CallbackController',
            'method'     => 'handle',
        ],
    ],
    'Sviat_Monobank_invoice' => [
        'slug' => 'payment/Sviat/Monobank/invoice/create/{$orderUrl}',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\InvoiceController',
            'method'     => 'createInvoice',
        ],
    ],
];
