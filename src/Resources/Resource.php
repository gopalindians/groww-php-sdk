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

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Unwrap documented { status, payload } responses.
     *
     * @throws GrowwApiException
     */
    protected function extractPayload(array $response): array
    {
        if (isset($response['status']) && ($response['status'] === 'FAILURE' || $response['status'] === 'ERROR')) {
            $error = is_array($response['error'] ?? null) ? $response['error'] : [];
            throw new GrowwApiException(
                $error['message'] ?? ($response['message'] ?? 'Unknown error'),
                $error['code'] ?? ($response['error_code'] ?? 'GA000')
            );
        }

        if (isset($response['payload']) && is_array($response['payload'])) {
            return $response['payload'];
        }

        if (isset($response['status'], $response['data']) && $response['status'] === 'SUCCESS' && is_array($response['data'])) {
            return $response['data'];
        }

        return $response;
    }
}
