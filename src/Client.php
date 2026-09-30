<?php

namespace Groww\API;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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

class Client
{
    public const BASE_URL = 'https://api.groww.in/v1';

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string|null
     */
    protected $tokenRefId;

    /**
     * @var string|null
     */
    protected $sessionName;

    /**
     * @var string|null
     */
    protected $expiry;

    /**
     * @var bool|null
     */
    protected $isActive;

    /**
     * @var string
     */
    protected $baseUrl = self::BASE_URL;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @var float
     */
    protected $lastRequestTime = 0;

    /**
     * Minimum gap between requests (ms). 100ms keeps calls under the 10/s order and live-data caps.
     *
     * @var int
     */
    protected $requestDelay = 100;

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
     * @param string $accessToken Access token (not the API key)
     * @param array $options Additional Guzzle options
     */
    public function __construct(string $accessToken, array $options = [])
    {
        if ($accessToken === '') {
            throw new \InvalidArgumentException('Access token cannot be empty');
        }

        $this->accessToken = $accessToken;

        $stack = HandlerStack::create();
        $stack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));

        $defaultOptions = [
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-VERSION' => Constants::API_VERSION,
            ],
            'handler' => $stack,
            'verify' => true,
            'timeout' => 30,
            'connect_timeout' => 10,
        ];

        $this->httpClient = new HttpClient(array_merge($defaultOptions, $options));
    }

    /**
     * SHA-256 checksum of api secret concatenated with epoch-second timestamp.
     */
    public static function generateChecksum(string $secret, string $timestamp): string
    {
        return hash('sha256', $secret . $timestamp);
    }

    /**
     * Exchange API key + secret for an access token (approval flow).
     *
     * @param HttpClient|null $tokenHttpClient Injected only in tests
     */
    public static function fromApproval(
        string $apiKey,
        string $secret,
        array $options = [],
        ?HttpClient $tokenHttpClient = null
    ): self {
        $timestamp = (string) time();
        $payload = self::requestAccessToken($apiKey, [
            'key_type' => 'approval',
            'checksum' => self::generateChecksum($secret, $timestamp),
            'timestamp' => $timestamp,
        ], $tokenHttpClient);

        return self::fromTokenResponse($payload, $options);
    }

    /**
     * Exchange API key + TOTP for an access token.
     *
     * @param HttpClient|null $tokenHttpClient Injected only in tests
     */
    public static function fromTotp(
        string $apiKey,
        string $totp,
        array $options = [],
        ?HttpClient $tokenHttpClient = null
    ): self {
        $payload = self::requestAccessToken($apiKey, [
            'key_type' => 'totp',
            'totp' => $totp,
        ], $tokenHttpClient);

        return self::fromTokenResponse($payload, $options);
    }

    /**
     * @param array $body
     * @throws GrowwApiException
     */
    protected static function requestAccessToken(string $apiKey, array $body, ?HttpClient $httpClient = null): array
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('API key cannot be empty');
        }

        if ($httpClient === null) {
            $httpClient = new HttpClient([
                'base_uri' => self::BASE_URL,
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'verify' => true,
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);
        }

        try {
            $response = $httpClient->request('POST', '/token/api/access', [
                'json' => $body,
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
            $decoded = json_decode((string) $response->getBody(), true);

            if (!is_array($decoded)) {
                throw new GrowwApiException('Invalid token response', 'GA000');
            }

            if (isset($decoded['status']) && $decoded['status'] === 'FAILURE') {
                $error = $decoded['error'] ?? [];
                throw new GrowwApiException(
                    $error['message'] ?? 'Token request failed',
                    $error['code'] ?? 'GA000'
                );
            }

            $token = $decoded['token'] ?? ($decoded['payload']['token'] ?? null);
            if (empty($token)) {
                throw new GrowwApiException('Token missing from access response', 'GA000');
            }

            return [
                'token' => $token,
                'tokenRefId' => $decoded['tokenRefId'] ?? ($decoded['payload']['tokenRefId'] ?? null),
                'sessionName' => $decoded['sessionName'] ?? ($decoded['payload']['sessionName'] ?? null),
                'expiry' => $decoded['expiry'] ?? ($decoded['payload']['expiry'] ?? null),
                'isActive' => $decoded['isActive'] ?? ($decoded['payload']['isActive'] ?? null),
            ];
        } catch (GrowwApiException $e) {
            throw $e;
        } catch (GuzzleException $e) {
            throw self::exceptionFromGuzzle($e);
        }
    }

    protected static function fromTokenResponse(array $payload, array $options): self
    {
        $client = new self($payload['token'], $options);
        $client->tokenRefId = $payload['tokenRefId'] ?? null;
        $client->sessionName = $payload['sessionName'] ?? null;
        $client->expiry = $payload['expiry'] ?? null;
        $client->isActive = isset($payload['isActive']) ? (bool) $payload['isActive'] : null;

        return $client;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTokenRefId(): ?string
    {
        return $this->tokenRefId;
    }

    public function getSessionName(): ?string
    {
        return $this->sessionName;
    }

    public function getExpiry(): ?string
    {
        return $this->expiry;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setLogging(bool $enable, ?callable $logger = null): self
    {
        $this->enableLogging = $enable;
        $this->logger = $logger;
        return $this;
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enableLogging) {
            return;
        }

        if (isset($context['headers']['Authorization'])) {
            $context['headers']['Authorization'] = 'Bearer ********';
        }

        if ($this->logger) {
            call_user_func($this->logger, $level, $message, $context);
            return;
        }

        error_log(sprintf(
            "[%s] %s: %s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message . ' ' . json_encode($context)
        ));
    }

    protected function retryDecider(): callable
    {
        return function (
            $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Exception $exception = null
        ) {
            if ($retries >= $this->maxRetries) {
                return false;
            }

            if ($response && $response->getStatusCode() === 429) {
                return true;
            }

            if ($response && $response->getStatusCode() >= 500) {
                return true;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            return false;
        };
    }

    protected function retryDelay(): callable
    {
        return function ($numberOfRetries) {
            return 1000 * pow(2, $numberOfRetries - 1);
        };
    }

    public function setHttpClient(HttpClient $client): self
    {
        $this->httpClient = $client;
        return $this;
    }

    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * @throws GrowwApiException
     */
    public function get(string $endpoint, array $params = []): array
    {
        $this->respectRateLimit();
        $endpoint = $this->sanitizeUrl($endpoint);
        $params = $this->sanitizeParams($params);

        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * Raw GET (CSV or other non-JSON bodies).
     *
     * @throws GrowwApiException
     */
    public function getRaw(string $endpoint, array $params = []): string
    {
        $this->respectRateLimit();
        $params = $this->sanitizeParams($params);

        try {
            $response = $this->httpClient->request('GET', $endpoint, ['query' => $params]);
            return (string) $response->getBody();
        } catch (GuzzleException $e) {
            throw self::exceptionFromGuzzle($e);
        }
    }

    /**
     * @param array $data Associative object or a JSON array (margin basket)
     * @throws GrowwApiException
     */
    public function post(string $endpoint, array $data = [], array $query = []): array
    {
        $this->respectRateLimit();
        $endpoint = $this->sanitizeUrl($endpoint);
        $data = $this->sanitizeParams($data);
        $query = $this->sanitizeParams($query);

        $options = ['json' => $data];
        if ($query !== []) {
            $options['query'] = $query;
        }

        return $this->request('POST', $endpoint, $options);
    }

    /**
     * @throws GrowwApiException
     */
    public function put(string $endpoint, array $data = [], array $query = []): array
    {
        $this->respectRateLimit();
        $endpoint = $this->sanitizeUrl($endpoint);
        $data = $this->sanitizeParams($data);
        $query = $this->sanitizeParams($query);

        $options = ['json' => $data];
        if ($query !== []) {
            $options['query'] = $query;
        }

        return $this->request('PUT', $endpoint, $options);
    }

    /**
     * @throws GrowwApiException
     */
    public function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $this->log('debug', "Sending $method request to $endpoint", [
                'method' => $method,
                'endpoint' => $endpoint,
                'options' => $this->redactSensitiveData($options),
            ]);

            $response = $this->httpClient->request($method, $endpoint, $options);
            $body = json_decode((string) $response->getBody(), true);

            if (!is_array($body)) {
                throw new GrowwApiException('Invalid JSON response', 'GA000');
            }

            $this->log('debug', "Received response from $endpoint", [
                'status_code' => $response->getStatusCode(),
                'body' => $this->redactSensitiveData($body),
            ]);

            $this->throwIfFailureBody($body, $response->getStatusCode());

            return $body;
        } catch (GrowwApiException $e) {
            throw $e;
        } catch (GuzzleException $e) {
            throw self::exceptionFromGuzzle($e);
        }
    }

    /**
     * @throws GrowwApiException
     */
    protected function throwIfFailureBody(array $body, int $statusCode = 200): void
    {
        if (($body['status'] ?? null) !== 'FAILURE' && ($body['status'] ?? null) !== 'ERROR') {
            return;
        }

        $error = is_array($body['error'] ?? null) ? $body['error'] : [];
        $errorCode = $error['code'] ?? ($body['error_code'] ?? 'GA000');
        $errorMessage = $error['message'] ?? ($body['message'] ?? 'Unknown error');

        if ($statusCode === 429 || $errorCode === 'GA003') {
            throw new GrowwRateLimitException($errorMessage, $errorCode);
        }

        throw new GrowwApiException($errorMessage, $errorCode);
    }

    /**
     * @throws GrowwApiException
     */
    protected static function exceptionFromGuzzle(GuzzleException $e): GrowwApiException
    {
        if ($e instanceof ClientException && $e->getResponse() !== null) {
            $statusCode = $e->getResponse()->getStatusCode();
            $responseBody = json_decode((string) $e->getResponse()->getBody(), true);

            if (is_array($responseBody)) {
                $error = is_array($responseBody['error'] ?? null) ? $responseBody['error'] : [];
                $errorCode = $error['code'] ?? ($responseBody['error_code'] ?? 'GA000');
                $errorMessage = $error['message'] ?? ($responseBody['message'] ?? 'Request failed');

                if ($statusCode === 429) {
                    return new GrowwRateLimitException($errorMessage, $errorCode === 'GA000' ? 'GA003' : $errorCode);
                }

                return new GrowwApiException($errorMessage, $errorCode, $e);
            }

            if ($statusCode === 429) {
                return new GrowwRateLimitException('Rate limit exceeded', 'GA003');
            }
        }

        return new GrowwApiException('Request failed: ' . $e->getMessage(), 'GA000', $e);
    }

    protected function sanitizeUrl(string $url): string
    {
        $url = str_replace(chr(0), '', $url);

        $parts = explode('/', $url);
        $parts = array_map('rawurlencode', $parts);

        return implode('/', $parts);
    }

    /**
     * @param array $params
     * @return array
     */
    protected function sanitizeParams(array $params): array
    {
        $sanitized = [];

        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $key = $this->sanitizeString($key);
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeParams($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function sanitizeString(string $value): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    }

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
            'access_token', 'refresh_token', 'private_key', 'secret_key', 'totp', 'checksum',
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveFields, true)) {
                $data[$key] = '********';
            } elseif (is_array($value)) {
                $data[$key] = $this->redactSensitiveData($value);
            }
        }

        return $data;
    }

    public function instruments(): Instruments
    {
        if (!isset($this->resources['instruments'])) {
            $this->resources['instruments'] = new Instruments($this);
        }

        return $this->resources['instruments'];
    }

    public function orders(): Orders
    {
        if (!isset($this->resources['orders'])) {
            $this->resources['orders'] = new Orders($this);
        }

        return $this->resources['orders'];
    }

    public function smartOrders(): SmartOrders
    {
        if (!isset($this->resources['smartOrders'])) {
            $this->resources['smartOrders'] = new SmartOrders($this);
        }

        return $this->resources['smartOrders'];
    }

    public function portfolio(): Portfolio
    {
        if (!isset($this->resources['portfolio'])) {
            $this->resources['portfolio'] = new Portfolio($this);
        }

        return $this->resources['portfolio'];
    }

    public function margin(): Margin
    {
        if (!isset($this->resources['margin'])) {
            $this->resources['margin'] = new Margin($this);
        }

        return $this->resources['margin'];
    }

    public function liveData(): LiveData
    {
        if (!isset($this->resources['liveData'])) {
            $this->resources['liveData'] = new LiveData($this);
        }

        return $this->resources['liveData'];
    }

    public function historicalData(): HistoricalData
    {
        if (!isset($this->resources['historicalData'])) {
            $this->resources['historicalData'] = new HistoricalData($this);
        }

        return $this->resources['historicalData'];
    }

    public function user(): User
    {
        if (!isset($this->resources['user'])) {
            $this->resources['user'] = new User($this);
        }

        return $this->resources['user'];
    }
}
