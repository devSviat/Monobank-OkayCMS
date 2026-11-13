<?php

namespace Okay\Modules\Sviat\Monobank\Controllers;

use Okay\Controllers\AbstractController;
use Okay\Core\Config;
use Okay\Core\Image;
use Okay\Core\Languages;
use Okay\Core\Response;
use Okay\Core\Router;
use Okay\Core\Settings;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\DeliveriesEntity;
use Okay\Entities\FeaturesValuesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\PurchasesEntity;
use Okay\Entities\VariantsEntity;
use Okay\Modules\Sviat\Monobank\Api\MonobankClient;
use Okay\Modules\Sviat\Monobank\Payment\InvoiceRequestBuilder;
use Okay\Modules\Sviat\Monobank\Payment\InvoiceStatus;
use Okay\Modules\Sviat\Monobank\Payment\MoneyConverter;
use Psr\Log\LoggerInterface;

class InvoiceController extends AbstractController
{
    public function createInvoice(
        $orderUrl,
        LoggerInterface $logger,
        Settings $settings,
        Config $config,
        MoneyConverter $moneyConverter,
        InvoiceRequestBuilder $invoiceRequestBuilder,
        MonobankClient $monobankClient,
        OrdersEntity $ordersEntity,
        PurchasesEntity $purchasesEntity,
        ProductsEntity $productsEntity,
        PaymentsEntity $paymentsEntity,
        FeaturesValuesEntity $featuresValuesEntity,
        CurrenciesEntity $currenciesEntity,
        ImagesEntity $imagesEntity,
        Image $image,
        DeliveriesEntity $deliveriesEntity,
        VariantsEntity $variantsEntity,
        Languages $languages
    ): void {
        // Лише секретний url: посилання відкривається без входу, а оплачене замовлення
        // веде на сторінку з даними покупця. Номери йдуть підряд, їх перебрали б усі.
        $order = $ordersEntity->findOne(['url' => (string) $orderUrl]);
        if (empty($order)) {
            $logger->warning('Monobank invoice: order not found');
            $this->response->setStatusCode(404);
            return;
        }

        $backUrl = Router::generateUrl('order', ['url' => $order->url], true);

        // Already-paid orders must not spawn a new Monobank invoice (idempotent:
        // guards against page refresh / back button / link prefetch).
        if (!empty($order->paid)) {
            Response::redirectTo($backUrl);
            return;
        }

        $paymentMethod = !empty($order->payment_method_id)
            ? $paymentsEntity->findOne(['id' => (int) $order->payment_method_id])
            : null;
        if (empty($paymentMethod)) {
            $logger->warning('Monobank invoice: payment method not found', ['order_id' => (int) $order->id]);
            Response::redirectTo($backUrl);
            return;
        }

        $paymentSettings = $paymentsEntity->getPaymentSettings($paymentMethod->id);
        $token = (string) ($paymentSettings['monobank_api_token'] ?? '');
        if ($token === '') {
            $logger->warning('Monobank invoice: empty API token', ['order_id' => (int) $order->id]);
            Response::redirectTo($backUrl);
            return;
        }

        $currency = $currenciesEntity->findOne(['id' => $paymentMethod->currency_id]);
        if (empty($currency)) {
            $logger->warning('Monobank invoice: currency not found', ['order_id' => (int) $order->id]);
            Response::redirectTo($backUrl);
            return;
        }

        $amountMinor = $moneyConverter->toMinorUnits((float) $order->total_price, $currency);
        if ($amountMinor <= 0) {
            $logger->warning('Monobank invoice: non-positive amount', ['order_id' => (int) $order->id]);
            Response::redirectTo($backUrl);
            return;
        }

        // Мова кошика: якщо в налаштуваннях модуля вибрано конкретну мову — тимчасово
        // перемикаємось на неї (як Notify для листів), і назви товарів/варіантів/доставки
        // тягнемо з каталогу цією мовою, а не зі знімка purchases мовою замовлення.
        $basketLangId    = (int) $settings->sviat__monobank__basket_lang;
        $currentLangId   = (int) $languages->getLangId();
        $useCatalogNames = $basketLangId > 0;
        if ($useCatalogNames && $basketLangId !== $currentLangId) {
            $languages->setLangId($basketLangId);
        }

        try {
            $items = $this->buildItems(
                $order, $currency, $settings, $moneyConverter,
                $purchasesEntity, $productsEntity, $featuresValuesEntity, $imagesEntity, $image,
                $variantsEntity, $useCatalogNames
            );

            $delivery = $this->buildDelivery($order, $currency, $settings, $moneyConverter, $deliveriesEntity, $image, $config);
        } finally {
            if ($useCatalogNames && $basketLangId !== $currentLangId) {
                $languages->setLangId($currentLangId);
            }
        }

        $payload = $invoiceRequestBuilder->build(
            $amountMinor,
            $moneyConverter->currencyNumericCode($currency),
            (string) $order->url,
            'Оплата замовлення #' . $order->id,
            $backUrl,
            Router::generateUrl('Sviat_Monobank_callback', [], true),
            $settings->sviat__monobank__hold_mode ? InvoiceStatus::HOLD : 'debit',
            $items,
            $delivery
        );

        $response = $monobankClient->createInvoice($token, $payload);
        if (!empty($response['pageUrl'])) {
            Response::redirectTo($response['pageUrl']);
            return;
        }

        $logger->warning('Monobank invoice: empty pageUrl, redirecting back', ['order_id' => (int) $order->id]);
        Response::redirectTo($backUrl);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildItems(
        object $order,
        object $currency,
        Settings $settings,
        MoneyConverter $moneyConverter,
        PurchasesEntity $purchasesEntity,
        ProductsEntity $productsEntity,
        FeaturesValuesEntity $featuresValuesEntity,
        ImagesEntity $imagesEntity,
        Image $image,
        VariantsEntity $variantsEntity,
        bool $useCatalogNames
    ): array {
        $items = [];

        foreach ($purchasesEntity->find(['order_id' => (int) $order->id]) as $purchase) {
            $qty = (float) ($purchase->amount ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $product = !empty($purchase->product_id)
                ? $productsEntity->findOne(['id' => (int) $purchase->product_id])
                : null;

            $item = [
                'name'     => $this->resolveName($purchase, $product, $variantsEntity, $useCatalogNames),
                'qty'      => $qty,
                'sumMinor' => $moneyConverter->toMinorUnits((float) $purchase->price, $currency),
                'unit'     => !empty($purchase->units) ? (string) $purchase->units : 'шт',
                'code'     => $this->resolveCode($purchase, $product, $settings, $featuresValuesEntity),
            ];

            if (!empty($product->main_image_id)) {
                $mainImage = $imagesEntity->findOne(['id' => $product->main_image_id]);
                if (!empty($mainImage->filename)) {
                    $item['icon'] = $image->getResizeModifier($mainImage->filename, 70, 70);
                }
            }

            foreach (['barcode' => 'barcode', 'uktzed' => 'uktzed', 'header' => 'header', 'footer' => 'footer'] as $key => $field) {
                $featureId = (int) $settings->{'sviat__monobank__' . $key};
                if ($product && $featureId) {
                    $value = trim((string) $featuresValuesEntity->col('value')->findOne([
                        'product_id' => $product->id,
                        'feature_id' => $featureId,
                    ]));
                    if ($value !== '') {
                        $item[$field] = $value;
                    }
                }
            }

            if ($tax = (int) $settings->sviat__monobank__tax) {
                $item['tax'] = [$tax];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Знімок назв у purchases зроблено мовою оформлення замовлення. Якщо ввімкнено
     * фіксовану мову кошика — беремо актуальні назви з каталогу (Entity вже читає їх
     * потрібною мовою, бо викликається після Languages::setLangId()); знімок лишається
     * запасним варіантом для видалених товарів/варіантів.
     */
    private function resolveName(
        object $purchase,
        ?object $product,
        VariantsEntity $variantsEntity,
        bool $useCatalogNames
    ): string {
        $productName = (string) ($purchase->product_name ?? '');
        $variantName = (string) ($purchase->variant_name ?? '');

        if ($useCatalogNames) {
            if (!empty($product->name)) {
                $productName = (string) $product->name;
            }
            if (!empty($purchase->variant_id)) {
                $variant = $variantsEntity->findOne(['id' => (int) $purchase->variant_id]);
                if (!empty($variant)) {
                    $variantName = (string) ($variant->name ?? '');
                }
            }
        }

        return trim($productName . ' ' . $variantName) ?: ('Товар #' . ($purchase->product_id ?? ''));
    }

    private function resolveCode(
        object $purchase,
        ?object $product,
        Settings $settings,
        FeaturesValuesEntity $featuresValuesEntity
    ): string {
        $skuFeatureId = (int) $settings->sviat__monobank__sku;

        if ($skuFeatureId && $product) {
            $code = $featuresValuesEntity->col('value')->findOne([
                'product_id' => $product->id,
                'feature_id' => $skuFeatureId,
            ]);
        } else {
            $code = !empty($purchase->sku) ? $purchase->sku : ($purchase->variant_id ?? null);
        }

        if (!$code) {
            $code = !empty($purchase->variant_id) ? $purchase->variant_id : ('purchase_' . ($purchase->id ?? uniqid()));
        }

        return (string) $code;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildDelivery(
        object $order,
        object $currency,
        Settings $settings,
        MoneyConverter $moneyConverter,
        DeliveriesEntity $deliveriesEntity,
        Image $image,
        Config $config
    ): ?array {
        if (empty($order->delivery_price) || !empty($order->separate_delivery)) {
            return null;
        }

        $delivery = !empty($order->delivery_id) ? $deliveriesEntity->findOne(['id' => (int) $order->delivery_id]) : null;

        $line = [
            'name'     => !empty($delivery->name) ? trim((string) $delivery->name) : 'Доставка',
            'sumMinor' => $moneyConverter->toMinorUnits((float) $order->delivery_price, $currency),
            'code'     => 'delivery_' . (!empty($delivery->id) ? $delivery->id : $order->id),
        ];
        if (!empty($delivery->image)) {
            $line['icon'] = $image->getResizeModifier($delivery->image, 70, 70, false, $config->resized_deliveries_dir);
        }
        if ($tax = (int) $settings->sviat__monobank__tax) {
            $line['tax'] = [$tax];
        }

        return $line;
    }
}
