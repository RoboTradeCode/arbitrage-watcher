<?php

use ccxt\pro\Exchange;
use Src\DB;

require_once dirname(__DIR__) . '/index.php';

$config = require_once dirname(__DIR__) . '/config/arbitrage.config.php';

$symbol = $config['symbol'];
$not_get_exchanges = $config['not_get_exchanges'];

DB::connect();

foreach (array_diff(Exchange::$exchanges, $not_get_exchanges) as $exchange)
    DB::createLiquidityTable($exchange);

$liquidities = [
    'one_hundred' => 100,
    'two_hundred' => 200,
    'five_hundred' => 500,
    'thousand' => 1000
];

while (true) {
    sleep(1);

    foreach (array_diff(Exchange::$exchanges, $not_get_exchanges) as $exchange) {
        $liquidity_id = DB::getMaxLiquidityTableId($exchange) ?? 0;

        $orderbooks = array_filter(
            DB::selectOrderbooksByIdAndHaving($exchange, $liquidity_id,  100),
            fn($orderbook) => $orderbook['id'] != $liquidity_id
        );

        if ($orderbooks) {
            foreach ($orderbooks as $record_orderbook) {
                $id = $record_orderbook['id'];
                $orderbook = json_decode($record_orderbook['orderbook'], true);

                foreach ($liquidities as $name => $liquidity) {
                    $counting = [
                        'bid' => [
                            'base' => null,
                            'price' => null
                        ],
                        'ask' => [
                            'base' => null,
                            'price' => null
                        ]
                    ];

                    $quote = $liquidity;
                    $base = 0;
                    foreach ($orderbook['asks'] as $price_and_amount) {
                        list($price, $amount) = $price_and_amount;

                        if ($amount * $price < $quote) {
                            $quote -= $amount * $price;

                            $base += $amount;
                        } else {
                            $base += $quote / $price;

                            $counting['ask'] = [
                                'base' => round($base, 8),
                                'price' => round($liquidity / $base, 2)
                            ];

                            break;
                        }
                    }

                    $quote = $liquidity;
                    $base = 0;
                    foreach ($orderbook['bids'] as $price_and_amount) {
                        list($price, $amount) = $price_and_amount;

                        if ($amount * $price < $quote) {
                            $quote -= $amount * $price;

                            $base += $amount;
                        } else {
                            $base += $quote / $price;

                            $counting['bid'] = [
                                'base' => round($base, 8),
                                'price' => round($liquidity / $base, 2)
                            ];

                            break;
                        }
                    }

                    $liquidity_data[$name] = $counting;
                }

                DB::insertResultLiquidity($exchange, $id, $liquidity_data['one_hundred'], $liquidity_data['two_hundred'], $liquidity_data['five_hundred'], $liquidity_data['thousand']);
            }

            echo '[' . date('Y-m-d H:i:s') . '] Insert liquidity for ' . $exchange . ' Id: '. ($id ?? 'null') . PHP_EOL;
        } else
            echo '[' . date('Y-m-d H:i:s') . '] Liquidity for ' . $exchange . ' full updated' . PHP_EOL;
    }

    echo '[' . date('Y-m-d H:i:s') . '] Again' . PHP_EOL;
}