<?php

namespace Polyel\Router;

use Polyel;

class Route
{
    private static $router;

    public static function get($route, $action)
    {
        Polyel::call(Polyel\Router\Router::class)->get($route, $action);
    }
}