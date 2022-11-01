<?php

namespace Src;

use ccxt\pro\Exchange;

class MemcachedData
{

    private int $expired_orderbook_time;
    public array $keys;

    public function __construct(string $symbol, int $expired_orderbook_time)
    {
        $this->expired_orderbook_time = $expired_orderbook_time;
        $this->keys = $this->getAllMemcachedKeys($symbol);
    }

    public function reformatAndSeparateData(array $memcached_data): array
    {
        $microtime = microtime(true);

        foreach ($memcached_data as $key => $data)
            if (isset($data)) {
                $parts = explode('_', $key);

                $exchange = $parts[0];
                $action = $parts[1];
                $value = $parts[2] ?? null;

                if ($action == 'orderbook' && $value) {
                    if (($microtime - $data['core_timestamp']) <= $this->expired_orderbook_time / 1000000) {
                        $orderbooks[$value][$exchange] = $data;
                    }
                } else
                    $undefined[$key] = $data;
            }

        return [
            'orderbooks' => $orderbooks ?? [],
            'undefined' => $undefined ?? []
        ];
    }

    private function getAllMemcachedKeys(string $symbol): array
    {
        foreach (Exchange::$exchanges as $exchange)
            $keys[] = $exchange . '_orderbook_' . $symbol;

        return $keys ?? [];
    }

}