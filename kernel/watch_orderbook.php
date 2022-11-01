<?php

use Src\DB;

require_once dirname(__DIR__) . '/index.php';

if (!isset($argv[1]))
    die('Set parameter: exchange');

if (!isset($argv[2]))
    die('Set parameter: symbol');

$exchange_name = $argv[1];
$symbol = $argv[1];

$exchange_class = "\\ccxt\\pro\\" . $exchange_name;

$exchange = new $exchange_class();

$symbol = 'BTC/USDT';

if ($exchange->has['watchOrderBook']) {
    $exchange::execute_and_run(function () use ($exchange, $symbol) {
        DB::connect();

        $name = $exchange->id;

        DB::createTable($name);

        while (true) {
            try {
                $start_time_before_get_orderbooks = microtime(true);

                $orderbook = yield $exchange->watch_order_book($symbol);
                $orderbook = (array) $orderbook;
                $orderbook['asks'] = (array) $orderbook['asks'];
                $orderbook['bids'] = (array) $orderbook['bids'];

                $orderbook['asks'] = array_slice($orderbook['asks'], 1, 10);
                $orderbook['bids'] = array_slice($orderbook['bids'], 1, 10);
                $orderbook['core_timestamp'] = microtime(true);

                $end_time_before_get_orderbooks = microtime(true);

                DB::insertOrderbook($name, $orderbook, $orderbook['timestamp'] ? date('Y-m-d H:i:s', $orderbook['timestamp'] / 1000) : null, date('Y-m-d H:i:s',  $orderbook['core_timestamp']));

                echo '[' . date('Y-m-d H:i:s') . '] Time get orderbook: ' . ($end_time_before_get_orderbooks - $start_time_before_get_orderbooks) . '. Time record to db: ' , (microtime(true) - $end_time_before_get_orderbooks) . PHP_EOL;
            } catch (Exception $e) {
                echo get_class($e), ' ', $e->getMessage(), "\n";
            }
        }
    });
} else {
    echo '[' . date('Y-m-d H:i:s') . '] [ERROR] ' . $exchange . ' has no websockets on orderbook' . PHP_EOL;
}