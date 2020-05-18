<?php

namespace Polyel\Session\Drivers;

use Polyel\Storage\Facade\Storage;

class FileSessionDriver implements SessionDriver
{
    private $sessionFileStorage = ROOT_DIR . '/storage/polyel/sessions/';

    public function __construct()
    {

    }

    public function isValid($sessionID)
    {
        if(file_exists($this->sessionFileStorage . $sessionID) === false)
        {
            return false;
        }

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

        $jsonOptions = JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT;
        $sessionData = json_encode($sessionData, $jsonOptions, 1024);

        $sessionFilePath = '/storage/polyel/sessions/' . $sessionID;
        Storage::access('local')->write($sessionFilePath, $sessionData);
    }

    public function getSessionData($sessionID)
    {
        if(file_exists($this->sessionFileStorage . $sessionID))
        {
            $jsonData = Storage::access('local')->read($this->sessionFileStorage . $sessionID);

            $jsonOptions = JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT;
            $jsonData = json_decode($jsonData, $jsonOptions, 1024);

            return $jsonData;
        }

        return null;
    }
}