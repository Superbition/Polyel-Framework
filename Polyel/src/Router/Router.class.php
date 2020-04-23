<?php

namespace Polyel\Router;

use Polyel;
use Exception;
use Polyel\Debug\Debug;
use Polyel\Http\Request;
use Polyel\Http\Response;
use Polyel\Middleware\Middleware;

class Router
{
    use RouteVerbs;
    use RouteUtilities;

    // The URI pattern the route responds to.
    private $uriSplit;

    // Holds the main requested route from the client
    private $requestedRoute;

    // Holds the current matched registered URL str
    private $currentRegURL;

    // Holds the current matched controller from the registered route
    private $currentController;

    // Holds the current matched route action for the controller
    private $currentRouteAction;

    // Holds the current parameters matched from a registered route
    private $currentRouteParams;

    // Holds the request method sent by the client
    private $requestMethod;

    // Holds all the request routes to respond to
    private $routes;

    // The last added registered route and its request method
    private $lastAddedRoute;

    // Full list of registered routes that were added
    private $listOfAddedRoutes;

    // The Debug service
    private $debug;

    // The Middleware service
    private $middleware;

    // The Request object
    private $request;

    // The Response object
    private $response;

    private $routeParamPattern;

    public function __construct(Debug $debug, Middleware $middleware, Request $request, Response $response)
    {
        $this->debug = $debug;
        $this->middleware = $middleware;
        $this->request = $request;
        $this->response = $response;
    }

    public function handle($request)
    {
        // Get the full URL from the clients request
        $this->requestedRoute = $request->server["request_uri"];

        /*
         * Split the URI into an array based on the delimiter
         * Remove empty array values from the URI because of the delimiters
         * Reindex the array back to 0
         */
        $this->uriSplit = explode("/", $request->server["request_uri"]);
        $this->uriSplit = array_filter($this->uriSplit);
        $this->uriSplit = array_values($this->uriSplit);

        // Get the request method: GET, POST, PUT etc.
        $this->requestMethod = $request->server["request_method"];

        // Check for a HEAD request
        if($this->requestMethod === "HEAD")
        {
            // Because HEAD and GET are basically the same, switch a HEAD to act like a GET request
            $this->requestMethod = "GET";
        }

        // Continue routing if there is a URL
        if(!empty($this->requestedRoute))
        {
            // Check if a redirection has been set...
            if(isset($this->routes["REDIRECT"][$this->requestedRoute]))
            {
                // Set a redirection to happen when responding
                $redirection = $this->routes["REDIRECT"][$this->requestedRoute];
                $this->response->redirect($redirection["url"], $redirection["statusCode"]);

                // Returning progresses the request to skip to responding directly
                return;
            }

            $this->request->capture($request);

            // Check if the route matches any registered routes
            if($this->routeExists($this->requestMethod, $this->requestedRoute))
            {
                // Set the default HTTP status code, might change throughout the request cycle
                $this->response->setStatusCode(200);

                // Get the current matched controller and route action
                $controller = $this->currentController;
                $controllerAction = $this->currentRouteAction;

                //The controller namespace and getting its instance from the container using ::call
                $controllerName = "App\Controllers\\" . $controller;
                $controller = Polyel::call($controllerName);

                // Check that the controller exists
                if(isset($controller) && !empty($controller))
                {
                    $this->middleware->runAnyBefore($this->request, $this->requestMethod, $this->currentRegURL);

                    // Resolve and perform method injection when calling the controller action
                    $methodDependencies = Polyel::resolveMethod($controllerName, $controllerAction);

                    // Method injection for any services first, then route parameters and get the response to send
                    $response = $controller->$controllerAction(...$methodDependencies, ...$this->currentRouteParams);

                    $this->middleware->runAnyAfter($this->response, $this->requestMethod, $this->currentRegURL);

                    // Give the response service the response the controller wants to send back to the client
                    $this->response->build($response);
                }
            }
            else
            {
                // Error 404 route not found
                $this->response->setStatusCode(404);
            }
        }
    }

    public function deliver($response)
    {
        if($this->debug->doDumpsExist())
        {
            // The rendered response but with the debug dumps at the start.
            $response->end($this->debug->getDumps() . "<br>" . Template::render($this->requestedView));

            // Resets the last amount of dumps so duplicates are not shown upon next request.
            $this->debug->cleanup();
        }
        else
        {
            $this->response->send($response);
        }
    }

    private function addRoute($requestMethod, $route, $action)
    {
        // Throw an error if trying to add a route that already exists...
        if(in_array($route, $this->listOfAddedRoutes[$requestMethod]))
        {
            throw new Exception("\e[41m Trying to add a route that already exists: " . $route . " \e[0m");
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
    }

    private function routeExists($requestMethod, $requestedRoute)
    {
        // For when the route requested is more than one char, meaning its not the index `/` route
        if(strlen($requestedRoute) > 1)
        {
            $requestedRoute = urldecode($requestedRoute);

            /*
             * Because the route requested is more than one char, it means we have a route that is not the
             * index `route` so it needs to be processed and matched to a registered route in order to
             * process further into the application. Here we prepare the requested route into segments and trim any
             * left or right `/` chars which would cause an empty element in an array during the matching process of
             * the matching logic. The requested route is segmented so its easy to loop through and find a match...
             */
            $segmentedRequestedRoute = explode("/", rtrim(ltrim($requestedRoute, "/"), "/"));
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
            // Get the built up registered URL that was matched
            $this->currentRegURL = $routeRequested["regURL"];

            // Extract the controller and action and set them so the class has access to them
            $routeRequested["controller"] = explode("@", $routeRequested["controller"]);
            $this->currentController = $routeRequested["controller"][0];
            $this->currentRouteAction = $routeRequested["controller"][1];

            // Give the class access to any route parameters if they were found
            $this->currentRouteParams = $routeRequested["params"];

            // A route match was made...
            return true;
        }

        // If no route can be matched to a registered route
        return false;
    }

    public function getCurrentRoute()
    {
        return $this->requestedRoute;
    }

    public function getCurrentRouteSplit()
    {
        return $this->uriSplit;
    }

    public function getCurrentRouteAction()
    {
        return $this->currentRouteAction;
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

        require ROOT_DIR . "/app/routing/web.php";
    }

    public function setup()
    {
        // Use the param tag from the Router config file, used when detecting params in routes
        $paramTag = explode(" ", config("router.routeParameterTag"));
        $this->routeParamPattern = "/(\\" . $paramTag[0] . "[a-zA-Z_0-9]*\\" . $paramTag[1] . ")/";
    }
}