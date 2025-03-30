# Groww PHP API SDK

A PHP SDK for interacting with the Groww Trading API.

## Installation

Install via Composer:

```bash
composer require groww/api-client
```

## Usage

### Authentication

```php
use Groww\API\Client;

// Initialize the client with your API key
$groww = new Client('your_api_key_here');

// Optional: Enable logging
$groww->setLogging(true, function($level, $message, $context) {
    // Custom logging implementation
    error_log("[$level] $message " . json_encode($context));
});
```

### Trading

#### Place a new order

```php
try {
    $orderData = [
        'validity' => 'DAY',
        'exchange' => 'NSE',
        'transaction_type' => 'BUY',
        'order_type' => 'MARKET',
        'price' => 0,
        'product' => 'CNC',
        'quantity' => 1,
        'segment' => 'CASH',
        'trading_symbol' => 'IDEA'
    ];
    
    $result = $groww->orders()->create($orderData);
    print_r($result);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

#### Get order details

```php
try {
    $orderDetails = $groww->orders()->details('GMK39038RDT490CCVRO');
    print_r($orderDetails);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

#### Cancel an order

```php
try {
    $result = $groww->orders()->cancel('GMK39038RDT490CCVRO');
    print_r($result);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

### Portfolio Management

#### Get holdings

```php
try {
    $holdings = $groww->portfolio()->holdings();
    print_r($holdings);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

#### Get positions

```php
try {
    $positions = $groww->portfolio()->positions();
    print_r($positions);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

### Market Data

#### Search for instruments

```php
try {
    $searchResults = $groww->instruments()->search('RELIANCE');
    print_r($searchResults);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

#### Get live quotes

```php
try {
    $quotes = $groww->liveData()->quotes(['RELIANCE', 'IDEA']);
    print_r($quotes);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

#### Get historical data

```php
try {
    $candleData = $groww->historicalData()->candles(
        'RELIANCE',
        '1d',
        '2023-01-01',
        '2023-01-31'
    );
    print_r($candleData);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

### Margin Information

```php
try {
    $availableMargin = $groww->margin()->available();
    print_r($availableMargin);
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getErrorCode() . ")";
}
```

## Error Handling

The SDK throws `GrowwApiException` when an error occurs. You can catch this exception to handle errors gracefully:

```php
try {
    // API operations
} catch (Groww\API\Exceptions\GrowwRateLimitException $e) {
    // Handle rate limiting specifically
    echo "Rate limit exceeded. Try again after " . $e->getWaitTime() . " seconds.\n";
    sleep($e->getWaitTime());
    
    // Retry the request
} catch (Groww\API\Exceptions\GrowwApiException $e) {
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getErrorCode() . "\n";
    
    // Handle different error codes
    switch ($e->getErrorCode()) {
        case 'GA001':
            echo "Bad request - check your parameters\n";
            break;
        case 'GA005':
            echo "Authentication error - check your API key\n";
            break;
        default:
            echo "Unknown error occurred\n";
            break;
    }
}
```

## Security Features

This SDK implements several security best practices:

1. **Input Validation**: All inputs are validated before being sent to the API.
2. **TLS/SSL Verification**: HTTPS connections are enforced by default.
3. **Rate Limiting Protection**: Built-in rate limiting with exponential backoff.
4. **Parameter Sanitization**: All parameters are sanitized to prevent injection attacks.
5. **Sensitive Data Protection**: API keys and other sensitive data are redacted in logs.
6. **Error Handling**: Comprehensive error handling for security-related issues.

### Secure Logging

The SDK includes a secure logging system that redacts sensitive information:

```php
// Enable logging with a custom logger
$groww->setLogging(true, function($level, $message, $context) {
    // Custom logging implementation
    // All sensitive data is automatically redacted
});
```

## API Documentation

For the full API reference, visit the Groww API documentation at: [https://groww.in/trade-api/docs/curl](https://groww.in/trade-api/docs/curl)

## License

MIT