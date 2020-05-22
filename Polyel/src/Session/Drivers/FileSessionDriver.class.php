<?php

namespace Polyel\Session\Drivers;

use Polyel\Http\Facade\Cookie;
use Polyel\Storage\Facade\Storage;

class FileSessionDriver implements SessionDriver
{
    private $sessionFileStorage = ROOT_DIR . '/storage/polyel/sessions/';

    private $jsonEncodeOptions = JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

    private $jsonDecodeOptions = JSON_INVALID_UTF8_SUBSTITUTE;

    public function __construct()
    {

    }

    /*
     * SessionID is the ID from the cookie data.
     * SessionData is what is found from the session file, the default has to be false and not null
     * because if it is null, it would mean no data was found but checking session data is optional.
     */
    public function isValid($sessionID, $sessionData = false)
    {
        // Check to see if the session file exists
        if(file_exists($this->sessionFileStorage . $sessionID) === false)
        {
            // False when no file is found matching the session ID
            return false;
        }

        // Check that the session data exists
        if(exists($sessionData))
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

    public function collisionCheckID($sessionID)
    {
        if(file_exists($this->sessionFileStorage . $sessionID))
        {
            return true;
        }

        return false;
    }

    public function createNewSession($sessionID, $request)
    {
        $sessionData['id'] = $sessionID;
        $sessionData['user_id'] = null;
        $sessionData['ip_addr'] = $request->clientIP;
        $sessionData['user_agent'] = $request->userAgent;
        $sessionData['last_active'] = date("Y-m-d H:i:s");
        $sessionData['data'] = null;

        $sessionData = json_encode($sessionData, $this->jsonEncodeOptions, 1024);

        $sessionFilePath = '/storage/polyel/sessions/' . $sessionID;
        Storage::access('local')->write($sessionFilePath, $sessionData);
    }

    public function saveSessionData($sessionID, $sessionData)
    {
        $sessionData = json_encode($sessionData, $this->jsonEncodeOptions, 1024);

        $sessionFilePath = '/storage/polyel/sessions/' . $sessionID;
        Storage::access('local')->write($sessionFilePath, $sessionData);
    }

    public function getSessionData($sessionID)
    {
        if(file_exists($this->sessionFileStorage . $sessionID))
        {
            $jsonData = Storage::access('local')->read($this->sessionFileStorage . $sessionID);

            $jsonData = json_decode($jsonData, true, 1024, $this->jsonDecodeOptions);

            return $jsonData;
        }

        return null;
    }

    public function destroySession($sessionID)
    {
        if(exists($sessionID) && file_exists($this->sessionFileStorage . $sessionID))
        {
            unlink(realpath($this->sessionFileStorage . $sessionID));
        }

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

        Cookie::queue(...$sessionCookie);
    }

    public function clear($sessionID)
    {
        $sessionData = $this->getSessionData($sessionID);

        if(exists($sessionData))
        {
            $sessionData['data'] = null;

            $this->saveSessionData($sessionID, $sessionData);
        }
    }
}