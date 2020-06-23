<?php

namespace Polyel\Router;

use Closure;
use RuntimeException;
use Polyel\Debug\Debug;
use Polyel\Http\Kernel;
use Polyel\Http\Request;
use Polyel\Http\Response;
use App\Controllers\Controller;
use Polyel\Middleware\Middleware;
use Polyel\Session\SessionManager;

class Router
{
    use RouteVerbs;
    use RouteUtilities;

    // Holds all the request routes to respond to
    private $routes;

    // The last added registered route and its request method
    private $lastAddedRoute;

    // Full list of registered routes that were added
    private $listOfAddedRoutes;

    private $groupStack;

    // The Session Manager service
    private $sessionManager;

    // The Debug service
    private $debug;

    // The Middleware service
    private $middleware;

    public function __construct(SessionManager $sessionManager, Debug $debug, Middleware $middleware)
    {
        $this->sessionManager = $sessionManager;
        $this->debug = $debug;
        $this->middleware = $middleware;
    }

    public function handle(Request $request, Kernel $HttpKernel): Response
    {
        // Check for a HEAD request
        if($request->method === "HEAD")
        {
            // Because HEAD and GET are basically the same, switch a HEAD to act like a GET request
            $request->method = "GET";
        }

        // Get the response from the HTTP Kernel that will be sent back to the client
        $response = $HttpKernel->response;

        // Continue routing if there is a URL
        if(!empty($request->uri))
        {
            // Check if a redirection has been set...
            if(isset($this->routes["REDIRECT"][$request->uri]))
            {
                // Set a redirection to happen when responding
                $redirection = $this->routes["REDIRECT"][$request->uri];
                $response->redirect($redirection["url"], $redirection["statusCode"]);

                // Returning progresses the request to skip to responding directly
                return $response;
            }

            /*
             * Search for a registered route based on the request method and URI.
             * If a route is found, route information is returned, controller, action, parameters and URL.
             * False is returned is no match can be made for the requested route.
             */
            $matchedRoute = $this->getRegisteredRouteFor($request->method, $request->uri);

            // Check if the requested route exists, we continue further into the application...
            if($matchedRoute !== false)
            {
                // Only operate the session system if set to active
                if(config('session.active'))
                {
                    // Check for a valid session and update the session data, create one if one doesn't exist
                    $this->sessionManager->startSession($HttpKernel);

                    // Create the CSRF token if it is missing in the clients session data
                    $HttpKernel->session->createCsrfToken();
                }

                // Set the default HTTP status code, might change throughout the request cycle
                $response->setStatusCode(200);

                // URL route parameters from request
                $routeParams = $matchedRoute['params'];

                /*
                 * The route action is either a a Closure or Controller
                 */
                if($matchedRoute['action'] instanceof Closure)
                {
                    // Get the Closure and set it as the route action
                    $routeAction = $matchedRoute['action'];
                }
                else if(is_string($matchedRoute['action']))
                {
                    // Extract the controller and action and set them so the class has access to them
                    $matchedRoute['action'] = explode("@", $matchedRoute['action']);

                    // Get the current matched controller and route action
                    list($controller, $controllerAction) = $matchedRoute['action'];

                    //The controller namespace and getting its instance from the container using ::call
                    $controllerName = "App\Controllers\\" . $controller;
                    $controller = $HttpKernel->container->resolveClass($controllerName);

                    // Set the route action to the resolved controller
                    $routeAction = $controller;
                }

                // Check that the controller exists
                if(isset($routeAction) && !empty($routeAction))
                {
                    // Capture a response from a before middleware if one returns a response
                    $beforeMiddlewareResponse = $this->middleware->runAnyBefore($HttpKernel, $request->method, $matchedRoute['url']);

                    // If a before middleware wants to return a response early in the app process...
                    if(exists($beforeMiddlewareResponse))
                    {
                        // Build the response from a before middleware and return to halt execution of the app
                        $response->build($beforeMiddlewareResponse);
                        return $response;
                    }

                    /*
                     * The route action is either a a Closure or Controller
                     */
                    if($routeAction instanceof Closure)
                    {
                        // Resolve and perform method injection when calling the Closure
                        $closureDependencies = $HttpKernel->container->resolveClosureDependencies($routeAction);

                        // Method injection for any services first, then route parameters and get the Closure response
                        $applicationResponse = $routeAction(...$closureDependencies, ...$routeParams);
                    }
                    else if($routeAction instanceof Controller)
                    {
                        // Resolve and perform method injection when calling the controller action
                        $methodDependencies = $HttpKernel->container->resolveMethodInjection($controllerName, $controllerAction);

                        // Method injection for any services first, then route parameters and get the Controller response
                        $applicationResponse = $controller->$controllerAction(...$methodDependencies, ...$routeParams);
                    }

                    // Capture a response returned from any after middleware if one returns a response...
                    $afterMiddlewareResponse = $this->middleware->runAnyAfter($HttpKernel, $request->method, $matchedRoute['url']);

                    // After middleware takes priority over the controller when returning a response
                    if(exists($afterMiddlewareResponse))
                    {
                        // If a after middleware wants to return a response, send it off to get built...
                        $response->build($afterMiddlewareResponse);
                    }
                    else
                    {
                        /*
                         * Execution reaches this level when no before or after middleware wants to return a response,
                         * meaning the controller action can return its response for the request that was sent.
                         * Give the response service the response the controller wants to send back to the client
                         */
                        $response->build($applicationResponse);
                    }
                }
            }
            else
            {
                // Error 404 route not found
                $response->build(response(view('404:error'), 404));
            }
        }

        return $response;
    }

