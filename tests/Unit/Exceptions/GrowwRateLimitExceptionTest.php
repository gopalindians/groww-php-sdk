<?php

namespace Groww\API\Tests\Unit\Exceptions;

use Groww\API\Exceptions\GrowwRateLimitException;
use Groww\API\Tests\TestCase;

class GrowwRateLimitExceptionTest extends TestCase
{
    /**
     * Test basic functionality.
     */
    public function testBasicFunctionality()
    {
        $message = 'Rate limit exceeded';
        $errorCode = 'GA003';
        $waitTime = 60;
        
        $exception = new GrowwRateLimitException($message, $errorCode, $waitTime);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errorCode, $exception->getErrorCode());
        $this->assertEquals($waitTime, $exception->getWaitTime());
    }

    /**
     * Test with default values.
     */
    public function testDefaultValues()
    {
        $exception = new GrowwRateLimitException();
        
        $this->assertEquals('Rate limit exceeded', $exception->getMessage());
        $this->assertEquals('GA003', $exception->getErrorCode());
        $this->assertEquals(60, $exception->getWaitTime());
    }

    /**
     * Test with custom wait time.
     */
    public function testCustomWaitTime()
    {
        $exception = new GrowwRateLimitException('Custom message', 'GA003', 120);
        
        $this->assertEquals(120, $exception->getWaitTime());
    }

    /**
     * Test with previous exception.
     */
    public function testWithPreviousException()
    {
        $previousException = new \Exception('Previous exception');
        $exception = new GrowwRateLimitException('Rate limit exceeded', 'GA003', 60, $previousException);
        
        $this->assertEquals($previousException, $exception->getPrevious());
    }

    /**
     * Test inheritance from GrowwApiException.
     */
    public function testInheritance()
    {
        $exception = new GrowwRateLimitException();
        
        $this->assertInstanceOf(\Groww\API\Exceptions\GrowwApiException::class, $exception);
    }
} 