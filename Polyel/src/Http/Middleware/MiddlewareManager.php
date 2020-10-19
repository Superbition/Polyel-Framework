<?php

namespace Polyel\Http\Middleware;

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

    public function prepareStack($HttpKernel, $routeMiddlewareStack, $routeMiddlewareAliases, $globalMiddlewareStack)
    {
        // Combined prepared route and global middleware stack array
        $preparedMiddlewareStack = [];

        $middlewareStack = array_merge($globalMiddlewareStack, $routeMiddlewareStack);

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

    public function executeStackWithCoreAction($HttpKernel, $middlewareStack, $coreAction)
    {
        /*
         * Create a middleware stack where the core action is in the centre
         * and the core gets wrapped with all the middleware from the stack.
         * Each layer will have its own closure function which can execute
         * its middleware operation. If no middleware returns a response, the
         * request is passed along to the next middleware and the final layer,
         * will be the core action, at the end.
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
        return $middlewareStackWithCore($HttpKernel->request);
    }

    private function createMiddlewareLayer($nextMiddleware, $middleware)
    {
        // Creates a middleware layer which gets executed later, returning the middleware response
        return function(Request $request) use($nextMiddleware, $middleware)
        {
            return $middleware['class']->process($request, $nextMiddleware, ...$middleware['params']);
        };
    }
}