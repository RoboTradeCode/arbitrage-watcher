<?php

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
        $memcached = new Memcached();
        $memcached->addServer('localhost', 11211);

        while (true) {
            try {
                $orderbook = yield $exchange->watch_order_book($symbol);
                $orderbook = (array) $orderbook;
                $orderbook['asks'] = (array) $orderbook['asks'];
                $orderbook['bids'] = (array) $orderbook['bids'];

                $orderbook['asks'] = array_slice($orderbook['asks'], 1, 5);
                $orderbook['bids'] = array_slice($orderbook['bids'], 1, 5);
                $orderbook['core_timestamp'] = microtime(true);
                $memcached->set($exchange->id . '_orderbook_' . $symbol, $orderbook);
            } catch (Exception $e) {
                echo get_class($e), ' ', $e->getMessage(), "\n";
            }
        }
    });
} else {
    echo '[' . date('Y-m-d H:i:s') . '] [ERROR] ' . $exchange . ' has no websockets on orderbook' . PHP_EOL;
}