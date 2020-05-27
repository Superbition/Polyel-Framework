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

    }

    public function close()
    {

    }

    public function pull()
    {

    }

    public function push()
    {

    }

    public function remove()
    {

    }

    public function new()
    {

    }
}