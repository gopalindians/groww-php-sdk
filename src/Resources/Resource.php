<?php

namespace Groww\API\Resources;

use Groww\API\Client;
use Groww\API\Exceptions\GrowwApiException;

abstract class Resource
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Resource constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Extract payload from API response
     *
     * @param array $response
     * @return array
     * @throws GrowwApiException
     */
    protected function extractPayload(array $response): array
    {
        if (!isset($response['status']) || $response['status'] !== 'SUCCESS') {
            throw new GrowwApiException(
                $response['error']['message'] ?? 'Unknown error',
                $response['error']['code'] ?? 'GA000'
            );
        }

        return $response['payload'] ?? [];
    }
} 