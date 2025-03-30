<?php

namespace Groww\API\Resources;

use Groww\API\Exceptions\GrowwApiException;

class HistoricalData extends Resource
{
    /**
     * Get historical candle data
     *
     * @param string $tradingSymbol Trading symbol
     * @param string $interval Candle interval (e.g., 1d, 1h, 15m)
     * @param string $from Start date (YYYY-MM-DD)
     * @param string $to End date (YYYY-MM-DD)
     * @param string $exchange Exchange (e.g., NSE, BSE)
     * @return array Historical candle data
     * @throws GrowwApiException
     */
    public function candles(
        string $tradingSymbol,
        string $interval,
        string $from,
        string $to,
        string $exchange = 'NSE'
    ): array {
        $response = $this->client->get('/historical/candles', [
            'trading_symbol' => $tradingSymbol,
            'interval' => $interval,
            'from' => $from,
            'to' => $to,
            'exchange' => $exchange
        ]);
        
        return $this->extractPayload($response);
    }

    /**
     * Get historical market data
     *
     * @param string $tradingSymbol Trading symbol
     * @param string $date Date (YYYY-MM-DD)
     * @param string $exchange Exchange (e.g., NSE, BSE)
     * @return array Historical market data
     * @throws GrowwApiException
     */
    public function marketData(
        string $tradingSymbol,
        string $date,
        string $exchange = 'NSE'
    ): array {
        $response = $this->client->get('/historical/market', [
            'trading_symbol' => $tradingSymbol,
            'date' => $date,
            'exchange' => $exchange
        ]);
        
        return $this->extractPayload($response);
    }

    /**
     * Get historical trade data
     *
     * @param string $tradingSymbol Trading symbol
     * @param string $date Date (YYYY-MM-DD)
     * @param string $exchange Exchange (e.g., NSE, BSE)
     * @return array Historical trade data
     * @throws GrowwApiException
     */
    public function trades(
        string $tradingSymbol,
        string $date,
        string $exchange = 'NSE'
    ): array {
        $response = $this->client->get('/historical/trades', [
            'trading_symbol' => $tradingSymbol,
            'date' => $date,
            'exchange' => $exchange
        ]);
        
        return $this->extractPayload($response);
    }

    /**
     * Get price history for analysis
     *
     * @param string $tradingSymbol Trading symbol
     * @param array $params Additional parameters
     * @return array Price history data
     * @throws GrowwApiException
     */
    public function priceHistory(string $tradingSymbol, array $params = []): array
    {
        $response = $this->client->get('/historical/price', array_merge([
            'trading_symbol' => $tradingSymbol
        ], $params));
        
        return $this->extractPayload($response);
    }
} 