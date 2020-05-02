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

        if($method === "queue")
        {
            return Polyel::call(Polyel\Http\Response::class)->queueCookie(...$arguments);
        }
    }
}