<?php

namespace Polyel\View\Facade;

use Polyel;

class View
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\View\View::class)::$method(...$arguments);
    }
}