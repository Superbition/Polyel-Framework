<?php

namespace Polyel\Http\Facade;

use Polyel;

class Cookie
{
    public static function __callStatic($method, $arguments)
    {
        if($method === "get")
        {
            return Polyel::call(Polyel\Http\Request::class)->cookie(...$arguments);
        }
    }
}