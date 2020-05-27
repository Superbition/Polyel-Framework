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
    }

    public function open()
    {
        for($i=0; $i<=$this->min; $i++)
        {
            $this->push($this->createConnection());
        }
    }

    public function close()
    {

    }

    public function pull()
    {

    }

    public function push($conn)
    {
        $this->pool[] = $conn;
    }

    public function remove()
    {

    }

    public function new()
    {

    }
}