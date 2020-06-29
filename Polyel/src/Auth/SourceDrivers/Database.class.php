<?php

namespace Polyel\Auth\SourceDrivers;

use Polyel\Database\Facade\DB;

class Database
{
    // The table name containing the users
    private string $table;

    public function __construct()
    {

    }

    public function setTable($table)
    {
        $this->table = $table;
    }

    public function table()
    {
        /*
         * If the source table has not been set, use the default table source
         * from the auth config. Here we get the default protector and use the
         * default source set for that protector.
         */
        if(!isset($this->table))
        {
            $defaultProtector = config('auth.defaults.protector');
            $authSource = config("auth.protectors.$defaultProtector.source");
            $this->table = config("auth.sources.$authSource.table");
        }

        return $this->table;
    }

    public function getUserById($id)
    {
        return DB::table($this->table())->findById($id);
    }

    public function getUserByToken($token)
    {

    }

    public function getUserByCredentials($credentials)
    {

    }
}