<?php

namespace Polyel\Session;

use Polyel;
use Polyel\Http\Request;
use Polyel\Http\Facade\Cookie;

class SessionManager
{
    private $driver;

    private $availableDrivers;

    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->availableDrivers = [
          'file' => Polyel\Session\Drivers\FileSessionDriver::class,
        ];
    }

    public function setDriver($driver)
    {
        if(array_key_exists($driver, $this->availableDrivers))
        {
            $driver = $this->availableDrivers[$driver];
            $this->driver = Polyel::resolveClass($driver);
        }
    }

    public function startSession($sessionCookie)
    {
        // Either cookie does not exist or the session is missing on the server
        if(!exists($sessionCookie) || $this->driver->isValid($sessionCookie) === false)
        {
            $prefix = config('session.prefix');

            do {

                $sessionID = $this->generateSessionID($prefix, 42);

            } while($this->driver->collisionCheckID($sessionID) === true);

            $this->driver->createNewSession($sessionID, $this->request);
            $this->queueSessionCookie($sessionID);
        }
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