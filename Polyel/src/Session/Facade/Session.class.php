<?php

namespace Polyel\Session\Facade;

use Polyel;

class Session
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Session\Session::class)->$method($arguments);
    }
}