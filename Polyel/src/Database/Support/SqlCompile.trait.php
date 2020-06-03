<?php

namespace Polyel\Database\Support;

trait SqlCompile
{
    private function compileSql()
    {
        $query = '';

        if(!exists($this->selects) && $this->compileMode === 0)
        {
            $this->selects = 'SELECT *';
            $query .= $this->selects;
        }
        else if(exists($this->selects))
        {
            $query .= 'SELECT ' . $this->selects;
        }

        if($this->distinct)
        {
            $query .= 'DISTINCT ';
        }

        if(exists($this->from))
        {
            $query .= ' FROM ' . $this->from;
        }

        if(exists($this->joins))
        {
            $query .= $this->joins;
        }

        if(exists($this->wheres))
        {
            if($this->compileMode === 0 || (exists($this->selects) && exists($this->from)))
            {
                $query .= ' WHERE ' . $this->wheres;
            }
            else if ($this->compileMode === 1)
            {
                $query .= $this->wheres;
            }
        }

        if(exists($this->groups))
        {
            $query .= " GROUP BY $this->groups";
        }

        if(exists($this->havings))
        {
            $query .= " HAVING $this->havings";
        }

        if(exists($this->order))
        {
            $query .= " ORDER BY $this->order";
        }

        return $query;
    }
}