<?php

namespace Polyel\Database;

use Exception;
use Polyel\Database\Connection\Pools\MySQLPool;

class DatabaseManager
{
    // Holds all the connection pools for all MySQL configured databases and thei connections
    private $mysqlPools;

    public function __construct()
    {

    }

    public function createWorkerPool(): void
    {
        $mysqlDatabases = config('database.connections.mysql.databases');
        foreach($mysqlDatabases as $dbName => $poolConfig)
        {
            $dbStatus = config("database.connections.mysql.databases.$dbName.active");

            if($dbStatus)
            {
                $minConn = $poolConfig['pool']['minConnections'];
                $maxConn = $poolConfig['pool']['maxConnections'];
                $maxConnectionIdle = $poolConfig['pool']['connectionIdleTimeout'];
                $waitTimeout = $poolConfig['pool']['waitTimeout'];
                $this->mysqlPools[$dbName] = new MySQLPool($dbName, $minConn, $maxConn, $maxConnectionIdle, $waitTimeout);

                $this->mysqlPools[$dbName]->open();
            }
        }
    }

    public function closeWorkerPool()
    {
        foreach($this->mysqlPools as $pool)
        {
            $pool->close();
        }
    }

    public function getConnection($type, $database = null)
    {
        // Use the default set database if one is not provided
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

        // Null, no database or pool found
        return null;
    }

    public function execute($type, $query, $data = null, $insert = false, $database = null)
    {
        $db = $this->getConnection($type, $database);

        $statement = $db['connection']->use()->prepare($query);

        try
        {
            $statement = $db['connection']->use()->prepare($query);
            $statement->execute($data);

            if($insert)
            {
                $result = $db['connection']->use()->lastInsertId();
            }
            else if($type === 'write')
            {
                $result = $statement->rowCount();
            }
            else
            {
                $result = $statement->fetchAll();
            }
        }
        catch(Exception $message)
        {
            echo $message->getMessage();
        }

        $this->returnConnection($db);

        return $result;
    }

    public function returnConnection($connection)
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