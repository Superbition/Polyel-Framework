<?php

namespace Polyel\Database;

class Database
{
    private $dbManager;

    private $queryBuilder;

    public function __construct(DatabaseManager $dbManager, QueryBuilder $queryBuilder)
    {
        $this->dbManager = $dbManager;
        $this->queryBuilder = $queryBuilder;
    }

    private function execute($type, $query, $data)
    {
        return $this->dbManager->execute($type, $query, $data);
    }

    public function select($query, $data = null)
    {
        return $this->execute("read", $query, $data);
    }

    public function table($table)
    {
        return $this->queryBuilder->from($table);
    }
}