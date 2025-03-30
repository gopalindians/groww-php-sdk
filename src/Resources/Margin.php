<?php

namespace Groww\API\Resources;

use Groww\API\Exceptions\GrowwApiException;

class Margin extends Resource
{
    /**
     * Get available margin
     *
     * @return array Margin details
     * @throws GrowwApiException
     */
    public function available(): array
    {
        $response = $this->client->get('/margin/available');
        return $this->extractPayload($response);
    }

    /**
     * Get margin required for order
     *
     * @param array $orderData Order details
     * @return array Margin requirements
     * @throws GrowwApiException
     */
    public function required(array $orderData): array
    {
        $response = $this->client->post('/margin/required', $orderData);
        return $this->extractPayload($response);
    }

    /**
     * Get margin utilization
     *
     * @return array Margin utilization details
     * @throws GrowwApiException
     */
    public function utilization(): array
    {
        $response = $this->client->get('/margin/utilization');
        return $this->extractPayload($response);
    }

    /**
     * Get margin limits
     *
     * @return array Margin limits
     * @throws GrowwApiException
     */
    public function limits(): array
    {
        $response = $this->client->get('/margin/limits');
        return $this->extractPayload($response);
    }
} 