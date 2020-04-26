<?php

namespace Polyel\Http;

trait CookieHandler
{
    // Queued cookies are added to the response later...
    private $queuedCookies;

    private function requestGetCookie($cookieName)
    {
        if(array_key_exists($cookieName, $this->cookies))
        {
            return $this->cookies[$cookieName];
        }

        return false;
    }

    private function queueCookieForResponse($cookies)
    {
        foreach($cookies as $cookie)
        {
            $this->queuedCookies[] = $cookie;
        }
    }

    public function queueCookie($name, $value, $expire = 86400, $path = "/", $domain = "", $secure = false, $httpOnly = true, $sameSite = "None")
    {
        // Setup the cookie in an array, ready for the Response service to process later
        $cookie = [
            $name,
            $value,
            $expire = (time() + $expire),
            $path,
            $domain,
            $secure,
            $httpOnly,
            $sameSite
        ];

        // Add the cookie to the queue list
        $this->queuedCookies[] = $cookie;
    }
}