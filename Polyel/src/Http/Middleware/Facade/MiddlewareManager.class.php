<?php

namespace Polyel\Http\Middleware\Facade;

use Polyel;

/**
 * Class MiddlewareManager
 *
 * @method static getStackForRoute($requestMethod, $requestUrl)
 *
 * @method static prepareStack($HttpKernel, $routeMiddlewareStack)
 *
 * @method static executeStackWithCoreAction($HttpKernel, $middlewareStack, $coreAction)
 */
class MiddlewareManager
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Http\Middleware\MiddlewareManager::class)->$method(...$arguments);
    }
}