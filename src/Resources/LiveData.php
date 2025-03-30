<?php

namespace Groww\API\Resources;

use Groww\API\Exceptions\GrowwApiException;

class LiveData extends Resource
{
    /**
     * Get market depth for a symbol
     *
     * @param string $tradingSymbol Trading symbol
     * @param string $exchange Exchange (e.g., NSE, BSE)
     * @return array Market depth data
     * @throws GrowwApiException
     */
    public function marketDepth(string $tradingSymbol, string $exchange = 'NSE'): array
    {
        $response = $this->client->get('/market/depth', [
            'trading_symbol' => $tradingSymbol,
            'exchange' => $exchange
        ]);
        
        return $this->extractPayload($response);
    }

    /**
     * Get quotes for multiple symbols
     *
     * @param array $symbols Array of trading symbols
     * @param string $exchange Exchange (e.g., NSE, BSE)
     * @return array Quotes data
     * @throws GrowwApiException
     */
    public function quotes(array $symbols, string $exchange = 'NSE'): array
    {
        $symbolString = implode(',', $symbols);
        
        $response = $this->client->get('/quotes', [
            'trading_symbols' => $symbolString,
            'exchange' => $exchange
        ]);
        
        return $this->extractPayload($response);
    }

    /**
     * Get last traded price for symbols
     *
     * @param array $symbols Array of trading symbols
     * @param string $exchange Exchange (e.g., NSE, BSE)
     * @return array LTP data
     * @throws GrowwApiException
     */
    public function ltp(array $symbols, string $exchange = 'NSE'): array
    {
        $symbolString = implode(',', $symbols);
        
        $response = $this->client->get('/ltp', [
            'trading_symbols' => $symbolString,
            'exchange' => $exchange
        ]);
        
        return $this->extractPayload($response);
    }

    /**
     * Get indices data
     *
     * @param array $params Filter parameters
     * @return array Indices data
     * @throws GrowwApiException
     */
    public function indices(array $params = []): array
    {
        $response = $this->client->get('/indices', $params);
        return $this->extractPayload($response);
    }
} 