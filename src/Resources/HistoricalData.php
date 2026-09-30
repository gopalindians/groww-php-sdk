<?php

namespace Groww\API\Resources;

use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

class HistoricalData extends Resource
{
    /**
     * GET /historical/candles
     *
     * @throws GrowwApiException
     */
    public function candles(
        string $exchange,
        string $segment,
        string $growwSymbol,
        string $startTime,
        string $endTime,
        string $candleInterval
    ): array {
        if (!in_array($exchange, Constants::EXCHANGES, true)) {
            throw new \InvalidArgumentException('Invalid exchange: ' . $exchange);
        }
        if (!in_array($segment, Constants::SEGMENTS, true)) {
            throw new \InvalidArgumentException('Invalid segment: ' . $segment);
        }
        if (!in_array($candleInterval, Constants::CANDLE_INTERVALS, true)) {
            throw new \InvalidArgumentException(
                'Invalid candle_interval. Valid values: ' . implode(', ', Constants::CANDLE_INTERVALS)
            );
        }
        if ($growwSymbol === '' || $startTime === '' || $endTime === '') {
            throw new \InvalidArgumentException('groww_symbol, start_time and end_time are required');
        }

        $response = $this->client->get('/historical/candles', [
            'exchange' => $exchange,
            'segment' => $segment,
            'groww_symbol' => $growwSymbol,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'candle_interval' => $candleInterval,
        ]);
        return $this->extractPayload($response);
    }

    /**
     * GET /historical/expiries
     *
     * @throws GrowwApiException
     */
    public function expiries(string $exchange, string $underlyingSymbol, ?int $year = null, ?int $month = null): array
    {
        if (!in_array($exchange, Constants::EXCHANGES, true)) {
            throw new \InvalidArgumentException('Invalid exchange: ' . $exchange);
        }
        if ($underlyingSymbol === '') {
            throw new \InvalidArgumentException('underlying_symbol cannot be empty');
        }

        $params = [
            'exchange' => $exchange,
            'underlying_symbol' => $underlyingSymbol,
        ];
        if ($year !== null) {
            $params['year'] = $year;
        }
        if ($month !== null) {
            $params['month'] = $month;
        }

        $response = $this->client->get('/historical/expiries', $params);
        return $this->extractPayload($response);
    }

    /**
     * GET /historical/contracts
     *
     * @throws GrowwApiException
     */
    public function contracts(string $exchange, string $underlyingSymbol, string $expiryDate): array
    {
        if (!in_array($exchange, Constants::EXCHANGES, true)) {
            throw new \InvalidArgumentException('Invalid exchange: ' . $exchange);
        }
        if ($underlyingSymbol === '' || $expiryDate === '') {
            throw new \InvalidArgumentException('underlying_symbol and expiry_date are required');
        }

        $response = $this->client->get('/historical/contracts', [
            'exchange' => $exchange,
            'underlying_symbol' => $underlyingSymbol,
            'expiry_date' => $expiryDate,
        ]);
        return $this->extractPayload($response);
    }
}
