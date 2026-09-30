<?php

namespace Groww\API;

/**
 * Values from the Groww Trading API annexures.
 *
 * @see https://groww.in/trade-api/docs/curl/annexures
 */
class Constants
{
    public const SEGMENT_CASH = 'CASH';
    public const SEGMENT_FNO = 'FNO';

    public const SEGMENTS = [
        self::SEGMENT_CASH,
        self::SEGMENT_FNO,
    ];

    public const EXCHANGE_NSE = 'NSE';
    public const EXCHANGE_BSE = 'BSE';

    public const EXCHANGES = [
        self::EXCHANGE_NSE,
        self::EXCHANGE_BSE,
    ];

    public const ORDER_TYPE_LIMIT = 'LIMIT';
    public const ORDER_TYPE_MARKET = 'MARKET';
    public const ORDER_TYPE_SL = 'SL';
    public const ORDER_TYPE_SL_M = 'SL_M';

    public const ORDER_TYPES = [
        self::ORDER_TYPE_LIMIT,
        self::ORDER_TYPE_MARKET,
        self::ORDER_TYPE_SL,
        self::ORDER_TYPE_SL_M,
    ];

    public const PRODUCT_CNC = 'CNC';
    public const PRODUCT_MIS = 'MIS';
    public const PRODUCT_NRML = 'NRML';

    public const PRODUCTS = [
        self::PRODUCT_CNC,
        self::PRODUCT_MIS,
        self::PRODUCT_NRML,
    ];

    public const TRANSACTION_BUY = 'BUY';
    public const TRANSACTION_SELL = 'SELL';

    public const TRANSACTION_TYPES = [
        self::TRANSACTION_BUY,
        self::TRANSACTION_SELL,
    ];

    public const VALIDITY_DAY = 'DAY';

    public const VALIDITIES = [
        self::VALIDITY_DAY,
    ];

    public const SMART_ORDER_GTT = 'GTT';
    public const SMART_ORDER_OCO = 'OCO';

    public const SMART_ORDER_TYPES = [
        self::SMART_ORDER_GTT,
        self::SMART_ORDER_OCO,
    ];

    public const CANDLE_INTERVALS = [
        '1minute',
        '2minute',
        '3minute',
        '5minute',
        '10minute',
        '15minute',
        '30minute',
        '1hour',
        '4hour',
        '1day',
        '1week',
        '1month',
    ];

    public const INSTRUMENTS_CSV_URL = 'https://growwapi-assets.groww.in/instruments/instrument.csv';

    public const API_VERSION = '1.0';
}
