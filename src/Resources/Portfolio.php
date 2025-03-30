<?php

namespace Groww\API\Resources;

use Groww\API\Exceptions\GrowwApiException;

class Portfolio extends Resource
{
    /**
     * Get holdings in portfolio
     *
     * @return array Holdings data
     * @throws GrowwApiException
     */
    public function holdings(): array
    {
        $response = $this->client->get('/portfolio/holdings');
        return $this->extractPayload($response);
    }

    /**
     * Get positions in portfolio
     *
     * @param array $params Optional filter parameters
     * @return array Positions data
     * @throws GrowwApiException
     */
    public function positions(array $params = []): array
    {
        $response = $this->client->get('/portfolio/positions', $params);
        return $this->extractPayload($response);
    }

    /**
     * Get detailed holdings for a specific symbol
     *
     * @param string $symbolIsin ISIN code of the symbol
     * @return array Holdings data for the symbol
     * @throws GrowwApiException
     */
    public function holdingDetails(string $symbolIsin): array
    {
        $response = $this->client->get('/portfolio/holding/detail', [
            'symbolIsin' => $symbolIsin
        ]);
        return $this->extractPayload($response);
    }

    /**
     * Convert position between different product types
     * 
     * @param array $conversionData Conversion details
     * @return array Conversion result
     * @throws GrowwApiException
     */
    public function convertPosition(array $conversionData): array
    {
        $response = $this->client->post('/portfolio/position/convert', $conversionData);
        return $this->extractPayload($response);
    }

    /**
     * Get profit and loss summary for the portfolio
     *
     * @param array $params Optional filter parameters
     * @return array Profit and loss data
     * @throws GrowwApiException
     */
    public function pnlSummary(array $params = []): array
    {
        $response = $this->client->get('/portfolio/pnl/summary', $params);
        return $this->extractPayload($response);
    }
} 