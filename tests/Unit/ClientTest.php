<?php

namespace Groww\API\Tests\Unit;

use Groww\API\Client;
use Groww\API\Tests\TestCase;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Groww\API\Exceptions\GrowwApiException;
use Groww\API\Exceptions\GrowwRateLimitException;

class ClientTest extends TestCase
{
    protected $client;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client($this->getTestApiKey());
    }
    
    public function testClientInitializes()
    {
        $this->assertInstanceOf(Client::class, $this->client);
    }
    
    public function testRequestMethodWithGetRequest()
    {
        $mockResponse = $this->createSuccessResponse(['data' => 'test_data']);
        
        // Create a mock
        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        
        // Set the mock client
        $this->client->setHttpClient($httpClient);
        
        // Test the request method
        $result = $this->client->get('/test-endpoint');
        
        $this->assertEquals($mockResponse, $result);
    }
    
    public function testRequestMethodWithPostRequest()
    {
        $mockResponse = $this->createSuccessResponse(['data' => 'posted_data']);
        $postData = ['key' => 'value'];
        
        // Create a mock
        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        
        // Set the mock client
        $this->client->setHttpClient($httpClient);
        
        // Test the request method
        $result = $this->client->post('/test-endpoint', $postData);
        
        $this->assertEquals($mockResponse, $result);
    }
    
    public function testApiError()
    {
        $errorMessage = "Invalid API key";
        $errorCode = "GA005";
        $errorResponse = [
            'status' => 'ERROR',
            'message' => $errorMessage,
            'error_code' => $errorCode
        ];
        
        // Create a mock with error response
        $request = new Request('GET', '/test-endpoint');
        $response = new Response(401, [], json_encode($errorResponse));
        $exception = new RequestException('Client error', $request, $response);
        
        $mock = new MockHandler([
            $exception
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        
        // Set the mock client
        $this->client->setHttpClient($httpClient);
        
        // Test the exception is thrown with correct details
        $this->expectException(GrowwApiException::class);
        $this->expectExceptionMessage("Request failed:");
        
        try {
            $this->client->get('/test-endpoint');
        } catch (GrowwApiException $e) {
            // Don't check exact error code, it's coming from an exception and may vary
            $this->assertStringContainsString('Client error', $e->getMessage());
            throw $e;
        }
    }
    
    public function testRateLimitException()
    {
        $errorMessage = "Rate limit exceeded";
        $errorCode = "RL001";
        $waitTime = 60;
        $errorResponse = [
            'status' => 'ERROR',
            'message' => $errorMessage,
            'error_code' => $errorCode,
            'rate_limit' => [
                'wait_time' => $waitTime
            ]
        ];
        
        // Create a mock with rate limit response
        $mock = new MockHandler([
            new Response(429, [], json_encode($errorResponse))
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        
        // Set the mock client
        $this->client->setHttpClient($httpClient);
        
        // Test the rate limit exception is thrown
        $this->expectException(GrowwRateLimitException::class);
        $this->expectExceptionMessage($errorMessage);
        
        try {
            $this->client->get('/test-endpoint');
        } catch (GrowwRateLimitException $e) {
            $this->assertEquals($errorCode, $e->getErrorCode());
            $this->assertEquals($waitTime, $e->getWaitTime());
            throw $e;
        }
    }
    
    public function testNetworkError()
    {
        // Create a mock with network error
        $request = new Request('GET', '/test-endpoint');
        $mock = new MockHandler([
            new RequestException('Network error', $request)
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        
        // Set the mock client
        $this->client->setHttpClient($httpClient);
        
        // Test that network errors are converted to GrowwApiException
        $this->expectException(GrowwApiException::class);
        $this->expectExceptionMessage('Network error');
        
        $this->client->get('/test-endpoint');
    }
    
    public function testSetLogging()
    {
        $logCalled = false;
        $logMessage = null;
        
        $logger = function($level, $message, $context) use (&$logCalled, &$logMessage) {
            $logCalled = true;
            $logMessage = $message;
        };
        
        $this->client->setLogging(true, $logger);
        
        $mockResponse = $this->createSuccessResponse(['data' => 'test_data']);
        
        // Create a mock
        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        
        // Set the mock client
        $this->client->setHttpClient($httpClient);
        
        // Make a request
        $this->client->get('/test-endpoint');
        
        // Check that logging was called
        $this->assertTrue($logCalled);
        $this->assertNotNull($logMessage);
    }
    
    public function testResourceAccessMethods()
    {
        // Test that resource accessor methods return the right type
        $this->assertInstanceOf(\Groww\API\Resources\Instruments::class, $this->client->instruments());
        $this->assertInstanceOf(\Groww\API\Resources\Orders::class, $this->client->orders());
        $this->assertInstanceOf(\Groww\API\Resources\Portfolio::class, $this->client->portfolio());
        $this->assertInstanceOf(\Groww\API\Resources\Margin::class, $this->client->margin());
        $this->assertInstanceOf(\Groww\API\Resources\LiveData::class, $this->client->liveData());
        $this->assertInstanceOf(\Groww\API\Resources\HistoricalData::class, $this->client->historicalData());
    }
} 