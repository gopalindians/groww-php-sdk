<?php

namespace Groww\API\Resources;

use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

class Orders extends Resource
{
    /**
     * Place an order.
     *
     * POST /order/create
     *
     * @throws GrowwApiException
     */
    public function create(array $orderData): array
    {
        $this->validateOrderData($orderData);
        $response = $this->client->post('/order/create', $orderData);
        return $this->extractPayload($response);
    }

    /**
     * GET /order/detail/{groww_order_id}
     *
     * @throws GrowwApiException
     */
    public function details(string $growwOrderId, string $segment = Constants::SEGMENT_CASH): array
    {
        $this->requireOrderId($growwOrderId);
        $this->validateSegment($segment);

        $response = $this->client->get("/order/detail/{$growwOrderId}", [
            'segment' => $segment,
        ]);
        return $this->extractPayload($response);
    }

    /**
     * GET /order/list
     *
     * @throws GrowwApiException
     */
    public function list(array $params = []): array
    {
        if (isset($params['segment'])) {
            $this->validateSegment($params['segment']);
        }

        $response = $this->client->get('/order/list', $params);
        return $this->extractPayload($response);
    }

    /**
     * GET /order/status/{groww_order_id}
     *
     * @throws GrowwApiException
     */
    public function status(string $growwOrderId, string $segment = Constants::SEGMENT_CASH): array
    {
        $this->requireOrderId($growwOrderId);
        $this->validateSegment($segment);

        $response = $this->client->get("/order/status/{$growwOrderId}", [
            'segment' => $segment,
        ]);
        return $this->extractPayload($response);
    }

    /**
     * GET /order/status/reference/{order_reference_id}
     *
     * @throws GrowwApiException
     */
    public function statusByReference(string $orderReferenceId, string $segment = Constants::SEGMENT_CASH): array
    {
        if ($orderReferenceId === '') {
            throw new \InvalidArgumentException('Order reference ID cannot be empty');
        }
        $this->validateSegment($segment);

        $response = $this->client->get("/order/status/reference/{$orderReferenceId}", [
            'segment' => $segment,
        ]);
        return $this->extractPayload($response);
    }

    /**
     * GET /order/trades/{groww_order_id}
     *
     * @throws GrowwApiException
     */
    public function trades(string $growwOrderId, string $segment = Constants::SEGMENT_CASH, array $params = []): array
    {
        $this->requireOrderId($growwOrderId);
        $this->validateSegment($segment);

        $response = $this->client->get("/order/trades/{$growwOrderId}", array_merge($params, [
            'segment' => $segment,
        ]));
        return $this->extractPayload($response);
    }

    /**
     * POST /order/cancel
     *
     * @throws GrowwApiException
     */
    public function cancel(string $growwOrderId, string $segment = Constants::SEGMENT_CASH): array
    {
        $this->requireOrderId($growwOrderId);
        $this->validateSegment($segment);

        $response = $this->client->post('/order/cancel', [
            'groww_order_id' => $growwOrderId,
            'segment' => $segment,
        ]);
        return $this->extractPayload($response);
    }

    /**
     * POST /order/modify
     *
     * @throws GrowwApiException
     */
    public function modify(string $growwOrderId, array $modificationData): array
    {
        $this->requireOrderId($growwOrderId);

        if (empty($modificationData['segment'])) {
            throw new \InvalidArgumentException('Missing required field: segment');
        }
        if (empty($modificationData['order_type'])) {
            throw new \InvalidArgumentException('Missing required field: order_type');
        }

        $this->validateSegment($modificationData['segment']);
        $this->validateOrderType($modificationData['order_type']);

        if (isset($modificationData['price'])) {
            $this->validatePrice($modificationData['price']);
        }
        if (isset($modificationData['quantity'])) {
            $this->validateQuantity($modificationData['quantity']);
        }
        if (isset($modificationData['trigger_price'])) {
            $this->validatePrice($modificationData['trigger_price'], 'trigger_price');
        }

        $data = array_merge(['groww_order_id' => $growwOrderId], $modificationData);
        $response = $this->client->post('/order/modify', $data);
        return $this->extractPayload($response);
    }

    protected function requireOrderId(string $growwOrderId): void
    {
        if ($growwOrderId === '') {
            throw new \InvalidArgumentException('Order ID cannot be empty');
        }
    }

