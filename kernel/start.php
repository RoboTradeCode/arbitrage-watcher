<?php

use ccxt\pro\Exchange;
use Src\Pm2;

require dirname(__DIR__) . '/index.php';

$config = require_once dirname(__DIR__) . '/config/arbitrage.config.php';

$symbol = $config['symbol'];
$not_get_exchanges = $config['not_get_exchanges'];

foreach (array_diff(Exchange::$exchanges, $not_get_exchanges) as $exchange) {
    Pm2::start(__DIR__ . '/watch_orderbook.php', 'Orderbook record ' . $symbol . ' ' . $exchange, 'record_orderbook', [$exchange, $symbol]);
    echo '[' . date('Y-m-d H:i:s') . '] Start: ' . $symbol . ' ' . $exchange . PHP_EOL;
}
