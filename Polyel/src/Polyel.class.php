<?php

use Polyel\Container\Container;

class Polyel
{
    private const version = '0.0.0';

    private static $container;

    public static function version()
    {
        return self::version;
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

    public static function new($class)
    {
        return self::$container->new($class);
    }

    public static function newHttpKernel()
    {
        $kernelContainer = new Container(Polyel\Http\Kernel::class);

        $HttpKernel = $kernelContainer->get(Polyel\Http\Kernel::class);

        $HttpKernel->setContainer($kernelContainer);

        $HttpKernel->setup();

        return $HttpKernel;
    }

    public static function resolveMethod($class, $method)
    {
        return self::$container->resolveMethodInjection($class, $method);
    }

    public static function containerList()
    {
        return self::$container->list();
    }
}