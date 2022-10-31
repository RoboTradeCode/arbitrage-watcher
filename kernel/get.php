<?php

use ccxt\pro\Exchange;
use Src\DB;

require_once dirname(__DIR__) . '/index.php';

DB::connect();

$memcached = new Memcached();
$memcached->addServer('localhost', 11211);

$config = require_once dirname(__DIR__) . '/config/arbitrage.config.php';

$symbol = $config['symbol'];

$keys = [];
foreach (Exchange::$exchanges as $exchange)
    $keys[] = $exchange . '_orderbook_' . $symbol;

$data = $memcached->getMulti($keys);

print_r($data); echo PHP_EOL; die();
