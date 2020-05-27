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

    public function get()
    {
        $this->lastActive = time();

        return $this->connection;
    }
}