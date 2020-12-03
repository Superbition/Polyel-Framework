<?php

namespace Polyel\Database\Connection;

use PDO;
use Swoole;
use RuntimeException;
use Swoole\Coroutine\Channel;

abstract class ConnectionPool implements ConnectionCreation
{
    // The status of the pool, open = true or closed = false
    private bool $status = false;

    // The pool itself where the read and write connections are held, in Swoole channels
    private $pool = [];

    // Holds the min and max limits for both read and write pools
    private array $limits = [];

    // The Swoole channel pop timeout
    private float $popTimeout;

    // Max connection idle timeout before being disconnected in minutes
    private int $maxConnectionIdle;

    // Counter to track the number of open connections, not the total in the pool
    private array $openConnections = [];

    // The ID of the gc timer collector timer process
    private int $connectionIdleGcTimerId;

    public function __construct(int $maxConnectionIdle, float $waitTimeout, int $readMin, int $readMax, $writeMin, $writeMax)
    {
        $this->maxConnectionIdle = $maxConnectionIdle;
        $this->popTimeout = $waitTimeout;

        $this->limits['read']['min'] = $readMin;
        $this->limits['read']['max'] = $readMax;

        $this->limits['write']['min'] = $writeMin;
        $this->limits['write']['max'] = $writeMax;

        $this->pool['read'] = new Channel($this->limits['read']['max']);
        $this->pool['write'] = new Channel($this->limits['write']['max']);

        $this->openConnections['read'] = 0;
        $this->openConnections['write'] = 0;
    }

    public function debug()
    {
        echo "\n";

        echo "READ POOL:\n";
        echo "\e[100m\e[30mOPEN POOL CONNECTIONS\e[49m\e[39m: " . $this->openConnections['read'] . "\n";
        echo "\e[100m\e[30mCURRENT POOL NUM\e[49m\e[39m: " . $this->pool['read']->length() . "\n";

        echo "\n";

        echo "WRITE POOL:\n";
        echo "\e[100m\e[30mOPEN POOL CONNECTIONS\e[49m\e[39m: " . $this->openConnections['write'] . "\n";
        echo "\e[100m\e[30mCURRENT POOL NUM\e[49m\e[39m: " . $this->pool['write']->length() . "\n";

        echo "\n";
    }

    private function runConnectionIdleGc()
    {
        // Run GC every 1 minute to check for idle DB connections...
        $this->connectionIdleGcTimerId = Swoole\Timer::tick(60000, function()
        {
            // Run GC for read and write pools
            $this->connectionIdleGcFor('read');
            $this->connectionIdleGcFor('write');
        });
    }

    private function connectionIdleGcFor($type)
    {
        $activeConnections = [];

        while($this->openConnections[$type] > $this->limits[$type]['min'])
        {
            if($this->status === false)
            {
                break;
            }

            if($this->pool[$type]->isEmpty())
            {
                break;
            }

            // Pop using a timeout of 1 second
            $connection = $this->pool[$type]->pop(1);

            if($connection === false)
            {
                continue;
            }

            $now = time();
            $lastConnectionActive = ($now - $connection->lastActive());

            if($lastConnectionActive < (60 * $this->maxConnectionIdle))
            {
                $activeConnections[] = $connection;
            }
            else
            {
                $connection = null;
                $this->openConnections[$type]--;
            }
        }

        foreach($activeConnections as $connection)
        {
            $status = $this->pool[$type]->push($connection, 0.1);

            if($status === false)
            {
                $connection = null;
                $this->openConnections[$type]--;
            }
        }
    }

    private function stopConnectionIdleGc()
    {
        Swoole\Timer::clear($this->connectionIdleGcTimerId);
    }

    public function open()
    {
        // Open up the read pool
        for($i=1; $i<=$this->limits['read']['min']; $i++)
        {
            $this->new('read');
        }

        // Open up the write pool
        for($i=1; $i<=$this->limits['write']['min']; $i++)
        {
            $this->new('write');
        }

        $this->status = true;

        $this->runConnectionIdleGc();
    }

    public function close()
    {
        if(!is_null($this->pool))
        {
            $this->stopConnectionIdleGc();

            // Close both the read and write pools
            $this->pool['read']->close();
            $this->pool['write']->close();
            $this->pool = null;

            // Reset the read & write open connections
            $this->openConnections['read'] = 0;
            $this->openConnections['write'] = 0;

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

    public function getPoolCountFor($type)
    {
        return $this->pool[$type]->length();
    }

    public function getOpenConnectionCountFor($type)
    {
        return $this->openConnections[$type];
    }

    /*
     * Pull out a connection based on the type: read or write
     * Each pool has a number of read and write connections
     */
    public function pull($type)
    {
        if(is_null($this->pool))
        {
            throw new RuntimeException('Connection pool was closed');
        }

        if($this->openConnections[$type] <= $this->limits[$type]['max'] && $this->pool[$type]->isEmpty())
        {
            // Create a new connection based on the type: read or write
            $this->new($type);
        }

        $connection = $this->pool[$type]->pop($this->popTimeout);

        // False when the pop timeout is reached or no connection available.
        if($connection === false)
        {
            // No connection available in the pool, wait timeout was reached or pool is exhausted
            return null;
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
            $this->openConnections[$type]--;
        }

        // The connection is not alive or active
        return null;
    }

    /*
     * Push the connection back into the pool based on its type, either it will
     * be a read or write connection.
     */
    public function push($type, $conn)
    {
        if(is_null($this->pool))
        {
            throw new RuntimeException('Connection pool was closed');
        }

        if($this->pool[$type]->isFull())
        {
            $conn = null;
            $this->openConnections[$type]--;
            return false;
        }

        if($this->openConnections[$type] <= $this->limits[$type]['max'] && exists($conn) && $conn instanceof DatabaseConnection)
        {
            // Push the connection back into the pool and use a channel timeout
            $this->pool[$type]->push($conn, 0.1);
        }
        else
        {
            $this->openConnections[$type]--;
        }
    }

    public function add($type)
    {
        $this->new($type);
    }

    public function remove($type, $num = 1)
    {
        go(function() use($type, $num)
        {
            for($i=1; $i<=$num; $i++)
            {
                if($this->pool[$type]->length() >= 1)
                {
                    $conn = $this->pull($type);
                    $this->openConnections[$type]--;
                    $conn = null;
                }
            }
        });
    }

    private function new($type)
    {
        go(function() use($type)
        {
            $newConn = new DatabaseConnection($this->createConnection($type));
            $this->openConnections[$type]++;
            $this->push($type, $newConn);
        });
    }
}