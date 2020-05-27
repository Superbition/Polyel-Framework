<?php

namespace Polyel\Database\Connection;

use PDO;

abstract class ConnectionPool implements ConnectionCreation
{
    private $pool;

    private $min;

    private $max;

    public function __construct(int $min, int $max)
    {
        $this->min = $min;
        $this->max = $max;

        $this->pool = [];
    }

    public function open()
    {
        for($i=1; $i<=$this->min; $i++)
        {
            $newConn = $this->createConnection();
            $this->push($newConn);
        }
    }

    public function close()
    {

    }

    public function pull()
    {

    }

    public function push(&$conn)
    {
        if(count($this->pool) <= $this->max && exists($conn))
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

    }
}