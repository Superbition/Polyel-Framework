<?php

namespace Polyel\Database\Connection;

class DatabaseConnection
{
    private $connection;

    public $transaction;

    private $lastActive;

    public function __construct($conn)
    {
        $this->connection = $conn;

        $this->transaction = false;

        $this->lastActive = time();
    }

    public function use()
    {
        $this->lastActive = time();

        return $this->connection;
    }

    public function transaction($status)
    {
        $this->transaction = $status;
    }
}