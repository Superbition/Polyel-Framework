<?php

namespace Polyel\Database\Connection;

use PDO;
use Throwable;

class DatabaseConnection
{
    private $connection;

    public $transactionStatus;

    private $lastActive;

    public function __construct($conn)
    {
        $this->connection = $conn;

        $this->transactionStatus = false;

        $this->lastActive = time();
    }

    public function use()
    {
        $this->lastActive = time();

        return $this->connection;
    }

    public function isConnected()
    {
        try
        {
            return (bool)$this->use()->getAttribute(PDO::ATTR_SERVER_INFO);
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
}