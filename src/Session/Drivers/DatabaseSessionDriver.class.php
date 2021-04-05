<?php

namespace Polyel\Session\Drivers;

use Polyel\Database\Facade\DB;

class DatabaseSessionDriver implements SessionDriver
{
    private $jsonEncodeOptions = JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

    private $jsonDecodeOptions = JSON_INVALID_UTF8_SUBSTITUTE;

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
        // To update the session we need the current session data so it doesn't get overwritten
        $sessionData = $this->getSessionData($sessionID);

        // Update session data which could have changed between requests and when the session was last active
        $sessionData['ip_addr'] = $request->clientIP;
        $sessionData['user_agent'] = $request->userAgent;
        $sessionData['last_active'] = date("Y-m-d H:i:s");

        // Re-save the updated session data
        $this->saveSessionData($sessionID, $sessionData);
    }

    public function gc()
    {
        go(function()
        {
            // Operate only by 100 sessions at a time from the DB...
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
        // If no ID already exists we should get a null value back
        if(DB::table('session')->where('id', '=', $sessionID)->first() === null)
        {
            return true;
        }

        // A collision check has failed
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
        // Encode the data as JSON again before updating in the database
        $sessionData['data'] = json_encode($sessionData['data'], $this->jsonEncodeOptions, 1024);

        // Update the session row in the DB with new values and with the data column encoded as JSON
        DB::table('session')
            ->where('id', '=', $sessionID)
            ->update([
                'user_id' => $sessionData['user_id'],
                'ip_addr' => $sessionData['ip_addr'],
                'user_agent' => $sessionData['user_agent'],
                'last_active' => $sessionData['last_active'],
                'data' => $sessionData['data'],
            ]);
    }

    public function getSessionData($sessionID)
    {
        // Check to see if we have a valid session first
        if($sessionData = DB::table('session')->select('*')->where('id', '=', $sessionID)->first())
        {
            // Decode the session data from JSON to a PHP array
            $sessionData['data'] = json_decode($sessionData['data'], true, 1024, $this->jsonDecodeOptions);

            return $sessionData;
        }

        // No session was found
        return null;
    }

    public function destroySession($sessionID, $destroyCookie = true)
    {
        // To destroy a session, it needs to already exist and be valid
        if($this->isValid($sessionID))
        {
            DB::table('session')
                ->where('id', '=', $sessionID)
                ->delete();
        }

        // TODO: Review this feature of destroying a cookie here
        if($destroyCookie)
        {
            $sessionCookie = [
                $name = config('session.cookieName'),
                $value = null,
                $expire = -1,
                $path = config('session.cookiePath'),
                $domain = config('session.domain'),
                $secure = config('session.secure'),
                $httpOnly = config('session.httpOnly'),
                $sameSite = 'None',
            ];

            // TODO: review this method of queuing a cookie
            Cookie::queue(...$sessionCookie);
        }
    }

    public function clear($sessionID)
    {
        $sessionData = $this->getSessionData($sessionID);

        // If session data exists, clear the data column by setting it to null
        if(exists($sessionData))
        {
            $sessionData['data'] = null;

            $this->saveSessionData($sessionID, $sessionData);
        }
    }
}