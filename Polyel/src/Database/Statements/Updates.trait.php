<?php

namespace Polyel\Database\Statements;

trait Updates
{
    private $updateValueSQL = '';

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
                if(exists($this->updateValueSQL))
                {
                    $updateQuery .= $column . " = $this->updateValueSQL ?";
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

        $result = $this->dbManager->execute('write', $updateQuery, $updateData);

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

        $recordExists = $this->dbManager->execute('write', $this->compileSql(), $this->data);

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
            $this->updateValueSQL = $column . ' +';
            $updateResult = $this->update([$column => $amount]);
            $this->updateValueSQL = '';

            return $updateResult;
        }
    }

    public function decrement($column, $amount = 1)
    {
        if(is_numeric($amount))
        {
            $this->updateValueSQL = $column . ' -';
            $updateResult = $this->update([$column => $amount]);
            $this->updateValueSQL = '';

            return $updateResult;
        }
    }
}