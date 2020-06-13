<?php

namespace Polyel\Database\Statements;

use Polyel\Database\Transaction;
use Polyel\Database\DatabaseManager;

trait Deletes
{
    public function delete($id = null)
    {
        $deleteQuery = "DELETE FROM $this->from";

        if(!is_null($id) && is_numeric($id))
        {
            $this->where('id', '=', $id);
        }

        if(exists($this->wheres))
        {
            $deleteQuery .= ' WHERE ' . $this->wheres;
        }
        else if($id !== '*')
        {
            return null;
        }

        /*
         * The connection used is either a DB Manager instance where it directly uses its
         * execute function to perform a query on the database or a transaction instance is used, where its
         * execute function uses the same database connection to perform a query within a transaction.
         */
        if($this->connection instanceof DatabaseManager)
        {
            $result = $this->connection->execute('write', $deleteQuery, $this->data, false, $this->database);
        }
        else if($this->connection instanceof Transaction)
        {
            $result = $this->connection->execute('write', $deleteQuery, $this->data);
        }

        return (int)$result;
    }

    public function truncate()
    {
        $truncate = "TRUNCATE TABLE $this->from";

        /*
         * The connection used is either a DB Manager instance where it directly uses its
         * execute function to perform a query on the database or a transaction instance is used, where its
         * execute function uses the same database connection to perform a query within a transaction.
         */
        if($this->connection instanceof DatabaseManager)
        {
            $this->connection->execute('write', $truncate, null, false, $this->database);
        }
        else if($this->connection instanceof Transaction)
        {
            $this->connection->execute('write', $truncate);
        }
    }
}