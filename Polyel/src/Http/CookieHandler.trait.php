<?php

namespace Polyel\Http;

trait CookieHandler
{
    private function requestGetCookie($cookieName)
    {
        if(array_key_exists($cookieName, $this->cookies))
        {
            return $this->cookies[$cookieName];
        }

        return false;
    }
}