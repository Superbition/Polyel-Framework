<?php

namespace Polyel\Router;

use Polyel;
use Exception;
use Polyel\Debug\Debug;
use Polyel\Http\Kernel;
use Polyel\Http\Request;
use Polyel\Http\Response;
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

    // The Session Manager service
    private $sessionManager;

    // The Debug service
    private $debug;

    // The Middleware service
    private $middleware;

    private $routeParamPattern;

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
                    $this->sessionManager->startSession($request, $response);
                }

                // Set the default HTTP status code, might change throughout the request cycle
                $response->setStatusCode(200);

                // URL route parameters from request
                $routeParams = $matchedRoute['params'];

                // Get the current matched controller and route action
                $controller = $matchedRoute['controller'];
                $controllerAction = $matchedRoute['action'];

                //The controller namespace and getting its instance from the container using ::call
                $controllerName = "App\Controllers\\" . $controller;
                $controller = $HttpKernel->container->resolveClass($controllerName);

                // Check that the controller exists
                if(isset($controller) && !empty($controller))
                {
                    // Capture a response from a before middleware if one returns a response
                    $beforeMiddlewareResponse = $this->middleware->runAnyBefore($this->request, $this->requestMethod, $this->currentRegURL);

                    // If a before middleware wants to return a response early in the app process...
                    if(exists($beforeMiddlewareResponse))
                    {
                        // Build the response from a before middleware and return to halt execution of the app
                        $this->response->build($beforeMiddlewareResponse);
                        return;
                    }

                    // Resolve and perform method injection when calling the controller action
                    $methodDependencies = Polyel::resolveMethod($controllerName, $controllerAction);

                    // Method injection for any services first, then route parameters and get the controller response
                    $controllerResponse = $controller->$controllerAction(...$methodDependencies, ...$this->currentRouteParams);

                    // Capture a response returned from any after middleware if one returns a response...
                    $afterMiddlewareResponse = $this->middleware->runAnyAfter($this->request, $this->response, $this->requestMethod, $this->currentRegURL);

                    // After middleware takes priority over the controller when returning a response
                    if(exists($afterMiddlewareResponse))
                    {
                        // If a after middleware wants to return a response, send it off to get built...
                        $this->response->build($afterMiddlewareResponse);
                    }
                    else
                    {
                        /*
                         * Execution reaches this level when no before or after middleware wants to return a response,
                         * meaning the controller action can return its response for the request that was sent.
                         * Give the response service the response the controller wants to send back to the client
                         */
                        $this->response->build($controllerResponse);
                    }
                }
            }
            else
            {
                // Error 404 route not found
                $this->response->build(response(view('404:error'), 404));
            }
        }

        return $response;
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
            // Extract the controller and action and set them so the class has access to them
            $routeRequested["controller"] = explode("@", $routeRequested["controller"]);

            $matchedRoute['url'] = $routeRequested["regURL"];
            $matchedRoute['controller'] = $routeRequested["controller"][0];
            $matchedRoute['params'] = $routeRequested["params"];
            $matchedRoute['action'] = $routeRequested["controller"][1];

            // A route match was made...
            return $matchedRoute;
        }

        // If no route can be matched to a registered route
        return false;
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