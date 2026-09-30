<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
use Groww\API\Constants;
use Groww\API\Resources\SmartOrders;
use Groww\API\Tests\TestCase;

class SmartOrdersTest extends TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $client;

    /**
     * @var SmartOrders
     */
    protected $smartOrders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->smartOrders = new SmartOrders($this->client);
    }

    public function testCreateGtt()
    {
        $data = [
            'reference_id' => 'sref-unique-123',
            'smart_order_type' => Constants::SMART_ORDER_GTT,
            'segment' => Constants::SEGMENT_CASH,
            'trading_symbol' => 'TCS',
            'quantity' => 10,
        ];
        $expected = ['smart_order_id' => 'gtt_91a7f4', 'status' => 'ACTIVE'];
        $this->client->expects($this->once())
            ->method('post')
            ->with('/order-advance/create', $data)
            ->willReturn($this->createSuccessResponse($expected));

        $this->assertEquals($expected, $this->smartOrders->create($data));
    }

    public function testModify()
    {
        $expected = ['smart_order_id' => 'gtt_91a7f4'];
        $body = ['smart_order_type' => 'GTT', 'segment' => 'CASH', 'quantity' => 12];
        $this->client->expects($this->once())
            ->method('put')
            ->with('/order-advance/modify/gtt_91a7f4', $body)
            ->willReturn($this->createSuccessResponse($expected));

        $this->assertEquals($expected, $this->smartOrders->modify('gtt_91a7f4', $body));
    }

    public function testCancelAndStatusAndList()
    {
        $payload = $this->createSuccessResponse(['smart_order_id' => 'gtt_91a7f4']);

        $this->client->expects($this->once())
            ->method('post')
            ->with('/order-advance/cancel/CASH/GTT/gtt_91a7f4')
            ->willReturn($payload);
        $this->smartOrders->cancel('CASH', 'GTT', 'gtt_91a7f4');

        $this->client->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['/order-advance/status/CASH/GTT/internal/gtt_91a7f4'],
                ['/order-advance/list', ['segment' => 'FNO']]
            )
            ->willReturn($payload);

        $this->smartOrders->status('CASH', 'GTT', 'gtt_91a7f4');
        $this->smartOrders->list(['segment' => 'FNO']);
    }
}
