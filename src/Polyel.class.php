<?php

use Polyel\Container\Container;

class Polyel
{
    private const version = '0.8.1';

    // Used to access the HTTP server object
    private static $server;

    private static Polyel\Container\Container $container;

    public static function version()
    {
        return self::version;
    }

    public static function setServer($server)
    {
        self::$server = $server;
    }

    public static function task($data)
    {
        self::$server->task($data);
    }

    public static function createContainer($baseClass)
    {
        self::$container = new Container($baseClass);
    }

    public static function registerBindService(string $class, Closure $service)
    {
        self::$container->bind($class, $service);
    }

    public static function registerSingletonService(string $class, Closure $service, $defer = false, $sharable = true)
    {
        self::$container->singleton($class, $service, $defer, $sharable);
    }

    public static function resolveClass($classToResolve)
    {
        return self::$container->resolveClass($classToResolve);
    }

    public static function resolveClassMethod($class, $methodToResolve)
    {
        return self::$container->resolveMethodInjection($class, $methodToResolve);
    }

    public static function call($requestedClass)
    {
        return self::$container->get($requestedClass);
    }

    public static function new($class)
    {
        return self::$container->new($class);
    }

    public static function newHttpKernel(array $binds, array $requestSingletons)
    {
        /*
         * Create a new container for each new HTTP Kernel
         * that handles a request. Pass in the application Kernel
         * class to start off with and the registered service
         * singletons that are local to a request.
         * Also include sharable server objects which are just global
         * singleton services resolved from the main Polyel
         * container instance.
         */
        $kernelContainer = new Container(
            App\Http\Kernel::class,
            $binds,
            $requestSingletons,
            self::$container->getShareableObjects()
        );

        $HttpKernel = $kernelContainer->get(App\Http\Kernel::class);

        $HttpKernel->setContainer($kernelContainer);

        $HttpKernel->setup();

        return $HttpKernel;
    }

    public static function newConsoleKernel()
    {
        require_once APP_DIR . '/app/Console/Kernel.php';

        return self::resolveClass(App\Console\Kernel::class);
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
