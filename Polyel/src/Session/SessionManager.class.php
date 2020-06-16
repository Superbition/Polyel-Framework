<?php

namespace Polyel\Session;

use Polyel;
use Polyel\Http\Request;
use Swoole\Timer as Timer;
use Polyel\Http\Facade\Cookie;

class SessionManager
{
    private $driver;

    private $availableDrivers;

    private $gcStarted = false;

    // Holds the current request session ID so the session service has access to it
    private $currentRequestSessionID;

    public function __construct()
    {
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

    public function startGc()
    {
        /*
         * Register a Swoole timer to gc sessions every 30 minutes.
         * Pass in access to the session driver that is configured so it can
         * run the gc function.
         */
        Timer::tick(1800000, function($timerID, $sessionDriver)
        {
            $sessionDriver->gc();
        }, $this->driver);

        // Mark the session gc as started
        $this->gcStarted = true;
    }

    public function startSession($HttpKernel)
    {
        $sessionCookieID = $HttpKernel->request->cookie(config('session.cookieName'));

        $sessionData = $this->driver->getSessionData($sessionCookieID);

        // Either cookie does not exist or the session is missing on the server
        if(!exists($sessionCookieID) || $this->driver->isValid($sessionCookieID, $sessionData) === false)
        {
            $this->regenerateSession();
        }

        // Update session ip, agent and last active time
        $this->driver->updateSession($this->currentRequestSessionID, $this->request);

        // The session gc is started on the first request once the server is booted
        if($this->gcStarted == false)
        {
            // Register the non-blocking session gc timer task
            $this->startGc();
        }
    }

    public function regenerateSession()
    {
        $prefix = config('session.prefix');

        do {

            $sessionID = $this->generateSessionID($prefix, 42);

        } while($this->driver->collisionCheckID($sessionID) === true);

        $this->driver->createNewSession($sessionID, $this->request);
        $this->queueSessionCookie($sessionID);

        $this->currentRequestSessionID = $sessionID;

        return $sessionID;
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

    public function getCurrentRequestSessionID()
    {
        return $this->currentRequestSessionID;
    }

    public function clearCurrentRequestSessionID()
    {
        $this->currentRequestSessionID = null;
    }

    public function driver()
    {
        return $this->driver;
    }
}