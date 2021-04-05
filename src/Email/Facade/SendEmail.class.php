<?php

namespace Polyel\Email\Facade;

use Polyel;

class SendEmail
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::new(Polyel\Email\SendEmail::class)->$method(...$arguments);
    }
}