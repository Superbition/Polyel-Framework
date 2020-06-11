<?php

namespace Polyel\Database;

class Database
{
    private $dbManager;

    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    public function raw($query, $data = null, $type = 'write')
    {
        return $this->dbManager->execute($type, $query, $data);
    }

    public function select($query, $data = null)
    {
        return $this->raw($query, $data, "read");
    }

    public function insert($query, $data = null)
    {
        return $this->raw($query, $data, "write");
    }

    public function update($query, $data = null)
    {
        return $this->raw($query, $data, "write");
    }

    public function delete($query, $data = null)
    {
        return $this->raw($query, $data, "write");
    }

    public function table($table)
    {
        $queryBuilder = new QueryBuilder($this->dbManager);

        return $queryBuilder->from($table);
    }
}