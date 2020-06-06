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

        $result = $this->dbManager->execute('write', $deleteQuery, $this->data);

        return (int)$result;
    }

    public function truncate()
    {
        $truncate = "TRUNCATE TABLE $this->from";

        $this->dbManager->execute('write', $truncate);
    }
}