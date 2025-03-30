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
        // Handle error responses first
        if (isset($response['status']) && $response['status'] === 'ERROR') {
            throw new GrowwApiException(
                $response['message'] ?? $response['error']['message'] ?? 'Unknown error',
                $response['error_code'] ?? $response['error']['code'] ?? 'GA000'
            );
        }

        // Check if this is a direct payload without wrapping
        if (!isset($response['status']) && !isset($response['payload']) && !isset($response['data'])) {
            return $response; // Response is already the data we want
        }

        // If using standard response format with 'data' field for testing
        if (isset($response['status']) && $response['status'] === 'SUCCESS' && isset($response['data'])) {
            return $response['data'];
        }
        
        // If using standard API response format with 'payload' field
        if ((!isset($response['status']) || $response['status'] === 'SUCCESS') && isset($response['payload'])) {
            return $response['payload'];
        }

        // Return the response if we can't determine the structure
        return $response;
    }
} 