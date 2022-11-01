<?php

namespace Src;

use PDO;
use PDOException;

class DB
{
    private static PDO $connect;

    public static function connect(): void
    {
        $db = require_once CONFIG . '/db.config.php';

        try {
            $dbh = new PDO(
                'mysql:host=' . $db['host'] . ';port=' . $db['port'] . ';dbname=' . $db['db'],
                $db['user'],
                $db['password'],
                [PDO::ATTR_PERSISTENT => true]
            );

            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo '[' . date('Y-m-d H:i:s') . '] [ERROR] Can not connect to db. Message: ' . $e->getMessage() . PHP_EOL;

            die();
        }

        self::$connect = $dbh;
    }

    public static function createTable(string $exchange): void
    {
        $sth = self::$connect->prepare(
            'CREATE TABLE IF NOT EXISTS  `orderbooks_' . $exchange . '` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `orderbook` JSON NOT NULL, `exchange_time` DATETIME NULL, `core_time` DATETIME NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE);',
        );

        $sth->execute();
    }

    public static function insertOrderbook(string $exchange, array $orderbook, string $exchange_time = null, string $core_time = null): void
    {
        self::insert(
            'orderbooks_' . $exchange,
            [
                'orderbook' => json_encode($orderbook),
                'exchange_time' => $exchange_time,
                'core_time' => $core_time
            ]
        );
    }

    private static function insert(string $table, array $columns_and_values): void
    {
        $columns = array_keys($columns_and_values);

        $sth = self::$connect->prepare(
            sprintf(
            /** @lang sql */ 'INSERT INTO `%s` (`%s`) VALUES (:%s)',
                $table,
                implode('`, `', $columns),
                implode(', :', $columns)
            )
        );

        $sth->execute($columns_and_values);
    }
}