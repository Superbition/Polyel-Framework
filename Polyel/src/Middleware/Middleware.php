<?php

namespace Polyel\Middleware;

use Polyel;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Middleware
{
    private $middlewareDirectory = ROOT_DIR . "/app/Middleware/";

    private $middlewares = [];

    public function __construct()
    {

    }

    public function loadAllMiddleware()
    {
        $middlewareDir = new RecursiveDirectoryIterator($this->middlewareDirectory);
        $pathIterator = new RecursiveIteratorIterator($middlewareDir);

        foreach($pathIterator as $middleware)
        {
            $middlewareFilePath = $middleware->getPathname();

            if(preg_match('/^.+\.php$/i', $middlewareFilePath))
            {
                // Make the class available by declaring it
                require_once $middlewareFilePath;

                // The last declared class will be the above when it was required_once
                $listOfDefinedClasses = get_declared_classes();

                // Get the last class in the array of declared classes
                $definedClass = explode("\\", end($listOfDefinedClasses));
                $definedClass = end($definedClass);

                Polyel::resolveClass("App\Middleware\\" . $definedClass);
            }
        }
    }

    public function register($requestMethod, $uri, $middleware)
    {
        $this->middlewares[$requestMethod][$uri] = $middleware;
    }
}