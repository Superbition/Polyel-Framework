<?php

namespace Polyel\Database\Statements;

trait Inserts
{
    public function insert($inserts, $getInsertId = false)
    {
        if(!is_array(reset($inserts)))
        {
            $inserts = [$inserts];
        }

        foreach($inserts as $insert)
        {
            $insertQuery = 'INSERT INTO ' . $this->from;

            $insertColumns = ' (';
            $insertValues = ' VALUES (';

            $insertData = [];

            $lastInsert = array_key_last(array_keys($insert));

            $currentInsert = 0;
            foreach($insert as $column => $value)
            {
                $insertColumns .= $column;

                $insertValues .= '?';
                $insertData[] = $value;

                if($currentInsert < $lastInsert)
                {
                    $insertColumns .= ', ';
                    $insertValues .= ', ';
                }

                $currentInsert++;
            }

            $insertQuery .= $insertColumns . ')' . $insertValues . ')';

            $result = $this->dbManager->execute('write', $insertQuery, $insertData, $getInsertId);

            return $result;
        }
    }

    public function insertAndGetId($inserts)
    {
        return $this->insert($inserts, true);
    }
}