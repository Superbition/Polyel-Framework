<?php

namespace Polyel\Router\Facade;

use Polyel;

class Route
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Router\Router::class)->$method(...$arguments);
    }
}