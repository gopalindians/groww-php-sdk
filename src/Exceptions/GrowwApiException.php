<?php

namespace Groww\API\Exceptions;

use Exception;
use Throwable;

class GrowwApiException extends Exception
{
    /**
     * @var string
     */
    protected $errorCode;

    /**
     * GrowwApiException constructor.
     *
     * @param string $message Error message
     * @param string $errorCode Error code from Groww API
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(string $message = "", string $errorCode = "GA000", Throwable $previous = null)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the Groww API error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get string representation of the exception
     *
     * @return string
     */
    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->errorCode}]: {$this->message}";
    }
} 