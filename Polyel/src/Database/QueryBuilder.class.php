<?php

namespace Polyel\Database;

class QueryBuilder
{
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