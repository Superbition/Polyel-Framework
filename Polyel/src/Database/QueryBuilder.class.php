<?php

namespace Polyel\Database;

use Polyel\Database\Support\SqlCompile;

class QueryBuilder
{
    use SqlCompile;

    private $dbManager;

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
}