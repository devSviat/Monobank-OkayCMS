<?php

namespace Modules\Sviat\Monobank;

use Okay\Core\Response;
use Okay\Entities\OrdersEntity;
use Okay\Modules\Sviat\Monobank\Controllers\InvoiceController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Посилання на рахунок відкривається без входу і для оплаченого замовлення веде на
 * його сторінку з адресою й телефоном. Номери замовлень ідуть підряд, тож шукаємо
 * лише за секретним `url`, який і так знає тільки покупець.
 */
class InvoiceOrderLookupTest extends TestCase
{
    public function testOrderIsLookedUpBySecretUrlOnly(): void
    {
        $filters = [];
        $orders = $this->createStub(OrdersEntity::class);
        $orders->method('findOne')->willReturnCallback(function (array $filter) use (&$filters) {
            $filters[] = $filter;
            return null;
        });

        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('setStatusCode')->with(404);

        $controller = new ReflectionClass(InvoiceController::class)->newInstanceWithoutConstructor();
        $property = new \ReflectionProperty($controller, 'response');
        $property->setValue($controller, $response);

        $args = $this->stubArgs(['orderUrl' => '1001', 'ordersEntity' => $orders]);
        $controller->createInvoice(...$args);

        $this->assertSame([['url' => '1001']], $filters);
    }

    /** @return array<string, mixed> */
    private function stubArgs(array $given): array
    {
        $args = [];
        foreach (new \ReflectionMethod(InvoiceController::class, 'createInvoice')->getParameters() as $p) {
            $name = $p->getName();
            $type = $p->getType();
            $args[$name] = $given[$name] ?? ($type instanceof \ReflectionNamedType && !$type->isBuiltin()
                ? $this->createStub($type->getName())
                : null);
        }

        return $args;
    }
}
