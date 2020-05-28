<?php

namespace Polyel\Database\Connection;

use PDO;

abstract class ConnectionPool implements ConnectionCreation
{
    private $status;

    private $pool;

    private $min;

    private $max;

    private $openConnections;

    public function __construct(int $min, int $max)
    {
        $this->min = $min;
        $this->max = $max;

        $this->status = false;
        $this->pool = [];
    }

    public function open()
    {
        for($i=1; $i<=$this->min; $i++)
        {
            $this->new();
        }

        $this->status = true;
    }

    public function status()
    {
        return $this->status;
    }

    public function close()
    {
        $this->pool = [];
        $this->openConnections = 0;
        $this->status = false;
    }

    public function pull()
    {
        if(count($this->pool) >= 1)
        {
            return array_pop($this->pool);
        }

        if($this->openConnections < $this->max)
        {
            $this->openConnections++;
            $this->new();
            return array_pop($this->pool);
        }

        return null;
    }

    public function push($conn)
    {
        if(count($this->pool) < $this->max && exists($conn) && $conn instanceof DatabaseConnection)
        {
            $this->pool[] = $conn;
        }
        else
        {
            $conn = null;
        }
    }

    public function remove()
    {

    }

    public function new()
    {
        $newConn = new DatabaseConnection($this->createConnection());
        $this->push($newConn);
        $this->openConnections++;
    }
}