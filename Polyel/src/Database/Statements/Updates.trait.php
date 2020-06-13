<?php

namespace Polyel\Database\Statements;

use Polyel\Database\Transaction;
use Polyel\Database\DatabaseManager;

trait Updates
{
    // Used to inject operators to the value of an update statement like plus or minus
    private $updateColumnOperator = '';

    public function update(array $updates): int
    {
        $updateQuery = "UPDATE $this->from SET ";

        $updateData = [];

        $currentColumn = 0;
        $lastUpdateKey = array_key_last(array_keys($updates));

        foreach($updates as $column => $value)
        {
            if(strpos($column, '->') !== false)
            {
                $column = $this->convertUpdateColumnToJsonSet($column);
                $updateQuery .= $column;
            }
            else
            {
                // Check if we need to add column operators to the update statement
                if(exists($this->updateColumnOperator))
                {
                    $updateQuery .= $column . " = $this->updateColumnOperator ?";
                }
                else
                {
                    $updateQuery .= $column . " = ?";
                }
            }

            $updateData[] = $value;

            if($currentColumn < $lastUpdateKey)
            {
                $updateQuery .= ', ';
            }

            $currentColumn++;
        }

        if(exists($this->wheres))
        {
            $updateQuery .= ' WHERE ' . $this->wheres;
            $updateData = array_merge($updateData, $this->data);
        }

        /*
         * The connection used is either a DB Manager instance where it directly uses its
         * execute function to perform a query on the database or a transaction instance is used, where its
         * execute function uses the same database connection to perform a query within a transaction.
         */
        if($this->connection instanceof DatabaseManager)
        {
            $result = $this->connection->execute('write', $updateQuery, $updateData, false, $this->database);
        }
        else if($this->connection instanceof Transaction)
        {
            $result = $this->connection->execute('write', $updateQuery, $updateData);
        }

        return (int)$result;
    }

    public function updateOrInsert(array $conditions, array $values, $getInsertId = false)
    {
        $this->select(1);

        foreach($conditions as $column => $value)
        {
            $this->where($column, '=', $value);
        }

        $this->limit(1);

        /*
         * The connection used is either a DB Manager instance where it directly uses its
         * execute function to perform a query on the database or a transaction instance is used, where its
         * execute function uses the same database connection to perform a query within a transaction.
         */
        if($this->connection instanceof DatabaseManager)
        {
            $recordExists = $this->connection->execute('write', $this->compileSql(), $this->data, false, $this->database);
        }
        else if($this->connection instanceof Transaction)
        {
            $recordExists = $this->connection->execute('write', $this->compileSql(), $this->data);
        }

        if($recordExists)
        {
            $this->update($values);

            return 1;
        }
        else
        {
            $insertResponse = $this->insert(array_merge($conditions, $values), $getInsertId);

            if($getInsertId)
            {
                return $insertResponse;
            }

            return 2;
        }
    }

    private function convertUpdateColumnToJsonSet($column)
    {
        $column = explode('->', $column);

        $jsonDataColumn = array_shift($column);

        $jsonConvertedColumn = $jsonDataColumn . ' = JSON_SET(' . $jsonDataColumn . ', "$.';

        foreach($column as $pathKey => $jsonPath)
        {
            $jsonConvertedColumn .= $jsonPath;

            if($pathKey < array_key_last($column))
            {
                $jsonConvertedColumn .= '.';
            }
        }

        $jsonConvertedColumn .= '", ?)';

        return $jsonConvertedColumn;
    }

    public function increment($column, $amount = 1)
    {
        if(is_numeric($amount))
        {
            $this->updateColumnOperator = $column . ' +';
            $updateResult = $this->update([$column => $amount]);
            $this->updateColumnOperator = '';

            return $updateResult;
        }
    }

    public function decrement($column, $amount = 1)
    {
        if(is_numeric($amount))
        {
            $this->updateColumnOperator = $column . ' -';
            $updateResult = $this->update([$column => $amount]);
            $this->updateColumnOperator = '';

            return $updateResult;
        }
    }
}