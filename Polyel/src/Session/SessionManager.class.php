<?php

namespace Polyel\Session;

use Polyel\Http\Facade\Cookie;
use Polyel\Storage\Facade\Storage;

class SessionManager
{
    private $sessionFileStorage = ROOT_DIR . '/storage/polyel/sessions/';

    private $driver;

    public function __construct()
    {

    }

    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    public function startSession($sessionCookie)
    {
        // Either cookie does not exist or the session is missing on the server
        if(!exists($sessionCookie) || $this->isValid($sessionCookie) === false)
        {
            $prefix = config('session.prefix');

            do {

                $sessionID = $this->generateSessionID($prefix, 42);

            } while($this->collisionCheckID($sessionID) === true);

            $this->createNewSession($sessionID);
            $this->queueSessionCookie($sessionID);
        }
    }

    private function isValid($sessionID)
    {
        if(file_exists($this->sessionFileStorage . $sessionID) === false)
        {
            return false;
        }

        return true;
    }

    private function generateSessionID($prefix, $length): string
    {
        $sessionPrefix = $prefix;
        $sessionID = $sessionPrefix;
        $idStringSpace = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($idStringSpace);

        for ($i=0; $i < $length; $i++)
        {
            $sessionID .= $idStringSpace[random_int(0, $max-1)];
        }

        return $sessionID;
    }

    private function collisionCheckID($sessionID)
    {
        if(file_exists($this->sessionFileStorage . $sessionID))
        {
            return true;
        }

        return false;
    }

    private function createNewSession($sessionID)
    {
        $sessionFilePath = '/storage/polyel/sessions/' . $sessionID;
        Storage::access('local')->write($sessionFilePath, '');
    }

    private function queueSessionCookie($sessionID)
    {
        $sessionLifetime = config('session.lifetime');

        if($sessionLifetime !== 0 && is_numeric($sessionLifetime))
        {
            $sessionLifetime *= 60;
        }

        $sessionCookie = [
            $name = config('session.cookieName'),
            $value = $sessionID,
            $expire = $sessionLifetime,
            $path = config('session.cookiePath'),
            $domain = config('session.domain'),
            $secure = config('session.secure'),
            $httpOnly = config('session.httpOnly'),
            $sameSite = 'None',
        ];

        Cookie::queue(...$sessionCookie);
    }
}