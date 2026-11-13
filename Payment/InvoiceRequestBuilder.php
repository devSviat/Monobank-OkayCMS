<?php

namespace Okay\Modules\Sviat\Monobank\Payment;

use Okay\Core\Modules\Extender\ExtenderFacade;

/**
 * Assembles the Monobank `invoice/create` payload from pre-priced line items.
 * Guarantees the basket total equals the invoice amount (Monobank requirement).
 */
class InvoiceRequestBuilder
{
    private const array PASS_THROUGH_FIELDS = ['icon', 'barcode', 'uktzed', 'header', 'footer', 'tax'];

    public function build(
        int $amountMinor,
        int $ccy,
        string $reference,
        string $destination,
        string $redirectUrl,
        string $webHookUrl,
        string $paymentType,
        array $items,
        ?array $delivery = null
    ): array {
        $basket      = [];
        $basketTotal = 0;

        foreach ($items as $item) {
            $line = $this->buildLine($item);
            $basket[]    = $line;
            $basketTotal += $line['total'];
        }

        if ($delivery !== null) {
            $deliveryMinor = (int) ($delivery['sumMinor'] ?? 0);
            $remaining     = $amountMinor - $basketTotal;
            if ($deliveryMinor > 0 && $remaining > 0) {
                $deliveryMinor = min($deliveryMinor, $remaining);
                $line = $this->buildLine([
                    'name'     => $delivery['name'] ?? 'Доставка',
                    'qty'      => 1,
                    'sumMinor' => $deliveryMinor,
                    'unit'     => $delivery['unit'] ?? 'послуга',
                    'code'     => $delivery['code'] ?? 'delivery',
                ] + array_intersect_key($delivery, array_flip(['icon', 'tax'])));
                $basket[]    = $line;
                $basketTotal += $line['total'];
            }
        }

        if (empty($basket)) {
            $line = $this->buildLine([
                'name'     => $destination,
                'qty'      => 1,
                'sumMinor' => $amountMinor,
                'unit'     => 'послуга',
                'code'     => 'order',
            ]);
            $basket[]    = $line;
            $basketTotal = $line['total'];
        }

        $merchantPaymInfo = [
            'reference'   => $reference,
            'destination' => $destination,
            'basketOrder' => $basket,
        ];

        $delta = $amountMinor - $basketTotal;
        if ($delta > 0) {
            $merchantPaymInfo['basketOrder'][] = $this->buildLine([
                'name'     => 'Коригування суми замовлення',
                'qty'      => 1,
                'sumMinor' => $delta,
                'unit'     => 'послуга',
                'code'     => 'adjustment',
            ]);
        } elseif ($delta < 0) {
            $merchantPaymInfo['discounts'] = [[
                'type'  => 'DISCOUNT',
                'mode'  => 'VALUE',
                'value' => abs($delta),
            ]];
        }

        $result = [
            'amount'           => $amountMinor,
            'ccy'              => $ccy,
            'merchantPaymInfo' => $merchantPaymInfo,
            'redirectUrl'      => $redirectUrl,
            'webHookUrl'       => $webHookUrl,
            'paymentType'      => $paymentType,
        ];

        return ExtenderFacade::execute(__METHOD__, $result, func_get_args());
    }

    private function buildLine(array $item): array
    {
        $qty      = (float) ($item['qty'] ?? 1);
        $sumMinor = max(0, (int) ($item['sumMinor'] ?? 0));

        $line = [
            'name'  => (string) ($item['name'] ?? ''),
            'qty'   => $qty == (int) $qty ? (int) $qty : $qty,
            'sum'   => $sumMinor,
            'total' => (int) round($sumMinor * $qty),
            'unit'  => (string) ($item['unit'] ?? 'шт'),
            'code'  => (string) ($item['code'] ?? ''),
        ];

        foreach (self::PASS_THROUGH_FIELDS as $field) {
            if (isset($item[$field]) && $item[$field] !== '' && $item[$field] !== []) {
                $line[$field] = $item[$field];
            }
        }

        return $line;
    }
}
