<?php

namespace Groww\API\Resources;

use Groww\API\Exceptions\GrowwApiException;

class Instruments extends Resource
{
    /**
     * Search for instruments
     *
     * @param string $query Search query
     * @param array $params Additional search parameters
     * @return array Search results
     * @throws GrowwApiException
     */
    public function search(string $query, array $params = []): array
    {
        $response = $this->client->get('/instruments/search', array_merge([
            'q' => $query
        ], $params));
        
        return $this->extractPayload($response);
    }

    /**
     * Get instrument details
     *
     * @param string $tradingSymbol Trading symbol of the instrument
     * @param string $exchange Exchange (e.g., NSE, BSE)
     * @return array Instrument details
     * @throws GrowwApiException
     */
    public function details(string $tradingSymbol, string $exchange = 'NSE'): array
    {
        $response = $this->client->get('/instruments/detail', [
            'trading_symbol' => $tradingSymbol,
            'exchange' => $exchange
        ]);
        
        return $this->extractPayload($response);
    }

    /**
     * Get instrument details by ISIN
     *
     * @param string $isin ISIN code of the instrument
     * @return array Instrument details
     * @throws GrowwApiException
     */
    public function detailsByIsin(string $isin): array
    {
        $response = $this->client->get('/instruments/detail/isin', [
            'isin' => $isin
        ]);
        
        return $this->extractPayload($response);
    }

    /**
     * Get all available exchanges
     *
     * @return array List of exchanges
     * @throws GrowwApiException
     */
    public function exchanges(): array
    {
        $response = $this->client->get('/instruments/exchanges');
        return $this->extractPayload($response);
    }

    /**
     * Get all available segments
     *
     * @return array List of segments
     * @throws GrowwApiException
     */
    public function segments(): array
    {
        $response = $this->client->get('/instruments/segments');
        return $this->extractPayload($response);
    }
} 