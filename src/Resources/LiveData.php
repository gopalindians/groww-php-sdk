<?php

namespace Groww\API\Resources;

use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

class LiveData extends Resource
{
    /**
     * GET /live-data/quote
     *
     * @throws GrowwApiException
     */
    public function quote(string $exchange, string $segment, string $tradingSymbol): array
    {
        $this->validateExchange($exchange);
        $this->validateSegment($segment);
        if ($tradingSymbol === '') {
            throw new \InvalidArgumentException('Trading symbol cannot be empty');
        }

        $response = $this->client->get('/live-data/quote', [
            'exchange' => $exchange,
            'segment' => $segment,
            'trading_symbol' => $tradingSymbol,
        ]);
        return $this->extractPayload($response);
    }

    /**
     * GET /live-data/ltp
     *
     * @param array $exchangeSymbols e.g. ['NSE_RELIANCE', 'BSE_SENSEX'] (max 50)
     * @throws GrowwApiException
     */
    public function ltp(string $segment, array $exchangeSymbols): array
    {
        $this->validateSegment($segment);
        $this->validateExchangeSymbols($exchangeSymbols);

        $response = $this->client->get('/live-data/ltp', [
            'segment' => $segment,
            'exchange_symbols' => implode(',', $exchangeSymbols),
        ]);
        return $this->extractPayload($response);
    }

    /**
     * GET /live-data/ohlc
     *
     * @param array $exchangeSymbols e.g. ['NSE_RELIANCE', 'BSE_SENSEX'] (max 50)
     * @throws GrowwApiException
     */
    public function ohlc(string $segment, array $exchangeSymbols): array
    {
        $this->validateSegment($segment);
        $this->validateExchangeSymbols($exchangeSymbols);

        $response = $this->client->get('/live-data/ohlc', [
            'segment' => $segment,
            'exchange_symbols' => implode(',', $exchangeSymbols),
        ]);
        return $this->extractPayload($response);
    }

    /**
     * GET /option-chain/exchange/{exchange}/underlying/{underlying}
     *
     * @throws GrowwApiException
     */
    public function optionChain(string $exchange, string $underlying, string $expiryDate): array
    {
        $this->validateExchange($exchange);
        if ($underlying === '') {
            throw new \InvalidArgumentException('Underlying cannot be empty');
        }
        if ($expiryDate === '') {
            throw new \InvalidArgumentException('Expiry date cannot be empty');
        }

        $response = $this->client->get(
            "/option-chain/exchange/{$exchange}/underlying/{$underlying}",
            ['expiry_date' => $expiryDate]
        );
        return $this->extractPayload($response);
    }

    /**
     * GET /live-data/greeks/exchange/{exchange}/underlying/{underlying}/trading_symbol/{trading_symbol}/expiry/{expiry}
     *
     * @throws GrowwApiException
     */
    public function greeks(string $exchange, string $underlying, string $tradingSymbol, string $expiry): array
    {
        $this->validateExchange($exchange);
        if ($underlying === '' || $tradingSymbol === '' || $expiry === '') {
            throw new \InvalidArgumentException('Exchange, underlying, trading symbol and expiry are required');
        }

        $response = $this->client->get(
            "/live-data/greeks/exchange/{$exchange}/underlying/{$underlying}/trading_symbol/{$tradingSymbol}/expiry/{$expiry}"
        );
        return $this->extractPayload($response);
    }

    protected function validateSegment(string $segment): void
    {
        if (!in_array($segment, Constants::SEGMENTS, true)) {
            throw new \InvalidArgumentException('Invalid segment: ' . $segment);
        }
    }

    protected function validateExchange(string $exchange): void
    {
        if (!in_array($exchange, Constants::EXCHANGES, true)) {
            throw new \InvalidArgumentException('Invalid exchange: ' . $exchange);
        }
    }

    protected function validateExchangeSymbols(array $exchangeSymbols): void
    {
        if ($exchangeSymbols === []) {
            throw new \InvalidArgumentException('At least one exchange_symbol is required');
        }
        if (count($exchangeSymbols) > 50) {
            throw new \InvalidArgumentException('A maximum of 50 exchange_symbols is supported per request');
        }
    }
}
