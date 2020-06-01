<?php

namespace Polyel\Database;

use Polyel\Database\Support\SqlCompile;

class QueryBuilder
{
    use SqlCompile;

    private $dbManager;

    // The type of query that will be executed: read or write
    private $type = 'read';

    private $data;

    private $selects;

    private $distinct = false;

    private $from;

    private $joins;

    private $wheres;

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

    public function join($table, $column1, $operator, $column2, $type = 'INNER')
    {
        $join = "$type JOIN " . $table . " ON " . $column1 . " $operator " . $column2;

        $this->joins .= " $join";

        return $this;
    }

    public function leftJoin($table, $column1, $operator, $column2)
    {
        $this->join($table, $column1, $operator, $column2, 'LEFT');

        return $this;
    }

    public function rightJoin($table, $column1, $operator, $column2)
    {
        $this->join($table, $column1, $operator, $column2, 'RIGHT');

        return $this;
    }

    public function crossJoin($table)
    {
        $this->joins .= " CROSS JOIN $table";

        return $this;
    }

    public function where($column, $operator = null, $value = null, $type = ' AND ')
    {
        if(is_array($column))
        {
            return $this->wheres($column, $type);
        }

        $where = $column . " $operator " . '?';

        $this->data[] = $value;

        if(exists($this->wheres))
        {
            $where = $type . $where;

            $this->wheres .= $where;
        }
        else
        {
            $this->wheres = $where;
        }

        return $this;
    }

    /*
     * Used to process an array of where statements
     */
    private function wheres($wheres, $type)
    {
        foreach($wheres as $where)
        {
            $this->where($where[0], $where[1], $where[2], $type);
        }

        return $this;
    }

    public function distinct()
    {
        $this->distinct = true;

        return $this;
    }

    public function get()
    {
        $query = $this->compileSql();

        $result = $this->dbManager->execute($this->type, $query, $this->data);

        return $result;
    }
}