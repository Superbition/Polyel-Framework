<?php

namespace Polyel\Database\Connection\Pools;

use Polyel\Database\Connection\ConnectionPool;

class MySQLPool extends ConnectionPool
{
    private $dbName;

    public function __construct(string $dbname, int $min, int $max)
    {
        parent::__construct($min, $max);

        $this->dbName = $dbname;
    }

    public function createConnection()
    {

    }
}