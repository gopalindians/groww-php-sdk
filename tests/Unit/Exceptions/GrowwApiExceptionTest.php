<?php

namespace Groww\API\Tests\Unit\Exceptions;

use Groww\API\Exceptions\GrowwApiException;
use Groww\API\Tests\TestCase;

class GrowwApiExceptionTest extends TestCase
{
    /**
     * Test exception basic functionality.
     */
    public function testBasicFunctionality()
    {
        $message = 'Test error message';
        $errorCode = 'GA001';
        
        $exception = new GrowwApiException($message, $errorCode);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errorCode, $exception->getErrorCode());
    }

    /**
     * Test toString method.
     */
    public function testToString()
    {
        $message = 'Test error message';
        $errorCode = 'GA001';
        
        $exception = new GrowwApiException($message, $errorCode);
        
        $this->assertEquals(
            'Groww\API\Exceptions\GrowwApiException: [GA001]: Test error message',
            (string) $exception
        );
    }

    /**
     * Test default values.
     */
    public function testDefaultValues()
    {
        $exception = new GrowwApiException();
        
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals('GA000', $exception->getErrorCode());
    }

    /**
     * Test with previous exception.
     */
    public function testWithPreviousException()
    {
        $previousException = new \Exception('Previous exception');
        $exception = new GrowwApiException('Test error', 'GA001', $previousException);
        
        $this->assertEquals($previousException, $exception->getPrevious());
    }
} 