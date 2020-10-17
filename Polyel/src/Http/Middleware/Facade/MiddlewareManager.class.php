<?php

namespace Polyel\Http\Middleware\Facade;

use Polyel;

class MiddlewareManager
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Http\Middleware\MiddlewareManager::class)->$method(...$arguments);
    }
}