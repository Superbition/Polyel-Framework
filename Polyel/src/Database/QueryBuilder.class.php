<?php

namespace Polyel\Database;

use Polyel\Database\Support\SqlCompile;

class QueryBuilder
{
    use SqlCompile;

    private $dbManager;

    // The type of query that will be executed: read or write
    private $type = 'read';

    private $from;

    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    public function get()
    {
        $query = $this->compileSql();

        $result = $this->dbManager->execute($this->type, $query);

        return $result;
    }
}