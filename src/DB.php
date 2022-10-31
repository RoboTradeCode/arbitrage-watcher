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
}