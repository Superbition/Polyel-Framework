<?php

namespace Polyel\Auth\SourceDrivers;

use Polyel\Database\Facade\DB;

class Database
{
    // The table containing the users
    private string $table;

    public function __construct()
    {

    }

    public function setTable($table)
    {
        $this->table = $table;
    }

    public function getUserById($id)
    {
        return DB::table($this->table)->findById($id);
    }

    public function getUserByToken($token)
    {

    }

    public function getUserByCredentials($credentials)
    {

    }
}