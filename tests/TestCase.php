<?php

namespace Groww\API\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Create a mock for the given class.
     *
     * @param string $class
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mock($class)
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get a test API key for testing.
     *
     * @return string
     */
    protected function getTestApiKey(): string
    {
        return $_ENV['TEST_API_KEY'] ?? 'test_api_key';
    }

    /**
     * Create a standard success response.
     *
     * @param array $data
     * @return array
     */
    protected function createSuccessResponse(array $data): array
    {
        return [
            'status' => 'SUCCESS',
            'data' => $data
        ];
    }

    /**
     * Create a standard error response.
     *
     * @param string $message
     * @param string $errorCode
     * @return array
     */
    protected function createErrorResponse(string $message, string $errorCode): array
    {
        return [
            'status' => 'ERROR',
            'message' => $message,
            'error_code' => $errorCode
        ];
    }
} 