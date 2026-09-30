<?php

namespace Groww\API\Resources;

use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

class Portfolio extends Resource
{
    /**
     * GET /holdings/user
     *
     * @throws GrowwApiException
     */
    public function holdings(): array
    {
        $response = $this->client->get('/holdings/user');
        return $this->extractPayload($response);
    }

    /**
     * GET /positions/user
     *
     * @throws GrowwApiException
     */
    public function positions(array $params = []): array
    {
        if (isset($params['segment']) && !in_array($params['segment'], Constants::SEGMENTS, true)) {
            throw new \InvalidArgumentException('Invalid segment: ' . $params['segment']);
        }

        $response = $this->client->get('/positions/user', $params);
        return $this->extractPayload($response);
    }

    /**
     * GET /positions/trading-symbol
     *
     * @throws GrowwApiException
     */
    public function positionForSymbol(string $tradingSymbol, string $segment = Constants::SEGMENT_CASH): array
    {
        if ($tradingSymbol === '') {
            throw new \InvalidArgumentException('Trading symbol cannot be empty');
        }
        if (!in_array($segment, Constants::SEGMENTS, true)) {
            throw new \InvalidArgumentException('Invalid segment: ' . $segment);
        }

        $response = $this->client->get('/positions/trading-symbol', [
            'trading_symbol' => $tradingSymbol,
            'segment' => $segment,
        ]);
        return $this->extractPayload($response);
    }
}
