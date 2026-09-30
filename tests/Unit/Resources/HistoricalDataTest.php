<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
use Groww\API\Resources\HistoricalData;
use Groww\API\Tests\TestCase;

class HistoricalDataTest extends TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $client;

    /**
     * @var HistoricalData
     */
    protected $historical;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historical = new HistoricalData($this->client);
    }

    public function testCandles()
    {
        $expected = ['candles' => []];
        $this->client->expects($this->once())
            ->method('get')
            ->with('/historical/candles', [
                'exchange' => 'NSE',
                'segment' => 'CASH',
                'groww_symbol' => 'NSE-WIPRO',
                'start_time' => '2025-09-24 10:56:00',
                'end_time' => '2025-09-24 15:21:00',
                'candle_interval' => '5minute',
            ])
            ->willReturn($this->createSuccessResponse($expected));

        $this->assertEquals($expected, $this->historical->candles(
            'NSE',
            'CASH',
            'NSE-WIPRO',
            '2025-09-24 10:56:00',
            '2025-09-24 15:21:00',
            '5minute'
        ));
    }

    public function testRejectsOldIntervalFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->historical->candles('NSE', 'CASH', 'NSE-WIPRO', '2025-01-01 09:15:00', '2025-01-01 15:30:00', '1d');
    }

    public function testExpiriesAndContracts()
    {
        $this->client->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['/historical/expiries', [
                    'exchange' => 'NSE',
                    'underlying_symbol' => 'NIFTY',
                    'year' => 2024,
                    'month' => 1,
                ]],
                ['/historical/contracts', [
                    'exchange' => 'NSE',
                    'underlying_symbol' => 'NIFTY',
                    'expiry_date' => '2025-01-25',
                ]]
            )
            ->willReturn($this->createSuccessResponse(['expiries' => []]));

        $this->historical->expiries('NSE', 'NIFTY', 2024, 1);
        $this->historical->contracts('NSE', 'NIFTY', '2025-01-25');
    }
}
