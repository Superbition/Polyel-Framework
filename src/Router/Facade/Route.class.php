<?php

namespace Polyel\Router\Facade;

use Polyel;
use Closure;

/**
 * @method static get($route, $action)
 * @method static post($route, $action)
 * @method static put($route, $action)
 * @method static patch($route, $action)
 * @method static delete($route, $action)
 * @method static group($attributes, Closure $routes)
 * @method static addAuthRoutes()
 */
class Route
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Router\Router::class)->$method(...$arguments);
    }
}