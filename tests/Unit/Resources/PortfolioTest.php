<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
use Groww\API\Resources\Portfolio;
use Groww\API\Tests\TestCase;

class PortfolioTest extends TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $client;

    /**
     * @var Portfolio
     */
    protected $portfolio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->portfolio = new Portfolio($this->client);
    }

    public function testGetHoldings()
    {
        $expected = [
            'holdings' => [
                [
                    'isin' => 'INE545U01014',
                    'trading_symbol' => 'RELIANCE',
                    'quantity' => 10,
                ],
            ],
        ];
        $apiResponse = $this->createSuccessResponse($expected);

        $this->client->expects($this->once())
            ->method('get')
            ->with('/holdings/user')
            ->willReturn($apiResponse);

        $this->assertEquals($expected, $this->portfolio->holdings());
    }

    public function testGetPositions()
    {
        $expected = ['positions' => [['trading_symbol' => 'RELIANCE', 'quantity' => 15]]];
        $apiResponse = $this->createSuccessResponse($expected);

        $this->client->expects($this->once())
            ->method('get')
            ->with('/positions/user', ['segment' => 'CASH'])
            ->willReturn($apiResponse);

        $this->assertEquals($expected, $this->portfolio->positions(['segment' => 'CASH']));
    }

    public function testPositionForSymbol()
    {
        $expected = ['positions' => [['trading_symbol' => 'RELIANCE']]];
        $apiResponse = $this->createSuccessResponse($expected);

        $this->client->expects($this->once())
            ->method('get')
            ->with('/positions/trading-symbol', [
                'trading_symbol' => 'RELIANCE',
                'segment' => 'CASH',
            ])
            ->willReturn($apiResponse);

        $this->assertEquals($expected, $this->portfolio->positionForSymbol('RELIANCE'));
    }
}
