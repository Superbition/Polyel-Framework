<?php

namespace Polyel\Database\Support;

trait SqlCompile
{
    private function compileSql()
    {
        $query = '';

        if(!exists($this->selects))
        {
            $this->selects = 'SELECT * ';
        }

        $query .= $this->selects;

        $query .= ' FROM ' . $this->from;

        return $query;
    }
}