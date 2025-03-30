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

    /**
     * Test getting holdings.
     */
    public function testGetHoldings()
    {
        $expectedResponse = [
            'holdings' => [
                [
                    'trading_symbol' => 'IDEA-EQ',
                    'exchange' => 'NSE',
                    'quantity' => 10,
                    'average_price' => 15.5,
                    'last_price' => 16.2
                ]
            ]
        ];
        
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('get')
            ->with('/portfolio/holdings')
            ->willReturn($apiResponse);
            
        $result = $this->portfolio->holdings();
        
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getting positions.
     */
    public function testGetPositions()
    {
        $params = ['date' => '2023-03-30'];
        
        $expectedResponse = [
            'positions' => [
                [
                    'trading_symbol' => 'IDEA-EQ',
                    'exchange' => 'NSE',
                    'product' => 'CNC',
                    'quantity' => 5,
                    'average_price' => 15.2,
                    'last_price' => 16.0
                ]
            ]
        ];
        
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('get')
            ->with('/portfolio/positions', $params)
            ->willReturn($apiResponse);
            
        $result = $this->portfolio->positions($params);
        
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getting holding details.
     */
    public function testGetHoldingDetails()
    {
        $symbolIsin = 'INE123456789';
        
        $expectedResponse = [
            'symbolIsin' => $symbolIsin,
            'trading_symbol' => 'IDEA-EQ',
            'exchange' => 'NSE',
            'quantity' => 10,
            'average_price' => 15.5,
            'last_price' => 16.2
        ];
        
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('get')
            ->with('/portfolio/holding/detail', ['symbolIsin' => $symbolIsin])
            ->willReturn($apiResponse);
            
        $result = $this->portfolio->holdingDetails($symbolIsin);
        
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test converting position.
     */
    public function testConvertPosition()
    {
        $conversionData = [
            'symbolIsin' => 'INE123456789',
            'from_product' => 'MIS',
            'to_product' => 'CNC',
            'quantity' => 5
        ];
        
        $expectedResponse = ['status' => 'SUCCESS'];
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('post')
            ->with('/portfolio/position/convert', $conversionData)
            ->willReturn($apiResponse);
            
        $result = $this->portfolio->convertPosition($conversionData);
        
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getting profit and loss summary.
     */
    public function testGetPnlSummary()
    {
        $params = ['from' => '2023-01-01', 'to' => '2023-03-30'];
        
        $expectedResponse = [
            'realized_profit' => 1200.50,
            'unrealized_profit' => 800.25,
            'total_profit' => 2000.75
        ];
        
        $apiResponse = $this->createSuccessResponse($expectedResponse);
        
        $this->client->expects($this->once())
            ->method('get')
            ->with('/portfolio/pnl/summary', $params)
            ->willReturn($apiResponse);
            
        $result = $this->portfolio->pnlSummary($params);
        
        $this->assertEquals($expectedResponse, $result);
    }
} 