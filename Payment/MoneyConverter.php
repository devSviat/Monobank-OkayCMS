<?php

namespace Okay\Modules\Sviat\Monobank\Payment;

use Okay\Core\Modules\Extender\ExtenderFacade;

/**
 * Converts shop money into Monobank minor units and resolves ISO 4217 codes.
 */
class MoneyConverter
{
    private const array NUMERIC_CODES = [
        'UAH' => 980,
        'USD' => 840,
        'EUR' => 978,
    ];

    private const int DEFAULT_NUMERIC_CODE = 980;

    public function toMinorUnits(float $sum, object $currency): int
    {
        $rateFrom = (float) ($currency->rate_from ?? 1.0);
        $rateTo   = (float) ($currency->rate_to ?? 1.0) ?: 1.0;

        // PHP 8.4 прибрав «підрівнювання» всередині round(): тепер вона округлює
        // рівно те число з плаваючою комою, яке їй дали. А 1.005 * 100 у double —
        // це 100.49999999999998…, тож пряме round() віддало б 100 замість 101.
        // Проміжне округлення до 6 знаків прибирає саме цей артефакт подання,
        // не зачіпаючи реальних сум.
        $amount = $sum * 100 * $rateFrom / $rateTo;
        $result = (int) round(round($amount, 6));

        return ExtenderFacade::execute(__METHOD__, $result, func_get_args());
    }

    public function currencyNumericCode(object $currency): int
    {
        $code   = (string) ($currency->code ?? '');
        $result = self::NUMERIC_CODES[$code] ?? self::DEFAULT_NUMERIC_CODE;

        return ExtenderFacade::execute(__METHOD__, $result, func_get_args());
    }
}
