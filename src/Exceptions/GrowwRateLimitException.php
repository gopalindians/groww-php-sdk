<?php

namespace Groww\API\Exceptions;

class GrowwRateLimitException extends GrowwApiException
{
    /**
     * Recommended wait time in seconds
     *
     * @var int
     */
    protected $waitTime = 60;

    /**
     * GrowwRateLimitException constructor
     *
     * @param string $message Error message
     * @param string $errorCode Error code
     * @param int $waitTime Recommended wait time in seconds
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = "Rate limit exceeded",
        string $errorCode = "GA003",
        int $waitTime = 60,
        \Throwable $previous = null
    ) {
        $this->waitTime = $waitTime;
        parent::__construct($message, $errorCode, $previous);
    }

    /**
     * Get the recommended wait time before retrying
     *
     * @return int Wait time in seconds
     */
    public function getWaitTime(): int
    {
        return $this->waitTime;
    }
} 