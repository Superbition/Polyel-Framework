<?php

namespace Polyel\Database\Connection;

use PDO;
use http\Exception\RuntimeException;

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
        $this->openConnections = 0;
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

    public function reset()
    {
        $this->close();
        $this->open();
    }

    public function close()
    {
        $this->pool = [];
        $this->openConnections = 0;
        $this->status = false;
    }

    public function pull()
    {
        if(is_null($this->pool))
        {
            throw new RuntimeException('Connection pool was closed');
        }

        if(count($this->pool) === 0 && $this->openConnections < $this->max)
        {
            $this->new();
        }

        return array_pop($this->pool);
    }

    public function push($conn)
    {
        if(count($this->pool) < $this->max && exists($conn) && $conn instanceof DatabaseConnection)
        {
            $this->pool[] = $conn;
        }
        else
        {
            $this->openConnections--;
        }
    }

    public function remove($num = 1)
    {
        for($i=1; $i<=$num; $i++)
        {
            if(count($this->pool) >= 1)
            {
                $conn = $this->pull();
                $this->openConnections--;
                $conn = null;
            }
        }
    }

    public function new()
    {
        $newConn = new DatabaseConnection($this->createConnection());
        $this->openConnections++;
        $this->push($newConn);
    }
}