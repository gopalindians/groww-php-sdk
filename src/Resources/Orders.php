<?php

namespace Groww\API\Resources;

use Groww\API\Exceptions\GrowwApiException;

class Orders extends Resource
{
    /**
     * List of valid order types
     */
    const ORDER_TYPES = ['MARKET', 'LIMIT', 'SL', 'SL-M'];
    
    /**
     * List of valid transaction types
     */
    const TRANSACTION_TYPES = ['BUY', 'SELL'];
    
    /**
     * List of valid product types
     */
    const PRODUCT_TYPES = ['CNC', 'MIS', 'NRML'];
    
    /**
     * List of valid validity types
     */
    const VALIDITY_TYPES = ['DAY', 'IOC', 'GTC'];
    
    /**
     * List of valid segments
     */
    const SEGMENTS = ['CASH', 'FNO', 'CURRENCY', 'COMMODITY'];

    /**
     * Create a new order
     *
     * @param array $orderData Order data
     * @return array Created order details
     * @throws GrowwApiException
     */
    public function create(array $orderData): array
    {
        $this->validateOrderData($orderData);
        $response = $this->client->post('/order/create', $orderData);
        return $this->extractPayload($response);
    }

    /**
     * Get order details by Groww order ID
     *
     * @param string $growwOrderId Groww order ID
     * @param string $segment Market segment (e.g., CASH)
     * @return array Order details
     * @throws GrowwApiException
     */
    public function details(string $growwOrderId, string $segment = 'CASH'): array
    {
        if (empty($growwOrderId)) {
            throw new \InvalidArgumentException('Order ID cannot be empty');
        }
        
        $this->validateSegment($segment);
        
        $response = $this->client->get("/order/detail/{$growwOrderId}", [
            'segment' => $segment
        ]);
        return $this->extractPayload($response);
    }

    /**
     * Get all orders
     *
     * @param array $params Optional filter parameters
     * @return array Orders list
     * @throws GrowwApiException
     */
    public function getAll(array $params = []): array
    {
        $response = $this->client->get('/orders', $params);
        return $this->extractPayload($response);
    }

    /**
     * Cancel an order
     *
     * @param string $growwOrderId Groww order ID
     * @param string $segment Market segment (e.g., CASH)
     * @return array Cancellation result
     * @throws GrowwApiException
     */
    public function cancel(string $growwOrderId, string $segment = 'CASH'): array
    {
        if (empty($growwOrderId)) {
            throw new \InvalidArgumentException('Order ID cannot be empty');
        }
        
        $this->validateSegment($segment);
        
        $response = $this->client->post('/order/cancel', [
            'groww_order_id' => $growwOrderId,
            'segment' => $segment
        ]);
        return $this->extractPayload($response);
    }

    /**
     * Modify an existing order
     *
     * @param string $growwOrderId Groww order ID
     * @param array $modificationData Data to modify
     * @return array Modified order details
     * @throws GrowwApiException
     */
    public function modify(string $growwOrderId, array $modificationData): array
    {
        if (empty($growwOrderId)) {
            throw new \InvalidArgumentException('Order ID cannot be empty');
        }
        
        // Validate modify data
        if (isset($modificationData['order_type'])) {
            $this->validateOrderType($modificationData['order_type']);
        }
        
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
    
    /**
     * Validate order data
     *
     * @param array $orderData
     * @throws \InvalidArgumentException
     */
    protected function validateOrderData(array $orderData): void
    {
        // Required fields
        $requiredFields = [
            'trading_symbol', 'exchange', 'transaction_type',
            'order_type', 'quantity', 'product', 'validity', 'segment'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($orderData[$field]) || empty($orderData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        // Validate field values
        $this->validateOrderType($orderData['order_type']);
        $this->validateTransactionType($orderData['transaction_type']);
        $this->validateProduct($orderData['product']);
        $this->validateValidity($orderData['validity']);
        $this->validateSegment($orderData['segment']);
        $this->validateQuantity($orderData['quantity']);
        
        // Validate price for limit orders
        if ($orderData['order_type'] === 'LIMIT' || $orderData['order_type'] === 'SL') {
            if (!isset($orderData['price'])) {
                throw new \InvalidArgumentException("Price is required for LIMIT and SL order types");
            }
            $this->validatePrice($orderData['price']);
        }
        
        // Validate trigger price for stop loss orders
        if ($orderData['order_type'] === 'SL' || $orderData['order_type'] === 'SL-M') {
            if (!isset($orderData['trigger_price'])) {
                throw new \InvalidArgumentException("Trigger price is required for SL and SL-M order types");
            }
            $this->validatePrice($orderData['trigger_price'], 'trigger_price');
        }
    }
    
    /**
     * Validate order type
     *
     * @param string $orderType
     * @throws \InvalidArgumentException
     */
    protected function validateOrderType(string $orderType): void
    {
        if (!in_array($orderType, self::ORDER_TYPES)) {
            throw new \InvalidArgumentException(
                "Invalid order type: {$orderType}. Valid types: " . implode(', ', self::ORDER_TYPES)
            );
        }
    }
    
    /**
     * Validate transaction type
     *
     * @param string $transactionType
     * @throws \InvalidArgumentException
     */
    protected function validateTransactionType(string $transactionType): void
    {
        if (!in_array($transactionType, self::TRANSACTION_TYPES)) {
            throw new \InvalidArgumentException(
                "Invalid transaction type: {$transactionType}. Valid types: " . implode(', ', self::TRANSACTION_TYPES)
            );
        }
    }
    
    /**
     * Validate product type
     *
     * @param string $product
     * @throws \InvalidArgumentException
     */
    protected function validateProduct(string $product): void
    {
        if (!in_array($product, self::PRODUCT_TYPES)) {
            throw new \InvalidArgumentException(
                "Invalid product: {$product}. Valid products: " . implode(', ', self::PRODUCT_TYPES)
            );
        }
    }
    
    /**
     * Validate validity type
     *
     * @param string $validity
     * @throws \InvalidArgumentException
     */
    protected function validateValidity(string $validity): void
    {
        if (!in_array($validity, self::VALIDITY_TYPES)) {
            throw new \InvalidArgumentException(
                "Invalid validity: {$validity}. Valid types: " . implode(', ', self::VALIDITY_TYPES)
            );
        }
    }
    
    /**
     * Validate segment
     *
     * @param string $segment
     * @throws \InvalidArgumentException
     */
    protected function validateSegment(string $segment): void
    {
        if (!in_array($segment, self::SEGMENTS)) {
            throw new \InvalidArgumentException(
                "Invalid segment: {$segment}. Valid segments: " . implode(', ', self::SEGMENTS)
            );
        }
    }
    
    /**
     * Validate price
     *
     * @param mixed $price
     * @param string $fieldName
     * @throws \InvalidArgumentException
     */
    protected function validatePrice($price, string $fieldName = 'price'): void
    {
        if (!is_numeric($price) || $price < 0) {
            throw new \InvalidArgumentException("{$fieldName} must be a non-negative number");
        }
    }
    
    /**
     * Validate quantity
     *
     * @param mixed $quantity
     * @throws \InvalidArgumentException
     */
    protected function validateQuantity($quantity): void
    {
        if (!is_numeric($quantity) || $quantity <= 0 || floor($quantity) != $quantity) {
            throw new \InvalidArgumentException("Quantity must be a positive integer");
        }
    }
} 