<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
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

    /**
     * Test creating an order.
     */
    public function testCreateOrder()
    {
        $orderData = [
            'validity' => 'DAY',
            'exchange' => 'NSE',
            'transaction_type' => 'BUY',
            'order_type' => 'MARKET',
            'price' => 0,
            'product' => 'CNC',
            'quantity' => 1,
            'segment' => 'CASH',
            'trading_symbol' => 'IDEA'
        ];
        
        $expectedResponse = ['order_id' => 'GMK39038RDT490CCVRO'];
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('post')
            ->with('/order/create', $orderData)
            ->willReturn($apiResponse);
            
        $result = $this->orders->create($orderData);
        
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test order creation validation with missing fields.
     */
    public function testCreateOrderValidationMissingFields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field');
        
        $incompleteOrderData = [
            'exchange' => 'NSE',
            'transaction_type' => 'BUY',
            // Missing other required fields
        ];
        
        $this->orders->create($incompleteOrderData);
    }

    /**
     * Test order creation validation with invalid order type.
     */
    public function testCreateOrderValidationInvalidOrderType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order type');
        
        $orderData = [
            'validity' => 'DAY',
            'exchange' => 'NSE',
            'transaction_type' => 'BUY',
            'order_type' => 'INVALID_TYPE', // Invalid order type
            'price' => 0,
            'product' => 'CNC',
            'quantity' => 1,
            'segment' => 'CASH',
            'trading_symbol' => 'IDEA'
        ];
        
        $this->orders->create($orderData);
    }

    /**
     * Test getting order details.
     */
    public function testGetOrderDetails()
    {
        $orderId = 'GMK39038RDT490CCVRO';
        $segment = 'CASH';
        
        $expectedResponse = [
            'groww_order_id' => $orderId,
            'trading_symbol' => 'IDEA-EQ',
            'status' => 'COMPLETE',
            'quantity' => 1
        ];
        
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('get')
            ->with("/order/detail/{$orderId}", ['segment' => $segment])
            ->willReturn($apiResponse);
            
        $result = $this->orders->details($orderId, $segment);
        
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getting order details with empty order ID.
     */
    public function testGetOrderDetailsEmptyOrderId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID cannot be empty');
        
        $this->orders->details('');
    }

    /**
     * Test cancelling an order.
     */
    public function testCancelOrder()
    {
        $orderId = 'GMK39038RDT490CCVRO';
        $segment = 'CASH';
        
        $expectedResponse = ['status' => 'SUCCESS'];
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('post')
            ->with('/order/cancel', [
                'groww_order_id' => $orderId,
                'segment' => $segment
            ])
            ->willReturn($apiResponse);
            
        $result = $this->orders->cancel($orderId, $segment);
        
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test modifying an order.
     */
    public function testModifyOrder()
    {
        $orderId = 'GMK39038RDT490CCVRO';
        $modificationData = [
            'quantity' => 2,
            'price' => 100
        ];
        
        $expectedResponse = ['status' => 'SUCCESS'];
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('post')
            ->with('/order/modify', array_merge(['groww_order_id' => $orderId], $modificationData))
            ->willReturn($apiResponse);
            
        $result = $this->orders->modify($orderId, $modificationData);
        
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test modifying an order with invalid price.
     */
    public function testModifyOrderInvalidPrice()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('price must be a non-negative number');
        
        $orderId = 'GMK39038RDT490CCVRO';
        $modificationData = [
            'quantity' => 2,
            'price' => -100 // Negative price
        ];
        
        $this->orders->modify($orderId, $modificationData);
    }

    /**
     * Test getting all orders.
     */
    public function testGetAllOrders()
    {
        $params = ['status' => 'OPEN'];
        
        $expectedResponse = [
            'orders' => [
                [
                    'groww_order_id' => 'GMK39038RDT490CCVRO',
                    'trading_symbol' => 'IDEA-EQ',
                    'status' => 'OPEN'
                ]
            ]
        ];
        
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('get')
            ->with('/orders', $params)
            ->willReturn($apiResponse);
            
        $result = $this->orders->getAll($params);
        
        $this->assertEquals($expectedResponse, $result);
    }
} 