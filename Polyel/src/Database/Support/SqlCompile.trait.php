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

        $query .= ' FROM ' . $this->from;

        return $query;
    }
}