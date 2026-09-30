<?php

namespace Groww\API\Tests\Unit;

use Groww\API\Client;
use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;
use Groww\API\Exceptions\GrowwRateLimitException;
use Groww\API\Resources\HistoricalData;
use Groww\API\Resources\Instruments;
use Groww\API\Resources\LiveData;
use Groww\API\Resources\Margin;
use Groww\API\Resources\Orders;
use Groww\API\Resources\Portfolio;
use Groww\API\Resources\SmartOrders;
use Groww\API\Resources\User;
use Groww\API\Tests\TestCase;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class ClientTest extends TestCase
{
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client($this->getTestAccessToken());
    }

    public function testClientInitializes()
    {
        $this->assertInstanceOf(Client::class, $this->client);
        $this->assertEquals($this->getTestAccessToken(), $this->client->getAccessToken());
    }

    public function testEmptyAccessTokenThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('');
    }

    public function testGenerateChecksum()
    {
        $checksum = Client::generateChecksum('secret', '1719830400');
        $this->assertEquals(hash('sha256', 'secret1719830400'), $checksum);
        $this->assertEquals(64, strlen($checksum));
    }

    public function testFromApproval()
    {
        $tokenResponse = [
            'token' => 'access-token-abc',
            'tokenRefId' => 'ref-123',
            'sessionName' => 'my-session',
            'expiry' => '2024-07-01T12:34:56',
            'isActive' => true,
        ];

        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], json_encode($tokenResponse)),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $tokenClient = new HttpClient(['handler' => $stack]);

        $client = Client::fromApproval('user-api-key', 'user-secret', [], $tokenClient);

        $this->assertEquals('access-token-abc', $client->getAccessToken());
        $this->assertEquals('ref-123', $client->getTokenRefId());
        $this->assertEquals('my-session', $client->getSessionName());
        $this->assertEquals('2024-07-01T12:34:56', $client->getExpiry());
        $this->assertTrue($client->isActive());

        $this->assertCount(1, $container);
        $request = $container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/token/api/access', $request->getUri()->getPath());
        $this->assertEquals('Bearer user-api-key', $request->getHeaderLine('Authorization'));
        $body = json_decode((string) $request->getBody(), true);
        $this->assertEquals('approval', $body['key_type']);
        $this->assertEquals(Client::generateChecksum('user-secret', $body['timestamp']), $body['checksum']);
        $this->assertMatchesRegularExpression('/^\d{10}$/', $body['timestamp']);
    }

    public function testFromTotp()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['token' => 'totp-token', 'isActive' => true])),
        ]);
        $stack = HandlerStack::create($mock);
        $tokenClient = new HttpClient(['handler' => $stack]);

        $client = Client::fromTotp('user-api-key', '123456', [], $tokenClient);

        $this->assertEquals('totp-token', $client->getAccessToken());
        $this->assertTrue($client->isActive());
    }

    public function testGetAndPostAndPut()
    {
        $success = $this->createSuccessResponse(['ok' => true]);
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], json_encode($success)),
            new Response(200, [], json_encode($success)),
            new Response(200, [], json_encode($success)),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $httpClient = new HttpClient(['handler' => $stack]);
        $this->client->setHttpClient($httpClient);

        $this->assertEquals($success, $this->client->get('/order/list', ['segment' => 'CASH']));
        $this->assertEquals($success, $this->client->post('/order/create', ['trading_symbol' => 'IDEA']));
        $this->assertEquals($success, $this->client->put('/order-advance/modify/gtt_1', ['quantity' => 1]));

        $this->assertEquals('GET', $container[0]['request']->getMethod());
        $this->assertEquals('POST', $container[1]['request']->getMethod());
        $this->assertEquals('PUT', $container[2]['request']->getMethod());
    }

    public function testPostJsonArrayWithQuery()
    {
        $success = $this->createSuccessResponse(['total_requirement' => 100]);
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([new Response(200, [], json_encode($success))]);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $this->client->setHttpClient(new HttpClient(['handler' => $stack]));

        $orders = [
            ['trading_symbol' => 'WIPRO', 'transaction_type' => 'BUY', 'quantity' => 1],
        ];
        $this->client->post('/margins/detail/orders', $orders, ['segment' => 'CASH']);

        $request = $container[0]['request'];
        $this->assertEquals('segment=CASH', $request->getUri()->getQuery());
        $this->assertEquals($orders, json_decode((string) $request->getBody(), true));
    }

    public function testFailureBodyOnHttp200()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode($this->createFailureResponse('Invalid trading symbol.', 'GA001'))),
        ]);
        $this->client->setHttpClient(new HttpClient(['handler' => HandlerStack::create($mock)]));

        try {
            $this->client->get('/order/list');
            $this->fail('Expected GrowwApiException');
        } catch (GrowwApiException $e) {
            $this->assertEquals('GA001', $e->getErrorCode());
            $this->assertEquals('Invalid trading symbol.', $e->getMessage());
        }
    }

    public function testFailureOnHttp4xx()
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode($this->createFailureResponse('Bad request', 'GA001'))),
        ]);
        $this->client->setHttpClient(new HttpClient(['handler' => HandlerStack::create($mock)]));

        $this->expectException(GrowwApiException::class);
        $this->expectExceptionMessage('Bad request');
        $this->client->get('/order/list');
    }

    public function testRateLimitException()
    {
        $errorResponse = $this->createFailureResponse('Unable to serve request currently', 'GA003');

        $mock = new MockHandler([
            new Response(429, [], json_encode($errorResponse)),
        ]);
        $this->client->setHttpClient(new HttpClient(['handler' => HandlerStack::create($mock)]));

        try {
            $this->client->get('/order/list');
            $this->fail('Expected GrowwRateLimitException');
        } catch (GrowwRateLimitException $e) {
            $this->assertEquals('GA003', $e->getErrorCode());
        }
    }

    public function testNetworkError()
    {
        $request = new Request('GET', '/order/list');
        $mock = new MockHandler([
            new RequestException('Network error', $request),
        ]);
        $this->client->setHttpClient(new HttpClient(['handler' => HandlerStack::create($mock)]));

        $this->expectException(GrowwApiException::class);
        $this->expectExceptionMessage('Network error');
        $this->client->get('/order/list');
    }

    public function testSetLogging()
    {
        $logCalled = false;
        $this->client->setLogging(true, function ($level, $message, $context) use (&$logCalled) {
            $logCalled = true;
        });

        $mock = new MockHandler([
            new Response(200, [], json_encode($this->createSuccessResponse(['ok' => true]))),
        ]);
        $this->client->setHttpClient(new HttpClient(['handler' => HandlerStack::create($mock)]));
        $this->client->get('/user/detail');

        $this->assertTrue($logCalled);
    }

    public function testResourceAccessMethods()
    {
        $this->assertInstanceOf(Instruments::class, $this->client->instruments());
        $this->assertInstanceOf(Orders::class, $this->client->orders());
        $this->assertInstanceOf(SmartOrders::class, $this->client->smartOrders());
        $this->assertInstanceOf(Portfolio::class, $this->client->portfolio());
        $this->assertInstanceOf(Margin::class, $this->client->margin());
        $this->assertInstanceOf(LiveData::class, $this->client->liveData());
        $this->assertInstanceOf(HistoricalData::class, $this->client->historicalData());
        $this->assertInstanceOf(User::class, $this->client->user());
    }

    public function testDefaultHeadersIncludeApiVersion()
    {
        $this->assertEquals(Constants::API_VERSION, '1.0');
        $this->assertEquals('https://api.groww.in/v1', Client::BASE_URL);
    }
}
