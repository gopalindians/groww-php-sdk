<?php

namespace Groww\API\Tests\Unit\Resources;

use Groww\API\Client;
use Groww\API\Resources\User;
use Groww\API\Tests\TestCase;

class UserTest extends TestCase
{
    public function testDetail()
    {
        $client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $expected = [
            'vendor_user_id' => 'd86890d1',
            'ucc' => '924189',
            'nse_enabled' => true,
            'active_segments' => ['CASH', 'FNO'],
        ];
        $client->expects($this->once())
            ->method('get')
            ->with('/user/detail')
            ->willReturn($this->createSuccessResponse($expected));

        $user = new User($client);
        $this->assertEquals($expected, $user->detail());
    }
}
