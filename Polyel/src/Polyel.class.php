<?php

use Polyel\Container\Container;

class Polyel
{
    private static $polyelVersion = "";

    private static $container;

    public static function version()
    {
        return self::$polyelVersion;
    }

    public static function createContainer($baseClass)
    {
        self::$container = new Container($baseClass);
    }

    public static function resolveClass($classToResolve)
    {
        return self::$container->resolveClass($classToResolve);
    }

    public static function call($requestedClass)
    {
        return self::$container->get($requestedClass);
    }
}