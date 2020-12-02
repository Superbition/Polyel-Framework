<?php

namespace Polyel\Database\Connection;

use PDO;
use Throwable;

class DatabaseConnection
{
    private $connection;

    private $transactionStatus;

    private $lastActive;

    public function __construct($conn)
    {
        $this->connection = $conn;

        $this->transactionStatus = false;

        $this->lastActive = time();
    }

    public function __call($method, $arguments)
    {
        $this->lastActive = time();

        return $this->connection->$method(...$arguments);
    }

    public function isConnected()
    {
        try
        {
            return (bool)$this->getAttribute(PDO::ATTR_SERVER_INFO);
        }
        catch(Throwable $e)
        {
            return false;
        }
    }

    public function lastActive()
    {
        return $this->lastActive;
    }

    public function transactionStatus()
    {
        return $this->transactionStatus;
    }

    public function startTransaction()
    {
        $this->connection->beginTransaction();
        $this->transactionStatus = true;
    }

    public function commitTransaction()
    {
        $this->connection->commit();
        $this->transactionStatus = false;
    }

    public function rollBackTransaction()
    {
        $this->connection->rollback();
        $this->transactionStatus = false;
    }
}