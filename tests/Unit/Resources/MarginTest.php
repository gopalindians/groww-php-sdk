<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
use Groww\API\Resources\Margin;
use Groww\API\Tests\TestCase;

class MarginTest extends TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $client;

    /**
     * @var Margin
     */
    protected $margin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->margin = new Margin($this->client);
    }

    public function testUser()
    {
        $expected = ['clear_cash' => 5000];
        $this->client->expects($this->once())
            ->method('get')
            ->with('/margins/detail/user')
            ->willReturn($this->createSuccessResponse($expected));

        $this->assertEquals($expected, $this->margin->user());
    }

    public function testForOrders()
    {
        $orders = [
            [
                'trading_symbol' => 'WIPRO',
                'transaction_type' => 'BUY',
                'quantity' => 1,
                'order_type' => 'LIMIT',
                'product' => 'CNC',
                'exchange' => 'NSE',
            ],
        ];
        $expected = ['total_requirement' => 2115];

        $this->client->expects($this->once())
            ->method('post')
            ->with('/margins/detail/orders', $orders, ['segment' => 'CASH'])
            ->willReturn($this->createSuccessResponse($expected));

        $this->assertEquals($expected, $this->margin->forOrders('CASH', $orders));
    }

    public function testForOrdersRejectsAssocBody()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->margin->forOrders('CASH', ['trading_symbol' => 'WIPRO']);
    }
}
