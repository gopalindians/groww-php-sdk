<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Groww\API\Client;
use Groww\API\Exceptions\GrowwApiException;

// Replace with your actual API key
$apiKey = 'your_api_key_here';

// Initialize the Groww API client
$groww = new Client($apiKey);

try {
    // Example 1: Search for instruments
    echo "Searching for RELIANCE...\n";
    $searchResults = $groww->instruments()->search('RELIANCE');
    print_r($searchResults);
    
    // Example 2: Get available margin
    echo "\nChecking available margin...\n";
    $margin = $groww->margin()->available();
    print_r($margin);
    
    // Example 3: Get holdings in portfolio
    echo "\nFetching portfolio holdings...\n";
    $holdings = $groww->portfolio()->holdings();
    print_r($holdings);
    
    // Example 4: Place a market order
    echo "\nPlacing a market order for RELIANCE...\n";
    $orderData = [
        'validity' => 'DAY',
        'exchange' => 'NSE',
        'transaction_type' => 'BUY',
        'order_type' => 'MARKET',
        'price' => 0,
        'product' => 'CNC',
        'quantity' => 1,
        'segment' => 'CASH',
        'trading_symbol' => 'RELIANCE-EQ'
    ];
    
    // Uncomment the following line to actually place the order
    // $orderResult = $groww->orders()->create($orderData);
    // print_r($orderResult);
    
    // Example 5: Get historical data
    echo "\nFetching historical data for RELIANCE...\n";
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = date('Y-m-d');
    
    $historicalData = $groww->historicalData()->candles(
        'RELIANCE-EQ',
        '1d',
        $startDate,
        $endDate
    );
    print_r($historicalData);
    
} catch (GrowwApiException $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getErrorCode() . "\n";
} catch (Exception $e) {
    echo "Generic error: " . $e->getMessage() . "\n";
} 