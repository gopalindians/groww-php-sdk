<?php

namespace Groww\API\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @param string $class
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mock($class)
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getTestAccessToken(): string
    {
        return $_ENV['TEST_ACCESS_TOKEN'] ?? 'test_access_token';
    }

    /**
     * @deprecated Use getTestAccessToken()
     */
    protected function getTestApiKey(): string
    {
        return $this->getTestAccessToken();
    }

    protected function createSuccessResponse(array $payload): array
    {
        return [
            'status' => 'SUCCESS',
            'payload' => $payload,
        ];
    }

    protected function createFailureResponse(string $message, string $errorCode = 'GA001'): array
    {
        return [
            'status' => 'FAILURE',
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'metadata' => null,
            ],
        ];
    }
}
