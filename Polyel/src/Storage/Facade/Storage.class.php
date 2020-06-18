<?php

namespace Polyel\Storage\Facade;

use Polyel;

class Storage
{
    public static function __callStatic($method, $arguments)
    {
        if($method === 'drive' || $method === 'access')
        {
            return Polyel::call(Polyel\Storage\Storage::class)->$method(...$arguments);
        }

        return Polyel::call(Polyel\Storage\Storage::class)->drive(null)->$method(...$arguments);
    }
}