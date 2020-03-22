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

    private function runMiddleware($type, $requestMethod, $route)
    {
        // Check if a middleware exists for the request method, GET, POST etc.
        if(array_key_exists($requestMethod, $this->middlewares))
        {
            // Then check for a middleware inside that request method, for a route...
            if(array_key_exists($route, $this->middlewares[$requestMethod]))
            {
                // Get the middleware key(s) set for this request method and route
                $middlewareKeys = $this->middlewares[$requestMethod][$route];

                // Turn the middleware key into a array if its only one middleware
                if(!is_array($middlewareKeys))
                {
                    // An array makes it easier to process single and multiple middlewares, no duplicate code...
                    $middlewareKeys = [$middlewareKeys];
                }

                // Process each middleware and run process() from each middleware
                foreach($middlewareKeys as $middlewareKey)
                {
                    // Use config() to get the full namespace based on the middleware key
                    $middleware = config("middleware.keys." . $middlewareKey);

                    // Call Polyel and get the middleware class from the container
                    $middlewareToRun = Polyel::call($middleware);

                    // Based on the passed in middleware type, execute if both types match
                    if($middlewareToRun->middlewareType == $type)
                    {
                        // Process the middleware if the request types match up
                        $middlewareToRun->process();
                    }
                }
            }
        }
    }

    public function runAnyBefore($requestMethod, $route)
    {
        $this->runMiddleware("before", $requestMethod, $route);
    }

    public function runAnyAfter($requestMethod, $route)
    {
        $this->runMiddleware("after", $requestMethod, $route);
    }
}