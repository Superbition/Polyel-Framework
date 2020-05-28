<?php

namespace Polyel\Database\Connection;

class DatabaseConnection
{
    private $connection;

    private $lastActive;

    public function __construct($conn)
    {
        $this->connection = $conn;

        $this->lastActive = time();
    }

    public function use()
    {
        $this->lastActive = time();

        return $this->connection;
    }
}