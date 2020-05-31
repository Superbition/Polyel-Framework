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

    private $selects;

    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    public function select($columns = ['*'])
    {
        // Get func args when no array is used
        if(!is_array($columns) || func_num_args() > 1)
        {
            // Either a single argument or multiple...
            $columns = func_get_args();
        }

        // Process the array of selects into a single string
        foreach($columns as $key => $column)
        {
            // If the array is only containing the select all symbol...
            if($column === '*')
            {
                // Return because we want to select everything...
                return $this;
            }

            // Add the column to the select statement
            $this->selects .= $column;

            if($key < array_key_last($columns))
            {
                // If the column is not the last one, add the separator
                $this->selects .= ', ';
            }
        }

        return $this;
    }

    public function get()
    {
        $query = $this->compileSql();

        $result = $this->dbManager->execute($this->type, $query);

        return $result;
    }
}