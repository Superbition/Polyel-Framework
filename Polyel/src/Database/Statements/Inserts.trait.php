<?php

namespace Polyel\Database\Statements;

trait Inserts
{
    public function insert(array $inserts, bool $getInsertId = false, $returnResult = true)
    {
        if(!is_array(reset($inserts)))
        {
            $inserts = [$inserts];
        }

        $results = [];

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

            $results[] = $this->dbManager->execute('write', $insertQuery, $insertData, $getInsertId);
        }

        // No return on defer inserts, run a check here, otherwise, return the outcome for an insert(s)
        if($returnResult)
        {
            // Return an array of insert results
            if(count($results) > 1)
            {
                return $results;
            }
            else
            {
                // Else only return the first insert result
                return $results[0];
            }
        }
    }

    public function insertAndGetId(array $inserts): int
    {
        return (int)$this->insert($inserts, true);
    }

    public function deferAndInsert(array $inserts): void
    {
        \Swoole\Event::defer(function() use ($inserts) {

            go(function() use ($inserts)
            {
                $this->insert($inserts, false, false);
            });
        });
    }
}