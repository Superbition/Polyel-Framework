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
        // Only proceed if the credentials exist and that it's not the password that was just sent...
        if(!exists($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials)))
        {
            return false;
        }

        // Start off the query that will be used to search for a user...
        $query = DB::table($this->table());

        /*
         * Using a for loop, build up each credential and
         * add them as a where clause which will be used to
         * search for a user we want to find by their credentials.
         * This is mostly used for when you are logging in a user and
         * trying to find them via an email or username etc.
         */
        foreach($credentials as $key => $value)
        {
            // We don't want to search for a user based on their password as it will be hashed...
            if($key === 'password')
            {
                continue;
            }

            $query->where($key, '=', $value);
        }

        // Execute the query but only grab the first record, only one record should be found though
        $user = $query->first();

        // Return the database retrieval result based on credentials
        return $user;
    }
}