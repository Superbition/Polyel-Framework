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
        $mysqlDatabases = [];

        $databaseConnections = config('database.connections');
        foreach($databaseConnections as $connectionName => $connectionConfig)
        {
            switch(($connectionConfig['driver']))
            {
                case 'mysql':

                    $mysqlDatabases[$connectionName] = $connectionConfig;

                break;
            }
        }

        foreach($mysqlDatabases as $connectionName => $connectionConfig)
        {
            if($connectionConfig['active'])
            {
                $waitTimeout = $connectionConfig['pool']['waitTimeout'];
                $maxConnectionIdle = $connectionConfig['pool']['connectionIdleTimeout'];

                $readMinConn = $connectionConfig['pool']['read']['minConnections'];
                $readMaxConn = $connectionConfig['pool']['read']['maxConnections'];

                $writeMinConn = $connectionConfig['pool']['write']['minConnections'];
                $writeMaxConn = $connectionConfig['pool']['write']['minConnections'];

                $this->mysqlPools[$connectionName] = new MySQLPool(
                    $connectionName,
                    $maxConnectionIdle,
                    $waitTimeout,
                    $readMinConn,
                    $readMaxConn,
                    $writeMinConn,
                    $writeMaxConn
                );

                $this->mysqlPools[$connectionName]->open();
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

    public function getConnection($type, $connectionName = null)
    {
        // Use the default set database if one is not provided
        if(is_null($connectionName))
        {
            $connectionName = config("database.default");
        }

        $connectionConfig = config("database.connections.$connectionName");

        $databaseConnection = [];

        switch($connectionConfig['driver'])
        {
            case 'mysql':

                $databaseConnection['driver'] = 'mysql';
                $databaseConnection['name'] = $connectionName;
                $databaseConnection['type'] = $type;
                $databaseConnection['connection'] = $this->mysqlPools[$connectionName]->pull($type);

                return $databaseConnection;

            break;
        }

        // Null, no database or pool found
        return null;
    }

    public function execute($type, $query, $data = null, $insert = false, $database = null)
    {
        $db = $this->getConnection($type, $database);

        try
        {
            $statement = $db['connection']->prepare($query);
            $statement->execute($data);

            if($insert)
            {
                $result = $db['connection']->lastInsertId();
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
        catch(Exception $exception)
        {
            throw $exception;
        }

        $this->returnConnection($db);

        if(exists($result))
        {
            return $result;
        }

        return false;
    }

    public function returnConnection($databaseConnection)
    {
        if(is_array($databaseConnection) && !array_diff(['driver', 'name', 'type', 'connection'], array_keys($databaseConnection)))
        {
            // A connection that is still in a transactional state is not re-usable and cannot enter the pool
            if($databaseConnection['connection']->transactionStatus() === true)
            {
                return false;
            }

            switch($databaseConnection['driver'])
            {
                case 'mysql':

                    $this->mysqlPools[$databaseConnection['name']]
                         ->push($databaseConnection['type'], $databaseConnection['connection']);

                break;
            }
        }
    }
}