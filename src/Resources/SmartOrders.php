<?php

namespace Groww\API\Resources;

use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

class SmartOrders extends Resource
{
    /**
     * Create a GTT or OCO smart order.
     *
     * POST /order-advance/create
     *
     * @throws GrowwApiException
     */
    public function create(array $data): array
    {
        if (empty($data['smart_order_type'])) {
            throw new \InvalidArgumentException('Missing required field: smart_order_type');
        }
        if (!in_array($data['smart_order_type'], Constants::SMART_ORDER_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Invalid smart_order_type. Valid types: ' . implode(', ', Constants::SMART_ORDER_TYPES)
            );
        }
        if (empty($data['segment']) || !in_array($data['segment'], Constants::SEGMENTS, true)) {
            throw new \InvalidArgumentException(
                'Invalid segment. Valid segments: ' . implode(', ', Constants::SEGMENTS)
            );
        }

        $response = $this->client->post('/order-advance/create', $data);
        return $this->extractPayload($response);
    }

    /**
     * PUT /order-advance/modify/{smart_order_id}
     *
     * @throws GrowwApiException
     */
    public function modify(string $smartOrderId, array $data): array
    {
        if ($smartOrderId === '') {
            throw new \InvalidArgumentException('Smart order ID cannot be empty');
        }

        $response = $this->client->put("/order-advance/modify/{$smartOrderId}", $data);
        return $this->extractPayload($response);
    }

    /**
     * POST /order-advance/cancel/{segment}/{smart_order_type}/{smart_order_id}
     *
     * @throws GrowwApiException
     */
    public function cancel(string $segment, string $smartOrderType, string $smartOrderId): array
    {
        $this->validatePathParts($segment, $smartOrderType, $smartOrderId);

        $response = $this->client->post("/order-advance/cancel/{$segment}/{$smartOrderType}/{$smartOrderId}");
        return $this->extractPayload($response);
    }

    /**
     * GET /order-advance/status/{segment}/{smart_order_type}/internal/{smart_order_id}
     *
     * @throws GrowwApiException
     */
    public function status(string $segment, string $smartOrderType, string $smartOrderId): array
    {
        $this->validatePathParts($segment, $smartOrderType, $smartOrderId);

        $response = $this->client->get(
            "/order-advance/status/{$segment}/{$smartOrderType}/internal/{$smartOrderId}"
        );
        return $this->extractPayload($response);
    }

    /**
     * GET /order-advance/list
     *
     * @throws GrowwApiException
     */
    public function list(array $params = []): array
    {
        $response = $this->client->get('/order-advance/list', $params);
        return $this->extractPayload($response);
    }

    protected function validatePathParts(string $segment, string $smartOrderType, string $smartOrderId): void
    {
        if ($smartOrderId === '') {
            throw new \InvalidArgumentException('Smart order ID cannot be empty');
        }
        if (!in_array($segment, Constants::SEGMENTS, true)) {
            throw new \InvalidArgumentException('Invalid segment: ' . $segment);
        }
        if (!in_array($smartOrderType, Constants::SMART_ORDER_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid smart_order_type: ' . $smartOrderType);
        }
    }
}
