<?php

namespace Polyel\Router;

use Polyel;
use Polyel\View\View;
use Polyel\Debug\Debug;
use Polyel\Http\Request;
use Polyel\Http\Response;
use Polyel\Middleware\Middleware;

class Router
{
    use RouteVerbs;

    // The URI pattern the route responds to.
    private $uriSplit;

    // Holds the main route name/page
    private $requestedRawRoute;

    private $currentRouteAction;

    // Holds the request method sent by the client
    private $requestMethod;

    // Holds all the request routes to respond to
    private $routes;

    private $lastAddedRoute;

    // Holds the requested view template file name
    private $requestedView;

    private $view;

    private $debug;

    private $middleware;

    private $request;

    private $response;

    public function __construct(View $view, Debug $debug, Middleware $middleware, Request $request, Response $response)
    {
        $this->view = $view;
        $this->debug = $debug;
        $this->middleware = $middleware;
        $this->request = $request;
        $this->response = $response;
    }

    public function handle(&$request)
    {
        // Get the full URL from the clients request
        $this->requestedRawRoute = $this->uriSplit = $request->server["request_uri"];

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

        // Continue routing if there is a URL
        if(!empty($this->requestedRawRoute))
        {
            // Check if the route matches any registered routes
            if($this->routeExists($this->requestMethod, $this->requestedRawRoute))
            {
                $this->requestedView = null;

                // Get the action of the route split based on controller@Action
                $routeAction = $this->getRouteAction($this->requestMethod, $this->requestedRawRoute);

                // Split both the controller and func into separate vars from controller@Action
                $controller = $routeAction[0];
                $controllerAction = $routeAction[1];
                $this->currentRouteAction = $controllerAction;

                //The controller namespace and getting its instance from the container using ::call
                $controllerName = "App\Controllers\\" . $controller;
                $controller = Polyel::call($controllerName);

                // Check that the controller exists
                if(isset($controller) && !empty($controller))
                {
                    $this->middleware->runAnyBefore($this->request, $this->requestMethod, $this->requestedRawRoute);

                    // Resolve and perform method injection when calling the controller action
                    $methodDependencies = Polyel::resolveMethod($controllerName, $controllerAction);
                    $controller->$controllerAction(...$methodDependencies);

                    $this->middleware->runAnyAfter($this->response, $this->requestMethod, $this->requestedRawRoute);
                }
            }
            else
            {
                // Error 404 route not found
                $this->requestedView = __DIR__ . "/../../../app/views/errors/404.html";
            }
        }
    }

    public function deliver(&$response)
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
            $response->end($this->view->render($this->requestedView));
        }
    }

    public function addRoute($requestMethod, $route, $action)
    {
        /*
         * Split the route into segments based on a '/'
         * The URL is split into segments so route params can be processed.
         * array_filter is used to remove the first empty '/'
         * array_values is used to reindex the array back to 0 from previous step
         * array_pack, a Polyel helper, is used to pack the segments into one single multidimensional array
         * The outcome of array_pack is for example: /blog/user/{post_id} = controller@Action
         */
        $routeSegments = explode("/", $route);
        $routeSegments = array_filter($routeSegments);
        $routeSegments = array_values($routeSegments);
        $routeSegments = array_pack($routeSegments, $action);

        // Finally the single multidimensional route array is merged into the main routes array
        $this->routes[$requestMethod] = array_merge_recursive($routeSegments, $this->routes[$requestMethod]);
        $this->lastAddedRoute[$requestMethod] = $route;
    }

    public function routeExists($requestMethod, $requestedRoute)
    {
        if(array_key_exists($requestedRoute, $this->routes[$requestMethod]))
        {
            return true;
        }

        return false;
    }

    private function getRouteAction($requestMethod, $requestedRoute)
    {
        // Each route will have a controller and func it wants to call
        return explode("@", $this->routes[$requestMethod][$requestedRoute]);
    }

    public function getCurrentRawRoute()
    {
        return $this->requestedRawRoute;
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

    public function loadRoutes()
    {
        $this->initialiseHttpVerbs();

        require __DIR__ . "/../../../app/routes.php";
    }
}