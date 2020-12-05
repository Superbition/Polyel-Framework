<?php

namespace Polyel\Session;

use Polyel;
use Polyel\Http\Kernel;
use Swoole\Timer as Timer;

class SessionManager
{
    private $driver;

    private $availableDrivers;

    private $gcStarted = false;

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

    public function driver()
    {
        return $this->driver;
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

    public function startSession(Kernel $HttpKernel)
    {
        $sessionCookieID = $HttpKernel->request->cookie(config('session.cookieName'));

        $sessionData = $this->driver->getSessionData($sessionCookieID);

        // Either cookie does not exist or the session is missing on the server
        if(!exists($sessionCookieID) || $this->driver->isValid($sessionCookieID, $sessionData) === false)
        {
            $sessionCookieID = $this->regenerateSession($HttpKernel->request, $HttpKernel->response);
        }

        // Update session ip, agent and last active time
        $this->driver->updateSession($sessionCookieID, $HttpKernel->request);

        $HttpKernel->setSessionID($sessionCookieID);

        // The session gc is started on the first request once the server is booted
        if($this->gcStarted == false)
        {
            // Register the non-blocking session gc timer task
            $this->startGc();
        }
    }

    public function regenerateSession($request, $response)
    {
        $prefix = config('session.prefix');

        do {

            $newSessionID = $this->generateSessionID($prefix, 42);

        } while($this->driver->collisionCheckID($newSessionID) === true);

        $this->driver->createNewSession($newSessionID, $request);
        $this->queueSessionCookie($newSessionID, $response);

        return $newSessionID;
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

    private function queueSessionCookie($sessionID, $response)
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
            $sameSite = 'Strict',
        ];

        $response->queueCookie(...$sessionCookie);
    }

    public function generateCsrfToken($length = 64)
    {
        return bin2hex(random_bytes($length));
    }
}