<?php

namespace Polyel\Session\Drivers;

use Polyel\Database\Facade\DB;

class DatabaseSessionDriver implements SessionDriver
{
    public function __construct()
    {

    }

    public function isValid($sessionID, $sessionData = false)
    {
        if(DB::table('session')->where('id', '=', $sessionID)->first() === null)
        {
            return false;
        }

        if($sessionData)
        {
            // Make sure the session ID from the cookie matches the session ID inside the session file
            if(!array_key_exists('id', $sessionData) || $sessionData['id'] !== $sessionID)
            {
                // False when the session ID does not match the ID in the file, not just the name of the session file
                return false;
            }
        }
        else
        {
            // If the data passed in is null, it means the session file was empty or the data was missing
            if($sessionData === null)
            {
                // False when no session data exists but the file does
                return false;
            }
        }

        // If nothing fails within this function, the session is deemed valid
        return true;
    }

    public function getSessionData($sessionID)
    {
        if($sessionData = DB::table('session')->where('id', '=', $sessionID)->first())
        {
            // TODO: Check if need to decode JSON here

            return $sessionData;
        }

        return null;
    }
}