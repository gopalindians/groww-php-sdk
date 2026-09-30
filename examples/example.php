<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Groww\API\Client;
use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

$accessToken = 'your_access_token';
$groww = new Client($accessToken);

// $groww = Client::fromApproval('your_api_key', 'your_api_secret');
// $groww = Client::fromTotp('your_api_key', '123456');

try {
    echo "User profile...\n";
    print_r($groww->user()->detail());

    echo "\nHoldings...\n";
    print_r($groww->portfolio()->holdings());

    echo "\nUser margin...\n";
    print_r($groww->margin()->user());

    echo "\nLTP...\n";
    print_r($groww->liveData()->ltp(Constants::SEGMENT_CASH, ['NSE_RELIANCE']));

    echo "\nHistorical candles...\n";
    print_r($groww->historicalData()->candles(
        Constants::EXCHANGE_NSE,
        Constants::SEGMENT_CASH,
        'NSE-RELIANCE',
        date('Y-m-d') . ' 09:15:00',
        date('Y-m-d') . ' 15:30:00',
        '1day'
    ));

    $orderData = [
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
    ];

    // Uncomment to place a live order
    // print_r($groww->orders()->create($orderData));
} catch (GrowwApiException $e) {
    echo "Error: {$e->getMessage()} (Code: {$e->getErrorCode()})\n";
}
