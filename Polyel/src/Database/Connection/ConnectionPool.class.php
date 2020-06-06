<?php

namespace Polyel\Database\Connection;

use PDO;
use Swoole\Coroutine\Channel;
use http\Exception\RuntimeException;

abstract class ConnectionPool implements ConnectionCreation
{
    // The status of the pool, open = true or closed = false
    private $status;

    // The pool itself where the connections are held, in a Swoole channel
    private $pool;

    // Minimum  number of DB connections in the pool
    private $min;

    // Maximum number of DB connections in the pool
    private $max;

    // The Swoole channel pop timeout
    private $popTimeout;

    // Counter to track the number of open connections, not the total in the pool
    private $openConnections;

    public function __construct(int $min, int $max, float $waitTimeout)
    {
        $this->min = $min;
        $this->max = $max;
        $this->popTimeout = $waitTimeout;

        $this->status = false;
        $this->openConnections = 0;
        $this->pool = new Channel($max);
    }

    public function debug()
    {
        echo "\n";
        echo "\e[100m\e[30mOPEN POOL CONNECTIONS\e[49m\e[39m: " . $this->openConnections . "\n";
        echo "\e[100m\e[30mCURRENT POOL NUM\e[49m\e[39m: " . $this->pool->length() . "\n";
        echo "\n";
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
        $this->pool->close();
        $this->pool = null;
        $this->openConnections = 0;
        $this->status = false;
    }

    public function pull()
    {
        if(is_null($this->pool))
        {
            throw new RuntimeException('Connection pool was closed');
        }

        if($this->pool->isEmpty() && $this->openConnections < $this->max)
        {
            $this->new();
        }

        return $this->pool->pop($this->popTimeout);
    }

    public function push($conn)
    {
        if($this->pool->isFull())
        {
            $conn = null;
            $this->openConnections--;
            return false;
        }

        if($this->openConnections < $this->max && exists($conn) && $conn instanceof DatabaseConnection)
        {
            $this->pool->push($conn);
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
            if($this->pool->length() >= 1)
            {
                $conn = $this->pull();
                $this->openConnections--;
                $conn = null;
            }
        }
    }

    public function new()
    {
        go(function()
        {
            $newConn = new DatabaseConnection($this->createConnection());
            $this->openConnections++;
            $this->push($newConn);
        });
    }
}