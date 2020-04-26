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
}