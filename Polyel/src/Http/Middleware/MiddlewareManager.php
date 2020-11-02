<?php

namespace Polyel\Http\Middleware;

use RuntimeException;
use Polyel\Http\Request;
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

        /*
         * Register a new middleware and assign it to the given request
         * method and URI or append the middleware to an existing URI which
         * is already part of a request method like GET, POST or PUT etc.
         */
        if(!isset($this->middlewares[$requestMethod][$uri]))
        {
            $this->middlewares[$requestMethod][$uri] = $middleware;
        }
        else
        {
            $this->middlewares[$requestMethod][$uri] = array_merge($this->middlewares[$requestMethod][$uri], $middleware);
        }
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

    public function prepareStack($HttpKernel, $routeMiddlewareStack, $routeMiddlewareAliases, $globalMiddlewareStack, $middlewareGroups)
    {
        // Combined prepared route and global middleware stack array
        $preparedMiddlewareStack = [];

        // The global middleware stack comes first
        $middlewareStack = array_merge($globalMiddlewareStack, $routeMiddlewareStack);
        /*
         * Scan the stack for any middleware group names, if
         * middleware group names are found, merge that middleware
         * group into the stack, using the object namespaces from the group.
         */
        foreach($middlewareStack as $key => $middleware)
        {
            if(array_key_exists($middleware, $middlewareGroups))
            {
                // Using the group name, place the group stack in position of where the group name is within the stack array
                array_splice($middlewareStack, $key, 0, $middlewareGroups[$middleware]);

                // Remove the name of the group from the stack
                unset($middlewareStack[$key + 1]);
            }
        }

        /*
         * We have to reverse the stack before we use it because when the layers
         * are created it will start with the first item in the array, meaning the
         * first item will be the closest to the core action, which would be wrong as it
         * does not respect the middleware order from how they are assigned in the Kernel
         * and with routes. So we flip the array so that the first item will be the last
         * outer middleware to be executed when our layered are created.
         */
        $middlewareStack = array_reverse($middlewareStack);

        foreach($middlewareStack as $middleware)
        {
            $middlewareParams = [];

            // A string means we have found a route middleware key with potential middleware parameters
            if(is_string($middleware))
            {
                // Extract any middleware parameters and convert the middleware key on its own...
                $middlewareParams = $this->getMiddlewareParamsFromKey($middleware);

                if(array_key_exists($middleware, $routeMiddlewareAliases))
                {
                    // Get the middleware full namespace using the middleware alias
                    $middleware = $routeMiddlewareAliases[$middleware];
                }
            }

            $middleware = $HttpKernel->container->resolveClass($middleware);

            $preparedMiddlewareStack[] = ['class' => $middleware, 'params' => $middlewareParams];
        }

        return $preparedMiddlewareStack;
    }

    public function generateStackForRoute($requestMethod, $requestUrl)
    {
        $routeMiddlewareStack = [];

        // Check if a middleware stack exists for the request method, GET, POST etc.
        if(array_key_exists($requestMethod, $this->middlewares))
        {
            // Then check for a middleware stack inside that request method, for a route (URL)...
            if(array_key_exists($requestUrl, $this->middlewares[$requestMethod]))
            {
                // Get the middleware stack for this request method and route (URL)
                $routeMiddlewareStack = $this->middlewares[$requestMethod][$requestUrl];
            }
        }

        return $routeMiddlewareStack;
    }

    public function executeStackWithCoreAction(Request $request, $middlewareStack, $coreAction)
    {
        /*
         * Create a middleware stack where the core action is in the centre
         * and the core gets wrapped with all the middleware from the stack.
         * Each layer will have its own closure function which can execute
         * its middleware operation. If no middleware returns a response, the
         * request is passed along to the next middleware and the final layer
         * will be the core action at the end.
         */
        $middlewareStackWithCore = array_reduce($middlewareStack, function($nextMiddleware, $middleware)
        {
            return $this->createMiddlewareLayer($nextMiddleware, $middleware);
        }, $coreAction);

        /*
         * With all the closure layers, execute each layer and pass
         * down the request object, then the final response to be built
         * is returned.
         */
        return $middlewareStackWithCore($request);
    }

    private function createMiddlewareLayer($nextMiddleware, $middleware)
    {
        // Creates a middleware layer which gets executed later, returning the middleware response
        return function(Request $request) use($nextMiddleware, $middleware)
        {
            $middlewareResponse = $middleware['class']->process($request, $nextMiddleware, ...$middleware['params']);

            if(is_null($middlewareResponse))
            {
                throw new RuntimeException('Middleware must always return a response');
            }

            return $middlewareResponse;
        };
    }
}