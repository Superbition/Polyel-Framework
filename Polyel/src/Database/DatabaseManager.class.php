<?php

namespace Polyel\Database;

use Polyel\Database\Connection\Pools\MySQLPool;

class DatabaseManager
{
    private $mysqlPools;

    public function __construct()
    {

    }

    public function createPools(): void
    {
        $mysqlDatabases = config('database.connections.mysql.databases');
        foreach($mysqlDatabases as $dbName => $poolConfig)
        {
            $minConn = $poolConfig['pool']['minConnections'];
            $maxConn = $poolConfig['pool']['maxConnections'];
            $this->mysqlPools[$dbName] = new MySQLPool($dbName, $minConn, $maxConn);

            $this->mysqlPools[$dbName]->open();
        }
    }
}