<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
use Groww\API\Constants;
use Groww\API\Resources\Instruments;
use Groww\API\Tests\TestCase;

class InstrumentsTest extends TestCase
{
    public function testParseCsv()
    {
        $csv = "exchange,trading_symbol,segment\nNSE,RELIANCE,CASH\nBSE,SENSEX,CASH\n";
        $client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $instruments = new Instruments($client);

        $rows = $instruments->parseCsv($csv);
        $this->assertCount(2, $rows);
        $this->assertEquals('NSE', $rows[0]['exchange']);
        $this->assertEquals('RELIANCE', $rows[0]['trading_symbol']);
    }

    public function testDownload()
    {
        $csv = "exchange,trading_symbol\nNSE,WIPRO\n";
        $client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $client->expects($this->once())
            ->method('getRaw')
            ->with(Constants::INSTRUMENTS_CSV_URL)
            ->willReturn($csv);

        $instruments = new Instruments($client);
        $rows = $instruments->download();
        $this->assertEquals('WIPRO', $rows[0]['trading_symbol']);
    }
}
