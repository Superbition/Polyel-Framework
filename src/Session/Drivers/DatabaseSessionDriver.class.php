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

    public function updateSession($sessionID, $request)
    {
        $sessionData = $this->getSessionData($sessionID);

        $sessionData['ip_addr'] = $request->clientIP;
        $sessionData['user_agent'] = $request->userAgent;
        $sessionData['last_active'] = date("Y-m-d H:i:s");

        $this->saveSessionData($sessionID, $sessionData);
    }

    public function gc()
    {
        go(function()
        {
            DB::table('session')->orderBy('id')->chunk(100, function($sessions) {

                $lifetime = config('session.lifetime');

                foreach($sessions as $session)
                {
                    // Setup the expired date format
                    $expiredTime = strtotime("-$lifetime minutes");

                    // If the session has passed the lifetime timestamp, it is invalid and old, delete it
                    if($expiredTime > strtotime($session['last_active']))
                    {
                        DB::table('session')
                            ->where('id', '=', $session['id'])
                            ->delete();
                    }
                }

            });
        });
    }

    public function collisionCheckID($sessionID)
    {
        if(DB::table('session')->where('id', '=', $sessionID)->first())
        {
            return true;
        }

        return false;
    }

    public function createNewSession($sessionID, $request)
    {
        $sessionData = [
            'id' => $sessionID,
            'user_id' => null,
            'ip_addr' => $request->clientIP,
            'user_agent' => $request->userAgent,
            'last_active' => date("Y-m-d H:i:s"),
            'data' => '{}',
        ];

        DB::table('session')->insert($sessionData);
    }

    public function saveSessionData($sessionID, $sessionData)
    {
        $sessionData = json_encode($sessionData, $this->jsonEncodeOptions, 1024);

        DB::table('session')
            ->where('id', '=', $sessionID)
            ->update(['data' => $sessionData]);
    }

    public function getSessionData($sessionID)
    {
        if($sessionData = DB::table('session')->select('*')->where('id', '=', $sessionID)->first())
        {
            // TODO: Check if need to decode JSON here

            return $sessionData;
        }

        return null;
    }
}