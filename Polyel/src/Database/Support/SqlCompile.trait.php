<?php

namespace Polyel\Database\Support;

trait SqlCompile
{
    private function compileSql()
    {
        $query = '';

        if(!exists($this->selects))
        {
            $this->selects = 'SELECT *';
            $query .= $this->selects;
        }
        else
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
            $query .= ' WHERE ' . $this->wheres;
        }

        return $query;
    }
}