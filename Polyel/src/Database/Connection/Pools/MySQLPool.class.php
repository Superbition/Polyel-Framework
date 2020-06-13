<?php

namespace Polyel\Database\Connection\Pools;

use PDO;
use PDOException;
use Polyel\Database\Connection\ConnectionPool;

class MySQLPool extends ConnectionPool
{
    private string $connectionName;

    public function __construct(string $connectionName, int $maxConnectionIdle, float $waitTimeout, int $readMin, int $readMax, $writeMin, $writeMax)
    {
        parent::__construct($maxConnectionIdle, $waitTimeout, $readMin, $readMax, $writeMin, $writeMax);

        $this->connectionName = $connectionName;
    }

    public function createConnection($type)
    {
        $hosts = config("database.connections.$this->connectionName.$type.hosts");

        $host = $hosts[array_rand($hosts)];

        $port = config("database.connections.$this->connectionName.port");
        $db = config("database.connections.$this->connectionName.database");
        $charset = config("database.connections.$this->connectionName.charset");

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

        $user = config("database.connections.$this->connectionName.username");
        $pass = config("database.connections.$this->connectionName.password");

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