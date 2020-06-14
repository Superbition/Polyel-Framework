<?php

namespace Polyel\Database\Connection\Pools;

use PDO;
use PDOException;
use Polyel\Database\Connection\ConnectionPool;

class MySQLPool extends ConnectionPool
{
    // The database config connection name
    private string $connectionName;

    public function __construct(string $connectionName, int $maxConnectionIdle, float $waitTimeout, int $readMin, int $readMax, $writeMin, $writeMax)
    {
        // Pass required parameters to the Connection Pool constructor
        parent::__construct($maxConnectionIdle, $waitTimeout, $readMin, $readMax, $writeMin, $writeMax);

        $this->connectionName = $connectionName;
    }

    /*
     * Creates a new connection based on the type requested: read or write.
     * It chooses from a list of read or write hosts and randomly selects a host IP.
     */
    public function createConnection($type)
    {
        // Grab all the host IPs for the request type...
        $hosts = config("database.connections.$this->connectionName.$type.hosts");

        // Randomly select a host IP from the list of hosts set in the config
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