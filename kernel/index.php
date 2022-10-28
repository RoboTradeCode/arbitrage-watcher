<?php

use ccxt\pro\binance;

require_once dirname(__DIR__) . '/index.php';

$exchange = new binance();

$symbol = 'BTC/USDT';
$limit = 5;

if ($exchange->has['watchOrderBook']) {
    $exchange::execute_and_run(function() use ($exchange, $symbol, $limit) {
        while (true) {
            try {
                $orderbook = yield $exchange->watch_order_book($symbol, $limit);
                echo date('c'), ' ', $symbol, ' ', json_encode(array($orderbook['asks'][0], $orderbook['bids'][0])), "\n";
            } catch (Exception $e) {
                echo get_class($e), ' ', $e->getMessage(), "\n";
            }
        }
    });
}
