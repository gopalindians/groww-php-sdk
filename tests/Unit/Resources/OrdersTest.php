<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
use Groww\API\Constants;
use Groww\API\Resources\Orders;
use Groww\API\Tests\TestCase;

class OrdersTest extends TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $client;

    /**
     * @var Orders
     */
    protected $orders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orders = new Orders($this->client);
    }

    protected function validOrderData(): array
    {
        return [
            'validity' => Constants::VALIDITY_DAY,
            'exchange' => Constants::EXCHANGE_NSE,
            'transaction_type' => Constants::TRANSACTION_BUY,
            'order_type' => Constants::ORDER_TYPE_MARKET,
            'price' => 0,
            'product' => Constants::PRODUCT_CNC,
            'quantity' => 1,
            'segment' => Constants::SEGMENT_CASH,
            'trading_symbol' => 'IDEA',
            'order_reference_id' => 'Ab-654321234',
        ];
    }

    public function testCreateOrder()
    {
        $orderData = $this->validOrderData();
        $expected = ['groww_order_id' => 'GMK39038RDT490CCVRO', 'order_status' => 'OPEN'];
        $apiResponse = $this->createSuccessResponse($expected);

        $this->client->expects($this->once())
            ->method('post')
            ->with('/order/create', $orderData)
            ->willReturn($apiResponse);

        $this->assertEquals($expected, $this->orders->create($orderData));
    }

    public function testCreateOrderRequiresReferenceId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: order_reference_id');

        $data = $this->validOrderData();
        unset($data['order_reference_id']);
        $this->orders->create($data);
    }

    public function testCreateOrderRejectsSlMHyphen()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order type');

        $data = $this->validOrderData();
        $data['order_type'] = 'SL-M';
        $this->orders->create($data);
    }

    public function testCreateOrderValidationMissingFields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field');
        $this->orders->create(['exchange' => 'NSE']);
    }

    public function testCreateOrderValidationInvalidOrderType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order type');

        $data = $this->validOrderData();
        $data['order_type'] = 'INVALID_TYPE';
        $this->orders->create($data);
    }

    public function testGetOrderDetails()
    {
        $orderId = 'GMK39038RDT490CCVRO';
        $expected = ['groww_order_id' => $orderId, 'trading_symbol' => 'IDEA'];
        $apiResponse = $this->createSuccessResponse($expected);

        $this->client->expects($this->once())
            ->method('get')
            ->with("/order/detail/{$orderId}", ['segment' => 'CASH'])
            ->willReturn($apiResponse);

        $this->assertEquals($expected, $this->orders->details($orderId, 'CASH'));
    }

    public function testGetOrderDetailsEmptyOrderId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID cannot be empty');
        $this->orders->details('');
    }

    public function testCancelOrder()
    {
        $orderId = 'GMK39038RDT490CCVRO';
        $expected = ['groww_order_id' => $orderId, 'order_status' => 'CANCELLED'];
        $apiResponse = $this->createSuccessResponse($expected);

        $this->client->expects($this->once())
            ->method('post')
            ->with('/order/cancel', [
                'groww_order_id' => $orderId,
                'segment' => 'CASH',
            ])
            ->willReturn($apiResponse);

        $this->assertEquals($expected, $this->orders->cancel($orderId, 'CASH'));
    }

    public function testModifyOrder()
    {
        $orderId = 'GMK39038RDT490CCVRO';
        $modificationData = [
            'quantity' => 2,
            'price' => 100,
            'order_type' => Constants::ORDER_TYPE_SL,
            'segment' => Constants::SEGMENT_CASH,
            'trigger_price' => 95,
        ];
        $expected = ['groww_order_id' => $orderId, 'order_status' => 'OPEN'];
        $apiResponse = $this->createSuccessResponse($expected);

        $this->client->expects($this->once())
            ->method('post')
            ->with('/order/modify', array_merge(['groww_order_id' => $orderId], $modificationData))
            ->willReturn($apiResponse);

        $this->assertEquals($expected, $this->orders->modify($orderId, $modificationData));
    }

    public function testModifyOrderRequiresSegmentAndOrderType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: segment');
        $this->orders->modify('GMK39038RDT490CCVRO', ['quantity' => 2]);
    }

    public function testModifyOrderInvalidPrice()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('price must be a non-negative number');

        $this->orders->modify('GMK39038RDT490CCVRO', [
            'segment' => 'CASH',
            'order_type' => 'LIMIT',
            'price' => -100,
        ]);
    }

    public function testListOrders()
    {
        $params = ['segment' => 'CASH', 'page' => 0, 'page_size' => 100];
        $expected = ['order_list' => [['groww_order_id' => 'GMK39038RDT490CCVRO']]];
        $apiResponse = $this->createSuccessResponse($expected);

        $this->client->expects($this->once())
            ->method('get')
            ->with('/order/list', $params)
            ->willReturn($apiResponse);

        $this->assertEquals($expected, $this->orders->list($params));
    }

    public function testStatusAndTradesAndReference()
    {
        $orderId = 'GMK39038RDT490CCVRO';
        $payload = $this->createSuccessResponse(['groww_order_id' => $orderId, 'order_status' => 'OPEN']);

        $this->client->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ["/order/status/{$orderId}", ['segment' => 'CASH']],
                ['/order/status/reference/Ab-654321', ['segment' => 'CASH']],
                ["/order/trades/{$orderId}", ['page' => 0, 'segment' => 'CASH']]
            )
            ->willReturn($payload);

        $this->orders->status($orderId);
        $this->orders->statusByReference('Ab-654321');
        $this->orders->trades($orderId, 'CASH', ['page' => 0]);
    }
}
