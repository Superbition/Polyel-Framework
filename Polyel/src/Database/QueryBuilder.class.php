<?php

namespace Polyel\Database;

class QueryBuilder
{
    private $from;

    public function __construct()
    {

    }

    public function from($table)
    {
        $this->from = $table;

        return $this;
    }
}