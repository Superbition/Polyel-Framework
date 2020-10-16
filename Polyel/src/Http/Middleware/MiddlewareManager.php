<?php

namespace Polyel\Http\Middleware;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class MiddlewareManager
{
    private $middlewareDirectory = ROOT_DIR . "/app/Http/Middleware/";

    // Holds all registered Middlewares, in the format of [requestMethod][uri] = middleware
    private $middlewares = [];

    public function __construct()
    {

    }

    public function loadAllMiddleware()
    {
        $middlewareDir = new RecursiveDirectoryIterator($this->middlewareDirectory);
        $pathIterator = new RecursiveIteratorIterator($middlewareDir);

        // Search through the Middleware directory for .php files to preload as Middleware
        foreach($pathIterator as $middleware)
        {
            $middlewareFilePath = $middleware->getPathname();

            // Only match .php files
            if(preg_match('/^.+\.php$/i', $middlewareFilePath))
            {
                // Make the class available by declaring it
                require_once $middlewareFilePath;
            }
        }
    }

    public function register($requestMethod, $uri, $middleware)
    {
        /*
         * Add an ending slash on the URI so that middleware
         * routes will match with registered routes because
         * not knowing if the end slash is there or not is
         * ambiguous.
         */
        if($uri[-1] !== '/')
        {
            $uri .= '/';
        }

        $this->middlewares[$requestMethod][$uri] = $middleware;
    }

    private function getMiddlewareParamsFromKey(&$middlewareKey)
    {
        /*
         * The middleware key will contain first, the middleware name itself, then any params after a ':'
         * Also remove any whitespace from the middleware key before exploding into an array.
         */
        $keys = explode(':', preg_replace('/\s+/', '', $middlewareKey));

        // The first key is always the name of the middleware, the key is changed by ref here
        $middlewareKey = $keys[0];

        // If keys is more than 1 element, it means we have middleware parameters to process...
        if(count($keys) > 1)
        {
            // Return all the middleware parameters, splitting on commas for multiple params
            return explode(',', $keys[1]);
        }

        // No middleware params were set, return an empty param array...
        return [];
    }

    public function runGlobalMiddleware($HttpKernel, $middlewareType)
    {
        $globalBeforeMiddleware = config("middleware.global." . $middlewareType);

        foreach($globalBeforeMiddleware as $middlewareKey)
        {
            // Extract any middleware params and put the middleware key on its own...
            $middlewareParams = $this->getMiddlewareParamsFromKey($middlewareKey);

            // Use config() to get the full namespace based on the middleware key
            $middlewareKey = config("middleware.keys." . $middlewareKey);

            // Call Polyel and get the middleware class from the container
            $middlewareToRun = $HttpKernel->container->resolveClass($middlewareKey);

            // Based on the passed in middleware type, execute if both types match
            if($middlewareToRun->middlewareType === $middlewareType)
            {
                // Only the after Middleware type can use the $response service
                if($middlewareType === 'before')
                {
                    // Process the middleware if the request types match up
                    $response = $middlewareToRun->process($HttpKernel->request, ...$middlewareParams);
                }
                else
                {
                    // Process the middleware if the request types match up
                    $response = $middlewareToRun->process($HttpKernel->request, $HttpKernel->response, ...$middlewareParams);
                }

                // If a Middleware wants to return a response early, halt and send it back
                if(exists($response))
                {
                    // Halt any more execution and send back a response...
                    return $response;
                }
            }
        }
    }

    /*
     * Runs any middleware based on the type passed in and processes the stage of the application,
     * before or after. $applicationStage is the request or response service that gets passed in to
     * allow a middleware to process its correct type.
     */
    private function runMiddleware($HttpKernel, $type, $requestMethod, $route)
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
                    // Extract any middleware params and put the middleware key on its own...
                    $middlewareParams = $this->getMiddlewareParamsFromKey($middlewareKey);

                    // Use config() to get the full namespace based on the middleware key
                    $middleware = config("middleware.keys." . $middlewareKey);

                    // Call Polyel and get the middleware class from the container
                    $middlewareToRun = $HttpKernel->container->resolveClass($middleware);

                    // Based on the passed in middleware type, execute if both types match
                    if($middlewareToRun->middlewareType === $type)
                    {
                        // Only the after Middleware type can use the $response service
                        if($type === 'before')
                        {
                            // Process the middleware if the request types match up
                            $response = $middlewareToRun->process($HttpKernel->request, ...$middlewareParams);
                        }
                        else
                        {
                            // Process the middleware if the request types match up
                            $response = $middlewareToRun->process($HttpKernel->request, $HttpKernel->response, ...$middlewareParams);
                        }

                        // If a Middleware wants to return a response early, halt and send it back
                        if(exists($response))
                        {
                            // Halt any more execution and send back a response...
                            return $response;
                        }
                    }
                }
            }
        }
    }

    public function runAnyBefore($HttpKernel, $requestMethod, $route)
    {
        $globalResponse = $this->runGlobalMiddleware($HttpKernel, 'before');

        if(exists($globalResponse))
        {
            // Halt any more execution and send back a response...
            return $globalResponse;
        }

        $beforeResponse = $this->runMiddleware($HttpKernel, "before", $requestMethod, $route);

        if(exists($beforeResponse))
        {
            // Halt any more execution and send back a response...
            return $beforeResponse;
        }

        // No Middleware has formed a response to be sent back
        return null;
    }

    public function runAnyAfter($HttpKernel, $requestMethod, $route)
    {
        $globalResponse = $this->runGlobalMiddleware($HttpKernel, "after");

        if(exists($globalResponse))
        {
            // Halt any more execution and send back a response...
            return $globalResponse;
        }

        $afterResponse = $this->runMiddleware($HttpKernel, "after", $requestMethod, $route);

        if(exists($afterResponse))
        {
            // Halt any more execution and send back a response...
            return $afterResponse;
        }

        // No Middleware has formed a response to be sent back
        return null;
    }
}