    private function addRoute($requestMethod, $route, $action)
    {
        // If the group stack exists, it means we are adding routes from a group Closure
        if(exists($this->groupStack))
        {
            // Add the prefix if one was set
            if(isset($this->groupStack['prefix']))
            {
                $route = $this->groupStack['prefix'] . $route;
            }

            // Get the middlewares if some were set, they are set once the route is registered
            if(isset($this->groupStack['middleware']))
            {
                $groupRouteMiddleware = $this->groupStack['middleware'];
            }
        }

        // Throw an error if trying to add a route that already exists...
        if(in_array($route, $this->listOfAddedRoutes[$requestMethod], true))
        {
            throw new RuntimeException("\e[41m Trying to add a route that already exists: " . $route . " \e[0m");
        }

        /*
         * Validate that the new route is a valid route and if it is using parameters correctly.
         * Routes must be separated with forward slashes and params must not touch each other.
         */
        if(preg_match_all("/^(\/([a-zA-Z-0-9]*|\{[a-z-0-9]+\}))+$/m", $route) === 0)
        {
            throw new RuntimeException("\e[41mInvalid route at:\e[0m '" . $route . "'");
        }

        // Only pack the route when it has more than one parameter
        if(strlen($route) > 1)
        {
            /*
             * Convert a route into a single multidimensional array, making it easier to handle parameters later...
             * The route becomes the multidimensional array where the action is stored.
             */
            $packedRoute = $this->packRoute($route, $action);
        }
        else
        {
            // Support the single index route `/`
            $packedRoute[$route] = $action;
        }

        // Finally the single multidimensional route array is merged into the main routes array
        $this->routes[$requestMethod] = array_merge_recursive($packedRoute, $this->routes[$requestMethod]);

        // All params are moved to the end of their array level because static routes take priority
        $this->shiftAllParamsToTheEnd($this->routes[$requestMethod]);

        // Reset the last added route and store the most recently added route
        $this->lastAddedRoute = null;
        $this->lastAddedRoute[$requestMethod] = $route;

        // Keep a list of all the added routes
        $this->listOfAddedRoutes[$requestMethod][] = $route;

        // Register any group middleware if they exist, they will set on the last added route
        if(isset($groupRouteMiddleware))
        {
            $this->middleware($groupRouteMiddleware);
        }
    }

    private function getRegisteredRouteFor($requestMethod, $requestedRoute)
    {
        // For when the route requested is more than one char, meaning its not the index `/` route
        if(strlen($requestedRoute) > 1)
        {
            $requestedRoute = urldecode($requestedRoute);

            /*
             * Because the route requested is more than one char, it means we have a route that is not the
             * index `route` so it needs to be processed and matched to a registered route in order to
             * process further into the application. Here we prepare the requested route into segments and trim any
             * right `/` chars. Only trim the right side of the route because we don't want to fix invalid routes
             * like '//admin' to '/admin' for the client and cause invalid routes to still match correctly.
             *
             * The requested route is segmented so its easy to loop through and find a match...
             */
            $segmentedRequestedRoute = explode("/", rtrim($requestedRoute, "/"));

            /*
             * Remove the first proceeding forward slash from the URL as explode treats '/' as empty.
             * We remove the first '/' to compensate for the first slash from the URL but not anymore. If a route
             * contains two or more slashes, these will not process or match properly.
             */
            array_shift($segmentedRequestedRoute);
        }
        else
        {
            // Else we check if the index route has been requested
            if($requestedRoute === "/")
            {
                // Index route requested, no need process a one char route, perform it manually instead
                $segmentedRequestedRoute[] = "/";
            }

            // Catch undefined requests
            if(!isset($requestedRoute))
            {
                // The requested route is null
                return false;
            }
        }

        // Try and match the requested route to a registered route, false is returned when no match is found
        $routeRequested = $this->matchRoute($this->routes[$requestMethod], $segmentedRequestedRoute);

        // If a route is found, the controller and action is returned, along with any set params
        if($routeRequested)
        {
            $matchedRoute['url'] = $routeRequested["regURL"];
            $matchedRoute['action'] = $routeRequested["action"];
            $matchedRoute['params'] = $routeRequested["params"];

            // A route match was made...
            return $matchedRoute;
        }

        // If no route can be matched to a registered route
        return false;
    }

    /*
     * Group a set of routes using specific attributes like prefix and middleware
     */
    public function group($attributes, Closure $routes)
    {
        /*
         * Using a closure, set the attributes first and then
         * run the closure which should be a group of routes. Each route in
         * the closure will use the attributes set in the group stack. The group
         * stack is cleared once the group call has finished.
         */
        if($routes instanceof Closure)
        {
            // Set the group stack of attributes for the group to use when registering new routes in the group
            $this->groupStack = $attributes;

            // Run the set of grouped rotues
            $routes();
        }

        // Clear the group stack so other routes don't conflict with attributes in this group
        $this->groupStack = null;
    }

    public function middleware($middlewareKeys)
    {
        $requestMethod = array_key_first($this->lastAddedRoute);
        $routeUri = $this->lastAddedRoute[$requestMethod];
        $this->middleware->register($requestMethod, $routeUri, $middlewareKeys);
    }

    public function redirect($src, $des, $statusCode = 302)
    {
        // Register a new redirection with its URL and status code
        $this->routes["REDIRECT"][$src]["url"] = $des;
        $this->routes["REDIRECT"][$src]["statusCode"] = $statusCode;
    }

    public function loadRoutes()
    {
        $this->initialiseHttpVerbs();

        // Load web routes...
        require ROOT_DIR . "/app/routing/web.php";

        // Load api routes...
        require ROOT_DIR . "/app/routing/api.php";
    }
}
