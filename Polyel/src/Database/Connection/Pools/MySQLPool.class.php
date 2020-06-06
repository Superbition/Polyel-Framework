<?php

namespace Polyel\Database\Connection\Pools;

use PDO;
use PDOException;
use Polyel\Database\Connection\ConnectionPool;

class MySQLPool extends ConnectionPool
{
    private $dbName;

    public function __construct(string $dbname, int $min, int $max)
    {
        parent::__construct($min, $max);

        $this->dbName = $dbname;
    }

    public function createConnection()
    {
        $host = config("database.connections.mysql.databases.$this->dbName.host");
        $port = config("database.connections.mysql.databases.$this->dbName.port");
        $db = config("database.connections.mysql.databases.$this->dbName.database");
        $charset = config("database.connections.mysql.databases.$this->dbName.charset");

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

        $user = config("database.connections.mysql.databases.$this->dbName.username");
        $pass = config("database.connections.mysql.databases.$this->dbName.password");

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT => false,
        ];

        try
        {
            return new PDO($dsn, $user, $pass, $options);
        }
        catch(PDOException $e)
        {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
}