    protected function validateOrderData(array $orderData): void
    {
        $requiredFields = [
            'trading_symbol',
            'exchange',
            'transaction_type',
            'order_type',
            'quantity',
            'product',
            'validity',
            'segment',
            'order_reference_id',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($orderData[$field]) || $orderData[$field] === '' || $orderData[$field] === null) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $this->validateOrderType($orderData['order_type']);
        $this->validateTransactionType($orderData['transaction_type']);
        $this->validateProduct($orderData['product']);
        $this->validateValidity($orderData['validity']);
        $this->validateSegment($orderData['segment']);
        $this->validateExchange($orderData['exchange']);
        $this->validateQuantity($orderData['quantity']);
        $this->validateOrderReferenceId($orderData['order_reference_id']);

        if ($orderData['order_type'] === Constants::ORDER_TYPE_LIMIT || $orderData['order_type'] === Constants::ORDER_TYPE_SL) {
            if (!isset($orderData['price'])) {
                throw new \InvalidArgumentException('Price is required for LIMIT and SL order types');
            }
            $this->validatePrice($orderData['price']);
        }

        if ($orderData['order_type'] === Constants::ORDER_TYPE_SL || $orderData['order_type'] === Constants::ORDER_TYPE_SL_M) {
            if (!isset($orderData['trigger_price'])) {
                throw new \InvalidArgumentException('Trigger price is required for SL and SL_M order types');
            }
            $this->validatePrice($orderData['trigger_price'], 'trigger_price');
        }
    }

    protected function validateOrderReferenceId(string $orderReferenceId): void
    {
        $hyphens = substr_count($orderReferenceId, '-');
        $length = strlen($orderReferenceId);

        if ($length < 8 || $length > 20) {
            throw new \InvalidArgumentException('order_reference_id must be 8 to 20 characters');
        }

        if ($hyphens > 2) {
            throw new \InvalidArgumentException('order_reference_id may contain at most two hyphens');
        }

        if (!preg_match('/^[A-Za-z0-9-]+$/', $orderReferenceId)) {
            throw new \InvalidArgumentException('order_reference_id must be alphanumeric with at most two hyphens');
        }
    }

    protected function validateOrderType(string $orderType): void
    {
        if (!in_array($orderType, Constants::ORDER_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Invalid order type: ' . $orderType . '. Valid types: ' . implode(', ', Constants::ORDER_TYPES)
            );
        }
    }

    protected function validateTransactionType(string $transactionType): void
    {
        if (!in_array($transactionType, Constants::TRANSACTION_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Invalid transaction type: ' . $transactionType . '. Valid types: ' . implode(', ', Constants::TRANSACTION_TYPES)
            );
        }
    }

    protected function validateProduct(string $product): void
    {
        if (!in_array($product, Constants::PRODUCTS, true)) {
            throw new \InvalidArgumentException(
                'Invalid product: ' . $product . '. Valid products: ' . implode(', ', Constants::PRODUCTS)
            );
        }
    }

    protected function validateValidity(string $validity): void
    {
        if (!in_array($validity, Constants::VALIDITIES, true)) {
            throw new \InvalidArgumentException(
                'Invalid validity: ' . $validity . '. Valid types: ' . implode(', ', Constants::VALIDITIES)
            );
        }
    }

    protected function validateSegment(string $segment): void
    {
        if (!in_array($segment, Constants::SEGMENTS, true)) {
            throw new \InvalidArgumentException(
                'Invalid segment: ' . $segment . '. Valid segments: ' . implode(', ', Constants::SEGMENTS)
            );
        }
    }

    protected function validateExchange(string $exchange): void
    {
        if (!in_array($exchange, Constants::EXCHANGES, true)) {
            throw new \InvalidArgumentException(
                'Invalid exchange: ' . $exchange . '. Valid exchanges: ' . implode(', ', Constants::EXCHANGES)
            );
        }
    }

    /**
     * @param mixed $price
     */
    protected function validatePrice($price, string $fieldName = 'price'): void
    {
        if (!is_numeric($price) || $price < 0) {
            throw new \InvalidArgumentException("{$fieldName} must be a non-negative number");
        }
    }

    /**
     * @param mixed $quantity
     */
    protected function validateQuantity($quantity): void
    {
        if (!is_numeric($quantity) || $quantity <= 0 || floor((float) $quantity) != $quantity) {
            throw new \InvalidArgumentException('Quantity must be a positive integer');
        }
    }
}
