<?php

namespace Polyel\Database\Support;

trait SqlCompile
{
    private function compileSql()
    {
        $query = 'SELECT ';

        if($this->distinct)
        {
            $query .= 'DISTINCT ';
        }

        if(!exists($this->selects))
        {
            $this->selects = '*';
            $query .= $this->selects;
        }
        else
        {
            $query .= $this->selects;
        }

        if(exists($this->from))
        {
            $query .= ' FROM ' . $this->from;
        }

        if(exists($this->joins))
        {
            $query .= $this->joins;
        }

        return $query;
    }
}