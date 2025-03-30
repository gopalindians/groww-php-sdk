<?php

namespace Groww\API;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Groww\API\Exceptions\GrowwApiException;
use Groww\API\Exceptions\GrowwRateLimitException;
use Groww\API\Resources\Instruments;
use Groww\API\Resources\Orders;
use Groww\API\Resources\Portfolio;
use Groww\API\Resources\Margin;
use Groww\API\Resources\LiveData;
use Groww\API\Resources\HistoricalData;

class Client
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $baseUrl = 'https://api.groww.in/v1/api/apex/v1';

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @var int
     */
    protected $lastRequestTime = 0;

    /**
     * @var int
     */
    protected $requestDelay = 100; // Milliseconds between requests

    /**
     * @var int
     */
    protected $maxRetries = 3;

    /**
     * @var bool
     */
    protected $enableLogging = false;

    /**
     * @var callable|null
     */
    protected $logger = null;

    /**
     * Client constructor.
     *
     * @param string $apiKey Your Groww API key
     * @param array $options Additional options for the HTTP client
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key cannot be empty');
        }

        $this->apiKey = $apiKey;
        
        // Create handler stack with middleware
        $stack = HandlerStack::create();
        
        // Add rate limiting middleware
        $stack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        
        // Set default security options
        $defaultOptions = [
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'handler' => $stack,
            'verify' => true, // Enforce SSL verification
            'timeout' => 30,
            'connect_timeout' => 10,
        ];
        
        $this->httpClient = new HttpClient(array_merge($defaultOptions, $options));
    }

    /**
     * Enable or disable request/response logging
     *
     * @param bool $enable
     * @param callable|null $logger Custom logger function
     * @return self
     */
    public function setLogging(bool $enable, ?callable $logger = null): self
    {
        $this->enableLogging = $enable;
        $this->logger = $logger;
        return $this;
    }

    /**
     * Log a message
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enableLogging) {
            return;
        }

        // Mask sensitive data
        if (isset($context['headers']['Authorization'])) {
            $context['headers']['Authorization'] = 'Bearer ********';
        }

        if ($this->logger) {
            call_user_func($this->logger, $level, $message, $context);
            return;
        }

        // Simple default logger
        error_log(sprintf("[%s] %s: %s", 
            date('Y-m-d H:i:s'), 
            strtoupper($level), 
            $message . ' ' . json_encode($context)
        ));
    }

    /**
     * Create retry decider function for rate limiting
     *
     * @return callable
     */
    protected function retryDecider(): callable
    {
        return function (
            $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Exception $exception = null
        ) {
            // Retry connection exceptions
            if ($retries >= $this->maxRetries) {
                return false;
            }

            // Retry rate limit errors
            if ($response && $response->getStatusCode() === 429) {
                return true;
            }

            // Retry server errors
            if ($response && $response->getStatusCode() >= 500) {
                return true;
            }

            // Retry on connection exceptions
            if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                return true;
            }

            return false;
        };
    }

    /**
     * Create retry delay function with exponential backoff
     *
     * @return callable
     */
    protected function retryDelay(): callable
    {
        return function ($numberOfRetries) {
            return 1000 * pow(2, $numberOfRetries - 1);
        };
    }

    /**
     * Set a custom HTTP client (mainly for testing)
     *
     * @param HttpClient $client
     * @return self
     */
    public function setHttpClient(HttpClient $client): self
    {
        $this->httpClient = $client;
        return $this;
    }

    /**
     * Send a GET request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array Response data
     * @throws GrowwApiException
     */
    public function get(string $endpoint, array $params = []): array
    {
        // Enforce rate limiting
        $this->respectRateLimit();
        
        // Sanitize URL path parameters
        $endpoint = $this->sanitizeUrl($endpoint);
        
        // Sanitize query parameters
        $params = $this->sanitizeParams($params);

        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * Send a POST request to the API
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws GrowwApiException
     */
    public function post(string $endpoint, array $data = []): array
    {
        // Enforce rate limiting
        $this->respectRateLimit();
        
        // Sanitize URL path parameters
        $endpoint = $this->sanitizeUrl($endpoint);
        
        // Sanitize request data
        $data = $this->sanitizeParams($data);

        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Send a request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array Response data
     * @throws GrowwApiException
     */
    public function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $this->log('debug', "Sending $method request to $endpoint", [
                'method' => $method,
                'endpoint' => $endpoint,
                'options' => $this->redactSensitiveData($options)
            ]);
            
            $response = $this->httpClient->request($method, $endpoint, $options);
            $body = json_decode((string) $response->getBody(), true);

            $this->log('debug', "Received response from $endpoint", [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $this->redactSensitiveData($body)
            ]);

            // Check for error responses in different formats
            if (isset($body['status']) && ($body['status'] === 'FAILURE' || $body['status'] === 'ERROR')) {
                // Extract error information directly from response
                $errorCode = $body['error_code'] ?? ($body['error']['code'] ?? 'GA000');
                $errorMessage = $body['message'] ?? ($body['error']['message'] ?? 'Unknown error');
                $waitTime = $body['rate_limit']['wait_time'] ?? ($body['error']['rate_limit']['wait_time'] ?? 60);
                
                // Check for rate limit errors
                if (($errorCode === 'GA003' || $errorCode === 'RL001') &&
                    (strpos($errorMessage, 'rate limit') !== false || $response->getStatusCode() === 429)) {
                    throw new GrowwRateLimitException($errorMessage, $errorCode, $waitTime);
                }
                
                throw new GrowwApiException($errorMessage, $errorCode);
            }

            return $body;
        } catch (GrowwRateLimitException $e) {
            $this->log('warning', "Rate limit exceeded: " . $e->getMessage());
            throw $e;
        } catch (GuzzleException $e) {
            // For HTTP 429 errors, convert to a rate limit exception
            if ($e instanceof \GuzzleHttp\Exception\ClientException && $e->getResponse()->getStatusCode() === 429) {
                $responseBody = json_decode((string) $e->getResponse()->getBody(), true);
                $errorCode = $responseBody['error_code'] ?? 'RL001';
                $errorMessage = $responseBody['message'] ?? 'Rate limit exceeded';
                $waitTime = $responseBody['rate_limit']['wait_time'] ?? 60;
                
                throw new GrowwRateLimitException($errorMessage, $errorCode, $waitTime);
            }
            
            // For other client errors, try to extract error details from the response
            if ($e instanceof \GuzzleHttp\Exception\ClientException) {
                try {
                    $responseBody = json_decode((string) $e->getResponse()->getBody(), true);
                    if (is_array($responseBody)) {
                        $errorCode = $responseBody['error_code'] ?? 'GA000';
                        $errorMessage = $responseBody['message'] ?? 'Request failed: ' . $e->getMessage();
                        throw new GrowwApiException($errorMessage, $errorCode, $e);
                    }
                } catch (\Exception $jsonException) {
                    // If we can't parse the body, fall back to default error
                }
            }
            
            $this->log('error', "Request failed: " . $e->getMessage());
            throw new GrowwApiException('Request failed: ' . $e->getMessage(), 'GA000', $e);
        }
    }

    /**
     * Sanitize URL to prevent path traversal
     *
     * @param string $url
     * @return string
     */
    protected function sanitizeUrl(string $url): string
    {
        // Remove any null bytes
        $url = str_replace(chr(0), '', $url);
        
        // URL encode path segments
        $parts = explode('/', $url);
        $parts = array_map('rawurlencode', $parts);
        
        return implode('/', $parts);
    }

    /**
     * Sanitize parameters to prevent injection
     *
     * @param array $params
     * @return array
     */
    protected function sanitizeParams(array $params): array
    {
        $sanitized = [];
        
        foreach ($params as $key => $value) {
            // Sanitize keys
            $key = $this->sanitizeString($key);
            
            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeParams($value);
            } else if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize a string value
     *
     * @param string $value
     * @return string
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes and strip control characters
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    }

    /**
     * Respect API rate limits with a simple delay mechanism
     *
     * @return void
     */
    protected function respectRateLimit(): void
    {
        $currentTime = microtime(true) * 1000;
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;
        
        if ($timeSinceLastRequest < $this->requestDelay) {
            $sleepTime = ($this->requestDelay - $timeSinceLastRequest) * 1000;
            usleep((int) $sleepTime);
        }
        
        $this->lastRequestTime = microtime(true) * 1000;
    }

    /**
     * Redact sensitive data for logging
     *
     * @param mixed $data
     * @return mixed
     */
    protected function redactSensitiveData($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        $sensitiveFields = [
            'apiKey', 'api_key', 'password', 'secret', 'Authorization', 'auth', 'token',
            'access_token', 'refresh_token', 'private_key', 'secret_key'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveFields, true)) {
                $data[$key] = '********';
            } else if (is_array($value)) {
                $data[$key] = $this->redactSensitiveData($value);
            }
        }
        
        return $data;
    }

    /**
     * Get the Instruments resource
     *
     * @return Instruments
     */
    public function instruments(): Instruments
    {
        if (!isset($this->resources['instruments'])) {
            $this->resources['instruments'] = new Instruments($this);
        }
        
        return $this->resources['instruments'];
    }

    /**
     * Get the Orders resource
     *
     * @return Orders
     */
    public function orders(): Orders
    {
        if (!isset($this->resources['orders'])) {
            $this->resources['orders'] = new Orders($this);
        }
        
        return $this->resources['orders'];
    }

    /**
     * Get the Portfolio resource
     *
     * @return Portfolio
     */
    public function portfolio(): Portfolio
    {
        if (!isset($this->resources['portfolio'])) {
            $this->resources['portfolio'] = new Portfolio($this);
        }
        
        return $this->resources['portfolio'];
    }

    /**
     * Get the Margin resource
     *
     * @return Margin
     */
    public function margin(): Margin
    {
        if (!isset($this->resources['margin'])) {
            $this->resources['margin'] = new Margin($this);
        }
        
        return $this->resources['margin'];
    }

    /**
     * Get the LiveData resource
     *
     * @return LiveData
     */
    public function liveData(): LiveData
    {
        if (!isset($this->resources['liveData'])) {
            $this->resources['liveData'] = new LiveData($this);
        }
        
        return $this->resources['liveData'];
    }

    /**
     * Get the HistoricalData resource
     *
     * @return HistoricalData
     */
    public function historicalData(): HistoricalData
    {
        if (!isset($this->resources['historicalData'])) {
            $this->resources['historicalData'] = new HistoricalData($this);
        }
        
        return $this->resources['historicalData'];
    }
} 