<?php

namespace Polyel\Database\Statements;

trait Aggregates
{
    public function count($columns = ['*'])
    {
        return $this->aggregate(__FUNCTION__, $columns);
    }

    public function min($columns)
    {
        return $this->aggregate(__FUNCTION__, $columns);
    }

    public function max($columns)
    {
        return $this->aggregate(__FUNCTION__, $columns);
    }

    public function avg($columns)
    {
        return $this->aggregate(__FUNCTION__, $columns);
    }

    public function sum($columns)
    {
        return $this->aggregate(__FUNCTION__, $columns);
    }

    public function aggregate($function, $columns = ['*'])
    {
        // Convert columns to an array if only a single column
        if(!is_array($columns))
        {
            $columns = [$columns];
        }

        $function = strtoupper($function);

        foreach($columns as $key => $column)
        {
            $columns[$key] = "$function($column)";
        }

        if(exists($this->selects))
        {
            $columns[0] = ', ' . $columns[0];
        }

        $this->select($columns);

        return $this->get(2);
    }
}