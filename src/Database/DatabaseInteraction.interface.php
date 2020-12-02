<?php

namespace Polyel\Database;

interface DatabaseInteraction
{
    public function raw($query, $data = null, $type = 'write');

    public function select($query, $data = null);

    public function insert($query, $data = null);

    public function update($query, $data = null);

    public function delete($query, $data = null);

    public function table($table);
}