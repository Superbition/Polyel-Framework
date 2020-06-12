<?php

namespace Polyel\Database\Statements;

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
        $result = $this->connection->execute('write', $deleteQuery, $this->data);

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
        $this->connection->execute('write', $truncate);
    }
}