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
        echo "Attempting to create database pools... ";

        $mysqlDatabases = config('database.connections.mysql.databases');
        foreach($mysqlDatabases as $dbName => $poolConfig)
        {
            $dbStatus = config("database.connections.mysql.databases.$dbName.active");

            if($dbStatus)
            {
                $minConn = $poolConfig['pool']['minConnections'];
                $maxConn = $poolConfig['pool']['maxConnections'];
                $this->mysqlPools[$dbName] = new MySQLPool($dbName, $minConn, $maxConn);

                $this->mysqlPools[$dbName]->open();
            }
        }

        echo "Done.\n";
    }

    public function getConnection($type, $database = null)
    {
        if(is_null($database))
        {
            $database = config("database.default");
        }

        switch($database)
        {
            case 'mysql':

                $dbServers = config("database.connections.$database.$type");
                $dbServer = $dbServers[array_rand($dbServers)];

                $connection = [];

                $connection['driver'] = 'mysql';
                $connection['database'] = $dbServer;
                $connection['connection'] = $this->mysqlPools[$dbServer]->pull();

                return $connection;

            break;
        }
    }

    public function putConnection($connection)
    {
        if(is_array($connection) && !array_diff(['driver', 'database', 'connection'], array_keys($connection)))
        {
            switch($connection['driver'])
            {
                case 'mysql':

                    $this->mysqlPools[$connection['database']]->push($connection['connection']);

                break;
            }
        }
    }
}