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
            'CREATE TABLE IF NOT EXISTS `orderbooks_' . $exchange . '` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `orderbook` JSON NOT NULL, `microtime` DECIMAL(25,8) NOT NULL, `exchange_time` DATETIME NULL, `core_time` DATETIME NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE);',
        );

        $sth->execute();
    }

    public static function insertOrderbook(string $exchange, array $orderbook, float $core_time, string $exchange_time = null): void
    {
        self::insert(
            'orderbooks_' . $exchange,
            [
                'orderbook' => json_encode($orderbook),
                'microtime' => $core_time,
                'exchange_time' => $exchange_time,
                'core_time' => date('Y-m-d H:i:s',  $core_time)
            ]
        );
    }

    public static function createLiquidityTable(string $exchange): void
    {
        $sth = self::$connect->prepare(
            sprintf(
            /** @lang sql */ 'CREATE TABLE IF NOT EXISTS `result_liquidity_%1$s` (`id` int unsigned NOT NULL AUTO_INCREMENT, `orderbooks_id` int unsigned NOT NULL, `one_hundred` json DEFAULT NULL, `two_hundred` json DEFAULT NULL, `five_hundred` json DEFAULT NULL, `thousand` json DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `id_UNIQUE` (`id`), UNIQUE KEY `orderbooks_id_UNIQUE` (`orderbooks_id`), CONSTRAINT `result_liquidity_%1$s_ibfk_1` FOREIGN KEY (`orderbooks_id`) REFERENCES `orderbooks_%1$s` (`id`) ON DELETE CASCADE) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;',
                $exchange,
            )
        );

        $sth->execute();
    }

    public static function getMaxLiquidityTableId(string $exchange): int|null
    {
        $sth = self::$connect->prepare(
            sprintf(
            /** @lang sql */ 'SELECT max(orderbooks_id) as id FROM `result_liquidity_%s` LIMIT 1;',
                $exchange,
            )
        );

        $sth->execute();

        return $sth->fetch(PDO::FETCH_ASSOC)['id'];
    }

    public static function selectOrderbooksByIdAndHaving(string $exchange, int $id, int $limit = 1000): array
    {
        $sth = self::$connect->prepare(
            sprintf(
            /** @lang sql */ 'SELECT `id`, `orderbook` FROM `orderbooks_%s` WHERE `id` >= :id GROUP BY `created_at` HAVING COUNT(*) >= 1 LIMIT %s',
                $exchange,
                $limit
            )
        );

        $sth->execute(['id' => $id]);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insertResultLiquidity(string $exchange, int $orderbooks_id, array $one_hundred, array $two_hundred, array $five_hundred, array $thousand): void
    {
        self::insert(
            'result_liquidity_' . $exchange,
            [
                'orderbooks_id' => $orderbooks_id,
                'one_hundred' => json_encode($one_hundred),
                'two_hundred' => json_encode($two_hundred),
                'five_hundred' => json_encode($five_hundred),
                'thousand' => json_encode($thousand),
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