# Groww PHP API SDK

[![Latest Stable Version](https://img.shields.io/packagist/v/gopalindians/groww-php-sdk.svg)](https://packagist.org/packages/gopalindians/groww-php-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/gopalindians/groww-php-sdk.svg)](https://packagist.org/packages/gopalindians/groww-php-sdk)
[![License](https://img.shields.io/packagist/l/gopalindians/groww-php-sdk.svg)](https://packagist.org/packages/gopalindians/groww-php-sdk)
[![PHP Version](https://img.shields.io/packagist/php-v/gopalindians/groww-php-sdk.svg)](https://packagist.org/packages/gopalindians/groww-php-sdk)
[![GitHub stars](https://img.shields.io/github/stars/gopalindians/groww-php-sdk.svg)](https://github.com/gopalindians/groww-php-sdk/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/gopalindians/groww-php-sdk.svg)](https://github.com/gopalindians/groww-php-sdk/issues)
[![Tests](https://github.com/gopalindians/groww-php-sdk/actions/workflows/php.yml/badge.svg)](https://github.com/gopalindians/groww-php-sdk/actions/workflows/php.yml)

PHP SDK for the [Groww Trading API](https://groww.in/trade-api/docs/curl). Equity (`CASH`) and derivatives (`FNO`) only.

## Installation

```bash
composer require gopalindians/groww-php-sdk
```

## Authentication

Trading calls use `Authorization: Bearer {ACCESS_TOKEN}` plus `X-API-VERSION: 1.0`.

### Access token (expires daily at 6:00 AM)

Generate a token in Groww: Profile → Settings → Trading APIs → Access Token.

```php
use Groww\API\Client;

$groww = new Client('your_access_token');
```

### API key and secret (approval checksum)

```php
use Groww\API\Client;

$groww = Client::fromApproval('your_api_key', 'your_api_secret');
```

Checksum is `sha256(secret + timestamp)` with `timestamp` in epoch seconds (valid 10 minutes).

### API key and TOTP

```php
$groww = Client::fromTotp('your_api_key', '123456');
```

## Usage

### Orders

```php
use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

$order = $groww->orders()->create([
    'validity' => Constants::VALIDITY_DAY,
    'exchange' => Constants::EXCHANGE_NSE,
    'transaction_type' => Constants::TRANSACTION_BUY,
    'order_type' => Constants::ORDER_TYPE_MARKET,
    'price' => 0,
    'product' => Constants::PRODUCT_CNC,
    'quantity' => 1,
    'segment' => Constants::SEGMENT_CASH,
    'trading_symbol' => 'IDEA',
    'order_reference_id' => 'Ab-654321234',
]);

$details = $groww->orders()->details($order['groww_order_id'], Constants::SEGMENT_CASH);
$list = $groww->orders()->list(['segment' => Constants::SEGMENT_CASH, 'page' => 0, 'page_size' => 100]);
$groww->orders()->modify($order['groww_order_id'], [
    'segment' => Constants::SEGMENT_CASH,
    'order_type' => Constants::ORDER_TYPE_LIMIT,
    'quantity' => 1,
    'price' => 10,
]);
$groww->orders()->cancel($order['groww_order_id'], Constants::SEGMENT_CASH);
```

Order type `SL_M` is the documented stop-loss market value (not `SL-M`). Validity is `DAY` only.

### Smart orders (GTT / OCO)

```php
$gtt = $groww->smartOrders()->create([
    'reference_id' => 'sref-unique-123',
    'smart_order_type' => Constants::SMART_ORDER_GTT,
    'segment' => Constants::SEGMENT_CASH,
    'trading_symbol' => 'TCS',
    'quantity' => 10,
    'trigger_price' => '3985.00',
    'trigger_direction' => 'DOWN',
    'order' => ['order_type' => 'LIMIT', 'price' => '3990.00', 'transaction_type' => 'BUY'],
    'product_type' => Constants::PRODUCT_CNC,
    'exchange' => Constants::EXCHANGE_NSE,
    'duration' => Constants::VALIDITY_DAY,
]);
```

### Portfolio and margin

```php
$holdings = $groww->portfolio()->holdings();
$positions = $groww->portfolio()->positions(['segment' => Constants::SEGMENT_CASH]);
$margin = $groww->margin()->user();
$required = $groww->margin()->forOrders(Constants::SEGMENT_CASH, [
    [
        'trading_symbol' => 'WIPRO',
        'transaction_type' => 'BUY',
        'quantity' => 1,
        'price' => 100,
        'order_type' => 'LIMIT',
        'product' => 'CNC',
        'exchange' => 'NSE',
    ],
]);
```

### Live data

```php
$quote = $groww->liveData()->quote('NSE', 'CASH', 'NIFTY');
$ltp = $groww->liveData()->ltp('CASH', ['NSE_RELIANCE', 'BSE_SENSEX']);
$ohlc = $groww->liveData()->ohlc('CASH', ['NSE_RELIANCE']);
$chain = $groww->liveData()->optionChain('NSE', 'NIFTY', '2025-10-14');
```

### Historical candles and instruments

```php
$candles = $groww->historicalData()->candles(
    'NSE',
    'CASH',
    'NSE-WIPRO',
    '2025-09-24 10:56:00',
    '2025-09-24 15:21:00',
    '5minute'
);
$instruments = $groww->instruments()->download();
$user = $groww->user()->detail();
```

Candle intervals: `1minute`, `5minute`, `1hour`, `1day`, and the rest listed in the annexures.

## Error handling

Failed responses use `{ "status": "FAILURE", "error": { "code", "message" } }` (`GA000`–`GA007`). HTTP 429 and `GA003` raise `GrowwRateLimitException`.

```php
try {
    $groww->orders()->list();
} catch (Groww\API\Exceptions\GrowwRateLimitException $e) {
    sleep($e->getWaitTime());
} catch (GrowwApiException $e) {
    echo $e->getErrorCode() . ': ' . $e->getMessage();
}
```

## Testing

```bash
composer install
./vendor/bin/phpunit
```

## API documentation

[https://groww.in/trade-api/docs/curl](https://groww.in/trade-api/docs/curl)

## License

MIT
