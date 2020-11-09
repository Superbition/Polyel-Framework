<?php

namespace Polyel\Http\Middleware;

use Polyel;
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

    public function optimiseRegisteredMiddleware()
    {
        // We need the App Kernel so we can process the defined middleware within the Kernel
        $httpKernel = Polyel::resolveClass(\App\Http\Kernel::class);

        // Loop through each HTTP method and the routes that have middleware
        foreach($this->middlewares as $method => $routes)
        {
            // Each registered route will contain some middleware
            foreach($routes as $route => $middleware)
            {
                // Foreach middleware stack, optimise and return back the middleware stack
                $optimisedMiddleware = $this->convertMiddlewareIfNotANamespace(
                    $middleware,
                    $httpKernel->getGlobalMiddleware(),
                    $httpKernel->getMiddlewareGroups(),
                    $httpKernel->getRouteMiddlewareAliases(),
                );

                if(!empty($optimisedMiddleware))
                {
                    $this->middlewares[$method][$route] = $optimisedMiddleware;
                }
            }
        }
    }

    private function convertMiddlewareIfNotANamespace(array $middlewares, array $globalMiddleware, array $middlewareGroups, array $routeMiddlewareAliases)
    {
        // Inject any middleware groups into the stack...
        foreach($middlewares as $key => &$middleware)
        {
            // Check to make sure the middleware is a group name...
            if(array_key_exists($middleware, $middlewareGroups))
            {
                /*
                 * Using the group name, place the middleware group stack in the position of
                 * where the group name is within the stack array, injecting the middleware
                 * group into the stack. Array splice uses the array key from the loop to
                 * insert at the correct position.
                 *
                 * By defining the length as 1 the splice will remove the
                 * middleware group name from the stack and replace it with the
                 * actual group middleware stack.
                 */
                array_splice($middlewares, $key, 1, $middlewareGroups[$middleware]);
            }
        }

        // break the reference with the last element because the above foreach loop uses a reference to the value
        unset($middleware);

        /*
         * Once we have checked to see if any middleware groups are
         * being used, we can now get onto converting middleware aliases
         * and aliases that are using middleware parameters to actual
         * full class namespaces, so that none of this conversion has to
         * be done during a request, saving time and reducing the request cycle.
         */

        $optimisedMiddleware = [];

        // The global middleware stack comes first if its not the global middleware stack being optimised
        if(!empty($globalMiddleware))
        {
            // Put the global middleware at the start but optimise the global stack before the merge
            $optimisedMiddleware = array_merge(
                $this->convertMiddlewareIfNotANamespace($globalMiddleware, [], $middlewareGroups, $routeMiddlewareAliases),
                $optimisedMiddleware
            );
        }

        // Optimise each middleware and make sure it is using a full class namespace...
        foreach($middlewares as $middleware)
        {
            // If the class can not be defined it means it is not a full class path/ namespace
            if(!class_exists($middleware, false))
            {
                // Extract any middleware parameters and convert the middleware key on its own...
                [$middlewareName, $middlewareParams] = $this->getMiddlewareParamsFromKey($middleware);

                // If a middleware alias is found...
                if(array_key_exists($middlewareName, $routeMiddlewareAliases))
                {
                    // If we have middleware parameters...
                    if(count($middlewareParams) >= 1)
                    {
                        // Set the middleware full namespace using the middleware alias and params
                        $optimisedMiddleware[] = [$routeMiddlewareAliases[$middlewareName], $middlewareParams];
                    }
                    else
                    {
                        // Else no parameters, just the full class namespace
                        $optimisedMiddleware[] = $routeMiddlewareAliases[$middlewareName];
                    }

                    // Move onto the next middleware as we have already optimised by this point for the current element
                    continue;
                }
            }

            /*
             * Some middleware may not need to be processed as it
             * already may have been defined as a full namespace, but any
             * middleware which has been converted into a full namespace is collected
             * and stored with middleware which is already been defined in an
             * optimised state and returned once this loop has completed.
             */
            $optimisedMiddleware[] = $middleware;
        }

        // Return the array of optimised middleware
        return $optimisedMiddleware;
    }

    private function getMiddlewareParamsFromKey($middlewareName)
    {
        /*
         * The middleware key will contain first, the middleware name itself, then any params after a ':'
         * Also remove any whitespace from the middleware key before exploding into an array.
         */
        $keys = explode(':', preg_replace('/\s+/', '', $middlewareName));

        // The first key is always the name of the middleware, the key is changed by ref here
        $middlewareName = $keys[0];

        // If keys is more than 1 element, it means we have middleware parameters to process...
        if(count($keys) > 1)
        {
            // Return all the middleware parameters, splitting on commas for multiple params
            return [$middlewareName, explode(',', $keys[1])];
        }

        // No middleware params were set, return an empty param array...
        return [$middlewareName, []];
    }

    public function prepareStack($HttpKernel, $routeMiddlewareStack)
    {
        // Combined prepared route and global middleware stack array
        $preparedMiddlewareStack = [];

        /*
         * Global middleware is preprocessed during server boot time.
         *
         * We have to reverse the stack before we use it because when the layers
         * are created it will start with the first item in the array, meaning the
         * first item will be the closest to the core action, which would be wrong as it
         * does not respect the middleware order from how they are assigned in the Kernel
         * and with routes. So we flip the array so that the first item will be the last
         * outer middleware to be executed when our layered are created.
         */
        $middlewareStack = array_reverse($routeMiddlewareStack);

        foreach($middlewareStack as $middleware)
        {
            // By default we start off with no parameters
            $middlewareParams = [];

            /*
             * An array indicates that we have the middleware class path and parameters.
             * If the middleware has no parameters, then it will just be a string of the class path.
             */
            if(is_array($middleware))
            {
                // Get the individual values as $middleware is an array
                [$middleware, $middlewareParams] = $middleware;
            }

            // Create a new class instance that can be used during the current request
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