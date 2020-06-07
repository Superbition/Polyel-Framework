<?php

namespace Polyel\Database\Connection;

use PDO;
use RuntimeException;
use Swoole\Coroutine\Channel;

abstract class ConnectionPool implements ConnectionCreation
{
    // The status of the pool, open = true or closed = false
    private bool $status;

    // The pool itself where the connections are held, in a Swoole channel
    private $pool;

    // Minimum  number of DB connections in the pool
    private int $min;

    // Maximum number of DB connections in the pool
    private int $max;

    // The Swoole channel pop timeout
    private float $popTimeout;

    // Counter to track the number of open connections, not the total in the pool
    private int $openConnections;

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

    public function close()
    {
        if(!is_null($this->pool))
        {
            $this->pool->close();
            $this->pool = null;
            $this->openConnections = 0;
            $this->status = false;
        }
    }

    public function reset()
    {
        $this->close();
        $this->open();
    }

    public function status()
    {
        return $this->status;
    }

    public function getPoolCount()
    {
        return $this->pool->length();
    }

    public function getOpenConnectionCount()
    {
        return $this->openConnections;
    }

    public function pull()
    {
        if(is_null($this->pool))
        {
            throw new RuntimeException('Connection pool was closed');
        }

        if($this->openConnections < $this->max && $this->pool->isEmpty())
        {
            $this->new();
        }

        $connection = $this->pool->pop($this->popTimeout);

        // False when the pop timeout is reached. Try again to get a connection...
        if($connection === false)
        {
            // Pull again because the pop timeout was reached
            return $this->pull();
        }

        // Check if the connection is alive and active
        if($connection->isConnected() === true)
        {
            // Connection is active, we can return it
            return $connection;
        }
        else
        {
            $connection = null;
            $this->openConnections--;
        }

        // The connection is not alive or active
        return null;
    }

    public function push($conn)
    {
        if(is_null($this->pool))
        {
            throw new RuntimeException('Connection pool was closed');
        }

        if($this->pool->isFull())
        {
            $conn = null;
            $this->openConnections--;
            return false;
        }

        if($this->openConnections < $this->max && exists($conn) && $conn instanceof DatabaseConnection)
        {
            // Push the connection back into the pool and use a channel timeout
            $this->pool->push($conn, 0.1);
        }
        else
        {
            $this->openConnections--;
        }
    }

    public function remove($num = 1)
    {
        go(function() use($num)
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
        });
    }

    private function new()
    {
        go(function()
        {
            $newConn = new DatabaseConnection($this->createConnection());
            $this->openConnections++;
            $this->push($newConn);
        });
    }
}