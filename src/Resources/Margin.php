<?php

namespace Groww\API\Resources;

use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

class Margin extends Resource
{
    /**
     * GET /margins/detail/user
     *
     * @throws GrowwApiException
     */
    public function user(): array
    {
        $response = $this->client->get('/margins/detail/user');
        return $this->extractPayload($response);
    }

    /**
     * POST /margins/detail/orders?segment=
     *
     * @param array $orders List of order objects
     * @throws GrowwApiException
     */
    public function forOrders(string $segment, array $orders): array
    {
        if (!in_array($segment, Constants::SEGMENTS, true)) {
            throw new \InvalidArgumentException('Invalid segment: ' . $segment);
        }

        if ($orders === [] || !$this->isList($orders)) {
            throw new \InvalidArgumentException('Orders must be a non-empty JSON array of order objects');
        }

        $response = $this->client->post('/margins/detail/orders', $orders, ['segment' => $segment]);
        return $this->extractPayload($response);
    }

    protected function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}
