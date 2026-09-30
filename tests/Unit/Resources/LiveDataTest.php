<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
use Groww\API\Constants;
use Groww\API\Resources\LiveData;
use Groww\API\Tests\TestCase;

class LiveDataTest extends TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $client;

    /**
     * @var LiveData
     */
    protected $liveData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->liveData = new LiveData($this->client);
    }

    public function testQuote()
    {
        $expected = ['last_price' => 149.5];
        $this->client->expects($this->once())
            ->method('get')
            ->with('/live-data/quote', [
                'exchange' => 'NSE',
                'segment' => 'CASH',
                'trading_symbol' => 'NIFTY',
            ])
            ->willReturn($this->createSuccessResponse($expected));

        $this->assertEquals($expected, $this->liveData->quote('NSE', 'CASH', 'NIFTY'));
    }

    public function testLtpAndOhlc()
    {
        $symbols = ['NSE_RELIANCE', 'BSE_SENSEX'];
        $this->client->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['/live-data/ltp', ['segment' => 'CASH', 'exchange_symbols' => 'NSE_RELIANCE,BSE_SENSEX']],
                ['/live-data/ohlc', ['segment' => 'CASH', 'exchange_symbols' => 'NSE_RELIANCE,BSE_SENSEX']]
            )
            ->willReturn($this->createSuccessResponse(['NSE_RELIANCE' => 2334.2]));

        $this->liveData->ltp(Constants::SEGMENT_CASH, $symbols);
        $this->liveData->ohlc(Constants::SEGMENT_CASH, $symbols);
    }

    public function testOptionChainAndGreeks()
    {
        $this->client->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['/option-chain/exchange/NSE/underlying/NIFTY', ['expiry_date' => '2025-10-14']],
                ['/live-data/greeks/exchange/NSE/underlying/NIFTY/trading_symbol/NIFTY25O1425100CE/expiry/2025-10-14']
            )
            ->willReturn($this->createSuccessResponse(['greeks' => ['delta' => 0.6]]));

        $this->liveData->optionChain('NSE', 'NIFTY', '2025-10-14');
        $this->liveData->greeks('NSE', 'NIFTY', 'NIFTY25O1425100CE', '2025-10-14');
    }

    public function testLtpMaxFifty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->liveData->ltp('CASH', array_fill(0, 51, 'NSE_RELIANCE'));
    }
}
