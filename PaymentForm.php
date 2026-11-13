<?php

namespace Okay\Modules\Sviat\Monobank;

use Okay\Core\EntityFactory;
use Okay\Core\Modules\AbstractModule;
use Okay\Core\Modules\Interfaces\PaymentFormInterface;
use Okay\Core\Router;
use Okay\Entities\OrdersEntity;

class PaymentForm extends AbstractModule implements PaymentFormInterface
{
    public function __construct(private EntityFactory $entityFactory)
    {
        parent::__construct();
    }

    public function checkoutForm($orderId): string
    {
        $orderUrl = $this->entityFactory->get(OrdersEntity::class)->col('url')->findOne(['id' => (int) $orderId]);
        if (empty($orderUrl)) {
            return '';
        }

        $this->design->assign('url', Router::generateUrl('Sviat_Monobank_invoice', ['orderUrl' => $orderUrl], true));
        return $this->design->fetch('form.tpl');
    }
}
